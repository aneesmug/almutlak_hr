# Excuse Leave Approval Chain - Database Implementation

## Overview
The Excuse Leave approval system has been updated to use the **database-driven approval chain** configuration from the `approval_request_types` table, replacing the previous hardcoded 2-level approval workflow.

## What Changed

### Before (Hardcoded)
```php
// Old: Fixed 2-level approval chain
$approver_chain = [
    (int)$first_approver['emp_id'],    // Direct Supervisor or Dept Manager
    (int)$hr_senior_bp['emp_id']       // HR Senior BP (fixed)
];
```

### After (Database-Driven)
```php
// New: Dynamic approval chain from database (approval_chain_excuse_leave)
// Fetches configured chain from app_settings table
// Falls back to legacy chain if not configured
```

---

## Implementation Details

### File Modified
- **`includes/ajaxFile/ajaxVacation.php`** - `applyLeave` function (lines ~3330-3550)

### Key Changes

#### 1. **Approval Chain Builder** (New Section)
```php
// Load configured approval chain from app_settings for 'excuse_leave'
$settingName = "approval_chain_excuse_leave";
$query_chain = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '{$settingName}' LIMIT 1");

if ($query_chain && mysqli_num_rows($query_chain) > 0) {
    $row_chain = mysqli_fetch_assoc($query_chain);
    $chainConfig = json_decode($row_chain['setting_value'], true);
    
    foreach ($chainConfig as $step) {
        $user_type = $step['user_type'] ?? '';
        // Resolve approver emp_id from user_type...
        $approver_chain[] = $approver_emp_id;
    }
}
```

**Special Handling:**
- `direct_supervisor` → Uses employee's assigned supervisor
- `dept_manager` → Uses department manager
- Other user types → Query `admin_login` table by `user_type`

#### 2. **Fallback Mechanism**
If no configured chain exists, uses legacy 2-level approval:
1. Direct Supervisor / Department Manager
2. HR Senior BP

#### 3. **Request Type Storage**
Changed from `'vacation_request'` to `'excuse_leave'`:
```php
save_approval_chain($conDB, $request_inv_no, 'excuse_leave', $approver_chain);
```

This allows **independent workflow configuration** for:
- Annual Vacation (`vacation_request`)
- Excuse Leave (`excuse_leave`)
- Loan Requests (`loan_request`)
- etc.

---

## Configuration

### Setup Approval Chain for Excuse Leave

1. **Navigate to App Settings** → **Approval Chain Configuration**
2. Find **"Excuse Leave"** card
3. Click **"Add Approver"** button
4. Select approver roles in desired order:
   - Example: Direct Supervisor → HR Supervisor → HR Payroll

### Available Approver Roles
- Direct Supervisor
- Department Manager
- HR Senior BP
- HR Supervisor
- HR Payroll
- HR Operations
- Finance Officer
- General Manager (GM)
- Administrator
- *...and more*

---

## Database Schema

### Tables Involved

#### 1. `approval_request_types`
Stores request type definitions:
```sql
id              | type_name      | description
--------------- | -------------- | ----------------------------------
excuse_leave    | Excuse Leave   | Sick leave, exam leave, etc
```

#### 2. `app_settings`
Stores approval chain configuration:
```sql
setting_name               | setting_value (JSON)
-------------------------- | ------------------------------------
approval_chain_excuse_leave | [{"level":1,"user_type":"direct_supervisor",...},...]
```

#### 3. `request_approvers`
Tracks approval status for each request:
```sql
request_id    | request_type  | approver_emp_id | approval_order | status
------------- | ------------- | --------------- | -------------- | --------
LV-20251222-... | excuse_leave | 1234           | 1              | pending
LV-20251222-... | excuse_leave | 5678           | 2              | pending
```

---

## Code Flow

### When Employee Submits Excuse Leave

```
1. Employee submits leave request (Sick, Exam, Hajj, etc)
   ↓
2. System fetches configured approval chain from app_settings
   ↓
3. For each step in chain:
   - Resolve user_type to actual emp_id
   - Add to approver_chain array
   ↓
4. If no configured chain found:
   - Use fallback: [Supervisor, HR Senior BP]
   ↓
5. Save approval chain to request_approvers table
   ↓
6. Send notification to first approver
   ↓
7. Return success message to employee
```

### When Approver Reviews Request

```
1. Approver clicks Approve/Reject
   ↓
2. System updates request_approvers for current level
   ↓
3. If approved:
   - Move to next approver in chain
   - Or mark as fully approved if final level
   ↓
4. If rejected:
   - Mark request as rejected
   - Notify employee
```

---

## Testing Checklist

### Before Testing
- [ ] Run database migration: `sql/simple_fix.sql` or `sql/migration_approval_request_types.sql`
- [ ] Verify `approval_request_types` table has `excuse_leave` row
- [ ] Configure approval chain in App Settings

### Test Cases

#### Test 1: Configured Approval Chain
1. Configure chain: Direct Supervisor → HR Supervisor → HR Payroll
2. Submit excuse leave request (e.g., Sick Leave)
3. Verify first approver is Direct Supervisor
4. Approve at each level
5. Verify chain follows configured order

#### Test 2: Fallback Chain (No Configuration)
1. Delete `approval_chain_excuse_leave` from `app_settings`
2. Submit excuse leave request
3. Verify fallback chain: [Supervisor, HR Senior BP]

#### Test 3: Special User Types
1. Configure chain with `direct_supervisor`
2. Verify it uses employee's assigned supervisor
3. Configure chain with `dept_manager`
4. Verify it uses department manager

#### Test 4: Different Leave Types
Test all excuse leave types:
- [ ] Sick Leave
- [ ] Exam Leave
- [ ] Hajj Leave
- [ ] Maternity Leave (female only)
- [ ] Marriage Leave
- [ ] Newborn Leave (male only)
- [ ] Death Leave
- [ ] Business Trip

---

## Benefits

### ✅ Flexibility
- Admin can change approval workflow from UI (no code changes)
- Different approval chains for different request types

### ✅ Scalability
- Add new approver roles without code changes
- Support unlimited approval levels

### ✅ Maintainability
- Single source of truth (database)
- Consistent with vacation request approval system

### ✅ Auditability
- All approval chains logged in `request_approvers` table
- Track approval history per request

---

## Notes

### Important Distinctions

| Feature | Vacation Request | Excuse Leave |
|---------|-----------------|--------------|
| Request Type | `vacation_request` | `excuse_leave` |
| Prefix | `VAC-` | `LV-` |
| Deducts Balance | Yes (annual vacation) | No |
| Attachment Required | Optional | **Required** (1-10 files) |
| Default Chain | Configurable | Configurable (fallback: Supervisor → HR Senior BP) |

### Backward Compatibility
- If no approval chain configured, system uses legacy 2-level approval
- Existing requests continue to work without migration

### Performance
- Approval chain loaded once per request submission
- Cached in `request_approvers` table for tracking

---

## Troubleshooting

### Issue: "No approvers configured"
**Solution:** Configure approval chain in App Settings → Approval tab

### Issue: Approver not found
**Cause:** User type has no active employee
**Solution:** Ensure at least one active employee has the required `user_type` in `admin_login` table

### Issue: Chain not following configuration
**Cause:** Cache or old data
**Solution:**
1. Clear `request_approvers` table for test requests
2. Verify `app_settings` has correct JSON for `approval_chain_excuse_leave`
3. Test with new request

---

## Future Enhancements

### Potential Improvements
1. **Conditional Chains** - Different chains based on:
   - Leave duration (e.g., >5 days requires GM approval)
   - Employee department
   - Leave type (different chain for Business Trip vs Sick Leave)

2. **Parallel Approvals** - Allow multiple approvers at same level
   - "Any one approves" or "All must approve"

3. **Auto-Skip** - Skip approver if they're on leave

4. **Delegation** - Allow approver to delegate to another user

---

## Related Files

- `includes/ajaxFile/ajaxVacation.php` - Main implementation
- `includes/approval_chain_handler.php` - Chain configuration API
- `app_seetings.php` - Admin UI for chain configuration
- `sql/migration_approval_request_types.sql` - Database setup
- `all_applied_vac.php` - View excuse leave requests

---

## Author
- **Updated:** December 22, 2025
- **Feature:** Database-driven approval chain for Excuse Leave
- **Version:** 1.0

---

## Summary

The Excuse Leave approval system now uses the **same database-driven architecture** as Annual Vacation requests, providing:
- Flexible, configurable approval workflows
- Easy administration through UI
- Independent chains per request type
- Full audit trail

Administrators can now customize the excuse leave approval process without code changes, simply by configuring the approval chain in App Settings.
