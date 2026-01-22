# FLY STATUS FIX - SICK LEAVE AND EXCUSE LEAVE

## Issue Description
When employees apply for sick leave or excuse leave, the system was incorrectly setting `employees.fly = 1`, which marks them as "away/on vacation". This is incorrect because:

1. Sick leave doesn't involve travel - employee is sick at home
2. Excuse leave types (Exam, Hajj, Maternity, etc.) are short-term local leaves
3. These leave types don't have a rejoin process to reset fly status back to 0
4. Setting fly=1 incorrectly marks employee as unavailable when they're not actually traveling

## Root Cause
The code was only checking for "Encashed" vacation type before updating fly status, but it should also exclude all excuse leave types that don't require travel/rejoin tracking.

## Vacation Types
### Regular Vacations (Require Rejoin - Should Set fly=1)
- **Fly + Annual**: Employee flies to another country for annual vacation
- **Fly + Emergency**: Employee flies for emergency (unpaid)
- **Local Vacation**: Employee takes local annual vacation (within Saudi Arabia)

### Excuse Leave Types (No Rejoin - Should NOT Set fly=1)
- **Sick Leave**: Employee is sick at home
- **Exam Leave**: Employee taking exam
- **Hajj Leave**: Religious pilgrimage
- **Maternity Leave**: Maternity time off
- **Marriage Leave**: Wedding leave
- **Newborn Leave**: New parent leave
- **Death Leave**: Bereavement leave
- **Business Trip**: Work-related travel

### Special Types
- **Encashed**: Cash payment instead of vacation (no physical leave)

## Solution Applied

### Files Modified

#### 1. `includes/ajaxFile/ajaxVacation.php` (Line ~2760)
**Location**: Payment updates section (updateVacationPayments)

**Before:**
```php
// Set employees.fly = 1 except for Encashment type
$vac_type_lower = strtolower($vac_data['vac_type'] ?? '');
if ($vac_type_lower !== 'encashed' && !empty($vac_data['emp_id'])) {
    $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
    // ...
}
```

**After:**
```php
// Set employees.fly = 1 except for Encashment type and Excuse Leave types
// Excuse leave types (Sick Leave, Exam Leave, etc.) don't require rejoin tracking
$vac_type_lower = strtolower($vac_data['vac_type'] ?? '');
// Define excuse leave types that should NOT update fly status
$excuse_leave_types = ['sick leave', 'exam leave', 'hajj leave', 'maternity leave', 'marriage leave', 'newborn leave', 'death leave', 'business trip'];

if ($vac_type_lower !== 'encashed' && !in_array($vac_type_lower, $excuse_leave_types) && !empty($vac_data['emp_id'])) {
    $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
    // ...
}
```

#### 2. `includes/ajaxFile/ajaxVacation.php` (Line ~3000)
**Location**: Payroll adjustments section (updateVacationAdjustments)

**Before:**
```php
// If completed in any branch, set employees.fly = 1 unless Encashment
if ($did_complete && !empty($vacation_row['emp_id'])) {
    $vac_type_lower = strtolower($vac_data['vac_type'] ?? '');
    if ($vac_type_lower !== 'encashed') {
        $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
        // ...
    }
}
```

**After:**
```php
// If completed in any branch, set employees.fly = 1 unless Encashment or Excuse Leave
// Excuse leave types (Sick Leave, Exam Leave, etc.) don't require rejoin tracking
if ($did_complete && !empty($vacation_row['emp_id'])) {
    $vac_type_lower = strtolower($vac_data['vac_type'] ?? '');
    // Define excuse leave types that should NOT update fly status
    $excuse_leave_types = ['sick leave', 'exam leave', 'hajj leave', 'maternity leave', 'marriage leave', 'newborn leave', 'death leave', 'business trip'];
    
    if ($vac_type_lower !== 'encashed' && !in_array($vac_type_lower, $excuse_leave_types)) {
        $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
        // ...
    }
}
```

#### 3. `includes/helper_functions.php` (Line ~2220)
**Location**: Approval chain handler (handleApprovalByApprover)

**Before:**
```php
// --- [UPDATED] Fly Status Management ---
// Set fly=1 at final HR_Payroll approval, except Encashment
if ($final_status === 'completed' && !empty($vacation_emp_id)) {
    $vac_type_lower = strtolower($vacation_type ?? '');
    if ($vac_type_lower !== 'encashed') {
        $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
        // ...
    }
}
```

**After:**
```php
// --- [UPDATED] Fly Status Management ---
// Set fly=1 at final HR_Payroll approval, except Encashment and Excuse Leave types
// Excuse leave types (Sick Leave, Exam Leave, etc.) don't require rejoin tracking
if ($final_status === 'completed' && !empty($vacation_emp_id)) {
    $vac_type_lower = strtolower($vacation_type ?? '');
    // Define excuse leave types that should NOT update fly status
    $excuse_leave_types = ['sick leave', 'exam leave', 'hajj leave', 'maternity leave', 'marriage leave', 'newborn leave', 'death leave', 'business trip'];
    
    if ($vac_type_lower !== 'encashed' && !in_array($vac_type_lower, $excuse_leave_types)) {
        $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
        // ...
    }
}
```

### Files Verified (No Changes Needed)

#### 4. `includes/session_check.php` (Line 340)
This file has automatic fly status updates, but it already correctly filters for:
- Only `VAC-%` prefixed requests (excludes `LV-%` leave requests)
- Excludes "Encashed" vacation types
- Only processes approved/completed vacation requests

Since excuse leave requests use the `LV-%` prefix (not `VAC-%`), they are already excluded from this automatic fly status update. **No changes needed.**

## Testing Scenarios

### Test Case 1: Sick Leave Application ✅
1. Employee applies for sick leave
2. Request goes through approval chain
3. HR Payroll approves sick leave
4. **VERIFY**: `employees.fly` should remain `0` (not updated to 1)

### Test Case 2: Annual Fly Vacation ✅
1. Employee applies for annual vacation (Fly type)
2. Request goes through approval chain
3. HR Payroll approves vacation
4. **VERIFY**: `employees.fly` should be set to `1` (employee is away)

### Test Case 3: Emergency Vacation ✅
1. Employee applies for emergency vacation
2. Request goes through approval chain
3. HR Payroll approves vacation
4. **VERIFY**: `employees.fly` should be set to `1` (employee is away)

### Test Case 4: Encashed Vacation ✅
1. Employee applies for encashed vacation
2. Request goes through approval chain
3. HR Payroll approves encashment
4. **VERIFY**: `employees.fly` should remain `0` (no physical leave)

### Test Case 5: Business Trip ✅
1. Employee applies for business trip
2. Request goes through approval chain
3. HR Payroll approves business trip
4. **VERIFY**: `employees.fly` should remain `0` (work-related, not vacation)

### Test Case 6: Maternity Leave ✅
1. Employee applies for maternity leave
2. Request goes through approval chain
3. HR Payroll approves maternity leave
4. **VERIFY**: `employees.fly` should remain `0` (local leave, no travel)

## Expected Behavior After Fix

### Fly Status Will Be Set to 1 For:
- Annual Fly Vacation (employee traveling abroad)
- Emergency Fly Vacation (emergency travel)
- Local Vacation (employee on vacation but local)

### Fly Status Will Remain 0 For:
- Sick Leave
- Exam Leave
- Hajj Leave
- Maternity Leave
- Marriage Leave
- Newborn Leave
- Death Leave
- Business Trip
- Encashed Vacation

## Impact
This fix prevents incorrect employee status tracking. Employees on sick leave or excuse leave will no longer be marked as "away/on vacation" in the system, which ensures:
- Accurate employee availability tracking
- Correct status display in employee profiles
- Proper workflow for rejoin process (only for actual vacations)
- No orphaned fly=1 status for leave types without rejoin

## Related Documentation
- VACATION_BALANCE_AUDIT_REPORT.md - Previous vacation system audit
- Database schema: `emp_vacation` table stores all leave/vacation requests
- Request prefixes: `VAC-%` for vacations, `LV-%` for excuse leave

---

**Fix Applied**: January 2024
**Modified By**: AI Agent (GitHub Copilot)
**Tested**: Pending user verification
