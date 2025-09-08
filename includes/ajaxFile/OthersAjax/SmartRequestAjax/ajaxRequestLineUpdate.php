<?php
	require_once __DIR__ . '/../../includes/db.php';

    $item_name_up = mysqli_real_escape_string($conDB, $_POST['item_name']);
    $location_up = $_POST['location'];
    $quantity_up = $_POST['quantity'];
    $product_price_up = $_POST['product_price'];
    $itmvalue_up = $_POST['itmvalue'];
    $vat_rate_up = $_POST['vat_rate'];
    $vat_val_up = $_POST['vat_val'];
    $amount_up = $_POST['amount'];
    $idiscount_up = $_POST['idiscount'];
    $total_cost_up = $_POST['total_cost'];

    $sql = "UPDATE `smart_request` SET `item_name`='$item_name_up', `location`='$location_up', `quantity`='$quantity_up', `product_price`='$product_price_up',`itmvalue`='$itmvalue_up', `vat_rate`='$vat_rate_up', `vat_val`='$vat_val_up', `amount`='$amount_up', `idiscount`='$idiscount_up', `total_cost`='$total_cost_up' WHERE `id`='".$_POST['itemid']."'";
    /*$data = [
        'title'   => $sql,
    ];
    echo json_encode($data);*/
    if(mysqli_query($conDB, $sql)){
    	$data = [
    		'title'   => "Updated!",
    		'message' => "This line has been update successfully.",
    		'type' 	  => 'success',
    	];
        echo json_encode($data);
    } else {
        $data = [
    		'title'   => "Error!",
    		'message' => "Record not updated because there are some error.",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }

?>