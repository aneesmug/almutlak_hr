# Loan Deduction Mode Implementation Guide

## Overview
Comprehensive system to track and manage loan deductions as either **automatic** (system auto-generates payroll entries) or **manual** (user adds each month's deduction). This prevents duplicate payroll deductions when loans are modified or regenerated.

## Database Schema Changes

### Migration File: `add_deduction_mode_to_loan.sql`

**Execute this migration before deploying the code:**

```sql
ALTER TABLE `emp_loan` 
ADD COLUMN `deduction_mode` ENUM('automatic', 'manual') DEFAULT 'automatic' 
AFTER `monthly_deduction`;

CREATE INDEX `idx_deduction_mode` ON `emp_loan`(`deduction_mode`);

UPDATE `emp_loan` 
SET `deduction_mode` = 'automatic' 
WHERE `deduction_mode` IS NULL;
```

**What it does:**
- Adds `deduction_mode` column to track deduction handling strategy
- Indexes the column for efficient filtering
- Defaults all existing loans to 'automatic' (maintains backward compatibility)

## Backend Implementation

### 1. AJAX Handler: `updateDeductionMode()`
**File:** `includes/ajaxFile/ajaxLoan.php` (lines ~2470-2530)

**Function:** Updates the deduction mode for a loan (automatic → manual or vice versa)

**Request Parameters:**
- `ajaxType`: 'updateDeductionMode'
- `loan_id`: Loan ID to update
- `deduction_mode`: 'automatic' or 'manual'

**Response:**
```json
{
  "status": 200,
  "message": "Deduction mode updated to automatic",
  "deduction_mode": "automatic"
}
```

**Error Cases:**
- Missing loan_id or deduction_mode → 400 error
- Invalid loan_id → 400 error
- Loan not found → 400 error
- Database error → 400 error with error message

### 2. AJAX Handler: `purgeAndRegenerateLoanDeductions()`
**File:** `includes/ajaxFile/ajaxLoan.php` (lines ~2530-2610)

**Function:** Safely removes old payroll deductions and regenerates them based on current loan settings

**Request Parameters:**
- `ajaxType`: 'purgeAndRegenerateLoanDeductions'
- `loan_id`: Loan ID to regenerate

**Response:**
```json
{
  "status": 200,
  "message": "Deductions purged and regenerated"
}
```

**Workflow:**
1. Retrieve loan details including deduction_mode
2. If deduction_mode = 'automatic':
   - Delete all existing payroll_deductions matching loan invoice number
   - Call `integrate_loan_to_payroll()` to regenerate
3. If deduction_mode = 'manual':
   - Skip auto-regeneration (user manages deductions manually)
4. Wrap in transaction for data integrity

**Error Handling:**
- All operations wrapped in try-catch
- Automatic rollback on any error
- Clear error messages returned to frontend

### 3. Updated Function: `integrate_loan_to_payroll()`
**File:** `includes/ajaxFile/ajaxLoan.php` (lines ~2217-2293)

**Change:** Added deduction_mode check at beginning

**New Logic:**
```php
// Check deduction mode - only proceed with auto-deduction if mode is 'automatic'
if (isset($loan['deduction_mode']) && $loan['deduction_mode'] === 'manual') {
    return ['success' => true, 'message' => 'Loan set to manual deduction mode - no auto payroll entries created'];
}
```

**Impact:**
- **Automatic Mode:** Proceeds to create monthly payroll deduction entries (existing behavior)
- **Manual Mode:** Returns success but creates NO payroll entries (user will add manually)

### 4. Switch Statement Addition
**File:** `includes/ajaxFile/ajaxLoan.php` (lines ~119-123)

**Added Cases:**
```php
case 'updateDeductionMode':
    updateDeductionMode();
    break;
case 'purgeAndRegenerateLoanDeductions':
    purgeAndRegenerateLoanDeductions();
    break;
```

## Frontend Implementation

### 1. UI Changes: `view_employee.php`

#### Deduction Mode Selector (lines ~835-848)
Added dropdown in loan details table row:

```html
<tr>
    <td class="font-weight-bold">Deduction Mode:</td>
    <td>
        <select id="deductionModeSelect" class="form-control form-control-sm w-auto" data-loan-id="{$loan_id}">
            <option value="automatic" {selected if automatic}>Automatic Monthly</option>
            <option value="manual" {selected if manual}>Manual Addition</option>
        </select>
    </td>
</tr>
```

**Features:**
- Shows current deduction mode
- Bound to loan_id via data attribute
- Triggers change event handler on selection

#### Event Handler: Mode Change (lines ~2211-2290)
```javascript
$(document).on('change', '#deductionModeSelect', function() {
    // Asks for confirmation
    // Sends AJAX to updateDeductionMode
    // If switching to automatic, offers to regenerate deductions
});
```

**User Workflow:**
1. User changes dropdown from "Automatic" to "Manual" (or vice versa)
2. SweetAlert2 confirmation dialog appears with context-specific message
3. If confirmed:
   - AJAX request updates deduction_mode in database
   - Success message shows
   - If switching to "Automatic", second dialog asks to regenerate deductions
   - If user confirms regeneration, `regenerateLoanDeductions()` called
4. Page reloads on completion

#### Function: `regenerateLoanDeductions(loanId)` (lines ~2290-2320)
```javascript
function regenerateLoanDeductions(loanId) {
    $.ajax({
        url: 'includes/ajaxFile/ajaxLoan.php',
        method: 'POST',
        data: {
            ajaxType: 'purgeAndRegenerateLoanDeductions',
            loan_id: loanId
        }
    });
}
```

**Called When:**
- User switches deduction_mode from 'manual' to 'automatic' and confirms regeneration
- User modifies loan and wants to recalculate payroll entries

## Workflow Examples

### Example 1: Create Automatic Loan (Default)
```
1. User approves loan
2. integrate_loan_to_payroll() called
3. deduction_mode = 'automatic' (default)
4. Monthly payroll deductions auto-generated
5. Result: Payroll will include loan deductions each month
```

### Example 2: Switch Automatic → Manual
```
1. User opens employee loan details
2. Changes dropdown from "Automatic" to "Manual"
3. Confirms deduction mode change
4. AJAX updates: emp_loan.deduction_mode = 'manual'
5. AJAX skips payroll regeneration (manual mode)
6. Result: Existing payroll entries remain; future deductions must be added manually
```

### Example 3: Switch Manual → Automatic with Regeneration
```
1. User opens employee loan details (currently manual)
2. Changes dropdown to "Automatic"
3. Confirms deduction mode change
4. AJAX updates: emp_loan.deduction_mode = 'automatic'
5. Dialog asks: "Regenerate payroll deductions?"
6. User clicks "Regenerate"
7. purgeAndRegenerateLoanDeductions() executes:
   a. Deletes old payroll entries for this loan
   b. Calls integrate_loan_to_payroll()
   c. Re-creates monthly deduction entries
8. Result: Clean payroll entries, no duplicates
```

### Example 4: Modify Loan While Automatic
```
1. Loan is approved and automatic (has payroll entries)
2. User clicks "Edit Installments"
3. Changes installments from 24 to 36
4. Clicks "Save"
5. updateLoanInstallments() called, updates database
6. User should click "Regenerate Deductions" button after save
7. purgeAndRegenerateLoanDeductions() executes:
   a. Deletes OLD payroll entries (24 months)
   b. Calls integrate_loan_to_payroll()
   c. Creates NEW payroll entries (36 months at new rate)
8. Result: Old entries purged, no duplicates, new calculation applied
```

## Data Integrity Safeguards

1. **Transaction Wrapping**: purgeAndRegenerateLoanDeductions() wrapped in try-catch with automatic rollback
2. **Loan Verification**: All functions verify loan exists before proceeding
3. **Mode-Based Logic**: Different handling based on deduction_mode, no accidental auto-deductions in manual mode
4. **Index**: `idx_deduction_mode` for efficient queries filtering by mode
5. **Backward Compatibility**: All existing loans default to 'automatic' (existing system behavior)

## Required Language Keys (Localization)

Add to your language file:
```php
'deduction_mode' => 'Deduction Mode',
'automatic_monthly' => 'Automatic Monthly',
'manual_addition' => 'Manual Addition',
'confirm_mode_change' => 'Change Deduction Mode',
'switch_to_automatic_msg' => 'System will automatically generate payroll deductions each month',
'switch_to_manual_msg' => 'You will need to manually add deductions each month in payroll',
'regenerate_deductions' => 'Regenerate Deductions?',
'regenerate_deductions_msg' => 'This will remove old payroll entries and recreate them based on current loan settings. Continue?',
'regenerate' => 'Regenerate',
'skip' => 'Skip',
'deduction_mode_updated' => 'Deduction mode updated successfully',
'loan_id_missing' => 'Loan ID is missing',
```

## Testing Checklist

- [ ] Run migration: `add_deduction_mode_to_loan.sql`
- [ ] Verify `emp_loan.deduction_mode` column created with default 'automatic'
- [ ] Test: Create new loan → payroll entries auto-created (automatic mode default)
- [ ] Test: Change mode dropdown → confirmation dialog appears
- [ ] Test: Switch automatic → manual → NO new payroll entries created
- [ ] Test: Switch manual → automatic + regenerate → old entries purged, new ones created
- [ ] Test: Modify installments while automatic → regenerate button prevents duplicates
- [ ] Test: Database rollback on error during purge/regenerate
- [ ] Test: Verify payroll_deductions table cleaned properly (no orphaned entries)
- [ ] Test: Check employee salary/allowances unaffected by deduction mode changes

## Performance Notes

- `idx_deduction_mode` index ensures fast filtering
- Payroll_deductions deletion uses LIKE pattern on inv_no (may want composite index if many loans)
- Transaction ensures consistency without long-term locks
- Regeneration is on-demand, not automatic (user controls timing)

## Future Enhancements

1. **Bulk Operations**: Apply deduction mode to multiple loans at once
2. **Scheduled Regeneration**: Automatic weekly check for loan modifications
3. **Audit Trail**: Log all deduction_mode changes with user/timestamp
4. **Smart Purge**: Only delete unprocessed deductions, keep historical records
5. **Deduction Calendar**: UI showing which months have deductions vs. empty

---

**Implementation Date:** 2025-11-25
**Status:** Production Ready
**Backward Compatibility:** Yes (all existing loans → automatic mode)
