<?php
if(!empty($_FILES['file']['name'])){
$connect = new PDO("mysql:host=localhost;dbname=mochachino_db;", "mochachino_user", "hain6539306", array(
       PDO::MYSQL_ATTR_LOCAL_INFILE => true,
));

$total_row = count(file($_FILES['file']['tmp_name']));
$file_location = str_replace("\\", "/", $_FILES['file']['tmp_name']);
$query_1 = '
       LOAD DATA LOCAL INFILE "'.$file_location.'" IGNORE 
       INTO TABLE attendance
       FIELDS 
              TERMINATED BY "," 
       LINES 
              TERMINATED BY "\r\n"
       IGNORE 1 LINES 
       (@column1,@column2,@column3,@column4,@column5,@column6,@column7,@column8,@column9,@column10,@column11) 
       SET emp_id = @column1, emp_name = @column2, date = str_to_date(@column4,"%Y-%m-%d"), time = @column5, punch_state = @column6, uptime = @column11
';
$statement = $connect->prepare($query_1);
$statement->execute();



$deleteQuery = "
DELETE t1 FROM `attendance` t1
INNER JOIN `attendance` t2
WHERE t1.id < t2.id AND t1.date = t2.date AND t1.time = t2.time AND t1.punch_state = t2.punch_state AND t1.uptime = t2.uptime ";
$statement = $connect->prepare($deleteQuery);
$statement->execute();

/*
 $query_2 = "
 SELECT MAX(customer_id) as customer_id FROM attendance
 ";

 $statement = $connect->prepare($query_2);

 $statement->execute();

 $result = $statement->fetchAll();

 $customer_id = 0;

 foreach($result as $row)
 {
  $customer_id = $row['customer_id'];
 }

 $first_customer_id = $customer_id - $total_row;

 $first_customer_id = $first_customer_id + 1;

 $query_3 = 'SET @customer_id:='.$first_customer_id.'';

 $statement = $connect->prepare($query_3);

 $statement->execute();

 $query_4 = '
 LOAD DATA LOCAL INFILE "'.$file_location.'" IGNORE 
 INTO TABLE order_table 
 FIELDS TERMINATED BY "," 
 LINES TERMINATED BY "\r\n" 
 IGNORE 1 LINES 
 (@column1,@column2,@column3,@column4,@column5,@column6,@column7) 
 SET customer_id = @customer_id:=@customer_id+1, product_name = @column5,  product_price = @column6, order_date = @column7
 ';

 $statement = $connect->prepare($query_4);

 $statement->execute();
*/
/* $output = array(
  'success' => 'Total <b>'.$total_row.'</b> Data imported'
 );
 echo json_encode($output);*/

echo "
<script type=\"text/javascript\">
       alert(\"Loaded a total of $total_row records from this csv file.\");
       window.location = \"index.php\"
</script>";

}
?>