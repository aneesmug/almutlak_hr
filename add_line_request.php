<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    include("./includes/convertNumbersToWords.php");
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
        if(mysqli_num_rows($query) == 1){
        include("./includes/avatar_select.php");
    }

    // $getquery = mysqli_query($conDB, "SELECT *, SUM(total_cost) as subtotal, SUM(vat_val) as vat_val FROM `smart_request` WHERE `inv_no`='".$_GET['id']."' AND `department`='".$user_dept."' AND `prep_by`='".$userwel."' ");
    if ($_SESSION['user_type'] == 'administrator' OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Finance") OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Management")) {
        $getquery = mysqli_query($conDB, "SELECT *, SUM(total_cost) as subtotal, SUM(vat_val) as vat_val FROM `smart_request` WHERE `inv_no`='".$_GET['id']."' ");
    } else {
        $getquery = mysqli_query($conDB, "SELECT *, SUM(total_cost) as subtotal, SUM(vat_val) as vat_val FROM `smart_request`  WHERE `inv_no`='".$_GET['id']."' AND `department`='".$user_dept."' ");
    }
        while($row = mysqli_fetch_assoc($getquery)){
            $idno = $row["id"];
            $inv_no_get = $row["inv_no"];
            $tally_id_get = $row["tally_id"];
            $injazat_id_get = $row["injazat_id"];
            $total_costget = $row['subtotal'];
            $vat_get = $row['vat_val'];
            $discount_get = $row["discount"];
            $location_get = $row["location"];
            $sub_type_get = $row["sub_type"];

            // $vat_get = $total_costget * 15 / 100;
            $total_cost_get = $total_costget - $vat_get;
            $total = $total_cost_get + $vat_get;
            $gtotal = $total - $discount_get;
        }

if(isset($_POST['submit'])){

    // Get multiple input field's value 
    
    if(
       !empty($_POST['item_name']) && !empty($_POST['quantity']) && !empty($_POST['product_price']) 
        // && is_array($_POST['inv_no']) && is_array($_POST['item_name']) && is_array($_POST['quantity']) && is_array($_POST['product_price']) 
        //&& count($inv_no) === count($item_name) === count($quantity) === count($product_price)
    ){
            $tally_id_po = $_POST['tally_id'];
            $injazat_id_po = $_POST['injazat_id'];
            $location_array = $_POST['location'];
            $sub_type_po = $_POST['sub_type'];
            $item_name_array = $_POST['item_name'];
            $quantity_array = $_POST['quantity'];
            $product_price_array = $_POST['product_price'];
            $itmvalue_array = $_POST['itmvalue'];
            $vat_rate_array = $_POST['vat_rate'];
            $vat_val_array = $_POST['vat_val'];
            $amount_array = $_POST['amount'];
            $idiscount_array = $_POST['idiscount'];
            $total_cost_array = $_POST['total_cost'];
            $discount_po = $_POST['discount'];


        for ($i = 0; $i < count($item_name_array); $i++) {

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

            mysqli_query($conDB, "INSERT INTO `smart_request` (`inv_no`,`tally_id`,`injazat_id`,`location`, `sub_type`, `item_name`, `quantity`, `product_price`, `itmvalue`, `vat_rate`, `vat_val`, `amount`, `idiscount`, `total_cost`, `discount`, `department`, `prep_by` ) VALUES ('".$inv_no_get."','".$tally_id_po."','".$injazat_id_po."','".$location_po."','".$sub_type_po."','".$item_name_po."','".$quantity_po."','".$product_price_po."','".$itmvalue_po."','".$vat_rate_po."','".$vat_val_po."','".$amount_po."','".$idiscount_po."','".$total_cost_po."','".$discount_po."','".$user_dept."','".$userwel."' )");

        } 


        /************log************/
        //mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['maker_name']."','".date("c")."')") or die (mysqli_error());
        /************log************/
        $getlastid = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `department`='".$user_dept."' AND `prep_by`='".$userwel."' ORDER BY id DESC LIMIT 1 ");
        while($rec = mysqli_fetch_assoc($getlastid)){
            $lstinvno = $rec["inv_no"];
        }
        $msg = '<div class="alert alert-success bg-success text-white border-0" role="alert">Add Seccssfully!</div>';
         header( "refresh:2 ; url=open_request.php?id=$lstinvno" );
// }

    } else {
        $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
    }   
}
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
        <style type="text/css">
            .noneDIV { 
                display:none; 
            } 
            .showDIV { 
                display:block; 
            }
            .swal-wide{ 
                width:850px !important;
            }
            .currencyicon{
                border: 1px solid #d9e3e9 !important;
                border-radius: 0 0.25rem 0.25rem 0 !important; 
                border-left: 0px !important;
            }
            .grandtotal, .discount, .total, .vat, .subtotal {
                border-right: 0px !important;
            }
            .input-group-text{
                border: 1px solid #d9e3e9 !important;
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
                    <form action="add_line_request.php?id=<?php echo $_GET['id'] ?>" method="post" >

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
                                                        <div class="input-group-text">Sub. Type</div>
                                                    </div>
                                                    <input class="form-control" type='text' name="sub_type" value="<?php echo $sub_type_get?>" readonly/>
                                                </div>
                                               
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-6 ">
                                            <div class="mt-3 float-right">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Invoice No.:</div>
                                                    </div>
                                                    <input class="form-control" type='text' name='inv_no' value="<?php echo $inv_no_get ?>" readonly />
                                                </div>                                               
                                                
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Tally ID.</div>
                                                    </div>
                                                    <input class="form-control" type='text' id='tally_id' name='tally_id' value="<?php echo $tally_id_get ?>" />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Injazat ID.</div>
                                                    </div>
                                                    <input class="form-control" type='text' id='injazat_id' name='injazat_id' placeholder="Enter Injazat No." />
                                                </div>
                                               
                                            </div>
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->


                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table mt-4" id="orders">
                                                    <thead>
                                                    <tr><th width="70">#</th>
                                                        <th>Description/Item Name/Invoice Num.</th>
                                                        <th width="150">Location</th>
                                                        <th width="80">Quantity</th>
                                                        <th width="120">Unit Cost <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                        <th width="130">Item Value <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                        <th width="105">Vat Opt.</th>
                                                        <th width="70">Vat%</th>
                                                        <th width="100">Vat Val <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                        <th width="130">Amount <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                        <th width="100">Discount <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                        <th width="150" class="text-right">Total <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                        <th width="60" class="text-right"></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                                </form>
                                                <!-- <input class="form-control" type='hidden' data-type="product_id_1" id='product_id_1' name='product_id[]'/> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            
                                            <!-- <div class="clearfix pt-5">
                                                <h6 class="text-muted">Notes:</h6>

                                                <small>
                                                    All accounts are to be paid within 7 days from receipt of
                                                    invoice. To be paid by cheque or credit card or direct payment
                                                    online. If account is not paid within 7 days the credits details
                                                    supplied as confirmation of work undertaken will be charged the
                                                    agreed quoted fee noted above.
                                                </small>
                                            </div> -->

                                        </div>
                                        <div class="col-6" id="gtotal">
                                            <div class="float-right">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Net-Total <small>(without VAT)</small></div>
                                                    </div>
                                                    <input class="form-control subtotal" type='text' id='subtotal' name='subtotal' readonly/>
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text currencyicon">
                                                            <i class="icon-saudi_riyal" style="font-size: 15px !important;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">VAT 15%</div>
                                                    </div>
                                                    <input class="form-control vat" type='text' id='vat' name='vat' readonly/>
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text currencyicon">
                                                            <i class="icon-saudi_riyal" style="font-size: 15px !important;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Total <small>(Before Disc.)</small></div>
                                                    </div>
                                                    <input class="form-control total" type='text' id='total' name='total' readonly/>
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text currencyicon">
                                                            <i class="icon-saudi_riyal" style="font-size: 15px !important;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Discount</div>
                                                    </div>
                                                    <input class="form-control discount" type='text' data-type="discount" id='discount' name='discount' value="0" onkeypress="return isNumberKey(event,this)" />
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text currencyicon">
                                                            <i class="icon-saudi_riyal" style="font-size: 15px !important;"></i>
                                                        </div>
                                                    </div>
                                                    <!-- <input class="form-control balance" type='text' id='balance' name='balance' readonly/> -->
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Grand Total</div>
                                                    </div>
                                                    <input class="form-control grandtotal" type='text' id='grandtotal' name='grandtotal' readonly/>
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text currencyicon">
                                                            <i class="icon-saudi_riyal" style="font-size: 15px !important;"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                            <div class="clearfix"></div>
                                        </div>
                                    </div>

                                    <div class="hidden-print mt-4 mb-4">
                                        <div class="text-right">
                                            <!-- <a href="javascript:window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print m-r-5"></i> Print</a> -->
                                            <button type="submit" name="submit" class="btn btn-info waves-effect waves-light">Save Lines</button>
                                        </div>
                                    </div>
                                </div>

                                </form> <?php ?>
            </div>
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
// ===== CONFIGURATION =====
    const DEFAULT_VAT_RATE = 15;
    const DEFAULT_DISCOUNT = 0;
    const DEFAULT_QTY = 1;
    const DEFAULT_PRICE = 0;
    let rowCount = 1;
    const locations = [
        <?php 
        $query = mysqli_query($conDB, "SELECT DISTINCT `section_name` FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name");
        while($rec = mysqli_fetch_assoc($query)) {
            $section = $rec["section_name"];
            echo '{ value: "'.addslashes($section).'", label: "'.str_replace(' ', '', addslashes($section)).'" },';
        }
        ?>
    ];
    // ===== UTILITY FUNCTIONS =====
    function generateLocationOptions() {
        return locations.reduce((options, loc) => 
            options + `<option value="${loc.value}">${loc.label}</option>`, 
            '<option value="">Select</option>'
        );
    }
    function formatCurrency(value) {
        return parseFloat(value).toFixed(2);
    }
    // ===== CORE CALCULATION FUNCTIONS =====
    function calculateRow(rowElement) {
        if (!rowElement.length) return;
        const qty = parseFloat(rowElement.find('.quantity').val()) || 0;
        const price = parseFloat(rowElement.find('.product_price').val()) || 0;
        const vatOption = rowElement.find('.vat_option').val();
        const vatRate = parseFloat(rowElement.find('.vat_rate').val()) || 0;
        const discount = parseFloat(rowElement.find('.idiscount').val()) || 0;
        let subtotal, vatValue, total;
        if (vatOption === 'exclude') {
            // Price is without VAT - we'll add VAT
            subtotal = qty * price;
            vatValue = subtotal * (vatRate / 100);
            total = subtotal + vatValue - discount;
        } else {
            // Price already includes VAT - we'll extract VAT
            subtotal = qty * price;
            vatValue = subtotal - (subtotal / (1 + (vatRate / 100)));
            total = subtotal - discount;
        }
        rowElement.find('.itmvalue').val(formatCurrency(subtotal));
        rowElement.find('.vat_val').val(formatCurrency(vatValue));
        rowElement.find('.amount').val(formatCurrency(subtotal + (vatOption === 'exclude' ? vatValue : 0)));
        rowElement.find('.total_cost').val(formatCurrency(total));
        calculateGrandTotal();
    }
    function calculateGrandTotal() {
        let subtotal = 0, totalVat = 0;
        const discount = parseFloat($('#discount').val()) || 0;
        $('tr[id^="row"]').each(function() {
            subtotal += parseFloat($(this).find('.total_cost').val()) || 0;
            totalVat += parseFloat($(this).find('.vat_val').val()) || 0;
        });
        $('#subtotal').val(formatCurrency(subtotal - totalVat));
        $('#total').val(formatCurrency(subtotal));
        $('#vat').val(formatCurrency(totalVat));
        $('#grandtotal').val(formatCurrency(subtotal - discount));
    }
    // ===== ROW MANAGEMENT =====
    function createRow(rowId, isFirstRow = false) {
        return `
            <tr id="row${rowId}">
                <td><input type="text" class="form-control rowid" value="${rowId}" readonly></td>
                <td><input type="text" name="item_name[]" class="form-control" required autocomplete="off"></td>
                <td><select class="form-control" name="location[]" required>${generateLocationOptions()}</select></td>
                <td><input class="form-control quantity" type="text" min="0" name="quantity[]" value="${DEFAULT_QTY}" required></td>
                <td><input class="form-control product_price" type="text" min="0" step="0.01" name="product_price[]" value="${DEFAULT_PRICE}" required></td>
                <td><input class="form-control itmvalue" type="text" name="itmvalue[]" readonly></td>
                <td>
                    <select class="form-control vat_option" name="vat_option[]">
                        <option value="include">Include 15%</option>
                        <option value="exclude" selected=selected>Exclude 15%</option>
                    </select>
                </td>
                <td><input class="form-control vat_rate" type="text" min="0" name="vat_rate[]" value="${DEFAULT_VAT_RATE}" readonly></td>
                <td><input class="form-control vat_val" type="text" name="vat_val[]" readonly></td>
                <td><input class="form-control amount" type="text" name="amount[]" readonly></td>
                <td><input class="form-control idiscount" type="text" min="0" name="idiscount[]" value="${DEFAULT_DISCOUNT}" required></td>
                <td><input class="form-control total_cost" type="text" name="total_cost[]" readonly></td>
                <td class="action-buttons">
                    ${isFirstRow ? 
                        `<button class="btn btn-success btn-sm bbtn btn_add" title="Add Row">
                            <i class="mdi mdi-plus"></i>
                        </button>` : 
                        `<button class="btn btn-danger btn-sm bbtn btn_remove" title="Remove Row">
                            <i class="mdi mdi-minus"></i>
                        </button>`
                    }
                </td>
            </tr>
        `;
    }
    function addRow() {
        rowCount++;
        $('#orders tbody').append(createRow(rowCount));
        $(`#row${rowCount} [name="item_name[]"]`).focus();
    }
    // ===== EVENT HANDLERS =====
    function initializeEventHandlers() {
        $(document)
            // Add row events
            .on('click', '#add, .btn_add', function() {
                addRow();
                $('html, body').animate({
                    scrollTop: $(`#row${rowCount}`).offset().top
                }, 200);
            })
            // Keyboard shortcut
            .on('keydown', function(e) {
                if (e.key === '+' && e.ctrlKey && e.shiftKey) {
                    e.preventDefault();
                    addRow();
                }
            })
            // Calculation triggers
            .on('input change', '.quantity, .product_price, .vat_option, .vat_rate, .idiscount', function() {
                calculateRow($(this).closest('tr'));
            })
            .on('input', '#discount', calculateGrandTotal)
            // Remove row
            .on('click', '.btn_remove', function() {
                const row = $(this).closest('tr');
                const rowId = parseInt(row.attr('id').replace('row', ''));
                if (row.find('[name="item_name[]"]').val() || 
                    row.find('.quantity').val() > DEFAULT_QTY || 
                    row.find('.product_price').val() > DEFAULT_PRICE) {
                    if (!confirm('Are you sure you want to remove this item?')) {
                        return;
                    }
                }           
                row.remove();
                rowCount--;
                calculateGrandTotal();
                
                $('tr[id^="row"]').each(function(index) {
                    const newId = index + 1;
                    $(this).attr('id', `row${newId}`);
                    $(this).find('.rowid').val(newId);
                });
            });
    }
    // ===== INITIALIZATION =====
    $(document).ready(function() {
        // Initialize first row with add button
        $('#orders tbody').html(createRow(1, true));
        calculateRow($('#row1'));
        // Set up event handlers
        initializeEventHandlers();
        // Focus first input field
        $('#row1 [name="item_name[]"]').focus();
    });        
</script>




<script type="text/javascript">
    $(document).ready(function() {
        $('form').parsley();
});

/*jslint  browser: true, white: true, plusplus: true */
/*global $, countries */
 
 /*
$(function () {
    'use strict';

// <?php
//  $sql_maker_name = "SELECT * FROM `cars` GROUP BY `maker_name`";
//  $query_maker_name = mysqli_query($conDB, $sql_maker_name);
// ?>
//  var nhlTeams_mn = [
// <?php
//  while($rec = mysqli_fetch_assoc($query_maker_name)){
//      $makername = $rec["maker_name"];
//      echo "'".$makername."',";
// }
// ?>
//  ];
//     var nhl_mn = $.map(nhlTeams_mn, function (team_mn) { return { value: team_mn}; });
//     var teams_mn = nhl_mn.concat();
//     // Initialize autocomplete with local lookup:

//     $('#maker_name').devbridgeAutocomplete({
//         lookup: teams_mn,
//         minChars: 1,
//         onSelect: function (suggestion) {
//             $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
//         },
//         showNoSuggestionNotice: true,
//         noSuggestionNotice: 'Sorry, no matching results',
// //        groupBy: 'category'
//     });
// <?php
//  $sql_model = "SELECT * FROM `cars` GROUP BY `model`";
//  $query_model = mysqli_query($conDB, $sql_model);
// ?>
//  var nhlTeams_md = [
// <?php
//  while($rec = mysqli_fetch_assoc($query_model)){
//      $modelcr = $rec["model"];
//      echo "'".$modelcr."',";
// }
// ?>
//  ];
//     var nhl_md = $.map(nhlTeams_md, function (team_md) { return { value: team_md}; });
//     var teams_md = nhl_md.concat();
//     // Initialize autocomplete with local lookup:
//     $('#model').devbridgeAutocomplete({
//         lookup: teams_md,
//         minChars: 1,
//         onSelect: function (suggestion) {
//             $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
//         },
//         showNoSuggestionNotice: true,
//         noSuggestionNotice: 'Sorry, no matching results',
// //        groupBy: 'category'
//     });
    
<?php
    /*$sql_made_year = "SELECT * FROM `machine_inv` GROUP BY `item`";
    $query_made_year = mysqli_query($conDB, $sql_made_year);*/
?>
    var nhlTeams_mdy = [
<?php
    /*while($rec = mysqli_fetch_assoc($query_made_year)){
        $madeyear = $rec["item"];
        echo "'".$madeyear."',";
    }*/
?>
    ];
    var nhl_mdy = $.map(nhlTeams_mdy, function (team_mdy) { return { value: team_mdy}; });
    var teams_mdy = nhl_mdy.concat();
    // Initialize autocomplete with local lookup:
    $('#item_name').devbridgeAutocomplete({
        lookup: teams_mdy,
        minChars: 1,
        onSelect: function (suggestion) {
            $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: 'Sorry, no matching results',
//        groupBy: 'category'
    });  
    

});
*/
</script>

    </body>
</html>