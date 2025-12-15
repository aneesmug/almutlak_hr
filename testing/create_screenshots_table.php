<?php
/**
 * Create Screenshots Table for System Guide
 * Run this file once to create the necessary database table
 */

require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/db.php");

try {
    // Create the screenshots table
    $sql = "CREATE TABLE IF NOT EXISTS `guide_screenshots` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `section` VARCHAR(50) NOT NULL COMMENT 'Section: vacations, loans, excuse, resignation, rejoin',
        `step_number` INT NOT NULL COMMENT 'Step number in the section',
        `title` VARCHAR(100) NOT NULL COMMENT 'Screenshot title',
        `filename` VARCHAR(255) NOT NULL COMMENT 'Original filename',
        `file_path` VARCHAR(255) NOT NULL COMMENT 'Path to the uploaded file',
        `display_order` INT DEFAULT 1 COMMENT 'Order to display screenshots',
        `is_active` TINYINT DEFAULT 1 COMMENT 'Whether to show this screenshot',
        `uploaded_by` INT COMMENT 'User ID who uploaded',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_section (section),
        INDEX idx_step (step_number),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($pdo->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Screenshots table created successfully!</div>";
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Screenshots Table</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .alert { max-width: 600px; margin: 20px auto; }
    </style>
</head>
<body>
    <div class="container">
        <h2>System Guide Screenshots Setup</h2>
        <p>Table creation completed. You can now use the screenshot upload system.</p>
        <p><a href="manage_guide_screenshots.php" class="btn btn-primary">Go to Screenshot Manager</a></p>
    </div>
</body>
</html>
