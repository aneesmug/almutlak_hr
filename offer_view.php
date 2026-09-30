<?php
// Public (no login) page for a sent candidate letter - job offer. The candidate opens the
// link from the email, prints the letter, signs it and sends it back by email.
// Access is by the letter's random token only (employee_memos.public_token, set when the
// letter is sent - see includes/ajaxFile/employeeMemoHandler.php 'send_memo').
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/memo_helper.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');

$h = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$memo = null;
$token = (string) ($_GET['t'] ?? '');
if (preg_match('/^[a-f0-9]{48}$/', $token)) {
    memo_ensure_table($conDB);
    $stmt = $conDB->prepare("SELECT id, subject, body_html, reference_no, viewed_at, sent_by FROM employee_memos WHERE public_token = ? AND status = 'sent' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $memo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if ($memo && empty($memo['viewed_at'])) {
    // First time the candidate opened it - shown to HR in the memo history.
    $stmt = $conDB->prepare("UPDATE employee_memos SET viewed_at = NOW() WHERE id = ? AND viewed_at IS NULL");
    $stmt->bind_param('i', $memo['id']);
    $stmt->execute();
    $stmt->close();
}

$logoUrl = '';
if ($logoPath = memo_logo_path($conDB)) {
    $logoUrl = ltrim(str_replace('\\', '/', substr($logoPath, strlen(realpath(__DIR__)))), '/');
}
// Signed copy goes back to the HR user who sent the letter (same as the email's Reply-To).
$hrEmail = ($memo ? memo_user_email($conDB, $memo['sent_by']) : '') ?: trim((string) get_setting($conDB, 'from_email'));
$title = $memo ? $memo['subject'] : 'Letter not found';
if (!$memo) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $h($title) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f6; color: #1f2937; font-family: "Segoe UI", Tahoma, Arial, sans-serif; font-size: 14px; line-height: 1.6; }
        .bar { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; padding: 12px 20px; background: #25256e; color: #fff; }
        .bar p { margin: 0; font-size: 13px; }
        .bar a { color: #c7d2fe; }
        .bar button { padding: 9px 20px; border: 0; border-radius: 6px; background: #fff; color: #25256e; font-weight: 700; font-size: 14px; cursor: pointer; }
        .sheet { max-width: 980px; margin: 22px auto; background: #fff; border: 1px solid #dde2ea; border-radius: 8px; overflow: hidden; }
        .sheet-head { padding: 16px 28px; border-bottom: 3px solid #25256e; text-align: center; }
        .sheet-head img { height: 80px; width: auto; }
        .sheet-body { padding: 24px 28px; }
        .sheet-body table { max-width: 100%; }
        .none { max-width: 520px; margin: 80px auto; padding: 28px; background: #fff; border: 1px solid #dde2ea; border-radius: 8px; text-align: center; }
        @media (max-width: 700px) {
            .sheet { margin: 0; border: 0; border-radius: 0; }
            .sheet-body { padding: 16px 12px; }
            /* English and Arabic halves stack on a phone. */
            .sheet-body > table, .sheet-body > table > tbody, .sheet-body > table > tbody > tr, .sheet-body > table > tbody > tr > td { display: block; width: 100% !important; }
            .sheet-body > table > tbody > tr > td { padding: 0 0 16px !important; border: 0 !important; }
        }
        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #fff; font-size: 12px; }
            .bar { display: none; }
            .sheet { max-width: none; margin: 0; border: 0; border-radius: 0; }
            .sheet-body { padding: 14px 0 0; }
        }
    </style>
</head>
<body>
<?php if (!$memo): ?>
    <div class="none">
        <h2>Letter not found</h2>
        <p>This link is not valid or the letter is no longer available. Please contact the HR Department.</p>
        <p dir="rtl">هذا الرابط غير صالح أو أن الخطاب لم يعد متاحاً. يرجى التواصل مع إدارة الموارد البشرية.</p>
    </div>
<?php else: ?>
    <div class="bar">
        <p>
            Print this letter, sign it and send the signed copy back by email<?php if ($hrEmail !== ''): ?> to
            <a href="mailto:<?= $h($hrEmail) ?>?subject=<?= rawurlencode('Signed: ' . $memo['subject']) ?>"><?= $h($hrEmail) ?></a><?php endif; ?>.
            <br><span dir="rtl">اطبع هذا الخطاب ووقّعه ثم أرسل النسخة الموقعة عبر البريد الإلكتروني.</span>
        </p>
        <button type="button" onclick="window.print()">Print | طباعة</button>
    </div>
    <div class="sheet">
        <?php if ($logoUrl !== ''): ?>
            <div class="sheet-head"><img src="<?= $h($logoUrl) ?>" alt=""></div>
        <?php endif; ?>
        <?php /* body_html was cleaned by memo_clean_html() before it was stored. */ ?>
        <div class="sheet-body"><?= $memo['body_html'] ?></div>
    </div>
<?php endif; ?>
</body>
</html>
