<?php
// Report page shell, part 1 (head, sidebar, top bar, report header, employee banner) - see includes/report_layout.php.
// Included at file scope by the report page after it sets $report.
require_once __DIR__ . '/report_layout.php';
$report = $report ?? [];
echo report_head_html($report);
?>
<body class="enlarged" data-keep-enlarged="true">
<div id="wrapper">
    <div class="left side-menu no-print">
        <div class="slimscroll-menu" id="remove-scroll">
            <div class="topbar-left"><a href="dashboard.php" class="logo"><span><img src="<?= htmlspecialchars((string)get_setting($conDB, 'logo')) ?>" alt="" height="22"></span><i><img src="<?= htmlspecialchars((string)get_setting($conDB, 'white_logo')) ?>" alt="" height="28"></i></a></div>
            <?php include __DIR__ . '/main_menu.php'; ?>
            <div class="clearfix"></div>
        </div>
    </div>
    <div class="content-page">
        <?php include __DIR__ . '/topbar.php'; ?>
        <div class="content">
            <div class="container-fluid">
                <?= report_card_open_html($report) ?>
