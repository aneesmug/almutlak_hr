<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->beginTransaction();
    
    // Check if columns exist before adding them
    $stmt = $pdo->query("DESCRIBE employee_assets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    $added = [];
    
    // Add return_date column if missing
    if (!in_array('return_date', $columnNames)) {
        $pdo->exec("ALTER TABLE employee_assets ADD COLUMN return_date DATE NULL AFTER assigned_date");
        $added[] = 'return_date';
    }
    
    // Add return_notes column if missing
    if (!in_array('return_notes', $columnNames)) {
        $pdo->exec("ALTER TABLE employee_assets ADD COLUMN return_notes TEXT NULL");
        $added[] = 'return_notes';
    }
    
    // Add signature_file column if missing
    if (!in_array('signature_file', $columnNames)) {
        $pdo->exec("ALTER TABLE employee_assets ADD COLUMN signature_file VARCHAR(255) NULL");
        $added[] = 'signature_file';
    }
    
    // Add proof_file column if missing
    if (!in_array('proof_file', $columnNames)) {
        $pdo->exec("ALTER TABLE employee_assets ADD COLUMN proof_file VARCHAR(255) NULL");
        $added[] = 'proof_file';
    }
    
    $pdo->commit();
    
    echo "<h2 style='color: green;'>Migration completed successfully!</h2>";
    if (!empty($added)) {
        echo "<p>Added columns: " . implode(", ", $added) . "</p>";
    } else {
        echo "<p>All columns already exist.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Migration failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
