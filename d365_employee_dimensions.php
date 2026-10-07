<?php
/**
 * D365 Employee Dimensions - payroll company + financial dimension values per employee (the same screen as
 * App Settings > D365 Config > Employee Dimensions), for finance users without App Settings access.
 * Access: system admins + the 'd365_employee_dimensions' special access.
 * Account templates, departments and company mapping stay in App Settings (system admins only);
 * includes/ajaxFile/d365_dimensions.php allows only the employee actions to this special access.
 */
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/D365Client.php';

if (!user_has_special_access($conDB, $empid ?? '', 'd365_employee_dimensions', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    http_response_code(403);
    die('Access Denied: You do not have permission to manage D365 employee dimensions');
}
if (empty($_SESSION['d365_csrf'])) {
    $_SESSION['d365_csrf'] = bin2hex(random_bytes(16));
}
$environment = 'unknown';
try {
    $environment = (new D365Client())->getEnvironment();
} catch (Throwable $ex) {
}
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>D365 Employee Dimensions</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/sweet-alert/v11/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .select2-container { width: 100% !important; }
    </style>
</head>
<body class="sr-standalone">
<div class="sr-page sr-standalone-wrap">
    <div class="sr-standalone-brand">
        <a href="dashboard.php"><img src="<?= $e(get_setting($conDB, 'logo')) ?>" alt=""></a>
    </div>
    <div class="sr-head">
        <div>
            <h1><i class="mdi mdi-microsoft"></i> D365 Employee Dimensions</h1>
            <p>Payroll company and financial dimension values of every employee. The payroll sync books each employee's lines with exactly these values, in the dimensions of their company's account template.</p>
        </div>
        <div class="sr-head-actions">
            <span class="sr-pill tone-<?= $environment === 'sandbox' ? 'sky' : 'red' ?>"><span class="sr-dot"></span><?= $e(ucfirst($environment)) ?></span>
        </div>
    </div>
    <div class="sr-card">
        <div class="sr-card-body" id="d365DimHost" style="padding:14px"></div>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="./plugins/select2/js/select2.min.js"></script>
<script src="./plugins/sweet-alert/v11/sweetalert2.all.min.js"></script>
<script>window.D365_DIM_CSRF = <?= json_encode($_SESSION['d365_csrf']) ?>;</script>
<script src="assets/js/d365_dimensions_settings.js?v=<?= @filemtime(__DIR__ . '/assets/js/d365_dimensions_settings.js') ?>"></script>
<script>
    // all active employees; no Add employees / Fill blanks from D365 / Excel Import here
    window.D365DimensionsSettings.render('employees', document.getElementById('d365DimHost'), { allEmployees: true });
</script>
</body>
</html>
