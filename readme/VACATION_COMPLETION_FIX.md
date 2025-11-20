# Vacation Completion Issue - Fix Summary

## Problem
When an employee completed their vacation and tried to apply for a new vacation, they received the error:
```
You are currently on an approved vacation (VAC-20251117212053-5152-64f1) from 2025-11-13 to 2025-11-25. 
You cannot apply for another vacation while your current vacation is active.
```

This prevented employees from applying for new vacations even after completing their previous one.

## Root Cause
1. **Missing 'completed' status**: The `emp_vacation` table's `current_status` enum only had values: `'draft'`, `'pending_approval'`, `'approved'`, `'rejected'` - missing a `'completed'` status.

2. **Incomplete vacation return workflow**: When an employee returned from vacation (via the "Confirm Employee Return" function), the system:
   - Updated the employee's `fly` status to 0
   - Updated vacation balance calculations
   - **BUT did NOT mark the vacation as 'completed'**

3. **Active vacation check logic**: The code checking for active vacations looked for:
   ```sql
   WHERE emp_id = ? 
     AND current_status = 'approved' 
     AND start_date <= TODAY 
     AND return_date >= TODAY
   ```
   Since returned vacations stayed as 'approved', they were still considered "active" even after completion.

## Solution Implemented

### 1. Database Schema Update
**File**: `sql/add_completed_status_to_vacation.sql`

Added a new 'completed' status to the enum:
```sql
ALTER TABLE `emp_vacation` 
MODIFY `current_status` enum('draft','pending_approval','approved','rejected','completed') 
NOT NULL DEFAULT 'draft';
```

### 2. Updated Vacation Check Logic
**File**: `includes/ajaxFile/ajaxVacation.php` (lines ~320)

Updated the SQL query to explicitly exclude completed vacations:
```php
$sql_active = "SELECT request_inv_no, start_date, return_date FROM emp_vacation 
               WHERE emp_id = ? 
                 AND current_status = 'approved' 
                 AND current_status != 'completed'  // <-- NEW: Explicitly exclude completed
                 AND start_date <= ? 
                 AND return_date >= ? 
               LIMIT 1";
```

### 3. Enhanced Return Vacation Endpoint
**File**: `includes/ajaxFile/ajaxVacation.php` (lines ~1158)

Updated the `returnVacation` AJAX handler to mark vacation as completed:
```php
// Mark vacation as completed so employee can apply for new vacation
$sql_complete_vac = "UPDATE `emp_vacation` 
                     SET `current_status` = 'completed', `arrived_date` = ? 
                     WHERE `id` = ?";
$stmt_complete_vac = mysqli_prepare($conDB, $sql_complete_vac);
if (!$stmt_complete_vac) {
    throw new Exception("Failed to mark vacation as completed: " . mysqli_error($conDB));
}

mysqli_stmt_bind_param($stmt_complete_vac, "si", $actual_return_date, $vacation_id);
if (!mysqli_stmt_execute($stmt_complete_vac)) {
    throw new Exception("Failed to update vacation status: " . mysqli_stmt_error($stmt_complete_vac));
}
mysqli_stmt_close($stmt_complete_vac);
```

## How It Works Now

1. **Employee applies for vacation** → System checks for active (approved & not completed) vacations
2. **Manager approves vacation** → `current_status` = 'approved'
3. **Employee returns from vacation** → Via "Confirm Employee Return" button:
   - Sets `fly` status to 0
   - Updates vacation balance
   - **Sets `current_status` to 'completed'** ✅
   - Sets `arrived_date` to the actual return date
4. **Employee applies for new vacation** → System sees completed vacation and allows new application ✅

## Testing
To verify the fix:
1. Employee applies for vacation (approved with status='approved')
2. Go to employee view and click "Confirm Employee Return"
3. Select the actual return date and confirm
4. Check database: `emp_vacation.current_status` should now be 'completed'
5. Employee can now apply for a new vacation without error

## Files Changed
- `sql/add_completed_status_to_vacation.sql` (NEW - SQL migration)
- `includes/ajaxFile/ajaxVacation.php` (UPDATED - 2 sections)

## Impact
- ✅ Employees can now apply for new vacations after completing their previous one
- ✅ Database properly tracks vacation lifecycle
- ✅ Backward compatible - existing approved vacations remain unaffected
- ✅ No breaking changes to existing functionality
