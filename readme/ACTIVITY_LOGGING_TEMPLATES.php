<?php
/**
 * ACTIVITY LOGGING INTEGRATION TEMPLATES
 * 
 * Copy-paste these patterns into your pages and customize the module/page/field names.
 * All pages that include session_check.php have access to ActivityLogger class.
 */

// ============================================================================
// TEMPLATE 1: CREATE (INSERT) OPERATION
// ============================================================================
// Location: Add pages (add_employee.php, add_customer.php, add_car.php, etc.)

if (isset($_POST['submit_form'])) {
    // Validate form
    if (empty($_POST['name'])) {
        $error = "Name is required";
    } else {
        // Prepare data
        $name = $_POST['name'];
        $dept = $_POST['dept'];
        $salary = $_POST['salary'];
        
        // INSERT
        $sql = "INSERT INTO employees (name, dept, salary) VALUES ('$name', '$dept', '$salary')";
        
        if (mysqli_query($conDB, $sql)) {
            $new_id = mysqli_insert_id($conDB);
            
            // LOG THE CREATE ACTION
            ActivityLogger::logCreate(
                'Employee',                    // Module name
                'add_employee.php',            // Page name
                $new_id,                       // Record ID
                [                              // New values array
                    'name' => $name,
                    'dept' => $dept,
                    'salary' => $salary
                ],
                "Created new employee: $name", // Description
                'employees'                    // Table name
            );
            
            $_SESSION['success_msg'] = "Employee created successfully!";
            header("Location: all_employee_list.php");
            exit;
        } else {
            $error = "Error creating employee";
        }
    }
}

// ============================================================================
// TEMPLATE 2: UPDATE (EDIT) OPERATION
// ============================================================================
// Location: Edit pages (edit_employee.php, edit_customer.php, edit_car.php, etc.)

if (isset($_POST['submit_form'])) {
    $emp_id = $_POST['emp_id'];
    $new_name = $_POST['name'];
    $new_dept = $_POST['dept'];
    $new_salary = $_POST['salary'];
    
    // FETCH OLD VALUES BEFORE UPDATE
    $old_result = mysqli_query($conDB, "SELECT name, dept, salary FROM employees WHERE id = '$emp_id'");
    $old_data = mysqli_fetch_assoc($old_result);
    
    // UPDATE
    $sql = "UPDATE employees SET name = '$new_name', dept = '$new_dept', salary = '$new_salary' WHERE id = '$emp_id'";
    
    if (mysqli_query($conDB, $sql)) {
        // LOG THE UPDATE ACTION
        ActivityLogger::logUpdate(
            'Employee',                    // Module name
            'edit_employee.php',           // Page name
            $emp_id,                       // Record ID
            [                              // Old values array
                'name' => $old_data['name'],
                'dept' => $old_data['dept'],
                'salary' => $old_data['salary']
            ],
            [                              // New values array
                'name' => $new_name,
                'dept' => $new_dept,
                'salary' => $new_salary
            ],
            "Updated employee: $new_name", // Description
            'employees'                    // Table name
        );
        
        $_SESSION['success_msg'] = "Employee updated successfully!";
        header("Location: all_employee_list.php");
        exit;
    }
}

// ============================================================================
// TEMPLATE 3: DELETE OPERATION
// ============================================================================
// Location: Delete pages or AJAX delete endpoints

if (isset($_POST['delete_id'])) {
    $emp_id = $_POST['delete_id'];
    
    // FETCH RECORD DATA BEFORE DELETION
    $result = mysqli_query($conDB, "SELECT * FROM employees WHERE id = '$emp_id'");
    $deleted_data = mysqli_fetch_assoc($result);
    
    // DELETE
    if (mysqli_query($conDB, "DELETE FROM employees WHERE id = '$emp_id'")) {
        // LOG THE DELETE ACTION
        ActivityLogger::logDelete(
            'Employee',                           // Module name
            'delete_employee.php',                // Page name
            $emp_id,                              // Record ID
            $deleted_data,                        // Old values (entire record)
            "Deleted employee: " . $deleted_data['name'], // Description
            'employees'                           // Table name
        );
        
        $_SESSION['success_msg'] = "Employee deleted successfully!";
        header("Location: all_employee_list.php");
        exit;
    }
}

// ============================================================================
// TEMPLATE 4: APPROVAL WORKFLOW (APPROVE/REJECT)
// ============================================================================
// Location: Approval pages (approve_vacation.php, approve_loan.php, etc.)

if (isset($_POST['approve_request'])) {
    $request_id = $_POST['request_id'];
    $approval_notes = $_POST['notes'];
    
    // APPROVE
    $sql = "UPDATE emp_vacation SET status = 'approved', approved_by = '$empid', approved_date = NOW(), notes = '$approval_notes' WHERE id = '$request_id'";
    
    if (mysqli_query($conDB, $sql)) {
        // LOG APPROVAL
        ActivityLogger::logApproval(
            'Vacation',                    // Module name
            'approve_vacation.php',        // Page name
            $request_id,                   // Record ID
            'approved',                    // Status: 'approved' or 'rejected'
            "Approved vacation request. Notes: $approval_notes", // Description
            'emp_vacation'                 // Table name
        );
        
        $_SESSION['success_msg'] = "Request approved!";
        header("Location: all_applied_vac.php");
        exit;
    }
}

if (isset($_POST['reject_request'])) {
    $request_id = $_POST['request_id'];
    $rejection_reason = $_POST['reason'];
    
    // REJECT
    $sql = "UPDATE emp_vacation SET status = 'rejected', rejected_by = '$empid', rejected_date = NOW(), reason = '$rejection_reason' WHERE id = '$request_id'";
    
    if (mysqli_query($conDB, $sql)) {
        // LOG REJECTION
        ActivityLogger::logApproval(
            'Vacation',                    // Module name
            'approve_vacation.php',        // Page name
            $request_id,                   // Record ID
            'rejected',                    // Status: 'approved' or 'rejected'
            "Rejected vacation request. Reason: $rejection_reason", // Description
            'emp_vacation'                 // Table name
        );
        
        $_SESSION['success_msg'] = "Request rejected!";
        header("Location: all_applied_vac.php");
        exit;
    }
}

// ============================================================================
// TEMPLATE 5: UPLOAD/DOWNLOAD OPERATIONS
// ============================================================================
// Location: File handling pages

// UPLOAD
if (isset($_FILES['upload_file'])) {
    $file = $_FILES['upload_file'];
    $filename = $file['name'];
    $filesize = $file['size'];
    $target_path = "uploads/" . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // LOG UPLOAD
        ActivityLogger::logUpload(
            'Document',                    // Module name
            'upload_document.php',         // Page name
            $filename,                     // File name
            $filesize,                     // File size in bytes
            "Uploaded document: $filename" // Description
        );
    }
}

// DOWNLOAD
if (isset($_GET['download_file'])) {
    $filename = $_GET['download_file'];
    $filepath = "uploads/" . $filename;
    
    if (file_exists($filepath)) {
        // LOG DOWNLOAD
        ActivityLogger::logDownload(
            'Document',                    // Module name
            'download_document.php',       // Page name
            $filename,                     // File name
            "Downloaded document: $filename" // Description
        );
        
        // Serve file
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filepath);
        exit;
    }
}

// ============================================================================
// TEMPLATE 6: EXPORT OPERATION
// ============================================================================
// Location: Report/export pages

if (isset($_POST['export_payroll'])) {
    // Fetch data to export
    $result = mysqli_query($conDB, "SELECT * FROM payroll WHERE month = '$selected_month'");
    $record_count = mysqli_num_rows($result);
    
    // Generate file (CSV, Excel, PDF, etc.)
    // ... export code ...
    
    // LOG EXPORT
    ActivityLogger::logExport(
        'Payroll',                     // Module name
        'payroll_report.php',          // Page name
        $record_count,                 // Number of records exported
        "Exported payroll for $selected_month ($record_count records)" // Description
    );
    
    // Serve download
    // ... download code ...
}

// ============================================================================
// TEMPLATE 7: IMPORT OPERATION
// ============================================================================
// Location: Bulk import pages

if (isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $lines = file($file['tmp_name']);
    $imported_count = 0;
    
    foreach ($lines as $line) {
        $data = str_getcsv($line);
        // Parse and insert data
        // ... import code ...
        $imported_count++;
    }
    
    // LOG IMPORT
    ActivityLogger::logImport(
        'Attendance',                  // Module name
        'import_attendance.php',       // Page name
        $imported_count,               // Number of records imported
        "Imported $imported_count attendance records from file" // Description
    );
}

// ============================================================================
// TEMPLATE 8: AJAX DELETE (Most Common AJAX Pattern)
// ============================================================================
// Location: includes/ajaxFile/deleteEmployee.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

if (isset($_POST['emp_id'])) {
    $emp_id = $_POST['emp_id'];
    
    // FETCH RECORD DATA BEFORE DELETION
    $result = mysqli_query($conDB, "SELECT * FROM employees WHERE id = '$emp_id'");
    $deleted_data = mysqli_fetch_assoc($result);
    
    // DELETE
    if (mysqli_query($conDB, "DELETE FROM employees WHERE id = '$emp_id'")) {
        // LOG THE DELETE ACTION
        ActivityLogger::logDelete(
            'Employee',
            'deleteEmployee.php',
            $emp_id,
            $deleted_data,
            "Deleted employee via AJAX: " . $deleted_data['name'],
            'employees'
        );
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Employee deleted successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete employee'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
?>

// ============================================================================
// TEMPLATE 9: FORM SUBMIT (SUBMIT Action)
// ============================================================================
// Location: Any form submission page

if (isset($_POST['submit_application'])) {
    // Process form
    $app_id = processApplicationForm($_POST);
    
    if ($app_id) {
        // LOG FORM SUBMISSION
        ActivityLogger::logSubmit(
            'Vacation',                     // Module name
            'apply_vacation.php',           // Page name
            'vacation_application_form',    // Form name
            "Submitted vacation application for 5 days" // Description
        );
        
        $_SESSION['success_msg'] = "Application submitted successfully!";
        header("Location: application_status.php?id=$app_id");
        exit;
    }
}

// ============================================================================
// TEMPLATE 10: VIEW/ACCESS LOGGING
// ============================================================================
// Location: Detail/view pages

<?php
// At the top of view_employee.php
$emp_id = $_GET['id'];
$result = mysqli_query($conDB, "SELECT * FROM employees WHERE id = '$emp_id'");
$employee = mysqli_fetch_assoc($result);

if ($employee) {
    // LOG VIEW
    ActivityLogger::logView(
        'Employee',                     // Module name
        'view_employee.php',            // Page name
        $emp_id,                        // Record ID
        "Viewed employee details: " . $employee['name'] // Description
    );
}
?>

?>
