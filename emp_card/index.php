<?php
// Public employee card - opened from the QR code on the employee profile
// (?hashcode=<emp_id>&verification=<employees.id>). No login: show only contact-card data.
include("./../includes/db.php");

$hashcode = trim((string)($_GET['hashcode'] ?? ''));
$verification = (int)($_GET['verification'] ?? 0);
$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

$rec = null;
if ($hashcode !== '' && $verification > 0) {
    $stmt = mysqli_prepare($conDB, "SELECT
            `employees`.*,
            `department`.`dep_nme` AS `dept`,
            `ac_jobs`.`job` AS `job_title`,
            `portfolio`.`description`
        FROM `employees`
        LEFT JOIN `portfolio` ON `portfolio`.`emp_id` = `employees`.`emp_id`
        LEFT JOIN `department` ON `department`.`id` = `employees`.`dept`
        LEFT JOIN `ac_jobs` ON `ac_jobs`.`id` = `employees`.`actual_job`
        WHERE `employees`.`id` = ? AND `employees`.`emp_id` = ?
        LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $verification, $hashcode);
    mysqli_stmt_execute($stmt);
    $rec = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
}

// "Save contact" -> vCard download
if ($rec && !empty($_GET['action']) && (int)$rec['status'] === 1) {
    require_once "VcardExport.php";
    (new VcardExport())->contactVcardExportService([$rec]);
    exit;
}

$logo = get_setting($conDB, 'logo');
// The 'logo' setting is the white version (made for dark backgrounds) - the badge sits on a white pill,
// so use the full-colour (dark blue) logo when it exists.
if (is_file(dirname(__DIR__) . '/assets/logo/logo.png')) {
    $logo = 'assets/logo/logo.png';
}
$favicon = get_setting($conDB, 'favicon');
$siteTitle = get_setting($conDB, 'site_title') ?: 'Al Mutlak';

if ($rec) {
    $name = trim((string)$rec['name']);
    $nameParts = preg_split('/\s+/', $name);
    $shortName = implode(' ', array_slice($nameParts, 0, 2));
    $initials = strtoupper(implode('', array_map(function ($w) { return mb_substr($w, 0, 1); }, array_slice($nameParts, 0, 2))));
    $jobTitle = $rec['job_title'] ?: trim(($rec['dept'] ?? '') . ' ' . ($rec['emptype'] ?? ''));
    $isActive = (int)$rec['status'] === 1;
    $isFemale = in_array(strtolower((string)$rec['sex']), ['2', 'female'], true);

    // Avatar: stored path (relative to the system root) if the file exists, else gender default
    $avatar = '';
    $avatarRel = ltrim(preg_replace('#^\./#', '', trim((string)$rec['avatar'])), '/');
    if ($avatarRel !== '' && is_file(dirname(__DIR__) . '/' . $avatarRel)) {
        $avatar = '../' . $avatarRel;
    }

    $socials = [];
    if ($isActive) {
        $stmt = mysqli_prepare($conDB, "SELECT `social_list`.`link`, `social_list`.`icon`, `social_list`.`color`, `social`.`s_link`
            FROM `social` JOIN `social_list` ON `social_list`.`id` = `social`.`social_id`
            WHERE `social`.`emp_id` = ?");
        mysqli_stmt_bind_param($stmt, 's', $rec['emp_id']);
        mysqli_stmt_execute($stmt);
        $socials = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}
?>
<?php
// Decorative barcode from the employee number (stable per employee)
$barcode = '';
if ($rec) {
    $seed = md5('emp-card-' . $rec['emp_id']);
    $x = 0;
    for ($i = 0; $i < 32 && $x < 236; $i++) {
        $w = 1 + (hexdec($seed[$i]) % 3);
        $gap = 1 + (hexdec($seed[31 - $i]) % 3);
        $barcode .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="40"/>';
        $x += $w + $gap;
    }
    $barcodeWidth = $x - $gap; // actual drawn width, so the bars can be centred
}
$cardNo = $rec ? str_pad((string)$rec['emp_id'], 6, '0', STR_PAD_LEFT) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#0b3d91">
  <title><?= $rec ? $h($shortName) . ' - ' : '' ?><?= $h($siteTitle) ?> ID</title>
  <link rel="icon" href="<?= $favicon ? '../' . $h(ltrim($favicon, './')) : 'favicon.ico' ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../fontawesome-7/css/all.css">
  <style>
    :root { --brand: #0b3d91; --brand2: #1d6ff2; --accent: #13a3b9; --ink: #0f172a; --muted: #64748b; --line: #e5e9f0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; }
    body {
      min-height: 100vh; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--ink);
      background: #dfe5ee radial-gradient(900px 420px at 50% -80px, #c7d3e6, transparent);
      display: flex; flex-direction: column; align-items: center; padding: 0 16px 40px;
    }

    /* Lanyard + clip */
    .lanyard { width: 34px; height: 64px; background: repeating-linear-gradient(90deg, var(--brand) 0 6px, var(--brand2) 6px 12px); box-shadow: inset 0 0 0 1px rgba(0,0,0,.08); }
    .clip { width: 62px; height: 26px; margin-top: -2px; border-radius: 8px 8px 12px 12px; background: linear-gradient(180deg, #e2e8f0, #94a3b8); box-shadow: 0 3px 6px rgba(0,0,0,.2); position: relative; z-index: 2; }
    .clip::after { content: ""; position: absolute; left: 50%; bottom: 6px; width: 28px; height: 6px; margin-left: -14px; border-radius: 4px; background: #475569; }

    /* The badge */
    .badge { width: 100%; max-width: 340px; margin-top: -8px; background: #fff; border-radius: 22px; overflow: hidden; position: relative;
      box-shadow: 0 2px 0 rgba(255,255,255,.6) inset, 0 30px 60px rgba(15,23,42,.25), 0 8px 18px rgba(15,23,42,.12); animation: swing 1.1s ease-out both; transform-origin: 50% -70px; }
    @keyframes swing { 0% { transform: rotate(-6deg); } 40% { transform: rotate(3deg); } 70% { transform: rotate(-1.2deg); } 100% { transform: rotate(0); } }
    .slot { position: absolute; top: 12px; left: 50%; width: 56px; height: 9px; margin-left: -28px; border-radius: 6px; background: rgba(0,0,0,.25); box-shadow: inset 0 1px 2px rgba(0,0,0,.4); z-index: 3; }

    .band { position: relative; padding: 32px 20px 64px; color: #fff; text-align: center; background: linear-gradient(135deg, var(--brand), var(--brand2) 60%, var(--accent)); overflow: hidden; }
    .band::before { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(135deg, rgba(255,255,255,.06) 0 2px, transparent 2px 14px); }
    .band .logo { position: relative; display: inline-flex; background: #fff; border-radius: 14px; padding: 8px 16px; box-shadow: 0 6px 14px rgba(0,0,0,.15); }
    .band .logo img { height: 46px; display: block; }
    .band .logo span { font-weight: 900; color: var(--brand); }
    .band .kind { position: relative; margin-top: 10px; font-size: 10.5px; font-weight: 800; letter-spacing: 3px; opacity: .9; }
    .badge.inactive .band { background: linear-gradient(135deg, #475569, #64748b); }

    .photo { position: relative; z-index: 2; width: 140px; height: 140px; margin: -56px auto 0; border-radius: 50%; overflow: hidden; border: 5px solid #fff; background: linear-gradient(135deg, var(--brand2), var(--accent));
      box-shadow: 0 10px 22px rgba(15,23,42,.22); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 44px; font-weight: 900; }
    .photo img { width: 100%; height: 100%; object-fit: cover; }
    .badge.inactive .photo img { filter: grayscale(1); }

    .who { padding: 14px 22px 4px; text-align: center; }
    .who h1 { margin: 0; font-size: 21px; font-weight: 900; letter-spacing: .4px; text-transform: uppercase; line-height: 1.2; }
    .who .title { margin-top: 5px; font-size: 13.5px; font-weight: 700; color: var(--brand2); }

    .facts { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 14px 18px 4px; }
    .fact { padding: 9px 11px; border-radius: 12px; background: #f4f7fb; border: 1px solid var(--line); min-width: 0; }
    .fact.wide { grid-column: 1 / -1; }
    .fact small { display: flex; align-items: center; gap: 5px; font-size: 9.5px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; color: var(--muted); }
    .fact small i { color: var(--brand2); }
    .fact b { display: block; margin-top: 3px; font-size: 13.5px; font-weight: 800; overflow-wrap: anywhere; }

    .status { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 12px 18px 0; padding: 9px; border-radius: 12px; font-size: 12px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }
    .status.ok { color: #15803d; background: rgba(22,163,74,.1); border: 1px dashed rgba(22,163,74,.45); }
    .status.off { color: #b91c1c; background: rgba(220,38,38,.08); border: 1px dashed rgba(220,38,38,.4); }

    .strip { margin-top: 14px; padding: 12px 18px 14px; text-align: center; background: repeating-linear-gradient(90deg, #f8fafc 0 10px, #f1f5f9 10px 20px); border-top: 1px solid var(--line); }
    .strip svg { max-width: 100%; height: 40px; fill: var(--ink); display: block; margin: 0 auto; }
    .strip .no { margin-top: 5px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; letter-spacing: 4px; color: var(--muted); }
    .holo { position: absolute; right: 14px; bottom: 74px; width: 42px; height: 42px; border-radius: 50%; opacity: .55;
      background: conic-gradient(from 0deg, #f0abfc, #93c5fd, #6ee7b7, #fde68a, #f0abfc); mix-blend-mode: multiply; }

    /* Below the badge */
    .below { width: 100%; max-width: 340px; margin-top: 20px; }
    .acts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .act { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 6px; border-radius: 16px; background: #fff; text-decoration: none; color: var(--ink);
      font-size: 12px; font-weight: 800; box-shadow: 0 6px 16px rgba(15,23,42,.08); transition: transform .15s ease; }
    .act i { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 17px; background: var(--c); }
    .act:hover { transform: translateY(-2px); }
    .act.is-off { opacity: .4; pointer-events: none; }
    .save { display: flex; align-items: center; justify-content: center; gap: 9px; margin-top: 12px; height: 52px; border-radius: 16px; color: #fff; text-decoration: none; font-weight: 900; font-size: 15px;
      background: linear-gradient(135deg, var(--brand), var(--brand2)); box-shadow: 0 12px 24px rgba(11,61,145,.3); }
    .panel { margin-top: 12px; padding: 14px 16px; border-radius: 16px; background: #fff; box-shadow: 0 6px 16px rgba(15,23,42,.08); }
    .panel h3 { margin: 0 0 8px; font-size: 11px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); }
    .panel .about { font-size: 13.5px; line-height: 1.6; }
    .panel .about p:first-child { margin-top: 0; } .panel .about p:last-child { margin-bottom: 0; }
    .socials { display: flex; flex-wrap: wrap; gap: 8px; }
    .social { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; text-decoration: none; }
    .foot { margin-top: 18px; text-align: center; font-size: 11.5px; color: var(--muted); }
    .foot i { color: #16a34a; }

    .nf { padding: 28px 22px 30px; text-align: center; }
    .nf i { font-size: 40px; color: #dc2626; }
    .nf h1 { margin: 12px 0 6px; font-size: 19px; font-weight: 900; }
    .nf p { margin: 0; font-size: 13.5px; color: var(--muted); line-height: 1.6; }

    @media (prefers-color-scheme: dark) {
      body { background: #0b1120 radial-gradient(900px 420px at 50% -80px, #1e293b, transparent); }
      .act, .panel { background: #1a2130; color: #e5e7eb; }
      .panel .about { color: #e5e7eb; }
    }
  </style>
</head>
<body>
  <div class="lanyard"></div>
  <div class="clip"></div>

<?php if (!$rec): ?>
  <div class="badge">
    <span class="slot"></span>
    <div class="band">
      <span class="logo"><?php if ($logo): ?><img src="../<?= $h(ltrim($logo, './')) ?>" alt=""><?php else: ?><span><?= $h($siteTitle) ?></span><?php endif; ?></span>
      <div class="kind">EMPLOYEE ID CARD</div>
    </div>
    <div class="nf">
      <i class="fa-solid fa-id-card-clip"></i>
      <h1>Card not found</h1>
      <p>This ID card link is not valid.<br>Please scan the QR code again or contact HR.</p>
    </div>
  </div>

<?php else:
  $mobile = trim((string)$rec['mobile']);
  $email = trim((string)$rec['c_email']);
  $tel = preg_replace('/[^\d+]/', '', $mobile);
  $wa = ltrim($tel, '+');
  if (strpos($wa, '05') === 0) { $wa = '966' . substr($wa, 1); } // local Saudi mobile -> international for WhatsApp
?>
  <div class="badge<?= $isActive ? '' : ' inactive' ?>">
    <span class="slot"></span>
    <div class="band">
      <span class="logo"><?php if ($logo): ?><img src="../<?= $h(ltrim($logo, './')) ?>" alt="<?= $h($siteTitle) ?>"><?php else: ?><span><?= $h($siteTitle) ?></span><?php endif; ?></span>
      <div class="kind">EMPLOYEE ID CARD</div>
    </div>

    <div class="photo"><?php if ($avatar): ?><img src="<?= $h($avatar) ?>" alt="<?= $h($shortName) ?>"><?php else: ?><?= $h($initials) ?><?php endif; ?></div>

    <div class="who">
      <h1><?= $h($name) ?></h1>
      <?php if ($isActive && $jobTitle): ?><div class="title"><?= $h($jobTitle) ?></div><?php endif; ?>
    </div>

    <?php if ($isActive): ?>
    <div class="facts">
      <div class="fact"><small><i class="fa-solid fa-hashtag"></i> Employee No.</small><b><?= $h($rec['emp_id']) ?></b></div>
      <div class="fact"><small><i class="fa-solid fa-sitemap"></i> Department</small><b><?= $h($rec['dept'] ?: '-') ?></b></div>
      <div class="fact wide"><small><i class="fa-solid fa-building"></i> Company</small><b><?= $h($siteTitle) ?></b></div>
    </div>
    <div class="status ok"><i class="fa-solid fa-circle-check"></i> Valid employee</div>
    <span class="holo"></span>
    <?php else: ?>
    <div class="status off"><i class="fa-solid fa-ban"></i> No longer employed</div>
    <?php endif; ?>

    <div class="strip">
      <svg viewBox="0 0 <?= (int)$barcodeWidth ?> 40" width="<?= (int)$barcodeWidth ?>" height="40" aria-hidden="true"><?= $barcode ?></svg>
      <div class="no"><?= $h($cardNo) ?></div>
    </div>
  </div>

  <div class="below">
    <?php if ($isActive): ?>
      <div class="acts">
        <a class="act<?= $tel ? '' : ' is-off' ?>" href="tel:<?= $h($tel) ?>" style="--c: #16a34a;"><i class="fa-solid fa-phone"></i> Call</a>
        <a class="act<?= $wa ? '' : ' is-off' ?>" href="https://wa.me/<?= $h($wa) ?>" target="_blank" rel="noopener" style="--c: #22c55e;"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
        <a class="act<?= $email ? '' : ' is-off' ?>" href="mailto:<?= $h($email) ?>" style="--c: #2563eb;"><i class="fa-solid fa-envelope"></i> Email</a>
      </div>
      <a class="save" href="index.php?action=export&amp;hashcode=<?= urlencode($rec['emp_id']) ?>&amp;verification=<?= (int)$rec['id'] ?>"><i class="fa-solid fa-address-card"></i> Save contact</a>

      <?php if (!empty($rec['description'])): ?>
      <div class="panel"><h3>About</h3><div class="about"><?= $rec['description'] /* portfolio rich text, entered by HR */ ?></div></div>
      <?php endif; ?>
      <?php if ($socials): ?>
      <div class="panel"><h3>Find me on</h3><div class="socials">
        <?php foreach ($socials as $s): ?>
          <a class="social" href="<?= $h($s['link'] . $s['s_link']) ?>" target="_blank" rel="noopener" style="background: <?= $h($s['color'] ?: '#475569') ?>;" title="<?= $h($s['s_link']) ?>"><i class="<?= $h($s['icon']) ?>"></i></a>
        <?php endforeach; ?>
      </div></div>
      <?php endif; ?>
    <?php else: ?>
      <div class="panel" style="text-align:center;"><div class="about"><?= $isFemale ? 'Ms.' : 'Mr.' ?> <?= $h($shortName) ?> is not currently working at <?= $h($siteTitle) ?>. Thank you.</div></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

  <div class="foot"><i class="fa-solid fa-shield-check"></i> Issued by <?= $h($siteTitle) ?> &middot; <?= date('Y') ?></div>
</body>
</html>
