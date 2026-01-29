# Payroll Regeneration Benefits & Deductions Preservation Fix

## Problem Summary
When users modified benefits/deductions after initial payroll generation and then regenerated payroll, the manually modified benefits and deductions were being lost. The system was overwriting custom entries with auto-calculated values.

## Root Causes Identified

### 1. **Automatic Benefit/Deduction Regeneration on Every Payroll Process**
In `process_payroll.php`, functions were always executing regardless of whether it was an initial generation or a regeneration:
- `addVacationWorkingDaysSalary()` - Auto-adds vacation salary benefits
- `addOrUpdateLeaveDeduction()` - Auto-adds leave deductions
- `addOrUpdateLoanDeduction()` - Auto-adds loan installments

### 2. **Aggressive Benefit Deletion During Regeneration**
The `addVacationWorkingDaysSalary()` function was deleting ALL vacation-related benefits before recreating them, without preserving manually modified entries.

## Solutions Implemented

### Fix #1: Skip Auto-Calculations on Payroll Regeneration
**File**: `d:\xampp\htdocs\almutlak\system\includes\api\process_payroll.php`

Added a check to detect if payroll already exists for the employee/month:

```php
// --- CHECK: Skip automatic calculations if payroll already generated ---
// If payroll exists and has manually added benefits/deductions, preserve them
$stmtCheckPayroll = $pdo->prepare("SELECT id FROM payrolls WHERE emp_id = :emp_id AND month_year = :month_year");
$stmtCheckPayroll->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
$existingPayroll = $stmtCheckPayroll->fetch(PDO::FETCH_ASSOC);

$isRegeneration = !empty($existingPayroll);

if (!$isRegeneration) {
    // Only add automatic benefits/deductions on initial payroll generation
    addOrUpdateLeaveDeduction($pdo, $empId, $monthYear, $totalGrossSalary);
    addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary);
    addOrUpdateLoanDeduction($pdo, $empId, $monthYear);
}
```

**Impact**: Auto-calculated benefits/deductions are only added once during initial generation. On regeneration, they are preserved if they exist.

---

### Fix #2: Preserve Manually Modified Vacation Benefits
**File**: `d:\xampp\htdocs\almutlak\system\includes\api\process_payroll.php`

Modified `addVacationWorkingDaysSalary()` to distinguish between auto-generated and manually added benefits:

#### When No Vacation Found
```php
if (!$vacation) {
    // Only remove auto-generated vacation benefits (which will have specific ID patterns)
    // Preserve any vacation benefits that were manually added/modified
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits 
        WHERE emp_id = :emp_id 
        AND month = :month_year 
        AND (benefit LIKE 'Working Days Salary for Vacation%' OR benefit LIKE 'Vacation Salary Benefit%')
        AND id IN (
            SELECT id FROM (
                SELECT pb.id FROM payroll_benefits pb
                LEFT JOIN emp_vacation ev ON pb.benefit LIKE CONCAT('%', CONCAT('(ID: ', ev.id, ')'))
                WHERE pb.emp_id = :emp_id 
                AND pb.month = :month_year 
                AND (pb.benefit LIKE 'Working Days Salary for Vacation%' OR pb.benefit LIKE 'Vacation Salary Benefit%')
                AND ev.id IS NULL
            ) AS subquery
        )");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    return;
}
```

#### When Vacation Exists - Clean Old Auto-Generated Benefits Only
```php
// MODIFIED: Only remove old auto-generated vacation benefits (not manually modified ones)
$stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits 
    WHERE emp_id = :emp_id 
    AND month = :month_year 
    AND (benefit LIKE 'Working Days Salary for Vacation%' OR benefit LIKE 'Vacation Salary Benefit%')
    AND id IN (
        SELECT id FROM (
            SELECT pb.id FROM payroll_benefits pb
            LEFT JOIN emp_vacation ev ON pb.benefit LIKE CONCAT('%', CONCAT('(ID: ', ev.id, ')'))
            WHERE pb.emp_id = :emp_id2
            AND pb.month = :month_year2
            AND (pb.benefit LIKE 'Working Days Salary for Vacation%' OR pb.benefit LIKE 'Vacation Salary Benefit%')
            AND ev.id IS NULL
        ) AS subquery
    )");
```

**How It Works**:
- Auto-generated benefits have the pattern: `Working Days Salary for Vacation (ID: X)` or `Vacation Salary Benefit (ID: X)`
- The query identifies benefits that match this pattern but have no corresponding vacation record (orphaned/outdated auto-generated benefits)
- Manually added benefits (without this specific pattern) are preserved
- This ensures manually modified amounts are never lost

---

## How to Verify the Fix

### Scenario 1: Add Manual Benefits Then Regenerate
1. Generate payroll for an employee
2. Open employee payroll details
3. Add a new benefit (e.g., "Bonus", 500 SAR)
4. Click "Save Changes"
5. Click "Regenerate Payroll"
6. Re-open the employee's payroll
7. ✅ The manual benefit of 500 SAR should still be there

### Scenario 2: Modify Existing Deductions Then Regenerate
1. Generate payroll for an employee
2. Open employee payroll details
3. Modify a deduction amount (e.g., GOSI from auto-calculated to custom)
4. Click "Save Changes"
5. Click "Regenerate Payroll"
6. Re-open the employee's payroll
7. ✅ The modified deduction should retain the custom amount

### Scenario 3: Auto-Add Vacation Benefits on First Generation
1. Create an approved vacation for an employee in the current month
2. Generate payroll for that employee
3. ✅ Vacation salary benefits should be auto-added
4. Open and modify them to custom amounts
5. Click "Save Changes"
6. Regenerate payroll
7. ✅ Custom vacation benefit amounts should be preserved

---

## Technical Details

### Database Impact
- **payroll_benefits** table: Manually added/modified benefits are preserved
- **payroll_deductions** table: Manually added/modified deductions are preserved
- **payrolls** table: Total amounts are recalculated correctly on regeneration

### Performance
- Minimal impact: Only one additional query to check if payroll exists
- Regeneration is slightly faster (skips auto-calculation functions when not needed)

### Backward Compatibility
- Existing payroll records are unaffected
- No schema changes required
- Safe to apply to existing systems with payroll history

---

## Files Modified
1. **d:\xampp\htdocs\almutlak\system\includes\api\process_payroll.php**
   - Added payroll existence check (Lines 156-164)
   - Modified vacation benefit deletion logic (Lines 579-596)
   - Modified old benefit cleanup logic (Lines 621-631)

---

## Testing Checklist
- [ ] Generate payroll without manual modifications - works as before
- [ ] Regenerate payroll without changes - preserves all benefits/deductions
- [ ] Add manual benefit, save, then regenerate - manual benefit preserved
- [ ] Modify deduction amount, save, then regenerate - modified amount preserved
- [ ] Vacation benefits auto-added on first generation
- [ ] Vacation benefits custom amounts preserved on regeneration
- [ ] GOSI deductions auto-calculated and preserved
- [ ] Loan deductions auto-added and preserved
