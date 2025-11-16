# Vacation Flight Dates and Remarks Update - Implementation Summary

## Overview
Added two new date fields (Departure Date and Arrival Date) and updated the Remarks field for Annual Fly vacation requests.

## Changes Made

### 1. Database Changes
**File:** `add_flight_dates_to_vacation.sql`

Added two new columns to the `emp_vacation` table:
- `departure_date` (DATE, NULL) - Flight departure date
- `arrival_date` (DATE, NULL) - Flight arrival date

**Migration Steps:**
```bash
# Run this SQL in your database
mysql -u root -p almutlak_db < add_flight_dates_to_vacation.sql
```

Or manually run:
```sql
ALTER TABLE `emp_vacation` 
ADD COLUMN `departure_date` DATE DEFAULT NULL COMMENT 'Flight departure date (for Fly + Annual vacations only)' AFTER `return_date`,
ADD COLUMN `arrival_date` DATE DEFAULT NULL COMMENT 'Flight arrival date (for Fly + Annual vacations only)' AFTER `departure_date`;
```

### 2. Frontend Changes (JavaScript)
**File:** `assets/js/jquery.app.js`

#### a) HTML Form Updates in `vacationApply_HTML()`
- Added new "Flight Dates" section with:
  - Departure Date field
  - Arrival Date field
- Removed required asterisk (*) from Remarks/Notes field

#### b) Toggle Logic Updates in `toggleVacationFields()`
- Updated to show/hide `#flightDatesSection` 
- Shows flight dates AND remarks ONLY when:
  - Vacation Type = "Fly"
  - Fly Type = "annual"

#### c) Date Picker Initialization in `willOpen()`
- Added datepicker for `departure_date`
- Added datepicker for `arrival_date`
- Bidirectional validation (departure can't be after arrival)

#### d) Validation Updates in `preConfirm()`
- Added validation for departure and arrival dates
- Only validates when Fly + Annual is selected
- Remarks field is NOT required (no validation added)

### 3. Backend Changes (PHP)
**File:** `includes/ajaxFile/ajaxVacation.php`

#### a) Input Sanitization (Line ~231)
```php
$departure_date = escape_string($_POST['departure_date'] ?? '');
$arrival_date = escape_string($_POST['arrival_date'] ?? '');
$notes = escape_string($_POST['remarks'] ?? ''); // Changed from 'notes' to 'remarks'
```

#### b) Database Insert (Line ~520)
Updated INSERT statement to include:
- `departure_date` column
- `arrival_date` column
- Proper NULL handling for empty dates

Updated bind_param to include new fields:
```php
mysqli_stmt_bind_param($stmt_vac, "sssssssisssds", 
    $emp_id, 
    $vac_type, 
    $fly_type, 
    $replacement_per,
    $start_date, 
    $end_date,
    $departure_date_val,  // NEW
    $arrival_date_val,    // NEW
    $vacdays_int,
    $notes,
    $vacation_salary_type,
    $attachment_path, 
    $encashment_amount_val,
    $request_inv_no
);
```

## Field Visibility Rules

### When "Fly + Annual" is selected:
✅ Fly Type Section  
✅ Replacement Person  
✅ Start Date & Return Date  
✅ Salary Type Selection  
✅ **Departure Date** (NEW - Required)  
✅ **Arrival Date** (NEW - Required)  
✅ **Remarks** (NEW - Optional)

### When "Fly + Emergency" is selected:
✅ Fly Type Section  
✅ Replacement Person  
✅ Start Date & Return Date  
❌ Salary Type Selection  
❌ Departure Date  
❌ Arrival Date  
❌ Remarks

### When "Local Vacation + Annual" is selected:
✅ Fly Type Section  
✅ Replacement Person  
✅ Start Date & Return Date  
✅ Salary Type Selection  
❌ Departure Date  
❌ Arrival Date  
❌ Remarks

## Testing Checklist

- [ ] Run database migration SQL
- [ ] Test Fly + Annual vacation:
  - [ ] Departure and Arrival date fields appear
  - [ ] Remarks field appears (optional)
  - [ ] Date validation works (departure before arrival)
  - [ ] Form submits successfully
  - [ ] Data saves to database correctly
  
- [ ] Test Fly + Emergency vacation:
  - [ ] Flight dates do NOT appear
  - [ ] Remarks does NOT appear
  
- [ ] Test Local Vacation + Annual:
  - [ ] Flight dates do NOT appear
  - [ ] Remarks does NOT appear

- [ ] Test form submission:
  - [ ] With all fields filled
  - [ ] With remarks empty (should work)
  - [ ] With flight dates empty (should show validation error for Fly+Annual)

## Files Modified

1. ✅ `add_flight_dates_to_vacation.sql` - NEW (Database migration)
2. ✅ `assets/js/jquery.app.js` - UPDATED
   - vacationApply_HTML() function
   - toggleVacationFields() function
   - willOpen() callback
   - preConfirm() validation
3. ✅ `includes/ajaxFile/ajaxVacation.php` - UPDATED
   - Input sanitization
   - INSERT statement
   - Parameter binding

## Notes

- **Remarks field is NOT required** - Can be left empty
- **Flight dates ARE required** - Only for Fly + Annual vacation type
- **Database columns accept NULL** - Safe for old records without flight dates
- **Backward compatible** - Existing vacation records will not be affected
