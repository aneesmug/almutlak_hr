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
    <style>
        .sample-download a {
            cursor: pointer;
        }
    </style>
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
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card-box">
                                <h4 class="m-t-0 header-title">Import and Update Iqama Expiration Dates</h4>
                                <p>Please upload an Excel or CSV file (<code>.xlsx</code>, <code>.xls</code>, <code>.csv</code>) with two columns in this order: <strong>iqama</strong> and <strong>iqama_exp</strong>.</p>
                                <p>The first row should be a header and will be skipped.</p>
                                <p class="text-muted">Note: The Gregorian date (<code>iqama_exp_g</code>) will be calculated automatically from the Hijri date and saved in <code>YYYY-MM-DD</code> format.</p>

                                <div class="alert alert-info sample-download">
                                    <p class="mb-1">Need a template? <a id="downloadSampleLink" class="font-weight-bold">Click here to download a sample file.</a></p>
                                    <small>You can open this file in Excel, add your employee data, and then upload it using the form below.</small>
                                </div>

                                <?php if (isset($_GET['status'])): ?>
                                    <?php if ($_GET['status'] == 'success'): ?>
                                        <div class="alert alert-success">
                                            <?=htmlspecialchars($_GET['updated_count']); ?> records updated successfully.
                                            <?php if (isset($_GET['not_found_count']) && $_GET['not_found_count'] > 0): ?>
                                                <br><?=htmlspecialchars($_GET['not_found_count']); ?> records failed because the Iqama number was not found.
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($_GET['status'] == 'error'): ?>
                                        <div class="alert alert-danger">
                                            <strong>Error:</strong> <?=htmlspecialchars($_GET['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <form action="./includes/process_iqama_import.php" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="employee_file">Select File:</label>
                                        <input type="file" name="employee_file" id="employee_file" class="form-control-file" required accept=".xlsx, .xls, .csv">
                                    </div>
                                    <button type="submit" name="import" class="btn btn-primary">Upload and Process</button>
                                </form>
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
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
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
