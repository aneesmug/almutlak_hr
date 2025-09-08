<?php
/*
MODIFICATION SUMMARY:
- Removed: The logic that automatically approved requests created by Managers has been removed. All new requests will now be saved with a 'draft' status.
- Removed: The "Finance Approver" and "Forward to GM?" dropdowns have been removed from this page. This logic is now handled in `open_request.php`.
- Simplified: The main INSERT query has been simplified to no longer handle different statuses at creation time, streamlining the process.
*/
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    include("./includes/convertNumbersToWords.php");
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
        if(mysqli_num_rows($query) == 1){
        include("./includes/avatar_select.php");
    }
if(isset($_POST['submit'])){

    if(
       !empty($_POST['item_name']) && !empty($_POST['quantity']) && !empty($_POST['product_price']) 
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
            $idiscount_array = $_POST['idiscount'];
            $total_cost_array = $_POST['total_cost'];
            $discount_po = $_POST['discount'];
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
            $idiscount_po = escape_string($idiscount_array[$i]);
            $total_cost_po = escape_string($total_cost_array[$i]);
            
            // Simplified SQL - All requests are now created as 'draft'
            $sql = "INSERT INTO `smart_request` 
                        (`inv_no`,`tally_id`,`injazat_id`,`location`, `sub_type`, `sub_title`, `item_name`, `quantity`, `product_price`, `itmvalue`, `vat_rate`, `vat_val`, `amount`, `idiscount`, `total_cost`, `discount`, `department`, `prep_by`, `remarks`, `emp_id`, `current_status`) 
                    VALUES 
                        ('".$inv_no_po."','".$tally_id_po."','".$injazat_id_po."','".$location_po."','".$sub_type_po."','".$sub_title_po."','".$item_name_po."','".$quantity_po."','".$product_price_po."','".$itmvalue_po."','".$vat_rate_po."','".$vat_val_po."','".$amount_po."','".$idiscount_po."','".$total_cost_po."','".$discount_po."','".$user_dept."','".$userwel."','".$remarks_po."','".$empid."', 'draft')";
            mysqli_query($conDB, $sql);
        }
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`) VALUES ('".$empid."', '".$inv_no_po."', '".$userwel."', 'draft' )");
        
        $msg = '<div class="alert alert-success bg-success text-white border-0" role="alert">'.__('added_successfully').'</div>';
         header( "refresh:0 ; url=open_request.php?id=$_GET[id]" );

    } else {
        $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">".__('fill_out_form_error')."</div>";
    }   
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('create_new_request')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="assets/images/favicon.ico">
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style type="text/css">
            .noneDIV { display:none; } 
            .showDIV { display:block; }
            .swal-wide{ width:850px !important; }
            .currencyicon{ border: 1px solid #d9e3e9 !important; border-radius: 0 0.25rem 0.25rem 0 !important; border-left: 0px !important; }
            .grandtotal, .discount, .total, .vat, .subtotal { border-right: 0px !important; }
            .input-group-text{ border: 1px solid #d9e3e9 !important; }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>
    <body class="enlarged" data-keep-enlarged="true">
        <div id="wrapper">
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span> <img src="assets/images/logo.png" alt="" height="22"> </span>
                            <i> <img src="assets/images/logo_sm.png" alt="" height="28"> </i>
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
                                <form action="new_request.php?id=<?= $_GET['id'] ?>" method="post" >
                                    <div class="card-box">  
                                        <?= $msg ?? '' ?>
                                        <div class="row">
                                            <div class="col-6 ">
                                                <div class="mt-3 float-left">
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('invoice_date')?>:</div></div>
                                                        <input class="form-control" type='text' value="<?= date("d F Y")?>" readonly />
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('sub_title_required')?><span class="text-danger ml-2">*</span></div></div>
                                                        <input class="form-control" type='text' name="sub_title" required="" />
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('sub_type_required')?><span class="text-danger ml-2">*</span></div></div>
                                                        <select class="form-control" name="sub_type" required>
                                                            <option value=""><?=__('select')?></option>
                                                            <?php
                                                                $query_sub_type = mysqli_query($conDB, "SELECT * FROM `smt_subject_type` ORDER BY `sub_type` REGEXP '^[^A-Za-z]' ASC, sub_type");
                                                                while($rec = mysqli_fetch_assoc($query_sub_type)){
                                                                    $sub_type = $rec["sub_type"];
                                                            ?>
                                                            <option value="<?= $sub_type ?>"><?= $sub_type ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('remarks')?></div></div>
                                                        <input class="form-control" type='text' name="remarks" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 ">
                                                <div class="mt-3 float-right">
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('invoice_no')?>:</div></div>
                                                        <input class="form-control" type='text' name='inv_no' value="<?= $_GET['id'] ?>" readonly />
                                                    </div>                                               
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="table-responsive">
                                                    <table class="table mt-4" id="orders">
                                                        <thead>
                                                            <tr><th width="70">#</th>
                                                                <th><?=__('description_item_name_invoice_num')?></th>
                                                                <th width="150"><?=__('location')?></th>
                                                                <th width="80"><?=__('quantity')?></th>
                                                                <th width="120"><?=__('unit_cost')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                <th width="130"><?=__('item_value')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                <th width="105"><?=__('vat_opt')?></th>
                                                                <th width="70"><?=__('vat_percent')?></th>
                                                                <th width="100"><?=__('vat_val')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                <th width="130"><?=__('amount')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                <th width="100"><?=__('discount')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                <th width="150" class="text-right"><?=__('total')?> <i class="icon-saudi_riyal" style="font-size: 13px !important;"></i></th>
                                                                <th width="60" class="text-right"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6"></div>
                                            <div class="col-6" id="gtotal">
                                                <div class="float-right">
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('net_total_without_vat')?></div></div>
                                                        <input class="form-control subtotal" type='text' id='subtotal' name='subtotal' readonly/>
                                                        <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('vat_15_percent')?></div></div>
                                                        <input class="form-control vat" type='text' id='vat' name='vat' readonly/>
                                                        <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('total_before_disc')?></div></div>
                                                        <input class="form-control total" type='text' id='total' name='total' readonly/>
                                                        <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('discount')?></div></div>
                                                        <input class="form-control discount" type='text' data-type="discount" id='discount' name='discount' value="0" onkeypress="return isNumberKey(event,this)" />
                                                        <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <div class="input-group-prepend"><div class="input-group-text"><?=__('grand_total')?></div></div>
                                                        <input class="form-control grandtotal" type='text' id='grandtotal' name='grandtotal' readonly/>
                                                        <div class="input-group-prepend"><div class="input-group-text currencyicon"><i class="icon-saudi_riyal" style="font-size: 15px !important;"></i></div></div>
                                                    </div>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>
                                        </div>
                                        <div class="hidden-print mt-4 mb-4">
                                            <div class="text-right"><button type="submit" name="submit" class="btn btn-info waves-effect waves-light"><?=__('save')?></button></div>
                                        </div>
                                    </div>
                                </form>
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
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
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
        <script src="assets/pages/jquery.form-pickers.init.js"></script>
        <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        <script src="assets/js/num-word.js"></script>
        <script type="text/javascript">
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
        </script>
<script type="text/javascript">
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
    function generateLocationOptions() {
        return locations.reduce((options, loc) => options + `<option value="${loc.value}">${loc.label}</option>`, `<option value="">${__('select')}</option>`);
    }
    function formatCurrency(value) { return parseFloat(value).toFixed(2); }
    function calculateRow(rowElement) {
        if (!rowElement.length) return;
        const qty = parseFloat(rowElement.find('.quantity').val()) || 0;
        const price = parseFloat(rowElement.find('.product_price').val()) || 0;
        const vatOption = rowElement.find('.vat_option').val();
        const discount = parseFloat(rowElement.find('.idiscount').val()) || 0;
        let vatRateInput = rowElement.find('.vat_rate');
        
        // Update VAT rate based on selection
        if (vatOption === 'no_vat') {
            vatRateInput.val(0);
        } else {
            vatRateInput.val(DEFAULT_VAT_RATE);
        }
        
        const vatRate = parseFloat(vatRateInput.val()) || 0;
        let subtotal, vatValue, total, amount;
        
        if (vatOption === 'exclude') {
            subtotal = qty * price;
            vatValue = subtotal * (vatRate / 100);
            amount = subtotal + vatValue;
            total = amount - discount;
        } else if (vatOption === 'include') {
            subtotal = qty * price; // This is price including VAT
            vatValue = subtotal - (subtotal / (1 + (vatRate / 100)));
            amount = subtotal;
            total = amount - discount;
        } else { // 'no_vat'
            subtotal = qty * price;
            vatValue = 0;
            amount = subtotal;
            total = amount - discount;
        }
        
        rowElement.find('.itmvalue').val(formatCurrency(subtotal));
        rowElement.find('.vat_val').val(formatCurrency(vatValue));
        rowElement.find('.amount').val(formatCurrency(amount));
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
    function createRow(rowId, isFirstRow = false) {
        return `
            <tr id="row${rowId}">
                <td><input type="text" class="form-control rowid" value="${rowId}" readonly></td>
                <td><input type="text" name="item_name[]" class="form-control" required autocomplete="off"></td>
                <td><select class="form-control" name="location[]" required>${generateLocationOptions()}</select></td>
                <td><input class="form-control quantity" type="text" min="0" name="quantity[]" value="${DEFAULT_QTY}" required></td>
                <td><input class="form-control product_price" type="text" min="0" step="0.01" name="product_price[]" value="${DEFAULT_PRICE}" required></td>
                <td><input class="form-control itmvalue" type="text" name="itmvalue[]" readonly></td>
                <td><select class="form-control vat_option" name="vat_option[]"><option value="include">${__('include')} ${DEFAULT_VAT_RATE}%</option><option value="exclude" selected=selected>${__('exclude')} ${DEFAULT_VAT_RATE}%</option><option value="no_vat">${__('no')} ${DEFAULT_VAT_RATE}%</option></select></td>
                <td><input class="form-control vat_rate" type="text" min="0" name="vat_rate[]" value="${DEFAULT_VAT_RATE}" readonly></td>
                <td><input class="form-control vat_val" type="text" name="vat_val[]" readonly></td>
                <td><input class="form-control amount" type="text" name="amount[]" readonly></td>
                <td><input class="form-control idiscount" type="text" min="0" name="idiscount[]" value="${DEFAULT_DISCOUNT}" required></td>
                <td><input class="form-control total_cost" type="text" name="total_cost[]" readonly></td>
                <td class="action-buttons">${isFirstRow ? `<button type="button" class="btn btn-success btn-sm bbtn btn_add" title="${__('add_row')}"><i class="mdi mdi-plus"></i></button>` : `<button type="button" class="btn btn-danger btn-sm bbtn btn_remove" title="${__('remove_row')}"><i class="mdi mdi-minus"></i></button>`}</td>
            </tr>`;
    }
    function addRow() {
        rowCount++;
        $('#orders tbody').append(createRow(rowCount));
        $(`#row${rowCount} [name="item_name[]"]`).focus();
    }
    function initializeEventHandlers() {
        $(document)
            .on('click', '.btn_add', addRow)
            .on('keydown', function(e) { if (e.key === '+' && e.ctrlKey && e.shiftKey) { e.preventDefault(); addRow(); } })
            .on('input change', '.quantity, .product_price, .vat_option, .vat_rate, .idiscount', function() { calculateRow($(this).closest('tr')); })
            .on('input', '#discount', calculateGrandTotal)
            .on('click', '.btn_remove', function() {
                const row = $(this).closest('tr');
                if (confirm(__('confirm_remove_item'))) {
                    row.remove();
                    calculateGrandTotal();
                    $('tr[id^="row"]').each(function(index) {
                        const newId = index + 1;
                        $(this).attr('id', `row${newId}`).find('.rowid').val(newId);
                    });
                    rowCount = $('tr[id^="row"]').length;
                }
            });
    }
    $(document).ready(function() {
        $('#orders tbody').html(createRow(1, true));
        calculateRow($('#row1'));
        initializeEventHandlers();
        $('#row1 [name="item_name[]"]').focus();
        $('form').parsley();
    });
</script>
    </body>
</html>
<?php
// Helper functions needed for this page
function getFinancePersonnel() {
    global $conDB;
    $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`dept`=2 AND e.`status`=1 AND e.emp_id IN ('4120', '3061') ORDER BY FIELD(e.emptype, 'Manager', 'Supporter')");
    $personnel = [];
    while ($row = mysqli_fetch_assoc($query)) { $personnel[] = $row; }
    return $personnel;
}
?>
