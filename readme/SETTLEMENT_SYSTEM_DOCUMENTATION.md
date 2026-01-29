# Settlement Approval System - Complete Implementation Guide

## Overview
The Settlement Approval System is fully integrated with the Al-Mutlak HR System, providing a complete approval workflow with email and browser notifications. Settlements follow the app_settings approval chain configuration ONLY.

## Key Features Implemented

### 1. Settlement Management Page (`all_settlements.php`)
- **Location**: `/system/all_settlements.php`
- **Purpose**: Central hub for viewing and managing all settlements
- **Features**:
  - Filter by status (my_pending, my_dept, pending_approval, approved, rejected, completed, all)
  - Search by employee name, ID, or settlement ID
  - Pagination with customizable items per page
  - Inline approval/rejection for approvers
  - View settlement details with full approval chain
  - Same layout and design as vacation and loan pages
  - Real-time status updates

### 2. Settlement Approval Workflow

#### Flow Chart:
```
Settlement Created → System assigns approvers from app_settings
                   ↓
          Browser notification + Email sent to Level 1 Approver
                   ↓
        Approver views all_settlements.php (my_pending filter)
                   ↓
        Approver approves/rejects with comment
                   ↓
        If approved: Next approver notified (Levels 2, 3)
        If rejected: Employee notified of rejection reason
                   ↓
        All levels approved → Settlement status = "approved"
```

#### Approval Levels (from app_settings.approval_chain_settlement):
1. **Level 1**: Department Manager (resolved from employee's department)
2. **Level 2**: Finance Officer (resolved from admin_login user_type)
3. **Level 3**: HR Payroll (resolved from admin_login user_type)

**CRITICAL**: These are the ONLY approvals created. No other approvals are added.

### 3. Notification System

#### Browser Notifications
- **Triggered**: When settlement created, approved, or rejected
- **Location**: Browser notification popup + stored in notifications table
- **Contains**: Settlement ID, action, request link
- **URL**: Points to `all_settlements.php` for quick access

#### Email Notifications
- **Triggered**: When settlement needs approval or rejected
- **Templates**:
  - `settlement_approval_email_template.html` - For approval requests
  - `settlement_rejection_email_template.html` - For rejections
- **Location**: `/includes/PHPMailerMaster/`
- **Fields**: Settlement ID, employee name, department, amount, approval/rejection reason

### 4. Database Tables Used

#### settlement_records
- `id`: Primary key
- `request_inv_no`: Settlement invoice (SETL-xxx)
- `request_type`: Source (annual_vacation, loan_request)
- `emp_id`: Employee ID
- `settlement_amount`: Amount to settle
- `settlement_status`: pending_approval, approved, rejected, completed
- `created_by`: User who created settlement
- `created_at`, `updated_at`: Timestamps

#### request_approvers
- `request_inv_no`: Links to settlement_records
- `request_type_id`: Settlement type ID (from approval_request_types)
- `approver_id`: Employee ID of approver
- `approval_level`: Level (1, 2, 3)
- `status`: pending, approved, rejected
- `approved_by`: ID of user who approved
- `approved_at`: When approval happened

#### smt_request_status
- `inv_no`: Settlement invoice
- `emp_id`: User taking action
- `emp_name`: User name
- `note`: Action description
- `status`: pending, approved, rejected
- `created_at`: When action occurred

#### approval_comments
- `request_inv_no`: Settlement invoice
- `request_type_id`: Settlement type ID
- `emp_id`: Approver ID
- `approval_action`: approved, rejected
- `comment_text`: Comment/reason
- `created_at`: When comment made

### 5. API Endpoints

#### `/includes/api/settlement_handler.php`

**create_settlement**
```php
POST /includes/api/settlement_handler.php
Parameters:
  - action: 'create_settlement'
  - request_inv_no: Original request ID
  - request_type: 'annual_vacation' or 'loan_request'
  - emp_id: Employee ID
  - settlement_amount: Amount in SAR

Response:
  {
    "success": true,
    "settlement_inv_no": "SETL-VAC-xxx",
    "message": "Settlement created successfully with configured approval chain"
  }
```

**approve_settlement**
```php
POST /includes/api/settlement_handler.php
Parameters:
  - action: 'approve_settlement'
  - settlement_id: Settlement ID (from settlement_records.id)
  - settlement_inv_no: Settlement invoice
  - emp_id: Employee ID
  - approval_comment: Optional comment

Response:
  {
    "success": true,
    "message": "Settlement approved - forwarded to next approver",
    "all_approvals_complete": false
  }
```

**reject_settlement**
```php
POST /includes/api/settlement_handler.php
Parameters:
  - action: 'reject_settlement'
  - settlement_id: Settlement ID
  - settlement_inv_no: Settlement invoice
  - rejection_reason: Required reason

Response:
  {
    "success": true,
    "message": "Settlement rejected successfully"
  }
```

**get_settlement_details**
```php
POST /includes/api/settlement_handler.php
Parameters:
  - action: 'get_settlement_details'
  - settlement_id: Settlement ID

Response:
  {
    "success": true,
    "data": {
      "settlement": {...},
      "approval_chain": [...],
      "history": [...]
    }
  }
```

### 6. Class: SettlementManager

**Location**: `/includes/SettlementManager_Corrected.php`

**Methods**:
- `__construct($conDB, $pdo)` - Initialize with database connections
- `createSettlement($requestInvNo, $requestType, $empId, $amount, $userId)` - Create settlement with approval chain and notifications
- `sendSettlementCreationNotifications($settlementInvNo, $empId, $amount)` - Send browser + email notifications to first approver
- `createLegacySettlementChain($settlementInvNo, $empId)` - Fallback chain creation (should not be used)

**Key Feature**: Uses ApprovalChainManager to enforce app_settings.approval_chain_settlement configuration ONLY.

### 7. Settlement Prefix

- **Format**: `SETL-` + original invoice number
- **Example**: `SETL-VAC-001` for vacation, `SETL-LN-001` for loan
- **Benefit**: Short, readable, and distinguishes from other request types

### 8. Integration with Existing Systems

#### vacation_report_details.php
Settlement is created from the vacation report details page:
```javascript
createSettlement(vacationId, requestInvNo, employeeId, employeeName, vacationDays, totalPayable, ...);
```

#### settlement_handler.php
Centralized API endpoint for all settlement operations:
- Validates user permissions
- Manages approval workflow
- Sends notifications
- Logs actions

#### ApprovalChainManager
Ensures ONLY app_settings configuration is used:
- Loads approval chain from app_settings
- Resolves approvers by role
- Creates request_approvers entries
- NO hardcoded approvals

### 9. Notifications Flow

#### When Settlement Created:
1. Settlement record inserted
2. Approval chain created by ApprovalChainManager
3. First approver identified (Level 1)
4. **Browser notification** sent to first approver
5. **Email notification** sent to first approver's email
6. All statuses logged in smt_request_status

#### When Settlement Approved:
1. request_approvers status updated to 'approved'
2. smt_request_status entry created
3. Approval comment logged
4. If more approvals needed:
   - Next approver notified (browser + email)
5. If all approvals complete:
   - Employee notified (approved)
   - Settlement status changed to 'approved'

#### When Settlement Rejected:
1. request_approvers status updated to 'rejected'
2. settlement_records status changed to 'rejected'
3. smt_request_status entry created with rejection note
4. Employee notified (browser + email) with rejection reason

### 10. Using the System

#### For Employees:
1. Create vacation/loan
2. Once approved, create settlement from all_applied_vac.php
3. Monitor settlement status in all_settlements.php (status=my_pending)
4. Receive notifications about approval progress

#### For Approvers:
1. Log into HR system
2. Go to all_settlements.php
3. Filter by "my_pending" to see settlements awaiting your approval
4. Click "Approve" or "Reject" button
5. Add comment (optional for approval, required for rejection)
6. Submit - next approver gets notified automatically

#### For HR/Finance:
1. View all_settlements.php with different filters
2. Track all settlement requests
3. Generate reports on settlement status
4. Process payments once approved

### 11. Configuration in app_settings

The settlement approval chain is configured in app_settings table:

```sql
setting_name: 'approval_chain_settlement'
setting_value: '[
  {"level":1,"user_type":"dept_manager","role_label":"Department Manager"},
  {"level":2,"user_type":"finance_officer","role_label":"Finance Officer"},
  {"level":3,"user_type":"hr_payroll","role_label":"HR Payroll"}
]'
```

**To modify approval chain**:
1. Update app_settings.setting_value JSON
2. ApprovalChainManager automatically uses new configuration
3. Next settlements will use updated chain

### 12. Error Handling

- Invalid parameters → Error response with message
- User not authorized → Error response
- Settlement already exists → Error message
- Missing approver → Still creates settlement with error logging
- Database errors → Logged with detailed messages

### 13. Testing Checklist

- [ ] Create settlement from vacation report
- [ ] Verify settlement created with SETL- prefix
- [ ] Check browser notification appears for approver
- [ ] Verify email sent to approver
- [ ] Open all_settlements.php and verify settlement shown
- [ ] Approver logs in, approves settlement
- [ ] Verify next level approver notified
- [ ] Final approval completes all levels
- [ ] Employee notified of final approval
- [ ] Test rejection with reason
- [ ] Verify employee receives rejection notification
- [ ] Check approval history in settlement details
- [ ] Verify all approvals only from app_settings

### 14. Files Modified/Created

**Created**:
- `/all_settlements.php` - Settlement management page
- `/includes/PHPMailerMaster/settlement_approval_email_template.html`
- `/includes/PHPMailerMaster/settlement_rejection_email_template.html`

**Modified**:
- `/includes/api/settlement_handler.php` - Added approval/rejection endpoints
- `/includes/SettlementManager_Corrected.php` - Added notification sending
- `/includes/helper_functions.php` - Added settlement email templates to load_email_template()

**Unchanged but Integrated**:
- `/includes/ApprovalChainManager.php` - Used for chain creation
- `/all_applied_vac.php` - Links to settlement creation

### 15. Support & Debugging

**Enable Logging**:
```php
error_log("Settlement creation: " . $message);
```

**Check Logs**:
- PHP error log: `/logs/php_error.log`
- Database queries logged with parameters

**Debug Notifications**:
1. Check browser_notifications table for entries
2. Check approval_comments for comment logging
3. Check smt_request_status for action history
4. Review request_approvers for approval levels

---

## Summary

The Settlement Approval System provides a complete, integrated solution for managing settlement approvals with:
- ✅ Full approval workflow matching app_settings configuration
- ✅ Browser notifications for real-time updates
- ✅ Email notifications with detailed templates
- ✅ Professional UI matching existing system design
- ✅ Comprehensive audit trail
- ✅ Security (authorization checks)
- ✅ Error handling and logging
- ✅ Scalable architecture

**All settlements created will ONLY follow the app_settings.approval_chain_settlement configuration with NO other approvals added.**
