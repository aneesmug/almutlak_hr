-- =====================================================================
-- VACATION ADJUSTMENTS MODULE - COMPLETE UPDATE SUMMARY
-- Date: 2025-12-23
-- =====================================================================

## OVERVIEW
This update adds payroll calculation display and "Other Earnings" field to the vacation adjustments modal. The system now:
1. Shows real-time calculations for overtime and deduction amounts
2. Adds a new "Other Earnings" field for bonuses/incentives
3. Displays net adjustment calculation
4. Stores calculated amounts in the database

## CHANGES MADE

### 1. DATABASE CHANGES (emp_vacation table)
========================================

File: VACATION_ADJUSTMENTS_ALTER_TABLE.sql

New Columns Added:
- other_earnings (DECIMAL(10,2), DEFAULT 0.00)
  * Stores additional earnings to add to payroll
  * Optional field, defaults to 0
  
- overtime_amount (DECIMAL(10,2), DEFAULT 0.00)
  * Calculated field storing overtime payment amount
  * Formula: overtime_hours * hourly_rate
  
- deduction_amount (DECIMAL(10,2), DEFAULT 0.00)
  * Calculated field storing deduction payment amount
  * Formula: (deduction_hours * hourly_rate) + (deduction_days * daily_rate)

SQL Commands to Run:
```sql
ALTER TABLE `emp_vacation` 
ADD COLUMN `other_earnings` DECIMAL(10, 2) DEFAULT 0.00 
AFTER `deduction_days`;

ALTER TABLE `emp_vacation` 
ADD COLUMN `overtime_amount` DECIMAL(10, 2) DEFAULT 0.00 
AFTER `deduction_days`;

ALTER TABLE `emp_vacation` 
ADD COLUMN `deduction_amount` DECIMAL(10, 2) DEFAULT 0.00 
AFTER `overtime_amount`;
```

### 2. BACKEND CHANGES (AJAX Handler)
======================================

File: includes/ajaxFile/ajaxVacation.php
Handler: updateVacationAdjustments

Updates:
- Added `other_earnings` parameter to POST data
- Fetches employee salary data to calculate hourly/daily rates
- Calculates overtime_amount: overtime_hours * hourly_rate
- Calculates deduction_amount: (deduction_hours * hourly_rate) + (deduction_days * daily_rate)
- Stores all calculated values in emp_vacation table
- Added validation for other_earnings >= 0

Parameters Received:
- vacation_id (int) - Required
- overtime_hours (float) - Hours of overtime
- deduction_hours (float) - Hours of deduction
- deduction_days (float) - Days of deduction
- other_earnings (float) - Additional earnings amount
- payroll_note (string) - Optional notes

Calculations:
- salary_base = sum of all salary components
- daily_rate = salary_base / 30
- hourly_rate = daily_rate / 8
- overtime_amount = overtime_hours * hourly_rate
- deduction_amount = (deduction_hours * hourly_rate) + (deduction_days * daily_rate)

### 3. FRONTEND CHANGES
=======================

File A: assets/js/jquery.app.js
Function: window.addVacationAdjustments()

Features Added:
1. Real-time Calculation Display
   - Shows overtime amount as user types
   - Shows deduction amount as user types
   - Shows other earnings amount as user types
   - Shows net adjustment (overtime - deduction + other_earnings)
   - Updates instantly on input change

2. New Other Earnings Field
   - Labeled: "Other Earnings" with $ icon
   - Optional field
   - Green text (success color) for additions
   - Step: 0.01 for precise currency entry

3. Payroll Summary Box
   - Styled with light background (bg-light)
   - Shows breakdown of all amounts
   - Color-coded:
     * Overtime: Green (+)
     * Deduction: Red (-)
     * Other Earnings: Green (+)
     * Net Adjustment: Blue (info color)

4. Backward Compatibility
   - Handles old parameter format (payroll_note as 6th param)
   - Auto-detects if parameter is string or number
   - Maintains compatibility with existing calls

Function Signature:
```javascript
addVacationAdjustments(
  vacationId,           // int - Required
  employeeName,         // string - For modal title
  currentOvertimeHours, // float - Current value
  currentDeductionHours,// float - Current value
  currentDeductionDays, // float - Current value
  otherEarningsOrNote,  // float or string - New/old format
  currentPayrollNote    // string - Optional
)
```

File B: all_applied_vac.php
Line: ~705

Updated onclick call:
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

### 4. CALCULATION DETAILS
===========================

Example Calculation (Demo values):
- Daily Rate: 300 SAR (= salary_base / 30)
- Hourly Rate: 37.5 SAR (= daily_rate / 8)

Input:
- Overtime Hours: 10
- Deduction Hours: 5
- Deduction Days: 2
- Other Earnings: 500

Calculation:
- Overtime Amount = 10 * 37.5 = 375.00 SAR
- Deduction Amount = (5 * 37.5) + (2 * 300) = 187.50 + 600 = 787.50 SAR
- Other Earnings = 500.00 SAR
- Net Adjustment = 375.00 - 787.50 + 500.00 = 87.50 SAR

### 5. MODAL APPEARANCE
========================

Modal Title: "Add/Edit adjustments for [Employee Name]"

Fields (in order):
1. Overtime (Hours) - input type="number", step=0.5, min=0
2. Deduction (Hours) - input type="number", step=0.5, min=0
3. Deduction (Days) - input type="number", step=0.5, min=0
4. Other Earnings - input type="number", step=0.01, min=0 [NEW]

Summary Box (NEW):
- Overtime Amount (displayed in green with +)
- Deduction Amount (displayed in red with -)
- Other Earnings (displayed in green with +)
- Net Adjustment (total, displayed in info color)

Notes Field:
- Textarea for additional notes
- Optional field
- 2 rows height

Buttons:
- "Save Adjustments" (confirm)
- "Cancel" (cancel)

### 6. VALIDATION
==================

Client-Side (JavaScript):
- No negative values allowed
- All numeric fields validated
- Display error: "Negative values not allowed"

Server-Side (PHP):
- vacation_id required and must exist
- All numeric fields >= 0
- Database prepare statement for security
- Transaction handling for data integrity

### 7. USAGE EXAMPLES
=====================

Example 1: Direct Function Call
```javascript
addVacationAdjustments(
  123,                  // vacation_id
  'Ahmed Al-Mutlak',   // employeeName
  8,                   // overtime_hours
  2,                   // deduction_hours
  1,                   // deduction_days
  500,                 // other_earnings (NEW)
  'Bonus for project'  // payroll_note
);
```

Example 2: With Delegation Handler
```html
<a class="dropdown-item" 
   class="addVacationAdjustments"
   data-vacation-id="123"
   data-employee-name="Ahmed Al-Mutlak"
   data-overtime-hours="8"
   data-deduction-hours="2"
   data-deduction-days="1"
   data-other-earnings="500"
   data-payroll-note="Bonus for project"
   href="javascript:void(0);">
  Add/Edit Adjustments
</a>
```

### 8. TRANSLATION KEYS
========================

Keys used in modal (add to translation system if needed):
- add_edit_adjustments_for
- overtime_hours
- deduction_hours
- deduction_days
- other_earnings [NEW]
- payroll_note
- payroll_summary
- overtime_amount
- deduction_amount
- net_adjustment
- save_adjustments
- error_saving_adjustments
- invalid_negative_values_not_allowed

### 9. BACKWARD COMPATIBILITY
==============================

Old Code Still Works:
```javascript
// Old format (without other_earnings)
addVacationAdjustments(id, name, ot, dh, dd, note);

// New format (with other_earnings)
addVacationAdjustments(id, name, ot, dh, dd, 500, note);
```

The function auto-detects the format:
- If 6th param is string → treats as payroll_note
- If 6th param is number → treats as other_earnings

### 10. TESTING CHECKLIST
==========================

Database:
- [ ] Run ALTER TABLE commands
- [ ] Verify columns created: other_earnings, overtime_amount, deduction_amount
- [ ] Verify data types: DECIMAL(10,2)

Backend:
- [ ] Test updateVacationAdjustments endpoint
- [ ] Verify other_earnings parameter is saved
- [ ] Verify overtime_amount is calculated correctly
- [ ] Verify deduction_amount is calculated correctly
- [ ] Test with various salary bases
- [ ] Test negative value validation

Frontend:
- [ ] Other Earnings field displays
- [ ] Calculations update in real-time
- [ ] Summary box shows correct amounts
- [ ] Save successfully stores data
- [ ] Page reloads after save
- [ ] Old format calls still work

### 11. FUTURE ENHANCEMENTS
===========================

Potential improvements:
1. Dynamic salary base fetching via AJAX (instead of placeholder)
2. Support for overtime multipliers (1.5x, 2x for different times)
3. Currency formatting based on locale
4. Monthly calculation templates
5. Bulk adjustment operations
6. Adjustment history/audit log

=====================================================================
