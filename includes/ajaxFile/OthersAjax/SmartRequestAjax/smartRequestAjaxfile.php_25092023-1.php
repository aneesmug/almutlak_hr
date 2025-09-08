<?php
include './../../includes/db.php';
// Include your database connection code here

// Define the main table and the JOINed table
$mainTable = 'smart_request';
$joinedTable = 'smt_request_status';

// Set the table columns that DataTables will request
$columns = array(
    array('db' => 'id', 'dt' => 'id'),
    array('db' => 'inv_no', 'dt' => 'inv_no'),
    array('db' => 'sub_title', 'dt' => 'sub_title'),
    array('db' => 'sub_type', 'dt' => 'sub_type'),
    array('db' => 'department', 'dt' => 'department'),
    array('db' => 'prep_by', 'dt' => 'prep_by'),
    array('db' => 'created_at', 'dt' => 'created_at'),
    // Add more columns as needed
);

// Construct the SQL query
$sql = "SELECT ";
$sql .= implode(", ", array_map(function ($col) {
    return "`$col[db]` as `$col[dt]`";
}, $columns));
$sql .= " FROM `$mainTable`";
$sql .= " LEFT JOIN `$joinedTable` ON $mainTable.inv_no = $joinedTable.inv_no";

// Example: Apply WHERE clause for searching
if (!empty($_POST['search']['value'])) {
    $sql .= " WHERE ";
    $sql .= implode(" OR ", array_map(function ($col) {
        return "`$col[db]` LIKE '%" . $_POST['search']['value'] . "%'";
    }, $columns));
}

// Example: Apply ORDER BY clause for sorting
if (!empty($_POST['order'])) {
    $orderBy = $columns[$_POST['order'][0]['column']]['db'];
    $sql .= " ORDER BY $orderBy " . $_POST['order'][0]['dir'];
}

// Example: Apply LIMIT and OFFSET for pagination
$start = $_POST['start'];
$length = $_POST['length'];
$sql .= " LIMIT $start, $length";

// Execute the SQL query
$result = $conDB->query($sql);

// Fetch data into an array
$data = $result->fetch_all(MYSQLI_ASSOC);

// Construct and send the response as JSON
$response = array(
    "draw" => intval($_POST['draw']),
    "recordsTotal" => $totalRecords, // Total records in the table
    "recordsFiltered" => $filteredRecords, // Total records after filtering
    "data" => $data
);

echo json_encode($response);
?>
