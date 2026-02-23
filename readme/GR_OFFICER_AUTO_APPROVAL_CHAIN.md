# GR Officer Auto-Addition to Approval Chain

## Overview
GR Officer is now automatically added to the approval chain for **Fly | Annual** vacation requests to handle exit and re-entry visa fee processing.

## Implementation Details

### Backend Changes

#### 1. ajaxVacation.php (Lines 1200-1261)
Added logic to automatically append GR Officer to the approval chain after it's created by ApprovalChainManager:

```php
// FLY | ANNUAL: ensure GR Officer is appended at the end of the approval chain (for visa/exit re-entry fee)
if ($is_fly_annual) {
    // Resolve GR Officer (user_type = 'gr_officer')
    $gr_officer_id = null;
    $stmt_gr = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type = 'gr_officer' AND e.status = 1 ORDER BY e.emp_id ASC LIMIT 1");
    // ... finds and appends GR Officer to approval chain
}
```

**How it works:**
1. After a Fly | Annual vacation request is created
2. ApprovalChainManager creates the standard approval chain from `app_settings`
3. System detects `$is_fly_annual` flag (remarks='Fly' AND fly_type='annual')
4. Queries for active user with `user_type = 'gr_officer'`
5. Appends GR Officer at the end of the approval chain (highest level)
6. GR Officer gets status = 'awaiting' until previous approvers complete

### Frontend Changes

#### 1. all_applied_vac.php (Lines 1920-1933)
Added informational message in approval modal showing that GR Officer will be auto-added:

```javascript
const grOfficerNote = isAnnualFly ? `<br><i class="fa fa-passport"></i> ${__('gr_officer_auto_added')}` : '';
chainHtml = `
    <div class="swal-approval-chain text-left mt-3">
        <hr>
        <p class="text-info">
            <i class="fa fa-info-circle"></i> 
            ${__('approval_chain_auto_built')}
            ${grOfficerNote}
        </p>
    </div>
`;
```

### Database Schema

**No new columns needed** - uses existing structure:

- **`request_approvers` table**: GR Officer inserted with incremented `approval_level`
- **`admin_login` table**: Uses `user_type = 'gr_officer'` to identify GR Officer
- **`emp_vacation` table**: Uses existing `permit_fee` column for visa fees

### Translation Keys

Added translation in `db_updates/add_gr_officer_auto_added_translation.sql`:

```sql
INSERT INTO translations (`key`, lang, value) VALUES
('gr_officer_auto_added', 'en', 'GR Officer will be automatically added for exit & re-entry visa processing.'),
('gr_officer_auto_added', 'ar', 'سيتم إضافة موظف العلاقات الحكومية تلقائيا لمعالجة تأشيرة الخروج والعودة.');
```

## Approval Flow Example

### Fly | Annual Vacation Request

**Standard Flow (from app_settings):**
1. Level 1: Direct Supervisor → ✅
2. Level 2: Department Manager → ✅
3. Level 3: HR Senior BP → ✅
4. Level 4: Asset Department Checkers → ✅ (if applicable)
5. Level 5: HR Payroll → ✅

**Auto-Added:**
6. **Level 6: GR Officer** → ✅ (automatically appended)
   - Status: 'awaiting' until Level 5 approves
   - Can add `permit_fee` (exit & re-entry visa fees)
   - Uses existing `emp_vacation.permit_fee` column

### Local | Annual Vacation Request
**No GR Officer added** - follows standard chain only.

### Fly | Emergency Vacation Request
**No GR Officer added** - only Annual Fly vacations require GR Officer.

## GR Officer Responsibilities

When the request reaches GR Officer in the approval queue:

1. **Review Request**: Vacation details, employee info, travel dates
2. **Process Visa Fees**: Enter total exit & re-entry visa permit fees in `permit_fee` field
3. **Approve/Reject**: Forward to next approver or reject with reason

## User Interface

### For Approvers (Level 1)
When approving a Fly | Annual vacation, they see:
> ℹ️ Approval chain will be automatically determined based on assigned assets (HR Senior BP + Asset Teams).
> 
> 🛂 GR Officer will be automatically added for exit & re-entry visa processing.

### For GR Officer
When reviewing a Fly | Annual vacation:
- **Section Header**: "Visa & Re-Entry Fee Information"
- **Field**: Permit & Visa Fees (Exit & Re-Entry) [Required]
- **Description**: Enter the total amount for exit and re-entry visa permit fees (in SAR)

## Technical Notes

- **Follows same pattern as Finance Manager** for encashed vacations
- **No duplicate check**: System verifies GR Officer not already in chain before adding
- **Dynamic level assignment**: GR Officer level = MAX(existing levels) + 1
- **Single GR Officer**: Uses `ORDER BY e.emp_id ASC LIMIT 1` to get first active GR Officer
- **JavaScript already supports** `gr_officer` role (defined in jquery.app.js?t=<?= time() ?> lines 7289, 7378)

## Files Modified

1. **d:\xampp\htdocs\almutlak\system\includes\ajaxFile\ajaxVacation.php**
   - Added GR Officer auto-append logic after line 1199

2. **d:\xampp\htdocs\almutlak\system\all_applied_vac.php**
   - Added informational message for GR Officer auto-addition (line ~1922)

3. **d:\xampp\htdocs\almutlak\system\db_updates\add_gr_officer_auto_added_translation.sql**
   - New translation keys for user interface

## Testing Checklist

- [ ] Create Fly | Annual vacation request
- [ ] Verify GR Officer appears in `request_approvers` table
- [ ] Verify GR Officer has highest `approval_level`
- [ ] Verify GR Officer status = 'awaiting'
- [ ] Approve through chain up to GR Officer
- [ ] Verify GR Officer can see request in "My Pending" queue
- [ ] Verify GR Officer can add `permit_fee`
- [ ] Verify approval continues after GR Officer approves
- [ ] Verify Local | Annual does NOT add GR Officer
- [ ] Verify Fly | Emergency does NOT add GR Officer

## Deployment Instructions

1. **Deploy Code**:
   ```bash
   # Upload modified files to production
   scp ajaxVacation.php production:/path/to/includes/ajaxFile/
   scp all_applied_vac.php production:/path/to/
   ```

2. **Run SQL**:
   ```bash
   mysql -u user -p database < db_updates/add_gr_officer_auto_added_translation.sql
   ```

3. **Verify GR Officer User**:
   ```sql
   SELECT e.emp_id, e.name, al.user_type, e.status
   FROM employees e
   JOIN admin_login al ON e.emp_id = al.emp_id
   WHERE al.user_type = 'gr_officer' AND e.status = 1;
   ```

4. **Test**: Create test Fly | Annual vacation and verify GR Officer in chain

## Related Documentation

- [GR_OFFICER_IMPLEMENTATION_UPDATED.md](GR_OFFICER_IMPLEMENTATION_UPDATED.md) - Initial GR Officer implementation
- [MULTIPLE_VACATION_REJOIN_FIX.md](MULTIPLE_VACATION_REJOIN_FIX.md) - Sequential rejoin implementation
- [VACATION_DATE_OVERLAP_FIX.md](VACATION_DATE_OVERLAP_FIX.md) - Date conflict validation

---

**Date**: January 7, 2026  
**Author**: Development Team  
**Status**: ✅ Implemented
