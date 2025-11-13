# Database Migration Guide - emp_loan Table Update

## Overview
This guide covers updating the `emp_loan` table from the old interest-based system to the new loan type system (End of Service, Housing, Advance Salary).

## Migration Options

### Option 1: Update Existing Table (Recommended for Production)
This keeps your existing data and adds new columns.

**File:** `update_emp_loan_table.sql`

```bash
mysql -u root -p almutlak_db < update_emp_loan_table.sql
```

**What it does:**
- ✅ Keeps all existing loan records
- ✅ Adds new loan types: `end_of_service`, `housing`, `advance_salary`
- ✅ Adds `installments` column
- ✅ Adds `reason` column
- ✅ Adds approval tracking columns
- ✅ Adds rejection tracking columns
- ✅ Adds modification tracking columns
- ✅ Creates performance indexes
- ✅ Keeps legacy approval status columns for backward compatibility

### Option 2: Fresh Table (For Development/Testing Only)
This completely recreates the table with the new structure.

**File:** `emp_loan_table_complete.sql`

⚠️ **WARNING:** This will **DELETE ALL EXISTING LOAN DATA**!

```bash
mysql -u root -p almutlak_db < emp_loan_table_complete.sql
```

## New Table Structure

### New Columns Added:

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `installments` | int(11) | 1 | Number of monthly installments (1-12 for EOS, 1-6 for Housing, 1 for Advance) |
| `reason` | text | NULL | Reason for loan application |
| `current_approval_level` | int(11) | 1 | Current level in approval chain |
| `approved_by_user_ids` | text | NULL | JSON array of approver user IDs |
| `rejected_by` | varchar(50) | NULL | User ID who rejected the loan |
| `rejection_reason` | text | NULL | Reason for rejection |
| `rejection_date` | datetime | NULL | When loan was rejected |
| `modified_by` | varchar(50) | NULL | User ID who modified loan (HR) |
| `modification_note` | text | NULL | Note about modifications |
| `original_amount` | decimal(10,2) | NULL | Original amount before HR modification |
| `original_installments` | int(11) | NULL | Original installments before modification |

### Updated Columns:

| Column | Old | New |
|--------|-----|-----|
| `loan_type` | enum('regular','emergency') | enum('regular','emergency','end_of_service','housing','advance_salary') |
| `interest_rate` | DEFAULT 1.40 | DEFAULT 0.00 |
| `end_date` | NOT NULL | NULL (nullable) |

### New Indexes:
- `idx_loan_type` - Faster queries by loan type
- `idx_emp_status` - Faster employee loan status lookups
- `idx_current_approval` - Faster approval chain queries

## Approval Chain System

The new system uses a **generic approval chain** instead of hardcoded status columns.

### Approval Flow:
1. **Employee** applies for loan → `status = 'pending'`, `current_approval_level = 1`
2. **Department Manager/Supervisor** approves → `current_approval_level = 2`
3. **HR Assistant** approves → `current_approval_level = 3`
4. **HR Manager** approves → `current_approval_level = 4`
5. **Finance Manager** approves → `current_approval_level = 5`
6. **GM** approves → `current_approval_level = 6`
7. **Finance Assistant** processes → `status = 'approved'`, `finance_assistant_status = 'processed'`

### Loan Type Rules:

#### End of Service Loan
- **Range:** SAR 1,000 - 20,000
- **Installments:** 1-12 months
- **Based on:** End of service benefit (no cap)
- **Monthly Deduction:** `loan_amount / installments`

#### Housing Loan
- **Range:** Up to 6 months housing allowance (max SAR 20,000)
- **Installments:** 1-6 months
- **Restrictions:** 
  - Must have housing allowance
  - 1 year gap between housing loans
  - Previous housing loan must be fully paid
- **Monthly Deduction:** Full housing allowance

#### Advance Salary
- **Range:** Up to 50% of total monthly salary
- **Installments:** 1 month only
- **Monthly Deduction:** Full amount in next payroll

## Data Migration Notes

### For Existing Loans:
```sql
-- Set installments for old loans based on calculation
UPDATE emp_loan 
SET installments = CEILING(total_payable / monthly_deduction) 
WHERE installments = 1 AND monthly_deduction > 0;

-- Convert old 'regular' loans to 'end_of_service' (optional)
UPDATE emp_loan 
SET loan_type = 'end_of_service' 
WHERE loan_type = 'regular';

-- Set interest to 0 for new loan types
UPDATE emp_loan 
SET interest_rate = 0.00 
WHERE loan_type IN ('end_of_service', 'housing', 'advance_salary');
```

## Legacy Column Handling

The migration script **KEEPS** the old approval columns for backward compatibility:
- `dept_manager_status`
- `hr_manager_status`
- `hr_assistant_status`
- `finance_manager_status`
- `gm_status`
- `finance_assistant_status`

### To Remove Legacy Columns (Optional):
If you want to fully migrate to the new generic approval system, uncomment these lines in `update_emp_loan_table.sql`:

```sql
ALTER TABLE `emp_loan` 
DROP COLUMN `dept_manager_status`,
DROP COLUMN `hr_manager_status`,
DROP COLUMN `hr_assistant_status`,
DROP COLUMN `finance_manager_status`,
DROP COLUMN `gm_status`,
DROP COLUMN `finance_assistant_status`;
```

⚠️ **Warning:** This will delete approval history for existing loans!

## Verification Steps

After running the migration:

### 1. Check table structure:
```sql
SHOW COLUMNS FROM emp_loan;
```

### 2. Verify loan types:
```sql
SELECT DISTINCT loan_type FROM emp_loan;
```

### 3. Check installments:
```sql
SELECT emp_id, loan_amount, installments, monthly_deduction 
FROM emp_loan 
WHERE loan_type IN ('end_of_service', 'housing', 'advance_salary');
```

### 4. Verify indexes:
```sql
SHOW INDEXES FROM emp_loan;
```

## Rollback Plan

If you need to rollback the migration:

### 1. Backup your database BEFORE migration:
```bash
mysqldump -u root -p almutlak_db emp_loan > emp_loan_backup_$(date +%Y%m%d).sql
```

### 2. To restore:
```bash
mysql -u root -p almutlak_db < emp_loan_backup_YYYYMMDD.sql
```

## Testing Checklist

After migration, test these scenarios:

- [ ] Apply for End of Service loan (1k-20k, 1-12 months)
- [ ] Apply for Housing loan (check allowance validation)
- [ ] Apply for Advance Salary loan (check 50% limit)
- [ ] Verify approval chain works correctly
- [ ] Test HR modification feature
- [ ] Test loan rejection with reason
- [ ] Verify monthly deduction calculations
- [ ] Check loan balance and payment tracking
- [ ] Test Finance Assistant disbursement flow

## Support Files

1. `update_emp_loan_table.sql` - Incremental migration (recommended)
2. `emp_loan_table_complete.sql` - Fresh table structure (dev/test only)
3. `insert_loan_translations.sql` - Translation keys for UI
4. `LOAN_TRANSLATION_IMPLEMENTATION.md` - Translation system guide

## Questions?

If you encounter any issues during migration:
1. Check MySQL error log
2. Verify you have ALTER privilege on the database
3. Ensure no active transactions are locking the table
4. Take a backup before trying again
