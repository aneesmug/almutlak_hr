<?php
require_once __DIR__ . '/includes/db.php';

$sql = file_get_contents(__DIR__ . '/db_updates/add_loan_payment_proof_columns.sql');

// Execute multi-query
if ($conDB->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conDB->store_result()) {
            while ($row = $result->fetch_assoc()) {
                echo $row['Status'] . "\n";
            }
            $result->free();
        }
    } while ($conDB->next_result());
    echo "Database updated successfully!\n";
} else {
    echo "Error updating database: " . $conDB->error . "\n";
}

$conDB->close();
?>
