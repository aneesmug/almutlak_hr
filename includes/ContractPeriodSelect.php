<?php
require_once __DIR__ . '/db.php';

//collect the passed id
$id = $_GET['vac_period'];

//run a prepared statement 
$stmt = mysqli_query($conDB, "SELECT * FROM `contract_period` WHERE `id`='$id' ");

//loop through all returned rows
while($row = mysqli_fetch_assoc($stmt)) {
    echo "".$row['vac_period']."";
}