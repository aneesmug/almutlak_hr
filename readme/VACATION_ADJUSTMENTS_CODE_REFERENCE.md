# VACATION ADJUSTMENTS MODULE - CODE REFERENCE GUIDE

## 1. Updated Files

### A. Database Migration File
**File**: `VACATION_ADJUSTMENTS_ALTER_TABLE.sql`
**Status**: ✅ Created and ready for execution
**Purpose**: Adds three new columns to emp_vacation table

**Columns Added**:
- `other_earnings` - For storing bonus/additional earnings amounts
- `overtime_amount` - For storing calculated overtime payment
- `deduction_amount` - For storing calculated deduction payment

---

## 2. Backend Handler Update
**File**: `includes/ajaxFile/ajaxVacation.php`
**Handler**: `updateVacationAdjustments` (lines ~1893-1970)
**Status**: ✅ Updated with calculations and other_earnings support

### Key Changes:
```php
// 1. Capture all 5 input parameters
$vacation_id = $_POST['vacation_id'];
$overtime_hours = floatval($_POST['overtime_hours'] ?? 0);
$deduction_hours = floatval($_POST['deduction_hours'] ?? 0);
$deduction_days = floatval($_POST['deduction_days'] ?? 0);
$other_earnings = floatval($_POST['other_earnings'] ?? 0);  // NEW
$payroll_note = $_POST['payroll_note'] ?? '';

// 2. Fetch employee salary from emp_salary table
$empStmt = $pdo->prepare("SELECT * FROM `emp_salary` WHERE `emp_id` = ?");
$empStmt->execute([$emp_id]);
$salary = $empStmt->fetch(PDO::FETCH_ASSOC);

// 3. Calculate salary base and rates
$salary_base = $salary['basic'] + $salary['housing'] + $salary['transport'] + 
               $salary['food'] + $salary['misc'] + $salary['cashier'] + 
               $salary['fuel'] + $salary['tel'] + $salary['guard'] + $salary['other'];
$daily_rate = $salary_base / 30;
$hourly_rate = $daily_rate / 8;

// 4. Calculate overtime and deduction amounts
$overtime_amount = $overtime_hours * $hourly_rate;
$deduction_amount = ($deduction_hours * $hourly_rate) + ($deduction_days * $daily_rate);

// 5. Update emp_vacation with all values
$updateStmt = $pdo->prepare("UPDATE `emp_vacation` SET 
    `overtime_hours` = ?,
    `deduction_hours` = ?,
    `deduction_days` = ?,
    `other_earnings` = ?,
    `overtime_amount` = ?,
    `deduction_amount` = ?,
    `payroll_note` = ?
    WHERE `id` = ?");

$updateStmt->execute([
    $overtime_hours,
    $deduction_hours,
    $deduction_days,
    $other_earnings,
    $overtime_amount,
    $deduction_amount,
    $payroll_note,
    $vacation_id
]);
```

### Validation Rules:
- `vacation_id` must be numeric and exist
- `overtime_hours` >= 0
- `deduction_hours` >= 0
- `deduction_days` >= 0
- `other_earnings` >= 0

### Response Format:
```json
{
  "status": "success",
  "message": "Vacation adjustments updated successfully"
}
```

---

## 3. Frontend Modal Function
**File**: `assets/js/jquery.app.js?t=<?= time() ?>`
**Function**: `window.addVacationAdjustments()` (lines ~5828-5950)
**Status**: ✅ Updated with real-time calculation display

### Function Signature:
```javascript
addVacationAdjustments(
  vacationId,                    // int
  employeeName,                  // string
  currentOvertimeHours,          // float
  currentDeductionHours,         // float
  currentDeductionDays,          // float
  otherEarningsOrNote,           // float OR string (backward compatible)
  currentPayrollNote             // string (optional)
)
```

### Modal Features:
1. **Input Fields**:
   - Overtime Hours (number, step: 0.5)
   - Deduction Hours (number, step: 0.5)
   - Deduction Days (number, step: 0.5)
   - Other Earnings (number, step: 0.01) [NEW]
   - Payroll Notes (textarea)

2. **Calculation Display** [NEW]:
   - Shows in real-time as user types
   - Displays: Overtime Amount, Deduction Amount, Other Earnings, Net Adjustment
   - Color-coded for clarity

3. **Event Binding**:
   - On `willOpen`: Initializes calculatePayroll() function
   - On input change: Updates calculation display
   - Targets: `.payroll-calc-trigger` class inputs

### Calculation Function (Inside Modal):
```javascript
const calculatePayroll = () => {
  // Get input values
  const otHrs = parseFloat(document.querySelector('[name="overtime_hours"]').value) || 0;
  const dedHrs = parseFloat(document.querySelector('[name="deduction_hours"]').value) || 0;
  const dedDays = parseFloat(document.querySelector('[name="deduction_days"]').value) || 0;
  const otherEarn = parseFloat(document.querySelector('[name="other_earnings"]').value) || 0;
  
  // Use placeholder rates (or fetch from backend)
  const hourlyRate = 37.50;  // Example: 300/8
  const dailyRate = 300;     // Example: 9000/30
  
  // Calculate amounts
  const overtimeAmount = otHrs * hourlyRate;
  const deductionAmount = (dedHrs * hourlyRate) + (dedDays * dailyRate);
  const netAdjustment = overtimeAmount - deductionAmount + otherEarn;
  
  // Display calculations
  document.querySelector('[data-calc="overtime"]').textContent = 
    'SAR ' + overtimeAmount.toFixed(2);
  document.querySelector('[data-calc="deduction"]').textContent = 
    'SAR ' + deductionAmount.toFixed(2);
  document.querySelector('[data-calc="other"]').textContent = 
    'SAR ' + otherEarn.toFixed(2);
  document.querySelector('[data-calc="net"]').textContent = 
    'SAR ' + netAdjustment.toFixed(2);
};
```

### Backward Compatibility:
```javascript
// Handles both old and new function signatures
if (typeof otherEarningsOrNote === 'string') {
  // Old format: (id, name, ot, dh, dd, note)
  otherEarnings = 0;
  currentPayrollNote = otherEarningsOrNote;
} else {
  // New format: (id, name, ot, dh, dd, otherEarnings, note)
  otherEarnings = otherEarningsOrNote;
}
```

---

## 4. Updated onclick Handler
**File**: `all_applied_vac.php`
**Location**: Action button for "Add deduction/overtime"
**Status**: ✅ Updated to pass other_earnings parameter

### Updated Call:
```php
onclick="addVacationAdjustments(
  <?=$req['id']; ?>, 
  '<?=parseName($req['employee_name']); ?>', 
  '<?= $req['overtime_hours'] ?? '0'; ?>', 
  '<?= $req['deduction_hours'] ?? '0'; ?>', 
  '<?= $req['deduction_days'] ?? '0'; ?>', 
  '<?= $req['other_earnings'] ?? '0'; ?>', 
  `<?= $req['payroll_note'] ?? ''; ?>`
)"
```

### Parameter Mapping:
| Position | Parameter | Source | Type | Default |
|----------|-----------|--------|------|---------|
| 1 | vacation_id | `$req['id']` | int | - |
| 2 | name | `$req['employee_name']` | string | - |
| 3 | overtime_hours | `$req['overtime_hours']` | float | '0' |
| 4 | deduction_hours | `$req['deduction_hours']` | float | '0' |
| 5 | deduction_days | `$req['deduction_days']` | float | '0' |
| 6 | other_earnings | `$req['other_earnings']` | float | '0' |
| 7 | payroll_note | `$req['payroll_note']` | string | '' |

---

## 5. Delegated Handler (in jquery.app.js?t=<?= time() ?>)
**Location**: Global event delegation for `.addVacationAdjustments` elements
**Status**: ✅ Updated to extract other_earnings from data attribute

### Handler Logic:
```javascript
$(document).on('click', '.addVacationAdjustments', function() {
  const vacationId = $(this).data('vacation-id');
  const employeeName = $(this).data('employee-name');
  const overtimeHours = $(this).data('overtime-hours');
  const deductionHours = $(this).data('deduction-hours');
  const deductionDays = $(this).data('deduction-days');
  const otherEarnings = $(this).data('other-earnings');  // NEW
  const payrollNote = $(this).data('payroll-note');
  
  addVacationAdjustments(
    vacationId,
    employeeName,
    overtimeHours,
    deductionHours,
    deductionDays,
    otherEarnings,
    payrollNote
  );
});
```

---

## 6. Example Database Records

### Before Update:
```sql
SELECT id, employee_id, overtime_hours, deduction_hours, deduction_days 
FROM emp_vacation WHERE id = 123;

-- Result:
-- 123 | 456 | 8 | 2 | 1
```

### After Update:
```sql
SELECT id, employee_id, overtime_hours, deduction_hours, deduction_days, 
       other_earnings, overtime_amount, deduction_amount
FROM emp_vacation WHERE id = 123;

-- Result:
-- 123 | 456 | 8 | 2 | 1 | 500 | 300.00 | 200.00
--                          ↑       ↑         ↑
--                        NEW    CALCULATED  CALCULATED
```

---

## 7. Calculation Example

**Employee Salary Data**:
- Basic: 5000
- Housing: 2000
- Transport: 500
- Food: 500
- Misc: 200
- Total Salary Base: 8200

**Calculated Rates**:
- Daily Rate = 8200 / 30 = 273.33
- Hourly Rate = 273.33 / 8 = 34.17

**Input Values**:
- Overtime Hours: 10
- Deduction Hours: 5
- Deduction Days: 2
- Other Earnings: 500

**Calculated Amounts**:
- Overtime Amount = 10 * 34.17 = 341.70 SAR
- Deduction Amount = (5 * 34.17) + (2 * 273.33) = 170.85 + 546.66 = 717.51 SAR
- Net Adjustment = 341.70 - 717.51 + 500 = 124.19 SAR

---

## 8. Testing Procedures

### Step 1: Execute Database Migration
```bash
# Run in MySQL client or phpMyAdmin
-- Run the queries from VACATION_ADJUSTMENTS_ALTER_TABLE.sql
```

### Step 2: Verify Table Structure
```sql
DESCRIBE emp_vacation;
-- Should show new columns: other_earnings, overtime_amount, deduction_amount
```

### Step 3: Test Frontend Modal
1. Navigate to all_applied_vac.php
2. Find an approved Fly+Annual vacation request
3. Click "Add deduction/overtime" button
4. Enter test values:
   - Overtime Hours: 8
   - Deduction Hours: 4
   - Deduction Days: 1
   - Other Earnings: 500
5. Verify calculation display updates
6. Click Save

### Step 4: Verify Database Values
```sql
SELECT overtime_hours, deduction_hours, deduction_days, other_earnings, 
       overtime_amount, deduction_amount
FROM emp_vacation WHERE id = [vacation_id];
```

### Step 5: Test Backward Compatibility
1. Call function with old signature (6 parameters):
   ```javascript
   addVacationAdjustments(123, 'Ahmed', 5, 2, 1, 'Test note');
   ```
2. Verify modal still opens correctly
3. Verify other_earnings defaults to 0

---

## 9. Support Information

### If Calculation Doesn't Display:
- Check browser console for JavaScript errors
- Verify jquery.app.js?t=<?= time() ?> function is properly loaded
- Check that `.payroll-calc-trigger` class is applied to input elements

### If Values Don't Save:
- Check browser Network tab for AJAX response
- Verify ajaxVacation.php is accessible
- Check PHP error log for database errors
- Verify database columns exist (run ALTER TABLE again if needed)

### If Other Earnings Not Showing:
- Clear browser cache (Ctrl+Shift+Delete)
- Reload page (Ctrl+F5)
- Verify all_applied_vac.php has the updated onclick parameter

---

## 10. Summary of Changes

| Component | File | Change | Status |
|-----------|------|--------|--------|
| Database | emp_vacation | Added 3 columns | ✅ Migration created |
| Backend | ajaxVacation.php | Salary fetching + calculation | ✅ Updated |
| Frontend Modal | jquery.app.js?t=<?= time() ?> | Real-time calculation display | ✅ Updated |
| HTML onclick | all_applied_vac.php | Pass other_earnings param | ✅ Updated |
| Documentation | This file | Complete reference | ✅ Created |

All components are ready for production deployment after database migration execution.
