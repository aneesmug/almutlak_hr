# Business Trip Request System - Implementation Guide

## Overview
The Business Trip Request System allows employees to submit business trip requests with multi-level approval workflow. Supports both Domestic (own car/rental) and International trips.

## Features
✅ **Two Trip Types**: Domestic (with vehicle options) and International
✅ **Multi-Level Approval Workflow**: Configurable through app_settings
✅ **Saudi Cities Selection**: From/To route selection from saudi_cities table  
✅ **Email Notifications**: Automated approval notifications with professional HTML templates
✅ **Browser Notifications**: Real-time notifications for approvers
✅ **SweetAlert2 Modal**: Same layout as vacation requests (Employee Name/ID at top)
✅ **Activity Logging**: Complete audit trail of all actions
✅ **Approval Chain**: Based on app_settings configuration (default: Supervisor → HR → Finance)

## Installation Steps

### Step 1: Create Database Table
Run the SQL migration:

```bash
mysql -u username -p database_name < sql/create_business_trip_table.sql
```

Or import via phpMyAdmin:
1. Go to phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Select `sql/create_business_trip_table.sql`
5. Click "Go"

**What was created:**
- `emp_business_trip` table with all required fields
- Automatic approval chain entry in `app_settings` (if not exists)
- Indexes for performance

### Step 2: Verify Files Exist
Ensure these files are in place:
- `/sql/create_business_trip_table.sql` - Database migration
- `/includes/ajaxFile/ajaxBusinessTrip.php` - AJAX handler
- `/includes/PHPMailerMaster/business_trip_email_template.html` - Email template
- `/assets/js/jquery.app.js` - Updated with functions (already done)

### Step 3: Add Button to Employee Interface
Add this button to employee profile or vacation page:

```html
<!-- Business Trip Request Button -->
<button type="button" class="btn btn-primary" onclick="openBusinessTripApplyModal(<?php echo $emp_id; ?>, <?php echo $dept_id; ?>, '<?php echo $country; ?>')">
    <i class="fa fa-plane"></i> Apply Business Trip
</button>
```

## Database Schema

### emp_business_trip Table

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Primary key |
| `request_inv_no` | VARCHAR(100) | Unique request ID (BT-YYYYMMDD-xxxxx) |
| `emp_id` | INT | Employee ID |
| `company_id` | INT | Company ID (optional, for multi-company support) |
| `trip_type` | ENUM | 'domestic' or 'international' |
| `trip_purpose` | VARCHAR(255) | Purpose of trip |
| `transportation_type` | ENUM | 'own_car' or 'rental' (domestic only) |
| `trip_start_date` | DATE | Departure date |
| `trip_start_time` | TIME | Departure time |
| `trip_end_date` | DATE | Return date |
| `trip_end_time` | TIME | Return time |
| `from_city_id` | INT | Departure city ID |
| `to_city_id` | INT | Destination city ID |
| `destination_country` | VARCHAR(100) | Country name (international) |
| `visa_required` | TINYINT | 0/1 flag for visa requirements |
| `current_status` | ENUM | 'pending_approval', 'approved', 'rejected', 'completed', 'cancelled' |
| `current_approval_level` | INT | Current approval stage (1, 2, 3, etc.) |
| `request_notes` | LONGTEXT | Additional requirements/notes |
| `created_at` | TIMESTAMP | Request submission time |
| `last_modified` | TIMESTAMP | Last update time |

## Approval Workflow

### Default Approval Chain (Configured in app_settings)
```
Level 1: Direct Supervisor
   ↓ (if approved)
Level 2: HR Senior BP
   ↓ (if approved)
Level 3: Finance Officer
   ↓ (if approved)
Status = APPROVED
```

### Customizing Approval Chain
1. Login as Administrator
2. Go to `app_settings.php`
3. Click "Approval" tab
4. Find "Business Trip" section
5. Modify approval levels (add/remove/reorder)
6. Save changes

### Configuration in app_settings
```json
{
    "setting_name": "approval_chain_business_trip",
    "setting_value": "[
        {\"level\":1,\"user_type\":\"direct_supervisor\",\"role_label\":\"Direct Supervisor\"},
        {\"level\":2,\"user_type\":\"hr_senior_bp\",\"role_label\":\"HR Senior BP\"},
        {\"level\":3,\"user_type\":\"finance_officer\",\"role_label\":\"Finance Officer\"}
    ]"
}
```

## Form Fields & Validation

### Required Fields
- **Trip Type**: Domestic or International ✓ Required
- **Trip Purpose**: Description of trip objectives ✓ Required
- **Start Date**: Trip departure date ✓ Required
- **End Date**: Trip return date ✓ Required

### Domestic Trip Fields (when trip_type = 'domestic')
- **Transportation Type**: Own Car or Rental ✓ Required
- **From City**: Departure city from list ✓ Required
- **To City**: Destination city from list ✓ Required

### International Trip Fields (when trip_type = 'international')
- **Destination Country**: Country name ✓ Required
- **Visa Required**: Checkbox (optional)

### Optional Fields
- Start Time
- Return Time
- Rental Car Details
- Additional Notes

## API Endpoints (AJAX)

### Get Saudi Cities
```javascript
$.ajax({
    url: './includes/ajaxFile/ajaxBusinessTrip.php',
    type: 'POST',
    dataType: 'JSON',
    data: {
        ajaxType: 'getSaudiCities',
        is_rtl: false // true for Arabic labels
    },
    success: function(res) {
        // res.cities contains: [{id: 1, name: 'Jeddah'}, ...]
    }
});
```

### Submit Business Trip Request
```javascript
const formData = new FormData(document.getElementById('submitBusinessTripForm'));
formData.append('ajaxType', 'submitBusinessTrip');
formData.append('emp_id', 5430);
formData.append('first_approver_id', 5431);

$.ajax({
    url: './includes/ajaxFile/ajaxBusinessTrip.php',
    type: 'POST',
    dataType: 'JSON',
    contentType: false,
    processData: false,
    data: formData,
    success: function(response) {
        // response.status: 'success' or 'error'
        // response.request_inv_no: Generated request ID
    }
});
```

## Email Notifications

### Email Template
File: `/includes/PHPMailerMaster/business_trip_email_template.html`

**Recipients:**
- Level 1 Approver: Initial notification
- Level 2+ Approvers: Notified only when previous level approves
- Employee: Notified of final approval/rejection

**Email Contains:**
- Request ID
- Employee Name
- Trip Type
- Trip Dates
- Direct link to review request
- Professional HTML formatting

**Template Variables:**
- `{{REQUEST_TYPE}}` - "Business Trip Request"
- `{{APPROVER_NAME}}` - Recipient name
- `{{REQUEST_ID}}` - Request ID (BT-YYYYMMDD-xxxxx)
- `{{EMPLOYEE_NAME}}` - Employee requesting trip
- `{{TRIP_TYPE}}` - "Domestic" or "International"
- `{{TRIP_DATES}}` - "2025-12-15 to 2025-12-18"
- `{{REQUEST_URL}}` - Link to review request
- `{{EMAIL_MESSAGE}}` - Status message

## User Workflows

### Employee - Submitting a Business Trip
1. Navigate to employee profile or vacation menu
2. Click "Apply Business Trip" button
3. Modal opens with form
4. Fill in trip details:
   - Select trip type (Domestic/International)
   - If Domestic: Select vehicles option, From/To cities
   - If International: Enter country, visa checkbox
   - Enter trip purpose
   - Select dates and times
5. Click "Register"
6. Request submitted successfully
7. Email sent to first approver
8. Browser notification to approver appears

### Approver - Reviewing Requests
1. Login to system
2. Browser notification or email received
3. Click link to review request
4. View request details
5. Options:
   - **Approve**: Request moves to next approver
   - **Reject**: Request rejected with reason to employee
   - **Add Comments**: Optional approval notes

### Admin - Configuring Approvers
1. Login as Administrator
2. Go to `app_settings.php`
3. Click "Approval" tab
4. Find "Business Trip" section
5. Add/Remove/Reorder approvers
6. Save changes

## Status Values

| Status | Meaning |
|--------|---------|
| `pending_approval` | Awaiting first approver action |
| `approved` | All approvers approved (final status) |
| `rejected` | Rejected by any approver |
| `completed` | Trip completed/closed |
| `cancelled` | Request cancelled by employee |

## Request ID Format
`BT-YYYYMMDD-xxxxx`

**Example:** `BT-20251214-00001`
- `BT` = Business Trip prefix
- `YYYYMMDD` = Date submitted (2025-12-14)
- `xxxxx` = Sequential 5-digit counter

## Current Limitations
- ⚠️ Domestic trips currently limited to 3 predefined cities (Jeddah, Riyadh, Dammam)
- ⚠️ City list is hardcoded in form, should use dynamic loading from `saudi_cities` table
- ⚠️ No attachment upload for trip documentation yet
- ⚠️ No expense tracking or settlement integration yet

## Future Enhancements
- [ ] Complete saudi_cities dynamic loading (add migration if table missing)
- [ ] Expense tracking and settlement system
- [ ] Trip cancellation by employee
- [ ] Attachment uploads (itinerary, booking confirmations)
- [ ] Trip history/analytics dashboard
- [ ] Visa/hotel booking integration
- [ ] SMS notifications support
- [ ] Multi-language support (RTL Arabic)

## Troubleshooting

### Issue: "Supervisor Assignment Required" Error
**Cause:** Employee doesn't have a supervisor assigned  
**Fix:** Assign supervisor to employee in employee profile

### Issue: No cities appearing in dropdown
**Cause:** `saudi_cities` table doesn't exist or is empty  
**Fix:** Run database migration or populate with cities

### Issue: Email not being sent
**Cause:** SMTP settings not configured  
**Fix:** Configure SMTP in app_settings (ensure `smtp_*` settings are filled)

### Issue: Approver not receiving notification
**Cause:** Email address missing or invalid  
**Fix:** Verify approver email in admin_login table

## Support & Documentation
- Database schema: [create_business_trip_table.sql](../sql/create_business_trip_table.sql)
- AJAX handler: [ajaxBusinessTrip.php](../includes/ajaxFile/ajaxBusinessTrip.php)
- Email template: [business_trip_email_template.html](../includes/PHPMailerMaster/business_trip_email_template.html)
- Form functions: [jquery.app.js](../assets/js/jquery.app.js) (openBusinessTripApplyModal, businessTripForm_HTML)

---

**Version:** 1.0  
**Last Updated:** 2025-12-14  
**Status:** Ready for Production
