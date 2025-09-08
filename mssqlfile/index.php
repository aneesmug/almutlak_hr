<?php
    $localhost = "localhost";
    $db_user = "mochachino_user";
    $db_pass = "hain6539306";
    $db_name = "mochachino_db";
    $conDB = mysqli_connect( $localhost , $db_user , $db_pass , $db_name ) or die('Error: Could not connect to database.');
	$localFilePath = './ZKtrans.json';
	$json_data = file_get_contents($localFilePath);
    $punches = json_decode($json_data, true);
    foreach ($punches as $punch) {
        $punchDate  = (date("Y-m-d", strtotime($punch['punch_time']['date'])));
        $employeeId = $punch['emp_code'];
        $uid        = $punch['id'];
        $state      = $punch['punch_state'];
        $type       = $punch['verify_type'];
        // Check if the date and employee ID combination already exists in the grouped punches
        if (!isset($groupedPunches[$punchDate][$employeeId])) {
            $groupedPunches[$punchDate][$employeeId] = [
                'employee_id' => $employeeId,
                'punch_date' => $punchDate,
                'uid' => $uid,
                'state' => $state,
                'type' => $type,
                'first_punch_time' => (date("H:i:s", strtotime($punch['punch_time']['date']))),
                'last_punch_time' => (date("H:i:s", strtotime($punch['punch_time']['date'])))
            ];
        } else {
            // Update first or last punch time if necessary
            $groupedPunches[$punchDate][$employeeId]['first_punch_time'] = min($groupedPunches[$punchDate][$employeeId]['first_punch_time'], (date("H:i:s", strtotime($punch['punch_time']['date']))));
            $groupedPunches[$punchDate][$employeeId]['last_punch_time'] = max($groupedPunches[$punchDate][$employeeId]['last_punch_time'], (date("H:i:s", strtotime($punch['punch_time']['date']))));
        }
    }
    foreach ($groupedPunches as $date => $employeePunches) {
        foreach ($employeePunches as $punch) {
            $sqlqry = "INSERT INTO `attendance`(`uid`, `emp_id`, `state`, `date`, `time_in`, `time_out`, `type`) VALUES ('".$punch['uid']."','".$punch['employee_id']."','".($punch['state'])."','".$punch['punch_date']."','".$punch['first_punch_time']."','".$punch['last_punch_time']."','".($punch['type'])."')";
            mysqli_query($conDB, $sqlqry);
        }
    }
?>