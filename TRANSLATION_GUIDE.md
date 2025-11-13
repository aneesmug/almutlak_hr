# Al-Mutlak HR Translation Implementation Guide

## Overview
This guide explains the translation implementation for the Al-Mutlak HR system, specifically for the `vacation_report_details.php` file. The same pattern can be applied to other files in the project.

## What Was Done

### 1. Files Modified
- **vacation_report_details.php** - All hardcoded English text replaced with `__()` translation function calls
- **vacation_report_translations.sql** - SQL file with all translation keys and Arabic translations

### 2. Translation Keys Added (70+ translations)
All static text in the vacation report has been converted to use translation keys. Examples:
- `print_report` → "Print Report" / "طباعة التقرير"
- `vacation_request_report` → "Vacation Request Report" / "تقرير طلب الإجازة"
- `employee_id` → "Employee ID" / "رقم الموظف"
- And many more...

## Installation Instructions

### Step 1: Import Translation SQL
Run the SQL file to add Arabic translations to your database:

```sql
-- Execute this file in your database
SOURCE d:\xampp\htdocs\almutlak\system\vacation_report_translations.sql;

-- OR copy and paste the INSERT statements directly into phpMyAdmin
```

### Step 2: Verify Translation Function
Ensure your project has the `__()` translation function. It should be in `includes/functions.php` or similar:

```php
function __($key) {
    global $conDB, $current_language;
    
    // Default to English if no language set
    $lang = $current_language ?? 'en';
    
    // Query translation from database
    $query = mysqli_query($conDB, "SELECT `{$lang}` FROM `translations` WHERE `key` = '" . mysqli_real_escape_string($conDB, $key) . "' LIMIT 1");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        return $row[$lang];
    }
    
    // Fallback to key if translation not found
    return $key;
}
```

### Step 3: Test the Translations
1. Open `vacation_report_details.php` in your browser
2. The page should display with English text by default
3. Switch language to Arabic (if you have language switcher)
4. All text should now display in Arabic

## How to Add Translations to Other Files

### Pattern 1: Simple Text Replacement
**Before:**
```php
<h5>Vacation Details</h5>
```

**After:**
```php
<h5><?= __('vacation_details') ?></h5>
```

### Pattern 2: Text with Dynamic Content
**Before:**
```php
<p>Calculated for <?= $working_days ?> days</p>
```

**After:**
```php
<p><?= str_replace('{days}', $working_days, __('calculated_for_days')) ?></p>
```

Then in SQL:
```sql
INSERT INTO `translations` (`key`, `en`, `ar`) VALUES
('calculated_for_days', 'Calculated for {days} days', 'محسوب لـ {days} أيام');
```

### Pattern 3: Conditional Text
**Before:**
```php
<?php if ($status == 'approved'): ?>
    <span>Request Approved</span>
<?php endif; ?>
```

**After:**
```php
<?php if ($status == 'approved'): ?>
    <span><?= __('request_approved') ?></span>
<?php endif; ?>
```

## Complete List of Translation Keys for Vacation Report

### Header & Basic Info
- `print_report`
- `vacation_request_report`
- `request_id`
- `employee_id`

### Vacation Details Section
- `vacation_details`
- `vacation_type`
- `start_date`
- `return_date`
- `total_days`
- `days`
- `day_s`
- `replacement`
- `requested_on`
- `attachment`
- `view_document`

### Encashment Section
- `encashment_payment_details`
- `vacation_balance_encashment`
- `employee_opted_encash_message`
- `encashed_vacation_days`
- `based_on_available_balance`
- `daily_salary_rate`
- `monthly_salary_divided_30`
- `gosi_deduction`
- `total_encashment_payment`
- `note`
- `encashment_balance_warning`
- `zero_days`

### Vacation Salary Information
- `vacation_salary_information`
- `vacation_salary_deferred_eos`
- `employee_chosen_receive_vacation_salary_eos`
- `vacation_days`
- `payment`
- `end_of_service_settlement`
- `amount_calculated_added_final_settlement`

### Payment Details
- `payment_details`
- `salary_benefits_not_applicable`
- `working_days_salary`
- `calculated_for_days_before_vacation`
- `vacation_salary`
- `calculated_for_days`
- `ticket_payment`
- `permit_fee`
- `total_payable`

### Approval Status
- `approval_status`
- `request_rejected`
- `request_approved`
- `approved_by`
- `pending_approval`
- `level`
- `status`

### Asset Clearance
- `asset_clearance_details`
- `cleared_by`
- `asset_name`
- `serial_number`
- `asset_type`
- `clearance_status`
- `clearance_department`
- `it_equipment`
- `communication`
- `vehicle`
- `other`
- `cleared`
- `pending`
- `it`
- `administration`
- `transportation`
- `asset_clearance_required`
- `assigned_assets_message`

### Footer
- `employee_signature`
- `hr_manager_signature`
- `general_manager_signature`

### Common Terms
- `remarks`
- `approved`
- `rejected`

## Next Steps: Translating Other Files

### Priority Files to Translate
1. **generate_payroll.php** - Payroll generation page
2. **all_applied_vac.php** - Vacation requests list
3. **add_vac_emp.php** - Add vacation form
4. **dashboard.php** - Main dashboard
5. **employee_card.php** - Employee information card

### Recommended Workflow
For each file:
1. Search for hardcoded text: Look for strings in quotes that are user-facing
2. Create translation keys: Use lowercase with underscores (e.g., `total_employees`)
3. Replace text with `__('key_name')`
4. Add translations to SQL file
5. Test the page

### Example for Other Files

If you're working on `all_applied_vac.php`:

```sql
-- Add to your translations
INSERT INTO `translations` (`key`, `en`, `ar`) VALUES
('all_vacation_requests', 'All Vacation Requests', 'جميع طلبات الإجازات'),
('filter_by_status', 'Filter by Status', 'تصفية حسب الحالة'),
('employee_name', 'Employee Name', 'اسم الموظف'),
('department', 'Department', 'القسم'),
('actions', 'Actions', 'الإجراءات'),
('approve', 'Approve', 'موافقة'),
('reject', 'Reject', 'رفض');
```

Then in the PHP file:
```php
<h4><?= __('all_vacation_requests') ?></h4>
<label><?= __('filter_by_status') ?></label>
<button><?= __('approve') ?></button>
```

## Database Structure

Your `translations` table should have this structure:
```sql
CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `en` text NOT NULL,
  `ar` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Tips for Arabic Translation

1. **Right-to-Left (RTL)**: Ensure your CSS supports RTL for Arabic
2. **Font Support**: Use fonts that support Arabic characters
3. **Date Formats**: Consider using Arabic date formats when language is Arabic
4. **Number Formats**: Arabic numbers vs. Western numbers (٠١٢٣ vs. 0123)

## Language Switcher Example

Add this to your header to allow users to switch languages:

```php
<div class="language-switcher">
    <a href="?lang=en" class="<?= ($_SESSION['language'] ?? 'en') == 'en' ? 'active' : '' ?>">English</a>
    <a href="?lang=ar" class="<?= ($_SESSION['language'] ?? 'en') == 'ar' ? 'active' : '' ?>">العربية</a>
</div>

<?php
// In your session_check.php or init file
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $_SESSION['language'] = $_GET['lang'];
    $current_language = $_GET['lang'];
} else {
    $current_language = $_SESSION['language'] ?? 'en';
}
```

## Support

If you need help with:
- Adding more translation keys
- Fixing translation issues
- Implementing RTL support
- Adding more languages

Contact: Your Development Team

## Version History

- **v1.0 (2025-11-11)**: Initial implementation for vacation_report_details.php
  - 70+ translation keys added
  - Full Arabic translation provided
  - Template and guidelines created
