# Al-Mutlak WMS - Error Fixes Summary

## Errors Fixed (December 16, 2025)

### 1. PHP Deprecated: mysqli_real_escape_string() - Passing null

**Files Fixed:**
- `includes/ajaxFile/ajaxVacation.php` (Lines 659-660)

**Issue:** 
```php
// BEFORE (Deprecated):
$departure_date_sql = ... mysqli_real_escape_string($conDB, $departure_date) ...
$arrival_date_sql = ... mysqli_real_escape_string($conDB, $arrival_date) ...
```

**Solution:**
```php
// AFTER (Fixed):
$departure_date_sql = ... mysqli_real_escape_string($conDB, (string)$departure_date) ...
$arrival_date_sql = ... mysqli_real_escape_string($conDB, (string)$arrival_date) ...
```

Cast variables to string type before passing to mysqli_real_escape_string to prevent null being passed as parameter.

---

### 2. PHP Deprecated: htmlspecialchars() - Passing null

**Files Fixed:**
- `includes/helper_functions.php` (Lines 3385-3410, 3397-3419)

**Issue:**
```php
// BEFORE (Deprecated):
htmlspecialchars($employee_name)
htmlspecialchars($passport_expiry_formatted)
htmlspecialchars($departure_formatted)
htmlspecialchars($arrival_formatted)
htmlspecialchars($request_inv_no)
htmlspecialchars($site_title)
```

**Solution:**
```php
// AFTER (Fixed):
htmlspecialchars((string)($employee_name ?? 'N/A'))
htmlspecialchars((string)($passport_expiry_formatted ?? 'N/A'))
htmlspecialchars((string)($departure_formatted ?? 'N/A'))
htmlspecialchars((string)($arrival_formatted ?? 'N/A'))
htmlspecialchars((string)($request_inv_no ?? 'N/A'))
htmlspecialchars((string)($site_title ?? 'HR'))
```

Cast to string and provide null coalescing operator (??) with default values to prevent null from being passed.

---

### 3. PHP Warning: Undefined array key "iqama_exp"

**File Fixed:**
- `includes/ajaxFile/ajaxEmployee.php` (Lines 1238-1246)

**Issue:**
```php
// BEFORE:
if ($_POST['iqama_exp']) { // Undefined key warning
    $iqama_exp = mysqli_real_escape_string($conDB, $_POST['iqama_exp']);
    // ...
} else {
    $iqama_exp_g = mysqli_real_escape_string($conDB, $_POST['iqama_exp_g']); // Could be undefined
}
```

**Solution:**
```php
// AFTER:
if (!empty($_POST['iqama_exp'])) {
    $iqama_exp = mysqli_real_escape_string($conDB, (string)$_POST['iqama_exp']);
    $iqama_exp_gup = $DateConv->HijriToGregorian($iqama_exp, $format);
    $iqama_exp_g = date("Y-m-d", strtotime($iqama_exp_gup));
} else if (!empty($_POST['iqama_exp_g'])) {
    $iqama_exp_g = mysqli_real_escape_string($conDB, (string)$_POST['iqama_exp_g']);
    $iqama_exp = $DateConv->GregorianToHijri($iqama_exp_g, $format);
} else {
    $iqama_exp = null;
    $iqama_exp_g = null;
}
```

Use !empty() instead of direct isset() to avoid undefined key warnings and provide proper null handling.

---

### 4. PHP Warning: Undefined array keys in Portfolio Upload

**File Fixed:**
- `includes/ajaxFile/ajaxEmployee.php` (Line 1205-1210)

**Issue:**
```php
// BEFORE:
$emp_id = $_POST['emp_id']; // Could trigger warning
$title_up = $_POST['title']; // Could trigger warning
$description_up = mysqli_real_escape_string($conDB, $_POST['description']); // Could trigger warning
```

**Solution:**
```php
// AFTER:
$emp_id = $_POST['emp_id'] ?? null;
$title_up = $_POST['title'] ?? null;
$description_up = isset($_POST['description']) ? mysqli_real_escape_string($conDB, (string)$_POST['description']) : '';
```

Use null coalescing operator (??) and isset() checks to safely access array keys.

---

## Errors NOT in Current Codebase (Production Server Only)

### 1. Missing Email Template Files

**Issue:** 
```
load_email_template: Template file not found: /home/almutlak/public_html/hr/includes/PHPMailerMaster/vacation_request_email_template.html
```

**Status:** File EXISTS at `includes/PHPMailerMaster/vacation_request_email_template.html`

**Investigation:** This error appears in production server logs but the template file exists. Possible causes:
- Server path difference (Linux vs Windows)
- File permissions issue
- Caching issue
- The file path in logs uses absolute Linux path `/home/almutlak/` which differs from development environment

**Action:** No fix needed in code. File exists in directory. If issue persists in production, check:
1. File permissions (should be readable)
2. Path resolution in production environment
3. Check if file needs to be deployed/uploaded to production server

---

### 2. Employee Contract Data Not Found (emp_id 5433)

**Error:**
```
getCalculatedBalance EXCEPTION for emp_id 5433: Employee contract data not found for emp_id: 5433
```

**Cause:** Employee with ID 5433 does not have contract data in the database or is inactive.

**Solution:** Data validation - not a code error but a data issue. Ensure employee record exists and is properly configured.

---

## Best Practices Applied

1. **Type Casting:** Always cast variables to appropriate types before using in functions
2. **Null Safety:** Use null coalescing operator (??) to provide default values
3. **Array Key Validation:** Always check array keys with isset() or !empty() before access
4. **String Handling:** Cast all strings to (string) before using in htmlspecialchars() and mysqli_real_escape_string()

---

## Files Modified

1. ✅ `includes/ajaxFile/ajaxVacation.php`
2. ✅ `includes/helper_functions.php`
3. ✅ `includes/ajaxFile/ajaxEmployee.php`

---

## Testing Recommendations

1. Test vacation request submission with travel date information
2. Test employee iqama expiry date updates  
3. Test portfolio/document uploads for employees
4. Monitor error logs for remaining deprecation warnings
5. Verify email templates load correctly for all request types

---

**Date Fixed:** December 16, 2025
**Environment:** Development (Windows XAMPP)
**Status:** All fixable errors resolved ✅
