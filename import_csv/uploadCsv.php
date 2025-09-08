<?php
//  Code Source: https://gist.github.com/stavrossk/0f513ccbfe7882870ab1

/*$databasehost = "localhost";
$databasename = "YOUR_DATABASE_NAME";

$databasetable = "YOUR_DATABASE_TABLE";

$databaseusername = "YOUR_DATABASE_USERNAME";
$databasepassword = 'YOUR_DATABASE_PASSWORD';*/

$fieldSeparator = ",";
$fieldEscapedBy = "";
$fieldEnclosedBy = '"';
$lineSeparator = "\n";

// $csvfile = "../FILE_TO_IMPORT.csv";
$csvfile = $_FILES['file']['tmp_name'];


if (!file_exists($csvfile)) {
    error_log('File does NOT exist!');
    die("File not found. Make sure you specified the correct path.");
}
try {

    $pdo = new PDO(
        "mysql:host=localhost;dbname=mochachino_db",
        "mochachino_user",
        "hain6539306",
        array(
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION/*,
            PDO::MYSQL_ATTR_MAX_BUFFER_SIZE => 1024*1024*50,
            PDO::ATTR_TIMEOUT => 1440,
            PDO::ATTR_EMULATE_PREPARES => false*/
        )
    );
} catch (PDOException $e) {
    error_log('database connection failed!');
    die("database connection failed: " . $e->getMessage());
}

$affectedRows = $pdo->exec(
    "LOAD DATA LOCAL INFILE "
        . $pdo->quote($csvfile)
        . " INTO TABLE `attendance` FIELDS TERMINATED BY "
        . $pdo->quote($fieldSeparator)
        . " ESCAPED BY "
        . $pdo->quote($fieldEscapedBy)
        . " ENCLOSED BY "
        . $pdo->quote($fieldEnclosedBy)
        . " LINES TERMINATED BY "
        . $pdo->quote($lineSeparator)
        . " IGNORE 1 LINES (@column1,@column2,@column3,@column4,@column5,@column6,@column7,@column8,@column9,@column10,@column11) 
       SET `emp_id` = @column1, `emp_name` = @column2, `date` = str_to_date(@column4,'%Y-%m-%d'), `time` = @column5, `punch_time` = CONCAT_WS(' ',str_to_date(@column4,'%Y-%m-%d'),@column5), `punch_state` = @column6, `uptime` = @column11"
);
/*$deleteQuery = "
DELETE t1 FROM `attendance` t1
INNER JOIN `attendance` t2
WHERE t1.id < t2.id AND t1.date = t2.date AND t1.time = t2.time AND t1.punch_state = t2.punch_state AND t1.uptime = t2.uptime ";
$statement = $pdo->prepare($deleteQuery);*/
$deleteQuery = "
DELETE FROM `attendance`
WHERE `id` NOT IN(
	SELECT `id`
	FROM (
		SELECT MIN(`id`) as `id`
		FROM `attendance`
		GROUP BY CONCAT(`emp_id`, `emp_name`, `date`, `time`, `uptime`)
	) AS tbl
)
";
$statement = $pdo->prepare($deleteQuery);

if($statement->execute()){
	$data = [
        'title'   => "Added!",
        'message' => "Loaded a total of $affectedRows records from this csv file.\n",
        'type'    => 'success',
    ];
    echo json_encode($data);
} else {
    $data = [
		'title'   => "Error!",
		'message' => "Record not added because there are some error.",
		'type' 	  => 'error',
	];
    echo json_encode($data);
}


?>
