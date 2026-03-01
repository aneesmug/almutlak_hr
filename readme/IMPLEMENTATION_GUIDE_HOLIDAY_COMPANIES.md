# Holiday Company Assignment System

## Overview

This implementation adds company-wise holiday assignment to the Al-Mutlak WMS system. This allows you to:

1. **Assign holidays to specific companies** - Each holiday can now be assigned to one or multiple companies
2. **Filter holidays by employee company** - Vacation deductions automatically filter holidays based on the employee's assigned company
3. **Company-level flexibility** - Different companies can have different holiday calendars (e.g., different regional/national holidays)

## Changes Made

### 1. Database Changes

**New Table: `holiday_companies`** (Junction Table)
- Links holidays to companies via a many-to-many relationship
- Allows each holiday to be assigned to multiple companies
- Location: See `sql/add_holiday_companies.sql`

```sql
CREATE TABLE IF NOT EXISTS `holiday_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `holiday_id` int(11) NOT NULL COMMENT 'Reference to emp_holidays',
  `company_id` int(11) NOT NULL COMMENT 'Reference to companies table (id field)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_holiday_company` (`holiday_id`, `company_id`),
  CONSTRAINT `fk_holiday_companies_holiday` FOREIGN KEY (`holiday_id`) REFERENCES `emp_holidays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holiday_companies_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

### 2. File Updates

#### `manage_holidays.php`
**New Features:**
- Select2 multi-select dropdown for company assignment in Add/Edit modals
- Company badges displayed in holiday table
- Company assignment validation (at least one company must be selected)
- Backend support for saving/updating company assignments

**New API Endpoints:**
- `GET manage_holidays.php?action=get_companies` - Returns all available companies
- Updated `POST manage_holidays.php?action=add` - Now accepts `company_ids[]` parameter
- Updated `POST manage_holidays.php?action=edit` - Now accepts `company_ids[]` parameter
- Updated `GET manage_holidays.php?action=get_single&id=X` - Returns assigned companies

**JavaScript Functions:**
- `loadCompaniesForSelect(selectElement, selectedIds)` - Loads companies via AJAX and initializes Select2

#### `includes/vacation_calculator.php`
**Enhanced Holiday Filtering:**
- Modified `getUsedVacationDays()` method to:
  - Get employee's company ID from `employees.comp_no`
  - Filter holidays using LEFT JOIN with `holiday_companies` table
  - Only count holidays assigned to the employee's company
  - Maintain backward compatibility with null checks

**Holiday Query Update:**
```php
// OLD (filtered all holidays):
SELECT total_days FROM emp_holidays 
WHERE is_active = 1 
AND start_date <= ? AND end_date >= ?

// NEW (filters by company):
SELECT h.total_days FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
WHERE h.is_active = 1 
AND h.start_date <= ? 
AND h.end_date >= ?
AND (hc.company_id = ? OR hc.holiday_id IS NULL)
```

## Installation Steps

### Step 1: Backup Your Database
```bash
# Always backup before making schema changes!
mysqldump -u your_user -p your_database > backup_$(date +%Y%m%d).sql
```

### Step 2: Run the Database Migration

Execute the SQL migration file: `sql/add_holiday_companies.sql`

**Option A: PHPMyAdmin**
1. Open PHPMyAdmin
2. Navigate to your database
3. Click "SQL" tab
4. Paste the SQL from `add_holiday_companies.sql`
5. Click "Go"

**Option B: MySQL CLI**
```bash
mysql -u your_user -p your_database < sql/add_holiday_companies.sql
```

### Step 3: Upload Updated Files

Replace these files on your server:
1. `manage_holidays.php`
2. `includes/vacation_calculator.php`

### Step 4: Verify Installation

1. **Check Database**
   ```sql
   SHOW TABLES LIKE 'holiday_companies';
   SELECT * FROM holiday_companies;
   ```

2. **Test the UI**
   - Go to manage_holidays.php
   - Try creating a new holiday - you should see the company selector
   - Add a holiday and select multiple companies
   - Verify companies appear in the table

3. **Test Vacation Deduction**
   - Create test holidays for Company A and Company B
   - Create employees in each company
   - Submit vacation requests for each employee
   - Verify that vacation deductions use the correct company's holidays

## Usage Guide

### Creating a Holiday with Company Assignment

1. Click "Add Holiday" button
2. Fill in holiday details:
   - Holiday Name (required)
   - Date Range (required)
   - Assign to Companies (required) - Select one or more companies
   - Holiday Type (optional)
   - Remarks (optional)
3. Click "Save Holiday"

### Editing Holiday Company Assignment

1. Click the edit button on a holiday row
2. Modify company assignment in the "Assign to Companies" dropdown
3. Click "Update Holiday"
4. Changes take effect immediately for vacation calculations

### Vacation Deduction Logic

When an employee submits a vacation request:
1. System gets employee's company ID from `employees.comp_no`
2. During vacation calculation, only holidays assigned to that company are considered
3. Vacation days that fall on company-specific holidays are not deducted

**Example:**
- Company A has Eid holiday (3 days)
- Company B doesn't have Eid assigned to them
- Employee from Company A taking 5 days vacation during Eid: 5 - 3 = 2 days deducted
- Employee from Company B taking 5 days vacation during Eid: 5 - 0 = 5 days deducted

## Backward Compatibility

The system maintains backward compatibility:

- **Existing holidays without company assignment** - Still work as before
- **Company field is optional in queries** - If company_id is NULL, no company filtering occurs
- **NULL checks** - Prevents errors if data is incomplete

## Optional: Backfill Existing Holidays

If you have existing holidays and want to assign them to all companies, uncomment this SQL:

```sql
INSERT INTO `holiday_companies` (`holiday_id`, `company_id`) 
SELECT h.`id`, c.`id` 
FROM `emp_holidays` h 
CROSS JOIN `companies` c 
WHERE NOT EXISTS (
  SELECT 1 FROM `holiday_companies` hc 
  WHERE hc.`holiday_id` = h.`id`
);
```

This will create an assignment for each holiday to every company (useful for global holidays).

## Troubleshooting

### Issue: Foreign Key Constraint Error

**Solution:** Ensure that:
- `emp_holidays` table exists
- `companies` table exists
- Both tables use InnoDB engine

### Issue: Select2 Not Working in Modal

**Solution:**
- Verify that `plugins/select2/js/select2.min.js` is loaded
- Check browser console for JavaScript errors
- Ensure jQuery is loaded before Select2

### Issue: Holidays Not Being Deducted

**Check:**
1. Verify holiday is marked as `is_active = 1`
2. Verify employee has `comp_no` field populated in database
3. Verify company_id is correctly linked in `holiday_companies` table
4. Check error logs for SQL errors

## Database Queries for Verification

```sql
-- See all holidays with their assigned companies
SELECT 
    h.id,
    h.holiday_name,
    h.start_date,
    h.end_date,
    GROUP_CONCAT(c.comp_name SEPARATOR ', ') as companies
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
LEFT JOIN companies c ON hc.company_id = c.id
WHERE h.is_active = 1
GROUP BY h.id
ORDER BY h.start_date DESC;

-- See which holidays are assigned to a specific company
SELECT 
    h.id,
    h.holiday_name,
    h.start_date,
    h.end_date
FROM emp_holidays h
JOIN holiday_companies hc ON h.id = hc.holiday_id
WHERE hc.company_id = 1
AND h.is_active = 1
ORDER BY h.start_date;

-- See holidays without any company assignment
SELECT 
    h.id,
    h.holiday_name,
    h.start_date,
    h.end_date
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
WHERE hc.holiday_id IS NULL
AND h.is_active = 1;
```

## Performance Considerations

- **Indexes created** on `holiday_companies` table for efficient lookups
- **LEFT JOIN used** to prevent excluding unassigned holidays
- **company_id filtering** is done at database level (efficient)

## Support & Maintenance

- Regular backups recommended before making changes
- Test in staging environment before production
- Monitor vacation deduction calculations after deployment
- Keep database backups for at least 30 days

## Technical Documentation

### Database Schema
- **Table:** holiday_companies
- **Primary Keys:** id (auto-increment)
- **Unique Constraints:** (holiday_id, company_id)
- **Foreign Keys:** References emp_holidays and companies
- **Indexes:** idx_holiday_company_lookup, idx_company_holiday_lookup

### API Endpoints
- **manage_holidays.php?action=get_companies** - GET - Returns company list
- **manage_holidays.php?action=add** - POST - Creates holiday with company assignment
- **manage_holidays.php?action=edit** - POST - Updates holiday and company assignment
- **manage_holidays.php?action=get_single&id=X** - GET - Returns holiday with assigned companies

### Data Flow
1. User creates/edits holiday with company selection
2. Holiday saved to emp_holidays table
3. Company assignments saved to holiday_companies table
4. When vacation is calculated, VacationCalculator queries holidays filtered by employee's company
5. Only applicable holidays are deducted from vacation days
