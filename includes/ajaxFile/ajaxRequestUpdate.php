<?php
    require_once __DIR__ . '/../../includes/db.php';
    $sub_type_up = $_POST['sub_type'];
    $sub_title_up = mysqli_real_escape_string($conDB, $_POST['sub_title']);
    $tally_id_up = $_POST['tally_id'];
    $injazat_id_up = $_POST['injazat_id'];
    $remarks_up = mysqli_real_escape_string($conDB, $_POST['remarks']);
    $sql = "UPDATE `smart_request` SET `sub_type`='$sub_type_up', `sub_title`='$sub_title_up', `tally_id`='$tally_id_up', `injazat_id`='$injazat_id_up',`remarks`='$remarks_up' WHERE `inv_no`='".$_POST['reqid']."' ";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Updated!",
            'message' => "This request has been update successfully.",
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
?>