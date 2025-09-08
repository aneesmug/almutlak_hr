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

    // $zk = new ZKLib('192.168.2.102' /*your device IP */ );
    $zk = new ZKLib('192.168.2.101' /*your device IP */ );
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

        <?php

        ?>

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
                        <td><?=($uItem['uid']); ?></td>
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
            <table border="1" cellpadding="5" cellspacing="2">
                <tr>
                    <th colspan="8">Data Attendance</th>
                </tr>
                <tr>
                    <th>SR</th>
                    <th>UID</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>State</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Type</th>
                </tr>
                <?php
                    $x = 1;
                    $attendance = $zk->getAttendance();
                    if (count($attendance) > 0) {
                        $attendance = array_reverse($attendance, true);
                            // code...
                        sleep(1);
                        foreach ($attendance as $attItem) {
                            if ($attItem['id'] == "152" && ( date("Y-m-d", strtotime($attItem['timestamp'])) >= '2024-01-01' ) ) {
                            ?>
                                <tr>
                                    <td><?=$x++;?></td>
                                    <td><?=($attItem['uid']); ?></td>
                                    <td><?=($attItem['id']); ?></td>
                                    <td><?=(isset($users[$attItem['id']]) ? $users[$attItem['id']]['name'] : $attItem['id']); ?></td>
                                    <td><?=(ZK\Util::getAttState($attItem['state'])); ?></td>
                                    <td><?=(date("Y-m-d", strtotime($attItem['timestamp']))); ?></td>
                                    <td><?=(date("H:i:s", strtotime($attItem['timestamp']))); ?></td>
                                    <td><?=(ZK\Util::getAttType($attItem['type'])); ?></td>
                                </tr>
                                <?php
                            }
                        }
                    }
                ?>
            </table>
            <?php
                if (count($attendance) > 0) {
                    //$zk->clearAttendance(); // Remove attendance log only if not empty
                }
                // $zk->clearAdmin();
                // $zk->clearUsers();
                // $zk->removeUser(152);
            ?>
        <?php } ?>
        <?php
        $zk->enableDevice();
        $zk->disconnect();
    }
?>
</body>
</html>
