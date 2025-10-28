<?php
/*
MODIFICATION SUMMARY (001-smt_print.php):
- MODIFIED: Main query updated to fetch new status columns (`current_status`, `current_approval_level`, `payable_by_emp_id`) and remove joins to old, non-existent approver columns.
- REMOVED: Tally ID and Injazat ID from the right-side details block.
- MODIFIED: Status `switch` block updated to use the new statuses (`draft`, `pending_approval`, `approved`, `rejected`, `paid`) and display the current level or payment status.
- MODIFIED: Approval Status Trail section completely replaced with the dynamic logic from `open_request.php`, using the `get_approval_chain_status()` function.
- ADDED: "Payable Assigned To" block now displays below the approval trail, matching the style.
- ADDED: "Payment Information" section now appears if the request status is 'paid', querying the `smt_payment` table for details.
- FIXED: All item and total costs in the table and summary now use `number_format($val, 2)` to correctly display decimal values.
*/
 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php'; // This should include custom_functions.php
 include("./includes/convertNumbersToWords.php");
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");
 
 include("./includes/Hijri_GregorianConvert.php");
 $DateConv=new Hijri_GregorianConvert;
 $format="DD/MM/YYYY";
 
 // Updated query to fetch new status columns
 $getquery = mysqli_query($conDB, "SELECT 
            `smt`.*, 
            SUM(`smt`.`total_cost`) as subtotal, 
            SUM(`smt`.`vat_val`) as vat_val,
            `dpt`.`dep_nme`
            FROM `smart_request` `smt`
            LEFT JOIN `department` `dpt` ON `dpt`.`id` = `smt`.`department`
            WHERE `smt`.`inv_no`='" . escape_string($_GET['id']) . "'
            GROUP BY `smt`.`inv_no`");

 if(mysqli_num_rows($getquery) !== 0){
 
    while($row = mysqli_fetch_assoc($getquery)){
        $invnoget = $row["inv_no"];
        // REMOVED: $tally_id_get = $row["tally_id"];
        // REMOVED: $injazat_id_get = $row["injazat_id"];
        $deptget = $row["dep_nme"];
        $createdatget = $row["created_at"];
        $subtypeget = $row["sub_type"];
        $sub_title_get = $row["sub_title"];
        $prep_by_get = (explode(" ",$row["prep_by"])[0])." ".(explode(" ",$row["prep_by"])[1]);
        $department_get = $row["dep_nme"];
        $remarks_get = $row["remarks"];
        
        $total_costget = $row['subtotal'];
        $vat_get = $row['vat_val'];
        $discount_get = $row["discount"];

        // New Status Columns
        $current_status_get = $row['current_status'];
        $current_approval_level_get = $row['current_approval_level'];
        $payable_by_emp_id_get = $row['payable_by_emp_id'];

        // REMOVED: Old status variables

        $total_cost_get = $total_costget - $vat_get;
        $total = $total_cost_get + $vat_get;
        $gtotal = $total - $discount_get;
        
        $timestamp_reg = strtotime($createdatget);
        $created_at_get = date('d, M Y', $timestamp_reg);

        // Fetch assigned payer name if ID exists
        $assigned_payer_name = null;
        if ($payable_by_emp_id_get) {
            $payerDetails = getEmployeeDetails($conDB, $payable_by_emp_id_get);
            if ($payerDetails && $payerDetails['name'] !== 'N/A') {
                $assigned_payer_name = $payerDetails['name'];
            }
        }
    }
  
} else {
 //when the id not equals id show database
 header("Location: ./reg_employee.php"); // Consider redirecting to all_requests.php
 exit; // Add exit
}

    // Updated Status Display Logic
    $status_get_display = "";
    switch ($current_status_get) {
        case "draft":
            $status_get_display = "<span class='badge badge-secondary font-14'>" . __('draft_status') . "</span>";
            break;
        case "pending_approval":
            $status_get_display = "<span class='badge badge-custom font-14'>" . __('pending_approval_level') . " " . $current_approval_level_get . "</span>";
            break;
        case "approved":
            if ($assigned_payer_name) {
                $status_text_approved = __('approved_pending_payment');
            } else {
                $status_text_approved = __('approved_pending_assignment');
            }
            $status_get_display = "<span class='badge badge-success font-14'>" . $status_text_approved . "</span>";
            break;
        case "rejected":
            $status_get_display = "<span class='badge badge-danger font-14'>" . __('rejected') . "</span>";
            break;
        case "paid":
            $status_get_display = "<span class='badge badge-purple font-14'>" . __('payment_paid') . "</span>";
            break;
        default:
            $status_get_display = "<span class='badge badge-danger font-14'>" . __('unknown_status') . "</span>";
    }

?>
<!doctype html> 
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('print_smart_request')?></title>
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
            body {
                -webkit-print-color-adjust: exact !important; /* Chrome, Safari */
                color-adjust: exact !important; /* Firefox */
            }
            .approval-status {
                padding: 10px;
                margin-bottom: 10px;
                border-left: 4px solid #ccc;
                background-color: #f9f9f9 !important; /* Ensure background prints */
            }
            .approval-status.pending { border-color: #ffc107; background-color: #fffaf0 !important; }
            .approval-status.approved { border-color: #28a745; background-color: #f0fff4 !important; }
            .approval-status.rejected { border-color: #dc3545; background-color: #fff0f1 !important; }
            .approval-status.awaiting { border-color: #e0e0e0; background-color: #fafafa !important; }

            /* Badge styles for printing */
            .badge {
                border: 1px solid #000;
                color: #000;
                background-color: #fff !important; /* Default simple badge */
            }
            .badge-secondary { border-color: #6c757d; color: #6c757d; }
            .badge-custom { border-color: #4351b0; color: #4351b0; }
            .badge-success, .badge-purple { border-color: #28a745; color: #28a745; }
            .badge-danger { border-color: #dc3545; color: #dc3545; }
            .badge-warning { border-color: #ffc107; color: #ffc107; }
            
            @media print {
                .hidden-print { display: none !important; }
                .content-page, .content { margin-left: 0 !important; margin-top: 0 !important; padding: 0 !important; }
                .card-box { box-shadow: none !important; border: 1px solid #ddd; }
                .left.side-menu, .topbar, .footer { display: none !important; }
            }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>
    <body class="enlarged" data-keep-enlarged="true" onLoad="javascript:window.print()">
        <div id="wrapper">
            <!-- Left side menu and topbar will be hidden by print CSS -->
            <div class="left side-menu hidden-print">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="20"></span>
                            <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                        </a>
                    </div>
                    <?php include("./includes/main_menu.php"); ?>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div class="content-page">
                <div class="topbar hidden-print">
                    <?php include("./includes/topbar.php"); ?>
                </div>
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12" id="DataContact">
                                <div class="card-box">
                                    <div class="clearfix">
                                        <div class="float-left mb-3">
                                            <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="100">
                                        </div>
                                        <div class="float-right">
                                            <h4 class="m-0"><?=__('smart_table_request')?></h4>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                          <div class="mt-3 float-left">
                                                <p class="m-b-10"><strong><?=__('request_date')?>: </strong> <?= $created_at_get ?></p>
                                                <p class="m-b-10"><strong><?=__('subject_type')?>: </strong> <?= htmlspecialchars($subtypeget) ?></p>
                                                <p class="m-b-10"><strong><?=__('subject_title')?>: </strong> <?= htmlspecialchars($sub_title_get) ?></p>
                                                <?php if ($remarks_get): ?>
                                                    <p class="m-b-10"><strong><?=__('remarks')?>: </strong> <?= htmlspecialchars($remarks_get) ?></p>
                                                <?php endif; ?>
                                                <p class="m-b-10"><strong><?=__('request_status')?>: </strong><?= $status_get_display ?></p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mt-3 float-right">
                                                <p class="m-b-10"><strong><?=__('request_id')?>: </strong> <?= htmlspecialchars($invnoget) ?></p>
                                                <p class="m-b-10"><strong><?=__('department')?>: </strong> <?= htmlspecialchars($department_get) ?></p>
                                                <p class="m-b-10"><strong><?=__('prepared_by')?>: </strong> <?= htmlspecialchars($prep_by_get) ?></p>
                                                <!-- REMOVED Tally ID and Injazat ID -->
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- NEW Dynamic Approval Status Trail -->
                                    <div class="row">
                                        <div class="col-12 mt-4">
                                            <h5><?=__('approval_status')?></h5>
                                            <?php
                                                // PASS $conDB
                                                $approval_chain = get_approval_chain_status($conDB, $invnoget, 'smart_request');
                                                if (empty($approval_chain) && $current_status_get == 'draft') {
                                                    echo "<div class='approval-status awaiting'><small>" . __('approval_chain_not_defined_yet') . "</small></div>";
                                                }
                                                foreach ($approval_chain as $step):
                                                    $status_class = $step['status']; // 'pending', 'approved', 'rejected', 'awaiting'
                                                    $status_text = __($step['status']);
                                                    $action_date = $step['action_date'] ? date('d M Y H:i', strtotime($step['action_date'])) : '';
                                            ?>
                                            <div class="approval-status <?= $status_class ?>">
                                                <strong><?=__('level')?> <?= $step['approval_level'] ?>: <?= parseName($step['approver_name']) ?></strong>
                                                <span class="float-right"><?= $status_text ?></span>
                                                <?php if($action_date): ?>
                                                    <br><small><?=__('on')?> <?= $action_date ?></small>
                                                <?php endif; ?>
                                                <?php if($step['note']): ?>
                                                    <br><small><em><?=__('note')?>: <?= htmlspecialchars($step['note']) ?></em></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>

                                            <!-- NEW: Show Assigned Payer -->
                                            <?php if ($assigned_payer_name): ?>
                                            <div class="approval-status approved">
                                                <strong><?=__('payable_assigned_to')?>: <?= htmlspecialchars($assigned_payer_name) ?></strong>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <!-- END NEW Approval Status Trail -->

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table mt-4">
                                                    <thead>
                                                    <tr><th width="70">#</th>
                                                        <th><?=__('description_item_name_invoice_num')?></th>
                                                        <th width="120"><?=__('location')?></th>
                                                        <th width="80"><?=__('quantity')?></th>
                                                        <th width="120"><?=__('unit_cost')?></th>
                                                        <th width="130"><?=__('item_value')?></th>
                                                        <th width="70"><?=__('vat_percent')?></th>
                                                        <th width="100"><?=__('vat_val')?></th>
                                                        <th width="130"><?=__('amount')?></th>
                                                        <th width="100"><?=__('discount')?></th>
                                                        <th width="120" class="text-right"><?=__('total')?></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $x = 1;
                                                    $getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='".escape_string($_GET['id'])."' ");
                                                    while($rec = mysqli_fetch_assoc($getdataloop)){
                                                ?>
                                                    <tr class="set">
                                                        <th scope="row"><?= $x++ ?></th>
                                                        <td><?= htmlspecialchars($rec["item_name"]); ?></td>
                                                        <td><?= htmlspecialchars($rec["location"]); ?></td>
                                                        <td class="text-center"><?= $rec["quantity"]; ?></td>
                                                        <td class="text-center"><?= number_format($rec["product_price"], 2); ?></td>
                                                        <td class="text-center"><?= number_format($rec["itmvalue"], 2); ?></td>
                                                        <td class="text-center"><?= number_format($rec["vat_rate"], 2); ?></td>
                                                        <td class="text-center"><?= number_format($rec["vat_val"], 2); ?></td>
                                                        <td class="text-center"><?= number_format($rec["amount"], 2); ?></td>
                                                        <td class="text-center"><?= number_format($rec["idiscount"], 2); ?></td>
                                                        <td class="text-right"><?= number_format($rec["total_cost"], 2); ?></td> 
                                                    </tr>
                                                <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                        <?php
                                         $queryempdocu = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='".escape_string($_GET['id'])."' "); 
                                         if($queryempdocu && mysqli_num_rows($queryempdocu) > 0):
                                        ?>
                                           <h4><?=__('attachments')?></h4>
                                           <ul>
                                            <?php                                               
                                                while($recempdoc = mysqli_fetch_assoc($queryempdocu)){
                                                    $attachment_get = $recempdoc["attachment"];
                                                    echo "<li>".htmlspecialchars($attachment_get)."</li>"; // Don't make link on print page
                                                }
                                            ?>
                                           </ul>
                                          <?php endif; ?>
                                        </div>
                                        <div class="col-8" id="gtotal">
                                            <div class="float-right">
                                                <p><strong><?=__('net_total_without_vat')?>:</strong> <span class="float-right"><?= number_format(round($total_cost_get,2), 2); ?> <?=__('sar')?></span></p>
                                                <p><strong><?=__('vat_15_percent')?>:</strong> <span class="float-right"><?= number_format(round($vat_get,2), 2); ?> <?=__('sar')?></span></p>
                                                <p><strong><?=__('total_before_disc')?>:</strong> <span class="float-right"><?= number_format(round($total,2), 2); ?> <?=__('sar')?></span></p>
                                                <p><strong><?=__('discount')?>:</strong> <span class="float-right"><?= number_format(round($discount_get,2), 2); ?> <?=__('sar')?></span></p>
                                                <h3><?= number_format(round($gtotal,2), 2); ?> <?=__('sar')?></h3>
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="col-12">
                                            <center><h4><u><?= ucfirst(getSaudiCurrency($gtotal)) ?></u></h4></center>
                                        </div>
                                    </div>

                                    <!-- NEW Payment Information Section -->
                                    <?php if($current_status_get == 'paid'): 
                                        $payment_details = null;
                                        $payment_query = mysqli_query($conDB, "SELECT * FROM `smt_payment` WHERE `inv_no` = '".escape_string($invnoget)."' ORDER BY `id` DESC LIMIT 1");
                                        if($payment_query && mysqli_num_rows($payment_query) > 0){
                                            $payment_details = mysqli_fetch_assoc($payment_query);
                                        }
                                    ?>
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <div class="alert alert-info" style="background-color: #f0f8ff !important; border-color: #bee5eb !important; color: #0c5460 !important;">
                                                <h5 class="alert-heading"><?=__('payment_information')?></h5>
                                                <?php if($payment_details): ?>
                                                    <p><strong><?=__('paid_amount')?>:</strong> <?= number_format($payment_details['paid_amount'], 2) ?> <?=__('sar')?></p>
                                                    <p><strong><?=__('paid_by')?>:</strong> <?= htmlspecialchars($payment_details['paid_by_name']) ?> <?=__('on')?> <?= date('d M Y H:i', strtotime($payment_details['created_at'])) ?></p>
                                                    <?php if ($assigned_payer_name): ?>
                                                        <p><strong><?=__('payable_assigned_to')?>:</strong> <?= htmlspecialchars($assigned_payer_name) ?></p>
                                                    <?php endif; ?>
                                                    <?php if($payment_details['note']): ?>
                                                        <p><strong><?=__('note')?>:</strong> <?= htmlspecialchars($payment_details['note']) ?></p>
                                                    <?php endif; ?>
                                                    <hr>
                                                    <p><strong><?=__('payment_invoice_receipt')?>:</strong> <?= htmlspecialchars($payment_details['payment_invoice']) ?></p>
                                                <?php else: ?>
                                                    <p><?=__('payment_details_not_found')?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <!-- END Payment Information Section -->


                                    <div class="hidden-print mt-4 mb-4">
                                        <div class="text-right">
                                            <a href="javascript:window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print m-r-5"></i> <?=__('print')?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="footer hidden-print">
                    <?= $site_footer ?>
                </footer>
            </div>
        </div>
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
    </body>
</html>
<?php } ?>
