# Database Updates on Vacation Approval - Summary

## ✅ Current Implementation Status

The system is **already correctly configured** to update `emp_vacation_balance` and `employees.fly` **only at the last approval step**.

---

## How It Works

### 1. Approval Chain is Built at Submission
When an employee submits a vacation request, the `build_vacation_approval_chain` endpoint is called to build the complete approval chain based on:
- Supervisor hierarchy
- Vacation parameters (salary type, fly type, remarks)
- Assigned assets (for clearance teams)

### 2. Approvals Progress Through the Chain
Each approver in the chain:
- Receives notification
- Reviews and approves/rejects
- System moves to next approver in the chain

### 3. Final Approval Triggers Database Updates

The `handle_approval_action()` function in `helper_functions.php` (lines 1076-1484) handles this logic:

```php
// Check if there's a next approver
$find_next_sql = "SELECT * FROM request_approvers 
                  WHERE request_inv_no = ? AND approval_level = ? 
                  AND status = 'awaiting' LIMIT 1";

if (next approver exists) {
    // NOT final approval - move to next level
    // Update next approver to 'pending'
    // Send notification to next approver
    // NO database updates to balance/fly
} else {
    // THIS IS FINAL APPROVAL - no more approvers
    
    // 1. Update emp_vacation status to 'approved'
    UPDATE emp_vacation SET current_status = 'approved';
    
    // 2. Update vacation balance
    if (vacation_request) {
        update_vacation_balance_on_approval($conDB, $vacation_id);
        // This deducts vacation days from emp_vacation_balance
    }
    
    // 3. Set employees.fly flag
    if (vac_type != 'Encashed') {
        UPDATE employees SET fly = 1 WHERE emp_id = ?;
    }
    
    // 4. Notify employee that vacation is fully approved
}
```

---

## Specific Scenarios

### Scenario 1: Vacation with GR Officer (Fly Vacation)
**Chain:** Supervisor → Manager → HR BP → Asset Teams → HR Payroll → **GR Officer (FINAL)**

1. Supervisor approves → moves to Manager
2. Manager approves → moves to HR BP
3. HR BP approves → moves to Asset Teams
4. Asset Teams approve → moves to HR Payroll
5. HR Payroll approves → moves to GR Officer
6. **GR Officer approves** (enters ticket_pay & permit_fee):
   - ✅ `emp_vacation.ticket_pay` updated
   - ✅ `emp_vacation.permit_fee` updated
   - ✅ `emp_vacation.current_status = 'approved'`
   - ✅ `emp_vacation_balance.remaining_balance` reduced by vacation days
   - ✅ `employees.fly = 1` (employee is on vacation)

### Scenario 2: Vacation with HR Payroll (No Fly)
**Chain:** Supervisor → Manager → HR BP → Asset Teams → **HR Payroll (FINAL)**

1-4. Same as above
5. **HR Payroll approves**:
   - ✅ `emp_vacation.current_status = 'approved'`
   - ✅ `emp_vacation_balance.remaining_balance` reduced
   - ✅ `employees.fly = 1`
   - ❌ No ticket/permit fees (GR Officer not in chain)

### Scenario 3: Simple Leave (No Assets, End-of-Service Pay)
**Chain:** Supervisor → **HR Senior BP (FINAL)**

1. Supervisor approves → moves to HR BP
2. **HR Senior BP approves**:
   - ✅ `emp_vacation.current_status = 'approved'`
   - ✅ `emp_vacation_balance.remaining_balance` reduced
   - ✅ `employees.fly = 1`
   - ❌ No HR Payroll (vacation_salary_type = 'end_of_service')
   - ❌ No GR Officer (not fly vacation)

---

## Code Locations

### 1. Final Approval Detection
**File:** `includes/helper_functions.php`
**Function:** `handle_approval_action()`
**Lines:** ~1240-1300

```php
// Find next approver
$find_next_sql = "SELECT * FROM request_approvers 
                  WHERE request_inv_no = ? AND approval_level = ?";

if (mysqli_num_rows($find_next_result) > 0) {
    // There IS a next approver - NOT final
} else {
    // NO next approver - THIS IS FINAL APPROVAL
    // Update balance and fly flag HERE
}
```

### 2. Balance Update Function
**File:** `includes/helper_functions.php`
**Function:** `update_vacation_balance_on_approval()`
**Lines:** ~2143-2280

**Logic:**
- Gets vacation details (emp_id, vacdays, vac_type)
- Checks if vacation type is deductible
- Fetches employee's current balance from `emp_vacation_balance`
- Calculates new balance: `remaining_balance - vacdays`
- Updates `emp_vacation_balance` table

### 3. Fly Flag Update
**File:** `includes/helper_functions.php`
**Lines:** ~1334-1346

```php
// Set employee fly status on final approval (except 'Encashed')
if (!empty($vacation_emp_id) && strtolower($vacation_type) !== 'encashed') {
    $sql_set_fly = "UPDATE employees SET fly = 1 WHERE emp_id = ?";
    // Execute query
}
```

### 4. Payment Fields Update
**File:** `includes/ajaxFile/ajaxVacation.php`
**Endpoint:** `approveVacation`
**Lines:** ~546-557

```php
// If payments were included (by GR Officer), update the main table
if ($ticket_pay > 0 || $permit_fee > 0) {
    $sql_pay = "UPDATE emp_vacation 
                SET ticket_pay = ?, permit_fee = ? 
                WHERE id = ?";
    // Execute query
}
```

---

## Database Tables Updated on Final Approval

### 1. `emp_vacation`
```sql
UPDATE emp_vacation SET
    current_status = 'approved',
    ticket_pay = ?,      -- Only if GR Officer entered value
    permit_fee = ?       -- Only if GR Officer entered value
WHERE id = ?;
```

### 2. `emp_vacation_balance`
```sql
UPDATE emp_vacation_balance SET
    used_days = used_days + ?,
    remaining_balance = remaining_balance - ?
WHERE emp_id = ? AND contract_id = ?;
```

### 3. `employees`
```sql
UPDATE employees SET
    fly = 1
WHERE emp_id = ?;
```

---

## Testing Checklist

To verify the final approval logic works correctly:

### Test 1: GR Officer is Final Approver
- [ ] Submit fly vacation with assets
- [ ] Approve through all levels EXCEPT GR Officer
- [ ] Check `emp_vacation_balance` - should NOT be updated yet
- [ ] Check `employees.fly` - should still be 0
- [ ] GR Officer enters ticket_pay and permit_fee and approves
- [ ] Verify `emp_vacation_balance.remaining_balance` is reduced
- [ ] Verify `employees.fly = 1`
- [ ] Verify `emp_vacation.ticket_pay` and `permit_fee` are saved

### Test 2: HR Payroll is Final Approver
- [ ] Submit local vacation with `vacation_salary_type='payroll'`
- [ ] Approve through all levels EXCEPT HR Payroll
- [ ] Check balance and fly flag - should NOT be updated yet
- [ ] HR Payroll approves
- [ ] Verify balance is reduced and fly=1

### Test 3: HR Senior BP is Final Approver
- [ ] Submit simple leave with no assets
- [ ] Supervisor approves
- [ ] Check balance and fly flag - should NOT be updated yet
- [ ] HR Senior BP approves
- [ ] Verify balance is reduced and fly=1

### Test 4: Verify Intermediate Approvals Don't Update
- [ ] Submit any vacation request
- [ ] At each approval level BEFORE final:
  - [ ] Check `emp_vacation_balance` - should remain unchanged
  - [ ] Check `employees.fly` - should remain 0
  - [ ] Check `emp_vacation.current_status` - should be 'pending_approval'

---

## Summary

✅ **The system is already correctly implemented!**

- `emp_vacation_balance` is updated ONLY on final approval
- `employees.fly` is set to 1 ONLY on final approval  
- Payment fields (ticket_pay, permit_fee) are saved when GR Officer approves
- The logic is in `handle_approval_action()` which detects when there are no more approvers in the chain

**No changes needed** - the implementation follows the requirement correctly.

---

## Additional Notes

### When is `employees.fly` reset back to 0?
This is handled by a separate process (likely a scheduled job or manual HR action) when the employee returns from vacation. This is NOT part of the approval flow.

### What if an employee has multiple vacation requests?
Each vacation request is independent. The `fly` flag represents the employee's current vacation status. Only the MOST RECENT approved vacation should set `fly=1`.

### Encashed Vacations
For "Encashed" vacation type (selling vacation days for cash):
- Balance IS deducted
- `employees.fly` is NOT set to 1 (employee not actually taking time off)
