<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

	$getquery = mysqli_query($conDB, "SELECT * FROM `machines` WHERE `id`='".$_GET['id']."' ");

	
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_car = $rec["id"];
            $macid = $rec["m_id"];
			$name_mach = $rec["name_mach"];
			$maker_name = $rec["maker_name"];
			$location = $rec["location"];
			$remarks = $rec["remarks"];
			$status = $rec["status"];
			$serial = $rec["serial"];
			$datereg = $rec["date_reg"];

			$timestamp_reg = strtotime("$datereg");
			$date_reg = date('d, M Y', $timestamp_reg);

			$query_mactrny = mysqli_query($conDB, "SELECT * FROM `machine_trans` WHERE `mid`='".$_GET['id']."' ORDER BY `id` DESC LIMIT 1");
            while ($rec = mysqli_fetch_array($query_mactrny)) {
                $new_location_trn = $rec["location"];
                $old_location_trn = $rec["old_location"];
            }

           $old_location = ($new_location_trn == "") ? $location : $new_location_trn;


           $timestamp_reg = strtotime("$datereg");
			$date_reg = date('d, M Y', $timestamp_reg);

			$query_macinvo = mysqli_query($conDB, "SELECT * FROM `machine_inv` WHERE `mid`='".$_GET['id']."' AND `inv_no`='".$_GET['invo']."'");
            while ($rec = mysqli_fetch_array($query_macinvo)) {
                $new_location_invo = $rec["location"];
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
				<div class="col-md-12">
					
						<div class="card-box">	
								<?php echo $msg ?>
                                    <div class="clearfix">
                                        <div class="float-left mb-3">
                                            <img src="assets/images/logo.png" alt="" height="50">
                                        </div>
                                        <div class="float-right">
                                            <h4 class="m-0 d-print-none">Items adding of "<?php echo $name_mach ?>"</h4>
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-6 ">
                                            <div class="mt-3 float-left">
                                                <p class="m-b-10"><strong>Invoice Date: </strong><?php echo date("d F Y") ?></p>
                                                <p class="m-b-10"><strong>Invoice No.: </strong><?php echo $_GET['invo'] ?></p>
                                                <p class="m-b-10"><strong>Machine ID: </strong>	<?php echo $macid ?> (<?php echo $name_mach ?>) </p>
                                                <p class="m-b-10"><strong>Invoice Location: </strong><?php echo $new_location_invo ?></p>
                                                <p class="m-b-10"><strong>Current Location: </strong> <span class="badge badge-success"><?php echo $old_location ?></span></p>
                                            </div>
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->


                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table mt-4" id="orders">
                                                    <thead>
                                                    <tr>
                                                    	<th width="60">#</th>
                                                        <th>Item</th>
                                                        <th width="120">Quantity</th>
                                                        <th width="150">Unit Cost</th>
                                                        <th width="150" class="text-right">Total</th>
                                                    </tr>
                                                	</thead>
                                                    <tbody>
                                                    <?php 
                                                    $x=1;
							$query_macinv = mysqli_query($conDB, "SELECT * FROM `machine_inv` WHERE `mid`='".$_GET['id']."' AND `inv_no`='".$_GET['invo']."' ");
											            while ($rec = mysqli_fetch_array($query_macinv)) {
											                $item = $rec["item"];
											                $qty = $rec["qty"];
											                $price = $rec["price"];
											                $subtotal = $price * $qty;
											                $gtotal += $subtotal;
											                $vatval = $gtotal / 100 * 15;

                                                    ?>
                                                    <tr>
                                                        <td><?php echo $x++; ?></td>
                                                        <td><b><?php echo $item; ?></b></td>
                                                        <td><?php echo $qty; ?></td>
                                                        <td><?php echo $price; ?></td>
                                                        <td class="text-right"><?php echo $subtotal ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                    </tbody>
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
                                        <div class="col-6">
                                            <div class="float-right">
                                           		<p><b>Sub-total:</b> <?php echo $gtotal ?></p>
                                                <p><b>VAT (%15):</b> <?php echo $vatval; ?></p>
                                                <h3><?php echo $gtotal + $vatval; ?> SAR</h3>
                                            <div class="clearfix"></div>
                                        </div>
                                    </div>

                                    <div class="hidden-print mt-4 mb-4">
                                        <div class="btn-group text-right" role="group" aria-label="Invoice">
										<a href="view_machine.php?id=<?php echo $_GET['id'] ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
										<a href="javascript:window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print m-r-5"></i> Print</a>
										</div>
                                    </div>
                                </div>
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
<!--		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>-->
		<script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
<!--		<script src="https://cdn.jsdelivr.net/gh/RobinHerbots/jquery.inputmask@5.0.0-beta.87/dist/jquery.inputmask.min.js" type="text/javascript"></script>-->
		

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
        <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script>
		
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

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
		</script>

		<script type="text/javascript">
		$(document).ready(function() {
			
		var rowCount = 1;
		  
		  $('#add').click(function() {
		    rowCount++;
		    $('#orders').append('<tr id="row'+rowCount+'"><td><input type="text" class="form-control" value="'+rowCount+'" readonly></td><td><input type="text" name="item_name[]" placeholder="Enter item name" class="form-control" id="item_name" required autocomplete="off"></td><td><input class="form-control quantity" type="number" id="quantity_'+rowCount+'" name="quantity[]" for="'+rowCount+'" required /></td><td><input class="form-control product_price" type="number" data-type="product_price" id="product_price_'+rowCount+'" name="product_price[]" for="'+rowCount+'" required /></td><td class="text-right"><input class="form-control total_cost" type="text" id="total_cost_'+rowCount+'" name="total_cost[]" for="'+rowCount+'" readonly /></td><td class="text-right"><a href="javascript:void(0);" class="btn_remove btn btn-danger btn-sm" id="'+rowCount+'"><i class="mdi mdi-database-minus"></a></td></tr>');
		});

		// Add a generic event listener for any change on quantity or price classed inputs
		$("#orders").on('input', 'input.quantity,input.product_price,input.discount', function() {
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
		  var totNumber = (qty * price);
		  var tot = totNumber.toFixed(2);
		  $('#total_cost_'+ind).val(tot);
		  calculateSubTotal();
		}

		function calculateSubTotal() {
			var subtotal = 0;
			var vat = 0;
			var bal = 0;
			$('.total_cost').each(function() {

			subtotal += parseFloat($(this).val());				

			// bal = $('input[name="discount"]').val();
			// bal = bal - subtotal;

			bal = $('input[name="discount"]').val();
	        bal = subtotal - bal ;
	        // $("#balance").val(bal.toFixed(2));
	        vat = bal / 100 * 15;
	        gtotal = vat + bal;

			});

		  // $("#balance").val(bal);
		  $('#subtotal').val(subtotal);
		  $('#vat').val(vat);
		  $('#gtotal').val(vat+' + '+bal+' = '+gtotal);
		  // $('#gtotal').val(gtotal);

		}

});

	</script>

<script type="text/javascript">
	$(document).ready(function() {
		$('form').parsley();
});

/*jslint  browser: true, white: true, plusplus: true */
/*global $, countries */

$(function () {
    'use strict';

// <?php
// 	$sql_maker_name = "SELECT * FROM `cars` GROUP BY `maker_name`";
// 	$query_maker_name = mysqli_query($conDB, $sql_maker_name);
// ?>
// 	var nhlTeams_mn = [
// <?php
// 	while($rec = mysqli_fetch_assoc($query_maker_name)){
// 		$makername = $rec["maker_name"];
// 		echo "'".$makername."',";
// }
// ?>
// 	];
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
// 	$sql_model = "SELECT * FROM `cars` GROUP BY `model`";
// 	$query_model = mysqli_query($conDB, $sql_model);
// ?>
// 	var nhlTeams_md = [
// <?php
// 	while($rec = mysqli_fetch_assoc($query_model)){
// 		$modelcr = $rec["model"];
// 		echo "'".$modelcr."',";
// }
// ?>
// 	];
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
	$sql_made_year = "SELECT * FROM `machine_inv` GROUP BY `item`";
	$query_made_year = mysqli_query($conDB, $sql_made_year);
?>
	var nhlTeams_mdy = [
<?php
	while($rec = mysqli_fetch_assoc($query_made_year)){
		$madeyear = $rec["item"];
		echo "'".$madeyear."',";
}
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

</script>

    </body>
</html>