# Loan Translation Implementation Guide

## Overview
The `__()` translation function doesn't work in AJAX backend files because they return JSON responses, not rendered HTML. The solution is to return **translation keys** from the backend and translate them on the frontend.

## Changes Made

### 1. Backend (`includes/ajaxFile/ajaxLoan.php`)
Changed the `check_loan_eligibility()` function to return translation keys instead of hardcoded English text:

**Old Approach (❌ Won't work):**
```php
$eligibility['message'] = __('end_of_service_message'); // __() doesn't work in AJAX files
$eligibility['message'] = 'You can apply for Housing loan...'; // Hardcoded English
```

**New Approach (✅ Correct):**
```php
$eligibility['message_key'] = 'loan_eos_eligible_message';
$eligibility['message_data'] = [
    'min' => 1000,
    'max' => 20000
];
```

#### Response Structure:
```json
{
    "status": "success",
    "eligible": true,
    "message_key": "loan_housing_eligible_message",
    "message_data": {
        "max": 15000
    },
    "max_amount": 15000,
    "min_amount": 0,
    "max_installments": 6
}
```

### 2. Frontend (`assets/js/loanHandling.js`)
Updated the `fetchEligibility()` function to handle `message_key` and `message_data`:

```javascript
// Build message from key and data
let message = '';
if (resp.message_key) {
    message = __(resp.message_key); // Translate the key
    // Replace placeholders like {max}, {date}, etc.
    if (resp.message_data) {
        for (let [key, value] of Object.entries(resp.message_data)) {
            message = message.replace(new RegExp('\\{' + key + '\\}', 'g'), value.toLocaleString());
        }
    }
}
```

### 3. Database (`insert_loan_translations.sql`)
Added new translation keys for backend messages:

| Key | English | Arabic |
|-----|---------|--------|
| `loan_eos_eligible_message` | You can apply for End of Service loan from SAR 1,000 to SAR 20,000 | يمكنك التقديم على قرض نهاية الخدمة من 1,000 ريال إلى 20,000 ريال |
| `loan_housing_no_allowance` | You do not have housing allowance... | ليس لديك بدل سكن... |
| `loan_housing_exists` | You have a housing loan from {date}... | لديك قرض سكن من تاريخ {date}... |
| `loan_housing_eligible_message` | You can apply for Housing loan up to SAR {max}... | يمكنك التقديم على قرض السكن حتى {max} ريال... |
| `loan_advance_eligible_message` | You can apply for Advance Salary up to SAR {max}... | يمكنك التقديم على سلفة راتب حتى {max} ريال... |
| `loan_invalid_type` | Invalid loan type. | نوع القرض غير صالح. |

## How to Use This Pattern in Other AJAX Files

### Backend (PHP):
```php
// Instead of:
echo json_encode(['message' => 'User not found']);

// Use:
echo json_encode([
    'message_key' => 'user_not_found',
    'message_data' => []
]);

// With placeholders:
echo json_encode([
    'message_key' => 'user_created_success',
    'message_data' => ['name' => $username, 'date' => date('Y-m-d')]
]);
```

### Frontend (JavaScript):
```javascript
if (response.message_key) {
    let msg = __(response.message_key);
    if (response.message_data) {
        for (let [key, val] of Object.entries(response.message_data)) {
            msg = msg.replace(new RegExp('\\{' + key + '\\}', 'g'), val);
        }
    }
    Swal.fire({ text: msg });
}
```

### Translation (SQL):
```sql
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('user_not_found', 'en', 'User not found'),
('user_not_found', 'ar', 'المستخدم غير موجود'),
('user_created_success', 'en', 'User {name} created on {date}'),
('user_created_success', 'ar', 'تم إنشاء المستخدم {name} في {date}');
```

## Installation Steps

1. **Import translations to database:**
   ```bash
   mysql -u root -p almutlak_db < insert_loan_translations.sql
   ```
   Or use phpMyAdmin to import `insert_loan_translations.sql`

2. **Test the loan application form:**
   - Open the loan application modal
   - Switch between loan types
   - Verify messages appear in the correct language
   - Check that placeholders (like amounts and dates) are replaced correctly

3. **Verify in both languages:**
   - Switch language in the system settings
   - Reload the page
   - Verify all messages translate properly

## Benefits of This Approach

✅ **Separation of Concerns:** Backend focuses on logic, frontend handles presentation  
✅ **Easy Translation:** All text is centralized in the database  
✅ **Dynamic Content:** Can insert values (amounts, dates) into translated strings  
✅ **Maintainable:** Changes to translations don't require backend code changes  
✅ **Consistent:** Same pattern can be used across all AJAX endpoints  

## Notes

- The `__()` function only works in PHP files that render HTML (pages that use `includes/header.php`)
- AJAX endpoints should **always** return translation keys, never translated text
- Use placeholders `{key}` in translations for dynamic values
- The frontend JavaScript `__()` function handles the actual translation
