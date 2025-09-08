<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    include("./includes/convertNumbersToWords.php");
    // include("./includes/custom_functions.php");
    
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
        if(mysqli_num_rows($query) == 1){
        include("./includes/avatar_select.php");
    }

        /*$getquery = mysqli_query($conDB, "SELECT * FROM `sm_request_sr` ORDER BY id DESC LIMIT 1 "); 
        while($rec = mysqli_fetch_assoc($getquery)){
            $inv_no_get = $rec["sr"];
        }*/
        /*if ($inv_no_get !== $_GET['id']) {
            $newinv = "SMT".number_pad(str_replace("SMT","",$empid."".$inv_no_get)+1,6);
            mysqli_query($conDB, "INSERT INTO `sm_request_sr` (`sr`) VALUES ('".$newinv."')");
            header( "refresh:0 ; url=new_request.php?id=$newinv" );
        }*/
    
if(isset($_POST['submit'])){

    // Get multiple input field's value 
    
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
            $sub_title_po = escape_string($_POST['sub_title']);
            $item_name_array = $_POST['item_name'];
            $quantity_array = $_POST['quantity'];
            $product_price_array = $_POST['product_price'];
            $itmvalue_array = $_POST['itmvalue'];
            $vat_rate_array = $_POST['vat_rate'];
            $vat_val_array = $_POST['vat_val'];
            $amount_array = $_POST['amount'];
            $total_cost_array = $_POST['total_cost'];
            $remarks_po = escape_string($_POST['remarks']);


        for ($i = 0; $i < count($item_name_array); $i++) {

            $item_name_po = escape_string($item_name_array[$i]);
            $location_po = escape_string($location_array[$i]);
            $quantity_po = escape_string($quantity_array[$i]);
            $product_price_po = escape_string($product_price_array[$i]);
            $itmvalue_po = escape_string($itmvalue_array[$i]);
            $vat_rate_po = escape_string($vat_rate_array[$i]);
            $vat_val_po = escape_string($vat_val_array[$i]);
            $amount_po = escape_string($amount_array[$i]);
            $total_cost_po = escape_string($total_cost_array[$i]);

            // mysqli_query($conDB, "INSERT INTO `customer_quotation` (`inv_no`,`tally_id`,`injazat_id`,`location`, `sub_type`, `sub_title`, `item_name`, `quantity`, `product_price`, `itmvalue`, `vat_rate`, `vat_val`, `amount`, `idiscount`, `total_cost`, `discount`, `department`, `prep_by`, `remarks`, `emp_id`) VALUES ('".$inv_no_po."','".$tally_id_po."','".$injazat_id_po."','".$location_po."','".$sub_type_po."','".$sub_title_po."','".$item_name_po."','".$quantity_po."','".$product_price_po."','".$itmvalue_po."','".$vat_rate_po."','".$vat_val_po."','".$amount_po."','".$idiscount_po."','".$total_cost_po."','".$discount_po."','".$user_dept."','".$userwel."','".$remarks_po."','".$empid."' )");
        }
            // mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name` ) VALUES ('".$empid."', '".$inv_no_po."', '".$userwel."' )");

        $getlastid = mysqli_query($conDB, "SELECT * FROM `sm_request_sr` ORDER BY id DESC LIMIT 1 ");
        while($rec = mysqli_fetch_assoc($getlastid)){
            $lstinvno = $rec["inv_no"];
        }
        $msg = '<div class="alert alert-success bg-success text-white border-0" role="alert">Add Seccssfully!</div>';
         header( "refresh:0 ; url=open_request.php?id=$_GET[id]" );
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
        <title><?= $site_title ?> - Create New Quotation</title>
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
                    <form action="new_request.php?id=<?= $_GET['id'] ?>" method="post" >

                        <div class="card-box">  
                                <?= $msg ?>
                                    <div class="row">
                                        <div class="col-6 ">
                                            <div class="mt-3 float-left">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Quotation Date:</div>
                                                    </div>
                                                    <input class="form-control" type='text' value="<?= date("d F Y")?>" readonly />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Sub-Title<span class="text-danger"> *</span></div>
                                                    </div>
                                                    <input class="form-control" type='text' name="sub_title" required="" />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Customer<span class="text-danger"> *</span></div>
                                                    </div>
                                                    <input type="text" name="customer" id="customer_ajax" placeholder="Enter Customer's name" class="form-control" required>
                                                    <a href="javascript:void(0);" class="btn btn-success btn-sm" onclick="addCustomerAtter()" >
                                                        <i class="mdi mdi-database-plus"></i>
                                                    </a>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Remarks</div>
                                                    </div>
                                                    <input class="form-control" type='text' name="remarks" />
                                                </div>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-6 ">
                                            <div class="mt-3 float-right">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Quotation No.:</div>
                                                    </div>
                                                    <input class="form-control" type='text' name='inv_no' value="<?= $_GET['id'] ?>" readonly />
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Tally ID.</div>
                                                    </div>
                                                    <input class="form-control" type='text' id='tally_id' name='tally_id' placeholder="Enter Tally ID." />
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
                                                    <tr class="text-center">
                                                        <th width="70">#</th>
                                                        <th>Description/Item Name</th>
                                                        <th width="160">UOM</th>
                                                        <th width="80">Quantity</th>
                                                        <th width="120">Unit Cost</th>
                                                        <th width="130">Item Value</th>
                                                        <th width="70">Vat%</th>
                                                        <th width="100">Vat Val</th>
                                                        <th width="130">Amount</th>
                                                        <th width="150" class="text-right">Total</th>
                                                        <th width="60" class="text-right"></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr class="set">
                                                        <td><input type="text" class="form-control rowid" readonly value="1" id="row"></td>
                                                        <td>
                                                            <input type="text" name="item_name[]" placeholder="Enter item name" class="form-control autocomplete" id="item_name" required autocomplete="off" >
                                                        </td>
                                                        <td>
                                                            <select class="select2 form-control" name="item_uom[]" required>
                                                                <option value="">Select</option>
                                                            <?php
                                                                $query_item_uom = mysqli_query($conDB, "SELECT * FROM `item_uom` WHERE `status`='1' ORDER BY `uom` REGEXP '^[^A-Za-z]' ASC, `uom`");
                                                                while($rec = mysqli_fetch_assoc($query_item_uom)){
                                                                    $uom = $rec["uom"];
                                                                    $en_uom_name = $rec["en_uom_name"];
                                                            ?>
                                                                <option value="<?=$uom?>"><?=$en_uom_name?></option>
                                                            <?php } ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input class="form-control quantity" type='text' id='quantity_1' name='quantity[]' for="1" required onkeypress="return isNumberKey(event,this)" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control product_price" type='text' data-type="product_price" id='product_price_1' name='product_price[]' for="1" required onkeypress="return isNumberKey(event,this)" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control itmvalue" type='text' data-type="itmvalue" id='itmvalue_1' name='itmvalue[]' for="1" readonly />
                                                        </td>
                                                        <td>
                                                            <input class="form-control vat_rate" type='text' data-type="vat_rate" id='vat_rate_1' name='vat_rate[]' for="1" required value="15" onkeypress="return isNumberKey(event,this)" />
                                                        </td>
                                                        <td>
                                                            <input class="form-control vat_val" type='text' data-type="vat_val" id='vat_val_1' name='vat_val[]' for="1" readonly />
                                                        </td>
                                                        <td>
                                                            <input class="form-control amount" type='text' data-type="amount" id='amount_1' name='amount[]' for="1" readonly />
                                                        </td>
                                                        <td class="text-right">
                                                            <input class="form-control total_cost" type='text' id='total_cost_1' name='total_cost[]' for='1' readonly />
                                                        </td>
                                                        <td class="text-right">
                                                            <a href="javascript:void(0);" class="btn btn-success btn-sm bbtn" id="add" title="Add field">
                                                                <i class="mdi mdi-database-plus"></i>
                                                            </a>
                                                        </td>

                                                    </tr>
                                                    </tbody>
                                                </table>
                                                </form>
                                                <!-- <input class="form-control" type='hidden' data-type="product_id_1" id='product_id_1' name='product_id[]'/> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <!-- <div class="col-6">
                                        </div> -->
                                        <div class="col-12 float-right" id="gtotal">
                                            <div class="float-right">
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Net-Total <small>(without VAT)</small></div>
                                                    </div>
                                                    <input class="form-control subtotal" type='text' id='subtotal' name='subtotal' readonly/>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">VAT 15%</div>
                                                    </div>
                                                    <input class="form-control vat" type='text' id='vat' name='vat' readonly/>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Total</div>
                                                    </div>
                                                    <input class="form-control total" type='text' id='total' name='total' readonly/>
                                                </div>
                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text">Grand Total</div>
                                                    </div>
                                                    <input class="form-control grandtotal" type='text' id='grandtotal' name='grandtotal' readonly/>
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

                                </form> <?php ?>
            </div>
        </div>
    </div> <!-- container -->

</div> <!-- content -->

<footer class="footer">
    <?= $site_footer ?>
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
        <script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>

        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>

        <script src="./plugins/switchery/switchery.min.js"></script>
        <script src="./plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.min.js"></script>
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-maxlength/bootstrap-maxlength.js" type="text/javascript"></script>        
        
        <!-- <script type="text/javascript" src="./plugins/autocomplete/jquery.mockjax.js"></script> -->
        <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>
        <!-- <script type="text/javascript" src="./plugins/autocomplete/countries.js"></script> -->
<!--        <script type="text/javascript" src="assets/pages/jquery.autocomplete.init.js"></script>-->
        <!-- App js -->
        <!-- <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script> -->
        
        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        <script src="assets/js/num-word.js"></script>

        <script type="text/javascript">
            
/*$('.autocomplete').on("click", function() {
'use strict';
    var searchTerm = $('.autocomplete').val();
    var data = $.ajax({
        url: './includes/ajaxFile/ajaxItemSearch.php', // Replace with the URL to your server-side script
        method: 'POST',
        dataType: 'JSON',
        context: document.body,
        global: false,
        async:false,
        data : {term: searchTerm},
        success: function(response) {
           return response;
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error: ' + status, error);
        }
    }).responseText;

    // console.log(data);
    var dataLoad = [""+data+","];

    var nhl_itms = $.map(dataLoad,function (team_itms) { return { value: team_itms}; });
    var teams_itms = nhl_itms.concat();
    $('.item_ajax').devbridgeAutocomplete({
        lookup: teams_itms,
        minChars: 1,
        onSelect: function (suggestion) {
            $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: 'Sorry, no matching results',
    });

});*/

$(function () {

    'use strict';
    <?php
        $sql_customer = "SELECT * FROM `customer` GROUP BY `full_name`";
        $query_customer = mysqli_query($conDB, $sql_customer);
    ?>
    var nhlTeams_cust = [
        <?php
            while($rec = mysqli_fetch_assoc($query_customer)){
                echo "\"".$rec["full_name"]."\",";
            }
        ?>
    ];
    // console.log(nhlTeams_cust);
    var nhl_cust = $.map(nhlTeams_cust, function (team_cust) { return { value: team_cust}; });
    var teams_cust = nhl_cust.concat();
    $('#customer_ajax').devbridgeAutocomplete({
        lookup: teams_cust,
        minChars: 1,
        onSelect: function (suggestion) {
            $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data);
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: 'Sorry, no matching results',
    });

    
    /*$('.item_ajax').keyup(function() {
        var searchTerm = $(this).val();
        if (searchTerm.length >= 2) {
            $.ajax({
                url: './includes/ajaxFile/ajaxItemSearch.php',
                type: 'POST',
                data: { term: searchTerm },
                dataType: 'JSON',
                success: function(data) {
                    var resultsList = $('#search-results');
                    resultsList.empty();
                    $.each(data, function(index, item) {
                        resultsList.append('<li>' + item + '</li>');
                    });
                }
            });
        } else {
            $('#search-results').empty();
        }
    });*/

    // <?php
    //     /*$items = "SELECT * FROM `menu_item` WHERE `status`='1' AND `price_level`='4' ";
    //     $query_item = mysqli_query($conDB, $items);*/
    // ?>
    // var nhlTeams_itm = [
    //     <?php
    //         /*while($rec = mysqli_fetch_assoc($query_item)){
    //             echo "\"".$rec["name_eng"]."\",";
    //         }*/
    //     ?>
    // ];
    // var nhl_itm = $.map(nhlTeams_itm, function (team_itm) { return { value: team_itm}; });
    // var teams_itm = nhl_itm.concat();
    // $('.item_ajax').devbridgeAutocomplete({
    //     lookup: teams_itm,
    //     minChars: 1,
    //     onSelect: function (suggestion) {
    //         $('.selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
    //     },
    //     showNoSuggestionNotice: true,
    //     noSuggestionNotice: 'Sorry, no matching results',
    // });

});

        </script>

        <script>
            /*$(function() {
                $('#customer_ajax').on('blur', function () {
                    console.log($(this).val());
                });
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


        var rowCount = 1;
            
        var data = $.ajax({
            url: './includes/ajaxFile/ajaxItemSearch.php',
            dataType: 'JSON',
            context: document.body,
            global: false,
            async:false,
            success: function(response) {
               return response;
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error: ' + status, error);
            }
        }).responseText;

        $('.autocomplete').devbridgeAutocomplete({
            lookup: JSON.parse(data),
            minChars: 1,
            showNoSuggestionNotice: true,
            noSuggestionNotice: 'Sorry, no matching results',
        });


        document.addEventListener('keydown', (e) => {
            if (e.key.toLowerCase() === '+' && e.ctrlKey && e.shiftKey) {
                e.preventDefault();
                // Add your code here
                rowCount++;
                var fields = `<tr id="row${rowCount}">
                <td><input type="text" class="form-control rowid" value="${rowCount}" readonly></td>
                <td><input type="text" name="item_name[]" placeholder="Enter item name" class="form-control autocomplete" id="item_name" required autocomplete="off"></td>
                <td><select class="form-control" name="item_uom[]" required>
                    <option value="">Select</option>
                <?php $query_item_uom = mysqli_query($conDB, "SELECT * FROM `item_uom` WHERE `status`='1' ORDER BY `uom` REGEXP '^[^A-Za-z]' ASC, `uom`");
                    while($rec = mysqli_fetch_assoc($query_item_uom)){
                        $uom = $rec["uom"];
                        $en_uom_name = $rec["en_uom_name"];
                ?>
                    <option value="<?= $uom ?>"><?=$en_uom_name?></option>
                <?php } ?>
                </select></td>
                <td><input class="form-control quantity" type="text" id="quantity_${rowCount}" name="quantity[]" for="${rowCount}" required onkeypress="return isNumberKey(event,this)" /></td>
                <td><input class="form-control product_price" type="text" data-type="product_price" id="product_price_${rowCount}" name="product_price[]" for="${rowCount}" required onkeypress="return isNumberKey(event,this)" /></td>
                <td><input class="form-control itmvalue" type="text" data-type="itmvalue" id="itmvalue_${rowCount}" name="itmvalue[]" for="${rowCount}" readonly /></td>
                <td><input class="form-control vat_rate" type="text" data-type="vat_rate" id="vat_rate_${rowCount}" name="vat_rate[]" for="${rowCount}" required value="15" onkeypress="return isNumberKey(event,this)" /></td>
                <td><input class="form-control vat_val" type="text" data-type="vat_val" id="vat_val_${rowCount}" name="vat_val[]" for="${rowCount}" readonly /></td>
                <td><input class="form-control amount" type="text" data-type="amount" id="amount_${rowCount}" name="amount[]" for="${rowCount}" readonly /></td>
                <td class="text-right"><input class="form-control total_cost" type="text" id="total_cost_${rowCount}" name="total_cost[]" for="${rowCount}" readonly /></td>
                <td class="text-right"><a href="javascript:void(0);" class="btn_remove btn btn-danger btn-sm bbtn" id="${rowCount}" title="Remove field"><i class="mdi mdi-database-minus"></a></td></tr>
                `;
                $('#orders').append(fields);
                $('.autocomplete').devbridgeAutocomplete({
                    lookup: JSON.parse(data),
                    minChars: 1,
                    showNoSuggestionNotice: true,
                    noSuggestionNotice: 'Sorry, no matching results',
                });
            } else if (e.key.toLowerCase() === 's' && e.ctrlKey && e.shiftKey) {
                // Add your code here
            }
        });
          
        $('#add').click(function() {
            rowCount++;
            var fields = `<tr id="row${rowCount}">
                <td><input type="text" class="form-control rowid" value="${rowCount}" readonly></td>
                <td><input type="text" name="item_name[]" placeholder="Enter item name" class="form-control autocomplete" id="item_name" required autocomplete="off"></td>
                <td><select class="form-control" name="item_uom[]" required>
                    <option value="">Select</option>
                <?php $query_item_uom = mysqli_query($conDB, "SELECT * FROM `item_uom` WHERE `status`='1' ORDER BY `uom` REGEXP '^[^A-Za-z]' ASC, `uom`");
                    while($rec = mysqli_fetch_assoc($query_item_uom)){
                        $uom = $rec["uom"];
                        $en_uom_name = $rec["en_uom_name"];
                ?>
                    <option value="<?= $uom ?>"><?=$en_uom_name?></option>
                <?php } ?>
                </select></td>
                <td><input class="form-control quantity" type="text" id="quantity_${rowCount}" name="quantity[]" for="${rowCount}" required onkeypress="return isNumberKey(event,this)" /></td>
                <td><input class="form-control product_price" type="text" data-type="product_price" id="product_price_${rowCount}" name="product_price[]" for="${rowCount}" required onkeypress="return isNumberKey(event,this)" /></td>
                <td><input class="form-control itmvalue" type="text" data-type="itmvalue" id="itmvalue_${rowCount}" name="itmvalue[]" for="${rowCount}" readonly /></td>
                <td><input class="form-control vat_rate" type="text" data-type="vat_rate" id="vat_rate_${rowCount}" name="vat_rate[]" for="${rowCount}" required value="15" onkeypress="return isNumberKey(event,this)" /></td>
                <td><input class="form-control vat_val" type="text" data-type="vat_val" id="vat_val_${rowCount}" name="vat_val[]" for="${rowCount}" readonly /></td>
                <td><input class="form-control amount" type="text" data-type="amount" id="amount_${rowCount}" name="amount[]" for="${rowCount}" readonly /></td>
                <td class="text-right"><input class="form-control total_cost" type="text" id="total_cost_${rowCount}" name="total_cost[]" for="${rowCount}" readonly /></td>
                <td class="text-right"><a href="javascript:void(0);" class="btn_remove btn btn-danger btn-sm bbtn" id="${rowCount}" title="Remove field"><i class="mdi mdi-database-minus"></a></td></tr>
                `;

                $('#orders').append(fields);
                $('.autocomplete').devbridgeAutocomplete({
                    lookup: JSON.parse(data),
                    minChars: 1,
                    showNoSuggestionNotice: true,
                    noSuggestionNotice: 'Sorry, no matching results',
                });
        });

        // Add a generic event listener for any change on quantity or price classed inputs
        $("#orders").on('input', 'input.quantity,input.product_price,input.vat_rate', function() {
          getTotalCost($(this).attr("for"));
        });

        $(document).on('click', '.btn_remove', function() {
          var button_id = $(this).attr('id');
          $('#row'+button_id+'').remove();
        });


        // Using a new index rather than your global variable i
        function getTotalCost(ind) {
            var qty = $('#quantity_'+ind).val();
            var price = $('#product_price_'+ind).val();
            var itmvalue = (qty * price);
            $('#itmvalue_'+ind).val( round(itmvalue,2) );
            var ivat = $('#vat_rate_'+ind).val();
            var totNumber = (qty * price);
            var vatValue = (totNumber * ivat / 100);
            var sub_tot = (vatValue + totNumber)
            $('#vat_val_'+ind).val( round(vatValue,2) );
            $('#amount_'+ind).val( round(sub_tot,2) );
            $('#total_cost_'+ind).val( sub_tot );
            calculateSubTotal();
        }

        $("#gtotal").on('input', function() {
          calculateSubTotal($(this).attr("for"));
        });
        function calculateSubTotal() {
            var subtotal = 0;
            var totalvat = 0;

            $('.total_cost').each(function() {
                subtotal += parseFloat($(this).val());
            });
            $('.vat_val').each(function() {
                totalvat += parseFloat($(this).val());
            });

            $('#subtotal').val( round(subtotal - totalvat, 2) );
            $('#total').val( round(subtotal, 2) );
            $('#vat').val( round(totalvat,2) );
            $('#grandtotal').val( round(subtotal, 2) );
            // $('#grandtotal').val( toWordsconver(subtotal - disc) );
        }

        function round(value, decimals) {
            return Number(Math.round(value +'e'+ decimals) +'e-'+ decimals).toFixed(decimals);
        }

});
</script>




<script type="text/javascript">
    $(document).ready(function() {
        $('form').parsley();
});
</script>


    </body>
</html>