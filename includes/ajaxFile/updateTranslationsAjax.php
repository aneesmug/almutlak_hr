<?php
// File: admin/includes/ajaxFile/updateTranslationsAjax.php
// Handles the bulk insert/update of translations from the new format.

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/translation_functions.php';

header('Content-Type: application/json');

if (isset($_POST['translations'])) {
    $translations_text = trim($_POST['translations']);
    $lines = explode("\n", $translations_text);

    $lines_processed = 0;
    $lines_failed = 0;

    if (empty($lines) || (count($lines) == 1 && trim($lines[0]) == '')) {
        echo json_encode(['title' => 'Empty!', 'message' => 'No translation data provided.', 'type' => 'warning']);
        exit;
    }

    // Use a prepared statement for security and performance
    $stmt = mysqli_prepare($conDB, "INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`)");

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Use str_getcsv to correctly parse lines, even if translations contain a comma
        $parts = str_getcsv($line);

        if (count($parts) === 3) {
            $lang_key = trim($parts[0]);
            $en_translation = trim($parts[1]);
            $ar_translation = trim($parts[2]);

            if (!empty($lang_key)) {
                // Insert/Update English
                $lang_code_en = 'en';
                mysqli_stmt_bind_param($stmt, "sss", $lang_key, $lang_code_en, $en_translation);
                mysqli_stmt_execute($stmt);

                // Insert/Update Arabic
                $lang_code_ar = 'ar';
                mysqli_stmt_bind_param($stmt, "sss", $lang_key, $lang_code_ar, $ar_translation);
                mysqli_stmt_execute($stmt);
                
                $lines_processed++;
            } else {
                $lines_failed++;
            }
        } else {
            $lines_failed++;
        }
    }
    mysqli_stmt_close($stmt);

    // Regenerate the JSON cache files
    regenerate_translation_files($conDB);

    $message = "Successfully processed {$lines_processed} lines.";
    if ($lines_failed > 0) {
        $message .= " Failed to process {$lines_failed} lines due to incorrect format.";
    }

    echo json_encode(['title' => 'Process Complete', 'message' => $message, 'type' => 'success']);

} else {
    echo json_encode(['title' => 'Error!', 'message' => 'No data received.', 'type' => 'error']);
}
?>