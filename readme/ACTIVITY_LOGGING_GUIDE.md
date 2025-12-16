# Activity Logging System - Developer Guide

## Overview

The Activity Logging System provides comprehensive tracking of all user actions across the Al-Mutlak WMS application. The ActivityLogger class is automatically available in all pages through `includes/init.php`.

### What Gets Logged:
- **Who**: user_id and user_name
- **What**: action_type (CREATE, UPDATE, DELETE, etc.)
- **Where**: module and page
- **When**: created_at timestamp
- **Which**: record_id and table_name
- **Changes**: old_values and new_values (JSON)
- **Context**: description, IP address, user agent
- **Metadata**: severity and status

---

## Database Structure

### Table: `activity_log`

```sql
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL COMMENT 'Employee ID',
  `user_name` varchar(255) DEFAULT NULL COMMENT 'User name',
  `action_type` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','VIEW','DOWNLOAD','UPLOAD','APPROVE','REJECT','SUBMIT','EXPORT','IMPORT','OTHER') NOT NULL DEFAULT 'OTHER',
  `module` varchar(100) NOT NULL COMMENT 'Module (Employee, Vacation, Loan, etc.)',
  `page` varchar(255) NOT NULL COMMENT 'Page/file name',
  `record_id` varchar(255) DEFAULT NULL COMMENT 'Affected record ID',
  `table_name` varchar(100) DEFAULT NULL COMMENT 'Database table',
  `description` text DEFAULT NULL COMMENT 'Human-readable description',
  `old_values` text DEFAULT NULL COMMENT 'JSON of old values',
  `new_values` text DEFAULT NULL COMMENT 'JSON of new values',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'User IP',
  `user_agent` text DEFAULT NULL COMMENT 'Browser info',
  `severity` enum('INFO','WARNING','CRITICAL','ERROR') DEFAULT 'INFO',
  `status` enum('SUCCESS','FAILED','PENDING') DEFAULT 'SUCCESS',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ActivityLogger Class

The ActivityLogger class is automatically loaded in `includes/init.php` and available on all pages.

### Main Method

```php
ActivityLogger::log(
    $action_type,    // CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, DOWNLOAD, UPLOAD, APPROVE, REJECT, SUBMIT, EXPORT, IMPORT, OTHER
    $module,         // Module name (e.g., 'Employee', 'Vacation', 'Loan')
    $description,    // Human-readable description
    $options = []    // Optional parameters (see below)
)
```

### Optional Parameters in $options Array:
- `user_id` - Override user ID (auto-detected from session if not provided)
- `user_name` - User name for display
- `record_id` - ID of the affected record
- `table_name` - Database table name
- `old_values` - Array of old values (will be JSON encoded)
- `new_values` - Array of new values (will be JSON encoded)
- `page` - Override page name (auto-detected if not provided)
- `severity` - INFO, WARNING, CRITICAL, ERROR (default: INFO)
- `status` - SUCCESS, FAILED, PENDING (default: SUCCESS)

---

## Quick Start Examples

### 1. CREATE Operation

```php
// Example: Adding a new customer
$customer_id = 12345;
$customer_data = [
    'name' => 'ABC Company',
    'email' => 'abc@company.com',
    'phone' => '0501234567'
];

// Insert into database
$stmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
$stmt->execute([$customer_data['name'], $customer_data['email'], $customer_data['phone']]);
$customer_id = $pdo->lastInsertId();

// Log the activity
ActivityLogger::logCreate(
    'Customer',                    // Module
    'add_customer.php',            // Page
    $customer_id,                  // Record ID
    $customer_data,                // New values (will be JSON encoded)
    "Created new customer: ABC Company",  // Description
    'customers'                    // Table name (optional)
);
```

### 2. UPDATE Operation

```php
// Example: Updating employee salary
$emp_id = 5127;

// Get old values first
$old_stmt = $pdo->prepare("SELECT salary, position FROM employees WHERE id = ?");
$old_stmt->execute([$emp_id]);
$old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);

// Update
$new_salary = 8000;
$update_stmt = $pdo->prepare("UPDATE employees SET salary = ? WHERE id = ?");
$update_stmt->execute([$new_salary, $emp_id]);

// Log the update
ActivityLogger::logUpdate(
    'Employee',                    // Module
    'edit_employee.php',           // Page
    $emp_id,                       // Record ID
    ['salary' => $old_data['salary']],  // Old values
    ['salary' => $new_salary],          // New values
    "Updated salary from {$old_data['salary']} to {$new_salary}",  // Description
    'employees'                    // Table name (optional)
);
```

### 3. DELETE Operation

```php
// Example: Deleting a vacation request
$vac_id = 789;

// Get record details before deleting
$stmt = $pdo->prepare("SELECT * FROM vacations WHERE id = ?");
$stmt->execute([$vac_id]);
$vacation_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Delete
$delete_stmt = $pdo->prepare("DELETE FROM vacations WHERE id = ?");
$delete_stmt->execute([$vac_id]);

// Log the deletion
ActivityLogger::logDelete(
    'Vacation',                    // Module
    'delete_vacation.php',         // Page
    $vac_id,                       // Record ID
    $vacation_data,                // Old values (the deleted data)
    "Deleted vacation request for employee {$vacation_data['emp_id']}",
    'vacations'                    // Table name (optional)
);
```

### 4. LOGIN/LOGOUT

```php
// In login.php - after successful authentication
if ($login_successful) {
    $_SESSION['user_id'] = $emp_id;
    $_SESSION['fname'] = $emp_name;
    
    ActivityLogger::logLogin($emp_id, $emp_name);
    
    header('Location: dashboard.php');
    exit;
}

// In logout.php - before destroying session
ActivityLogger::logLogout();

session_destroy();
header('Location: login.php');
exit;
```

### 5. APPROVE/REJECT

```php
// Example: Approving a loan request
if (isset($_POST['approve_loan'])) {
    $loan_id = $_POST['loan_id'];
    
    // Update status
    $stmt = $pdo->prepare("UPDATE loans SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $loan_id]);
    
    // Get loan details
    $loan_stmt = $pdo->prepare("SELECT employee_name, amount FROM loans WHERE id = ?");
    $loan_stmt->execute([$loan_id]);
    $loan = $loan_stmt->fetch();
    
    // Log the approval
    ActivityLogger::logApproval(
        'Loan',                    // Module
        'loan_approval.php',       // Page
        $loan_id,                  // Record ID
        'approved',                // Status (approved/rejected)
        "Approved loan of {$loan['amount']} SAR for {$loan['employee_name']}",  // Comments
        'loans'                    // Table name (optional)
    );
}
```

### 6. FILE UPLOAD/DOWNLOAD

```php
// Upload
if (isset($_FILES['document'])) {
    $filename = $_FILES['document']['name'];
    move_uploaded_file($_FILES['document']['tmp_name'], "uploads/$filename");
    
    ActivityLogger::logUpload(
        'Documents',               // Module
        'upload_document.php',     // Page
        $filename,                 // Filename
        $record_id,                // Related record ID (optional)
        'employee_documents'       // Table name (optional)
    );
}

// Download
ActivityLogger::logDownload(
    'Documents',                   // Module
    'download_document.php',       // Page
    $filename,                     // Filename
    $record_id                     // Related record ID (optional)
);
```

### 7. EXPORT/IMPORT

```php
// Export
$count = export_payroll_to_excel($month);

ActivityLogger::logExport(
    'Payroll',                     // Module
    'payroll_export.php',          // Page
    "Exported payroll for $month", // Description
    $count                         // Record count (optional)
);

// Import
$imported_count = import_attendance_data($file);

ActivityLogger::logImport(
    'Attendance',                  // Module
    'import_attendance.php',       // Page
    "Imported attendance data",    // Description
    $imported_count,               // Record count (optional)
    'SUCCESS'                      // Status (SUCCESS/FAILED/PENDING)
);
```

---

## Helper Methods Reference

### logCreate()
```php
ActivityLogger::logCreate($module, $page, $record_id, $new_values, $description, $table_name)
```
Logs a CREATE action - use after inserting new records.

### logUpdate()
```php
ActivityLogger::logUpdate($module, $page, $record_id, $old_values, $new_values, $description, $table_name)
```
Logs an UPDATE action - automatically extracts only changed fields if arrays are provided.

### logDelete()
```php
ActivityLogger::logDelete($module, $page, $record_id, $old_values, $description, $table_name)
```
Logs a DELETE action - severity automatically set to WARNING.

### logLogin()
```php
ActivityLogger::logLogin($user_id, $user_name)
```
Logs user login.

### logLogout()
```php
ActivityLogger::logLogout()
```
Logs user logout - auto-detects user from session.

### logApproval()
```php
ActivityLogger::logApproval($module, $page, $record_id, $approval_status, $comments, $table_name)
```
Logs approval/rejection - `$approval_status` should be 'approved' or 'rejected'.

### logUpload() / logDownload()
```php
ActivityLogger::logUpload($module, $page, $filename, $record_id, $table_name)
ActivityLogger::logDownload($module, $page, $filename, $record_id)
```
Logs file operations.

### logExport() / logImport()
```php
ActivityLogger::logExport($module, $page, $description, $record_count)
ActivityLogger::logImport($module, $page, $description, $record_count, $status)
```
Logs data export/import operations.

### logSubmit()
```php
ActivityLogger::logSubmit($module, $page, $record_id, $description, $table_name)
```
Logs form submissions.

### logView()
```php
ActivityLogger::logView($module, $page, $record_id, $description)
```
Logs when records are viewed (optional - only for sensitive pages).

---

## Query Methods

### getRecentActivity()
```php
$logs = ActivityLogger::getRecentActivity($limit, $module, $user_id, $action_type);
```
Get recent logs with optional filters.

### getUserActivity()
```php
$logs = ActivityLogger::getUserActivity($user_id, $limit);
```
Get all activity for a specific user.

### getModuleActivity()
```php
$logs = ActivityLogger::getModuleActivity($module, $limit);
```
Get all activity for a specific module.

---

## Advanced Usage

### Custom Logging with Full Options

```php
ActivityLogger::log('UPDATE', 'Payroll', 'Payroll processed', [
    'user_id' => '5127',
    'user_name' => 'Ahmed Ali',
    'record_id' => 'PAY-2025-12',
    'table_name' => 'payroll',
    'page' => 'process_payroll.php',
    'old_values' => ['status' => 'pending'],
    'new_values' => ['status' => 'processed'],
    'severity' => 'INFO',
    'status' => 'SUCCESS'
]);
```

### Logging Failed Operations

```php
try {
    // Attempt operation
    $stmt->execute();
    
    ActivityLogger::log('CREATE', 'Employee', 'Created employee', [
        'record_id' => $emp_id,
        'status' => 'SUCCESS'
    ]);
} catch (Exception $e) {
    // Log failure
    ActivityLogger::log('CREATE', 'Employee', 'Failed to create employee: ' . $e->getMessage(), [
        'severity' => 'ERROR',
        'status' => 'FAILED'
    ]);
}
```

### Logging with Severity Levels

```php
// Critical action
ActivityLogger::log('DELETE', 'Database', 'Dropped table: old_data', [
    'severity' => 'CRITICAL',
    'page' => 'maintenance.php'
]);

// Warning
ActivityLogger::log('UPDATE', 'Settings', 'Changed security settings', [
    'severity' => 'WARNING'
]);

// Info (default)
ActivityLogger::log('VIEW', 'Reports', 'Viewed monthly report');
```

---

## Best Practices Summary

1. **No require needed** - ActivityLogger is auto-loaded via init.php
2. **Log after success** - Only log when operations succeed
3. **Use module names** - Keep them consistent (e.g., 'Employee', 'Vacation', 'Loan')
4. **Provide context** - Include descriptive messages
5. **Capture changes** - Store old and new values for updates
6. **Use arrays** - Let the logger JSON-encode complex data
7. **Set severity** - Use WARNING for deletes, CRITICAL for dangerous operations
8. **Don't log passwords** - Never log sensitive authentication data
9. **Clean regularly** - Archive or delete old logs periodically

---

## Integration Checklist

When adding activity logging to a new page:

- [ ] Identify all CRUD operations (Create, Read, Update, Delete)
- [ ] Choose appropriate module name
- [ ] Add logging after successful operations
- [ ] Capture old values before updates/deletes
- [ ] Use descriptive messages
- [ ] Include record IDs
- [ ] Test logging functionality
- [ ] Verify logs appear in admin panel

---

## Viewing Logs

### Access the Admin Panel
URL: `view_activity_logs.php`

**Permissions**: Admin and IT Manager only

**Features**:
- Real-time statistics dashboard
- Filter by: User, Module, Page, Action, Date Range
- View change details (old/new values)
- Export to CSV
- Responsive DataTable interface

---

## Maintenance & Cleanup

### Automatic Cleanup (Recommended)
Create a monthly cron job:

```php
// File: cron_cleanup_activity_logs.php
<?php
require_once 'includes/init.php';

// Delete logs older than 1 year
$deleted = ActivityLogger::cleanOldLogs(365);

// Log the cleanup
ActivityLogger::log('OTHER', 'System', 'Cleaned old activity logs', [
    'description' => "Deleted logs older than 365 days"
]);

echo "Cleanup complete\n";
```

### Manual Cleanup
From admin panel or phpMyAdmin:
```sql
-- Delete logs older than 6 months
DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- Archive to backup table
CREATE TABLE activity_log_archive AS SELECT * FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

---

## Troubleshooting

### Problem: Logs not being created
**Solutions**:
1. Check `$conDB` is available (database connection)
2. Verify table exists: `SHOW TABLES LIKE 'activity_log';`
3. Check table structure matches schema
4. Ensure user has INSERT permission
5. Check for PHP errors in error log

### Problem: User ID shows as 'SYSTEM'
**Cause**: Session not set or user not logged in
**Solutions**:
1. Ensure session is started
2. Check `$_SESSION['user_id']` or `$_SESSION['empid']` is set
3. Pass user_id explicitly: `['user_id' => $empid]`

### Problem: old_values/new_values empty
**Cause**: Not passing data or passing wrong type
**Solutions**:
1. Ensure you're passing arrays: `['field' => 'value']`
2. Check data is retrieved before logging
3. For simple values, pass as arrays: `['value' => $simple_value]`

### Problem: Performance issues
**Solutions**:
1. Add indexes if missing (see table schema)
2. Clean old logs regularly
3. Archive logs to separate table
4. Consider logging only critical operations, not VIEWs

---

## Security Considerations

1. **Access Control**: Only admins can view logs (`view_activity_logs.php`)
2. **Sensitive Data**: Never log passwords, tokens, or encryption keys
3. **IP Tracking**: Automatically captured for audit trail
4. **Immutable**: Logs should never be edited, only created
5. **Retention**: Keep logs for compliance period (usually 1-2 years)

---

## FAQ

**Q: Do I need to include activity_logger.php?**
A: No, it's automatically loaded via `includes/init.php`.

**Q: What if I don't know the table name?**
A: It's optional. You can pass `null` or omit it.

**Q: Can I log without a record_id?**
A: Yes, pass `null` for operations that don't affect specific records (like exports).

**Q: Should I log all VIEW actions?**
A: Only for sensitive pages (employee details, salary info). Don't log every page view.

**Q: What's the difference between description and old_values/new_values?**
A: Description is human-readable text. Values are for actual data changes (JSON).

**Q: Can I add custom fields?**
A: Modify the table schema and update the log() method in init.php.

---

## Support & Resources

- **Documentation**: This file
- **Source Code**: `includes/init.php` (ActivityLogger class)
- **Admin Panel**: `view_activity_logs.php`
- **Database Schema**: See CREATE TABLE statement above
- **Examples**: `manage_guide_screenshots.php` (reference implementation)

For technical support, contact the IT department.

---

**Last Updated**: December 15, 2025
**Version**: 2.0 (Enhanced Schema)
