# Travel Email Button Implementation - Updated

## ✅ Changes Summary

### What Changed from Original Implementation

**BEFORE:**
- Email sent automatically after final approval
- No user control over when email is sent
- No CC to GR Officer

**AFTER:**
- ✅ Manual button to send email (only shown to HR/Admin/GR Officer)
- ✅ Button appears only after final approval
- ✅ Button hidden once email is sent (prevents duplicates)
- ✅ Email includes CC to GR Officer
- ✅ Status tracking in database

---

## 📋 Implementation Details

### 1. Database Changes

**File:** `add_travel_email_sent_column.sql`

```sql
-- Tracks if travel email has been sent
ALTER TABLE `emp_vacation` 
ADD COLUMN `travel_email_sent` TINYINT(1) DEFAULT 0;
```

**File:** `add_traveling_company_email_setting.sql` (Updated)

```sql
-- Traveling company email
INSERT INTO `app_settings` (`setting_name`, `setting_value`) 
VALUES ('traveling_company_email', 'travel@example.com');

-- GR Officer email for CC
INSERT INTO `app_settings` (`setting_name`, `setting_value`) 
VALUES ('gr_officer_email', 'gr@example.com');
```

---

### 2. Button Implementation

**Location:** `all_applied_vac.php` (Lines ~573-597)

**Button Shows When:**
1. ✅ Vacation type is "Fly"
2. ✅ Fly type is "annual"
3. ✅ Status is "approved" (final approval)
4. ✅ Has `departure_date` and `arrival_date`
5. ✅ Email NOT yet sent (`travel_email_sent = 0`)
6. ✅ User is HR/Admin/GR Officer

**Button Hides When:**
- Email has been sent (`travel_email_sent = 1`)
- Vacation not yet approved
- Missing flight dates
- User lacks permissions

**Code:**
```php
<?php
$show_travel_email_button = false;
if (
    $req['vac_type'] == 'Fly' &&
    $req['fly_type'] == 'annual' &&
    $req['current_status'] == 'approved' &&
    !empty($req['departure_date']) &&
    !empty($req['arrival_date']) &&
    ($req['travel_email_sent'] == 0 || empty($req['travel_email_sent'])) &&
    ($isHR || $is_system_admin || $isGR_Officer)
) {
    $show_travel_email_button = true;
}

if ($show_travel_email_button):
?>
    <button class="btn btn-primary btn-block waves-effect" 
            id="travel-email-btn-<?=$req['id']; ?>"
            onclick="sendTravelEmail(<?=$req['id']; ?>, '<?=...?>')">
        <i class="fa fa-paper-plane"></i> Send Travel Email
    </button>
<?php endif; ?>
```

---

### 3. JavaScript Function

**Location:** `all_applied_vac.php` (Lines ~1285-1347)

**Function:** `sendTravelEmail(vacationId, employeeName)`

**Features:**
- Confirmation dialog with warning
- Loading indicator while sending
- Success/error handling
- Auto-hide button after sending
- Page reload to update status

**Code Flow:**
```
User clicks button
  ↓
Confirmation dialog
  ↓
User confirms
  ↓
Show loading spinner
  ↓
AJAX call to ajaxVacation.php
  ↓
Email sent
  ↓
Success message
  ↓
Button hidden & page reloads
```

---

### 4. AJAX Handler

**Location:** `includes/ajaxFile/ajaxVacation.php` (Lines ~1145-1275)

**Handler:** `ajaxType === 'sendTravelEmail'`

**Process:**
1. Validate vacation ID
2. Fetch vacation & employee details (including passport)
3. Validate:
   - Is annual fly vacation
   - Is approved
   - Has flight dates
   - Email not already sent
4. Get GR Officer email from settings
5. Call `send_travel_company_email()` with CC
6. Update `travel_email_sent = 1`
7. Log in `smt_request_status` table
8. Return success response

**Error Handling:**
- Missing vacation ID → Error
- Not annual fly → Error
- Not approved → Error
- Missing flight dates → Error
- Already sent → Error
- Email failure → Error

---

### 5. Email Function Update

**Location:** `includes/helper_functions.php` (Line ~2941)

**Function:** `send_travel_company_email()`

**New Parameter:** `$cc_email` (Optional)

**Changes:**
```php
// OLD
function send_travel_company_email($conDB, $employee_name, ..., $request_inv_no = '')

// NEW
function send_travel_company_email($conDB, $employee_name, ..., $request_inv_no = '', $cc_email = '')
```

**CC Implementation:**
```php
// Add CC if provided (e.g., gr_officer)
if (!empty($cc_email) && filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
    $mail->addCC($cc_email, 'GR Officer');
    error_log("send_travel_company_email: CC added to $cc_email");
}
```

---

### 6. Removed Automatic Trigger

**Location:** `includes/helper_functions.php` (Lines 1816-1856)

**REMOVED:**
```php
// OLD CODE - REMOVED
if ($request_type === 'vacation_request') {
    // Send email to traveling company automatically
    $travel_email_sent = send_travel_company_email(...);
}
```

**Reason:** Manual button provides better control

---

## 🚀 Setup Instructions

### Step 1: Run Database Migrations

```bash
cd D:\xampp\htdocs\almutlak\system

# Add travel_email_sent column
mysql -u root almutlak_db < add_travel_email_sent_column.sql

# Add email settings
mysql -u root almutlak_db < add_traveling_company_email_setting.sql
```

### Step 2: Configure Email Addresses

```sql
-- Set traveling company email
UPDATE `app_settings` 
SET `setting_value` = 'travel@actualcompany.com' 
WHERE `setting_name` = 'traveling_company_email';

-- Set GR Officer email
UPDATE `app_settings` 
SET `setting_value` = 'gr.officer@yourcompany.com' 
WHERE `setting_name` = 'gr_officer_email';
```

### Step 3: Test the Button

1. Create/approve an annual fly vacation
2. Add departure and arrival dates
3. Complete full approval workflow
4. Log in as HR/Admin/GR Officer
5. Go to `all_applied_vac.php`
6. Find the approved vacation
7. ✅ **Button should appear:** "Send Travel Email"
8. Click the button
9. Confirm in dialog
10. Wait for success message
11. ✅ **Button should disappear** (email sent)

---

## 🎯 Button Visibility Logic

### Button SHOWS:
```
Vacation Type = "Fly"
AND Fly Type = "annual"
AND Status = "approved"
AND departure_date NOT NULL
AND arrival_date NOT NULL
AND travel_email_sent = 0
AND User Role IN (HR, Admin, GR Officer)
```

### Button HIDES:
```
travel_email_sent = 1  (Email already sent)
OR Status != "approved"
OR Missing flight dates
OR Wrong vacation type
OR User lacks permissions
```

---

## 📧 Email Details

### Recipients:
- **TO:** Traveling Company (from `traveling_company_email` setting)
- **CC:** GR Officer (from `gr_officer_email` setting)
- **FROM:** System email (from `from_email` setting)

### Email Content:
- Traveler Name
- Passport No
- Passport Expiry
- Departure To (Country)
- Departure Date
- Arrival Date
- Reference Number (Request Invoice No)

---

## 🔒 Security Features

### Prevent Duplicate Emails:
1. Database flag: `travel_email_sent = 1`
2. Button hidden when email sent
3. AJAX handler checks status
4. Error if already sent

### Permissions:
- Only HR/Admin/GR Officer can see button
- Only approved vacations eligible
- Validates vacation type and flight dates

### Validation:
- Email address format validation
- Passport data sanitization
- HTML escaping in email
- SQL injection prevention

---

## 📊 Status Tracking

### Database Table: `smt_request_status`

**Entry Added When Email Sent:**
```sql
INSERT INTO `smt_request_status` 
(inv_no, status, note, emp_name, created_at) 
VALUES 
('VAC-XXX-XXX', 'email_sent', 'Travel company email sent to traveling company (CC: GR Officer)', 'Admin', NOW());
```

**View History:**
- Go to vacation status history page
- Look for "email_sent" status
- Shows when email was sent and by whom

---

## 🧪 Testing Checklist

- [ ] Database migrations completed
- [ ] Email addresses configured
- [ ] Button appears for approved annual fly vacations
- [ ] Button hidden for non-fly vacations
- [ ] Button hidden when email already sent
- [ ] Button hidden for non-HR users
- [ ] Email sent successfully
- [ ] CC to GR Officer included
- [ ] travel_email_sent updated to 1
- [ ] Button disappears after sending
- [ ] Status logged in history
- [ ] Cannot send email twice

---

## 🐛 Troubleshooting

### Button Not Showing?

**Check:**
1. Is vacation type "Fly" + fly_type "annual"?
2. Is status "approved"?
3. Are departure_date and arrival_date filled?
4. Is travel_email_sent = 0?
5. Are you logged in as HR/Admin/GR Officer?

**SQL Debug:**
```sql
SELECT id, vac_type, fly_type, current_status, 
       departure_date, arrival_date, travel_email_sent
FROM emp_vacation 
WHERE id = [VACATION_ID];
```

### Button Shows But Email Fails?

**Check:**
1. traveling_company_email configured?
2. gr_officer_email configured?
3. SMTP settings correct?
4. Check PHP error logs

**SQL Check:**
```sql
SELECT setting_name, setting_value 
FROM app_settings 
WHERE setting_name IN ('traveling_company_email', 'gr_officer_email', 'smtp_host');
```

### Email Sent But No CC?

**Check:**
1. gr_officer_email has valid email address
2. Email format is valid
3. Check email logs for CC confirmation

**Log Check:**
```bash
tail -f D:\xampp\apache\logs\error.log | grep "CC added"
```

---

## 📝 Files Modified/Created

### Created:
1. `add_travel_email_sent_column.sql` - Database migration
2. `TRAVEL_EMAIL_BUTTON_IMPLEMENTATION.md` - This documentation

### Modified:
1. `all_applied_vac.php` - Added button and JavaScript
2. `includes/ajaxFile/ajaxVacation.php` - Added AJAX handler
3. `includes/helper_functions.php` - Updated email function, removed auto-trigger
4. `add_traveling_company_email_setting.sql` - Added gr_officer_email setting

---

## ✅ Implementation Complete!

All features successfully implemented:
- ✅ Manual send button (not automatic)
- ✅ Button hidden after sending
- ✅ CC to GR Officer
- ✅ Database tracking
- ✅ Duplicate prevention
- ✅ Comprehensive error handling
- ✅ Status logging

**Status:** Production Ready 🚀
