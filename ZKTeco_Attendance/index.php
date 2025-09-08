<?php
    // include("./../../../mochasys2022/system/includes/db.php");
?>
<html>
<head>
    <title>ZK Test</title>
</head>

<body>
<?php
    $enableGetDeviceInfo = true;
    $enableGetUsers = true;
    $enableGetData = true;

    include('zklib/ZKLib.php');

    $zk = new ZKLib('192.168.2.101' /*your device IP */ );
    // $zk = new ZKLib('192.168.2.101' /*your device IP */ );
    $ret = $zk->connect();


    if ($ret) {
        $zk->disableDevice();
        $zk->setTime(date('Y-m-d H:i:s')); // Synchronize time
?>
        <?php if($enableGetDeviceInfo === true) { ?>
        <table border="1" cellpadding="5" cellspacing="2">
            <tr>
                <td><b>Status</b></td>
                <td>Connected</td>
                <td><b>Version</b></td>
                <td><?=($zk->version()); ?></td>
                <td><b>OS Version</b></td>
                <td><?=($zk->osVersion()); ?></td>
                <td><b>Platform</b></td>
                <td><?=($zk->platform()); ?></td>
            </tr>
            <tr>
                <td><b>Firmware Version</b></td>
                <td><?=($zk->fmVersion()); ?></td>
                <td><b>WorkCode</b></td>
                <td><?=($zk->workCode()); ?></td>
                <td><b>SSR</b></td>
                <td><?=($zk->ssr()); ?></td>
                <td><b>Pin Width</b></td>
                <td><?=($zk->pinWidth()); ?></td>
            </tr>
            <tr>
                <td><b>Face Function On</b></td>
                <td><?=($zk->faceFunctionOn()); ?></td>
                <td><b>Serial Number</b></td>
                <td><?=($zk->serialNumber()); ?></td>
                <td><b>Device Name</b></td>
                <td><?=($zk->deviceName()); ?></td>
                <td><b>Get Time</b></td>
                <td><?=($zk->getTime()); ?></td>
            </tr>
        </table>
        <?php } ?>
        <hr/>
        <?php if($enableGetUsers === true) { ?>
        <table border="1" cellpadding="5" cellspacing="2" style="float: left; margin-right: 10px;">
            <tr>
                <th colspan="6">Data User</th>
            </tr>
            <tr>
                <th>UID</th>
                <th>ID</th>
                <th>Name</th>
                <th>Card #</th>
                <th>Role</th>
                <th>Password</th>
            </tr>
            <?php
            try {
                // $zk->setUser(152, '152', 'Anees Afzal', '1234', ZK\Util::LEVEL_USER, '001213234');
                // $zk->setUser(5, '5', 'Admin', '1234', ZK\Util::LEVEL_ADMIN);
                $users = $zk->getUser();
                sleep(1);
                foreach ($users as $uItem) {
                    ?>
                    <tr>
                        <th><?=($uItem['uid']); ?></th>
                        <td><?=($uItem['userid']); ?></td>
                        <td><?=($uItem['name']); ?></td>
                        <td><?=($uItem['cardno']); ?></td>
                        <td><?=(ZK\Util::getUserRole($uItem['role'])); ?></td>
                        <td><?=($uItem['password']); ?>&nbsp;</td>
                    </tr>
                    <?php
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                header('HTTP', true, 500); // 500 internal server error
            }
            ?>
        </table>
        <?php } ?>
        <?php if ($enableGetData === true) { ?>
        <table border="1" cellpadding="5" cellspacing="2" style="float: left; margin-right: 10px;">
            <tr>
                <th colspan="9">Data Attendance</th>
            </tr>
            <tr>
                <th>SR</th>
                <th>UID</th>
                <th>ID</th>
                <th>Name</th>
                <th>State</th>
                <th>Date</th>
                <th>IN</th>
                <th>OUT</th>
                <th>Type</th>
            </tr>

        <?php

            $punches = $zk->getAttendance();
            $punches = array_reverse($punches, true);
            $users = $zk->getUser();
            $x = 1;
            $groupedPunches = [];
            // (date("Y-m-d", strtotime($punch['timestamp'])))
            // (date("H:i:s", strtotime($punch['timestamp'])))
            if (count($punches) > 0) {
                foreach ($punches as $punch) {
                    $punchDate = (date("Y-m-d", strtotime($punch['timestamp'])));
                    $employeeId = $punch['id'];
                    $uid        = $punch['uid'];
                    $state      = $punch['state'];
                    $type       = $punch['type'];
                    // Check if the date and employee ID combination already exists in the grouped punches
                    if (!isset($groupedPunches[$punchDate][$employeeId])) {
                        $groupedPunches[$punchDate][$employeeId] = [
                            'employee_id' => $employeeId,
                            'punch_date' => $punchDate,
                            'uid' => $uid,
                            'state' => $state,
                            'type' => $type,
                            'first_punch_time' => (date("H:i:s", strtotime($punch['timestamp']))),
                            'last_punch_time' => (date("H:i:s", strtotime($punch['timestamp'])))
                        ];
                    } else {
                        // Update first or last punch time if necessary
                        $groupedPunches[$punchDate][$employeeId]['first_punch_time'] = min($groupedPunches[$punchDate][$employeeId]['first_punch_time'], (date("H:i:s", strtotime($punch['timestamp']))));
                        $groupedPunches[$punchDate][$employeeId]['last_punch_time'] = max($groupedPunches[$punchDate][$employeeId]['last_punch_time'], (date("H:i:s", strtotime($punch['timestamp']))));
                    }
                }
                foreach ($groupedPunches as $date => $employeePunches) {
                    foreach ($employeePunches as $punch) {
                        //echo "Employee ID: {$punch['employee_id']}, Date: {$punch['punch_date']}, First Punch: {$punch['first_punch_time']}, Last Punch: {$punch['last_punch_time']}<br>";
                        if ($punch['employee_id'] == "152" && ( $punch['punch_date'] >= (isset($_GET['date'])?$_GET['date']:"")) ) {

                            $sqlqry = "INSERT INTO `zkdata`(`uid`, `emp_id`, `name`, `state`, `date`, `time_in`, `time_out`, `type`) VALUES ('".$punch['uid']."','".$punch['employee_id']."','".$users[$punch['employee_id']]['name']."','".(ZK\Util::getAttState($punch['state']))."','".$punch['punch_date']."','".$punch['first_punch_time']."','".$punch['last_punch_time']."','".(ZK\Util::getAttType($punch['type']))."')";
                            mysqli_query($conDB, $sqlqry);

                    ?>
                    <tr>
                        <th><?=$x++;?></th>
                        <td><?=($punch['uid']); ?></td>
                        <td><?=($punch['employee_id']); ?></td>
                        <td><?=(isset($users[$punch['employee_id']]) ? $users[$punch['employee_id']]['name'] : $punch['employee_id']); ?></td>
                        <td><?=(ZK\Util::getAttState($punch['state'])); ?></td>
                        <td><?=$punch['punch_date']; ?></td>
                        <td><?=$punch['first_punch_time']; ?></td>
                        <td><?=$punch['last_punch_time']; ?></td>
                        <td><?=(ZK\Util::getAttType($punch['type'])); ?></td>
                    </tr>
                    <?php
                        }
                    }
                }
            }
        ?>
        </table>
        <?php } ?>
            <?php
                if (count($punches) > 0) {
                    //$zk->clearAttendance(); // Remove attendance log only if not empty
                }
                // $zk->clearAdmin();
                // $zk->clearUsers();
                // $zk->removeUser(152);
            ?>
        <?php } ?>
        <?php
        // $zk->restartDevice();
        $zk->enableDevice();
        $zk->disconnect();
?>
</body>
</html>
