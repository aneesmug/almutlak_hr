# Generic Approval Chain System for Loans

## Overview
The loan system now uses the same generic approval chain pattern as vacation and smart requests, instead of hardcoded individual status columns.

## Key Components

### 1. Tables Used

#### `emp_loan` - Main loan table
- `inv_no` (NEW): Unique identifier like `LOAN-20251111-5127-a1b2`
- `status`: Generic status (pending_level_1 through pending_level_6, approved, rejected, paid)
- `current_approval_level`: Current level in approval chain (1-6)

#### `approval_request_types` - Request type registry
```sql
INSERT INTO approval_request_types (id, type_name, main_table_name) 
VALUES (2, 'loan_request', 'emp_loan');
```

#### `request_approvers` - Approval chain tracker
Stores the approval chain for each loan with:
- `request_inv_no`: Links to `emp_loan.inv_no`
- `request_type_id`: 2 for loan_request
- `approver_id`: Employee ID of approver
- `approval_level`: 1-6 representing the approval level
- `status`: pending, approved, rejected, awaiting
- `action_date`: When action was taken

#### `smt_request_status` - Approval history log
Records each approval action:
- `inv_no`: Loan invoice number
- `emp_id`: Approver ID
- `status`: approved_level_1, approved_level_2, etc. or rejected
- `note`: Approval/rejection notes
- `created_at`: Timestamp

## Approval Flow

### 6-Level Approval Chain:

| Level | Role | Status | Table Column Check |
|-------|------|--------|-------------------|
| 1 | Department Manager/Supervisor | `pending_level_1` | `request_approvers.approval_level = 1 AND status = 'pending'` |
| 2 | HR Assistant | `pending_level_2` | `request_approvers.approval_level = 2 AND status = 'pending'` |
| 3 | HR Manager | `pending_level_3` | `request_approvers.approval_level = 3 AND status = 'pending'` |
| 4 | Finance Manager | `pending_level_4` | `request_approvers.approval_level = 4 AND status = 'pending'` |
| 5 | GM | `pending_level_5` | `request_approvers.approval_level = 5 AND status = 'pending'` |
| 6 | Finance Assistant | `pending_level_6` | `request_approvers.approval_level = 6 AND status = 'pending'` |
| - | Completed | `approved` | All levels approved + disbursement complete |
| - | Rejected | `rejected` | Any level rejected |
| - | Paid Off | `paid` | All payments received |

## Implementation Steps

### Step 1: Run Database Migration
```bash
mysql -u root -p almutlak_db < migrate_to_generic_approval_system.sql
```

This will:
- Add `inv_no` column to `emp_loan`
- Generate `inv_no` for existing loans
- Create approval chains for pending loans
- Update status values to generic pattern

### Step 2: Update Backend Code

#### Creating New Loan (apply_for_loan)
```php
require_once __DIR__ . '/../loan_approval_chain_helpers.php';

// Generate unique invoice number
$inv_no = generate_loan_inv_no($emp_id);

// Insert loan
$stmt = $conDB->prepare("
    INSERT INTO emp_loan (inv_no, emp_id, loan_type, loan_amount, installments, reason, 
                          total_payable, monthly_deduction, start_date, end_date, 
                          status, current_approval_level) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_level_1', 1)
");
$stmt->bind_param("sssdissddss", $inv_no, $emp_id, $loan_type, $loan_amount, 
                  $installments, $reason, $total_payable, $monthly_deduction, 
                  $start_date, $end_date);
$stmt->execute();

// Create approval chain
create_loan_approval_chain($conDB, $inv_no, $emp_id);
```

#### Approving Loan (approve_loan)
```php
require_once __DIR__ . '/../loan_approval_chain_helpers.php';

// Get loan details
$stmt = $conDB->prepare("SELECT inv_no, current_approval_level FROM emp_loan WHERE id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

// Approve at current level
approve_loan_level($conDB, $loan['inv_no'], $approver_id, 
                   $loan['current_approval_level'], $note);
```

#### Rejecting Loan (reject_loan)
```php
require_once __DIR__ . '/../loan_approval_chain_helpers.php';

// Get loan details
$stmt = $conDB->prepare("SELECT inv_no, current_approval_level FROM emp_loan WHERE id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();

// Reject
reject_loan_request($conDB, $loan['inv_no'], $approver_id, 
                   $loan['current_approval_level'], $rejection_reason);
```

### Step 3: Update Frontend Display

#### Checking User Permission to Act
```php
// Get current pending approver
$current_approver = get_current_loan_approver($conDB, $loan['inv_no']);

// Check if logged-in user can approve
$can_approve = false;
if ($current_approver && $current_approver['approver_id'] == $username) {
    $can_approve = true;
}

// Or admin can always approve
if ($user_role == 'administrator') {
    $can_approve = true;
}
```

#### Status Display Mapping
```php
$status_labels = [
    'pending_level_1' => __('pending_department_manager'),
    'pending_level_2' => __('pending_hr_assistant'),
    'pending_level_3' => __('pending_hr_manager'),
    'pending_level_4' => __('pending_finance_manager'),
    'pending_level_5' => __('pending_gm'),
    'pending_level_6' => __('pending_final_processing'),
    'approved' => __('approved_and_processed'),
    'paid' => __('paid_and_closed'),
    'rejected' => __('rejected')
];
```

## Benefits of Generic System

### ✅ Flexibility
- Easy to add/remove approval levels
- Can customize chain per department or loan type
- Reuses existing approval infrastructure

### ✅ Consistency
- Same pattern as vacation/smart requests
- Developers familiar with one system know all systems
- Centralized approval tracking

### ✅ Better Tracking
- Complete audit trail in `smt_request_status`
- Can query approval history across all request types
- Easy to generate reports

### ✅ Maintainability
- No hardcoded status columns
- Changes to approval chain don't require schema changes
- Easier to test and debug

## Example Data Flow

### New Loan Application:
```sql
-- 1. Insert loan
INSERT INTO emp_loan (inv_no, emp_id, ..., status, current_approval_level)
VALUES ('LOAN-20251111-5127-a1b2', '5127', ..., 'pending_level_1', 1);

-- 2. Create approval chain
INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
VALUES 
('LOAN-20251111-5127-a1b2', 2, '5430', 1, 'pending'),    -- Dept Manager
('LOAN-20251111-5127-a1b2', 2, '5455', 2, 'awaiting'),   -- HR Assistant
('LOAN-20251111-5127-a1b2', 2, '3431', 3, 'awaiting'),   -- HR Manager
('LOAN-20251111-5127-a1b2', 2, '5021', 4, 'awaiting'),   -- Finance Manager
('LOAN-20251111-5127-a1b2', 2, '3401', 5, 'awaiting'),   -- GM
('LOAN-20251111-5127-a1b2', 2, '2105', 6, 'awaiting');   -- Finance Assistant
```

### Level 1 Approval:
```sql
-- 1. Update approver status
UPDATE request_approvers 
SET status = 'approved', action_date = NOW() 
WHERE request_inv_no = 'LOAN-20251111-5127-a1b2' AND approval_level = 1;

-- 2. Log approval
INSERT INTO smt_request_status (inv_no, emp_id, status, note)
VALUES ('LOAN-20251111-5127-a1b2', '5430', 'approved_level_1', 'Approved');

-- 3. Move to level 2
UPDATE request_approvers 
SET status = 'pending' 
WHERE request_inv_no = 'LOAN-20251111-5127-a1b2' AND approval_level = 2;

-- 4. Update loan status
UPDATE emp_loan 
SET status = 'pending_level_2', current_approval_level = 2 
WHERE inv_no = 'LOAN-20251111-5127-a1b2';
```

### Final Approval (Level 6):
```sql
-- After level 6 approval
UPDATE emp_loan 
SET status = 'approved', current_approval_level = 7 
WHERE inv_no = 'LOAN-20251111-5127-a1b2';
```

## Migration Checklist

- [x] Create migration SQL script
- [x] Create helper functions file
- [ ] Update `apply_for_loan()` function
- [ ] Update `approve_loan()` function
- [ ] Update `reject_loan()` function
- [ ] Update `all_applied_loan.php` to use generic statuses
- [ ] Update status filter dropdowns
- [ ] Update translation keys
- [ ] Test complete approval flow
- [ ] Test rejection at each level
- [ ] Test modification by HR/GM
- [ ] Verify approval history display

## Helper Functions Reference

### `generate_loan_inv_no($emp_id)`
Generates unique invoice number: `LOAN-20251111-5127-a1b2`

### `create_loan_approval_chain($conDB, $inv_no, $emp_id)`
Creates 6-level approval chain in `request_approvers` table

### `get_current_loan_approver($conDB, $inv_no)`
Returns current pending approver details

### `approve_loan_level($conDB, $inv_no, $approver_id, $approval_level, $note)`
Approves current level and moves to next

### `reject_loan_request($conDB, $inv_no, $approver_id, $approval_level, $reason)`
Rejects loan at current level

## Testing Queries

### View approval chain for a loan:
```sql
SELECT ra.*, e.name as approver_name
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
WHERE ra.request_inv_no = 'LOAN-20251111-5127-a1b2'
AND ra.request_type_id = 2
ORDER BY ra.approval_level;
```

### View approval history:
```sql
SELECT * FROM smt_request_status
WHERE inv_no = 'LOAN-20251111-5127-a1b2'
ORDER BY created_at;
```

### Find loans pending my approval:
```sql
SELECT l.*, ra.approval_level
FROM emp_loan l
JOIN request_approvers ra ON l.inv_no = ra.request_inv_no
WHERE ra.approver_id = '5430'
AND ra.status = 'pending'
AND ra.request_type_id = 2;
```
