# Activity Logging Implementation Guide

## Overview
Activity logging is now available throughout the Al-Mutlak WMS system via the `ActivityLogger` class in `includes/init.php`. This guide shows how to integrate logging into your pages systematically.

## Quick Start

The `ActivityLogger` class is **auto-loaded** in init.php and available on every page that includes `session_check.php`.

### Core Logging Methods

```php
// CREATE - New record added
ActivityLogger::logCreate(
    $module,        // 'Employee', 'Vacation', 'Loan', etc.
    $page,          // 'add_employee.php'
    $record_id,     // ID of new record
    $new_values,    // Array of new data: ['name' => 'Ahmed', 'dept' => 5]
    $description,   // "Created new employee Ahmed"
    $table_name     // 'employees'
);

// UPDATE - Record modified
ActivityLogger::logUpdate(
    $module,
    $page,
    $record_id,
    $old_values,    // Array of old data: ['salary' => 5000]
    $new_values,    // Array of new data: ['salary' => 6000]
    $description,   // "Updated salary from 5000 to 6000 SAR"
    $table_name
);

// DELETE - Record removed
ActivityLogger::logDelete(
    $module,
    $page,
    $record_id,
    $old_values,    // Array of deleted data
    $description,   // "Deleted employee Ahmed"
    $table_name
);

// LOGIN
ActivityLogger::logLogin($user_id, $user_name);

// LOGOUT
ActivityLogger::logLogout();

// APPROVAL (APPROVE/REJECT)
ActivityLogger::logApproval($module, $page, $record_id, 'approved'|'rejected', $description, $table_name);

// UPLOAD
ActivityLogger::logUpload($module, $page, $file_name, $file_size, $description);

// DOWNLOAD
ActivityLogger::logDownload($module, $page, $file_name, $description);

// EXPORT
ActivityLogger::logExport($module, $page, $record_count, $description);

// IMPORT
ActivityLogger::logImport($module, $page, $record_count, $description);

// SUBMIT (Forms)
ActivityLogger::logSubmit($module, $page, $form_name, $description);

// VIEW (Record accessed)
ActivityLogger::logView($module, $page, $record_id, $description);
```

## Implementation Patterns

### Pattern 1: INSERT Operation (CREATE)

```php
// After successful INSERT
if (mysqli_query($conDB, $sql)) {
    $new_id = mysqli_insert_id($conDB);
    ActivityLogger::logCreate(
        'Employee',
        'add_new_employee.php',
        $new_id,
        [
            'name' => $_POST['emp_name'],
            'dept' => $_POST['dept_id'],
            'salary' => $_POST['salary']
        ],
        "Created new employee: " . $_POST['emp_name'],
        'employees'
    );
}
```

### Pattern 2: UPDATE Operation (UPDATE)

```php
// Fetch old values before update
$old_result = mysqli_query($conDB, "SELECT * FROM employees WHERE id = ?");
$old_data = mysqli_fetch_assoc($old_result);

// Perform update
if (mysqli_query($conDB, $update_sql)) {
    ActivityLogger::logUpdate(
        'Employee',
        'edit_employee.php',
        $emp_id,
        [
            'salary' => $old_data['salary'],
            'dept' => $old_data['dept']
        ],
        [
            'salary' => $_POST['new_salary'],
            'dept' => $_POST['new_dept']
        ],
        "Updated employee details",
        'employees'
    );
}
```

### Pattern 3: DELETE Operation (DELETE)

```php
// Fetch data before deletion
$result = mysqli_query($conDB, "SELECT * FROM employees WHERE id = ?");
$deleted_data = mysqli_fetch_assoc($result);

// Perform delete
if (mysqli_query($conDB, "DELETE FROM employees WHERE id = ?")) {
    ActivityLogger::logDelete(
        'Employee',
        'delete_employee.php',
        $emp_id,
        $deleted_data,
        "Deleted employee: " . $deleted_data['name'],
        'employees'
    );
}
```

### Pattern 4: Approval Workflow (APPROVE/REJECT)

```php
// Vacation approval
if (isset($_POST['approve_vacation'])) {
    if (mysqli_query($conDB, "UPDATE emp_vacation SET status = 'approved' WHERE id = ?")) {
        ActivityLogger::logApproval(
            'Vacation',
            'approve_vacation.php',
            $vacation_id,
            'approved',
            "Approved 5 days vacation for Ahmed",
            'emp_vacation'
        );
    }
}

// Loan rejection
if (isset($_POST['reject_loan'])) {
    if (mysqli_query($conDB, "UPDATE loans SET status = 'rejected' WHERE id = ?")) {
        ActivityLogger::logApproval(
            'Loan',
            'approve_loan.php',
            $loan_id,
            'rejected',
            "Rejected loan: Invalid documentation",
            'loans'
        );
    }
}
```

### Pattern 5: AJAX Operations

For AJAX endpoints in `includes/ajaxFile/`, log the operation:

```php
// In includes/ajaxFile/editEmployee.php
if ($_POST['action'] == 'update_salary') {
    $emp_id = $_POST['emp_id'];
    $old_salary = $_POST['old_salary'];
    $new_salary = $_POST['new_salary'];
    
    if (mysqli_query($conDB, "UPDATE employees SET salary = ? WHERE id = ?")) {
        ActivityLogger::logUpdate(
            'Employee',
            'editEmployee.php',
            $emp_id,
            ['salary' => $old_salary],
            ['salary' => $new_salary],
            "Updated salary via AJAX",
            'employees'
        );
        
        echo json_encode(['status' => 'success']);
    }
}
```

## Modules & Pages

Implement logging in these critical areas:

### Authentication
- `login_verification.php` - Log login attempts (success/failure)
- `logout.php` - Log logout
- Pages: use ActivityLogger::logLogin() and logLogout()

### Employee Management
- `add_new_employee.php` - Log CREATE
- `edit_employee.php` - Log UPDATE
- `delete_employee.php` or AJAX - Log DELETE
- `all_employee_list.php` - Optional: Log VIEW for bulk operations

### User Management
- `add_user.php` - Log CREATE
- `edit_user.php` - Log UPDATE
- AJAX delete - Log DELETE
- `all_users.php` - Check if any bulk operations need logging

### Vacation Management
- `apply_vac_emp_dept.php` - Log SUBMIT (vacation request)
- `emp_vac.php` - Log VIEW
- Approval endpoint - Log APPROVE/REJECT

### Loan Management
- `all_applied_loan.php` - Check for submit/request logging
- Approval endpoint - Log APPROVE/REJECT

### Asset Management
- `add_car.php` - Log CREATE
- `edit_car.php` - Log UPDATE
- Deletion - Log DELETE

### Document Upload/Download
- Screenshot upload - Log UPLOAD
- Document download - Log DOWNLOAD
- Report export - Log EXPORT

## Testing Activity Logs

View all activity:
```
http://your-site/view_activity_logs.php
```

Filter by:
- User ID
- Module (Employee, Vacation, Loan, etc.)
- Page
- Action Type (CREATE, UPDATE, DELETE, etc.)
- Date Range

## Best Practices

1. **Always log after successful operations** - Check return value before logging
2. **Use descriptive modules** - 'Employee', 'Vacation', 'Loan', not 'add_emp'
3. **Include record IDs** - Always capture $record_id for traceability
4. **Log before DELETE** - Fetch old data before deleting so we have the record
5. **Describe actions clearly** - "Updated salary from 5000 to 6000 SAR" is better than "Updated"
6. **Use consistent table names** - Match actual database table names

## Automatic Fields

These are automatically captured:
- `user_id` - From session ($empid, $_SESSION['user_id'], etc.)
- `user_name` - From session ($fname, $_SESSION['fname'])
- `ip_address` - Auto-detected (handles proxies)
- `user_agent` - Browser info
- `created_at` - Current timestamp
- `page` - Auto-detected if not provided

## Module Naming Convention

Use these standardized module names across the project:
- Employee
- Vacation
- Loan
- Customer
- Vehicle/Car
- Machine
- Location
- Contract
- Payroll
- Attendance
- Asset
- Request
- Document
- User/Admin
- System Guide
- Authentication
- Report

## Activity Log Retention

Old logs are automatically retained. To clean up logs older than 1 year:
```php
ActivityLogger::cleanOldLogs(365);
```

(Typically done via cron job, not on each page)

## Support

For questions or issues with activity logging, refer to:
- `view_activity_logs.php` - Admin viewer/debugger
- `includes/init.php` - ActivityLogger class source
- This guide - Implementation patterns

---

**Last Updated:** December 15, 2025
**Status:** Production Ready
