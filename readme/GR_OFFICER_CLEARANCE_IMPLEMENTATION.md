# GR Officer Clearance Implementation for Fly | Annual Vacations

## Overview
This implementation adds a new approval step for GR Officer (General Relations Officer) in the vacation approval chain specifically for **Fly | Annual** vacation types. The GR Officer is responsible for processing exit and re-entry visa fees, which are critical for employees traveling internationally.

## Changes Made

### 1. Database Schema Update
**File:** `db_updates/add_exit_re_entry_fee_column.sql`

Added a new column to the `emp_vacation` table:
```sql
ALTER TABLE `emp_vacation` 
ADD COLUMN `exit_re_entry_fee` DECIMAL(10, 2) DEFAULT 0.00 
COMMENT 'Exit and re-entry visa fee amount (SAR) - filled by GR Officer for Fly | Annual vacations'
AFTER `permit_fee`;

ALTER TABLE `emp_vacation` 
ADD INDEX `idx_exit_re_entry_fee` (`exit_re_entry_fee`);
```

### 2. Approval Chain Configuration
**File:** `includes/ajaxFile/ajaxVacation.php` (applyVacation section, lines ~395-486)

#### New Variable
- `$is_fly_annual`: Tracks whether the vacation is specifically Fly | Annual type

#### GR Officer Resolution in `resolveApprover()` function
```php
// [NEW] GR Officer - find user with user_type = 'gr_officer'
if ($role === 'gr_officer') {
    $stmt = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type = 'gr_officer' AND e.status = 1 ORDER BY e.emp_id ASC LIMIT 1");
    // Returns the first active GR Officer
}
```

#### Automatic GR Officer Inclusion
For Fly | Annual vacations, the GR Officer is automatically appended to the approval chain:
```php
// [NEW] For Fly | Annual vacations, ensure GR Officer is in the chain (after HR Payroll if exists)
if ($is_fly_annual) {
    $gr_officer_id = $resolveApprover('gr_officer');
    if ($gr_officer_id && !in_array($gr_officer_id, $approver_chain, true)) {
        $approver_chain[] = $gr_officer_id; // Append at end
    }
}
```

### 3. Approval Handling Updates
**File:** `includes/ajaxFile/ajaxVacation.php` (approveVacation section, lines ~1207-1843)

#### Input Field Addition
```php
// [NEW] Exit Re-Entry Fee (sent by GR Officer for Fly | Annual vacations)
$exit_re_entry_fee = (float)($_POST['exit_re_entry_fee'] ?? 0);
```

#### Database Query Update
The `emp_vacation` table query now includes the new `exit_re_entry_fee` column:
```php
$query_inv = mysqli_query($conDB, "SELECT `request_inv_no`, `vac_type`, `fly_type`, ..., `exit_re_entry_fee` FROM `emp_vacation` WHERE `id` = " . $vacation_id);
```

#### Field Update Logic
```php
// [NEW] Check if GR Officer is updating exit_re_entry_fee (for Fly | Annual vacations)
if ($exit_re_entry_fee > 0) {
    $update_fields[] = "`exit_re_entry_fee` = ?";
    $update_values[] = $exit_re_entry_fee;
    $update_types .= "d";
    $needs_update = true;
}
```

### 4. Frontend UI Updates
**File:** `all_applied_vac.php` (JavaScript sections)

#### Enable GR Officer Role
```javascript
// --- Define approval flow conditions ---
const isLevel1 = (currentLevel == 1);
const isHR_Assistant = (userRole === 'assistant');
const isHR_SeniorBP = (userRole === 'hr_senior_bp');
const isHR_Payroll = (userRole === 'hr_payroll');
const isGR_Officer = (userRole === 'gr_officer'); // [NEW] Enable GR Officer role
const isAnnualFly = (vacType === 'Fly');
```

#### GR Officer Form Fields
```javascript
// [NEW] --- GR Officer Visa/Re-Entry Fee Section ---
if (isGR_Officer && isAnnualFly) {
    hrPayrollHtml = `
        <div class="swal-gr-officer-fields text-left mt-3">
            <hr>
            <h6 class="text-primary mb-3">
                <i class="fa fa-passport"></i> ${__('visa_re_entry_fee_information') || 'Visa & Re-Entry Fee Information'}
            </h6>
            <div class="form-group">
                <label for="swal_exit_re_entry_fee" class="font-weight-bold">
                    <i class="fa fa-coins"></i> ${__('exit_re_entry_fee') || 'Exit & Re-Entry Visa Fee'} 
                    <span class="text-danger">*</span>
                </label>
                <input type="number" id="swal_exit_re_entry_fee" class="form-control" placeholder="0.00" step="0.01" required min="0" ...>
            </div>
        </div>
    `;
}
```

#### Field Validation
```javascript
// [NEW] Validate GR Officer required fields if GR Officer is approving Fly | Annual
if ((isGR_Officer && isAnnualFly)) {
    const exitReEntryFee = $(swalModal).find('#swal_exit_re_entry_fee').val();
    if (!exitReEntryFee || parseFloat(exitReEntryFee) <= 0) {
        Swal.showValidationMessage(__('exit_re_entry_fee_required') || 'Exit Re-Entry Fee is required');
        return false;
    }
}
```

#### Data Transmission
```javascript
// Include exit_re_entry_fee in AJAX data sent to backend
data: {
    ajaxType: 'approveVacation',
    vacation_id: vacationId,
    // ... other fields ...
    exit_re_entry_fee: approveData.exit_re_entry_fee || null, // [NEW]
    // ... more fields ...
}
```

## Approval Chain Flow for Fly | Annual Vacations

### New Flow
```
Employee → Supervisor/Manager
         ↓
       HR Senior BP (or HR Assistant)
         ↓
       HR Payroll (if configured)
         ↓
       [NEW] GR Officer ← Exit & Re-entry Visa Fee Processing
         ↓
       Asset Clearance (IT, Admin, Transportation)
         ↓
       Completion
```

## Requirement Details

### GR Officer Responsibilities
1. **Process Exit Visa Fee:** Record the amount for employee's exit visa processing
2. **Process Re-Entry Visa Fee:** Record the amount for employee's re-entry visa processing
3. **Total Amount:** Enter the combined exit + re-entry visa fee amount (in SAR)
4. **Required Field:** The exit_re_entry_fee field is MANDATORY for GR Officer approval of Fly | Annual vacations

### When GR Officer Step is Triggered
- **Vacation Type:** Fly
- **Fly Type:** Annual
- **Not Required For:**
  - Local Vacation (annual or emergency)
  - Emergency Fly
  - Encashed Vacation
  - Sick Leave, Maternity, Paternity, etc.

## User Type Configuration
The GR Officer is identified by:
- **User Type in admin_login table:** `gr_officer`
- **Status:** Active (status = 1)
- **Selection:** First active GR Officer in the system (by emp_id ASC)

## Database Installation

### Step 1: Run the migration
```bash
mysql -u root -p almutlak < db_updates/add_exit_re_entry_fee_column.sql
```

Or execute manually in SQL:
```sql
SOURCE db_updates/add_exit_re_entry_fee_column.sql;
```

### Step 2: Verify the column
```sql
DESCRIBE emp_vacation;
-- Should show: exit_re_entry_fee | DECIMAL(10,2) | YES | MUL | 0.00 |
```

## Testing Checklist

- [ ] Fly | Annual vacation submitted by employee
- [ ] Supervisor approves
- [ ] HR Senior BP approves
- [ ] HR Payroll (if configured) approves
- [ ] **[NEW] GR Officer sees the approval task**
- [ ] **[NEW] GR Officer can enter exit_re_entry_fee value**
- [ ] **[NEW] GR Officer's approval validates exit_re_entry_fee is not zero**
- [ ] **[NEW] exit_re_entry_fee is saved to emp_vacation.exit_re_entry_fee**
- [ ] Asset Clearance continues normally
- [ ] Vacation is completed successfully

## Translation Keys Added
The following translation keys are referenced (ensure they exist in your translation system):

- `visa_re_entry_fee_information` - Section header
- `exit_re_entry_fee` - Field label
- `enter_exit_re_entry_fee_amount` - Field hint
- `exit_re_entry_fee_required` - Validation message

## Rollback Instructions

### If you need to remove this feature:
```sql
ALTER TABLE `emp_vacation` DROP COLUMN `exit_re_entry_fee`;
ALTER TABLE `emp_vacation` DROP INDEX `idx_exit_re_entry_fee`;
```

Then revert the code changes in:
1. `includes/ajaxFile/ajaxVacation.php` (applyVacation and approveVacation sections)
2. `all_applied_vac.php` (JavaScript approval modal)

## Key Features

✅ **Automatic Chain Building** - GR Officer is automatically added for Fly | Annual vacations  
✅ **Required Field** - Exit re-entry fee is mandatory for GR Officer approval  
✅ **Database Persistence** - Fee amount stored in emp_vacation table  
✅ **Validation** - Frontend and backend validation of fee amount  
✅ **Role-Based Access** - Only GR Officer sees and can approve this step  
✅ **Backward Compatible** - Other vacation types unaffected  

## Notes

- The GR Officer step comes AFTER HR Payroll (if configured) to ensure payroll has already processed salary deductions
- The exit_re_entry_fee is separate from ticket_pay and permit_fee (which are handled by HR Assistant)
- If no active GR Officer exists in the system, the vacation will still proceed but without this approval step
- The fee amount is stored as a decimal for precise financial tracking

