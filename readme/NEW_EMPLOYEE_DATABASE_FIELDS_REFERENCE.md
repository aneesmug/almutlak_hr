# New Employee Registration - Database Fields & SQL Reference

## Employee Data Captured from Form

The new employee form captures these fields in the `employees` table:

### Personal Information
| Field | Type | Required | Validation | Sample Value |
|-------|------|----------|-----------|--------------|
| name | VARCHAR | ✅ Yes | Text | "Ahmed Mohamed Ali" |
| emp_id | INT | ✅ Yes | Numeric, Remove commas | 5678 |
| iqama | VARCHAR | ✅ Yes | 10 digits | "1234567890" |
| sex | TINYINT | ✅ Yes | 1 = Male, 2 = Female | 1 |
| dob | DATE | ✅ Yes | Gregorian format | "1990-05-15" |
| dob_h | VARCHAR | ✅ Yes | Hijri format | "1411-11-20" |
| country | INT | ✅ Yes | Foreign key to countries | 191 (Saudi Arabia) |
| mar_status | VARCHAR | ⚠️ No | "married" or "single" | "single" |
| blood_type | VARCHAR | ⚠️ No | A+, B+, O+, AB+, A-, B-, O-, AB- | "O+" |

### Contact Information
| Field | Type | Required | Validation | Sample Value |
|-------|------|----------|-----------|--------------|
| mobile | VARCHAR | ✅ Yes | Extract digits only | "0599999999" |
| emg_mobile | VARCHAR | ✅ Yes | Emergency contact | "0501234567" |
| emg_name | VARCHAR | ✅ Yes | Text | "Fatima Al-Mutlak" |
| email | VARCHAR | ⚠️ No | Email format validation | "ahmed@almutlak.com" |
| address | VARCHAR | ✅ Yes | Text | "Riyadh, Saudi Arabia" |
| insurance_no | VARCHAR | ⚠️ No | Text | "INS123456" |

### Employment Information
| Field | Type | Required | Validation | Sample Value |
|-------|------|----------|-----------|--------------|
| dept | INT | ✅ Yes | Foreign key to department | 5 |
| sectin_nme | INT | ✅ Yes | Foreign key to section | 12 |
| emptype | VARCHAR | ✅ Yes | "Manager", "Supervisor", "Supporter" | "Supervisor" |
| actual_Job | INT | ✅ Yes | Foreign key to ac_jobs | 8 |
| comp_no | INT | ✅ Yes | Foreign key to companies | 14 |
| joining_date | DATE | ✅ Yes | Gregorian format | "2025-01-15" |
| salary | DECIMAL | ✅ Yes | Remove commas, Numeric | 5000.00 |
| emp_sup_type | INT | ✅ Yes | Foreign key to sponsorship | 1 |
| probation | VARCHAR | ✅ Yes | "3 Months" or "6 Months" | "3 Months" |

### Compensation & Banking
| Field | Type | Required | Validation | Sample Value |
|-------|------|----------|-----------|--------------|
| bank_name | INT | ✅ Yes | Foreign key to bank_list | 5 |
| iban | VARCHAR | ✅ Yes | Remove spaces | "SA9210000000000123456" |
| payment_type | INT | ✅ Yes | 1 = Bank, 2 = Cash | 1 |
| vacation_days | INT | ⚠️ No | Auto-populated from vac_period | 30 |
| vac_period | INT | ✅ Yes | Foreign key to contract_period | 2 |

### Identification & Documentation
| Field | Type | Required | Validation | Sample Value |
|-------|------|----------|-----------|--------------|
| iqama_exp_g | DATE | ✅ Yes | Gregorian expiry | "2026-05-15" |
| iqama_exp | VARCHAR | ✅ Yes | Hijri expiry | "1447-11-20" |
| passport_number | VARCHAR | ⚠️ No | Text | "J123456789" |
| passport_exp | DATE | ⚠️ No | Gregorian format | "2028-06-20" |

### Insurance & Additional
| Field | Type | Required | Validation | Sample Value |
|-------|------|----------|-----------|--------------|
| insurance_class | VARCHAR | ⚠️ No | A, B, C, CLT, VIP | "A" |
| insurance_exp | DATE | ⚠️ No | Gregorian format | "2025-12-31" |
| gosi | DECIMAL | ⚠️ No* | Percentage (if Saudi national) | 9.75 |
| t_shirt_size | VARCHAR | ⚠️ No | Text | "L" |

*GOSI (General Organization for Social Insurance) is only required for Saudi nationals (country = 191)

---

## Lookup Tables Queried During Notification

### 1. Department Table
```sql
SELECT dep_nme_ar 
FROM `department` 
WHERE `id` = ?
```

**Purpose:** Get department name for email display
**Result Field:** `department_name`

### 2. Job Title Table
```sql
SELECT job_ar 
FROM `ac_jobs` 
WHERE `id` = ?
```

**Purpose:** Get job/position title for email display
**Result Field:** `job_title`

### 3. Company Table
```sql
SELECT comp_name 
FROM `companies` 
WHERE `comp_id` = ?
```

**Purpose:** Get company name for email display
**Result Field:** `comp_name`

---

## Notification Recipient Query

```sql
SELECT id, fullname, email, user_type, emp_id
FROM admin_login
WHERE status = 1 
AND (user_type = 'hr' OR user_type = 'gm')
AND email IS NOT NULL
AND email != ''
AND email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$'
ORDER BY user_type ASC, fullname ASC
```

**Filters:**
1. **status = 1** - User must be active
2. **user_type IN ('hr', 'gm')** - Must be HR or GR staff
3. **email IS NOT NULL** - Email field exists
4. **email != ''** - Email not empty
5. **email REGEXP** - Valid email format

---

## Email Template Placeholders

The following data is inserted into the email template:

```html
<!-- Recipient -->
APPROVER_NAME          → "Fatima Al-Mutlak"

<!-- Employee Details -->
EMPLOYEE_NAME          → "Ahmed Mohamed Ali"
EMPLOYEE_ID            → "5678"
IQAMA_NUMBER           → "1234567890"
EMPLOYEE_EMAIL         → "ahmed@almutlak.com"
EMPLOYEE_MOBILE        → "0599999999"
DEPARTMENT_NAME        → "أدارة الموارد البشرية" (HR Department in Arabic)
JOB_TITLE              → "مسؤول الموارد البشرية" (HR Officer in Arabic)
JOINING_DATE           → "2025-01-15"
SALARY                 → "5,000.00" (formatted with commas)
COMPANY_NAME           → "Al-Mutlak Co."

<!-- URLs -->
REQUEST_URL            → "https://hr.almutlak.com/view_employee.php?id=5678"
ALL_EMPLOYEES_URL      → "https://hr.almutlak.com/all_employee_list.php"

<!-- Logo -->
LOGO_URL               → "https://hr.almutlaksystem.com/assets/logo/logo_color_sm.png"
```

---

## Auto-Generated Fields

These fields are automatically set by the system:

| Field | Value | Source |
|-------|-------|--------|
| created_at | Current timestamp | System function |
| fly | 0 | Default (not traveling) |
| avatar | Path based on sex | Determined by sex field (1/2) |

---

## Database Insert Query Structure

```php
$columns = ['name', 'emp_id', 'iqama', 'mobile', 'salary', ...];
$placeholders = [':name', ':emp_id', ':iqama', ':mobile', ':salary', ...];

INSERT INTO `employees` 
(name, emp_id, iqama, mobile, salary, dept, email, comp_no, avatar, ...)
VALUES
(:name, :emp_id, :iqama, :mobile, :salary, :dept, :email, :comp_no, :avatar, ...)
```

**Total Allowed Columns:** 41 fields
**Whitelisted:** Only form fields that exist in employees table

---

## Validation Rules Applied

### String Fields
- Trimmed of whitespace
- HTML special characters NOT escaped (preserved as-is)

### Numeric Fields
- Integer/Decimal type checking
- Commas removed from numbers (e.g., "5,000" → "5000")

### Email Field
- PHP FILTER_SANITIZE_EMAIL applied
- Regex validation in recipient query

### Phone Fields
- Only digits extracted (e.g., "0599-999-9999" → "0599999999")
- Leading zero preserved

### Salary/IBAN
- Commas removed for salary
- Spaces removed for IBAN

### Required Fields
Validation check (must not be empty):
- name ✅
- emp_id ✅
- iqama ✅
- mobile ✅
- salary ✅

---

## Data Types Reference

| Type | Size | Example |
|------|------|---------|
| VARCHAR | 255 | "Text string" |
| INT | Integer | 5678 |
| DECIMAL(10,2) | Decimal | 5000.00 |
| DATE | YYYY-MM-DD | "2025-01-15" |
| TINYINT | 0-255 | 1, 2 |
| TEXT | Large text | "Long description" |

---

## Field Mapping for Email Display

| Form Field | Database Field | Email Placeholder | Format |
|-----------|----------------|-------------------|--------|
| name | name | EMPLOYEE_NAME | As-is |
| emp_id | emp_id | EMPLOYEE_ID | As-is |
| iqama | iqama | IQAMA_NUMBER | As-is |
| email | email | EMPLOYEE_EMAIL | As-is |
| mobile | mobile | EMPLOYEE_MOBILE | As-is |
| department (ID) | dept | DEPARTMENT_NAME | Lookup→dep_nme_ar |
| actual_Job (ID) | actual_Job | JOB_TITLE | Lookup→job_ar |
| joining_date | joining_date | JOINING_DATE | As-is |
| salary | salary | SALARY | Formatted (commas) |
| comp_no (ID) | comp_no | COMPANY_NAME | Lookup→comp_name |

---

## Audit Log JSON Structure

```json
{
  "emp_id": 5678,
  "action": "new_employee_notification_sent",
  "description": "Notification sent to HR/GR staff for new employee",
  "details": {
    "new_emp_id": "5678",
    "new_emp_name": "Ahmed Mohamed Ali",
    "recipient_id": "1234",
    "recipient_name": "Fatima Al-Mutlak",
    "recipient_type": "hr",
    "sent_at": "2025-01-20 14:30:45"
  },
  "created_by": "2539",
  "created_at": "2025-01-20 14:30:45"
}
```

---

## SMTP Configuration Settings

Required settings in `app_settings` table:

| Setting | Example Value | Notes |
|---------|---------------|-------|
| smtp_host | mail.almutlak.com | SMTP server address |
| smtp_port | 587 | Port (usually 25, 587, 465) |
| smtp_user | notifications@almutlak.com | SMTP username |
| smtp_pass | ******* | SMTP password (encrypted) |
| from_email | hr@almutlak.com | Email FROM address |
| from_name | Al Mutlak HR System | Display name in emails |
| smtp_encryption | tls | TLS, SSL, or empty |

---

## Sample Email Recipients Query Result

```
id  fullname              email                          user_type  emp_id
1   Fatima Al-Mutlak     fatima@almutlak.com            hr         1234
2   Mohammed Hassan      mohammed@almutlak.com         hr         1235
5   Sara AlDossary       sara@almutlak.com              gm         1250
12  Noor Alsalem         noor@almutlak.com              gm         1260
```

---

## Browser Notification Data

For user_type = 'gm' (GR staff):

```sql
INSERT INTO `user_notifications` 
(`emp_id`, `title`, `message`, `url`, `created_at`)
VALUES 
(1250, "New Employee Registered", "Employee: Ahmed Mohamed Ali (ID: 5678) has been registered in the system", "view_employee.php?id=5678", NOW())
```

---

## Testing SQL Queries

### Count HR/GR Staff to be notified:
```sql
SELECT COUNT(*) as count
FROM admin_login
WHERE status = 1
AND (user_type = 'hr' OR user_type = 'gm')
AND email IS NOT NULL
AND email != ''
AND email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$';
```

### View recent notifications:
```sql
SELECT id, emp_id, title, message, url, created_at
FROM user_notifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;
```

### Check activity log for new employees:
```sql
SELECT emp_id, action, created_at, details
FROM activity_log
WHERE action = 'new_employee_notification_sent'
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;
```

### Get employee lookup data:
```sql
SELECT 
    e.emp_id,
    e.name,
    d.dep_nme_ar as department,
    j.job_ar as job_title,
    c.comp_name as company
FROM employees e
LEFT JOIN department d ON e.dept = d.id
LEFT JOIN ac_jobs j ON e.actual_Job = j.id
LEFT JOIN companies c ON e.comp_no = c.comp_id
WHERE e.emp_id = 5678;
```

---

**Database Schema Version:** As of January 2025
**Compatible with:** MySQL 5.7+

