<?php
// api/get_benefit_types.php
header('Content-Type: application/json');
require_once("./../../includes/db.php"); // Adjust this path as needed

$pdo = getDbConnection();

$monthYear = $_GET['month'] ?? null; // Get the month from the GET request

try {
    // Always include gp.status with a LEFT JOIN.
    // If no matching record in generated_payrolls, gp.status will be NULL.
    $sql = "SELECT * FROM benefit_types";

    $stmt = $pdo->prepare($sql);

    // Bind the month_year parameter. If null, PDO handles it by matching NULL or no specific row.
    // However, for explicit behavior with LEFT JOIN, it's better to bind even if it's NULL,
    // or provide a default value to the query. For this case, a bound NULL works.
    $stmt->execute();
    $benefit_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'benefit_types' => $benefit_types]);

} catch (PDOException $e) {
    error_log('Error fetching benefit_types: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to load employee data. Database error.']);
} catch (Exception $e) {
    error_log('General error in get_benefit_types.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred.']);
}
?>
