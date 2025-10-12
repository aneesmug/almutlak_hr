<?php
/**
 * MODIFICATION SUMMARY
 *
 * Created this new file to handle the server-side processing of the uploaded Excel file.
 * It reads the Excel data using PhpSpreadsheet, converts Gregorian dates to Hijri using 
 * the IntlDateFormatter, and updates the employee records in the database based on the 
 * Iqama number. It includes error handling and redirects back to the import page with 
 * status messages. A new function convertGregorianToHijri() is added at the end for the 
 * date conversion.
 */

// NOTE: Ensure you have a database connection file (e.g., db.php) and PhpSpreadsheet library installed.
// Example: require_once 'db.php';
// Example: require_once 'vendor/autoload.php';
require_once(__DIR__ . "/db.php");
require_once(__DIR__ . "/vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Check if the form was submitted
if (isset($_POST["import"])) {
    // Check if file was uploaded without errors
    if (isset($_FILES["employee_file"]) && $_FILES["employee_file"]["error"] == 0) {
        $allowedFileType = ['application/vnd.ms-excel', 'text/xls', 'text/xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        if (in_array($_FILES["employee_file"]["type"], $allowedFileType)) {
            
            // It's recommended to use a more secure temporary path
            $targetPath = 'uploads/' . basename($_FILES['employee_file']['name']);
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            move_uploaded_file($_FILES['employee_file']['tmp_name'], $targetPath);

            try {
                // Database connection - replace with your actual connection logic
                // $conn = new mysqli("localhost", "username", "password", "database");
                // if ($conDB->connect_error) {
                //     throw new Exception("Connection failed: " . $conDB->connect_error);
                // }

                $spreadsheet = IOFactory::load($targetPath);
                $sheet = $spreadsheet->getActiveSheet();
                // toArray(null, true, true, true) returns data as an associative array with cell coordinates (A1, B2)
                // We will iterate by row index instead to be more robust
                $highestRow = $sheet->getHighestRow();

                $updatedCount = 0;
                $notFoundCount = 0;

                // Prepare the update statement
                $stmt = $conDB->prepare("UPDATE employees SET iqama_exp_g = ?, iqama_exp = ? WHERE iqama = ?");

                // Start from row 2 to skip the header
                for ($row = 2; $row <= $highestRow; $row++) {
                    $iqama = trim($sheet->getCell('A' . $row)->getValue());
                    $iqama_exp_hijri_excel = trim($sheet->getCell('B' . $row)->getValue());

                    if (!empty($iqama) && !empty($iqama_exp_hijri_excel)) {
                        // We expect the Hijri date to be a string in 'YYYY-MM-DD' format
                        $hijriDateStr = $iqama_exp_hijri_excel;
                       
                        // Convert Hijri to Gregorian
                        $gregorianDateStr = convertHijriToGregorian($hijriDateStr);

                        if (empty($gregorianDateStr)) {
                            // Skip if date conversion fails
                            continue;
                        }

                        // Check if employee exists with the given iqama
                        $checkStmt = $conDB->prepare("SELECT id FROM employees WHERE iqama = ?");
                        $checkStmt->bind_param("s", $iqama);
                        $checkStmt->execute();
                        $result = $checkStmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            // Employee found, update the record
                            $stmt->bind_param("sss", $gregorianDateStr, $hijriDateStr, $iqama);
                            if($stmt->execute()){
                                $updatedCount++;
                            }
                        } else {
                            // Employee not found
                            $notFoundCount++;
                        }
                        $checkStmt->close();
                    }
                }
                $successMessage = "<b>{$updatedCount}</b> records updated successfully.";
                if ($notFoundCount > 0) {
                    $successMessage .= "<br><b>{$notFoundCount}</b> Iqama numbers were not found.";
                }
                showSweetAlert('Import Successful!', $successMessage, 'success', '../import_iqama_exp.php');

            } catch (Exception $e) {
                $errorMessage = "Error processing file: " . htmlspecialchars($e->getMessage());
                showSweetAlert('Processing Error!', $errorMessage, 'error', '../import_iqama_exp.php');
            }
        } else {
            $errorMessage = "Invalid file type. Please upload a valid Excel or CSV file.";
            showSweetAlert('File Type Error!', $errorMessage, 'error', '../import_iqama_exp.php');
        }
    } else {
        $errorMessage = "No file uploaded or an error occurred during upload. Please try again.";
        showSweetAlert('Upload Error!', $errorMessage, 'error', '../import_iqama_exp.php');
    }
} else {
    header("Location../: import_iqama_exp.php");
    exit();
}

/**
 * Converts a Hijri date string to a Gregorian date string.
 *
 * @param string $hijriDateStr The date in Hijri format 'YYYY-MM-DD'.
 * @return string The date in Gregorian format 'Y-m-d'.
 */
function convertHijriToGregorian($hijriDateStr) {
    if (!class_exists('IntlDateFormatter') || empty($hijriDateStr)) {
        return '';
    }

    // Create a formatter to parse the Hijri date
    $hijriParser = new IntlDateFormatter(
        'en_US@calendar=islamic-umalqura',
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Riyadh',
        IntlDateFormatter::TRADITIONAL,
        'yyyy-MM-dd'
    );

    // Parse the Hijri date string to a timestamp
    $timestamp = $hijriParser->parse($hijriDateStr);

    if ($timestamp === false) {
        return ''; // Return empty if parsing fails
    }

    // Create a formatter for the Gregorian calendar
    $gregorianFormatter = new IntlDateFormatter(
        'en_US@calendar=gregorian',
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Riyadh',
        IntlDateFormatter::TRADITIONAL,
        'yyyy-MM-dd'
    );

    return $gregorianFormatter->format($timestamp);
}

/**
 * Converts a Gregorian date string to a Hijri (Umm al-Qura) date string.
 *
 * This function requires the 'intl' PHP extension to be enabled.
 *
 * @param string $gregorianDateStr The date in a format parsable by strtotime(), preferably 'Y-m-d'.
 * @return string The date in Hijri format 'yyyy-MM-dd'.
 * @throws Exception If the 'intl' extension is not available.
 */
function convertGregorianToHijri($gregorianDateStr) {
    if (!class_exists('IntlDateFormatter')) {
        throw new Exception("PHP 'intl' extension is required for accurate date conversion but it is not enabled.");
    }
    // Create a formatter for the Hijri calendar
    // 'ar_SA' for Arabic, Saudi Arabia. @calendar=islamic-umalqura is for the Umm al-Qura calendar
    $formatter = new IntlDateFormatter(
        'en_US@calendar=islamic-umalqura',
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Riyadh',
        IntlDateFormatter::TRADITIONAL,
        'yyyy-MM-dd' // The desired output format (e.g., 1445-03-25)
    );

    $timestamp = strtotime($gregorianDateStr);
    if ($timestamp === false) {
        return ''; // Or handle error for invalid date string
    }

    return $formatter->format($timestamp);
}
function showSweetAlert($title, $text, $icon, $redirectUrl) {
    // Using a heredoc for clean HTML output
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Processing Status</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            // Wait for the document to be fully loaded before showing the alert
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '{$title}',
                    html: '{$text}',
                    icon: '{$icon}',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false // Prevents closing by clicking outside
                }).then((result) => {
                    // Redirect after the user clicks "OK"
                    if (result.isConfirmed) {
                        window.location.href = '{$redirectUrl}';
                    }
                });
            });
        </script>
    </body>
    </html>
HTML;
    exit(); // Stop script execution after sending the alert page
}