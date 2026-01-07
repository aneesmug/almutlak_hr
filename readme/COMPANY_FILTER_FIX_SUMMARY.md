## Cross-Company Approval Chain Fix - COMPLETED

### Problem Identified
Employee ID 5232 from company "2 Matal" could not see HR Senior BP approvers after their direct manager's approval. The issue was caused by company filters restricting approver lookups to the same company only.

### Root Cause
The `getCompanyFilterSQL()` function was being applied to approver lookups in `ajaxEmployee.php`:
1. **Line 202**: `get_hr_senior_bp` - Used to fetch HR Senior BP users for simple leave approval
2. **Line 231**: `get_hr_team_members` - Used to fetch HR team members for CC notifications

This prevented employees from one company from finding approvers in other companies, breaking the cross-company approval chain.

### Solution Implemented

**File**: `includes/ajaxFile/ajaxEmployee.php`

#### Change 1: Removed company filter from `get_hr_senior_bp` (Lines 199-209)
```php
// BEFORE:
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$sql = "SELECT e.emp_id, e.name, al.user_type 
        FROM employees e 
        JOIN admin_login al ON e.emp_id = al.emp_id 
        WHERE al.user_type = 'hr_senior_bp' AND e.status = 1 ".$company_filter."
        ORDER BY e.name ASC";

// AFTER:
$sql = "SELECT e.emp_id, e.name, al.user_type 
        FROM employees e 
        JOIN admin_login al ON e.emp_id = al.emp_id 
        WHERE al.user_type = 'hr_senior_bp' AND e.status = 1
        ORDER BY e.name ASC";
```

#### Change 2: Removed company filter from `get_hr_team_members` (Lines 227-239)
```php
// BEFORE:
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$sql = "SELECT DISTINCT e.emp_id, e.name, e.email, d.dep_nme, al.user_type 
        FROM employees e 
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
        WHERE e.status = 1 
        AND e.dept = 5 ".$company_filter."
        ORDER BY e.name ASC";

// AFTER:
$sql = "SELECT DISTINCT e.emp_id, e.name, e.email, d.dep_nme, al.user_type 
        FROM employees e 
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
        WHERE e.status = 1 
        AND e.dept = 5
        ORDER BY e.name ASC";
```

### Why This Works

**Approvers vs. Employee Data:**
- ✅ **Keep company filters ON** for: Employee search, employee data, vacation records (user-specific data)
- ✅ **Keep company filters OFF** for: System-wide approvers (HR Senior BP, Finance, GM), HR team members (notifications)

**Cross-Company Approval Chain:**
1. Employee from "2 Matal" applies for vacation
2. Manager from "2 Matal" approves
3. System searches for HR Senior BP without company restriction
4. HR Senior BP from ANY company (not just "2 Matal") becomes available
5. Approval chain continues cross-company ✅

### Impact

**Before Fix:**
- Employee 5232 from "2 Matal" → Manager approves → "No HR Senior BP found" ❌
- Cross-company approvals blocked

**After Fix:**
- Employee 5232 from "2 Matal" → Manager approves → HR Senior BP available from any company ✅
- Cross-company approvals work correctly

### Files Modified
- `includes/ajaxFile/ajaxEmployee.php` (2 changes, 18 lines modified)

### Testing
The fix allows employees from any company to access approvers from any company in the approval chain. Specifically:
- Employee 5232 from "2 Matal" can now find HR Senior BP approvers
- HR team members from other companies can receive CC notifications
- Company-specific filters remain active for employee/vacation data (not affected by this change)

### Related Previous Fix
This complements the earlier **double deduction bug fix** (Phase 1-6) where vacation days were being deducted twice. Both fixes ensure the vacation system works correctly across companies.
