# Access Control Implementation Guide
## Adding allowed_companies, allowed_departments, allowed_employees checks to AJAX approval functions

Generated: February 17, 2026

---

## Summary

This guide outlines all AJAX files that need to implement access control checks for approval/rejection operations based on an approver's scope restrictions (allowed_companies, allowed_departments, allowed_employees).

## Implementation Status

### ✅ COMPLETED
- **ajaxResignation.php** (Both approve_resignation and reject_resignation)
  - Location: Lines 747-821 (approve), Lines 1033-1100 (reject)
  - Status: Implements full access control checks
  - Verifies allowed_companies, allowed_departments, allowed_employees

---

## 📋 TO BE IMPLEMENTED

### 1. **ajaxLoan.php** - Loan Approval Functions
**File Path:** `/includes/ajaxFile/ajaxLoan.php`

**Functions to Update:**
- `approve_loan()` - Line 250
- `modify_and_approve_loan()` - Line 2054  
- `modify_and_approve_loan_hr_assistant()` - Line 2230
- **Rejection functions** (if exist)

**Current Issue:** 
- Uses ApprovalChainManager::verifyApprover() but doesn't check allowed_companies/departments/employees
- Approvers can access loans for employees outside their scope

**Implementation Required:**
```php
// After verifyApprover check, add:
// Check allowed_companies
// Check allowed_departments  
// Check allowed_employees
```

**Related Flow:**
- Retrieves loan applicant info (emp_id, company)
- Needs to verify approver has access to that employee/company/department

---

### 2. **ajaxVacation.php** - Vacation Approval Functions
**File Path:** `/includes/ajaxFile/ajaxVacation.php`

**Functions to Update:**
- `approveVacation()` - Line 1431
- `processRejoinApproval()` - Line 5818
- Other approval-related functions

**Current Issue:**
- Gets vacation_id and employee_id from vacation record
- Does NOT check if approver has access to that employee
- Approver can approve vacations for any employee

**Key Variables Available:**
- `$employee_id` = employee requesting vacation
- `$current_user_id` = approver (from session)
- `$current_user_dept` = approver's department

**Implementation Required:**
```php
// After retrieving employee_id from vacation record:
// Get approver's scope restrictions from admin_login table
// Verify employee is within approver's allowed_companies/departments/employees
```

---

### 3. **ajaxGeneralRequest.php** - General Request Approval
**File Path:** `/includes/ajaxFile/ajaxGeneralRequest.php`

**Functions to Update:**
- Any action that handles approval/approval workflow related operations

**Current Issue:**
- Need to verify what approval actions exist in this file
- Check if it uses ApprovalChainManager

**Potential Function Names:**
- handle_approval_action()
- approve_general_request()
- Similar patterns

---

### 4. **ajaxEvaluation.php** - Performance Evaluation Approval
**File Path:** `/includes/ajaxFile/ajaxEvaluation.php`

**Potential Functions:**
- Any approval/sign-off related functions
- Acknowledgment functions that require manager approval

**Current Issue:**
- Need to verify evaluation approval workflow
- May need access control for who can evaluate whom

---

## 🔧 Implementation Template

Use this template for each approval function:

```php
// ===== CHECK ACCESS CONTROL =====
$approverId = $current_user_id; // or $_SESSION['empid']
$employeeIdBeingApproved = ...; // From the request/vacation/loan record

// Get approver's allowed scope restrictions
$approverScopeQuery = "SELECT allowed_companies, allowed_departments, allowed_employees 
                       FROM admin_login 
                       WHERE emp_id = ?";
$approverScopeResult = mysqli_query($conDB, $approverScopeQuery);
$approverScopeData = mysqli_fetch_assoc($approverScopeResult);
mysqli_free_result($approverScopeResult);

if ($approverScopeData) {
    $allowedCompanies = !empty($approverScopeData['allowed_companies']) 
        ? json_decode($approverScopeData['allowed_companies'], true) 
        : null;
    $allowedDepts = !empty($approverScopeData['allowed_departments']) 
        ? json_decode($approverScopeData['allowed_departments'], true) 
        : null;
    $allowedEmps = !empty($approverScopeData['allowed_employees']) 
        ? json_decode($approverScopeData['allowed_employees'], true) 
        : null;
    
    // Get employee's company and department
    $empScopeQuery = "SELECT comp_no, dept, emp_id FROM employees 
                      WHERE emp_id = ?";
    $empScopeResult = mysqli_query($conDB, $empScopeQuery);
    $empScope = mysqli_fetch_assoc($empScopeResult);
    mysqli_free_result($empScopeResult);
    
    $hasAccess = true;
    
    // Check company restriction
    if (is_array($allowedCompanies) && !empty($allowedCompanies) && is_array($empScope)) {
        if (!in_array($empScope['comp_no'], $allowedCompanies)) {
            $hasAccess = false;
        }
    }
    
    // Check department restriction
    if ($hasAccess && is_array($allowedDepts) && !empty($allowedDepts) && is_array($empScope)) {
        if (!in_array($empScope['dept'], $allowedDepts)) {
            $hasAccess = false;
        }
    }
    
    // Check employee restriction
    if ($hasAccess && is_array($allowedEmps) && !empty($allowedEmps)) {
        $empId = (int)$empScope['emp_id'];
        if (!in_array($empId, array_map('intval', $allowedEmps))) {
            $hasAccess = false;
        }
    }
    
    if (!$hasAccess) {
        echo json_encode([
            'type' => 'error',
            'title' => 'Access Denied',
            'message' => 'This employee is outside your approval scope.'
        ]);
        exit;
    }
}
```

---

## 📝 Priority Order for Implementation

1. **HIGH PRIORITY** - Used frequently:
   - ajaxVacation.php (`approveVacation`)
   - ajaxLoan.php (`approve_loan`)

2. **MEDIUM PRIORITY**:
   - ajaxGeneralRequest.php (approval functions)
   - ajaxResignation.php (✅ DONE)

3. **LOW PRIORITY**:
   - ajaxEvaluation.php (if applicable)
   - Other niche approval functions

---

## 🔍 How to Find Approval Functions

Search patterns to identify approval/rejection operations:
```
- ajaxType == 'approve...'
- ajaxType == 'reject...'
- action == 'approve...'
- function approve_...(
- verifyApprover() calls
- ApprovalChainManager usage
```

---

## ✅ Testing Checklist

For each function updated:

- [ ] Approver with NO restrictions can approve any employee
- [ ] Approver with company restrictions can only approve employees in those companies
- [ ] Approver with department restrictions can only approve employees in those departments
- [ ] Approver with employee restrictions can only approve employees in that list
- [ ] Approver outside any restriction gets "Access Denied" error
- [ ] Error message is clear and helpful

---

## 📚 Related Files

- `/includes/session_check.php` - Sets up allowed_companies_array, allowed_departments_array, allowed_employees_array
- `/includes/ApprovalChainManager.php` - Handles approval chain verification
- `/includes/helper_functions.php` - Contains getCompanyFilterSQL, getDepartmentFilterSQL, getEmployeeFilterSQL

---

## 💡 Notes

- All scope arrays from admin_login are JSON-encoded and need json_decode() to convert to PHP arrays
- An empty/null scope value means "no restriction" (access to all)
- Multiple restrictions are cumulative (must be in company AND department AND employee list)
- Test with supervisors who have various restriction combinations

