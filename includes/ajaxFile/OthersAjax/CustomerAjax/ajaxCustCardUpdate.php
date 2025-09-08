<?php
    require_once __DIR__ . '/../../includes/db.php';
        
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
        mysqli_query($conDB, $u) or die (mysqli_error());
        $cust_u = "UPDATE `customer` SET `exp_date`='".$card_exp."', `sectin_nme`='".$sectin_nme_up."' WHERE `id`='".$_POST['id']."' ";
        mysqli_query($conDB, $cust_u) or die (mysqli_error());
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
?>