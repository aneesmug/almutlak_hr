<?php
require_once(__DIR__ . "/includes/init.php");

try {
    $stmt = $pdo->query("SELECT section, step_number, title, file_path FROM guide_screenshots WHERE is_active = 1 ORDER BY section, step_number");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total screenshots: " . count($results) . "\n\n";
    
    foreach ($results as $row) {
        echo $row['section'] . " - Step " . $row['step_number'] . ": " . $row['title'] . "\n";
    }
} catch (Exception $e) {
    echo "No screenshots found or database error: " . $e->getMessage() . "\n";
}
?>
