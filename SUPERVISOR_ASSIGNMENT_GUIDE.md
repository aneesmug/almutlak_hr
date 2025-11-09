# Supervisor Assignment System - Implementation Guide

## Overview
This system allows you to assign a **direct supervisor/manager** to each employee. When employees apply for leave or other requests, the system will route approvals to their assigned supervisor first, then to HR Senior BP.

---

## Step 1: Update Database

Run the SQL file to add the `supervisor_id` field to the `employees` table:

```bash
# Execute this SQL file in your database
php -r "require 'includes/db.php'; $sql = file_get_contents('add_supervisor_field.sql'); mysqli_multi_query(\$conDB, \$sql);"
```

Or manually run this SQL:

```sql
ALTER TABLE `employees` 
ADD COLUMN `supervisor_id` VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'Employee ID of direct supervisor/manager' 
AFTER `emptype`;

ALTER TABLE `employees` 
ADD INDEX `idx_supervisor` (`supervisor_id`);
```

---

## Step 2: Assign Supervisors to Employees

### Option A: Through Edit Employee Page

1. Open `edit_employee.php`
2. Find the employee you want to assign a supervisor to
3. Add a new field for selecting supervisor (see Step 3 below for UI implementation)

### Option B: Bulk Update via SQL

```sql
-- Example: Assign supervisor to specific employees
UPDATE `employees` 
SET `supervisor_id` = '3061'  -- Supervisor's Employee ID
WHERE `emp_id` IN ('5313', '5408', '4120');  -- Employee IDs

-- Example: Assign all employees in a department to their manager
UPDATE `employees` e1
SET `supervisor_id` = (
    SELECT e2.`emp_id` 
    FROM `employees` e2 
    WHERE e2.`dept` = e1.`dept` 
    AND e2.`emptype` = 'Manager' 
    AND e2.`status` = 1 
    LIMIT 1
)
WHERE e1.`emptype` != 'Manager';
```

---

## Step 3: Add Supervisor Field to Employee Forms

### A. Add to `edit_employee.php`

Find the employee type dropdown and add this after it:

```php
<!-- Existing emptype field -->
<div class="form-group col-md-2">
    <label for="emptype" class="col-form-label"><?= __("employee_type_label") ?><span class="text-danger">*</span></label>
    <select class="form-control" name="emptype" required />
        <option value=""><?= __("select_option") ?></option>
        <option value="Manager" <?= ($emprow['emptype'] == 'Manager' ? 'selected' : '') ?>><?= __("manager_option") ?></option>
        <option value="Supervisor" <?= ($emprow['emptype'] == 'Supervisor' ? 'selected' : '') ?>><?= __("supervisor_option") ?></option>
        <option value="Supporter" <?= ($emprow['emptype'] == 'Supporter' ? 'selected' : '') ?>><?= __("supporter_option") ?></option>
    </select>
</div>

<!-- NEW: Direct Supervisor/Manager Selection -->
<div class="form-group col-md-3">
    <label for="supervisor_id" class="col-form-label"><?= __("direct_supervisor_label") ?></label>
    <select class="form-control select2" name="supervisor_id" id="supervisor_id">
        <option value=""><?= __("no_supervisor") ?></option>
        <?php
        // Get list of potential supervisors (Managers and Supervisors in the same department)
        $supervisor_query = mysqli_query($conDB, "
            SELECT `emp_id`, `name`, `emptype` 
            FROM `employees` 
            WHERE `status` = 1 
            AND `emptype` IN ('Manager', 'Supervisor')
            AND `emp_id` != '{$emprow['emp_id']}'
            ORDER BY `emptype` = 'Manager' DESC, `name` ASC
        ");
        while ($supervisor = mysqli_fetch_assoc($supervisor_query)) {
            $selected = ($emprow['supervisor_id'] == $supervisor['emp_id']) ? 'selected' : '';
            echo "<option value='{$supervisor['emp_id']}' {$selected}>" . 
                 htmlspecialchars($supervisor['name']) . " (ID: {$supervisor['emp_id']} - {$supervisor['emptype']})</option>";
        }
        ?>
    </select>
    <small class="form-text text-muted">Assign a direct supervisor for this employee's leave approvals</small>
</div>
```

### B. Add to `add_new_employee.php`

Similar structure as above, but without the selected value:

```php
<div class="form-group col-md-3">
    <label for="supervisor_id" class="col-form-label"><?= __("direct_supervisor_label") ?></label>
    <select class="form-control select2" name="supervisor_id" id="supervisor_id">
        <option value=""><?= __("no_supervisor") ?></option>
        <?php
        $supervisor_query = mysqli_query($conDB, "
            SELECT `emp_id`, `name`, `emptype` 
            FROM `employees` 
            WHERE `status` = 1 
            AND `emptype` IN ('Manager', 'Supervisor')
            ORDER BY `emptype` = 'Manager' DESC, `name` ASC
        ");
        while ($supervisor = mysqli_fetch_assoc($supervisor_query)) {
            echo "<option value='{$supervisor['emp_id']}'>" . 
                 htmlspecialchars($supervisor['name']) . " (ID: {$supervisor['emp_id']} - {$supervisor['emptype']})</option>";
        }
        ?>
    </select>
</div>
```

### C. Update the Form Submission Handlers

In both `edit_employee.php` and `add_new_employee.php`, add `supervisor_id` to the allowed columns and INSERT/UPDATE statements:

```php
// In the allowed columns array
$allowedColumns = [
    'name', 'emp_id', 'iqama', /* ... other fields ... */,
    'supervisor_id'  // ADD THIS
];

// Make sure the POST data includes it
$supervisor_id = trim($_POST['supervisor_id'] ?? '');
```

---

## Step 4: Display Supervisor Information

### In `view_employee.php`

Add this to display the employee's supervisor:

```php
<!-- Add this in the employee details section -->
<?php
if (!empty($emprow['supervisor_id'])) {
    $supervisor_query = mysqli_query($conDB, "
        SELECT `name`, `emp_id`, `emptype` 
        FROM `employees` 
        WHERE `emp_id` = '{$emprow['supervisor_id']}' 
        LIMIT 1
    ");
    $supervisor = mysqli_fetch_assoc($supervisor_query);
    if ($supervisor) {
?>
    <div class="row">
        <div class="col-md-6">
            <div class="card-box">
                <h4 class="header-title"><?= __("direct_supervisor") ?></h4>
                <p class="text-muted m-b-15">
                    <strong><?= htmlspecialchars($supervisor['name']) ?></strong><br>
                    <?= __("employee_id") ?>: <?= $supervisor['emp_id'] ?><br>
                    <?= __("position") ?>: <?= $supervisor['emptype'] ?>
                </p>
            </div>
        </div>
    </div>
<?php 
    }
}
?>
```

---

## How It Works

### Leave Request Approval Flow:

1. **Employee applies for leave**
   - System checks if employee has `supervisor_id` assigned
   
2. **First Approver Selection:**
   - **If supervisor assigned:** Route to Direct Supervisor
   - **If NO supervisor:** Route to Department Manager (fallback)
   
3. **Second Approver:**
   - Always routes to **HR Senior BP** (final approval)

4. **If leave type is deductible:**
   - `is_deductible = 1` flag is set
   - Payroll system counts these days as absent
   - Deductible types: Sick Leave, Casual Leave, Unpaid Leave

---

## Example Scenarios

### Scenario 1: Employee with Direct Supervisor
```
Employee 5313 → Supervisor 3061 → HR Senior BP
```

### Scenario 2: Employee without Supervisor
```
Employee 4120 → Department Manager → HR Senior BP
```

### Scenario 3: Manager (No Supervisor)
```
Manager → Department Head → HR Senior BP
```

---

## Testing

1. **Run the database update:**
   ```bash
   mysql -u root -p almutlak_db < add_supervisor_field.sql
   ```

2. **Assign a supervisor to a test employee:**
   ```sql
   UPDATE `employees` 
   SET `supervisor_id` = '3061' 
   WHERE `emp_id` = '5313';
   ```

3. **Submit a leave request** as employee 5313

4. **Verify:**
   - Check `emp_vacation` table for new record
   - Check `approval_chain` table for approvers
   - Verify `current_approval_level = 1`
   - First approver should be supervisor (3061)
   - Second approver should be HR Senior BP

---

## Translation Keys to Add

Add these to your translation files:

```php
'direct_supervisor_label' => 'Direct Supervisor/Manager',
'no_supervisor' => 'No Direct Supervisor',
'direct_supervisor' => 'Direct Supervisor',
```

---

## Benefits

✅ **Flexible Hierarchy:** Each employee can have their own supervisor
✅ **Fallback Protection:** Falls back to department manager if no supervisor assigned
✅ **Clear Approval Path:** Employees know exactly who will review their requests
✅ **Payroll Integration:** `is_deductible` flag automatically handles salary deductions
✅ **Email Notifications:** Approvers get notified at each step (if email functions exist)

---

## Next Steps

1. Run the SQL to add the field
2. Add the supervisor dropdown to employee forms
3. Assign supervisors to your employees
4. Test with a leave request
5. Optional: Create a bulk supervisor assignment page for HR

---

**Need Help?**
- Check error logs in `includes/logs/` if requests fail
- Verify `supervisor_id` field exists: `DESCRIBE employees;`
- Check approval chain: `SELECT * FROM approval_chain WHERE inv_no LIKE 'LV-%';`
