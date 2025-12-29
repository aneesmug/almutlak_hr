# Fix for SQL Error: Column 'payment_amount' not found

## Issue
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'payment_amount' in 'field list'
```

## Root Cause
The `ApprovalChainManager.php` class was attempting to store payment tracking data (`payment_amount` and `payment_proof_path`) in the `request_approvers` table, but these columns did not exist in the database schema.

## Solution Applied
### 1. Database Migration
Added two new columns to the `request_approvers` table:

- **`payment_amount` (DECIMAL(10,2))**: Stores the amount paid by the payer (for payer-level approvers)
- **`payment_proof_path` (VARCHAR(500))**: Stores the path to the payment proof document

### 2. Migration Files Created
- `sql/add_payment_columns_to_request_approvers.sql` - Full migration with comments
- `sql/add_payment_columns.sql` - Clean migration file for production
- `migrate_payment_columns.php` - PHP migration script (already executed)

### 3. Table Structure After Migration
```
request_approvers:
  id                   (int)
  request_inv_no       (varchar)
  request_type_id      (int)
  approver_id          (int)
  approval_level       (int)
  status               (enum)
  note                 (text)
  payment_amount       (decimal) ✅ NEW
  payment_proof_path   (varchar) ✅ NEW
  action_date          (datetime)
```

## Impact
- ✅ Fixes the SQL error in `ApprovalChainManager::processPayerPayment()`
- ✅ Enables payment tracking for vacation/leave payer approvals
- ✅ Maintains backward compatibility with existing data
- ✅ Adds performance index for payment status queries

## Files Modified
- Database: `request_approvers` table structure updated
- No application code changes needed - the schema now matches the code requirements

## Next Steps
If you encounter any similar errors:
1. Review the `ApprovalChainManager` class for any new column references
2. Check that the database schema matches the application requirements
3. Run migrations as needed from the `sql/` directory
