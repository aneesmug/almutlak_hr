    <?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    include("./includes/convertNumbersToWords.php");
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
        if(mysqli_num_rows($query) == 1){
        include("./includes/avatar_select.php");
    }

        /*$getquery = mysqli_query($conDB, "SELECT * FROM `sm_request_sr` ORDER BY id DESC LIMIT 1 "); 
        while($rec = mysqli_fetch_assoc($getquery)){
            $inv_no_get = $rec["sr"];
        }
        if ($inv_no_get !== $_GET['nid']) {
            $newinv = "SMT".number_pad(str_replace("SMT","",$empid."".$inv_no_get)+1,6);
            mysqli_query($conDB, "INSERT INTO `sm_request_sr` (`sr`) VALUES ('".$newinv."')");
            header( "refresh:0 ; url=reopen_request.php?id=$_GET[id]&nid=$newinv" );
        }*/
        
    if ($_SESSION['user_type'] == 'administrator' OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Finance") OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Management")) {
        $getquery = mysqli_query($conDB, "SELECT *, SUM(total_cost) as subtotal, SUM(vat_val) as vat_val FROM `smart_request` WHERE `inv_no`='".$_GET['id']."' ");
    } else {
        $getquery = mysqli_query($conDB, "SELECT *, SUM(total_cost) as subtotal, SUM(vat_val) as vat_val FROM `smart_request`  WHERE `inv_no`='".$_GET['id']."' AND `department`='".$user_dept."' ");
    }    
        while($row = mysqli_fetch_assoc($getquery)){
            $idno = $row["id"];
            $invnoget = $row["inv_no"];
            $tally_id_get = $row["tally_id"];
            $injazat_id_get = $row["injazat_id"];
            $total_costget = $row['subtotal'];
            $vat_get = $row['vat_val'];
            $discount_get = $row["discount"];
            $location_get = $row["location"];
            $sub_type_get = $row["sub_type"];
            $sub_title_get = $row["sub_title"];
            $approv_by_get = $row["approv_by"];
            $prep_by_get = (explode(" ",$row["prep_by"])[0])." ".(explode(" ",$row["prep_by"])[1]);
            $department_get = $row["department"];
            $total_cost_get = $total_costget - $vat_get;
            $total = $total_cost_get + $vat_get;
            $gtotal = $total - $discount_get;
        }

if(isset($_POST['submit_edit'])){
        $item_name_up = escape_string(/*$conDB,*/ $_POST['item_name']);
        $location_up = $_POST['location'];
        $quantity_up = $_POST['quantity'];
        $product_price_up = $_POST['product_price'];
        $itmvalue_up = $_POST['itmvalue'];
        $vat_rate_up = $_POST['vat_rate'];
        $vat_val_up = $_POST['vat_val'];
        $amount_up = $_POST['amount'];
        $idiscount_up = $_POST['idiscount'];
        $total_cost_up = $_POST['total_cost'];

    if($item_name_up){
        mysqli_query($conDB, "UPDATE `smart_request` SET `item_name`='".$item_name_up."', `location`='".$location_up."', `quantity`='".$quantity_up."', `product_price`='".$product_price_up."',`itmvalue`='".$itmvalue_up."', `vat_rate`='".$vat_rate_up."', `vat_val`='".$vat_val_up."', `amount`='".$amount_up."', `idiscount`='".$idiscount_up."', `total_cost`='".$total_cost_up."' WHERE `id`='".$_POST['itemid']."' ") or die (mysqli_error());
        $msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Item Modified Seccssfully!</div>
        ";      
        header( "refresh:1 ; url=reopen_request.php?id=$_GET[id]&nid=$_GET[nid]" );
    } else {
        $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
    }
}

if(isset($_POST['submit'])){    
    if(
       !empty($_POST['item_name']) && !empty($_POST['quantity']) && !empty($_POST['product_price']) 
        // && is_array($_POST['inv_no']) && is_array($_POST['item_name']) && is_array($_POST['quantity']) && is_array($_POST['product_price']) 
        //&& count($inv_no) === count($item_name) === count($quantity) === count($product_price)
    ){

            $inv_no_po = $_POST['inv_no'];
            $tally_id_po = $_POST['tally_id'];
            $injazat_id_po = $_POST['injazat_id'];
            $location_array = $_POST['location'];
            $sub_type_po = $_POST['sub_type'];
            $sub_title_po = escape_string(/*$conDB,*/ $_POST['sub_title']);
            $item_name_array = escape_string($_POST['item_name']);
            $quantity_array = $_POST['quantity'];
            $product_price_array = $_POST['product_price'];
            $itmvalue_array = $_POST['itmvalue'];
            $vat_rate_array = $_POST['vat_rate'];
            $vat_val_array = $_POST['vat_val'];
            $amount_array = $_POST['amount'];
            $idiscount_array = $_POST['idiscount'];
            $total_cost_array = $_POST['total_cost'];
            $discount_po = $_POST['discount'];
            $status_po = $_POST['status'];
            $remarks_po = escape_string(/*$conDB,*/ $_POST['remarks']);


        for ($i = 0; $i < count($location_array); $i++) {

            $item_name_po = escape_string(/*$conDB,*/ $item_name_array[$i]);
            $location_po = escape_string(/*$conDB,*/ $location_array[$i]);
            $quantity_po = escape_string(/*$conDB,*/ $quantity_array[$i]);
            $product_price_po = escape_string(/*$conDB,*/ $product_price_array[$i]);
            $itmvalue_po = escape_string(/*$conDB,*/ $itmvalue_array[$i]);
            $vat_rate_po = escape_string(/*$conDB,*/ $vat_rate_array[$i]);
            $vat_val_po = escape_string(/*$conDB,*/ $vat_val_array[$i]);
            $amount_po = escape_string(/*$conDB,*/ $amount_array[$i]);
            $idiscount_po = escape_string(/*$conDB,*/ $idiscount_array[$i]);
            $total_cost_po = escape_string(/*$conDB,*/ $total_cost_array[$i]);

            mysqli_query($conDB, "INSERT INTO `smart_request` (`inv_no`,`tally_id`,`injazat_id`,`location`, `sub_type`, `sub_title`, `item_name`, `quantity`, `product_price`, `itmvalue`, `vat_rate`, `vat_val`, `amount`, `idiscount`, `total_cost`, `discount`, `department`, `prep_by`, `remarks` ) VALUES ('".$inv_no_po."','".$tally_id_po."','".$injazat_id_po."','".$location_po."','".$sub_type_po."','".$sub_title_po."','".$item_name_po."','".$quantity_po."','".$product_price_po."','".$itmvalue_po."','".$vat_rate_po."','".$vat_val_po."','".$amount_po."','".$idiscount_po."','".$total_cost_po."','".$discount_po."','".$user_dept."','".$userwel."','".$remarks_po."' )");
        } 
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name` ) VALUES ('".$empid."', '".$inv_no_po."', '".$userwel."' )");


        $getlastid = mysqli_query($conDB, "SELECT * FROM `sm_request_sr` ORDER BY id DESC LIMIT 1 ");
        while($rec = mysqli_fetch_assoc($getlastid)){
            $lstinvno = $rec["inv_no"];
        }
        $msg = '<div class="alert alert-success bg-success text-white border-0" role="alert">Add Seccssfully!</div>';
         header( "refresh:0 ; url=open_request.php?id=$_GET[nid]" );
// }

    } else {
        $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
    }   
}



    if($emptypeget == "Manager" AND $_SESSION['user_dept'] <> "Finance" ){
        $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `dept`='Finance' AND `status`=1 AND `emptype`='Manager' ");
    } elseif ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Finance") {
        $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='2' ");  
    } elseif ($user_type == "gm") {
        $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='2' ");
    } else {
        $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `dept`='".$user_dept."' AND `status`=1 AND `emptype`='Manager' ");
        /*$query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 AND `emptype`='Manager' ");*/
    }

    /*$getstatus = mysqli_query($conDB, "SELECT * FROM `smt_request_status` WHERE `inv_no`='".$_GET['id']."' ORDER BY id DESC LIMIT 1");*/
    $getstatus = mysqli_query($conDB, "
        SELECT *, `smt_request_status`.`status` AS `smtstatus`, `smt_request_status`.`note` AS `smtnote`
        FROM `smt_request_status`
        LEFT JOIN `employees` ON `smt_request_status`.`emp_id` = `employees`.`emp_id`
        WHERE `inv_no` = '$_GET[nid]'
        ORDER BY `smt_request_status`.`id` DESC
        LIMIT 1 
    ");
    while($row = mysqli_fetch_assoc($getstatus)){
        $statusget = $row["smtstatus"];
        $invnoget = $row["inv_no"];
        $deptget = $row["dept"];
        $noteget = $row["smtnote"];
    }


while($rec = mysqli_fetch_array($query_apprv)){
    $applst[] = "<option value=\"".$rec['emp_id']."\">".$rec['name']."</option>";
}
$applist = implode(",",$applst);

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - <?php echo $name_mach ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

        <link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />
        
        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
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
                            <span>
                                <img src="assets/images/logo.png" alt="" height="22">
                            </span>
                            <i>
                                <img src="assets/images/logo_sm.png" alt="" height="28">
                            </i>
                        </a>
                    </div>

                    <!-- User box -->
                    
                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->

                    <div class="clearfix"></div>

                </div>
                <!-- Sidebar -left -->

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
            <div class="col-12">
                    <form action="reopen_request.php?id=<?php echo $_GET['id']?>&nid=<?php echo $_GET['nid']?>" method="post" >

                        <div class="card-box">  
                                <?php echo $msg ?>
                                    <div class="row">
                                        <div class="col-6 ">
                                            <div class="mt-3 float-left">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Invoice Date:</div>
                                                    </div>
                                                    <input class="form-control" type='text' value="<?php echo date("d F Y")?>" readonly />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Sub-Title *</div>
                                                    </div>
                                                    <input class="form-control" type='text' name="sub_title" required="" />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Sub. Type *</div>
                                                    </div>
                                                    <select class="form-control" name="sub_type" required>
                                                        <option value="">Select</option>
                                                    <?php
                                                        $query_sub_type = mysqli_query($conDB, "SELECT * FROM `smt_subject_type` ORDER BY `sub_type` REGEXP '^[^A-Za-z]' ASC, sub_type");
                                                        while($rec = mysqli_fetch_assoc($query_sub_type)){
                                                            $sub_type = $rec["sub_type"];
                                                    ?>
                                                        <option value="<?php echo $sub_type ?>"><?php echo $sub_type ?></option>
                                                    <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Re-open remarks *</div>
                                                    </div>
                                                    <input class="form-control" type='text' name="remarks" required="" />
                                                </div>

                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-6 ">
                                            <div class="mt-3 float-right">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Invoice No.:</div>
                                                    </div>
                                                    <input class="form-control" type='text' name='inv_no' value="<?php echo $_GET['nid'] ?>" readonly />
                                                </div> 
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Department:</div>
                                                    </div>
                                                    <input class="form-control" type='text' name='department' value="<?php echo $department_get ?>" readonly />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Prepared by:</div>
                                                    </div>
                                                    <input class="form-control" type='text' value="<?php echo $prep_by_get ?>" readonly />
                                                </div>                                               
                                                
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Tally ID.</div>
                                                    </div>
                                                    <input class="form-control" type='text' id='tally_id' name='tally_id' />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Injazat ID.</div>
                                                    </div>
                                                    <input class="form-control" type='text' id='injazat_id' name='injazat_id' />
                                                </div>
                                               
                                            </div>
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->


                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table mt-4">
                                                    <thead>
                                                    <tr><th width="70">#</th>
                                                        <th>Description/Item Name/Invoice Num.</th>
                                                        <th width="160">Location</th>
                                                        <th width="80">Quantity</th>
                                                        <th width="120">Unit Cost</th>
                                                        <th width="130">Item Value</th>
                                                        <th width="70">Vat%</th>
                                                        <th width="100">Vat Val</th>
                                                        <th width="130">Amount</th>
                                                        <th width="100">Discount</th>
                                                        <th width="150" class="text-right">Total</th>
                                                        <?php if ($statusget <> "approve" && $statusget <> "reject" ):?>
                                                            <th width="60" class="text-right"></th>
                                                        <?php endif ?>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                <?php
                                                    $x = 1;
                                                    if ($_SESSION['user_type'] == 'administrator' OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Finance") OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Management")) {
                                                        $getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='".$_GET['id']."' ");
                                                    } else {
                                                        $getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='".$_GET['id']."' AND `department`='".$user_dept."' ");

                                                    }
                                                    
                                                    while($rec = mysqli_fetch_assoc($getdataloop)){
                                                ?>
                                                    <tr class="set">
                                                        <td><input type="text" class="form-control" readonly value="<?php echo $x++ ?>" id="row"></td>
                                                        <td>
                                                            <input type="text" name="item_name[]" readonly class="form-control" value="<?php echo $rec["item_name"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input type="text" name="location[]" readonly class="form-control" value="<?php echo $rec["location"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control" readonly type='text' name='quantity[]' for="1" onkeypress="return isNumberKey(event,this)" value="<?php echo $rec["quantity"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control" type='text' data-type="product_price" name='product_price[]' for="1" readonly onkeypress="return isNumberKey(event,this)" value="<?php echo $rec["product_price"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control" type='text' data-type="itmvalue" name='itmvalue[]' for="1" readonly value="<?php echo $rec["itmvalue"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control" type='text' data-type="vat_rate" name='vat_rate[]' for="1" readonly onkeypress="return isNumberKey(event,this)" value="<?php echo $rec["vat_rate"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control" type='text' data-type="vat_val" name='vat_val[]' for="1" readonly value="<?php echo $rec["vat_val"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control" type='text' data-type="amount" name='amount[]' for="1" readonly value="<?php echo $rec["amount"]; ?>" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control" type='text' data-type="idiscount" name='idiscount[]' for="1" readonly onkeypress="return isNumberKey(event,this)" value="<?php echo $rec["idiscount"]; ?>" />
                                                        </td>
                                                        <td class="text-right">
                                                            <input class="form-control" type='text' name='total_cost[]' for='1' readonly value="<?php echo $rec["total_cost"]; ?>" />
                                                        </td>
                                                        
                                                        
                                                        <td class="text-right">
                                                            <div class="btn-group" role="group" aria-label="Edit Button">
                                                                <a href="javascript:void(0);" class="btn btn-sm btn-primary waves-effect editItemAttr bbtn" data-toggle="modal" data-target="#editItemModal" data-id="<?php echo $rec['id']?>" data-i_item_name="<?php echo $rec['item_name']?>" data-i_quantity="<?php echo $rec['quantity']?>" data-i_product_price="<?php echo $rec['product_price']?>" data-i_vat_rate="<?php echo $rec['vat_rate']?>" data-i_idiscount="<?php echo $rec['idiscount']?>" data-i_itmvalue="<?php echo $rec['itmvalue']?>" data-i_vat_val="<?php echo $rec['vat_val']?>" data-i_amount="<?php echo $rec['amount']?>" data-i_total_cost="<?php echo $rec['total_cost']?>" data-i_location="<?php echo $rec['location']?>" >
                                                                    <i class="mdi mdi-table-edit"></i>
                                                                </a>
                                                                <a href="./includes/delete.php?tbl=smart_request&id=<?php echo $rec["id"] ?>"  class="btn_remove btn btn-danger btn-sm bbtn" onclick="return confirm('Are you sure you want to delete this item?');">
                                                                    <i class="mdi mdi-database-minus"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                                    </tbody>
                                                </table>
                                                <!-- <input class="form-control" type='hidden' data-type="product_id_1" id='product_id_1' name='product_id[]'/> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-9"></div>
                                        <div class="col-3" id="gtotal">
                                            <div class="float-right">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Net-Total <small>(without VAT)</small></div>
                                                    </div>
                                                    <input class="form-control subtotal" type='text' id='subtotal' name='subtotal' readonly value="<?php echo round($total_cost_get,2); ?>" />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">VAT 15%</div>
                                                    </div>
                                                    <input class="form-control vat" type='text' id='vat' name='vat' readonly value="<?php echo round($vat_get,2); ?>" />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Total <small>(Before Disc.)</small></div>
                                                    </div>
                                                    <input class="form-control total" type='text' id='total' name='total' readonly value="<?php echo round($total,2); ?>" />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Discount</div>
                                                    </div>
                                                    <input class="form-control discount" type='text' data-type="discount" id='discount' name='discount' onkeypress="return isNumberKey(event,this)" value="<?php echo round($discount_get,2); ?>" <?php echo ($statusget == "Manager" AND $emptypeget == "Manager" AND $_SESSION['user_dept'] == $deptget)? "" : "readonly";?> />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Grand Total</div>
                                                    </div>
                                                    <input class="form-control grandtotal" type='text' id='grandtotal' name='grandtotal' readonly value="<?php echo round($gtotal,2); ?>" />
                                                </div>
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>

                                    </div>

                                    <div class="hidden-print mt-4 mb-4">
                                        <div class="text-right">
                                            <!-- <a href="javascript:window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print m-r-5"></i> Print</a> -->
                                            <button type="submit" name="submit" class="btn btn-info waves-effect waves-light">Save</button>
                                        </div>
                                    </div>

                                </div>

                                <?php /* if ($_SESSION['user_dept'] <> "Finance" AND $_SESSION['user_type'] <> 'gm' AND $emptypeget == "Manager"):?>
                                    <input type='text' name="status" value="Finance" readonly/>
                                <?php elseif ($_SESSION['user_type'] == 'employee' AND $emptypeget == "Supporter"):?>
                                    <input type='text' name="status" value="Manager" readonly />
                                <?php endif */ ?>
                                
                                </form>

        </div>
    </div> <!-- container -->

</div> <!-- content -->

<footer class="footer">
    <?php echo $site_footer ?>
</footer>

</div>

            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->


<div class="modal fade" id="editItemModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <section class="contact-form">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel17">Edit Item Details</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="orders">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="item_name">Item Name</label>
                                <input type="text" name="item_name" class="form-control item_name"  >
                            </div>
                            <div class="form-group col-md-3">
                                <label for="i_location">Location</label>
                                <select class="form-control i_location" name="location">
                                    <option value="">Select</option>
                                <?php
                                    $query_sectin_nme = mysqli_query($conDB, "SELECT DISTINCT `section_name` FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name");
                                    while($rec = mysqli_fetch_assoc($query_sectin_nme)){
                                        $sectin_nme = $rec["section_name"];
                                ?>
                                    <option value="<?php echo $sectin_nme ?>"><?php echo str_replace(' ', '', $sectin_nme) ?></option>
                                <?php } ?>
                                </select>
                            </div>
                            <div class="form-group col-md-1">
                                <label for="quantity">Quantity</label>
                                <input type="text" name="quantity" class="form-control quantity" id='quantity'>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="product_price">Unit Cost</label>
                                <input type="text" name="product_price" class="form-control product_price" id='product_price'>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="vat_rate">Item Value</label>
                                <input type='text' id='itmvalue' class="form-control itmvalue" name='itmvalue' readonly />
                            </div>
                            <div class="form-group col-md-2">
                                <label for="vat_rate">Vat Rate %</label>
                                <input type="text" name="vat_rate" class="form-control vat_rate" id="vat_rate">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="vat_rate">Vat Val. %</label>
                                <input type='text' class="form-control vat_val i_vat_val" id='vat_val' name='vat_val' readonly />
                            </div>
                            <div class="form-group col-md-3">
                                <label for="vat_rate">Amount</label>
                                <input type='text' class="form-control amount i_amount" id='amount' name='amount' readonly />
                            </div>
                            <div class="form-group col-md-2">
                                <label for="idiscount">Discount</label>
                                <input type="text" name="idiscount" class="form-control idiscount" id='idiscount' >
                            </div>
                            <div class="form-group col-md-3">
                                <label for="vat_rate">Total</label>
                                <input type='text' class="form-control total_cost i_total_cost" id='total_cost' name='total_cost' readonly />
                            </div>

                        </div>
                        <span id="fav" class="d-none"></span>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" id="itemid" name="itemid">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="submit" name="submit_edit" class="btn btn-info">Update Details</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>


<div class="modal fade upload_documents" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
<!--    <div class="modal-dialog modal-lg" style="max-width: 1450px !important">-->
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2D7BF4 !important; color: #fff !important;">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myLargeModalLabel">
                    <i class="mdi mdi-image-filter-tilt-shift "></i> 
                    Upload Documents for <?php echo $section_name ?>
                </h4>
            </div>
            <div class="modal-body">
<!---->
                
        <div class="row">

            <div class="col-12">
            <div class="card-box">
                <h4 class="header-title m-t-0">Dropzone File Upload</h4>
                <p class="text-muted font-14 m-b-10">
                    Your awesome text goes here.
                </p>
                <form action="#" class="dropzone" id="dropzone">
                    <div class="fallback">
                        <input name="file" type="file" multiple />
                    </div>

                </form>
            </div>
            </div>
            
        </div>
                
<!---->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success waves-effect waves-light" id="startUpload"><i class="mdi mdi-backup-restore"></i> Upload</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>


        <!-- Modal-Effect -->
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
<!--        <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>-->
        <script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
<!--        <script src="https://cdn.jsdelivr.net/gh/RobinHerbots/jquery.inputmask@5.0.0-beta.87/dist/jquery.inputmask.min.js" type="text/javascript"></script>-->
        

        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>

        <script src="./plugins/switchery/switchery.min.js"></script>
        <script src="./plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.min.js"></script>
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-maxlength/bootstrap-maxlength.js" type="text/javascript"></script>
        
        
        <script type="text/javascript" src="./plugins/autocomplete/jquery.mockjax.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/countries.js"></script>
<!--        <script type="text/javascript" src="assets/pages/jquery.autocomplete.init.js"></script>-->

        <!-- App js -->
        <script src="assets/pages/jquery.form-pickers.init.js"></script>
        <!-- <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script> -->
        
        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        <script src="assets/js/num-word.js"></script>

<script>

/***************************/
 jQuery('.showAttach').on('click', function(event) { 
  var id = $(this).data('id');  
  var img = $(this).data('i_attachment');
  $(".previewImg").empty().append("<iframe src="+"./assets/assets/smt_attachment/"+img+" frameborder='0' scrolling='no' id='iFramePreview'></iframe");
    jQuery('.preview').show('slow');
        $("#DataContact").addClass('col-md-8');
        $("#DataContact").removeClass('col-md-12');
    });
    jQuery('#closeTab').on('click', function(event) { 
        jQuery('.preview').hide('slow');
        $("#DataContact").removeClass('col-md-8');
        $("#DataContact").addClass('col-md-12');
    });
/****************************/

        //Disabling autoDiscover
        Dropzone.autoDiscover = false;
        $(function() {
            var myDropzone = new Dropzone(".dropzone", {
                url: "upload_smt_attachments.php?id=<?php echo $invnoget ?>",
                paramName: "file",
                maxFilesize: 5,
                maxFiles: 10,
                acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp,application/pdf",
                parallelUploads: 10,
                autoProcessQueue: false,
                
                init: function() {
                    this.on('success', function(){
                        if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {
                                // reload after 5 sec
                                setTimeout(function() {
                                    location.reload();
                                }, 3000);
                        }
                    });
                }
            });
            $('#startUpload').click(function(){           
                myDropzone.processQueue();
            });

        });

        function showAttachment(){
            $("#attachmentDIV").removeClass("noneDIV");
            // $("#checkatt").removeClass("noneDIV");
            $("#checkatt").prop('required',true);
        }
        function hideAttachment(){
            $("#attachmentDIV").addClass("noneDIV");
            // $("#checkatt").addClass("noneDIV");
            $("#checkatt").prop('required',null);
        }

        $('.checkattach').click(function() {
            $('#checkatt')      .val($(this).data('attach'));
        });


         $(document).ready(function(){
            $("#statlist").change(function(){
                $( "#statlist option:selected").each(function(){
                    if($(this).attr("value")=="reject"){
                        $("#RejectDIV").show();
                        $("#ApproveDIV").hide();
                        $("#RejectInput").prop('required',true);
                        $("#ApproveInput").prop('required',null);
                        $("input#RejectInput:text").attr('name','note');
                        $("select#ApproveInput").removeAttr('name');
                    }

                    if($(this).attr("value")=="approve"){
                        $("#ApproveDIV").hide();
                        $("#RejectDIV").hide();
                        $("#ApproveInput").prop('required',null);
                        $("#RejectInput").prop('required',null);
                        $("input#RejectInput:text").removeAttr('name');
                        $("select#ApproveInput").removeAttr('name');   
                    }

                    if($(this).attr("value")=="Management"){
                        $("#ApproveDIV").show();
                        $("#RejectDIV").hide();
                        $("#ApproveInput").prop('required',true);
                        $("#RejectInput").prop('required',null);
                        $("input#RejectInput:text").removeAttr('name');
                        $("select#ApproveInput").attr('name','approv_by');   
                    }

                    if ($(this).attr("value") == ""){
                        $("#RejectDIV").hide();
                        $("#ApproveDIV").hide();
                        $("#RejectInput").prop('required',null);
                        $("#ApproveInput").prop('required',null);
                        $("input#RejectInput:text").removeAttr('name');
                        $("select#ApproveInput").removeAttr('name');
                    }
                });
            }).change();
        });


        </script>
        <script>
        // $(function() {
        //   $('input').on('input', function() {
        //     // Find the closest set and recalculate it
        //     var set = $(this).closest('.set');
        //     // Get your values
        //     var n1 = parseInt(set.find('.n1').val() || 0);
        //     var n2 = parseInt(set.find('.n2').val() || 0);
        //     // Set their result
        //     set.find('.result').val(n1 * n2);
        //     set.find('.resultPlus').val(n1 + n2);
        //     set.find('.resultDiv').val(n1 / n2);
        //   });
        // });
        /*document.addEventListener('keydown', (e) => {
            if (e.key.toLowerCase() === '+' && e.ctrlKey && e.shiftKey) {
                e.preventDefault();

                // Add your code here
                alert('S key pressed with ctrl');
            }
        });*/
        </script>

        <script type="text/javascript">
            /*only allow numeric input*/
            function isNumberKey(evt, obj) {

                var charCode = (evt.which) ? evt.which : event.keyCode
                var value = obj.value;
                var dotcontains = value.indexOf(".") != -1;
                if (dotcontains)
                    if (charCode == 46) return false;
                if (charCode == 46) return true;
                if (charCode > 31 && (charCode < 48 || charCode > 57))
                    return false;
                return true;
            }
            /*only allow numeric input*/
        </script>

        <script type="text/javascript">
        // $(document).ready(function() {
        $(document).ready(function() {
            /*var rowCount = 1;
        $('#add').click(function() {
            rowCount++;
            $('#orders').append('<tr id="row'+rowCount+'">'+
                '<td><input type="text" class="form-control rowid" value="'+rowCount+'" readonly></td>'+
                '<td><input type="text" name="item_name[]" placeholder="Enter item name" class="form-control" id="item_name" required autocomplete="off"></td>'+
                '<td><input class="form-control quantity" type="text" id="quantity_'+rowCount+'" name="quantity[]" for="'+rowCount+'" required onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td><input class="form-control product_price" type="text" data-type="product_price" id="product_price_'+rowCount+'" name="product_price[]" for="'+rowCount+'" required onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td><input class="form-control itmvalue" type="text" data-type="itmvalue" id="itmvalue_'+rowCount+'" name="itmvalue[]" for="'+rowCount+'" readonly /></td>'+
                '<td><input class="form-control vat_rate" type="text" data-type="vat_rate" id="vat_rate_'+rowCount+'" name="vat_rate[]" for="'+rowCount+'" required value="15" onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td><input class="form-control vat_val" type="text" data-type="vat_val" id="vat_val_'+rowCount+'" name="vat_val[]" for="'+rowCount+'" readonly /></td>'+
                '<td><input class="form-control amount" type="text" data-type="amount" id="amount_'+rowCount+'" name="amount[]" for="'+rowCount+'" readonly /></td>'+
                '<td><input class="form-control idiscount" type="text" data-type="idiscount" id="idiscount_'+rowCount+'" name="idiscount[]" for="'+rowCount+'" required value="0" onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td class="text-right"><input class="form-control total_cost" type="text" id="total_cost_'+rowCount+'" name="total_cost[]" for="'+rowCount+'" readonly /></td>'+
                '<td class="text-right"><a href="javascript:void(0);" class="btn_remove btn btn-danger btn-sm bbtn" id="'+rowCount+'" title="Remove field"><i class="mdi mdi-database-minus"></a></td></tr>'+
                '');
        });
*/
        // Add a generic event listener for any change on quantity or price classed inputs
        $("#orders").on('input', 'input.quantity,input.product_price,input.vat_rate,input.idiscount', function() {
          getTotalCost($(this).attr("for"));
        });
        // Using a new index rather than your global variable i
        function getTotalCost(ind) {
            var qty = $('#quantity').val();
            var price = $('#product_price').val();
            var itmvalue = (qty * price);
            $('#itmvalue').val( round(itmvalue,2) );
            var ivat = $('#vat_rate').val();
            var idesc = $('#idiscount').val();
            var totNumber = (qty * price);
            var vatValue = (totNumber * ivat / 100);
            // var tot = totNumber.toFixed(2);
            var sub_tot = (vatValue + totNumber)

            $('#vat_val').val( round(vatValue,2) );
            $('#amount').val( round(sub_tot,2) );
            $('#total_cost').val( round(sub_tot - idesc,2) );

            // calculateSubTotal();
        }


        /*$("#gtotal").on('input', 'input.discount', function() {
          calculateSubTotal($(this).attr("for"));
        });
        function calculateSubTotal() {
            var subtotal = 0;
            var totalvat = 0;

            var disc = $('#discount').val();
            $('.total_cost').each(function() {
                subtotal += parseFloat($(this).val());
            });
            $('.vat_val').each(function() {
                totalvat += parseFloat($(this).val());
            });

            $('#subtotal').val( round(subtotal - totalvat, 2) );
            $('#total').val( round(subtotal, 2) );
            $('#vat').val( round(totalvat,2) );
            $('#grandtotal').val( round(subtotal - disc, 2) );
            // $('#grandtotal').val( toWordsconver(subtotal - disc) );
        }*/

        function round(value, decimals) {
            return Number(Math.round(value +'e'+ decimals) +'e-'+ decimals).toFixed(decimals);
        }

});

        $('.editItemAttr').click(function() {
            $('#itemid')      .val($(this).data('id'));
            $('.item_name')     .val($(this).data('i_item_name'));
            $('.quantity')     .val($(this).data('i_quantity'));
            $('.product_price')     .val($(this).data('i_product_price'));
            $('.vat_rate')     .val($(this).data('i_vat_rate'));
            $('.itmvalue')     .val($(this).data('i_itmvalue'));
            $('.i_vat_val')     .val($(this).data('i_vat_val'));
            $('.i_amount')     .val($(this).data('i_amount'));
            $('.idiscount')     .val($(this).data('i_idiscount'));
            $('.i_total_cost')     .val($(this).data('i_total_cost'));
            var ilocation       = $(this).data('i_location');
            $('.i_location option[value="'+ilocation+'"]').prop("selected", "selected");
        });

    </script>




<script type="text/javascript">
    $(document).ready(function() {
        $('form').parsley();
});
</script>

    </body>
</html>