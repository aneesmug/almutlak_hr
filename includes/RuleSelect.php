<?php
require_once __DIR__ . '/db.php';

//collect the passed id
$id = $_GET['pid'];

//run a prepared statement 
$stmt = mysqli_query($conDB, "SELECT * FROM `eos_calc` WHERE `prid`='$id' ORDER BY `cid` REGEXP '^[^A-Za-z]' ASC, `cid` ");

//loop through all returned rows
while($row = mysqli_fetch_assoc($stmt)) {
    // echo "<option value='$row[cid]'>$row[details]</option>";
    echo "<option value='$row[cid]'>$row[details]</option>";
}