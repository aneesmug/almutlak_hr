# Business Trip Request - Quick Integration Guide

## Prerequisites
Before proceeding, ensure you have completed:
1. ✅ Database table created (`emp_business_trip`)
2. ✅ Request type registered (`approval_request_types`)
3. ✅ Approval chain configured (`app_settings`)
4. ✅ AJAX handler installed (`ajaxBusinessTrip.php`)
5. ✅ Email template installed (`business_trip_email_template.html`)
6. ✅ JavaScript functions added (`jquery.app.js`)

## Installation Checklist

### Step 1: Run Database Migrations
```bash
# From d:\xampp\htdocs\almutlak\system\sql directory

# Create the business_trip table
mysql -u username -p database_name < create_business_trip_table.sql

# Register the request type
mysql -u username -p database_name < register_business_trip_request_type.sql
```

Or via phpMyAdmin:
1. Select database
2. Click "Import"
3. Upload SQL files one by one
4. Click "Go"

### Step 2: Verify Installation
```sql
-- Verify table exists
SELECT * FROM information_schema.TABLES WHERE TABLE_NAME = 'emp_business_trip';

-- Verify request type registered
SELECT * FROM approval_request_types WHERE type_name = 'business_trip';

-- Verify approval chain configured
SELECT * FROM app_settings WHERE setting_name = 'approval_chain_business_trip';
```

### Step 3: Add Button to Employee Interface

Find the employee profile or vacation request area (typically `profile.php` or `employee_profile.php`) and add:

```html
<!-- Business Trip Request Button -->
<button type="button" class="btn btn-primary" onclick="openBusinessTripApplyModal(<?php echo $emprow['empid']; ?>, <?php echo $emprow['dept']; ?>, false)">
    <i class="fa fa-plane"></i> Apply Business Trip
</button>
```

**Location Options:**
- **Employee Profile Page** (`profile.php`): Add next to vacation/leave buttons
- **My Account/Dashboard** (`my_account.php`): Add in requests section
- **Employee Profile Modal** (in admin): Add to employee details page
- **Top Menu Bar** (More menu): Add as a dropdown option

### Example Integration in profile.php

```php
<?php
// Find this section in profile.php
if ($canApplyVacation) {
    echo '<button class="btn btn-info" onclick="openVacationApplyModal(...)">Vacation</button>';
}

// Add below it:
if ($canApplyVacation) { // Or add your own permission check
    echo '<button type="button" class="btn btn-primary" onclick="openBusinessTripApplyModal(' . $emprow['empid'] . ', ' . $emprow['dept'] . ', false)">';
    echo '<i class="fa fa-plane"></i> Apply Business Trip</button>';
}
?>
```

### Example Integration in Employee Profile Modal

```javascript
// In jquery.app.js, within employee profile modal
// Add to action buttons section:

html += `
    <div class="button-group">
        <button class="btn btn-primary" onclick="openVacationApplyModal(${emp_id}, ${dept_id}, '${country}')">
            <i class="fa fa-calendar"></i> Vacation
        </button>
        <button class="btn btn-primary" onclick="openBusinessTripApplyModal(${emp_id}, ${dept_id}, false)">
            <i class="fa fa-plane"></i> Business Trip
        </button>
    </div>
`;
```

## Function Signature

```javascript
/**
 * Open Business Trip Apply Modal
 * @param {number} empid - Employee ID
 * @param {number} deptId - Department ID
 * @param {boolean} country - Country code (for future use)
 */
function openBusinessTripApplyModal(empid, deptId, country)
```

## Form Features

### Automatic Features
✅ Loads employee name and ID from database
✅ Loads cities from `saudi_cities` table
✅ Auto-finds direct supervisor as first approver
✅ Date validation (end date must be after start date)
✅ City validation (from ≠ to for domestic trips)
✅ Field validation based on trip type

### Dynamic Fields

**When Trip Type = "Domestic"**
- Show: Transportation Type (Own Car / Rental)
- Show: City selection (From/To)
- Hide: Country and Visa fields

**When Trip Type = "International"**
- Hide: Transportation Type
- Hide: City selection
- Show: Destination Country (required)
- Show: Visa Required (checkbox, optional)

## Testing the Integration

### Test 1: Form Submission
```
1. Login as employee
2. Click "Apply Business Trip" button
3. Fill form with test data:
   - Trip Type: Domestic
   - Transportation: Own Car
   - From: Jeddah
   - To: Riyadh
   - Dates: Dec 15-18, 2025
4. Submit form
5. Should see success message with request ID (BT-20251214-00001)
```

### Test 2: Email Notification
```
1. Submit business trip request
2. Check email inbox of supervisor (first approver)
3. Should receive HTML email with:
   - Request ID
   - Employee name
   - Trip dates
   - "Review Request" button
4. Click button should go to view page (create next)
```

### Test 3: Approval Chain
```
1. Submit request
2. Supervisor approves (Level 1)
3. HR Senior BP receives notification
4. HR Senior BP approves (Level 2)
5. Finance Officer receives notification
6. Finance Officer approves (Level 3)
7. Request status = "approved"
8. Employee receives approval email
```

## Customization Options

### Change Approval Levels
1. Go to `app_settings.php`
2. Click "Approval" tab
3. Find "Business Trip" section
4. Modify levels:
   - Add more approvers
   - Remove levels
   - Change order

### Change Default Cities
In `businessTripForm_HTML()` function in `jquery.app.js`:

```javascript
// Find this section:
<select name="from_city_id" id="from_city_id" class="form-control-modern" required>
    <option value="">Select departure city</option>
    <option value="1">Jeddah</option>
    <option value="2">Riyadh</option>
    <option value="3">Dammam</option>
</select>

// Add/modify options as needed (use saudi_cities table IDs)
```

### Customize Email Template
File: `/includes/PHPMailerMaster/business_trip_email_template.html`

Available variables:
- `{{COMPANY_NAME}}` - Company name
- `{{REQUEST_TYPE}}` - "Business Trip Request"
- `{{APPROVER_NAME}}` - Recipient name
- `{{REQUEST_ID}}` - Request ID
- `{{EMPLOYEE_NAME}}` - Employee name
- `{{TRIP_TYPE}}` - "Domestic" or "International"
- `{{TRIP_DATES}}` - Date range
- `{{STATUS}}` - Current status
- `{{REQUEST_URL}}` - Link to request details

## File Locations

| File | Location | Purpose |
|------|----------|---------|
| Database Migration | `sql/create_business_trip_table.sql` | Creates emp_business_trip table |
| Type Registration | `sql/register_business_trip_request_type.sql` | Registers business_trip in approval system |
| AJAX Handler | `includes/ajaxFile/ajaxBusinessTrip.php` | Processes submissions and approvals |
| Email Template | `includes/PHPMailerMaster/business_trip_email_template.html` | HTML email format |
| JS Functions | `assets/js/jquery.app.js` | Modal and form functions |
| Implementation Guide | `readme/BUSINESS_TRIP_REQUEST_SETUP.md` | Detailed documentation |

## Troubleshooting

### Button doesn't open modal
**Check:**
- JavaScript functions are properly included
- `jquery.app.js` is loaded on the page
- console for JavaScript errors

### Form doesn't submit
**Check:**
- `ajaxBusinessTrip.php` file exists and is readable
- Employee has supervisor assigned
- All required fields are filled

### Email not sent
**Check:**
- SMTP settings configured in `app_settings`
- Approver email address is valid
- Check email logs in error_log

### Cities dropdown empty
**Check:**
- `saudi_cities` table exists
- Table has data
- Use dynamic loading instead of hardcoded options

## Next Steps

After integration, create:
1. **View Business Trip Page** (`view_business_trip.php`) - Display request details
2. **All Business Trips Page** (`all_business_trips.php`) - List all requests with filtering
3. **Approval Interface** - In existing approval page or separate page
4. **Trip History** - Employee can see past trips

## Support

For issues or questions:
1. Check the detailed guide: `readme/BUSINESS_TRIP_REQUEST_SETUP.md`
2. Review error logs: `error_log`
3. Check database schema: `information_schema.TABLES`
4. Test AJAX endpoint: Call `ajaxBusinessTrip.php` directly

---

**Integration Status:** Ready for deployment  
**Last Updated:** 2025-12-14
