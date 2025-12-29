# Encashment Calculation Critical Bug Fix

## Problem Description
The encashment (cash payment for vacation days) calculation was using a hardcoded divisor of **30 days** for all employees, regardless of their employment contract. This caused incorrect daily salary rates for employees with different vacation entitlements.

### Example Issue:
- **Employee ID 5127**: Has 22.73 days balance with a **21-day/year** contract
- **Old Calculation (WRONG)**: Daily rate = 7000 ÷ 30 = 233.33 SAR/day
- **New Calculation (CORRECT)**: Daily rate = 7000 ÷ 21 = 333.33 SAR/day

The difference is **40% higher** for employees on 21-day contracts!

## Root Cause
The bug was located in [ajaxEmployee.php](includes/ajaxFile/ajaxEmployee.php#L1933) in the `calculate_encash_salary` AJAX handler:

```php
// BUGGY CODE (Line 1933)
$daily_rate = $total_monthly_salary / 30;  // Hardcoded 30 - WRONG!
```

This didn't consider that employees have different vacation entitlements:
- **21 days/year** (Contract ID: 4)
- **30 days/year** (Contract ID: 6)
- **42 days/2 years** (Contract ID: 5)
- **30 days/2 years** (Contract ID: 3)
- **60 days/2 years** (Contract ID: 7)
- **15 days/2 years** (Contract ID: 3)

## Solution Implemented
Modified [ajaxEmployee.php](includes/ajaxFile/ajaxEmployee.php) to:

1. **Fetch the employee's contract vacation days** from the `contract_period` table by joining:
   - `employees` table (to get `vac_period` field)
   - `contract_period` table (to get actual `vac_period` days)

2. **Use the correct contract days** instead of hardcoded 30:
```php
// FIXED CODE
$contract_stmt = mysqli_query($conDB, "SELECT e.vac_period, cp.vac_period AS contract_days, cp.period 
                                       FROM employees e 
                                       JOIN contract_period cp ON e.vac_period = cp.id 
                                       WHERE e.emp_id = {$empid} LIMIT 1");

$contract_days = 30; // Default fallback
if ($contract_stmt && mysqli_num_rows($contract_stmt) > 0) {
    $contract_row = mysqli_fetch_assoc($contract_stmt);
    mysqli_free_result($contract_stmt);
    $contract_days = (float)($contract_row['contract_days'] ?? 30);
}

// Calculate daily rate using CONTRACT days, not hardcoded 30
$daily_rate = $total_monthly_salary / $contract_days;
$encash_amount = $daily_rate * $days;
```

## Impact
- **Frontend**: No changes needed - JavaScript already calls the backend to calculate the encashment amount
- **Backend**: The AJAX handler now returns the correct encashment salary based on actual contract days
- **Database**: The `contract_period` table contains the correct vacation day mapping

## Verification Example
For Employee 5127 (21-day contract, 7000 SAR salary):
- **Requested encashment**: 7 days
- **Old calculation (WRONG)**: 7 × (7000 ÷ 30) = 7 × 233.33 = **1,633.33 SAR**
- **New calculation (CORRECT)**: 7 × (7000 ÷ 21) = 7 × 333.33 = **2,333.33 SAR**

## Files Modified
- [includes/ajaxFile/ajaxEmployee.php](includes/ajaxFile/ajaxEmployee.php) - Lines 1905-1939

## Testing Recommendations
1. Test encashment requests for employees with different contract periods:
   - 21-day contracts
   - 30-day contracts
   - 42-day (2-year) contracts
2. Verify the displayed encashment amount is correct
3. Check vacation report details to confirm GOSI deductions are calculated on the correct base amount
4. Ensure existing encashment records are not affected (this only applies to new encashment calculations)

## Related Files
- [assets/js/employee_profile.js](assets/js/employee_profile.js) - Frontend calls the backend to calculate
- [vacation_report_details.php](vacation_report_details.php) - Displays encashment details
- [includes/vacation_calculator.php](includes/vacation_calculator.php) - Uses correct vacation days for balance calculations
