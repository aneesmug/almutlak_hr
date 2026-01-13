<?php
require_once __DIR__ . '/includes/db.php';

try {
    $stmt = $pdo->query("DESCRIBE employee_assets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Employee Assets Table Columns:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<h3>Looking for these columns:</h3>";
    $requiredColumns = ['return_date', 'return_notes', 'signature_file', 'proof_file'];
    $foundColumns = [];
    
    foreach ($columns as $col) {
        $foundColumns[] = $col['Field'];
    }
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $foundColumns)) {
            echo "✓ $col - EXISTS<br>";
        } else {
            echo "✗ $col - MISSING<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>SQL to add missing columns:</h3>";
    echo "<pre>";
    
    $missingCols = array_diff($requiredColumns, $foundColumns);
    if (!empty($missingCols)) {
        echo "ALTER TABLE employee_assets ADD COLUMN (";
        $sqlParts = [];
        foreach ($missingCols as $col) {
            if ($col === 'return_date') {
                $sqlParts[] = "return_date DATE NULL";
            } elseif ($col === 'return_notes') {
                $sqlParts[] = "return_notes TEXT NULL";
            } elseif ($col === 'signature_file') {
                $sqlParts[] = "signature_file VARCHAR(255) NULL";
            } elseif ($col === 'proof_file') {
                $sqlParts[] = "proof_file VARCHAR(255) NULL";
            }
        }
        echo implode(", ", $sqlParts);
        echo ");";
    } else {
        echo "All required columns exist!";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
