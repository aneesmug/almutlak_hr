# Emergency Vacation Balance Deduction Fix

## Issue
When employees applied for "Emergency Vacation" type, the system was:
1. Checking their vacation balance (blocking requests if insufficient)
2. Deducting balance from `emp_vacation_balance.available_balance` upon approval

This was incorrect because emergency vacation is **unpaid leave** and should not affect vacation balance.

## Solution Implemented

### 1. Application Phase (ajaxVacation.php - Line ~778)
**What Changed**: Enhanced emergency vacation detection to include:
```php
$is_emergency_vacation = ($vac_type === 'Fly' && $fly_type === 'emergency') 
                      || ($vac_type === 'Local Vacation' && $fly_type === 'emergency')
                      || ($vac_type === 'Emergency Vacation')  // NEW: Direct selection
                      || ($vac_type === 'Emergency vacation')
                      || (strpos($remarks_lower, 'emergency') !== false && $vac_type === 'Local Vacation');
```

**Effect**: When submitting emergency vacation, the system skips the balance check (line ~884) and allows the request to proceed regardless of available balance.

### 2. Database Storage Phase (ajaxVacation.php - Line ~970)
**What Changed**: Set `is_deductible = 0` for emergency vacations:
```php
if ($is_emergency_vacation || (($vac_type === 'Fly' || $vac_type === 'Local Vacation') && $fly_type === 'annual')) {
    $is_deductible = 0; // Not deductible: does NOT reduce vacation balance
}
```

**Effect**: The `is_deductible` flag in `emp_vacation` table is marked as 0, signaling that this leave should not deduct from balance.

### 3. Approval Phase (helper_functions.php - Line ~3215)
**What Changed**: Modified `update_vacation_balance_on_approval()` function to NOT deduct balance for emergency vacations:

```php
$is_emergency_vacation = ($vac_details['vac_type'] == 'Fly' && $vac_details['fly_type'] == 'emergency') 
                      || ($vac_details['vac_type'] == 'Local Vacation' && $vac_details['fly_type'] == 'emergency')
                      || ($vac_type_lower === 'emergency vacation')
                      || (strpos($remarks, 'emergency') !== false && $vac_details['vac_type'] == 'Local Vacation');

// Emergency vacation is UNPAID and does NOT deduct from balance
if ($is_emergency_vacation) {
    $is_balance_deductible = false;  // ← KEY CHANGE
}
```

**Effect**: When HR_Payroll approves, emergency vacation is processed without deducting from `emp_vacation_balance.available_balance`.

## Workflow Now

### Local Vacation with Type = Emergency
**User fills form:**
- Vacation Type: Local Vacation
- Fly Type: Emergency
- Remarks: Emergency vacation
- Days: 5

**Application:**
- ✅ NO balance check
- ✅ Request forwarded to approvers
- ✅ `is_deductible = 0` (stored in DB)

**HR Approves:**
- ✅ Request moves to next level
- ✅ Still NO balance deduction

**HR_Payroll Approves:**
- ✅ Request marked as "approved"
- ✅ **NO balance deduction** (emergency leave is unpaid)
- ✅ Employee's `available_balance` remains unchanged

## Testing Checklist

- [ ] Employee with 0 balance can apply for Emergency Vacation
- [ ] Remarks "Emergency vacation" correctly identified
- [ ] `is_deductible = 0` in emp_vacation table
- [ ] HR_Payroll approval doesn't deduct balance
- [ ] Regular vacation (Local Vacation without emergency) still deducts balance
- [ ] Annual vacation still deducts balance
- [ ] Encashment still deducts balance

## Files Modified

1. **d:\xampp\htdocs\almutlak\system\includes\ajaxFile\ajaxVacation.php**
   - Line ~778: Enhanced `$is_emergency_vacation` detection
   - Line ~970: Set `is_deductible = 0` for emergency vacations

2. **d:\xampp\htdocs\almutlak\system\includes\helper_functions.php**
   - Line ~3215: Changed balance deduction logic in `update_vacation_balance_on_approval()`
   - Added logic to skip balance deduction for emergency vacations
