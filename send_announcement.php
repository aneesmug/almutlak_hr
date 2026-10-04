<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/page_access_helper.php';

// Same rule the sidebar (includes/main_menu.php) applies: system admin, a role allowed in
// App Settings > Page Access, or the 'access_send_announcement' Special Access grant.
// Checked here as well because the form is processed before the sidebar guard runs.
$canSendAnnouncement = page_role_allowed($conDB, 'send_announcement.php', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'access_send_announcement', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canSendAnnouncement) {
    header('Location: ./dashboard.php');
    exit();
}

/**
 * Get the next circular number (auto-increment).
 */
function get_next_circular_number(mysqli $conDB): string
{
    $result = mysqli_query($conDB, "SELECT MAX(CAST(circular_no AS UNSIGNED)) AS max_no FROM announcement_broadcasts");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $maxNo = (int)($row['max_no'] ?? 0);
        return str_pad((string)($maxNo + 1), 3, '0', STR_PAD_LEFT);
    }
    return '001';
}

/**
 * Convert announcement HTML to PNG binary using headless Edge.
 */
function render_announcement_to_image(array $data, string $logoUrl = ''): string
{
    $tempDir = sys_get_temp_dir();
    $htmlContent = build_announcement_email_html($data, $logoUrl);
    $uniqId = uniqid('announcement_', true);
    $tempHtmlFile = $tempDir . DIRECTORY_SEPARATOR . $uniqId . '.html';
    $tempImageFile = $tempDir . DIRECTORY_SEPARATOR . $uniqId . '.png';
    $edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

    if (!file_put_contents($tempHtmlFile, $htmlContent) || !is_file($edgePath)) {
        return '';
    }

    $fileUrl = 'file:///' . str_replace(['\\', ' '], ['/', '%20'], $tempHtmlFile);
    $command = escapeshellarg($edgePath)
        . ' --headless=new --disable-gpu --hide-scrollbars --virtual-time-budget=3000'
        . ' --window-size=1200,1600 --screenshot=' . escapeshellarg($tempImageFile)
        . ' ' . escapeshellarg($fileUrl) . ' 2>&1';
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);

    @unlink($tempHtmlFile);

    if ($return_var === 0 && file_exists($tempImageFile)) {
        $imageData = file_get_contents($tempImageFile);
        @unlink($tempImageFile);
        return $imageData !== false ? $imageData : '';
    }

    return '';
}

/**
 * Decode a posted data URL image into raw binary.
 */
function decode_posted_announcement_image(string $imageDataUrl): string
{
    $imageDataUrl = trim($imageDataUrl);
    if ($imageDataUrl === '' || strpos($imageDataUrl, 'data:image/png;base64,') !== 0) {
        return '';
    }

    $base64 = substr($imageDataUrl, strlen('data:image/png;base64,'));
    $binary = base64_decode($base64, true);

    return $binary !== false ? $binary : '';
}


/**
 * Decode the page images of a scanned (attachment) announcement. The browser converts
 * the chosen PDF or image into one JPEG data URL per page before posting, so only real
 * JPEG data is accepted here.
 */
function decode_posted_scan_pages(array $dataUrls, int $maxPages = 10): array
{
    $prefix = 'data:image/jpeg;base64,';
    $pages = [];

    foreach ($dataUrls as $dataUrl) {
        if (count($pages) >= $maxPages) {
            break;
        }
        if (!is_string($dataUrl) || strpos($dataUrl, $prefix) !== 0) {
            continue;
        }

        $binary = base64_decode(substr($dataUrl, strlen($prefix)), true);
        if ($binary === false || $binary === '') {
            continue;
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false || ($info['mime'] ?? '') !== 'image/jpeg') {
            continue;
        }

        $pages[] = $binary;
    }

    return $pages;
}

/**
 * Keep a copy of the scanned pages that were emailed, under uploads/announcements/.
 * Returns the stored relative paths.
 */
function store_announcement_scan_pages(string $circularNo, array $pages): array
{
    $relativeDir = 'uploads/announcements/';
    $dir = __DIR__ . '/' . $relativeDir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return [];
    }

    $safeNo = preg_replace('/[^A-Za-z0-9_-]/', '', $circularNo);
    $stamp = date('Ymd_His');
    $files = [];

    foreach (array_values($pages) as $index => $binary) {
        $name = 'circular_' . ($safeNo !== '' ? $safeNo : 'x') . '_' . $stamp . '_p' . ($index + 1) . '.jpg';
        if (file_put_contents($dir . $name, $binary) !== false) {
            $files[] = $relativeDir . $name;
        }
    }

    return $files;
}

/**
 * Read the stored pages of a sent scanned announcement (announcement_broadcasts.scan_files)
 * back as JPEG data URLs, so a loaded circular shows its pages and can be sent again
 * without re-selecting the file.
 */
function load_announcement_scan_pages(string $scanFilesJson): array
{
    $files = json_decode($scanFilesJson, true);
    if (!is_array($files)) {
        return [];
    }

    $pages = [];
    foreach ($files as $file) {
        // Only ever read from the announcements folder, whatever path the row holds.
        $path = __DIR__ . '/uploads/announcements/' . basename((string)$file);
        if (!is_file($path)) {
            continue;
        }

        $binary = file_get_contents($path);
        $info = $binary !== false ? @getimagesizefromstring($binary) : false;
        if ($info === false || ($info['mime'] ?? '') !== 'image/jpeg') {
            continue;
        }

        $pages[] = 'data:image/jpeg;base64,' . base64_encode($binary);
    }

    return $pages;
}

/**
 * Build sanitized dynamic bilingual content rows.
 */
function normalize_announcement_blocks(array $enBlocks, array $arBlocks): array
{
    $rows = [];
    $maxCount = max(count($enBlocks), count($arBlocks));

    // The rich editor posts markup like "<p><br></p>" for an empty row - treat tag-only content as empty.
    $isBlank = static function (string $html): bool {
        return trim(str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'))) === '';
    };

    for ($i = 0; $i < $maxCount; $i++) {
        $en = trim((string)($enBlocks[$i] ?? ''));
        $ar = trim((string)($arBlocks[$i] ?? ''));
        if ($isBlank($en)) {
            $en = '';
        }
        if ($isBlank($ar)) {
            $ar = '';
        }

        if ($en === '' && $ar === '') {
            continue;
        }

        $rows[] = [
            'en' => $en,
            'ar' => $ar
        ];
    }

    return $rows;
}

/**
 * Collapse several content rows into the single English/Arabic pair the form now edits.
 * Circulars saved before the rich editor had one row per paragraph - each becomes a
 * paragraph of the merged content.
 */
function merge_announcement_blocks(array $blocks): array
{
    if (count($blocks) <= 1) {
        $only = reset($blocks);
        return [
            'en' => (string)($only['en'] ?? ''),
            'ar' => (string)($only['ar'] ?? '')
        ];
    }

    $merged = ['en' => '', 'ar' => ''];
    foreach ($blocks as $block) {
        foreach (['en', 'ar'] as $lang) {
            $part = trim((string)($block[$lang] ?? ''));
            if ($part === '') {
                continue;
            }
            if (!preg_match('#<(p|div|ul|ol|li|table|h[1-6]|blockquote|pre)\b#i', $part)) {
                $part = '<p>' . nl2br($part, false) . '</p>';
            }
            $merged[$lang] .= $part;
        }
    }

    return $merged;
}

/**
 * Allow a safe subset of inline HTML for announcement content.
 */
function sanitize_announcement_html_fragment(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $html = str_replace(["\r\n", "\r"], "\n", $html);
    // Plain-text rows keep their line breaks; editor HTML already carries its own block tags.
    if (!preg_match('#<(p|div|ul|ol|li|table|h[1-6]|blockquote|pre)\b#i', $html)) {
        $html = nl2br($html, false);
    }

    // Remove dangerous container tags completely.
    $html = preg_replace('#<(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta)[^>]*>.*?</\1>#is', '', $html) ?? '';
    $html = preg_replace('#</?(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta)[^>]*>#is', '', $html) ?? '';

    // Keep only formatting-oriented tags.
    $allowedTags = '<b><strong><i><em><u><br><p><ul><ol><li><span><div><h1><h2><h3><h4><h5><h6><blockquote><pre><table><thead><tbody><tr><th><td><a>';
    $html = strip_tags($html, $allowedTags);

    // Remove event-handler attributes like onclick.
    $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

    // Allow only a controlled href protocol and safe link attributes.
    $html = preg_replace_callback(
        '/<a\b([^>]*)>/i',
        static function (array $matches): string {
            $attr = (string)($matches[1] ?? '');
            $href = '#';
            $target = '';

            if (preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attr, $hrefMatch)) {
                $candidate = trim((string)($hrefMatch[2] ?? $hrefMatch[3] ?? $hrefMatch[4] ?? ''));
                if (preg_match('#^(https?://|mailto:|#)#i', $candidate)) {
                    $href = htmlspecialchars($candidate, ENT_QUOTES, 'UTF-8');
                }
            }

            if (preg_match('/target\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attr, $targetMatch)) {
                $candidateTarget = strtolower(trim((string)($targetMatch[2] ?? $targetMatch[3] ?? $targetMatch[4] ?? '')));
                if (in_array($candidateTarget, ['_blank', '_self'], true)) {
                    $target = $candidateTarget;
                }
            }

            $tag = '<a href="' . $href . '"';
            if ($target !== '') {
                $tag .= ' target="' . $target . '"';
                if ($target === '_blank') {
                    $tag .= ' rel="noopener noreferrer"';
                }
            }
            $tag .= '>';

            return $tag;
        },
        $html
    ) ?? '';

    return $html;
}

/**
 * Validate and normalize announcement link URL.
 */
function sanitize_announcement_link_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (!preg_match('#^(https?://|mailto:)#i', $url)) {
        $url = 'https://' . $url;
    }

    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

/**
 * Convert dynamic rows to HTML.
 */
function render_announcement_blocks_html(array $blocks, string $lang): string
{
    $isArabic = strtolower($lang) === 'ar';
    $html = '';

    foreach ($blocks as $block) {
        $raw = $isArabic ? (string)($block['ar'] ?? '') : (string)($block['en'] ?? '');
        if (trim($raw) === '') {
            continue;
        }

        $html .= '<div class="paragraph">' . sanitize_announcement_html_fragment($raw) . '</div>';
    }

    return $html;
}

/**
 * Build bilingual mirrored announcement email HTML.
 */
function build_announcement_email_html(array $data, string $logoUrl = ''): string
{
    $logoSafe = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');

    $circularNo = htmlspecialchars($data['circular_no'] ?? '', ENT_QUOTES, 'UTF-8');
    $dateText = htmlspecialchars($data['issue_date'] ?? '', ENT_QUOTES, 'UTF-8');
    $toEn = nl2br(htmlspecialchars($data['to_en'] ?? '', ENT_QUOTES, 'UTF-8'));
    $toAr = nl2br(htmlspecialchars($data['to_ar'] ?? '', ENT_QUOTES, 'UTF-8'));

    $subjectEn = nl2br(htmlspecialchars($data['subject_en'] ?? '', ENT_QUOTES, 'UTF-8'));
    $subjectAr = nl2br(htmlspecialchars($data['subject_ar'] ?? '', ENT_QUOTES, 'UTF-8'));

    $footerEn = nl2br(htmlspecialchars($data['footer_en'] ?? '', ENT_QUOTES, 'UTF-8'));
    $footerAr = nl2br(htmlspecialchars($data['footer_ar'] ?? '', ENT_QUOTES, 'UTF-8'));

    $blocks = is_array($data['content_blocks'] ?? null) ? $data['content_blocks'] : [];
    $blocksEnHtml = render_announcement_blocks_html($blocks, 'en');
    $blocksArHtml = render_announcement_blocks_html($blocks, 'ar');

    $displayYear = date('Y');
    if (!empty($data['issue_date'])) {
        $convertedDate = DateTime::createFromFormat('d-m-Y', (string)$data['issue_date']);
        if ($convertedDate !== false) {
            $displayYear = $convertedDate->format('Y');
        }
    }

    return '
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin: 0; background: #f2f2f2; font-family: Arial, Tahoma, sans-serif; color: #202020; }
        .sheet-wrap { padding: 24px 10px; }
        .sheet { max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid #d4d4d4; }
        .head { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; border-bottom: 3px solid #5c5c5c; padding: 18px 20px; }
        .head-left { font-weight: 700; color: #25256e; font-size: 28px; line-height: 1.1; }
        .head-right { font-weight: 700; color: #25256e; font-size: 28px; line-height: 1.2; text-align: right; direction: rtl; }
        .head-logo img { max-height: 86px; }
        .meta { background: #646464; color: #fff; text-align: center; padding: 8px 20px; }
        .meta .line { margin: 3px 0; font-size: 15px; font-weight: 700; }
        .to-row { display: grid; grid-template-columns: 1fr 1fr; background: #838383; color: #fff; border-top: 1px solid #666; border-bottom: 1px solid #666; }
        .to-row > div { padding: 10px 14px; font-size: 14px; font-weight: 700; }
        .to-row .ar { text-align: right; direction: rtl; border-left: 1px solid #666; }
        .content { position: relative; display: grid; grid-template-columns: 1fr 1fr; }
        .content::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url(' . ($logoSafe !== '' ? ('"' . $logoSafe . '"') : 'none') . ');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 58%;
            opacity: 0.06;
            pointer-events: none;
        }
        .col { position: relative; z-index: 1; min-height: 460px; padding: 18px 16px 24px; font-size: 16px; line-height: 1.55; }
        .col-en { border-right: 1px solid #6d6d6d; }
        .col-ar { text-align: right; direction: rtl; }
        .date { font-weight: 700; margin-bottom: 16px; }
        .subject { font-size: 29px; font-weight: 700; margin: 10px 0 18px; }
        .paragraph { margin-bottom: 16px; }
        .paragraph p { margin: 0 0 10px; }
        .paragraph p:last-child { margin-bottom: 0; }
        .paragraph table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .paragraph th, .paragraph td { border: 1px solid #8a8a8a; padding: 5px 8px; }
        .footer { border-top: 3px solid #5c5c5c; text-align: center; color: #25256e; font-weight: 700; padding: 16px; }
        @media only screen and (max-width: 820px) {
            .head { grid-template-columns: 1fr; gap: 10px; text-align: center; }
            .head-right { text-align: center; }
            .to-row, .content { grid-template-columns: 1fr; }
            .to-row .ar, .col-en { border-left: 0; border-right: 0; border-top: 1px solid #666; }
        }
    </style>
</head>
<body>
    <div class="sheet-wrap">
        <div class="sheet">
            <div class="head">
                <div class="head-left">Almutlak Trade &amp;<br>Industries Holding Co.</div>
                <div class="head-logo">' . ($logoSafe !== '' ? '<img src="' . $logoSafe . '" alt="logo">' : '') . '</div>
                <div class="head-right">شركة المطلق<br>للتجارة والصناعة القابضة</div>
            </div>

            <div class="meta">
                <div class="line">Circular No: ' . $circularNo . ' in ' . $displayYear . '</div>
                <div class="line">تعميم رقم ' . $circularNo . ' لعام ' . $displayYear . 'م</div>
            </div>

            <div class="to-row">
                <div class="en">To: ' . $toEn . '</div>
                <div class="ar">إلى: ' . $toAr . '</div>
            </div>

            <div class="content">
                <div class="col col-en">
                    <div class="date">Date: ' . $dateText . '</div>
                    <div class="subject">' . $subjectEn . '</div>
                    ' . $blocksEnHtml . '
                </div>
                <div class="col col-ar">
                    <div class="date">التاريخ: ' . $dateText . '</div>
                    <div class="subject">' . $subjectAr . '</div>
                    ' . $blocksArHtml . '
                </div>
            </div>

            <div class="footer">
                <div>' . $footerEn . '</div>
                <div>' . $footerAr . '</div>
            </div>
        </div>
    </div>
</body>
</html>';
}

/**
 * Send a single announcement email using dedicated announcement SMTP credentials.
 *
 * $embeddedImage is either the PNG binary of the rendered text announcement (shown in
 * the body as cid:announcement_preview), or a list of inline images for a scanned
 * announcement: [['cid' => ..., 'data' => ..., 'filename' => ..., 'mime' => ...], ...].
 */
function send_announcement_email(mysqli $conDB, string $toEmail, string $toName, string $subject, string $htmlBody, $embeddedImage = ''): bool
{
    global $announcementLastMailError;
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('ANNOUNCEMENT_EMAIL_ERROR: PHPMailer class not found');
        $announcementLastMailError = 'PHPMailer class not found';
        return false;
    }

    // Values come from App Settings -> Email -> Announcement Config.
    $cfg = [];
    $cfgRes = mysqli_query($conDB, "SELECT setting_name, setting_value FROM app_settings WHERE setting_name LIKE 'announcement_smtp_%'");
    if ($cfgRes) {
        while ($cfgRow = mysqli_fetch_assoc($cfgRes)) {
            $cfg[$cfgRow['setting_name']] = trim((string)$cfgRow['setting_value']);
        }
    }
    $smtp_host = $cfg['announcement_smtp_host'] ?? '';
    $smtp_port = $cfg['announcement_smtp_port'] ?? '';
    $smtp_user = $cfg['announcement_smtp_user'] ?? '';
    $smtp_pass = $cfg['announcement_smtp_pass'] ?? '';
    $smtp_from_email = $cfg['announcement_smtp_from_email'] ?? '';
    $smtp_from_name = $cfg['announcement_smtp_from_name'] ?? '';
    $smtp_secure = $cfg['announcement_smtp_encryption'] ?? 'tls';

    if (
        empty($smtp_host) || empty($smtp_port) || empty($smtp_user) ||
        empty($smtp_pass) || empty($smtp_from_email) || empty($smtp_from_name)
    ) {
        error_log('ANNOUNCEMENT_EMAIL_ERROR: Missing SMTP configuration - host: ' . (empty($smtp_host) ? 'EMPTY' : 'OK') . 
                  ', port: ' . (empty($smtp_port) ? 'EMPTY' : 'OK') . ', user: ' . (empty($smtp_user) ? 'EMPTY' : 'OK'));
        $announcementLastMailError = 'SMTP not configured. Fill App Settings > Email > Announcement Config.';
        return false;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;

        switch (strtolower((string)$smtp_secure)) {
            case 'tls':
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                break;
            case 'ssl':
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                break;
            default:
                $mail->SMTPSecure = false;
                break;
        }

        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 15;
        $mail->SMTPDebug = 0; // Set to 2 or 3 for debugging

        // Log SMTP configuration being used
        error_log('ANNOUNCEMENT_SMTP_CONFIG: host=' . $smtp_host . ', port=' . $smtp_port . ', security=' . strtolower((string)$smtp_secure) . ', user=' . $smtp_user);

        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($smtp_from_email, $smtp_from_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        if (is_array($embeddedImage)) {
            foreach ($embeddedImage as $inlineImage) {
                $mail->addStringEmbeddedImage($inlineImage['data'], $inlineImage['cid'], $inlineImage['filename'], 'base64', $inlineImage['mime']);
            }
        } elseif ($embeddedImage !== '') {
            $mail->addStringEmbeddedImage($embeddedImage, 'announcement_preview', 'announcement.png', 'base64', 'image/png');
        }

        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        if ($mail->send()) {
            return true;
        } else {
            error_log('ANNOUNCEMENT_EMAIL_ERROR [' . $toEmail . ']: Send failed - ' . $mail->ErrorInfo);
            $announcementLastMailError = $mail->ErrorInfo;
            return false;
        }
    } catch (Throwable $e) {
        error_log('ANNOUNCEMENT_EMAIL_ERROR [' . $toEmail . ']: Exception - ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')');
        $announcementLastMailError = $e->getMessage();
        return false;
    }
}

// Recipient list and the "Other" option come from App Settings > Email > Announcement Recipients.
$announcementRecipientSettings = get_announcement_recipient_settings($conDB);
$announcementGroups = $announcementRecipientSettings['recipients'];
$allowOtherRecipient = $announcementRecipientSettings['allow_other'];
$selectedRecipientMode = '';
// 'text' = announcement written in the editors; 'attachment' = signed scan (PDF/image) sent as the body.
$announcementType = 'text';
// Scan pages (JPEG data URLs) to put back in the form: from a loaded circular, or the ones just posted.
$initialScanPages = [];
$initialScanLabel = '';
$messageHtml = '';
$messageType = '';

// On initial GET request, default to first recipient group
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['load_circular_no'])) {
    $selectedRecipientMode = (string)(array_key_first($announcementGroups) ?? ($allowOtherRecipient ? 'other' : ''));
}

/**
 * Save announcement to database.
 */
function save_announcement_to_db(mysqli $conDB, array $formData, string $selectedRecipientMode, int $sentCount = 0, string $announcementType = 'text', array $scanFiles = []): bool
{
    $createTableSql = "CREATE TABLE IF NOT EXISTS announcement_broadcasts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        circular_no VARCHAR(100) NOT NULL,
        issue_date DATE NULL,
        to_en TEXT NULL,
        to_ar TEXT NULL,
        subject_en TEXT NULL,
        subject_ar TEXT NULL,
        body_en MEDIUMTEXT NULL,
        body_ar MEDIUMTEXT NULL,
        footer_en TEXT NULL,
        footer_ar TEXT NULL,
        content_blocks_json LONGTEXT NULL,
        recipient_mode VARCHAR(20) NOT NULL DEFAULT 'company',
        recipients_count INT NOT NULL DEFAULT 0,
        sent_success_count INT NOT NULL DEFAULT 0,
        created_by_emp_id VARCHAR(50) NULL,
        created_by_name VARCHAR(255) NULL,
        is_draft TINYINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conDB, $createTableSql);

    $checkColRes = mysqli_query($conDB, "SHOW COLUMNS FROM announcement_broadcasts LIKE 'content_blocks_json'");
    if ($checkColRes && mysqli_num_rows($checkColRes) === 0) {
        mysqli_query($conDB, "ALTER TABLE announcement_broadcasts ADD COLUMN content_blocks_json LONGTEXT NULL AFTER footer_ar");
    }

    $checkDraftCol = mysqli_query($conDB, "SHOW COLUMNS FROM announcement_broadcasts LIKE 'is_draft'");
    if ($checkDraftCol && mysqli_num_rows($checkDraftCol) === 0) {
        mysqli_query($conDB, "ALTER TABLE announcement_broadcasts ADD COLUMN is_draft TINYINT NOT NULL DEFAULT 0");
    }

    $checkTypeCol = mysqli_query($conDB, "SHOW COLUMNS FROM announcement_broadcasts LIKE 'announcement_type'");
    if ($checkTypeCol && mysqli_num_rows($checkTypeCol) === 0) {
        mysqli_query($conDB, "ALTER TABLE announcement_broadcasts ADD COLUMN announcement_type VARCHAR(20) NOT NULL DEFAULT 'text', ADD COLUMN scan_files TEXT NULL");
    }

    // Backward-compatible migration: allow empty subjects at DB schema level.
    $subjectEnCol = mysqli_query($conDB, "SHOW COLUMNS FROM announcement_broadcasts LIKE 'subject_en'");
    if ($subjectEnCol) {
        $subjectEnMeta = mysqli_fetch_assoc($subjectEnCol);
        if (is_array($subjectEnMeta) && strtoupper((string)($subjectEnMeta['Null'] ?? 'YES')) === 'NO') {
            mysqli_query($conDB, "ALTER TABLE announcement_broadcasts MODIFY subject_en TEXT NULL");
        }
    }

    $subjectArCol = mysqli_query($conDB, "SHOW COLUMNS FROM announcement_broadcasts LIKE 'subject_ar'");
    if ($subjectArCol) {
        $subjectArMeta = mysqli_fetch_assoc($subjectArCol);
        if (is_array($subjectArMeta) && strtoupper((string)($subjectArMeta['Null'] ?? 'YES')) === 'NO') {
            mysqli_query($conDB, "ALTER TABLE announcement_broadcasts MODIFY subject_ar TEXT NULL");
        }
    }

    $issueDateSql = null;
    if ($formData['issue_date'] !== '') {
        $converted = DateTime::createFromFormat('d-m-Y', $formData['issue_date']);
        if ($converted !== false) {
            $issueDateSql = $converted->format('Y-m-d');
        }
    }

    $blocksJson = json_encode($formData['content_blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $bodyEnLegacy = implode("\n\n", array_map(static function ($row) {
        return (string)($row['en'] ?? '');
    }, $formData['content_blocks']));
    $bodyArLegacy = implode("\n\n", array_map(static function ($row) {
        return (string)($row['ar'] ?? '');
    }, $formData['content_blocks']));

    $insertSql = "INSERT INTO announcement_broadcasts
        (circular_no, issue_date, to_en, to_ar, subject_en, subject_ar, body_en, body_ar,
         footer_en, footer_ar, content_blocks_json, recipient_mode,
         recipients_count, sent_success_count, created_by_emp_id, created_by_name, is_draft,
         announcement_type, scan_files)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conDB, $insertSql);
    if ($stmt) {
        $creatorEmpId = (string)($GLOBALS['empid'] ?? '');
        $creatorName = (string)($GLOBALS['fname'] ?? '');
        $isDraft = $sentCount === 0 ? 1 : 0;
        $recipientsCount = 0;
        $scanFilesJson = !empty($scanFiles) ? json_encode(array_values($scanFiles), JSON_UNESCAPED_SLASHES) : null;

        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssssiississ',
            $formData['circular_no'],
            $issueDateSql,
            $formData['to_en'],
            $formData['to_ar'],
            $formData['subject_en'],
            $formData['subject_ar'],
            $bodyEnLegacy,
            $bodyArLegacy,
            $formData['footer_en'],
            $formData['footer_ar'],
            $blocksJson,
            $selectedRecipientMode,
            $recipientsCount,
            $sentCount,
            $creatorEmpId,
            $creatorName,
            $isDraft,
            $announcementType,
            $scanFilesJson
        );
        return mysqli_stmt_execute($stmt);
    }
    return false;
}

$defaults = [
    'circular_no' => '',
    'issue_date' => date('d-m-Y'),
    'to_en' => 'Directors of Factories, Showrooms and Warehouses',
    'to_ar' => 'السادة / مدراء المصانع والمعارض والمستودعات',
    'subject_en' => 'Dear colleagues/General Administration staff',
    'subject_ar' => 'السلام / موظفي الادارة العامة المحترمين',
    'announcement_link_url' => '',
    'announcement_link_text' => 'Open Announcement Link',
    'footer_en' => 'Shared Services - HR Department',
    'footer_ar' => 'الخدمات المشتركة - قسم الموارد البشرية',
    'content_blocks' => [
        [
            'en' => '<p>In light of expected weather fluctuations and rainfall, all employees are requested to complete their tasks remotely from home.</p>'
                . '<p>We hope everyone will adhere to this announcement and follow upcoming updates.</p>',
            'ar' => '<p>إشارة إلى التقلبات الجوية المتوقعة وما يصاحبها من هطول الأمطار، نأمل من جميع الموظفين إنجاز أعمالهم عن بعد من المنزل.</p>'
                . '<p>نأمل من الجميع التقيد بهذا التعميم ومتابعة أي تحديثات لاحقة.</p>'
        ]
    ]
];

$formData = $defaults;

// Fetch recent announcements for the search picker — one row per circular_no, newest first.
$recentAnnouncements = [];
$recentAnnouncementsRes = mysqli_query($conDB, "SELECT circular_no, subject_en, issue_date FROM announcement_broadcasts GROUP BY circular_no ORDER BY MAX(id) DESC LIMIT 20");
if ($recentAnnouncementsRes) {
    while ($recentAnnouncementsRow = mysqli_fetch_assoc($recentAnnouncementsRes)) {
        $recentAnnouncements[] = $recentAnnouncementsRow;
    }
}

// All circulars (for the "View all" Swal picker), newest first. One row per circular_no.
$allCirculars = [];
$allCircularsRes = mysqli_query($conDB, "SELECT a.circular_no, a.subject_en, a.issue_date, a.recipient_mode, a.sent_success_count, a.is_draft FROM announcement_broadcasts a INNER JOIN (SELECT MAX(id) AS mid FROM announcement_broadcasts GROUP BY circular_no) m ON a.id = m.mid ORDER BY a.id DESC");
if ($allCircularsRes) {
    while ($allCircularsRow = mysqli_fetch_assoc($allCircularsRes)) {
        $allCirculars[] = [
            'no' => (string)$allCircularsRow['circular_no'],
            'subject' => (string)($allCircularsRow['subject_en'] ?? ''),
            'date' => !empty($allCircularsRow['issue_date']) && strtotime((string)$allCircularsRow['issue_date']) ? date('d-m-Y', strtotime((string)$allCircularsRow['issue_date'])) : '',
            'to' => (string)($allCircularsRow['recipient_mode'] ?? ''),
            'draft' => (int)($allCircularsRow['is_draft'] ?? 0) === 1,
        ];
    }
}

// Handle loading a previous announcement by Circular No.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['load_circular_no'])) {
    $loadCircularNo = trim((string)$_GET['load_circular_no']);
    $loadStmt = mysqli_prepare($conDB, "SELECT * FROM announcement_broadcasts WHERE circular_no = ? ORDER BY id DESC LIMIT 1");
    if ($loadStmt) {
        mysqli_stmt_bind_param($loadStmt, 's', $loadCircularNo);
        mysqli_stmt_execute($loadStmt);
        $loadResult = mysqli_stmt_get_result($loadStmt);
        $loadedRow = $loadResult ? mysqli_fetch_assoc($loadResult) : null;

        if ($loadedRow) {
            $loadedBlocks = [];
            if (!empty($loadedRow['content_blocks_json'])) {
                $decodedBlocks = json_decode((string)$loadedRow['content_blocks_json'], true);
                if (is_array($decodedBlocks)) {
                    $loadedBlocks = $decodedBlocks;
                }
            }
            if (empty($loadedBlocks)) {
                $legacyEn = trim((string)($loadedRow['body_en'] ?? ''));
                $legacyAr = trim((string)($loadedRow['body_ar'] ?? ''));
                if ($legacyEn !== '' || $legacyAr !== '') {
                    $loadedBlocks = [['en' => $legacyEn, 'ar' => $legacyAr]];
                }
            }

            $newCircularNo = get_next_circular_number($conDB);
            $formData = [
                'circular_no'            => $newCircularNo,
                'issue_date'             => date('d-m-Y'),
                'to_en'                  => (string)($loadedRow['to_en'] ?? $defaults['to_en']),
                'to_ar'                  => (string)($loadedRow['to_ar'] ?? $defaults['to_ar']),
                'subject_en'             => (string)($loadedRow['subject_en'] ?? $defaults['subject_en']),
                'subject_ar'             => (string)($loadedRow['subject_ar'] ?? $defaults['subject_ar']),
                'announcement_link_url'  => '',
                'announcement_link_text' => 'Open Announcement Link',
                'footer_en'              => (string)($loadedRow['footer_en'] ?? $defaults['footer_en']),
                'footer_ar'             => (string)($loadedRow['footer_ar'] ?? $defaults['footer_ar']),
                'content_blocks'         => !empty($loadedBlocks) ? $loadedBlocks : $defaults['content_blocks'],
            ];
            $selectedRecipientMode = (string)($loadedRow['recipient_mode'] ?? '');
            $announcementType = (string)($loadedRow['announcement_type'] ?? 'text') === 'attachment' ? 'attachment' : 'text';
            if ($announcementType === 'attachment') {
                $initialScanPages = load_announcement_scan_pages((string)($loadedRow['scan_files'] ?? ''));
                $initialScanLabel = 'Circular #' . $loadCircularNo;
            }
            $messageType = 'info';
            $messageHtml = 'Loaded circular #' . htmlspecialchars($loadCircularNo, ENT_QUOTES, 'UTF-8') . '. New Circular No set to <strong>' . htmlspecialchars($newCircularNo, ENT_QUOTES, 'UTF-8') . '</strong>. Modify the content and send.';
        } else {
            $messageType = 'warning';
            $messageHtml = 'No announcement found with Circular No: ' . htmlspecialchars($loadCircularNo, ENT_QUOTES, 'UTF-8') . '.';
            $formData['circular_no'] = get_next_circular_number($conDB);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $formData['circular_no'] === '') {
    $formData['circular_no'] = get_next_circular_number($conDB);
}

// A post bigger than PHP's post_max_size arrives with an empty $_POST - say so instead of silently reloading.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $messageType = 'danger';
    $messageHtml = 'The announcement was too large for the server to accept (limit ' . htmlspecialchars((string)ini_get('post_max_size'), ENT_QUOTES, 'UTF-8') . '). Use a smaller file or fewer pages.';
    $formData['circular_no'] = get_next_circular_number($conDB);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (
    isset($_POST['send_announcement']) ||
    isset($_POST['save_draft']) ||
    in_array((string)($_POST['form_action'] ?? ''), ['send_announcement', 'save_draft'], true)
)) {
    foreach (['circular_no', 'issue_date', 'to_en', 'to_ar', 'subject_en', 'subject_ar', 'announcement_link_url', 'announcement_link_text', 'footer_en', 'footer_ar'] as $field) {
        $formData[$field] = trim((string)($_POST[$field] ?? ''));
    }

    $formData['content_blocks'] = normalize_announcement_blocks(
        (array)($_POST['block_en'] ?? []),
        (array)($_POST['block_ar'] ?? [])
    );

    $announcementType = (string)($_POST['announcement_type'] ?? 'text') === 'attachment' ? 'attachment' : 'text';
    $scanPages = $announcementType === 'attachment'
        ? decode_posted_scan_pages((array)($_POST['scan_pages'] ?? []))
        : [];
    // Keep the selected pages in the form after the page reloads (the file input itself cannot be refilled).
    foreach ($scanPages as $scanPageBinary) {
        $initialScanPages[] = 'data:image/jpeg;base64,' . base64_encode($scanPageBinary);
    }
    $initialScanLabel = 'Selected file';

    $selectedRecipientMode = (string)($_POST['recipient_mode'] ?? '');
    $otherRecipientEmail = trim((string)($_POST['other_email'] ?? ''));
    if ($selectedRecipientMode === 'other') {
        if ($allowOtherRecipient && filter_var($otherRecipientEmail, FILTER_VALIDATE_EMAIL)) {
            $announcementGroups['other'] = ['name' => 'Other', 'email' => $otherRecipientEmail];
        } else {
            $selectedRecipientMode = '';
        }
    } elseif (!isset($announcementGroups[$selectedRecipientMode])) {
        $selectedRecipientMode = '';
    }

    $formAction = (string)($_POST['form_action'] ?? '');
    $isSavingDraft = isset($_POST['save_draft']) || $formAction === 'save_draft';
    $isSending = isset($_POST['send_announcement']) || $formAction === 'send_announcement';

    $required = ['circular_no', 'issue_date'];
    $missing = [];
    foreach ($required as $field) {
        if ($formData[$field] === '') {
            $missing[] = $field;
        }
    }

    if ($announcementType === 'attachment') {
        if (empty($scanPages)) {
            $missing[] = 'attachment file';
        }
    } elseif (empty($formData['content_blocks'])) {
        $missing[] = 'content_blocks';
    }

    if ($selectedRecipientMode === '') {
        $missing[] = 'recipient_mode';
    }

    if (!empty($missing)) {
        $messageType = 'danger';
        $messageHtml = 'Please fill all required fields and ' . ($announcementType === 'attachment' ? 'select the announcement file' : 'write the announcement content') . ' before ' . ($isSending ? 'sending' : 'saving') . '. Missing: ' . htmlspecialchars(implode(', ', $missing), ENT_QUOTES, 'UTF-8');
    } else {
        if ($isSavingDraft && $announcementType === 'attachment') {
            // The scanned pages only exist in the browser until they are sent - there is nothing to reload later.
            $messageType = 'danger';
            $messageHtml = 'Drafts are only available for text announcements. Select the file and send it directly.';
        } elseif ($isSavingDraft) {
            if (save_announcement_to_db($conDB, $formData, $selectedRecipientMode, 0)) {
                if (class_exists('ActivityLogger')) {
                    ActivityLogger::logCreate(
                        'Announcement',
                        'send_announcement.php',
                        (string)($formData['circular_no'] ?? ''),
                        [
                            'subject_en' => $formData['subject_en'],
                            'subject_ar' => $formData['subject_ar'],
                            'recipient_mode' => $selectedRecipientMode,
                            'dynamic_blocks_count' => count($formData['content_blocks'])
                        ],
                        'Saved draft announcement circular ' . $formData['circular_no']
                    );
                }
                $messageType = 'success';
                $messageHtml = 'Announcement saved as draft. (ID: ' . htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') . ')';
            } else {
                $messageType = 'danger';
                $messageHtml = 'Failed to save announcement draft.';
            }
        } elseif ($isSending) {
            error_log('ANNOUNCEMENT_DEBUG: Starting announcement send for circular ' . ($formData['circular_no'] ?? 'unknown'));
            error_log('ANNOUNCEMENT_DEBUG: Selected recipient mode: ' . $selectedRecipientMode);
            error_log('ANNOUNCEMENT_DEBUG: POST recipient_mode: ' . (isset($_POST['recipient_mode']) ? $_POST['recipient_mode'] : 'NOT SET'));
            error_log('ANNOUNCEMENT_DEBUG: Available groups: ' . implode(', ', array_keys($announcementGroups)));
            
            $selectedGroup = $announcementGroups[$selectedRecipientMode] ?? null;
            $recipients = [];
            
            error_log('ANNOUNCEMENT_DEBUG: Selected group is: ' . ($selectedGroup ? 'FOUND' : 'NOT FOUND'));
            
            if (is_array($selectedGroup)) {
                $recipients[] = [
                    'email' => (string)($selectedGroup['email'] ?? ''),
                    'recipient_name' => (string)($selectedGroup['name'] ?? 'Announcement Group')
                ];
            }

            error_log('ANNOUNCEMENT_DEBUG: Recipients collected: ' . count($recipients) . ' group(s)');
            foreach ($recipients as $idx => $rec) {
                error_log('ANNOUNCEMENT_DEBUG: Recipient ' . $idx . ': ' . ($rec['email'] ?? 'EMPTY') . ' (' . ($rec['recipient_name'] ?? 'unknown') . ')');
            }

            if (empty($recipients)) {
                error_log('ANNOUNCEMENT_ERROR: No recipients found');
                $messageType = 'danger';
                $messageHtml = 'No recipients found with valid email addresses.';
            } else {
                $mailSubject = implode(' | ', array_filter([$formData['subject_en'], $formData['subject_ar']], 'strlen'));
                if ($mailSubject === '') {
                    $mailSubject = 'Circular No ' . $formData['circular_no'];
                }
                $logo = get_setting($conDB, 'logo');
                $imageBinary = $announcementType === 'attachment'
                    ? ''
                    : decode_posted_announcement_image((string)($_POST['announcement_image_data'] ?? ''));
                $linkUrl = sanitize_announcement_link_url((string)($formData['announcement_link_url'] ?? ''));
                $linkTextRaw = trim((string)($formData['announcement_link_text'] ?? ''));
                $linkText = $linkTextRaw !== '' ? $linkTextRaw : 'Open Announcement Link';
                $linkHtml = '';

                if ($linkUrl !== '') {
                    $linkHtml = '<div style="padding:14px 16px 22px;text-align:center;background:#f2f2f2;">'
                        . '<a href="' . htmlspecialchars($linkUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" '
                        . 'style="display:inline-block;background:#1f4b8f;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:4px;font-weight:700;">'
                        . htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8')
                        . '</a></div>';
                }

                if ($announcementType === 'attachment') {
                    // Signed scan: every page is shown inline in the body, in order - never as an attachment.
                    $embeddedImages = [];
                    $pagesHtml = '';
                    foreach ($scanPages as $pageIndex => $pageBinary) {
                        $pageCid = 'announcement_page_' . ($pageIndex + 1);
                        $embeddedImages[] = [
                            'cid' => $pageCid,
                            'data' => $pageBinary,
                            'filename' => 'announcement-page-' . ($pageIndex + 1) . '.jpg',
                            'mime' => 'image/jpeg'
                        ];
                        $pagesHtml .= '<img src="cid:' . $pageCid . '" alt="Announcement" style="display:block;max-width:100%;width:100%;height:auto;border:0;margin:0 0 8px;" />';
                    }
                    error_log('ANNOUNCEMENT_DEBUG: Scanned announcement, ' . count($scanPages) . ' page(s)');
                    $emailBody = '<html><body style="margin:0;padding:0;background:#f2f2f2;">' . $pagesHtml . $linkHtml . '</body></html>';
                } else {
                    if ($imageBinary === '') {
                        error_log('ANNOUNCEMENT_DEBUG: No embedded image provided, rendering from announcement HTML');
                        $imageBinary = render_announcement_to_image($formData, (string)$logo);
                    }

                    // Fallback: if image generation failed, send text-based HTML email instead
                    if ($imageBinary === '') {
                        error_log('ANNOUNCEMENT_WARNING: Image rendering failed, using text fallback for circular ' . ($formData['circular_no'] ?? 'unknown'));
                        $emailBody = build_announcement_email_html($formData, (string)$logo) . $linkHtml;
                    } else {
                        error_log('ANNOUNCEMENT_DEBUG: Image rendered successfully, size: ' . strlen($imageBinary) . ' bytes');
                        $emailBody = '<html><body style="margin:0;padding:0;background:#f2f2f2;"><img src="cid:announcement_preview" alt="Announcement" style="display:block;max-width:100%;width:100%;height:auto;border:0;" />' . $linkHtml . '</body></html>';
                    }
                    $embeddedImages = $imageBinary;
                }

                error_log('ANNOUNCEMENT_DEBUG: Email body prepared, length: ' . strlen($emailBody) . ' bytes');

                $sentSuccess = 0;
                $emailAttemptCount = 0;
                $announcementLastMailError = '';
                foreach ($recipients as $rec) {
                    $toEmail = trim((string)($rec['email'] ?? ''));
                    $toName = trim((string)($rec['recipient_name'] ?? 'Colleague'));

                    error_log('ANNOUNCEMENT_DEBUG: Processing recipient: email=' . $toEmail . ', name=' . $toName);

                    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                        error_log('ANNOUNCEMENT_DEBUG: Email validation FAILED for: ' . $toEmail);
                        continue;
                    }

                    error_log('ANNOUNCEMENT_DEBUG: Email validation PASSED for: ' . $toEmail . ', attempting to send...');
                    $emailAttemptCount++;
                    
                    if (send_announcement_email($conDB, $toEmail, $toName, $mailSubject, $emailBody, $embeddedImages)) {
                        error_log('ANNOUNCEMENT_DEBUG: Email send SUCCESS for: ' . $toEmail);
                        $sentSuccess++;
                    } else {
                        error_log('ANNOUNCEMENT_DEBUG: Email send FAILED for: ' . $toEmail);
                    }
                }

                error_log('ANNOUNCEMENT_DEBUG: Send complete - attempted: ' . $emailAttemptCount . ', successful: ' . $sentSuccess);

                // Only save to database if at least one email was sent successfully
                if ($sentSuccess > 0) {
                    $scanFiles = $announcementType === 'attachment'
                        ? store_announcement_scan_pages((string)$formData['circular_no'], $scanPages)
                        : [];
                    if (save_announcement_to_db($conDB, $formData, $selectedRecipientMode, $sentSuccess, $announcementType, $scanFiles)) {
                        if (class_exists('ActivityLogger')) {
                            ActivityLogger::logCreate(
                                'Announcement',
                                'send_announcement.php',
                                (string)($formData['circular_no'] ?? ''),
                                [
                                    'subject_en' => $formData['subject_en'],
                                    'subject_ar' => $formData['subject_ar'],
                                    'recipient_mode' => $selectedRecipientMode,
                                    'recipients_count' => count($recipients),
                                    'sent_success_count' => $sentSuccess,
                                    'announcement_type' => $announcementType,
                                    'dynamic_blocks_count' => count($formData['content_blocks'])
                                ],
                                'Sent bilingual announcement circular ' . $formData['circular_no']
                            );
                        }
                    }
                } else {
                    error_log('ANNOUNCEMENT_ERROR: Email send completely failed - not saving to database. Circular: ' . ($formData['circular_no'] ?? 'unknown'));
                }

                $messageType = $sentSuccess > 0 ? 'success' : 'warning';
                $messageHtml = 'Announcement sent to ' . (int)$sentSuccess . ' recipient(s). (ID: ' . htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') . ')';
                
                // Debug: append info if no recipients were processed
                if ($sentSuccess === 0) {
                    $debugInfo = 'selectedRecipientMode="' . htmlspecialchars($selectedRecipientMode, ENT_QUOTES, 'UTF-8') . '", ';
                    $debugInfo .= 'recipients_count=' . count($recipients) . ', ';
                    $debugInfo .= 'emailAttemptCount=' . $emailAttemptCount . ', ';
                    $debugInfo .= 'mail_error="' . htmlspecialchars((string)$announcementLastMailError, ENT_QUOTES, 'UTF-8') . '", ';
                    $debugInfo .= 'received_POST_recipient_mode="' . htmlspecialchars((string)($_POST['recipient_mode'] ?? 'NOT_SET'), ENT_QUOTES, 'UTF-8') . '"';
                    
                    $messageHtml .= '<br><small class="mt-2">DEBUG: ' . $debugInfo . '</small>';
                    
                    // Also log POST data for analysis
                    error_log('ANNOUNCEMENT_POSTDATA: ' . json_encode([
                        'recipient_mode_post' => $_POST['recipient_mode'] ?? 'NOT_SET',
                        'recipient_mode_var' => $selectedRecipientMode,
                        'recipients_array' => $recipients,
                        'all_post_keys' => array_keys($_POST)
                    ], JSON_UNESCAPED_SLASHES));
                }
            }
        }
    }
}

// The form edits one English + one Arabic content editor; older multi-row circulars are merged into it.
$contentBlock = merge_announcement_blocks((array)($formData['content_blocks'] ?? []));
$formData['content_blocks'] = [$contentBlock];

$previewYear = date('Y');
if (!empty($formData['issue_date'])) {
    $previewDateObj = DateTime::createFromFormat('d-m-Y', (string)$formData['issue_date']);
    if ($previewDateObj !== false) {
        $previewYear = $previewDateObj->format('Y');
    }
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Bilingual Announcement</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/summernote/0.8.20/summernote-bs4.min.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>

    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        /* ---------- New GUI layout ---------- */
        .ann-grid { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 20px; align-items: start; }
        @media (max-width: 1199px) { .ann-grid { grid-template-columns: 1fr; } }
        @media (min-width: 1200px) { .ann-side .sr-card { position: sticky; top: 86px; } }
        .ann-recipients { display: grid; gap: 8px; }
        .sr-form .ann-recipients .sr-choice span { justify-content: flex-start; flex-wrap: wrap; }
        .ann-recipients .sr-choice small { font-weight: 500; color: var(--sr-muted); }
        .ann-scan-status { color: var(--tone-green-fg) !important; font-weight: 600; }
        .ann-scan-status:empty { display: none !important; }
        .ann-actions .sr-card-body { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 12px 18px; }
        .ann-actions .sr-spacer { flex: 1 1 auto; }
        .sr-page .preview-shell { border-color: var(--sr-border); background: var(--sr-surface-3); }
        .circ-pop .sr-table tbody tr { cursor: pointer; }
        .announcement-editor .card-box { border-radius: 14px; }
        /* Summernote - same look as the Memo page (employee_memos.php) */
        .note-editor.note-frame { border-color: #e3e6f0; }
        .note-editor .note-btn-group .btn-light { background-image: none !important; }
        html:not(.app-dark) .note-editor .note-btn-group .btn-light { background-color: #fff !important; color: #334155 !important; border: 1px solid #e3e6f0 !important; }
        html:not(.app-dark) .note-editor .note-btn-group .btn-light:hover,
        html:not(.app-dark) .note-editor .note-btn-group .btn-light.active { background-color: #eef2ff !important; color: #4338ca !important; }
        .note-editor .note-editing-area .note-editable { overflow: auto; word-wrap: break-word; }
        .note-editor:not(.codeview) .note-editing-area .note-codable { display: none; }
        .preview-shell {
            background: #f4f4f4;
            border: 1px solid #cfcfcf;
            border-radius: 12px;
            padding: 12px;
            overflow-x: auto;
        }
        .scan-preview { background: #fff; border: 1px solid #d4d4d4; min-height: 300px; }
        .scan-preview img { display: block; width: 100%; height: auto; border-bottom: 8px solid #f4f4f4; }
        .scan-preview img:last-child { border-bottom: 0; }
        .scan-preview .scan-empty { padding: 110px 20px; text-align: center; color: #8a8a8a; }
        .announcement-sheet {
            min-width: 880px;
            background: #fff;
            border: 1px solid #d4d4d4;
        }
        .announcement-head {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: center;
            border-bottom: 3px solid #5b5b5b;
            padding: 16px;
        }
        .announcement-head .en-title {
            color: #25256e;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.05;
        }
        .announcement-head .ar-title {
            color: #25256e;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.15;
            direction: rtl;
            text-align: right;
        }
        .announcement-head .logo img {
            max-height: 84px;
            width: auto;
        }
        .announcement-meta {
            background: #626262;
            color: #fff;
            text-align: center;
            padding: 9px 12px;
            border-top: 1px solid #585858;
            border-bottom: 1px solid #585858;
            font-weight: 700;
        }
        .announcement-to {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #818181;
            color: #fff;
            border-bottom: 1px solid #666;
        }
        .announcement-to .cell {
            padding: 9px 12px;
            font-size: 14px;
            font-weight: 700;
        }
        .announcement-to .ar {
            direction: rtl;
            text-align: right;
            border-left: 1px solid #666;
        }
        .announcement-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 460px;
            position: relative;
        }
        .announcement-body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: var(--watermark);
            background-repeat: no-repeat;
            background-position: center;
            background-size: 58%;
            opacity: 0.06;
            pointer-events: none;
        }
        .announcement-col {
            position: relative;
            z-index: 1;
            padding: 16px 14px 20px;
            font-size: 16px;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .announcement-col.en { border-right: 1px solid #666; }
        .announcement-col.ar { direction: rtl; text-align: right; }
        .announcement-date { font-weight: 700; margin-bottom: 14px; }
        .announcement-subject { font-size: 26px; line-height: 1.25; font-weight: 700; margin-bottom: 18px; }
        .announcement-block-item { margin-bottom: 14px; }
        .announcement-block-item p { margin: 0 0 10px; }
        .announcement-block-item p:last-child { margin-bottom: 0; }
        .announcement-block-item table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .announcement-block-item th, .announcement-block-item td { border: 1px solid #8a8a8a; padding: 5px 8px; }
        .announcement-footer { border-top: 3px solid #5b5b5b; color: #25256e; text-align: center; font-weight: 700; padding: 14px; }
        @media (max-width: 991px) {
            .announcement-head { grid-template-columns: 1fr; text-align: center; }
            .announcement-head .ar-title { text-align: center; }
        }
    </style>
</head>
<body class="enlarged" data-keep-enlarged="true">
<div id="wrapper">
    <div class="left side-menu">
        <div class="slimscroll-menu" id="remove-scroll">
            <div class="topbar-left">
                <a href="dashboard.php" class="logo">
                    <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                    <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                </a>
            </div>
            <?php include('./includes/main_menu.php'); ?>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="content-page">
        <?php include('./includes/topbar.php'); ?>

        <div class="content sr-page">
            <div class="container-fluid announcement-editor">
                <div class="sr-head">
                    <div>
                        <h1>Bilingual Announcement Sender</h1>
                        <p>Create and send mirrored circular announcements side by side (English / العربية).</p>
                    </div>
                    <div class="sr-head-actions">
                        <button type="button" id="btnViewAllCirculars" class="sr-btn"><i class="fa fa-list"></i> View All Circulars</button>
                    </div>
                </div>

                <!-- Load Previous Announcement -->
                <div class="sr-card">
                    <form method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" class="sr-toolbar">
                        <h2 class="sr-card-title"><i class="fa fa-history"></i> Load Previous Announcement</h2>
                        <div class="sr-toolbar-right">
                            <div class="sr-search" style="flex: 0 1 240px;">
                                <i class="mdi mdi-pound"></i>
                                <input type="text" name="load_circular_no" placeholder="Circular No - e.g. 001" list="recentCircularsList" autocomplete="off"
                                    value="<?= htmlspecialchars((string)($_GET['load_circular_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <datalist id="recentCircularsList">
                                    <?php foreach ($recentAnnouncements as $recentItem): ?>
                                        <option value="<?= htmlspecialchars($recentItem['circular_no'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars('#' . $recentItem['circular_no'] . ' — ' . mb_substr((string)($recentItem['subject_en'] ?? ''), 0, 40), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <button type="submit" class="sr-btn"><i class="fa fa-search"></i> Load &amp; Reuse</button>
                        </div>
                    </form>
                </div>

                <?php if ($messageHtml !== ''): ?>
                    <?php $annTone = ['success' => 'tone-green', 'danger' => 'tone-red', 'warning' => 'tone-amber'][$messageType] ?? 'tone-sky'; ?>
                    <div class="sr-notice <?= $annTone ?>">
                        <i class="mdi mdi-information-outline"></i>
                        <div><?= $messageHtml ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" id="announcementForm" class="sr-form" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="announcement_image_data" id="announcementImageData" value="">
                    <input type="hidden" name="form_action" id="formAction" value="">
                    <div class="ann-grid">
                        <div>
                            <div class="sr-card">
                                <div class="sr-card-body">
                                    <div class="sr-fsec">
                                        <div class="sr-fsec-head"><span><i class="mdi mdi-format-list-bulleted"></i> Announcement Type</span></div>
                                        <div class="sr-fgrid">
                                            <div class="sr-fcol c-12">
                                                <div class="sr-attach-choice">
                                                    <label class="sr-choice"><input type="radio" id="announcement_type_text" name="announcement_type" value="text" <?= $announcementType === 'text' ? 'checked' : '' ?>><span><i class="mdi mdi-format-text"></i> Text body</span></label>
                                                    <label class="sr-choice"><input type="radio" id="announcement_type_attachment" name="announcement_type" value="attachment" <?= $announcementType === 'attachment' ? 'checked' : '' ?>><span><i class="mdi mdi-paperclip"></i> Signed scan (PDF / image)</span></label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sr-fsec">
                                        <div class="sr-fsec-head"><span><i class="mdi mdi-file-document"></i> Circular</span></div>
                                        <div class="sr-fgrid">
                                            <div class="sr-fcol c-6">
                                                <label>Circular No <span class="text-danger">*</span></label>
                                                <input type="text" name="circular_no" class="form-control js-bind" data-bind="circular_no" required value="<?= htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-6">
                                                <label>Date (dd-mm-yyyy) <span class="text-danger">*</span></label>
                                                <input type="text" name="issue_date" class="form-control js-bind" data-bind="issue_date" required value="<?= htmlspecialchars($formData['issue_date'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-6 js-text-only">
                                                <label>To (English)</label>
                                                <input type="text" name="to_en" class="form-control js-bind" data-bind="to_en" value="<?= htmlspecialchars($formData['to_en'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-6 js-text-only">
                                                <label>إلى (Arabic)</label>
                                                <input type="text" name="to_ar" class="form-control js-bind" data-bind="to_ar" dir="rtl" value="<?= htmlspecialchars($formData['to_ar'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-6">
                                                <label>Subject (English) <span class="text-danger">*</span></label>
                                                <input type="text" name="subject_en" class="form-control js-bind" data-bind="subject_en" value="<?= htmlspecialchars($formData['subject_en'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-6">
                                                <label>العنوان (Arabic) <span class="text-danger">*</span></label>
                                                <input type="text" name="subject_ar" class="form-control js-bind" data-bind="subject_ar" dir="rtl" value="<?= htmlspecialchars($formData['subject_ar'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-7">
                                                <label>Announcement Link URL</label>
                                                <input type="text" name="announcement_link_url" class="form-control js-bind" data-bind="announcement_link_url" placeholder="https://example.com" value="<?= htmlspecialchars($formData['announcement_link_url'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-5">
                                                <label>Link Button Text</label>
                                                <input type="text" name="announcement_link_text" class="form-control js-bind" data-bind="announcement_link_text" value="<?= htmlspecialchars($formData['announcement_link_text'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sr-fsec js-attachment-only" style="display:none;">
                                        <div class="sr-fsec-head"><span><i class="mdi mdi-paperclip"></i> Announcement File *</span></div>
                                        <div class="sr-fgrid">
                                            <div class="sr-fcol c-12">
                                                <label class="sr-filepick" for="scanFile">
                                                    <input type="file" id="scanFile" accept="application/pdf,.pdf,image/*">
                                                    <i class="mdi mdi-cloud-upload"></i>
                                                    <span><b>Choose the signed PDF or image</b>
                                                    <small id="scanStatus" class="ann-scan-status"></small>
                                                    <small>Shown in the email body as a picture, not as an attachment - a PDF becomes one picture per page (up to 10 pages). The subject above is used as the email subject.</small></span>
                                                </label>
                                                <button type="button" id="scanClearBtn" class="sr-btn sr-btn-sm sr-btn-ghost text-danger mt-2" style="display:none;"><i class="fa fa-trash"></i> Remove file</button>
                                                <div id="scanPagesInputs"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sr-fsec js-text-only">
                                        <div class="sr-fsec-head"><span><i class="mdi mdi-format-align-left"></i> Content *</span></div>
                                        <div class="sr-fgrid">
                                            <div class="sr-fcol c-12">
                                                <span class="sr-fhint mt-0 mb-2">Write the whole announcement in each editor - press Enter for a new paragraph and use the toolbar for headings, bold, lists, alignment, tables and links.</span>
                                                <div id="contentBlock">
                                                    <div class="mb-3">
                                                        <label>English Content</label>
                                                        <textarea name="block_en[]" rows="8" class="form-control js-block-en js-rich-editor"><?= htmlspecialchars($contentBlock['en'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                    </div>
                                                    <div>
                                                        <label>Arabic Content</label>
                                                        <textarea name="block_ar[]" rows="8" class="form-control js-block-ar js-rich-editor"><?= htmlspecialchars($contentBlock['ar'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="sr-fcol c-6">
                                                <label>Footer (English)</label>
                                                <input type="text" name="footer_en" class="form-control js-bind" data-bind="footer_en" value="<?= htmlspecialchars($formData['footer_en'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="sr-fcol c-6">
                                                <label>التذييل (Arabic)</label>
                                                <input type="text" name="footer_ar" class="form-control js-bind" data-bind="footer_ar" dir="rtl" value="<?= htmlspecialchars($formData['footer_ar'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sr-fsec mb-0">
                                        <div class="sr-fsec-head"><span><i class="mdi mdi-account-multiple"></i> Recipients</span></div>
                                        <div class="sr-fgrid">
                                            <div class="sr-fcol c-12">
                                                <div class="ann-recipients">
                                                    <?php foreach ($announcementGroups as $recipientKey => $recipientGroup): ?>
                                                        <?php if ($recipientKey === 'other') { continue; } ?>
                                                        <label class="sr-choice"><input type="radio" id="recipient_<?= htmlspecialchars((string)$recipientKey, ENT_QUOTES, 'UTF-8') ?>" name="recipient_mode" value="<?= htmlspecialchars((string)$recipientKey, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedRecipientMode === (string)$recipientKey ? 'checked' : '' ?>><span><i class="mdi mdi-email-outline"></i> <b><?= htmlspecialchars($recipientGroup['name'], ENT_QUOTES, 'UTF-8') ?></b> <small><?= htmlspecialchars($recipientGroup['email'], ENT_QUOTES, 'UTF-8') ?></small></span></label>
                                                    <?php endforeach; ?>
                                                    <?php if ($allowOtherRecipient): ?>
                                                        <label class="sr-choice"><input type="radio" id="recipient_other" name="recipient_mode" value="other" <?= $selectedRecipientMode === 'other' ? 'checked' : '' ?>><span><i class="mdi mdi-flask-outline"></i> <b>Other</b> <small>enter an email for testing</small></span></label>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($allowOtherRecipient): ?>
                                                    <input type="email" id="other_email" name="other_email" class="form-control mt-2" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['other_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="display:<?= $selectedRecipientMode === 'other' ? 'block' : 'none' ?>;">
                                                <?php endif; ?>
                                                <?php if (count(array_diff_key($announcementGroups, ['other' => true])) === 0 && !$allowOtherRecipient): ?>
                                                    <div class="sr-notice tone-red mt-2 mb-0"><i class="mdi mdi-alert-circle-outline"></i><div>No recipients are set up yet.</div></div>
                                                <?php endif; ?>
                                                <span class="sr-fhint">This list is managed in App Settings &gt; Email &gt; Announcement Recipients.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ann-side">
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h2 class="sr-card-title"><i class="mdi mdi-eye-outline"></i> Live Preview</h2>
                                    <span class="sr-card-sub">This is how the email will look</span>
                                </div>
                                <div class="sr-card-body">
                                    <div class="preview-shell ad-keep">
                                        <div class="announcement-sheet" id="announcementPreview" style="--watermark: url('<?= htmlspecialchars((string)get_setting($conDB, 'logo'), ENT_QUOTES, 'UTF-8') ?>');">
                                            <div class="announcement-head">
                                                <div class="en-title">Almutlak Trade &amp;<br>Industries Holding Co.</div>
                                                <div class="logo">
                                                    <img src="<?= htmlspecialchars((string)get_setting($conDB, 'logo'), ENT_QUOTES, 'UTF-8') ?>" alt="logo">
                                                </div>
                                                <div class="ar-title">شركة المطلق<br>للتجارة والصناعة القابضة</div>
                                            </div>

                                            <div class="announcement-meta">
                                                <div id="pvCircularEn">Circular No: <?= htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') ?> in <?= htmlspecialchars($previewYear, ENT_QUOTES, 'UTF-8') ?></div>
                                                <div id="pvCircularAr">تعميم رقم <?= htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') ?> لعام <?= htmlspecialchars($previewYear, ENT_QUOTES, 'UTF-8') ?>م</div>
                                            </div>

                                            <div class="announcement-to">
                                                <div class="cell" id="pvToEn">To: <?= htmlspecialchars($formData['to_en'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="cell ar" id="pvToAr">إلى: <?= htmlspecialchars($formData['to_ar'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>

                                            <div class="announcement-body">
                                                <div class="announcement-col en">
                                                    <div class="announcement-date" id="pvDateEn">Date: <?= htmlspecialchars($formData['issue_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="announcement-subject" id="pvSubjectEn"><?= htmlspecialchars($formData['subject_en'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div id="pvBlocksEn">
                                                        <?php foreach (($formData['content_blocks'] ?? []) as $block): ?>
                                                            <?php if (trim((string)($block['en'] ?? '')) !== ''): ?>
                                                                <div class="announcement-block-item"><?= sanitize_announcement_html_fragment((string)$block['en']) ?></div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="announcement-col ar">
                                                    <div class="announcement-date" id="pvDateAr">التاريخ: <?= htmlspecialchars($formData['issue_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="announcement-subject" id="pvSubjectAr"><?= htmlspecialchars($formData['subject_ar'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div id="pvBlocksAr">
                                                        <?php foreach (($formData['content_blocks'] ?? []) as $block): ?>
                                                            <?php if (trim((string)($block['ar'] ?? '')) !== ''): ?>
                                                                <div class="announcement-block-item"><?= sanitize_announcement_html_fragment((string)$block['ar']) ?></div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="announcement-footer">
                                                <div id="pvFooterEn"><?= htmlspecialchars($formData['footer_en'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div id="pvFooterAr"><?= htmlspecialchars($formData['footer_ar'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div id="pvAnnouncementLink" class="mt-2"></div>
                                            </div>
                                        </div>
                                        <div class="scan-preview" id="scanPreview" style="display:none;">
                                            <div class="scan-empty">Select a PDF or image to see how it will look in the email.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sr-card ann-actions">
                        <div class="sr-card-body">
                            <a href="dashboard.php" class="sr-btn sr-btn-ghost"><i class="fa fa-angle-double-left"></i> Back</a>
                            <span class="sr-spacer"></span>
                            <button type="submit" name="save_draft" class="sr-btn js-text-only"><i class="fa fa-save"></i> Save as Draft</button>
                            <button type="submit" name="send_announcement" class="sr-btn sr-btn-primary"><i class="fa fa-paper-plane"></i> Send Announcement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <footer class="footer"><?= $site_footer ?></footer>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script src="./plugins/summernote/0.8.20/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function() {
    var isCapturingPreview = false;
    var submitAction = '';
    var pageMessageType = <?= json_encode($messageType) ?>;
    var pageMessageHtml = <?= json_encode($messageHtml) ?>;

    function showSubmitLoader(actionName) {
        if (typeof Swal !== 'function') {
            return;
        }

        Swal.fire({
            title: actionName === 'send_announcement' ? 'Sending announcement...' : 'Saving draft...',
            text: actionName === 'send_announcement' ? 'Please wait while the email image is prepared and sent.' : 'Please wait while the draft is saved.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });
    }

    function showResultMessage() {
        if (!pageMessageType || !pageMessageHtml || typeof Swal !== 'function') {
            return;
        }

        var icon = 'info';
        if (pageMessageType === 'success') {
            icon = 'success';
        } else if (pageMessageType === 'danger') {
            icon = 'error';
        } else if (pageMessageType === 'warning') {
            icon = 'warning';
        }

        Swal.fire({
            icon: icon,
            title: pageMessageType === 'success' ? 'Completed' : 'Status',
            text: pageMessageHtml,
            confirmButtonText: 'OK'
        });
    }

    $(document).on('change', 'input[name="recipient_mode"]', function () {
        var isOther = $('#recipient_other').is(':checked');
        $('#other_email').toggle(isOther).prop('required', isOther);
        if (isOther) { $('#other_email').focus(); }
    });

    var allCirculars = <?= json_encode($allCirculars, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    function escHtml(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }

    $('#btnViewAllCirculars').on('click', function () {
        if (typeof Swal !== 'function') { return; }
        var rows = allCirculars.map(function (c) {
            var badge = c.draft ? '<span class="sr-pill sr-pill-xs tone-amber"><span class="sr-dot"></span>Draft</span>' : '<span class="sr-pill sr-pill-xs tone-green"><span class="sr-dot"></span>Sent</span>';
            return '<tr class="circ-row" data-no="' + escHtml(c.no) + '" style="cursor:pointer;">' +
                '<td><span class="sr-chip sr-mono">#' + escHtml(c.no) + '</span></td><td class="sr-date">' + escHtml(c.date) + '</td>' +
                '<td class="text-left">' + escHtml(c.subject) + '</td><td>' + escHtml(c.to) + '</td><td>' + badge + '</td></tr>';
        }).join('');
        if (!rows) { rows = '<tr><td colspan="5"><div class="sr-empty"><i class="mdi mdi-inbox"></i>No circulars found.</div></td></tr>'; }
        Swal.fire({
            title: 'All Circulars',
            width: "75%",
            showConfirmButton: false,
            showCloseButton: true,
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup' },
            html: '<div class="sr-page circ-pop text-left">' +
                '<div class="sr-search mb-2" style="max-width:none;"><i class="mdi mdi-magnify"></i><input type="search" id="circSearch" placeholder="Search by number, subject or date" autocomplete="off"></div>' +
                '<div style="max-height:420px;overflow:auto;border:1px solid var(--sr-border);border-radius:12px;"><table class="sr-table"><thead><tr><th>No</th><th>Date</th><th>Subject</th><th>To</th><th>Status</th></tr></thead><tbody id="circBody">' + rows + '</tbody></table></div>' +
                '<span class="sr-fhint mt-2">Click a circular to open it here with a new Circular No.</span></div>',
            didOpen: function () {
                $('#circSearch').on('input', function () {
                    var q = $(this).val().toLowerCase();
                    $('#circBody .circ-row').each(function () {
                        $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
                    });
                }).focus();
                $('#circBody').on('click', '.circ-row', function () {
                    window.location.href = '?load_circular_no=' + encodeURIComponent($(this).data('no'));
                });
            }
        });
    });

    function validateRecipientSelection() {
        var hasRecipient = $('input[name="recipient_mode"]:checked').length > 0;
        if (hasRecipient && $('#recipient_other').is(':checked')) {
            var em = ($('#other_email').val() || '').trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
                alert('Please enter a valid email address for the Other recipient.');
                $('#other_email').focus();
                return false;
            }
        }
        if (hasRecipient) {
            return true;
        }

        if (typeof Swal === 'function') {
            Swal.fire({
                icon: 'warning',
                title: 'Recipient Required',
                text: 'Please select a recipient group before sending or saving.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
            });
        } else {
            alert('Please select a recipient group before sending or saving.');
        }

        var firstRecipient = $('input[name="recipient_mode"]').first();
        if (firstRecipient.length) {
            firstRecipient.focus();
        }

        return false;
    }

    // Same Summernote setup as the Memo page (editorOptions in assets/js/employee_memos.js):
    // Summernote's own icon font doesn't load here, so the toolbar uses the app's Font Awesome.
    function editorOptions(height) {
        var fa = function (name) { return 'fa-solid fa-' + name; };
        return {
            height: height,
            dialogsInBody: true,
            icons: {
                magic: fa('heading'), bold: fa('bold'), italic: fa('italic'), underline: fa('underline'),
                eraser: fa('eraser'), unorderedlist: fa('list-ul'), orderedlist: fa('list-ol'),
                align: fa('align-left'), alignLeft: fa('align-left'), alignCenter: fa('align-center'),
                alignRight: fa('align-right'), alignJustify: fa('align-justify'),
                indent: fa('indent'), outdent: fa('outdent'), table: fa('table'), link: fa('link'),
                unlink: fa('link-slash'), code: fa('code'), caret: fa('caret-down'),
                rowAbove: fa('arrow-up'), rowBelow: fa('arrow-down'), colBefore: fa('arrow-left'),
                colAfter: fa('arrow-right'), rowRemove: fa('minus'), colRemove: fa('minus'), trash: fa('trash'),
                menuCheck: fa('check'), close: fa('xmark'), arrowsAlt: fa('expand')
            },
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ],
            callbacks: {
                onChange: function () { refreshPreview(); }
            }
        };
    }

    function rtlEditor($textarea) {
        $textarea.next('.note-editor').find('.note-editable').attr('dir', 'rtl').css('text-align', 'right');
    }

    function hasBlockTags(html) {
        return /<(p|div|ul|ol|li|table|h[1-6]|blockquote|pre)\b/i.test(html);
    }

    // Turn the English and Arabic textareas into Summernote editors (Arabic one right-to-left).
    function initContentEditors() {
        if (!$.fn.summernote) {
            return;
        }
        $('#contentBlock').find('.js-block-en, .js-block-ar').each(function () {
            var $textarea = $(this);
            if ($textarea.data('summernote')) {
                return;
            }
            // Older circulars were typed as plain text - keep their line breaks in the editor.
            var value = $textarea.val() || '';
            if (value.indexOf('\n') !== -1 && !hasBlockTags(value)) {
                $textarea.val(value.replace(/\r\n?|\n/g, '<br>'));
            }
            $textarea.summernote(editorOptions(260));
            if ($textarea.hasClass('js-block-ar')) {
                rtlEditor($textarea);
            }
        });
    }

    // Editor HTML as it should be previewed and posted ('' when the editor is empty).
    function getBlockHtml($textarea) {
        if (!$textarea.data('summernote')) {
            return $textarea.val() || '';
        }
        return $textarea.summernote('isEmpty') ? '' : $textarea.summernote('code');
    }

    // Write every editor's content back into its textarea before the form posts.
    function syncEditorsToTextareas() {
        $('#contentBlock').find('.js-block-en, .js-block-ar').each(function () {
            $(this).val(getBlockHtml($(this)));
        });
    }

    function sanitizeHtmlForPreview(rawHtml) {
        var allowedTags = {
            b: true,
            strong: true,
            i: true,
            em: true,
            u: true,
            br: true,
            p: true,
            ul: true,
            ol: true,
            li: true,
            span: true,
            div: true,
            h1: true,
            h2: true,
            h3: true,
            h4: true,
            h5: true,
            h6: true,
            blockquote: true,
            pre: true,
            table: true,
            thead: true,
            tbody: true,
            tr: true,
            th: true,
            td: true,
            a: true
        };
        var blockedTags = {
            script: true,
            style: true,
            iframe: true,
            object: true,
            embed: true,
            form: true,
            input: true,
            button: true,
            textarea: true,
            select: true,
            link: true,
            meta: true
        };

        var html = String(rawHtml || '').replace(/\r\n?/g, '\n');
        // Plain-text rows keep their line breaks; editor HTML already carries its own block tags.
        html = hasBlockTags(html) ? html.replace(/>\s*\n\s*</g, '><').replace(/\n/g, ' ') : html.replace(/\n/g, '<br>');
        var template = document.createElement('template');
        template.innerHTML = html;

        function sanitizeNode(node) {
            if (!node || !node.childNodes) {
                return;
            }

            var children = Array.prototype.slice.call(node.childNodes);
            children.forEach(function(child) {
                if (child.nodeType === 1) {
                    var tagName = (child.tagName || '').toLowerCase();

                    if (blockedTags[tagName]) {
                        node.removeChild(child);
                        return;
                    }

                    if (!allowedTags[tagName]) {
                        var textNode = document.createTextNode(child.textContent || '');
                        node.replaceChild(textNode, child);
                        return;
                    }

                    var attrs = Array.prototype.slice.call(child.attributes || []);
                    attrs.forEach(function(attr) {
                        var attrName = (attr.name || '').toLowerCase();
                        var attrValue = String(attr.value || '');

                        if (attrName.indexOf('on') === 0) {
                            child.removeAttribute(attr.name);
                            return;
                        }

                        if (attrName === 'style') {
                            // Keep only the alignment set by the editor's paragraph button.
                            var align = /text-align\s*:\s*(left|right|center|justify)/i.exec(attrValue);
                            if (align) {
                                child.setAttribute('style', 'text-align: ' + align[1].toLowerCase() + ';');
                            } else {
                                child.removeAttribute(attr.name);
                            }
                            return;
                        }

                        if ((tagName === 'td' || tagName === 'th') && (attrName === 'colspan' || attrName === 'rowspan') && /^\d{1,2}$/.test(attrValue)) {
                            return;
                        }

                        if (tagName === 'a' && attrName === 'href') {
                            var safeHref = attrValue.trim();
                            if (!/^(https?:\/\/|mailto:|#)/i.test(safeHref)) {
                                child.setAttribute('href', '#');
                            }
                            return;
                        }

                        if (tagName === 'a' && attrName === 'target') {
                            if (!/^(_blank|_self)$/i.test(attrValue.trim())) {
                                child.removeAttribute(attr.name);
                            }
                            if (/^_blank$/i.test(attrValue.trim())) {
                                child.setAttribute('rel', 'noopener noreferrer');
                            }
                            return;
                        }

                        if (tagName === 'a' && attrName === 'rel') {
                            return;
                        }

                        child.removeAttribute(attr.name);
                    });

                    sanitizeNode(child);
                } else if (child.nodeType === 8) {
                    node.removeChild(child);
                }
            });
        }

        sanitizeNode(template.content);
        return template.innerHTML;
    }

    function getYearFromIssueDate(value) {
        var dateStr = String(value || '').trim();
        var m = dateStr.match(/^(\d{2})-(\d{2})-(\d{4})$/);
        if (m) {
            return m[3];
        }
        return String(new Date().getFullYear());
    }

    function getSafeLinkUrl(value) {
        var raw = String(value || '').trim();
        if (!raw) {
            return '';
        }

        if (!/^(https?:\/\/|mailto:)/i.test(raw)) {
            raw = 'https://' + raw;
        }

        return /^(https?:\/\/|mailto:)/i.test(raw) ? raw : '';
    }

    function renderBlocksPreview() {
        var enText = getBlockHtml($('#contentBlock .js-block-en'));
        var arText = getBlockHtml($('#contentBlock .js-block-ar'));

        var enHtml = $.trim(enText) !== ''
            ? '<div class="announcement-block-item">' + sanitizeHtmlForPreview(enText) + '</div>'
            : '<div class="announcement-block-item text-muted">Add English content...</div>';
        var arHtml = $.trim(arText) !== ''
            ? '<div class="announcement-block-item">' + sanitizeHtmlForPreview(arText) + '</div>'
            : '<div class="announcement-block-item text-muted">اضف المحتوى العربي...</div>';

        $('#pvBlocksEn').html(enHtml);
        $('#pvBlocksAr').html(arHtml);
    }

    function refreshPreview() {
        var circularNo = $('[name="circular_no"]').val();
        var issueDate = $('[name="issue_date"]').val();
        var yearText = getYearFromIssueDate(issueDate);

        $('#pvCircularEn').text('Circular No: ' + circularNo + ' in ' + yearText);
        $('#pvCircularAr').text('تعميم رقم ' + circularNo + ' لعام ' + yearText + 'م');

        $('#pvToEn').text('To: ' + ($('[name="to_en"]').val() || ''));
        $('#pvToAr').text('إلى: ' + ($('[name="to_ar"]').val() || ''));

        $('#pvDateEn').text('Date: ' + issueDate);
        $('#pvDateAr').text('التاريخ: ' + issueDate);

        $('#pvSubjectEn').text($('[name="subject_en"]').val() || '');
        $('#pvSubjectAr').text($('[name="subject_ar"]').val() || '');

        $('#pvFooterEn').text($('[name="footer_en"]').val() || '');
        $('#pvFooterAr').text($('[name="footer_ar"]').val() || '');

        var linkUrl = getSafeLinkUrl($('[name="announcement_link_url"]').val());
        var linkText = ($('[name="announcement_link_text"]').val() || '').trim() || 'Open Announcement Link';
        if (linkUrl) {
            $('#pvAnnouncementLink').html('<a href="' + $('<div>').text(linkUrl).html() + '" target="_blank" rel="noopener noreferrer">' + $('<div>').text(linkText).html() + '</a>');
        } else {
            $('#pvAnnouncementLink').html('');
        }

        renderBlocksPreview();
    }


    function captureAnnouncementPreview() {
        if (typeof html2canvas !== 'function') {
            return Promise.resolve('');
        }

        // The emailed image must always be the light design, even when the app's dark
        // theme is on (App Settings > Theme Config) - switch it off for the capture.
        var darkTheme = window.AppDark || null;
        if (darkTheme) darkTheme.suspend();

        return html2canvas(document.getElementById('announcementPreview'), {
            backgroundColor: '#f4f4f4',
            scale: 2,
            useCORS: true,
            logging: false
        }).then(function(canvas) {
            return canvas.toDataURL('image/png');
        }).catch(function() {
            return '';
        }).then(function(dataUrl) {
            if (darkTheme) darkTheme.resume();
            return dataUrl;
        });
    }

    // ---- Attachment type: a signed scan (PDF or image) sent as the email body ----
    // The browser turns the chosen file into one JPEG per page (pdf.js renders PDFs),
    // previews them and posts them as scan_pages[] - the server never receives the raw file.
    var SCAN_MAX_PAGES = 10;
    var SCAN_MAX_WIDTH = 1600;
    var SCAN_JPEG_QUALITY = 0.85;
    var SCAN_MAX_POST_CHARS = 28 * 1024 * 1024;
    var scanPages = [];
    var initialScanPages = <?= json_encode($initialScanPages, JSON_UNESCAPED_SLASHES) ?>;
    var initialScanLabel = <?= json_encode($initialScanLabel) ?>;

    function getAnnouncementType() {
        return $('input[name="announcement_type"]:checked').val() === 'attachment' ? 'attachment' : 'text';
    }

    function applyAnnouncementType() {
        var isAttachment = getAnnouncementType() === 'attachment';
        $('.js-text-only').toggle(!isAttachment);
        $('.js-attachment-only').toggle(isAttachment);
        $('#announcementPreview').toggle(!isAttachment);
        $('#scanPreview').toggle(isAttachment);
    }

    function showScanError(message) {
        if (typeof Swal === 'function') {
            Swal.fire({ icon: 'error', title: 'File not accepted', text: message, confirmButtonText: 'OK' });
        } else {
            alert(message);
        }
    }

    // White canvas of the page size (JPEG has no transparency).
    function newPageCanvas(width, height) {
        var canvas = document.createElement('canvas');
        canvas.width = Math.round(width);
        canvas.height = Math.round(height);
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        return canvas;
    }

    function imageFileToPages(file) {
        return new Promise(function(resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function() {
                var scale = Math.min(1, SCAN_MAX_WIDTH / img.naturalWidth);
                var canvas = newPageCanvas(img.naturalWidth * scale, img.naturalHeight * scale);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                resolve([canvas.toDataURL('image/jpeg', SCAN_JPEG_QUALITY)]);
            };
            img.onerror = function() {
                URL.revokeObjectURL(url);
                reject(new Error('This image could not be read. Use a JPG or PNG file, or a PDF.'));
            };
            img.src = url;
        });
    }

    function pdfFileToPages(file) {
        if (typeof pdfjsLib === 'undefined') {
            return Promise.reject(new Error('The PDF converter could not be loaded. Check the internet connection and reload the page.'));
        }
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        return file.arrayBuffer().then(function(buffer) {
            return pdfjsLib.getDocument({ data: buffer }).promise;
        }).then(function(pdf) {
            if (pdf.numPages > SCAN_MAX_PAGES) {
                throw new Error('This PDF has ' + pdf.numPages + ' pages. The limit is ' + SCAN_MAX_PAGES + ' pages.');
            }

            var pages = [];
            var chain = Promise.resolve();
            for (var pageNo = 1; pageNo <= pdf.numPages; pageNo++) {
                (function(no) {
                    chain = chain.then(function() {
                        return pdf.getPage(no);
                    }).then(function(page) {
                        var viewport = page.getViewport({ scale: SCAN_MAX_WIDTH / page.getViewport({ scale: 1 }).width });
                        var canvas = newPageCanvas(viewport.width, viewport.height);
                        return page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise.then(function() {
                            pages.push(canvas.toDataURL('image/jpeg', SCAN_JPEG_QUALITY));
                        });
                    });
                })(pageNo);
            }
            return chain.then(function() { return pages; });
        });
    }

    function setScanPages(pages, fileName) {
        scanPages = pages;
        var $inputs = $('#scanPagesInputs').empty();
        var $preview = $('#scanPreview').empty();

        if (pages.length === 0) {
            $preview.append('<div class="scan-empty">Select a PDF or image to see how it will look in the email.</div>');
            $('#scanStatus').text('');
            $('#scanClearBtn').hide();
            return;
        }

        pages.forEach(function(dataUrl) {
            $inputs.append($('<input type="hidden" name="scan_pages[]">').val(dataUrl));
            $preview.append($('<img alt="Announcement page">').attr('src', dataUrl));
        });
        $('#scanStatus').text(fileName + ' - ' + pages.length + (pages.length === 1 ? ' page ready' : ' pages ready'));
        $('#scanClearBtn').show();
    }

    $('#scanFile').on('change', function() {
        var input = this;
        var file = input.files && input.files[0];
        if (!file) {
            return;
        }

        var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
        if (!isPdf && !/^image\//.test(file.type)) {
            input.value = '';
            showScanError('Select a PDF or an image file.');
            return;
        }

        $('#scanStatus').text('Converting ' + file.name + '...');
        (isPdf ? pdfFileToPages(file) : imageFileToPages(file)).then(function(pages) {
            var totalChars = pages.reduce(function(sum, page) { return sum + page.length; }, 0);
            if (totalChars > SCAN_MAX_POST_CHARS) {
                throw new Error('This file is too large to send by email. Scan it at a lower resolution or with fewer pages.');
            }
            setScanPages(pages, file.name);
        }).catch(function(error) {
            input.value = '';
            setScanPages([], '');
            showScanError((error && error.message) || 'This file could not be converted.');
        });
    });

    $('#scanClearBtn').on('click', function() {
        $('#scanFile').val('');
        setScanPages([], '');
    });

    $('input[name="announcement_type"]').on('change', applyAnnouncementType);

    $('#contentBlock').on('keyup change', '.js-block-en, .js-block-ar', refreshPreview);
    $('.js-bind').on('keyup change', refreshPreview);

    $('#announcementForm button[type="submit"]').on('click', function(event) {
        if (!validateRecipientSelection()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        if (getAnnouncementType() === 'attachment' && scanPages.length === 0) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showScanError('Select the announcement PDF or image before sending.');
            return;
        }

        syncEditorsToTextareas();
        submitAction = $(this).attr('name') || '';
        $('#formAction').val(submitAction);
    });

    $('#announcementForm').on('submit', function(event) {
        if (submitAction === 'save_draft') {
            showSubmitLoader(submitAction);
            return;
        }

        var isSendSubmit = submitAction === 'send_announcement';
        if (!isSendSubmit || isCapturingPreview) {
            return;
        }

        // Attachment type posts its page images as they are - no preview capture needed.
        if (getAnnouncementType() === 'attachment') {
            showSubmitLoader(submitAction);
            return;
        }

        event.preventDefault();
        isCapturingPreview = true;
        showSubmitLoader(submitAction);

        captureAnnouncementPreview().then(function(imageDataUrl) {
            $('#announcementImageData').val(imageDataUrl);
            isCapturingPreview = false;
            $('#announcementForm')[0].submit();
        });
    });

    initContentEditors();
    applyAnnouncementType();
    if (initialScanPages.length > 0) {
        setScanPages(initialScanPages, initialScanLabel);
    }
    refreshPreview();
    showResultMessage();
})();
</script>
</body>
</html>
