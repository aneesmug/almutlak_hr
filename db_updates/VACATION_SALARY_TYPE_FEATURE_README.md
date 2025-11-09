# Vacation Salary Type Feature - Implementation Guide

## Overview
This feature allows employees to choose when they want to receive their vacation salary:
- **With Payroll**: Vacation salary is paid during the vacation month (default behavior)
- **With End of Service**: Vacation salary is deferred and paid at the end of employment

## Database Changes

### 1. Run the Column Addition Script
Execute the following SQL file to add the new column to the `emp_vacation` table:

```sql
-- File: db_updates/add_vacation_salary_type_column.sql
ALTER TABLE `emp_vacation` 
ADD COLUMN `vacation_salary_type` ENUM('payroll', 'end_of_service') NOT NULL DEFAULT 'payroll' 
COMMENT 'Determines when vacation salary is paid: with payroll or at end of service'
AFTER `remarks`;

ALTER TABLE `emp_vacation` 
ADD INDEX `idx_vacation_salary_type` (`vacation_salary_type`);
```

### 2. Run the Translation Script
Execute the following SQL file to add translation strings:

```sql
-- File: db_updates/add_vacation_salary_type_translations.sql
-- This adds English and Arabic translations for the UI labels
```

## Files Modified

### 1. Frontend (JavaScript)
**File**: `assets/js/empVacationHandle.js`

**Changes**:
- Added radio button group for vacation salary type selection
- Two options: "With Payroll" (default) and "With End of Service"
- Added help text explaining the options
- Automatically submits the selected option with the vacation application

### 2. Backend (PHP)
**File**: `includes/ajaxFile/ajaxVacation.php`

**Changes**:
- Modified `applyVacation` block to capture `vacation_salary_type` from POST data
- Added validation to ensure only valid values ('payroll' or 'end_of_service') are accepted
- Updated INSERT query to include the new column
- Updated bind_param to handle the additional parameter

### 3. Vacation Report (PHP)
**File**: `vacation_report_details.php`

**Changes**:
- Modified SQL query to fetch `vacation_salary_type` from database
- Updated salary calculation logic:
  - **If 'payroll'**: Calculate vacation salary normally (existing behavior)
  - **If 'end_of_service'**: 
    - Exclude vacation days salary from payroll
    - Only calculate working days salary (days from 1st to day before vacation)
    - Show "Deferred" message for vacation salary
    - Add warning notice that salary will be paid at end of service
- Updated GOSI calculation to exclude vacation salary when type is 'end_of_service'
- Updated display section to show appropriate messages

## How It Works

### Employee Application Flow
1. Employee clicks "Apply Vacation" button
2. Vacation form appears with new "Vacation Salary Payment" option
3. Employee selects either:
   - **With Payroll** (default) - Get vacation salary now
   - **With End of Service** - Defer vacation salary until employment ends
4. Employee completes the rest of the vacation form
5. System saves the selection in `emp_vacation.vacation_salary_type`

### Salary Calculation Impact

#### Option 1: With Payroll (Default)
```
Working Days Salary: (Salary ÷ 30) × Days worked before vacation
Vacation Salary: (Salary ÷ 30) × Vacation days
Ticket Fee: As entered
Permit Fee: As entered
GOSI Deduction: Based on (Working Days + Vacation Days)
Total Payable: All of the above
```

#### Option 2: With End of Service
```
Working Days Salary: (Salary ÷ 30) × Days worked before vacation
Vacation Salary: DEFERRED (shown as "Deferred" in report)
Ticket Fee: As entered
Permit Fee: As entered
GOSI Deduction: Based on Working Days only (no vacation salary)
Total Payable: Working days + Fees - GOSI
```

**Note**: The vacation salary will need to be calculated and paid separately during end of service settlement.

## Translation Keys Added

| Key | English | Arabic |
|-----|---------|--------|
| `vacation_salary_payment` | Vacation Salary Payment | دفع راتب الإجازة |
| `with_payroll` | With Payroll | مع الراتب الشهري |
| `with_end_of_service` | With End of Service | مع نهاية الخدمة |
| `vacation_salary_type_help` | Choose when you want to receive your vacation salary: now with payroll or later at end of service. | اختر متى تريد استلام راتب إجازتك: الآن مع الراتب الشهري أو لاحقًا عند نهاية الخدمة. |

## Installation Steps

1. **Backup your database** before making any changes
2. Run `db_updates/add_vacation_salary_type_column.sql`
3. Run `db_updates/add_vacation_salary_type_translations.sql`
4. Clear browser cache to ensure JavaScript changes are loaded
5. Test the feature:
   - Apply a vacation and select "With Payroll" - verify salary appears normally
   - Apply a vacation and select "With End of Service" - verify salary shows as "Deferred"

## Important Notes

### For HR/Payroll Processing
- When processing end of service, you'll need to:
  1. Query all vacations where `vacation_salary_type = 'end_of_service'`
  2. Calculate total deferred vacation salary
  3. Add it to the end of service settlement

### For Developers
- The default value is 'payroll' to maintain backward compatibility
- Existing vacation records will automatically default to 'payroll'
- The feature respects existing business rules (emergency leave not payable, etc.)

## Testing Checklist

- [ ] Database column added successfully
- [ ] Translations loaded in browser console (check `window.lang`)
- [ ] Vacation application form shows the new radio buttons
- [ ] Selecting "With Payroll" saves correctly and shows salary in report
- [ ] Selecting "With End of Service" saves correctly and shows "Deferred" in report
- [ ] Working days salary calculated correctly for both options
- [ ] GOSI deduction calculated correctly for both options
- [ ] Existing vacation requests still work (default to 'payroll')

## Rollback Plan

If you need to rollback this feature:

```sql
-- Remove the column
ALTER TABLE `emp_vacation` DROP COLUMN `vacation_salary_type`;

-- Remove translations
DELETE FROM `language` WHERE `lang_key` IN (
    'vacation_salary_payment',
    'with_payroll', 
    'with_end_of_service',
    'vacation_salary_type_help'
);
```

Then restore the previous versions of the modified files from git.

## Support

For questions or issues, please contact the development team.

---
**Last Updated**: October 30, 2025
**Version**: 1.0
