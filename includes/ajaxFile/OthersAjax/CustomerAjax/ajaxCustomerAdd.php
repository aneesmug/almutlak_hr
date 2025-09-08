<?php
	require_once __DIR__ . '/../../includes/db.php';
    
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

?>