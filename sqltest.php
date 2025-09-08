<TITLE>SQLSRV_ MSSQL</TITLE>
<?php
/*
* Specify the server and connection string attributes.
*/
// error_reporting(E_ALL);
$serverName = "injazatftp.ddns.net,1433";
$connectionInfo = array('Database'=>'if_mochachino', "UID"=>"sa", "PWD"=>"@DmiN56539306");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if($conn) {
     echo "Connection established.<br />";
}else {
    echo  "Connection could not be established.<br />";
    die("<pre>".print_r(sqlsrv_errors(), true));
}

$sql = sqlsrv_query($conn, "SELECT * FROM inty_item_ms");
while ($row = sqlsrv_fetch_array($sql)) {
    print_r($row['itemdesc_en']."<br />");
} 

/*$servername = "injazatftp.ddns.net";
$port = "1433";
$database = "if_mochachino";
$username = "sa";
$password = "@DmiN56539306";
try {
    $conn = new PDO("sqlsrv:server=$servername,$port;Database=$database;ConnectionPooling=0", $username, $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        )
    );
    $sql = "SELECT * FROM inty_item_ms";

    foreach ($conn->query($sql) as $row) {
        print_r($row['itemdesc_en']."<br />");
    } 
} catch (PDOException $e) {
    echo ("Error connecting to SQL Server: " . $e->getMessage());
}*/
?>










