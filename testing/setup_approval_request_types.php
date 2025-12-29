<?php
/**
 * Setup Script: Ensure approval_request_types table exists with default data
 * 
 * Usage:
 * 1. Navigate to: http://yoursite.com/system/setup_approval_request_types.php
 * 2. Script will create table and seed default data
 * 3. Delete this file after running (optional but recommended)
 */

require_once __DIR__ . '/includes/db.php';

// Check if table exists
$tableCheckQuery = mysqli_query($conDB, "SHOW TABLES LIKE 'approval_request_types'");
$tableExists = mysqli_num_rows($tableCheckQuery) > 0;

if ($tableCheckQuery) mysqli_free_result($tableCheckQuery);

echo '<pre style="background: #f4f4f4; padding: 20px; border-radius: 4px; font-family: monospace;">';

if (!$tableExists) {
    echo "📋 Creating approval_request_types table...\n";
    
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `approval_request_types` (
      `id` varchar(64) NOT NULL COMMENT 'Unique request type ID (e.g., vacation_request, loan_request)',
      `type_name` varchar(255) NOT NULL COMMENT 'Display name for the request type',
      `description` text DEFAULT NULL COMMENT 'Description of what this request type is for',
      `is_default` tinyint(1) DEFAULT 0 COMMENT 'Flag: 1 = default type (cannot delete), 0 = custom type',
      `is_active` tinyint(1) DEFAULT 1 COMMENT 'Flag: 1 = active, 0 = inactive/deprecated',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Timestamp when this request type was created',
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'Timestamp when last updated',
      PRIMARY KEY (`id`),
      KEY `idx_is_default` (`is_default`),
      KEY `idx_is_active` (`is_active`),
      KEY `idx_type_name` (`type_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Request types for approval chain system'";
    
    if (mysqli_query($conDB, $createTableSQL)) {
        echo "✅ Table created successfully!\n\n";
    } else {
        echo "❌ Error creating table: " . mysqli_error($conDB) . "\n\n";
        die();
    }
} else {
    echo "✅ Table approval_request_types already exists\n\n";
}

// Default request types
$defaultTypes = [
    [
        'id' => 'vacation_request',
        'type_name' => 'Vacation Request',
        'description' => 'Annual vacation and fly vacation approval chain',
        'is_default' => 1
    ],
    [
        'id' => 'excuse_leave',
        'type_name' => 'Excuse Leave',
        'description' => 'Sick leave, exam leave, hajj, maternity, marriage, death, business trip',
        'is_default' => 1
    ],
    [
        'id' => 'loan_request',
        'type_name' => 'Loan Request',
        'description' => 'Employee loan application approval chain (regular, emergency, end of service, housing, advance salary)',
        'is_default' => 1
    ],
    [
        'id' => 'resignation_request',
        'type_name' => 'Resignation Request',
        'description' => 'Employee resignation approval chain with asset clearance',
        'is_default' => 1
    ],
    [
        'id' => 'rejoin_request',
        'type_name' => 'Rejoin Request',
        'description' => 'Employee rejoin after resignation approval chain',
        'is_default' => 1
    ]
];

echo "📝 Seeding default request types...\n";
$insertedCount = 0;
$skippedCount = 0;

foreach ($defaultTypes as $type) {
    $id = mysqli_real_escape_string($conDB, $type['id']);
    $typeName = mysqli_real_escape_string($conDB, $type['type_name']);
    $description = mysqli_real_escape_string($conDB, $type['description']);
    $isDefault = $type['is_default'];
    
    // Check if already exists
    $checkQuery = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE id = '{$id}' LIMIT 1");
    
    if ($checkQuery && mysqli_num_rows($checkQuery) > 0) {
        echo "⏭️  Skipped: $typeName (already exists)\n";
        $skippedCount++;
        mysqli_free_result($checkQuery);
    } else {
        $insertQuery = "INSERT INTO approval_request_types (id, type_name, description, is_default, is_active, created_at) 
                        VALUES ('{$id}', '{$typeName}', '{$description}', {$isDefault}, 1, NOW())";
        
        if (mysqli_query($conDB, $insertQuery)) {
            echo "✅ Inserted: $typeName\n";
            $insertedCount++;
        } else {
            echo "❌ Error inserting $typeName: " . mysqli_error($conDB) . "\n";
        }
        if ($checkQuery) mysqli_free_result($checkQuery);
    }
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Summary:\n";
echo "  ✅ Inserted: $insertedCount\n";
echo "  ⏭️  Skipped: $skippedCount\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Verify data
echo "📊 Verifying data in database...\n\n";
$verifyQuery = mysqli_query($conDB, "SELECT id, type_name, is_default, is_active FROM approval_request_types ORDER BY id");

if ($verifyQuery && mysqli_num_rows($verifyQuery) > 0) {
    echo "Request Types in Database:\n";
    echo "┌────────────────────────────────────┬─────────────────────┬───────────┬──────────┐\n";
    echo "│ ID                                 │ Type Name           │ Default   │ Active   │\n";
    echo "├────────────────────────────────────┼─────────────────────┼───────────┼──────────┤\n";
    
    while ($row = mysqli_fetch_assoc($verifyQuery)) {
        $id = str_pad($row['id'], 35);
        $name = str_pad(substr($row['type_name'], 0, 19), 21);
        $default = $row['is_default'] ? '✅ Yes' : '❌ No ';
        $active = $row['is_active'] ? '✅ Yes' : '❌ No ';
        echo "│ $id │ $name │ $default   │ $active  │\n";
    }
    
    echo "└────────────────────────────────────┴─────────────────────┴───────────┴──────────┘\n";
    mysqli_free_result($verifyQuery);
} else {
    echo "❌ No request types found in database!\n";
}

echo "\n✅ Setup completed successfully!\n";
echo "\n⚠️  Security Note: Delete this file (setup_approval_request_types.php) after running.\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "</pre>";

?>
