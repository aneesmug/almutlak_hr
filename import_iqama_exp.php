<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
    if(mysqli_num_rows($query) == 1){
        include("./includes/avatar_select.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?=$site_title ?> - Import Iqama Expiration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
</head>
<body class="enlarged" data-keep-enlarged="true">

    <!-- Begin page -->
    <div id="wrapper">

        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <!-- LOGO -->
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <!--- Sidemenu -->
                <?php include("./includes/main_menu.php"); ?>
                <!-- Sidebar -->
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="content-page">
            <!-- Top Bar Start -->
            <?php include("./includes/topbar.php"); ?>
            <!-- Top Bar End -->

            <!-- Start Page content -->
            <div class="content sr-page">
                <div class="container-fluid">
                    <div class="sr-head">
                        <div>
                            <h1>Import Iqama Expiration</h1>
                            <p>Bulk-update employee Iqama expiry dates from an Excel or CSV file.</p>
                        </div>
                        <div class="sr-head-actions">
                            <a href="#" id="downloadSampleLink" class="sr-btn"><i class="mdi mdi-download"></i> Download sample file</a>
                        </div>
                    </div>

                    <?php if (isset($_GET['status'])): ?>
                        <?php if ($_GET['status'] == 'success'): ?>
                            <div class="sr-notice tone-green">
                                <i class="mdi mdi-check-circle-outline"></i>
                                <div>
                                    <strong><?=htmlspecialchars($_GET['updated_count'] ?? '0'); ?></strong> records updated successfully.
                                    <?php if (isset($_GET['not_found_count']) && $_GET['not_found_count'] > 0): ?>
                                        <br><strong><?=htmlspecialchars($_GET['not_found_count']); ?></strong> records failed because the Iqama number was not found.
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($_GET['status'] == 'error'): ?>
                            <div class="sr-notice tone-red">
                                <i class="mdi mdi-alert-circle-outline"></i>
                                <div><strong>Error:</strong> <?=htmlspecialchars($_GET['message'] ?? ''); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="sr-import">
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-account-card-details"></i> Upload file</h2>
                            </div>
                            <div class="sr-card-body">
                                <form id="iqamaImportForm" class="sr-form" action="./includes/process_iqama_import.php" method="post" enctype="multipart/form-data" novalidate>
                                    <label class="sr-filepick is-lg" for="employee_file">
                                        <input type="file" name="employee_file" id="employee_file" accept=".xlsx, .xls, .csv">
                                        <i class="mdi mdi-cloud-upload"></i>
                                        <span><b class="js-file-name">Choose a file or drop it here</b>
                                        <small>Supported formats: .xlsx, .xls, .csv</small></span>
                                    </label>
                                    <div class="sr-import-actions">
                                        <button type="submit" name="import" value="1" class="sr-btn sr-btn-primary"><i class="mdi mdi-upload"></i> Upload and Process</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-information-outline"></i> File format</h2>
                            </div>
                            <div class="sr-card-body">
                                <ol class="sr-guide">
                                    <li>Use two columns in this order: <code>iqama</code> and <code>iqama_exp</code>.</li>
                                    <li>The first row is a header and will be skipped.</li>
                                    <li>Write the Hijri expiry date as <code>YYYY-MM-DD</code> (e.g. <code>1448-04-05</code>).</li>
                                    <li>The Gregorian date (<code>iqama_exp_g</code>) is calculated automatically and saved as <code>YYYY-MM-DD</code>.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div> <!-- container -->
            </div> <!-- content -->

            <footer class="footer">
                <?=$site_footer ?>
            </footer>
        </div>
        <!-- ============================================================== -->
        <!-- End Right content here -->
        <!-- ============================================================== -->
    </div>
    <!-- END wrapper -->

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- App js -->
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
    SRForm.bindFilePicker($('#iqamaImportForm'));
    $('#iqamaImportForm').on('submit', function(e) {
        if (!$('#employee_file')[0].files.length) {
            e.preventDefault();
            $(this).find('.sr-filepick').addClass('is-invalid');
        }
    });

    document.getElementById('downloadSampleLink').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent default link behavior

        // Define the CSV content
        const csvContent = "iqama,iqama_exp\n" +
                           "2451234567,1448-04-05\n" +
                           "2387654321,1448-07-26\n" +
                           "2519876543,1447-05-16\n" +
                           "2498765432,1447-08-11\n" +
                           "2334567890,1448-11-13";

        // Create a Blob from the CSV content
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        
        // Create a temporary link element
        const link = document.createElement("a");

        // Use the Object URL method to create a temporary link to the blob
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "sample_iqama_import.csv");
        
        // Append to the DOM, click, and then remove
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up the Object URL
        URL.revokeObjectURL(url);
    });
    </script>
</body>
</html>
