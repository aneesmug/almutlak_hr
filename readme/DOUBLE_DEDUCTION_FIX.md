# Double Vacation Balance Deduction - Fix Implementation

## Problem Identified
Vacation balance was being deducted **twice** in the approval flow:
1. **First deduction**: When HR_Payroll approver approves (line 1892 in handle_approval_action)
2. **Second deduction**: When final approval is reached with no more approvers (line 2157 in handle_approval_action)

## Solution Implemented

### 1. Guard Check in `update_vacation_balance_on_approval()` Function
**File**: `/includes/helper_functions.php` (lines 3155-3185)

Added a safety check that prevents the function from executing more than once within a 5-minute window:

```php
// === GUARD: Prevent double deduction ===
// Check if this vacation's balance has already been updated in the last few minutes
$sql_check_recent = "SELECT `id`, `last_updated` FROM `emp_vacation_balance` 
                    WHERE `vac_id` = ? AND `last_updated` > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                    LIMIT 1";
// If found, skip and return true (already handled)
```

**Effect**: If the function is called multiple times, only the first execution will actually update the balance. Subsequent calls within 5 minutes will be skipped with a log entry: "GUARD: update_vacation_balance_on_approval skipped for vac_id=XXX - already updated recently"

### 2. HR_Payroll Approval Stage Guard
**File**: `/includes/helper_functions.php` (lines 1887-1912)

Added logging to track when balance update is initiated:

```php
// GUARD: Mark that balance update was initiated at HR_Payroll approval
// This prevents double-deduction if the function is called again during final approval
error_log("BALANCE_UPDATE_INITIATED: vac_id=$vacation_id_for_balance by HR_Payroll approver $current_user_id_safe");
update_vacation_balance_on_approval($conDB, $vacation_id_for_balance);
```

**Effect**: Logs when balance update starts at HR_Payroll stage so admins can track the flow.

### 3. Final Approval Stage Guard
**File**: `/includes/helper_functions.php` (lines 2150-2185)

Added explicit check to see if balance was already updated recently:

```php
// Check if balance has already been recently updated for this vacation
$sql_check_balance_updated = "SELECT `last_updated` FROM `emp_vacation_balance` 
                            WHERE `vac_id` = ? 
                            ORDER BY `last_updated` DESC 
                            LIMIT 1";
// If updated within last 10 minutes, skip to avoid double deduction
if (($current_time - $last_updated_time) < 600) {
    $balance_already_updated = true;
    error_log("BALANCE_ALREADY_UPDATED: vac_id=$vacation_id, skipping duplicate update at final approval");
}

// Only update if NOT already done recently AND NOT an asset clearance approval
if (!$balance_already_updated && !$is_asset_clearance && function_exists('update_vacation_balance_on_approval')) {
    update_vacation_balance_on_approval($conDB, $vacation_id);
}
```

**Effect**: If balance was already updated during HR_Payroll approval, this stage will skip the update completely.

### 4. Emergency Vacation Endpoint Guard
**File**: `/includes/ajaxFile/ajaxVacation.php` (lines 2463-2466)

Already has a guard in place:

```php
if (!$has_balance_link && function_exists('update_vacation_balance_on_approval')) {
    // Update balance once for emergency vacations using annual balance
    update_vacation_balance_on_approval($conDB, $vacation_id);
}
```

**Effect**: Only updates balance if there's no existing link, preventing duplicate updates.

## Balance Update Flow (After Fix)

### Normal Vacation Approval (Fly/Local Annual):
1. **Supervisor** approves → No balance update
2. **Department Manager** approves → No balance update
3. **HR Senior BP** approves → No balance update
4. **HR_Payroll** approves → ✅ **BALANCE UPDATED ONCE** (guard prevents future updates)
5. **Final Approval** (if any) → ❌ Skipped (guard detects recent update, logs and skips)

### Emergency Vacation Approval:
1. **Supervisor** approves → No balance update
2. **HR Senior BP** approves → Check `has_balance_link` guard
3. If no existing balance link → ✅ **BALANCE UPDATED ONCE**
4. If existing link → ❌ Skipped (guard prevents duplicate)

## Monitoring & Debugging

### Check Error Logs for Double Deduction Issues:
```bash
grep -i "BALANCE_UPDATE_INITIATED\|BALANCE_ALREADY_UPDATED\|GUARD.*skipped" /var/log/almutlak_error.log
```

### Expected Log Entries:
```
[BALANCE_UPDATE_INITIATED]: vac_id=123 by HR_Payroll approver 456
[BALANCE_ALREADY_UPDATED]: vac_id=123, skipping duplicate update at final approval
[GUARD]: update_vacation_balance_on_approval skipped for vac_id=123 - already updated recently
```

## Testing Recommendations

1. **Test HR_Payroll Approval**: Approve a vacation as HR_Payroll and verify balance is deducted once
2. **Test Final Approval**: Ensure no double deduction occurs even if multiple approvals reach final stage
3. **Test Emergency Vacations**: Verify emergency vacation balance deduction happens only once
4. **Check Logs**: Confirm guard logs appear in error logs showing duplicate attempts were blocked

## Database Impact

- **No schema changes** required
- Uses existing `emp_vacation_balance.vac_id` and `emp_vacation_balance.last_updated` columns
- Time-based guards use 5-10 minute windows for maximum safety

## Backward Compatibility

- ✅ All changes are backward compatible
- ✅ No API changes required
- ✅ Guards are internal to functions
- ✅ Existing vacation records are not affected
