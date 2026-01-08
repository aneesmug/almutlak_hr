# Quick Reference: GR Officer Implementation

## Overview
GR Officer is now automatically added to the approval chain for **Fly | Annual** vacations only.

## Approval Chain Order (Fly | Annual)
```
Employee Submit 
  ↓
Manager/Supervisor (Level 1)
  ↓
HR Senior BP or Assistant (Level 2)
  ↓
HR Payroll [if configured] (Level 3)
  ↓
GR OFFICER ← Exit & Re-Entry Fee Required Here ✨
  ↓
Asset Clearance (IT, Admin, Transport)
  ↓
Complete ✓
```

## What GR Officer Does
1. Reviews the vacation request
2. Enters the **Exit Re-Entry Visa Fee** amount (SAR)
3. Approves or rejects the vacation

## Key Features

| Feature | Details |
|---------|---------|
| **Trigger** | When vacation is Fly + Annual |
| **Required Field** | Exit Re-Entry Fee (must be > 0) |
| **Field Type** | Decimal number (2 decimal places) |
| **Currency** | SAR (Saudi Arabian Riyal) |
| **Not Required For** | Local Vacation, Emergency Fly, Encashed, Leaves |
| **Role** | user_type = 'gr_officer' in admin_login |
| **Status** | Employees.status = 1 (active) |
| **Database Column** | emp_vacation.exit_re_entry_fee |

## Database Schema
```sql
ALTER TABLE `emp_vacation` 
ADD COLUMN `exit_re_entry_fee` DECIMAL(10, 2) DEFAULT 0.00 
AFTER `permit_fee`;
```

## Installation Checklist
- [ ] Run SQL migration: `db_updates/add_exit_re_entry_fee_column.sql`
- [ ] Verify column exists in emp_vacation table
- [ ] Clear browser cache
- [ ] Ensure at least one user has user_type = 'gr_officer'
- [ ] Test with Fly | Annual vacation

## Field Validation
**GR Officer will see error if:**
- Exit Re-Entry Fee is empty
- Exit Re-Entry Fee is 0
- Exit Re-Entry Fee is negative

## Translation Keys
If using custom translations, ensure these exist:
- `visa_re_entry_fee_information`
- `exit_re_entry_fee`
- `enter_exit_re_entry_fee_amount`
- `exit_re_entry_fee_required`

## Files Involved

### Backend
- `includes/ajaxFile/ajaxVacation.php` (applyVacation + approveVacation)

### Frontend
- `all_applied_vac.php` (approval modal)

### Database
- `db_updates/add_exit_re_entry_fee_column.sql`

### Documentation
- `readme/GR_OFFICER_CLEARANCE_IMPLEMENTATION.md`
- `GR_OFFICER_IMPLEMENTATION_SUMMARY.txt`

## What Changed vs What Didn't

### ✅ Changed
- Fly | Annual vacation approval chain now includes GR Officer
- Exit Re-Entry Fee field added to emp_vacation table
- GR Officer approval modal shows visa fee input field

### ✅ NOT Changed
- Local Vacation approval chain (unchanged)
- Emergency Fly approval (unchanged)
- Encashed Vacation approval (unchanged)
- Sick/Maternity/Paternity Leave (unchanged)
- HR Payroll step (still optional, still in same position)

## Common Questions

**Q: Will this affect other vacation types?**  
A: No. Only Fly + Annual vacations are affected. All other types work exactly as before.

**Q: What if there's no GR Officer in the system?**  
A: The vacation will still proceed through the chain without the GR Officer step.

**Q: Can GR Officer skip entering the fee?**  
A: No. The field is required and must have a value > 0.

**Q: Is the fee deducted from the employee's salary?**  
A: No. GR Officer only records the amount. Payroll deductions (if any) are handled separately by HR Payroll.

**Q: When is this fee used?**  
A: The amount is stored in the database for reference/reporting. It may be used by Finance/HR for:
- Visa processing vendor payments
- Employee reimbursement tracking
- Financial reporting and reconciliation

**Q: Can GR Officer see vacations for other roles?**  
A: Yes. GR Officer can see and approve all Fly | Annual vacation requests assigned to them.

## Rollback
If needed to remove this feature:
1. Drop the column: `ALTER TABLE emp_vacation DROP COLUMN exit_re_entry_fee;`
2. Revert code changes in ajaxVacation.php and all_applied_vac.php
3. Clear browser cache

## Support & Documentation
- **Technical Details:** See `readme/GR_OFFICER_CLEARANCE_IMPLEMENTATION.md`
- **Implementation Summary:** See `GR_OFFICER_IMPLEMENTATION_SUMMARY.txt`
- **Code Comments:** Look for `[NEW]` comments in modified files

