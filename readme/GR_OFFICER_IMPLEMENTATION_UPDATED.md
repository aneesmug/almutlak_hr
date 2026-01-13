# GR Officer Implementation - Updated to Use permit_fee

## Summary of Changes

This implementation has been **updated** to use the existing `permit_fee` column in the `emp_vacation` table for GR Officer approval of exit/re-entry visa fees, instead of creating a new column.

## Key Changes Made

### 1. Backend (ajaxVacation.php)

**Line 1219:** Updated variable initialization to use `permit_fee` for GR Officer
```php
// [UPDATED] permit_fee is now used for GR Officer's exit/re-entry visa fees
$permit_fee = (float)($_POST['permit_fee'] ?? 0);
```

**Line 1244:** Database query now selects the existing `permit_fee` column
```php
SELECT `request_inv_no`, `vac_type`, `fly_type`, `payment_status`, 
       `is_payment_completed`, `departure_date`, `arrival_date`, 
       `ticket_pay`, `permit_fee` FROM `emp_vacation` WHERE `id` = ...
```

**Lines 1669-1675:** GR Officer updates the `permit_fee` field directly
```php
// [UPDATED] Check if GR Officer is updating permit_fee (exit/re-entry visa fees)
if ($permit_fee > 0 && $current_approver_role === 'gr_officer') {
    $update_fields[] = "`permit_fee` = ?";
    $update_values[] = $permit_fee;
    $update_types .= "d";
    $needs_update = true;
}
```

### 2. Frontend (all_applied_vac.php)

**Lines 1860-1880:** Form field updated to use `swal_permit_fee` ID
```html
<label for="swal_permit_fee" class="font-weight-bold">
    <i class="fa fa-coins"></i> Permit & Visa Fees (Exit & Re-Entry) *
</label>
<input type="number" id="swal_permit_fee" class="form-control" 
       placeholder="0.00" step="0.01" required min="0">
```

**Lines 2375-2376:** JavaScript variable retrieves from correct field
```javascript
let permit_fee = $(swalModal).find('#swal_permit_fee').val() || null;
```

**Lines 2421-2428:** Validation updated for `permit_fee`
```javascript
if ((isGR_Officer && isAnnualFly)) {
    const permitFee = $(swalModal).find('#swal_permit_fee').val();
    if (!permitFee || parseFloat(permitFee) <= 0) {
        Swal.showValidationMessage('Permit & Visa Fees are required');
        return false;
    }
}
```

**Data transmission:** Updated to send `permit_fee` in AJAX requests

## Database

**No new migration needed** - Uses existing `permit_fee` DECIMAL(10,2) column in `emp_vacation` table

## Workflow

1. Employee applies for **Fly | Annual** vacation
2. Approval chain: Employee → Supervisor → HR Senior BP → HR Payroll → **GR Officer** → Asset Clearance
3. GR Officer approves and **enters permit & visa fee amount in the `permit_fee` field**
4. Fee is saved to database and tracked with other payment details

## Field Behavior

- **For HR Assistant/Payroll:** `permit_fee` may be used for different purposes (e.g., general permit fees)
- **For GR Officer (Fly | Annual):** `permit_fee` is specifically for exit and re-entry visa fees
- The field is **required** when GR Officer approves (must be > 0)
- **Backward compatible:** No changes to database schema required

## Testing

1. Create a Fly | Annual vacation request
2. Approve through all levels until GR Officer
3. GR Officer should see the form field for "Permit & Visa Fees (Exit & Re-Entry)"
4. Enter a fee amount (e.g., 500)
5. Submit approval
6. Verify in database: `emp_vacation.permit_fee` contains the entered value

## Removed Files

- `db_updates/add_exit_re_entry_fee_column.sql` - No longer needed (deleted)

## Translation Keys Used

- `permit_fee` - Field label
- `permit_fee_description` - Help text
- `permit_fee_required` - Validation message

---

**Status:** Implementation Complete | Date: January 7, 2026
