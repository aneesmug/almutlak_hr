<?php

ob_start();
$localhost = "localhost";
$db_user = "mochachino_user";
$db_pass = "hain6539306";
$db_name = "mochachino_db";

$conDB = mysqli_connect( $localhost , $db_user , $db_pass , $db_name ) or die('Error: Could not connect to database.');

if(!empty($_FILES['file']['name'])){
$total_row = count(file($_FILES['file']['tmp_name']));
$file_location = str_replace("\\", "/", $_FILES['file']['tmp_name']);

	$result1=mysqli_query($conDB,"select count(*) count from attendance");
	$r1=mysqli_fetch_array($result1);
	$count1=(int)$r1['count'];
	//If the fields in CSV are not seperated by comma(,)  replace comma(,) in the below query with that  delimiting character 
	//If each tuple in CSV are not seperated by new line.  replace \n in the below query  the delimiting character which seperates two tuples in csv
	// for more information about the query http://dev.mysql.com/doc/refman/5.1/en/load-data.html
	mysqli_query($conDB, '
	    LOAD DATA LOCAL INFILE "'.$file_location.'" IGNORE 
		INTO TABLE attendance
		FIELDS TERMINATED BY \',\'
        LINES TERMINATED BY \'\n\'
		IGNORE 1 LINES 
		(@column1,@column2,@column3,@column4,@column5,@column6) 
		SET emp_id = @column1, emp_name = @column2, date = str_to_date(@column4,"%Y-%m-%d"), time = @column5, punch_state = @column6
	')or die(mysql_error());

	$result2=mysqli_query($conDB,"select count(*) count from attendance");
	$r2=mysqli_fetch_array($result2);
	$count2=(int)$r2['count'];

	$count=$count2-$count1;
	if($count>0)
	echo "Success";
	echo "<b> total $count records have been added to the table attendance </b> ";
} else {
	echo "ERROR!";
}
?>