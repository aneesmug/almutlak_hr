# HR Team CC Notification Feature

## Overview
When HR Senior BP approves a vacation request, they can select HR team members to receive CC email notifications. This allows HR team members to be informed without being part of the approval chain.

## Implementation Details

### 1. Database Query - Get HR Team Members
**Endpoint:** `ajaxEmployee.php` → `get_hr_team_members`

**Query:**
```sql
SELECT DISTINCT e.emp_id, e.name, e.email, d.dept_name, al.user_type 
FROM employees e 
LEFT JOIN department d ON e.dept = d.dept_id
LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
WHERE e.status = 1 
AND (d.dept_name LIKE '%HR%' OR d.dept_name LIKE '%Human Resources%')
AND (al.user_type IS NULL OR al.user_type NOT IN ('hr_senior_bp', 'hr_payroll', 'gr_officer'))
ORDER BY e.name ASC
```

**Logic:**
- Fetches active employees from HR department
- Excludes HR Senior BP, HR Payroll, and GR Officer roles (they're in the approval chain)
- Returns HR staff who can receive CC notifications
- Current user is excluded from the dropdown in the frontend

### 2. UI Component - Multi-Select Dropdown
**File:** `all_applied_vac.php` → `approveRequest()` function

**Condition:**
```javascript
if (isHR_SeniorBP) {
    // Show HR Team CC selection
}
```

**HTML:**
```html
<select id="hr_team_cc_select" class="form-control swal-select2-dynamic" multiple="multiple">
    <!-- Populated via AJAX -->
</select>
```

**Features:**
- Multi-select enabled with Select2
- Optional (not required)
- Shows employee name and department
- Excludes current user (HR Senior BP themselves)

### 3. Data Flow

#### Step 1: Modal Opens (willOpen)
```javascript
if (isHR_SeniorBP) {
    // Initialize Select2
    initSelect2('#hr_team_cc_select', __('select_hr_team_members'));
    
    // Load HR team members via AJAX
    $.ajax({
        url: './includes/ajaxFile/ajaxEmployee.php',
        data: { ajaxType: "get_hr_team_members" },
        success: function(res) {
            // Populate dropdown
        }
    });
}
```

#### Step 2: User Confirms Approval (preConfirm)
```javascript
let hr_team_cc = [];
if (isHR_SeniorBP) {
    let selectedCC = $(swalModal).find('#hr_team_cc_select').val();
    if (selectedCC && Array.isArray(selectedCC)) {
        hr_team_cc = selectedCC;
    }
}

return {
    approver_chain: approver_chain,
    ticket_pay: ticket_pay,
    permit_fee: permit_fee,
    hr_team_cc: hr_team_cc  // Array of emp_ids
}
```

#### Step 3: Backend Processing (ajaxVacation.php)
```php
$hr_team_cc = (array)($_POST['hr_team_cc'] ?? []);

// After approval is processed...
if (!empty($hr_team_cc) && is_array($hr_team_cc)) {
    // Get vacation details
    // Get CC recipient emails
    // Send email notifications
}
```

### 4. Email Notification

**Template:**
```html
<h3>Vacation Request Approved - CC Notification</h3>
<p>This is a CC notification. The following vacation request has been approved:</p>
<ul>
    <li><strong>Employee:</strong> {emp_name}</li>
    <li><strong>Vacation Type:</strong> {vacation_type}</li>
    <li><strong>Start Date:</strong> {start_date}</li>
    <li><strong>End Date:</strong> {end_date}</li>
    <li><strong>Total Days:</strong> {total_days}</li>
    <li><strong>Request Invoice:</strong> {request_inv_no}</li>
</ul>
<p><em>You are receiving this as a CC notification from HR Senior BP.</em></p>
```

**Current Status:**
- Email logic is implemented but commented out for testing
- Currently logs to error_log instead of sending emails
- To enable: Uncomment `send_email()` call in ajaxVacation.php

### 5. Testing Checklist

#### Frontend Tests
- [ ] HR Senior BP sees multi-select dropdown when approving
- [ ] HR team members load correctly from database
- [ ] Dropdown excludes current user (HR Senior BP)
- [ ] Dropdown excludes HR Payroll and GR Officer
- [ ] Select2 allows multiple selections
- [ ] Selected members are highlighted
- [ ] Can deselect members

#### Backend Tests
- [ ] `get_hr_team_members` endpoint returns correct data
- [ ] Empty array is handled when no CC selected
- [ ] Single CC selection works
- [ ] Multiple CC selections work
- [ ] CC data is received in `approveVacation` endpoint
- [ ] Email notifications are sent (or logged)

#### Integration Tests
- [ ] CC selection doesn't affect approval chain
- [ ] Approval succeeds with 0 CC recipients
- [ ] Approval succeeds with 1 CC recipient
- [ ] Approval succeeds with 5+ CC recipients
- [ ] CC recipients receive email notifications
- [ ] Email contains correct vacation details

### 6. Database Considerations

**Current Implementation:**
- CC recipients are sent via email immediately
- No permanent storage of CC list (could be added later)

**Future Enhancement:**
If you want to store CC recipients permanently:

**Option A: Add JSON column to emp_vacation**
```sql
ALTER TABLE emp_vacation ADD COLUMN cc_recipients JSON DEFAULT NULL;
```

**Option B: Create new table**
```sql
CREATE TABLE vacation_cc_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vacation_id INT NOT NULL,
    emp_id INT NOT NULL,
    notified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vacation_id) REFERENCES emp_vacation(id),
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id)
);
```

### 7. Translation Keys

Add these to your translation files:

```javascript
{
    "notify_hr_team": "Notify HR Team",
    "select_hr_team_members": "Select HR Team Members",
    "select_hr_team_members_cc": "Select HR Team Members (CC)",
    "hr_team_cc_note": "Selected HR team members will receive email notifications (CC only, not approvers)",
    "loading_hr_team": "Loading HR team...",
    "no_hr_team_found": "No HR team members found",
    "error_loading_hr_team": "Error loading HR team"
}
```

### 8. Security Considerations

**Access Control:**
- Only HR Senior BP can see and use CC selection
- UI checks `userRole === 'hr_senior_bp'`
- Backend should validate user has HR Senior BP role (TODO)

**Data Validation:**
- CC recipients are validated as integers
- Only active employees from HR department can be selected
- Email addresses are fetched from database (not user input)

**Email Security:**
- Uses system email function (assumed to exist)
- Email content is HTML-escaped in template
- No sensitive data beyond vacation details

### 9. Code Locations

**Files Modified:**

1. **ajaxEmployee.php** (line ~145)
   - Added `get_hr_team_members` endpoint

2. **all_applied_vac.php** (multiple sections)
   - Line ~632: Added `hrTeamCCHtml` variable
   - Line ~661: Added HR Team CC HTML template
   - Line ~719: Updated Swal.fire to include `hrTeamCCHtml`
   - Line ~800: Added HR Team CC loading logic in willOpen
   - Line ~920: Added `hr_team_cc` extraction in preConfirm
   - Line ~983: Updated return object to include `hr_team_cc`

3. **ajaxVacation.php** (line ~496)
   - Added `$hr_team_cc` parameter extraction
   - Added email notification logic for CC recipients

### 10. How to Enable Email Sending

**Current State:**
```php
// For now, log it
error_log("HR Team CC notification would be sent to: {$cc_rec['name']} ...");
```

**To Enable Emails:**
1. Locate the email sending function in your system (likely in `includes/functions.php`)
2. Uncomment the line in `ajaxVacation.php`:
```php
send_email($cc_rec['email'], $subject, $message);
```

**Example Email Function:**
```php
function send_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: HR System <noreply@almutlak.com>" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}
```

---

## Quick Reference

**When does CC selection appear?**
- Only when HR Senior BP approves a vacation request

**Who can be selected as CC?**
- HR department employees (excluding HR Senior BP, HR Payroll, GR Officer)

**Is CC selection required?**
- No, it's optional

**What happens to CC recipients?**
- They receive email notifications (not approval requests)
- They are NOT part of the approval chain

**How to test without sending emails?**
- Check error_log for "HR Team CC notification would be sent to..." messages
- Verify correct employee names and emails appear in logs

---

## Related Documentation
- See `VACATION_APPROVAL_FLOW.md` for complete approval chain documentation
- See `all_applied_vac.php` lines 619-1050 for full approval modal code
