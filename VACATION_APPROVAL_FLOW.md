# Vacation Approval Flow - Complete Implementation

## Overview
This document describes the complete vacation approval workflow implemented in the Al-Mutlak HR system.

## Important: Database Updates on Final Approval

### When does `emp_vacation_balance` get updated?
- **Only on FINAL approval** (when no more approvers in the chain)
- Updated by `update_vacation_balance_on_approval()` function in `helper_functions.php`
- Deducts vacation days from employee's remaining balance
- Only for deductible vacation types: 'Fly' (annual/emergency), 'Local Vacation', 'Encashed'
- NOT deducted for: 'Sick Leave', 'Business Trip', etc.

### When does `employees.fly` flag get set?
- **Only on FINAL approval** (when no more approvers in the chain)
- Set to `1` in the `handle_approval_action()` function in `helper_functions.php`
- Logic:
  ```php
  // Set fly=1 EXCEPT for 'Encashed' vacation type
  if (!empty($vacation_emp_id) && strtolower($vacation_type) !== 'encashed') {
      UPDATE employees SET fly = 1 WHERE emp_id = ?
  }
  ```
- When GR Officer is in the chain, they are the FINAL approver
- GR Officer must enter `ticket_pay` and `permit_fee` before approving
- After GR Officer approval, both `emp_vacation_balance` and `employees.fly` are updated

### Final Approval Logic
```
IF (GR Officer in approval chain):
    Last Approver = GR Officer
    GR Officer → Enters ticket_pay & permit_fee → Approves
    → handle_approval_action() detects FINAL approval (no next approver)
    → emp_vacation_balance updated (deduct vacation days)
    → employees.fly = 1 (employee is on vacation)
    → emp_vacation.current_status = 'approved'
    
ELSE IF (HR Payroll in chain):
    Last Approver = HR Payroll
    HR Payroll → Approves
    → handle_approval_action() detects FINAL approval
    → emp_vacation_balance updated
    → employees.fly = 1
    → emp_vacation.current_status = 'approved'
    
ELSE:
    Last Approver = Asset Team OR HR Senior BP (whichever is last)
    Last Approver → Approves
    → handle_approval_action() detects FINAL approval
    → emp_vacation_balance updated  
    → employees.fly = 1
    → emp_vacation.current_status = 'approved'
```

---

## Complete Approval Flow

### Step-by-Step Flow:
1. **Employee** submits vacation request
2. **Assigned Supervisor** OR **Department Manager** (if no supervisor) - Level 1
3. **Supervisor's Direct Manager** (if supervisor has a manager) - Level 2
4. **HR Senior BP** - Always required
5. **Asset Clearance Teams** - Based on assigned assets:
   - **IT Team** - if employee has Laptop/Computer assigned
   - **Administration Team** - if employee has Mobile Phone assigned
   - **Transportation Team** - if employee has Car assigned
6. **HR Team** - CC notification only (informational, not approval)
7. **HR Payroll** - ONLY if `vacation_salary_type = 'payroll'`
   - Skipped if `vacation_salary_type = 'end_of_service'`
8. **GR Officer** - ONLY if `fly_type = 'annual'` AND `remarks` contains "fly"
   - This is the FINAL step
   - GR Officer enters ticket and exit-reentry fees
   - Sets `employees.fly = 1` on final approval

## Database Schema

### emp_vacation Table (Key Fields):
- `vacation_salary_type` ENUM('payroll', 'end_of_service') - Determines if HR Payroll step is included
- `fly_type` VARCHAR - 'annual' or 'emergency'
- `remarks` VARCHAR - Contains notes like "fly", "family vacation", etc.
- `ticket_pay` DECIMAL - Ticket allowance (entered by GR Officer if fly)
- `permit_fee` DECIMAL - Exit-reentry permit fee (entered by GR Officer if fly)

### employees Table:
- `supervisor_id` VARCHAR - Direct supervisor assignment
- `fly` TINYINT - Flag set to 1 when employee is on flying vacation (final GR Officer approval)

### User Types (admin_login table):
- `hr_senior_bp` - HR Senior Business Partner
- `hr_payroll` - HR Payroll Manager
- `gr_officer` - General Relations Officer

## Implementation Files

### 1. ajaxEmployee.php - New Endpoint
**Endpoint:** `build_vacation_approval_chain`

**Input Parameters:**
```php
$_POST['emp_id']                  // Employee ID (required)
$_POST['vacation_salary_type']    // 'payroll' or 'end_of_service'
$_POST['fly_type']                // 'annual' or 'emergency'
$_POST['remarks']                 // Vacation notes/remarks
```

**Output:**
```json
{
    "status": 200,
    "chain": [5430, 4120, 5408, 6001, 6002, 3431, 5021],
    "chain_details": [
        {"emp_id": 5430, "name": "John Doe", "label": "Direct Supervisor", "level": 1},
        {"emp_id": 4120, "name": "Jane Smith", "label": "Supervisor's Manager", "level": 2},
        {"emp_id": 5408, "name": "HR Senior", "label": "HR Senior BP", "level": 3},
        {"emp_id": 6001, "name": "IT Manager", "label": "IT Team (Asset Clearance)", "level": 4},
        {"emp_id": 6002, "name": "Admin Manager", "label": "Administration Team (Asset Clearance)", "level": 5},
        {"emp_id": 3431, "name": "Payroll Manager", "label": "HR Payroll", "level": 6},
        {"emp_id": 5021, "name": "GR Officer", "label": "GR Officer (Final - Ticket & Exit Fee)", "level": 7, "is_final": true}
    ],
    "total_levels": 7,
    "flow_type": "with_gr_officer"
}
```

### 2. Integration Points

#### Vacation Submission (ajaxVacation.php):
- **TODO:** Update `applyVacation` endpoint to call `build_vacation_approval_chain`
- Save the complete chain to `request_approvers` table during submission
- Set `current_approval_level = 1` and `current_status = 'pending_approval'`

#### Vacation Approval (all_applied_vac.php):
- **TODO:** Remove asset-chain building logic from Level 1 approval
- Level 1 should ONLY show supervisor/dept manager approval
- All subsequent approvals follow the pre-built chain from `request_approvers` table

#### Final Approval (helper_functions.php):
- `handle_approval_action()` already sets `employees.fly = 1` on final approval
- Final approval is detected when `current_approval_level` reaches the last level in chain

## Asset Type Mapping

Assets are matched using substring search on `assets.name`:

| Asset Type | Keywords | Department | Example Assets |
|------------|----------|------------|----------------|
| IT | laptop, computer, notebook | IT | "Dell Laptop", "HP Computer" |
| Administration | mobile, phone, cell | Administration | "iPhone 12", "Samsung Mobile" |
| Transportation | car, vehicle, transport | Transportation | "Toyota Camry", "Company Van" |

## Conditional Flow Examples

### Example 1: Simple Annual Vacation (No Assets, Payroll, No Fly)
```
Employee → Supervisor → Supervisor's Manager → HR Senior BP → [END]
```

### Example 2: Annual Vacation with Laptop (Payroll, No Fly)
```
Employee → Supervisor → HR Senior BP → IT Team → HR Payroll → [END]
```

### Example 3: Complete Flow (Laptop + Mobile, Payroll, Fly)
```
Employee → Supervisor → Supervisor's Manager → HR Senior BP → IT Team → 
Administration Team → HR Payroll → GR Officer (enters ticket/permit fees) → [END]
Sets employees.fly = 1
```

### Example 4: Annual Vacation (End of Service Salary, No Fly)
```
Employee → Supervisor → HR Senior BP → [END]
(HR Payroll step skipped)
```

### Example 5: Emergency Vacation
```
Employee → Supervisor → HR Senior BP → [END]
(GR Officer step skipped, asset clearance may still apply)
```

## Testing Checklist

- [ ] Test with employee having supervisor with manager
- [ ] Test with employee having supervisor without manager
- [ ] Test with employee having no supervisor (dept manager)
- [ ] Test with laptop assigned (IT clearance)
- [ ] Test with mobile assigned (Admin clearance)
- [ ] Test with car assigned (Transportation clearance)
- [ ] Test with multiple assets (all clearances)
- [ ] Test with no assets (no clearance steps)
- [ ] Test with vacation_salary_type = 'payroll' (HR Payroll included)
- [ ] Test with vacation_salary_type = 'end_of_service' (HR Payroll skipped)
- [ ] Test with fly_type = 'annual' and remarks = 'fly' (GR Officer included)
- [ ] Test with fly_type = 'annual' and remarks = 'not fly' (GR Officer skipped)
- [ ] Test with fly_type = 'emergency' (GR Officer skipped)
- [ ] Verify employees.fly = 0 until final approval
- [ ] Verify employees.fly = 1 only after GR Officer approval (if fly vacation)
- [ ] Verify ticket_pay and permit_fee entered by GR Officer

## Next Steps

1. **Update ajaxVacation.php** - Integrate `build_vacation_approval_chain` into vacation submission
2. **Update all_applied_vac.php** - Remove manual chain building from Level 1 approval
3. **Test end-to-end** - Verify complete flow with all scenarios
4. **Update UI** - Show approval chain details to employee when submitting
5. **Add notifications** - Email/SMS to each approver when their turn comes

## Notes

- HR Team CC is informational only - they receive notifications but don't approve
- Asset clearance ensures all company property is returned before vacation
- GR Officer is responsible for booking tickets and arranging exit-reentry permits
- fly flag prevents duplicate vacation requests while employee is away
- Payroll step ensures salary is processed correctly for vacation period
