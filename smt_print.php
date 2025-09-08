<?php
/*
MODIFICATION SUMMARY:
- Modified: The main database query has been completely overhauled to fetch comprehensive details, including the names of all approvers (Department Manager, Finance Manager, GM) by joining the `employees` table.
- Modified: The logic for determining the request's status and details now correctly handles the new workflow, including requests created by managers.
- Modified: The "Approval Status" section in the HTML now displays a full audit trail, showing who approved the request and when, similar to the `open_request.php` page.
- Removed: Outdated status logic and variables have been removed to align with the new system.
*/
 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php';
 include("./includes/convertNumbersToWords.php");
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");
 
 include("./includes/Hijri_GregorianConvert.php");
 $DateConv=new Hijri_GregorianConvert;
 $format="DD/MM/YYYY";
 
 // Updated query to fetch all necessary details including approver names
 $getquery = mysqli_query($conDB, "SELECT 
            `smt`.*, 
            SUM(`smt`.`total_cost`) as subtotal, 
            SUM(`smt`.`vat_val`) as vat_val,
            `dpt`.`dep_nme`,
            dm.name AS dept_manager_name,
            fm.name AS finance_manager_name,
            gm.name AS gm_name
            FROM `smart_request` `smt`
            LEFT JOIN `department` `dpt` ON `dpt`.`id` = `smt`.`department`
            LEFT JOIN `employees` dm ON dm.emp_id = smt.dept_manager_id
            LEFT JOIN `employees` fm ON fm.emp_id = smt.finance_manager_id
            LEFT JOIN `employees` gm ON gm.emp_id = smt.gm_id
            WHERE `smt`.`inv_no`='" . $_GET['id'] . "'
            GROUP BY `smt`.`inv_no`");

 if(mysqli_num_rows($getquery) !== 0){
 
    while($row = mysqli_fetch_assoc($getquery)){
        $invnoget = $row["inv_no"];
        $tally_id_get = $row["tally_id"];
        $injazat_id_get = $row["injazat_id"];
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

        $current_status_get = $row['current_status'];
        $dept_manager_status_get = $row['dept_manager_status'];
        $dept_manager_name_get = $row['dept_manager_name'];
        $dept_manager_date_get = $row['dept_manager_date'];
        $finance_manager_status_get = $row['finance_manager_status'];
        $finance_manager_name_get = $row['finance_manager_name'];
        $finance_manager_date_get = $row['finance_manager_date'];
        $gm_status_get = $row['gm_status'];
        $gm_name_get = $row['gm_name'];
        $gm_date_get = $row['gm_date'];
        $requires_gm_approval_get = $row['requires_gm_approval'];

        $total_cost_get = $total_costget - $vat_get;
        $total = $total_cost_get + $vat_get;
        $gtotal = $total - $discount_get;
        
        $timestamp_reg = strtotime($createdatget);
        $created_at_get = date('d, M Y', $timestamp_reg);
    }
  
} else {
 //when the id not equals id show database
 header("Location: ./reg_employee.php");
}

    // Updated Status Display Logic
    $status_get_display = "";
    switch ($current_status_get) {
        case "draft":
            $status_get_display = "<span class='badge badge-secondary font-14'>Draft (Not Submitted)</span>";
            break;
        case "pending_dept_manager_approval":
            $status_get_display = "<span class='badge badge-custom font-14'>Pending Department Manager Approval</span>";
            break;
        case "pending_finance_approval":
            $status_get_display = "<span class='badge badge-warning font-14'>Pending Finance Approval</span>";
            break;
        case "pending_gm_approval":
            $status_get_display = "<span class='badge badge-primary font-14'>Pending General Manager Approval</span>";
            break;
        case "approved":
            $status_get_display = "<span class='badge badge-success font-14'>Approved</span>";
            break;
        case "rejected":
            $status_get_display = "<span class='badge badge-danger font-14'>Rejected</span>";
            break;
        case "Paid":
            $status_get_display = "<span class='badge badge-success font-14'>Paid</span>";
            break;
        default:
            $status_get_display = "<span class='badge badge-danger font-14'>Unknown Status</span>";
    }

?>
<!doctype html> 
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Print Smart Request</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .approval-status {
                padding: 10px;
                margin-bottom: 10px;
                border-left: 4px solid #ccc;
            }
            .approval-status.pending { border-color: #ffc107; }
            .approval-status.approved { border-color: #28a745; }
            .approval-status.rejected { border-color: #dc3545; }
        </style>
    </head>
    <body class="enlarged" data-keep-enlarged="true" onLoad="javascript:window.print()">
        <div id="wrapper">
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="assets/images/logo.png" alt="" height="20"></span>
                            <i><img src="assets/images/logo_sm.png" alt="" height="28"></i>
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
                            <div class="col-md-12" id="DataContact">
                                <div class="card-box">
                                    <div class="clearfix">
                                        <div class="float-left mb-3">
                                            <img src="assets/images/logo.png" alt="" height="100">
                                        </div>
                                        <div class="float-right">
                                            <h4 class="m-0 d-print-none">Smart Table Request</h4>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                          <div class="mt-3 float-left">
                                                <p class="m-b-10"><strong>Request Date: </strong> <?= $created_at_get ?></p>
                                                <p class="m-b-10"><strong>Subject Type: </strong> <?= $subtypeget ?></p>
                                                <p class="m-b-10"><strong>Subject Title: </strong> <?= $sub_title_get ?></p>
                                                <?php if ($remarks_get): ?>
                                                    <p class="m-b-10"><strong>Remarks: </strong> <?= $remarks_get ?></p>
                                                <?php endif; ?>
                                                <p class="m-b-10"><strong>Request Status: </strong><?= $status_get_display ?></p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mt-3 float-right">
                                                <p class="m-b-10"><strong>Request ID: </strong> <?= $invnoget ?></p>
                                                <p class="m-b-10"><strong>Department: </strong> <?= $department_get ?></p>
                                                <p class="m-b-10"><strong>Prepared by: </strong> <?= $prep_by_get ?></p>
                                                <p class="m-b-10"><strong>Tally ID: </strong> <?= $tally_id_get ?></p>
                                                <p class="m-b-10"><strong>Injazat ID: </strong> <?= $injazat_id_get ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Approval Status Trail -->
                                    <div class="row">
                                        <div class="col-12 mt-4">
                                            <h5>Approval Status</h5>
                                            <div class="approval-status <?= $dept_manager_status_get ? ($dept_manager_status_get == 'approved' ? 'approved' : ($dept_manager_status_get == 'rejected' ? 'rejected' : 'pending')) : '' ?>">
                                                <strong>Department Manager:</strong> 
                                                <span><?= ucfirst($dept_manager_status_get ?? 'Pending') ?></span>
                                                <?php if($dept_manager_name_get): ?>
                                                    <br><small>by <?= htmlspecialchars($dept_manager_name_get) ?> on <?= $dept_manager_date_get ? date('d M Y H:i', strtotime($dept_manager_date_get)) : '' ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="approval-status <?= $finance_manager_status_get ? ($finance_manager_status_get == 'approved' ? 'approved' : ($finance_manager_status_get == 'rejected' ? 'rejected' : 'pending')) : '' ?>">
                                                <strong>Finance:</strong>
                                                <span><?= ucfirst($finance_manager_status_get ?? 'Pending') ?></span>
                                                <?php if($finance_manager_name_get): ?>
                                                    <br><small>by <?= htmlspecialchars($finance_manager_name_get) ?> on <?= $finance_manager_date_get ? date('d M Y H:i', strtotime($finance_manager_date_get)) : '' ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php if($requires_gm_approval_get == 1): ?>
                                            <div class="approval-status <?= $gm_status_get ? ($gm_status_get == 'approved' ? 'approved' : ($gm_status_get == 'rejected' ? 'rejected' : 'pending')) : '' ?>">
                                                <strong>General Manager:</strong>
                                                <span><?= ucfirst($gm_status_get ?? 'Pending') ?></span>
                                                <?php if($gm_name_get): ?>
                                                    <br><small>by <?= htmlspecialchars($gm_name_get) ?> on <?= $gm_date_get ? date('d M Y H:i', strtotime($gm_date_get)) : '' ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table mt-4">
                                                    <thead>
                                                    <tr><th width="70">#</th>
                                                        <th>Description/Item Name/Invoice Num.</th>
                                                        <th width="120">Location</th>
                                                        <th width="80">Quantity</th>
                                                        <th width="120">Unit Cost</th>
                                                        <th width="130">Item Value</th>
                                                        <th width="70">Vat%</th>
                                                        <th width="100">Vat Val</th>
                                                        <th width="130">Amount</th>
                                                        <th width="100">Discount</th>
                                                        <th width="120" class="text-right">Total</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $x = 1;
                                                    $getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='".$_GET['id']."' ");
                                                    while($rec = mysqli_fetch_assoc($getdataloop)){
                                                ?>
                                                    <tr class="set">
                                                        <th scope="row"><?= $x++ ?></th>
                                                        <td><?= $rec["item_name"]; ?></td>
                                                        <td><?= $rec["location"]; ?></td>
                                                        <td class="text-center"><?= $rec["quantity"]; ?></td>
                                                        <td class="text-center"><?= number_format($rec["product_price"]); ?></td>
                                                        <td class="text-center"><?= number_format($rec["itmvalue"]); ?></td>
                                                        <td class="text-center"><?= number_format($rec["vat_rate"]); ?></td>
                                                        <td class="text-center"><?= number_format($rec["vat_val"]); ?></td>
                                                        <td class="text-center"><?= number_format($rec["amount"]); ?></td>
                                                        <td class="text-center"><?= number_format($rec["idiscount"]); ?></td>
                                                        <td class="text-right"><?= number_format($rec["total_cost"]); ?></td> 
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
                                         $queryempdocu = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='".$_GET['id']."' "); 
                                         if(mysqli_num_rows($queryempdocu) !== 0):
                                        ?>
                                           <h4>Attachments</h4>
                                           <ul>
                                            <?php                                               
                                                while($recempdoc = mysqli_fetch_assoc($queryempdocu)){
                                                    $attachment_get = $recempdoc["attachment"];
                                                    echo "<li><a href='./assets/smt_attachment/{$attachment_get}' target='_blank'>{$attachment_get}</a></li>";
                                                }
                                            ?>
                                           </ul>
                                          <?php endif; ?>
                                        </div>
                                        <div class="col-8" id="gtotal">
                                            <div class="float-right">
                                                <p><strong>Net-Total (w/o VAT):</strong> <span class="float-right"><?= number_format(round($total_cost_get,2)); ?> SR</span></p>
                                                <p><strong>VAT 15%:</strong> <span class="float-right"><?= number_format(round($vat_get,2)); ?> SR</span></p>
                                                <p><strong>Total (Before Disc.):</strong> <span class="float-right"><?= number_format(round($total,2)); ?> SR</span></p>
                                                <p><strong>Discount:</strong> <span class="float-right"><?= number_format(round($discount_get,2)); ?> SR</span></p>
                                                <h3><?= number_format(round($gtotal,2)); ?> SR</h3>
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="col-12">
                                            <center><h4><u><?= ucfirst(getSaudiCurrency($gtotal)) ?></u></h4></center>
                                        </div>
                                    </div>

                                    <div class="hidden-print mt-4 mb-4">
                                        <div class="text-right">
                                            <a href="javascript:window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print m-r-5"></i> Print</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="footer">
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
