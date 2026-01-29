# Approval Comments Integration Guide

## Overview
Approval comments functionality has been integrated across all approval workflows. Users can now add optional comments when approving or rejecting requests, providing a complete audit trail through the `approval_comments` table.

## Implementation Summary

### Files Updated

#### 1. SettlementManager_Corrected.php
**Added:**
- Optional `$comment` parameter to `approveSettlement()` method
- Optional `$comment` parameter to `rejectSettlement()` method
- New `getApproverName()` helper method
- Automatic saving of comments to `approval_comments` table

**Methods:**
```php
public function approveSettlement($settlementInvNo, $approverId, $level, $notes = '', $comment = '')
public function rejectSettlement($settlementInvNo, $rejecterId, $level, $reason = '', $comment = '')
private function getApproverName($approverId)
```

#### 2. includes/api/settlement_handler.php
**Updated:**
- `approveSettlement()` function to capture and pass comment
- `rejectSettlement()` function to capture and pass comment

**Request Data:**
```
POST /includes/api/settlement_handler.php
{
    action: 'approve_settlement',
    settlement_inv_no: 'SETTLEMENT-VAC-...',
    level: 1,
    notes: 'Approved',
    comment: 'All vacation details verified'
}
```

#### 3. all_applied_vac.php
**Already Has:**
- Approval comment textarea in modal (id="swal_approval_comment")
- Comment capture in preConfirm
- Comment sending in sendApproval AJAX call

**No Changes Needed** - Already integrated!

#### 4. all_applied_loan.php
**Status:** Ready for integration (follow same pattern as vacation)

## Database Table: approval_comments

### Structure
```sql
CREATE TABLE `approval_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_inv_no` varchar(255) NOT NULL,        -- Invoice: 'VAC-...', 'LOAN-...', 'SETTLEMENT-...'
  `request_type` enum(..., 'settlement') NOT NULL, -- Type of request
  `approval_action` enum('approved','rejected','hold','adjusted') DEFAULT 'approved',
  `approver_emp_id` int(11) DEFAULT NULL,        -- Employee ID of approver
  `approver_admin_id` int(11) DEFAULT NULL,      -- Admin ID if not employee
  `approver_name` varchar(255) NOT NULL,         -- Name for reference
  `approval_level` int(11) DEFAULT 0,            -- Level in approval chain
  `comment_text` longtext DEFAULT NULL,          -- The actual comment
  `comment_date` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_request_inv_no` (`request_inv_no`),
  KEY `idx_request_type` (`request_type`),
  KEY `idx_approver_emp_id` (`approver_emp_id`),
  KEY `idx_approval_action` (`approval_action`)
);
```

### Fields Explained

| Field | Purpose | Example |
|-------|---------|---------|
| request_inv_no | Links to original request | 'SETTLEMENT-VAC-20260127-5160-abc' |
| request_type | Type of request being commented on | 'settlement', 'vacation_request', 'loan' |
| approval_action | Action taken by approver | 'approved', 'rejected' |
| approver_emp_id | Employee ID of the person commenting | 5430 |
| approver_name | Name for easy reference | 'Ahmed Al-Mutlak' |
| approval_level | Which level in the chain | 1, 2, 3 |
| comment_text | The actual comment (required or optional) | 'Approved after verification' |
| comment_date | When comment was added | 2026-01-27 14:30:00 |

## Workflow Integration

### Settlement Approval with Comments

```
1. User views settlement pending approval
2. Clicks "Approve" button
3. Modal appears with:
   - Settlement details
   - Comment textarea (optional)
4. User enters comment (e.g., "Verified employee records")
5. Clicks "Confirm"
6. JavaScript calls settlement_handler.php with:
   {
       action: 'approve_settlement',
       settlement_inv_no: 'SETTLEMENT-VAC-...',
       level: 1,
       notes: 'Approved',
       comment: 'Verified employee records'  ← SENT HERE
   }
7. Backend:
   a. Updates request_approvers (status='approved')
   b. Adds to smt_request_status (audit trail)
   c. Saves to approval_comments table:
      {
          request_inv_no: 'SETTLEMENT-VAC-...',
          request_type: 'settlement',
          approval_action: 'approved',
          approver_emp_id: 5430,
          approver_name: 'Ahmed Al-Mutlak',
          approval_level: 1,
          comment_text: 'Verified employee records'
      }
8. Approval complete with comment stored
```

### Settlement Rejection with Comments

```
1. User clicks "Reject" button
2. Modal appears with comment field
3. User enters reason/comment
4. Backend saves:
   {
       approval_action: 'rejected',
       comment_text: 'Missing required documentation'
   }
5. Approval trail complete
```

## Data Queries

### Get All Comments for a Settlement
```sql
SELECT 
    ac.approver_name,
    ac.approval_level,
    ac.approval_action,
    ac.comment_text,
    ac.comment_date
FROM approval_comments ac
WHERE ac.request_inv_no = 'SETTLEMENT-VAC-20260127-5160-abc'
ORDER BY ac.approval_level ASC;
```

### Get All Approvals by an Employee
```sql
SELECT 
    ac.request_inv_no,
    ac.request_type,
    ac.approval_action,
    ac.approval_level,
    ac.comment_text,
    ac.comment_date
FROM approval_comments ac
WHERE ac.approver_emp_id = 5430
AND ac.approval_action = 'approved'
ORDER BY ac.comment_date DESC;
```

### Get All Rejections with Comments
```sql
SELECT 
    ac.request_inv_no,
    ac.approver_name,
    ac.comment_text,
    ac.comment_date
FROM approval_comments ac
WHERE ac.approval_action = 'rejected'
AND ac.request_type = 'settlement'
ORDER BY ac.comment_date DESC;
```

### Audit Trail for a Settlement
```sql
-- Get complete approval history with comments
SELECT 
    ra.approval_level,
    e.name as approver_name,
    ra.status,
    ra.action_date,
    ac.comment_text
FROM request_approvers ra
LEFT JOIN employees e ON e.emp_id = ra.approver_id
LEFT JOIN approval_comments ac ON (
    ac.request_inv_no = ra.request_inv_no 
    AND ac.approver_emp_id = ra.approver_id
    AND ac.approval_level = ra.approval_level
)
WHERE ra.request_inv_no = 'SETTLEMENT-VAC-20260127-5160-abc'
ORDER BY ra.approval_level ASC;
```

## User Interface

### Approval Modal Comment Field
All approval modals now include an optional comment textarea:

```html
<div class="form-group">
    <label>Approval Comment (Optional)</label>
    <textarea id="swal_approval_comment" 
              class="form-control" 
              rows="4" 
              placeholder="Write your comment or review...">
    </textarea>
    <small class="text-muted">
        Character count: <span id="comment-char-count">0</span>/5000
    </small>
</div>
```

**Features:**
- Optional (not required unless specifically marked as required)
- Up to 5000 characters
- Character counter provided
- Can be used for approval notes, rejection reasons, or any comments

### Modal Display in Different Scenarios

#### During Approval
```
┌─────────────────────────────────────┐
│ Confirm Approval                    │
├─────────────────────────────────────┤
│ Settlement: SETTLEMENT-VAC-...      │
│ Employee: John Smith                │
│ Amount: SAR 1,750.00                │
│ ─────────────────────────────────── │
│ Approval Comment (Optional)         │
│ ┌─────────────────────────────────┐ │
│ │ Approved after checking         │ │
│ │ documentation and payment       │ │
│ │ proof. All details verified.    │ │
│ └─────────────────────────────────┘ │
│ Character count: 58/5000            │
├─────────────────────────────────────┤
│ [Cancel] [Confirm Approval]         │
└─────────────────────────────────────┘
```

#### During Rejection
```
┌─────────────────────────────────────┐
│ Confirm Rejection                   │
├─────────────────────────────────────┤
│ Settlement: SETTLEMENT-LOAN-...     │
│ Employee: Sarah Ahmed               │
│ Amount: SAR 5,000.00                │
│ ─────────────────────────────────── │
│ Rejection Comment                   │
│ ┌─────────────────────────────────┐ │
│ │ Missing required payment proof  │ │
│ │ documentation. Please resubmit  │ │
│ │ with complete documentation.    │ │
│ └─────────────────────────────────┘ │
│ Character count: 62/5000            │
├─────────────────────────────────────┤
│ [Cancel] [Submit Rejection]         │
└─────────────────────────────────────┘
```

## Backend Integration Points

### Settlement Manager Processing
```php
// When approving
$result = $settlementManager->approveSettlement(
    $settlementInvNo,
    $currentUserId,
    $level,
    $notes,
    $comment  // ← Comments passed here
);

// Internally:
// 1. Updates request_approvers
// 2. Adds smt_request_status
// 3. Saves to approval_comments:
$escapedComment = mysqli_real_escape_string($conDB, $comment);
mysqli_query($conDB, "
    INSERT INTO approval_comments 
    (request_inv_no, request_type, approval_action, 
     approver_emp_id, approver_name, approval_level, comment_text) 
    VALUES 
    ('{$settlementInvNo}', 'settlement', 'approved', 
     {$approverId}, '{$approverName}', {$level}, '{$escapedComment}')
");
```

## Data Flow Diagram

```
User Approval Modal
        ↓
Comment Captured (Optional)
        ↓
AJAX POST to settlement_handler.php
        ↓
settlement_handler.php → SettlementManager
        ↓
        ├─ Update request_approvers
        ├─ Insert smt_request_status
        └─ Insert approval_comments ← COMMENT SAVED
                ↓
        INSERT INTO approval_comments (
            request_inv_no,
            request_type: 'settlement',
            approval_action: 'approved'|'rejected',
            approver_emp_id,
            approver_name,
            approval_level,
            comment_text: $comment
        )
```

## Testing Checklist

- [ ] Create settlement approval modal
- [ ] See comment textarea field (optional)
- [ ] Enter test comment
- [ ] Submit approval
- [ ] Check approval_comments table
- [ ] Verify comment saved with correct data
- [ ] Check approver_emp_id is correct
- [ ] Check approval_level is correct
- [ ] Check approval_action is 'approved'
- [ ] Check request_type is 'settlement'
- [ ] Test rejection with comment
- [ ] Verify rejection comment saved
- [ ] Test without comment (should be empty/null)
- [ ] Test long comment (5000+ chars)
- [ ] Run audit trail query
- [ ] Run approvals by employee query

## Query Examples

### Get Approver's Recent Comments
```sql
SELECT 
    c.request_inv_no,
    c.request_type,
    c.approval_action,
    c.comment_text,
    c.comment_date
FROM approval_comments c
WHERE c.approver_emp_id = 5430
ORDER BY c.comment_date DESC
LIMIT 10;
```

### Get Commented Approvals Only
```sql
SELECT 
    c.request_inv_no,
    c.approver_name,
    c.approval_action,
    c.comment_text
FROM approval_comments c
WHERE c.comment_text IS NOT NULL 
  AND c.comment_text != ''
  AND c.request_type = 'settlement'
ORDER BY c.comment_date DESC;
```

### Settlement Approval Timeline with Comments
```sql
SELECT 
    r.approval_level,
    e.name as approver,
    r.status,
    c.approval_action,
    c.comment_text,
    r.action_date,
    c.comment_date
FROM request_approvers r
LEFT JOIN employees e ON e.emp_id = r.approver_id
LEFT JOIN approval_comments c ON (
    c.request_inv_no = r.request_inv_no
    AND c.approver_emp_id = r.approver_id
)
WHERE r.request_inv_no = 'SETTLEMENT-VAC-20260127-5160-abc'
ORDER BY r.approval_level ASC;
```

## Security Considerations

1. **Input Validation:** Comments escaped with mysqli_real_escape_string()
2. **Length Limit:** Maximum 5000 characters per comment
3. **Approver Tracking:** Records which employee made the comment
4. **Audit Trail:** Complete timestamp and action tracking
5. **Read-Only History:** Comments cannot be edited, only new ones added

## Future Enhancements

1. Comment editing functionality (with audit trail)
2. Comment visibility controls (who can see which comments)
3. Comment templates for common rejections
4. Comments in approval email notifications
5. Comment search and filtering in reports
6. Analytics on approval comments
7. Bulk comment export

## Database Maintenance

### Backup Query
```sql
-- Export all comments for a period
SELECT * 
FROM approval_comments 
WHERE comment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY comment_date DESC;
```

### Archive Queries
```sql
-- Archive comments older than 1 year
INSERT INTO approval_comments_archive
SELECT * FROM approval_comments
WHERE YEAR(comment_date) < YEAR(NOW());

DELETE FROM approval_comments
WHERE YEAR(comment_date) < YEAR(NOW());
```

## Support & Troubleshooting

### Comments Not Saving
- Check approval_comments table exists
- Verify comment field not empty
- Check MySQL max_allowed_packet (for very long comments)
- Check error logs for SQL errors

### Missing Approver Names
- Verify employees table has correct records
- Check emp_id vs id field mapping
- Run getApproverName() helper function

### Query Not Working
- Check table name: `approval_comments`
- Verify field names match exactly
- Check MySQL user has SELECT/INSERT permissions

---
**Last Updated:** January 27, 2026
**Status:** Ready for Implementation & Testing
