<?php
    require_once __DIR__ . '/db.php';
    $id = $_POST['department'];
    $stmt = mysqli_query($conDB, "SELECT * FROM `section` WHERE `dept`='$id' AND `status`='1' ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, `section_name` ");
    while($row = mysqli_fetch_assoc($stmt)) {
            $sections[] = $row;
        }
        $data = [
            'data'   	=> $sections,
            'status'  	=> 200
        ];
        echo json_encode($data);