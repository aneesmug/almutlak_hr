<?php
include("./../includes/db.php");

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Payroll ID is required']);
    exit;
}

$payroll_id = (int)$_GET['id'];

$query = "SELECT p.*, e.name, e.bank_name, e.iban, d.dep_nme AS dept, ac.job 
          FROM payroll p
          JOIN employees e ON p.emp_id = e.emp_id
          LEFT JOIN department d ON e.dept = d.id
          LEFT JOIN ac_jobs ac ON e.actual_job = ac.id 
          WHERE p.id = ?";
          
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $payroll_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $data['status'] = $data['status'] ?? 'pending';
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Payroll record not found']);
}

$conDB->close();
?>