<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
    include("./includes/avatar_select.php");
    
    $comparison_results = [];
    $upload_error = '';
    $upload_success = '';
    
    if(isset($_POST['upload_excel'])){
        if(isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0){
            require 'vendor/autoload.php';
            
            $allowed_extensions = ['xls', 'xlsx'];
            $file_extension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
            
            if(in_array(strtolower($file_extension), $allowed_extensions)){
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                    
                    // Skip header row
                    array_shift($rows);
                    
                    foreach($rows as $row){
                        if(empty($row[0])) continue; // Skip empty rows
                        
                        $emp_id = trim($row[0]);
                        $old_system_balance = floatval($row[1] ?? 0);
                        
                        // Get employee details
                        $emp_query = mysqli_query($conDB, "SELECT 
                            emp_id, 
                            name, 
                            vacation_days, 
                            joining_date 
                            FROM employees 
                            WHERE emp_id = '".mysqli_real_escape_string($conDB, $emp_id)."'");
                        
                        if(mysqli_num_rows($emp_query) > 0){
                            $emp_data = mysqli_fetch_assoc($emp_query);
                            
                            // Get current balance from emp_vacation_balance
                            $current_balance = 0.0;
                            $debug_info = '';
                            
                            $vac_balance_q = mysqli_query($conDB, "SELECT available_balance FROM emp_vacation_balance WHERE emp_id = '".mysqli_real_escape_string($conDB, $emp_id)."' ORDER BY id DESC LIMIT 1");
                            if($vac_balance_q && mysqli_num_rows($vac_balance_q) > 0){
                                $vac_row = mysqli_fetch_assoc($vac_balance_q);
                                $current_balance = floatval($vac_row['available_balance']);
                                $debug_info = "From emp_vacation_balance: " . round($current_balance, 2);
                            } else {
                                $debug_info = "No vacation balance record found";
                            }
                            
                            $current_balance = max(0, $current_balance);
                            $difference = $current_balance - $old_system_balance;
                            
                            $comparison_results[] = [
                                'emp_id' => $emp_id,
                                'emp_name' => $emp_data['name'],
                                'old_balance' => $old_system_balance,
                                'current_balance' => round($current_balance, 2),
                                'difference' => round($difference, 2),
                                'status' => abs($difference) < 0.01 ? 'match' : 'mismatch',
                                'debug_info' => $debug_info
                            ];
                        } else {
                            $comparison_results[] = [
                                'emp_id' => $emp_id,
                                'emp_name' => 'NOT FOUND',
                                'old_balance' => $old_system_balance,
                                'current_balance' => 0,
                                'difference' => 0,
                                'status' => 'not_found'
                            ];
                        }
                    }
                    
                    $upload_success = 'File processed successfully. Total records: ' . count($comparison_results);
                    
                } catch (Exception $e) {
                    $upload_error = 'Error processing file: ' . $e->getMessage();
                }
            } else {
                $upload_error = 'Invalid file format. Please upload .xls or .xlsx file.';
            }
        } else {
            $upload_error = 'Please select a file to upload.';
        }
    }
?>
<!doctype html>
<html lang="<?=$current_lang?>" dir="<?=($is_rtl) ? 'rtl' : 'ltr'?>">
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?=__('Compare Vacation Balance');?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    
    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    
    <style>
        .match { background-color: #d4edda !important; }
        .mismatch { background-color: #fff3cd !important; }
        .not_found { background-color: #f8d7da !important; }
    </style>
</head>
<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>
        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title mb-4"><?=__('Compare Vacation Balance');?></h4>
                                
                                <?php if($upload_error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($upload_error); ?></div>
                                <?php endif; ?>
                                
                                <?php if($upload_success): ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($upload_success); ?></div>
                                <?php endif; ?>
                                
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title"><?=__('Upload Excel File');?></h5>
                                        <p class="text-muted"><?=__('Upload an Excel file with Employee ID (Column A) and Old System Balance (Column B)');?></p>
                                        
                                        <form method="post" enctype="multipart/form-data">
                                            <div class="form-row">
                                                <div class="form-group col-md-8">
                                                    <input type="file" name="excel_file" class="form-control" accept=".xls,.xlsx" required>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <button type="submit" name="upload_excel" class="btn btn-primary btn-block">
                                                        <i class="mdi mdi-upload"></i> <?=__('Upload & Compare');?>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="alert alert-info mt-3">
                                            <strong><?=__('Excel Format:');?></strong><br>
                                            <ul class="mb-0">
                                                <li><?=__('Column A: Employee ID');?></li>
                                                <li><?=__('Column B: Old System Balance (Days)');?></li>
                                                <li><?=__('First row will be treated as header and skipped');?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if(!empty($comparison_results)): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?=__('Comparison Results');?></h5>
                                        
                                        <div class="mb-3">
                                            <span class="badge badge-success">Match: <?= count(array_filter($comparison_results, function($r){ return $r['status'] == 'match'; })); ?></span>
                                            <span class="badge badge-warning">Mismatch: <?= count(array_filter($comparison_results, function($r){ return $r['status'] == 'mismatch'; })); ?></span>
                                            <span class="badge badge-danger">Not Found: <?= count(array_filter($comparison_results, function($r){ return $r['status'] == 'not_found'; })); ?></span>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th><?=__('Employee ID');?></th>
                                                        <th><?=__('Employee Name');?></th>
                                                        <th><?=__('Old System Balance');?></th>
                                                        <th><?=__('Current System Balance');?></th>
                                                        <th><?=__('Difference');?></th>
                                                        <th><?=__('Status');?></th>
                                                        <th><?=__('Calculation Info');?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($comparison_results as $result): ?>
                                                    <tr class="<?= $result['status']; ?>">
                                                        <td><?= htmlspecialchars($result['emp_id']); ?></td>
                                                        <td><?= htmlspecialchars($result['emp_name']); ?></td>
                                                        <td class="text-right"><?= number_format($result['old_balance'], 2); ?></td>
                                                        <td class="text-right"><strong><?= number_format($result['current_balance'], 2); ?></strong></td>
                                                        <td class="text-right">
                                                            <?php if($result['difference'] > 0): ?>
                                                                <span class="text-success">+<?= number_format($result['difference'], 2); ?></span>
                                                            <?php elseif($result['difference'] < 0): ?>
                                                                <span class="text-danger"><?= number_format($result['difference'], 2); ?></span>
                                                            <?php else: ?>
                                                                <?= number_format($result['difference'], 2); ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if($result['status'] == 'match'): ?>
                                                                <span class="badge badge-success"><?=__('Match');?></span>
                                                            <?php elseif($result['status'] == 'mismatch'): ?>
                                                                <span class="badge badge-warning"><?=__('Mismatch');?></span>
                                                            <?php else: ?>
                                                                <span class="badge badge-danger"><?=__('Not Found');?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><small class="text-muted"><?= isset($result['debug_info']) ? htmlspecialchars($result['debug_info']) : 'N/A'; ?></small></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
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
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
</body>
</html>
<?php } ?>
