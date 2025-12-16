# Activity Logging - Quick Reference Card

**Keeping this quick for developers to use when integrating logging.**

---

## One-Liner Checklist Before Each Integration

- [ ] Include/require session_check.php? (No - auto-loaded via init.php)
- [ ] Is ActivityLogger available? (Yes - always, it's in init.php)
- [ ] Do I need to fetch old values for UPDATE? (Yes)
- [ ] Do I need to fetch complete record for DELETE? (Yes)
- [ ] Should I log this operation? (Yes, if it modifies data)

---

## The 4 Most Common Cases

### 1️⃣ CREATE (INSERT) - Simplest

```php
// After INSERT
ActivityLogger::logCreate(
    'ModuleName',
    'page_name.php',
    $new_id,
    $_POST,  // or $data array
    "Created new item",
    'table_name'
);
```

**3 Lines. Done.**

### 2️⃣ UPDATE (EDIT) - Get OLD values first

```php
// BEFORE UPDATE: Fetch old data
$old = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT * FROM table WHERE id = '$id'"));

// Do UPDATE...
// ... update code ...

// AFTER UPDATE: Log both old and new
ActivityLogger::logUpdate(
    'ModuleName',
    'page_name.php',
    $id,
    $old,
    $_POST,
    "Updated item",
    'table_name'
);
```

**Key:** Fetch old data BEFORE you change anything.

### 3️⃣ DELETE - Keep deleted data

```php
// BEFORE DELETE: Save record
$deleted = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT * FROM table WHERE id = '$id'"));

// Do DELETE...
// ... delete code ...

// AFTER DELETE: Log what was deleted
ActivityLogger::logDelete(
    'ModuleName',
    'page_name.php',
    $id,
    $deleted,
    "Deleted item",
    'table_name'
);
```

**Key:** Save the COMPLETE record before deleting.

### 4️⃣ APPROVE/REJECT - Workflow actions

```php
// For approval
ActivityLogger::logApproval(
    'Vacation',
    'approve_vacation.php',
    $request_id,
    'approved',  // or 'rejected'
    "Approved by manager X",
    'vacation_requests'
);
```

---

## All Available Methods (Copy-Paste Ready)

### Main Logging Methods

```php
// CREATE
ActivityLogger::logCreate($module, $page, $record_id, $new_values, $description, $table_name);

// UPDATE
ActivityLogger::logUpdate($module, $page, $record_id, $old_values, $new_values, $description, $table_name);

// DELETE
ActivityLogger::logDelete($module, $page, $record_id, $old_values, $description, $table_name);

// LOGIN/LOGOUT
ActivityLogger::logLogin($user_id, $user_name);
ActivityLogger::logLogout();

// APPROVALS
ActivityLogger::logApproval($module, $page, $record_id, 'approved'|'rejected', $description, $table_name);

// FILE OPERATIONS
ActivityLogger::logUpload($module, $page, $file_name, $file_size, $description);
ActivityLogger::logDownload($module, $page, $file_name, $description);

// DATA OPERATIONS
ActivityLogger::logExport($module, $page, $record_count, $description);
ActivityLogger::logImport($module, $page, $record_count, $description);

// FORMS
ActivityLogger::logSubmit($module, $page, $form_name, $description);
ActivityLogger::logView($module, $page, $record_id, $description);
```

---

## Module Names (Use Consistently)

```
'Employee'          - Employee records
'Customer'          - Customer records
'Vehicle'           - Cars/vehicles
'Machine'           - Machines/equipment
'Location'          - Locations/offices
'Vacation'          - Vacation requests
'Loan'              - Loan requests
'User'              - System users (different from employees)
'Asset'             - General assets
'Document'          - Document uploads
'Authentication'    - Login/logout (already integrated)
```

---

## View Your Logs

**Admin Page:** view_activity_logs.php

**Features:**
- See all logs in real-time
- Filter by user, module, action type, date
- Click "View Details" to see before/after values for updates
- Export logs if needed

---

## Common Mistakes ❌

### ❌ Mistake 1: Forgetting to fetch old values
```php
// WRONG - no old values logged
ActivityLogger::logUpdate(..., [], $_POST, ...);

// RIGHT - old values captured
$old = fetch_from_db(...);
ActivityLogger::logUpdate(..., $old, $_POST, ...);
```

### ❌ Mistake 2: Logging in wrong place
```php
// WRONG - BEFORE checking success
mysqli_query($conDB, $sql);
ActivityLogger::logCreate(...);

// RIGHT - AFTER confirming success
if (mysqli_query($conDB, $sql)) {
    ActivityLogger::logCreate(...);
}
```

### ❌ Mistake 3: Wrong module name
```php
// WRONG - not consistent with other pages
ActivityLogger::logCreate('emp', ...);  // lowercase
ActivityLogger::logCreate('Employees', ...);  // plural

// RIGHT - consistent capitalization and singular
ActivityLogger::logCreate('Employee', ...);  // Singular, title case
```

### ❌ Mistake 4: Missing table_name
```php
// WRONG
ActivityLogger::logCreate('Customer', 'add_customer.php', $id, $data, "Added customer", null);

// RIGHT
ActivityLogger::logCreate('Customer', 'add_customer.php', $id, $data, "Added customer", 'customers');
```

---

## File Location Reminder

**The class lives here:**
```
includes/init.php
```

**Already included in:**
```
includes/session_check.php  → Always included on pages with session
dashboard.php
all_*.php pages
... and 100+ other files
```

**You DON'T need to:**
- `require_once` anything
- Create new instances with `new ActivityLogger()`
- Pass database connections

**You CAN just use:**
```php
ActivityLogger::logCreate(...)  // Works on any page with session_check.php
```

---

## Real Example from Existing Code

**File: edit_employee.php (Lines 49-102)**

```php
// Step 1: Fetch old employee data BEFORE making changes
$old_stmt = $pdo->prepare("SELECT * FROM employees WHERE emp_id = :emp_id");
$old_stmt->execute([':emp_id' => $employee_id]);
$old_employee = $old_stmt->fetch(PDO::FETCH_ASSOC);
$old_values = [];

// Step 2: Do the UPDATE (loop through form fields)
foreach ($formData as $field => $value) {
    // ... clean value ...
    $params[":$field"] = $value;
    // Collect what changed
    if ($old_employee && isset($old_employee[$field])) {
        $old_values[$field] = $old_employee[$field];
    }
}

// Step 3: Execute UPDATE query
$sql = "UPDATE `employees` SET ... WHERE `emp_id` = :emp_id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Step 4: Log the change
if ($stmt->rowCount() > 0) {
    ActivityLogger::logUpdate(
        'Employee',
        'edit_employee.php',
        $employee_id,
        $old_values,      // ← What it was
        $new_values,      // ← What it is now
        "Updated employee: " . $old_employee['name'],
        'employees'
    );
}
```

---

## Testing Your Integration

1. Open `view_activity_logs.php` in browser (admin account)
2. Perform the action you just logged (e.g., add customer)
3. Go back to `view_activity_logs.php`
4. Refresh page
5. Should see new entry immediately
6. Click "View Details" to verify all data captured

**If not showing:**
- Check that includes/init.php has ActivityLogger class
- Check that page includes session_check.php
- Look at browser console for JavaScript errors
- Check PHP error logs for exceptions

---

## Performance Note

Logging adds **~5ms** per operation. Not noticeable to users.

For bulk operations (100+ records), consider:
- Batch logging (one log entry for "Imported 150 records")
- Use `logImport()` instead of logging each record

---

## Need Help?

- **All methods:** ACTIVITY_LOGGING_GUIDE.md
- **Implementation patterns:** ACTIVITY_LOGGING_IMPLEMENTATION_GUIDE.md  
- **Code examples:** ACTIVITY_LOGGING_TEMPLATES.php
- **Full checklist:** LOGGING_ROLLOUT_CHECKLIST.md
- **Integration status:** INTEGRATION_PROGRESS_REPORT.md

---

## Integrated Pages (Currently)

✅ **Done:**
- login_verification.php (LOGIN)
- logout.php (LOGOUT)
- new_comp_employee.php (CREATE employee)
- new_mnpow_employee.php (CREATE employee)
- edit_employee.php (UPDATE employee)

⏳ **Next up:**
- Customer pages
- Vehicle pages
- Vacation workflows
- User management
- AJAX operations

---

**Last Updated:** [Current Date]

**Print this page. Tape to desk. Refer while coding.**
