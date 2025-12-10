# Manager Evaluation Acknowledgment/Objection System Documentation

## Overview
This system allows managers to acknowledge or object to employee evaluations with optional notes. Management (HR_Recruitment, HR_Manager, or Administrator) can then view reports of these acknowledgments.

## Database Changes

### ALTER TABLE `emp_evaluations`

```sql
ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledgment_status` ENUM('pending', 'acknowledged', 'objected') DEFAULT 'pending';
ALTER TABLE `emp_evaluations` ADD COLUMN `manager_objection_note` LONGTEXT NULL;
ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledgment_date` DATETIME NULL;
ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledged_by` INT NULL;
```

## New Files Created

### 1. SQL Files
- **`sql/add_manager_evaluation_acknowledgment.sql`** - Database schema changes
- **`sql/manager_acknowledgment_translations.sql`** - UI text translations (EN/AR)

### 2. PHP Files
- **`includes/evaluation_acknowledgment_handler.php`** - Core business logic functions
- **`includes/ajaxFile/ajaxEvaluationAcknowledgment.php`** - AJAX endpoint handler

### 3. JavaScript Files
- **`assets/js/evaluation_acknowledgment.js`** - Frontend modal and report handling

## Implementation Guide

### Step 1: Run SQL Files
Execute the SQL files to add the necessary database columns:

```bash
mysql -u root almutlak_db < sql/add_manager_evaluation_acknowledgment.sql
mysql -u root almutlak_db < sql/manager_acknowledgment_translations.sql
```

### Step 2: Include Helper Files
Add this to your evaluation page:

```php
<?php
require_once __DIR__ . '/includes/evaluation_acknowledgment_handler.php';
?>
```

### Step 3: Add JavaScript
Include this in your evaluation page template:

```html
<script src="assets/js/evaluation_acknowledgment.js"></script>
```

### Step 4: Add Buttons to Evaluation Display
Add acknowledgment/objection buttons to your evaluation display:

```html
<?php if (can_acknowledge_evaluations($user_type, $user_role)): ?>
    <div class="evaluation-acknowledgment-buttons">
        <button class="btn btn-success" onclick="showAcknowledgmentModal(<?=$eval_id?>, '<?=$employee_name?>', null)">
            <i class="fa fa-check"></i> <?=__('acknowledge_evaluation')?>
        </button>
        <button class="btn btn-danger" onclick="showObjectionModal(<?=$eval_id?>, '<?=$employee_name?>', null)">
            <i class="fa fa-exclamation-circle"></i> <?=__('object_to_evaluation')?>
        </button>
    </div>
<?php endif; ?>
```

### Step 5: Display Current Status
Show current acknowledgment status:

```html
<?php
$ack_data = get_evaluation_acknowledgment($conDB, $eval_id);
if ($ack_data):
?>
    <div class="acknowledgment-status">
        <h5><?=__('manager_acknowledgment_report')?></h5>
        <p><strong><?=__('status')?>:</strong> 
            <?php
            if ($ack_data['manager_acknowledgment_status'] === 'acknowledged') {
                echo '<span class="badge badge-success">' . __('manager_acknowledgment') . '</span>';
            } elseif ($ack_data['manager_acknowledgment_status'] === 'objected') {
                echo '<span class="badge badge-danger">' . __('manager_objection') . '</span>';
                if (!empty($ack_data['manager_objection_note'])) {
                    echo '<br><strong>' . __('objection_reason') . ':</strong> ' . htmlspecialchars($ack_data['manager_objection_note']);
                }
            }
            ?>
        </p>
        <?php if (!empty($ack_data['manager_acknowledged_by'])): ?>
            <p><strong><?=__('acknowledged_by')?>:</strong> <?=htmlspecialchars($ack_data['manager_name'])?></p>
            <p><strong><?=__('acknowledgment_date')?>:</strong> <?=htmlspecialchars($ack_data['manager_acknowledgment_date'])?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

### Step 6: Create Management Report Page (Optional)
Create a new page `evaluation_acknowledgment_report.php`:

```php
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/evaluation_acknowledgment_handler.php';

// Check permission
if (!can_view_acknowledgment_report($user_type, $user_role)) {
    header("Location: dashboard.php");
    exit();
}
?>
<!-- Your page HTML -->
<div id="acknowledgment-report-container"></div>

<script>
    function loadReport(filter) {
        loadAcknowledgmentReport(filter);
    }
    
    // Load on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAcknowledgmentReport('pending');
    });
</script>
```

## User Roles & Permissions

### Can Acknowledge Evaluations:
- Department Managers (DPT_Manager)
- HR Manager
- IT Team Manager
- HR Team Manager
- Finance Team Manager
- Executive Team Manager
- Administrator

### Can View Acknowledgment Reports:
- Administrator
- HR_Recruitment
- HR_Manager
- HR_Senior_BP
- GM

## Translation Keys

The following translation keys are used (see `sql/manager_acknowledgment_translations.sql`):

- `manager_acknowledgment` - "Acknowledged"
- `manager_objection` - "Objected"
- `manager_acknowledgment_pending` - "Pending Manager Acknowledgment"
- `acknowledge_evaluation` - "Acknowledge Evaluation"
- `object_to_evaluation` - "Object to Evaluation"
- `manager_acknowledgment_title` - "Manager Evaluation Acknowledgment"
- `manager_objection_title` - "Manager Evaluation Objection"
- `acknowledge_prompt` - "You are about to acknowledge this evaluation..."
- `objection_prompt` - "Please provide your objection note/reason:"
- `objection_required` - "Objection note is required when objecting..."

## API Endpoints

### AJAX Endpoint: `ajaxEvaluationAcknowledgment.php`

#### 1. Submit Acknowledgment/Objection
**POST Parameters:**
- `ajaxType`: `submit_acknowledgment`
- `eval_id`: Evaluation ID (int)
- `acknowledgment_status`: 'acknowledged' or 'objected' (string)
- `objection_note`: Objection reason (string, required if objected)

**Response:**
```json
{
    "status": 200,
    "message": "Acknowledged successfully recorded",
    "acknowledgment_status": "acknowledged",
    "timestamp": "2025-12-07 10:30:00"
}
```

#### 2. Get Acknowledgment Status
**POST Parameters:**
- `ajaxType`: `get_acknowledgment_status`
- `eval_id`: Evaluation ID (int)

**Response:**
```json
{
    "status": 200,
    "data": {
        "manager_acknowledgment_status": "acknowledged",
        "manager_objection_note": null,
        "manager_acknowledgment_date": "2025-12-07 10:30:00",
        "manager_acknowledged_by": 123,
        "manager_name": "John Manager",
        "manager_role": "DPT_Manager"
    }
}
```

#### 3. Get Acknowledgment Report
**POST Parameters:**
- `ajaxType`: `get_acknowledgment_report`
- `filter`: 'pending', 'acknowledged', or 'objected' (string)

**Response:**
```json
{
    "status": 200,
    "data": [
        {
            "id": 1,
            "emp_id": 100,
            "employee_name": "Ahmed Ali",
            "employee_id": 100,
            "employee_position": "Software Engineer",
            "evaluation_date": "2025-12-01",
            "manager_acknowledgment_status": "acknowledged",
            "manager_acknowledgment_date": "2025-12-07 10:30:00",
            "manager_objection_note": null,
            "manager_name": "John Manager",
            "manager_role": "DPT_Manager"
        }
    ],
    "count": 1
}
```

## Feature Summary

### For Managers:
✅ View employee evaluations
✅ Acknowledge evaluations (green button)
✅ Object to evaluations with required note (red button)
✅ Track acknowledgment history

### For HR/Management:
✅ View pending acknowledgments
✅ View acknowledged evaluations
✅ View objected evaluations with reasons
✅ Generate acknowledgment reports
✅ Filter by status

### Data Tracking:
✅ Manager who acknowledged/objected
✅ Date/time of acknowledgment
✅ Objection notes/reasons
✅ Previous acknowledgment status

## Security Considerations

1. **Permission Validation** - Only authorized managers can acknowledge
2. **Data Validation** - Objection notes are required when objecting
3. **User Verification** - System verifies user identity before recording acknowledgment
4. **SQL Injection Prevention** - All queries use prepared statements
5. **Authorization Checks** - Report access restricted to HR/Management only

## Troubleshooting

### Issue: "You do not have permission to acknowledge evaluations"
- Check user role is in the authorized list
- Verify user_role is set correctly in session

### Issue: "Objection note is required"
- Ensure objection_note parameter is provided when status is 'objected'
- Note must not be empty or whitespace

### Issue: Acknowledgment status not updating
- Verify emp_evaluations table has new columns
- Check database permissions
- Review browser console for AJAX errors

## Future Enhancements

1. Email notifications for acknowledgments
2. Acknowledgment deadline enforcement
3. Bulk acknowledgment/rejection
4. Acknowledgment history timeline
5. Department-level acknowledgment summaries
6. Integration with performance management system
