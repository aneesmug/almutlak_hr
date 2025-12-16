<?php
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/session_check.php';

$ajaxType = $_POST['ajaxType'];

if ($ajaxType == 'add_customer') {
    $injazat_no_up = $_POST['injazat_no'];
    $acc_no_up = strtoupper($_POST['acc_no']);
    $full_name_up = strtoupper($_POST['full_name']);
    $mobile_up = $_POST['mobile'];
    $card_exp_up = $_POST['card_exp'];
    $sectin_nme_up = $_POST['location'];
    $injazat_no_up = str_replace(',', '', $injazat_no_up);
    $newquery="INSERT INTO `customer` (`full_name`,`injazat_no`, `exp_date`,`sectin_nme`, `mobile`, `acc_no`, `created_at`) VALUES ('".$full_name_up."', '".$injazat_no_up."', '".$card_exp_up."', '".$sectin_nme_up."', '".$mobile_up."', '".$acc_no_up."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $newquery)){
        $lastentryquery = mysqli_query($conDB, "SELECT * FROM `customer` ORDER BY `id` DESC LIMIT 1");
            while($rec = mysqli_fetch_assoc($lastentryquery)){
                 $lastentryid = $rec['id'];
            }
            $id_cust = $lastentryid;
        $query="INSERT INTO `cust_card_update` (`cust_no`,`injazat_no`, `exp_date`, `sectin_nme`) VALUES ('".$id_cust."', '".$injazat_no_up."', '".$card_exp_up."', '".$sectin_nme_up."')";
        mysqli_query($conDB, $query);
        
        // Log customer creation
        ActivityLogger::logCreate('Customer', 'ajaxCustomer.php', $id_cust, [
            'full_name' => $full_name_up,
            'injazat_no' => $injazat_no_up,
            'acc_no' => $acc_no_up,
            'mobile' => $mobile_up,
            'exp_date' => $card_exp_up,
            'location' => $sectin_nme_up
        ], "Added customer: {$full_name_up}, Account: {$acc_no_up}", 'customer');

        $data = [
            'title'   => "Added!",
            'message' => "This customer has been added successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif($ajaxType == 'edit_customer'){
    $full_name_up = strtoupper($_POST['full_name']);
    $acc_no_up = strtoupper($_POST['acc_no']);
    $mobile_up = $_POST['mobile'];
    $sectin_nme_up = $_POST['location'];
    $customer_id = $_POST['id'];
    
    // Fetch old customer data for logging
    $old_result = mysqli_query($conDB, "SELECT * FROM customer WHERE id = '{$customer_id}'");
    $old_customer = mysqli_fetch_assoc($old_result);
    
    $sql = "UPDATE `customer` SET `full_name`='".$full_name_up."', `acc_no`='".$acc_no_up."', `mobile`='".$mobile_up."', `sectin_nme`='".$sectin_nme_up."' WHERE `id`='".$customer_id."' ";
    if(mysqli_query($conDB, $sql)){
        // Log customer update
        ActivityLogger::logUpdate('Customer', 'ajaxCustomer.php', $customer_id, $old_customer ?? [], [
            'full_name' => $full_name_up,
            'acc_no' => $acc_no_up,
            'mobile' => $mobile_up,
            'location' => $sectin_nme_up
        ], "Updated customer: {$full_name_up}", 'customer');
        
        $data = [
            'title'   => "Updated!",
            'message' => "This customer has been update successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "Record not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif($ajaxType == 'card_update') {
    $query_mactrny = mysqli_query($conDB, "SELECT * FROM `cust_card_update` WHERE `cust_no`='".$_POST['id']."' ORDER BY `id` DESC LIMIT 1");
        while ($rec = mysqli_fetch_array($query_mactrny)) {
            $upd_status_lst = $rec["id"];
        }
    $card_exp = $_POST['card_exp'];
    $injazat_no = $_POST['injazat_no'];
    $sectin_nme_up = $_POST['location'];
    $query="INSERT INTO `cust_card_update` (`cust_no`,`injazat_no`, `exp_date`, `sectin_nme`, `created_at`) VALUES ('".$_POST['id']."', '".$injazat_no."', '".$card_exp."', '".$sectin_nme_up."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $query)){
        $u = "UPDATE `cust_card_update` SET `status`='I' WHERE `id`='".$upd_status_lst."' ";
        mysqli_query($conDB, $u) or die ();
        $cust_u = "UPDATE `customer` SET `exp_date`='".$card_exp."', `sectin_nme`='".$sectin_nme_up."' WHERE `id`='".$_POST['id']."' ";
        mysqli_query($conDB, $cust_u) or die ();
        $data = [
            'title'   => "Updated!",
            'message' => "This customer VIP card has been updated successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif($ajaxType == 'add_card') {
    $query_mactrny = mysqli_query($conDB, "SELECT * FROM `cust_card_update` WHERE `cust_no`='".$_POST['id']."' ORDER BY `id` DESC LIMIT 1");
    while ($rec = mysqli_fetch_array($query_mactrny)) {
        $upd_status_lst = $rec["id"];
    }
    $card_exp_up = $_POST['card_exp'];
    $injazat_no_up = $_POST['injazat_no'];
    $acc_no_up = $_POST['acc_no'];
    $sectin_nme_up = $_POST['location'];
    $injazat_no_up = str_replace(',', '', $injazat_no_up); 
    $query="INSERT INTO `cust_card_update` (`cust_no`,`injazat_no`, `exp_date`, `sectin_nme`, `created_at`) VALUES ('".$_POST['id']."', '".$injazat_no_up."', '".$card_exp_up."', '".$sectin_nme_up."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $query)){
        $upd_stat = "UPDATE `cust_card_update` SET `status`='I' WHERE `id`='".$upd_status_lst."' ";
        mysqli_query($conDB, $upd_stat) or die ();
        $upd_inj_cst = "UPDATE `customer` SET `injazat_no`='".$injazat_no_up."', `exp_date`='".$card_exp_up."', `acc_no`='".$acc_no_up."', `sectin_nme`='".$sectin_nme_up."' WHERE `id`='".$_POST['id']."' ";
        mysqli_query($conDB, $upd_inj_cst) or die ();
        $data = [
            'title'   => "Added!",
            'message' => "This customer VIP card has been added successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
}
?>