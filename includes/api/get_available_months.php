<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL query has been updated to select from the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 ****************************************************************/
// api/get_available_payroll_months.php
header('Content-Type: application/json');

require_once("./../../includes/db.php");


$pdo = getDbConnection();

try {
    // Select distinct month_year values from the payrolls table
    $stmt = $pdo->query("SELECT DISTINCT month_year FROM payrolls ORDER BY month_year DESC");
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedMonths = [];
    foreach ($months as $month) {
        $date = new DateTime($month['month_year'] . '-01'); // Append '-01' to make it a valid date string
        $formattedMonths[] = [
            'value' => $month['month_year'], // e.g., "2023-01"
            'label' => $date->format('F Y')   // e.g., "January 2023"
        ];
    }

    echo json_encode(['status' => 'success', 'months' => $formattedMonths]);

} catch (PDOException $e) {
    error_log('Error fetching available payroll months: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: Could not retrieve available months.']);
} catch (Exception $e) {
    error_log('General error in get_available_payroll_months.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred.']);
}
?>
