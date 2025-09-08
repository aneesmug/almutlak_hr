<?php
	require_once __DIR__ . '/../../includes/db.php';
        $query_mactrny = mysqli_query($conDB, "SELECT * FROM `cust_card_update` WHERE `cust_no`='".$_POST['id']."' ORDER BY `id` DESC LIMIT 1");
        while ($rec = mysqli_fetch_array($query_mactrny)) {
            $upd_status_lst = $rec["id"];
        }
    $card_exp_up = $_POST['card_exp_add'];
    $injazat_no_up = $_POST['injazat_no_add'];
    $acc_no_up = $_POST['acc_no_add'];
    $sectin_nme_up = $_POST['sectinName_add'];
    $injazat_no_up = str_replace(',', '', $injazat_no_up); 
    $query="INSERT INTO `cust_card_update` (`cust_no`,`injazat_no`, `exp_date`, `sectin_nme`) VALUES ('".$_POST['id']."', '".$injazat_no_up."', '".$card_exp_up."', '".$sectin_nme_up."')";
    if(mysqli_query($conDB, $query)){
        $upd_stat = "UPDATE `cust_card_update` SET `status`='I' WHERE `id`='".$upd_status_lst."' ";
        mysqli_query($conDB, $upd_stat) or die (mysqli_error());
        $upd_inj_cst = "UPDATE `customer` SET `injazat_no`='".$injazat_no_up."', `exp_date`='".$card_exp_up."', `acc_no`='".$acc_no_up."', `sectin_nme`='".$sectin_nme_up."' WHERE `id`='".$_POST['id']."' ";
        mysqli_query($conDB, $upd_inj_cst) or die (mysqli_error());
    	$data = [
    		'title'   => "Added!",
    		'message' => "This customer VIP card has been added successfully.",
    		'type' 	  => 'success',
    	];
        echo json_encode($data);
    } else {
        $data = [
    		'title'   => "Error!",
    		'message' => "User not updated because there are some error.",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }
?>