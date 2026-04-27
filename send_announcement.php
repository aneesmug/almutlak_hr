<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

if (!(($is_system_admin ?? false) || ($isHR ?? false))) {
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
 * Fixed announcement group recipients.
 */
function get_announcement_group_recipients(): array
{
    return [
        'company' => [
            'name' => 'Almutlak Email List',
            'email' => 'almutlak.emails@almutlak.com'
        ],
        'head_office' => [
            'name' => 'H.O',
            'email' => 'head.office@almutlak.com'
        ],
        'anees' => [
            'name' => 'Anees',
            'email' => 'a.afzal@almutlak.com'
        ]
    ];
}

/**
 * Build sanitized dynamic bilingual content rows.
 */
function normalize_announcement_blocks(array $enBlocks, array $arBlocks): array
{
    $rows = [];
    $maxCount = max(count($enBlocks), count($arBlocks));

    for ($i = 0; $i < $maxCount; $i++) {
        $en = trim((string)($enBlocks[$i] ?? ''));
        $ar = trim((string)($arBlocks[$i] ?? ''));

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
 * Allow a safe subset of inline HTML for announcement content rows.
 */
function sanitize_announcement_html_fragment(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $html = str_replace(["\r\n", "\r"], "\n", $html);
    $html = nl2br($html, false);

    // Remove dangerous container tags completely.
    $html = preg_replace('#<(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta)[^>]*>.*?</\1>#is', '', $html) ?? '';
    $html = preg_replace('#</?(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta)[^>]*>#is', '', $html) ?? '';

    // Keep only formatting-oriented tags.
    $allowedTags = '<b><strong><i><em><u><br><p><ul><ol><li><span><div><h1><h2><h3><h4><a>';
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
 */
function send_announcement_email(mysqli $conDB, string $toEmail, string $toName, string $subject, string $htmlBody, string $embeddedImage = ''): bool
{
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return false;
    }

    $smtp_host = (string)get_setting($conDB, 'smtp_host');
    $smtp_port = (int)get_setting($conDB, 'smtp_port');
    $smtp_user = 'internal.Communication@almutlak.com';
    $smtp_from_email = 'internal.Communication@almutlak.com';
    $smtp_from_name = 'Internal Communication';
    $smtp_secure = (string)get_setting($conDB, 'smtp_encryption');

    if (
        empty($smtp_host) || empty($smtp_port) || empty($smtp_user) ||
        empty($smtp_pass) || empty($smtp_from_email) || empty($smtp_from_name)
    ) {
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

        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($smtp_from_email, $smtp_from_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        if ($embeddedImage !== '') {
            $mail->addStringEmbeddedImage($embeddedImage, 'announcement_preview', 'announcement.png', 'base64', 'image/png');
        }

        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        return $mail->send();
    } catch (Throwable $e) {
        error_log('ANNOUNCEMENT_EMAIL_ERROR: ' . $e->getMessage());
        return false;
    }
}

$announcementGroups = get_announcement_group_recipients();
$selectedRecipientMode = '';
$messageHtml = '';
$messageType = '';

/**
 * Save announcement to database.
 */
function save_announcement_to_db(mysqli $conDB, array $formData, string $selectedRecipientMode, int $sentCount = 0): bool
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
         recipients_count, sent_success_count, created_by_emp_id, created_by_name, is_draft)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conDB, $insertSql);
    if ($stmt) {
        $creatorEmpId = (string)($GLOBALS['empid'] ?? '');
        $creatorName = (string)($GLOBALS['fname'] ?? '');
        $isDraft = $sentCount === 0 ? 1 : 0;
        $recipientsCount = 0;

        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssssiissi',
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
            $isDraft
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
    'footer_en' => 'Shared Services - HR Department',
    'footer_ar' => 'الخدمات المشتركة - قسم الموارد البشرية',
    'content_blocks' => [
        [
            'en' => 'In light of expected weather fluctuations and rainfall, all employees are requested to complete their tasks remotely from home.',
            'ar' => 'إشارة إلى التقلبات الجوية المتوقعة وما يصاحبها من هطول الأمطار، نأمل من جميع الموظفين إنجاز أعمالهم عن بعد من المنزل.'
        ],
        [
            'en' => 'We hope everyone will adhere to this announcement and follow upcoming updates.',
            'ar' => 'نأمل من الجميع التقيد بهذا التعميم ومتابعة أي تحديثات لاحقة.'
        ]
    ]
];

$formData = $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $formData['circular_no'] === '') {
    $formData['circular_no'] = get_next_circular_number($conDB);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (
    isset($_POST['send_announcement']) ||
    isset($_POST['save_draft']) ||
    in_array((string)($_POST['form_action'] ?? ''), ['send_announcement', 'save_draft'], true)
)) {
    foreach (['circular_no', 'issue_date', 'to_en', 'to_ar', 'subject_en', 'subject_ar', 'footer_en', 'footer_ar'] as $field) {
        $formData[$field] = trim((string)($_POST[$field] ?? ''));
    }

    $formData['content_blocks'] = normalize_announcement_blocks(
        (array)($_POST['block_en'] ?? []),
        (array)($_POST['block_ar'] ?? [])
    );

    $selectedRecipientMode = (string)($_POST['recipient_mode'] ?? '');
    if (!isset($announcementGroups[$selectedRecipientMode])) {
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

    if (empty($formData['content_blocks'])) {
        $missing[] = 'content_blocks';
    }

    if ($selectedRecipientMode === '') {
        $missing[] = 'recipient_mode';
    }

    if (!empty($missing)) {
        $messageType = 'danger';
        $messageHtml = 'Please fill all required fields and add at least one content row before ' . ($isSending ? 'sending' : 'saving') . '.';
    } else {
        if ($isSavingDraft) {
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
                $messageHtml = 'Announcement saved as draft with ' . count($formData['content_blocks']) . ' content block(s). (ID: ' . htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') . ')';
            } else {
                $messageType = 'danger';
                $messageHtml = 'Failed to save announcement draft.';
            }
        } elseif ($isSending) {
            $selectedGroup = $announcementGroups[$selectedRecipientMode] ?? null;
            $recipients = [];
            if (is_array($selectedGroup)) {
                $recipients[] = [
                    'email' => (string)($selectedGroup['email'] ?? ''),
                    'recipient_name' => (string)($selectedGroup['name'] ?? 'Announcement Group')
                ];
            }

            if (empty($recipients)) {
                $messageType = 'danger';
                $messageHtml = 'No recipients found with valid email addresses.';
            } else {
                $mailSubject = $formData['subject_en'] . ' | ' . $formData['subject_ar'];
                $logo = get_setting($conDB, 'logo');
                $imageBinary = decode_posted_announcement_image((string)($_POST['announcement_image_data'] ?? ''));

                if ($imageBinary === '') {
                    $imageBinary = render_announcement_to_image($formData, (string)$logo);
                }

                if ($imageBinary === '') {
                    $messageType = 'danger';
                    $messageHtml = 'Announcement image could not be generated. Edge-based screenshot rendering is required, so the email was not sent as HTML fallback.';
                } else {
                    $emailBody = '<html><body style="margin:0;padding:0;background:#f2f2f2;"><img src="cid:announcement_preview" alt="Announcement" style="display:block;max-width:100%;width:100%;height:auto;border:0;" /></body></html>';

                    $sentSuccess = 0;
                    foreach ($recipients as $rec) {
                        $toEmail = trim((string)($rec['email'] ?? ''));
                        $toName = trim((string)($rec['recipient_name'] ?? 'Colleague'));

                        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }

                        if (send_announcement_email($conDB, $toEmail, $toName, $mailSubject, $emailBody, $imageBinary)) {
                            $sentSuccess++;
                        }
                    }

                    if (save_announcement_to_db($conDB, $formData, $selectedRecipientMode, $sentSuccess)) {
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
                                    'dynamic_blocks_count' => count($formData['content_blocks'])
                                ],
                                'Sent bilingual announcement circular ' . $formData['circular_no']
                            );
                        }
                    }

                    $messageType = $sentSuccess > 0 ? 'success' : 'warning';
                    $messageHtml = 'Announcement sent to ' . (int)$sentSuccess . ' recipient(s) with ' . count($formData['content_blocks']) . ' content block(s). (ID: ' . htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') . ')';
                }
            }
        }
    }
}

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
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>

    <style>
        .announcement-editor .card-box { border-radius: 14px; }
        .dynamic-block-row { border: 1px solid #e2e2e2; border-radius: 10px; padding: 10px; margin-bottom: 10px; background: #fafafa; }
        .preview-shell {
            background: #f4f4f4;
            border: 1px solid #cfcfcf;
            border-radius: 12px;
            padding: 12px;
            overflow-x: auto;
        }
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

        <div class="content">
            <div class="container-fluid pt-3 announcement-editor">
                <div class="row">
                    <div class="col-12">
                        <div class="card-box">
                            <h4 class="m-0 header-title">Bilingual Announcement Sender (English / العربية)</h4>
                            <p class="text-muted mt-2 mb-4">Create and send mirrored circular announcements side by side using dynamic content rows.</p>

                            <?php if ($messageHtml !== ''): ?>
                                <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($messageHtml, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="announcementForm" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="announcement_image_data" id="announcementImageData" value="">
                                <input type="hidden" name="form_action" id="formAction" value="">
                                <div class="row">
                                    <div class="col-lg-5">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Circular No *</label>
                                                <input type="text" name="circular_no" class="form-control js-bind" data-bind="circular_no" required value="<?= htmlspecialchars($formData['circular_no'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Date (dd-mm-yyyy) *</label>
                                                <input type="text" name="issue_date" class="form-control js-bind" data-bind="issue_date" required value="<?= htmlspecialchars($formData['issue_date'], ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>To (English)</label>
                                            <input type="text" name="to_en" class="form-control js-bind" data-bind="to_en" value="<?= htmlspecialchars($formData['to_en'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>إلى (Arabic)</label>
                                            <input type="text" name="to_ar" class="form-control js-bind" data-bind="to_ar" value="<?= htmlspecialchars($formData['to_ar'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>

                                        <div class="form-group">
                                            <label>Subject (English) *</label>
                                            <input type="text" name="subject_en" class="form-control js-bind" data-bind="subject_en" value="<?= htmlspecialchars($formData['subject_en'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>العنوان (Arabic) *</label>
                                            <input type="text" name="subject_ar" class="form-control js-bind" data-bind="subject_ar" value="<?= htmlspecialchars($formData['subject_ar'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>

                                        <hr>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h5 class="mb-0">Dynamic Content Rows *</h5>
                                            <button type="button" id="addBlockBtn" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i> Add Row</button>
                                        </div>
                                        <small class="text-muted d-block mb-2">HTML formatting is supported in rows (example: &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, &lt;br&gt;, &lt;ul&gt;&lt;li&gt;...&lt;/li&gt;&lt;/ul&gt;).</small>
                                        <div id="dynamicBlocksContainer">
                                            <?php foreach (($formData['content_blocks'] ?? []) as $block): ?>
                                                <div class="dynamic-block-row">
                                                    <div class="form-group mb-2">
                                                        <label>English Row</label>
                                                        <textarea name="block_en[]" rows="4" class="form-control js-block-en js-rich-editor"><?= htmlspecialchars((string)($block['en'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                    </div>
                                                    <div class="form-group mb-2">
                                                        <label>Arabic Row</label>
                                                        <textarea name="block_ar[]" rows="4" class="form-control js-block-ar js-rich-editor"><?= htmlspecialchars((string)($block['ar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-danger js-remove-block"><i class="fa fa-trash"></i> Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="form-group mt-3">
                                            <label>Footer (English)</label>
                                            <input type="text" name="footer_en" class="form-control js-bind" data-bind="footer_en" value="<?= htmlspecialchars($formData['footer_en'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>التذييل (Arabic)</label>
                                            <input type="text" name="footer_ar" class="form-control js-bind" data-bind="footer_ar" value="<?= htmlspecialchars($formData['footer_ar'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>

                                    <div class="col-lg-7">
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <h5 class="mb-3">Recipients</h5>
                                                <div class="custom-control custom-radio mb-2">
                                                    <input type="radio" id="recipient_company" name="recipient_mode" value="company" class="custom-control-input" <?= $selectedRecipientMode === 'company' ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="recipient_company">Almutlak Email List &lt;almutlak.emails@almutlak.com&gt;</label>
                                                </div>
                                                <div class="custom-control custom-radio mb-3">
                                                    <input type="radio" id="recipient_head_office" name="recipient_mode" value="head_office" class="custom-control-input" <?= $selectedRecipientMode === 'head_office' ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="recipient_head_office">H.O &lt;head.office@almutlak.com&gt;</label>
                                                </div>
                                                <div class="custom-control custom-radio mb-3">
                                                    <input type="radio" id="recipient_anees" name="recipient_mode" value="anees" class="custom-control-input" <?= $selectedRecipientMode === 'anees' ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="recipient_anees">Anees &lt;a.afzal@almutlak.com&gt;</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="preview-shell">
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex align-items-center gap-2">
                                    <a href="dashboard.php" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                    <button type="submit" name="save_draft" class="btn btn-outline-secondary"><i class="fa fa-save"></i> Save as Draft</button>
                                    <button type="submit" name="send_announcement" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Send Announcement</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
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

    function validateRecipientSelection() {
        var hasRecipient = $('input[name="recipient_mode"]:checked').length > 0;
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

        var html = String(rawHtml || '').replace(/\r\n?/g, '\n').replace(/\n/g, '<br>');
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

    function renderBlocksPreview() {
        var enHtml = '';
        var arHtml = '';

        $('#dynamicBlocksContainer .dynamic-block-row').each(function() {
            var enText = $(this).find('.js-block-en').val() || '';
            var arText = $(this).find('.js-block-ar').val() || '';

            if ($.trim(enText) !== '') {
                enHtml += '<div class="announcement-block-item">' + sanitizeHtmlForPreview(enText) + '</div>';
            }
            if ($.trim(arText) !== '') {
                arHtml += '<div class="announcement-block-item">' + sanitizeHtmlForPreview(arText) + '</div>';
            }
        });

        if (enHtml === '') {
            enHtml = '<div class="announcement-block-item text-muted">Add dynamic English rows...</div>';
        }
        if (arHtml === '') {
            arHtml = '<div class="announcement-block-item text-muted">اضف اسطر عربية ديناميكية...</div>';
        }

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

        renderBlocksPreview();
    }

    function addDynamicBlock(enValue, arValue) {
        var rowHtml = '' +
            '<div class="dynamic-block-row">' +
                '<div class="form-group mb-2">' +
                    '<label>English Row</label>' +
                    '<textarea name="block_en[]" rows="2" class="form-control js-block-en"></textarea>' +
                '</div>' +
                '<div class="form-group mb-2">' +
                    '<label>Arabic Row</label>' +
                    '<textarea name="block_ar[]" rows="2" class="form-control js-block-ar"></textarea>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger js-remove-block"><i class="fa fa-trash"></i> Remove</button>' +
            '</div>';

        var $row = $(rowHtml);
        if (typeof enValue === 'string') {
            $row.find('.js-block-en').val(enValue);
        }
        if (typeof arValue === 'string') {
            $row.find('.js-block-ar').val(arValue);
        }

        $('#dynamicBlocksContainer').append($row);
        refreshPreview();
    }

    function captureAnnouncementPreview() {
        if (typeof html2canvas !== 'function') {
            return Promise.resolve('');
        }

        return html2canvas(document.getElementById('announcementPreview'), {
            backgroundColor: '#f4f4f4',
            scale: 2,
            useCORS: true,
            logging: false
        }).then(function(canvas) {
            return canvas.toDataURL('image/png');
        }).catch(function() {
            return '';
        });
    }

    $('#addBlockBtn').on('click', function() {
        addDynamicBlock('', '');
    });

    $('#dynamicBlocksContainer').on('click', '.js-remove-block', function() {
        $(this).closest('.dynamic-block-row').remove();
        if ($('#dynamicBlocksContainer .dynamic-block-row').length === 0) {
            addDynamicBlock('', '');
        }
        refreshPreview();
    });

    $('#dynamicBlocksContainer').on('keyup change', '.js-block-en, .js-block-ar', refreshPreview);
    $('.js-bind').on('keyup change', refreshPreview);

    $('#announcementForm button[type="submit"]').on('click', function(event) {
        if (!validateRecipientSelection()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

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

        event.preventDefault();
        isCapturingPreview = true;
        showSubmitLoader(submitAction);

        captureAnnouncementPreview().then(function(imageDataUrl) {
            $('#announcementImageData').val(imageDataUrl);
            isCapturingPreview = false;
            $('#announcementForm')[0].submit();
        });
    });

    if ($('#dynamicBlocksContainer .dynamic-block-row').length === 0) {
        addDynamicBlock('', '');
    }

    refreshPreview();
    showResultMessage();
})();
</script>
</body>
</html>
