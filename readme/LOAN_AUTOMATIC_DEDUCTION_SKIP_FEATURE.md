# Automatic Loan Deduction with Monthly Skip Feature

## Overview

This feature enables automatic monthly loan deductions with the ability to skip specific months and automatically carry forward the skipped amounts to future months.

## Features

### 1. Automatic Monthly Deductions
- Loans with `deduction_mode = 'automatic'` automatically create monthly deductions in the payroll
- Deductions are generated when running payroll for each month
- Payment tracking is maintained in `emp_loan_payments` table

### 2. Monthly Skip Capability
- Specific months can be marked with `status = 0` to skip deduction
- Common use cases:
  - Employee on unpaid leave
  - Financial hardship/emergency
  - Special arrangements with management
- Skip reasons can be documented for audit trail

### 3. Automatic Carry-Forward
- When a month is skipped, the system automatically calculates the missed amount
- The skipped amount is distributed evenly across remaining loan months
- Each payment includes notes showing carry-forward amounts
- Example:
  - Normal monthly deduction: 500 SAR
  - 2 months skipped: 2 × 500 = 1,000 SAR to carry forward
  - 10 months remaining
  - New monthly deduction: 500 + (1,000 / 10) = 600 SAR per month

## Database Schema

### New Table: `emp_loan_monthly_status`

```sql
CREATE TABLE `emp_loan_monthly_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Active, 0 = Skip',
  `skip_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_loan_month` (`loan_id`, `month_year`)
);
```

### Modified Table: `emp_loan_payments`

Added `notes` column to track carry-forward information:

```sql
ALTER TABLE `emp_loan_payments` 
ADD COLUMN `notes` TEXT NULL COMMENT 'Payment notes including carry-forward info';
```

## Installation

### Step 1: Run SQL Migration

Execute the SQL migration file:

```bash
mysql -u your_username -p your_database < sql/add_loan_monthly_status.sql
```

Or run manually in phpMyAdmin/MySQL client.

### Step 2: Verify Files

Ensure these files are in place:

- `includes/api/update_loan_monthly_status.php` - API to update monthly status
- `includes/api/get_loan_monthly_status.php` - API to retrieve loan status
- `includes/api/process_payroll.php` - Modified with skip/carry-forward logic
- `sql/add_loan_monthly_status.sql` - Database migration

## Usage

### For HR/Finance Staff

#### Marking a Month to Skip

**API Endpoint:** `POST includes/api/update_loan_monthly_status.php`

**Request Body:**
```json
{
  "loan_id": 123,
  "month_year": "2025-03",
  "status": 0,
  "skip_reason": "Employee on unpaid leave"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Monthly status created successfully.",
  "loan_id": 123,
  "month_year": "2025-03",
  "monthly_status": 0
}
```

#### Viewing Loan Monthly Status

**API Endpoint:** `GET includes/api/get_loan_monthly_status.php?loan_id=123`

**Response:**
```json
{
  "status": "success",
  "loan": {
    "id": 123,
    "emp_id": "5455",
    "inv_no": "LN-20250101-5455-abcd",
    "employee_name": "John Doe",
    "loan_amount": 10000.00,
    "monthly_deduction": 500.00,
    "start_date": "2025-01-01",
    "end_date": "2025-12-31",
    "deduction_mode": "automatic"
  },
  "months": [
    {
      "month_year": "2025-01",
      "month_label": "January 2025",
      "status": 1,
      "skip_reason": null,
      "payment_amount": 500.00,
      "payment_notes": null,
      "is_paid": true
    },
    {
      "month_year": "2025-02",
      "month_label": "February 2025",
      "status": 0,
      "skip_reason": "Employee on unpaid leave",
      "payment_amount": null,
      "payment_notes": null,
      "is_paid": false
    },
    {
      "month_year": "2025-03",
      "month_label": "March 2025",
      "status": 1,
      "skip_reason": null,
      "payment_amount": 550.00,
      "payment_notes": "Including 50.00 SAR carry-forward from 1 skipped month(s)",
      "is_paid": true
    }
  ],
  "total_paid": 1050.00,
  "remaining_balance": 8950.00
}
```

### For Developers

#### Payroll Processing Logic

The modified `addOrUpdateLoanDeduction()` function in `process_payroll.php` now:

1. **Checks monthly status:**
   ```php
   SELECT status FROM emp_loan_monthly_status 
   WHERE loan_id = ? AND month_year = ?
   ```

2. **Skips if status = 0:**
   ```php
   if ($monthStatus && $monthStatus['status'] == 0) {
       return; // Skip this month
   }
   ```

3. **Calculates carry-forward:**
   ```php
   // Count skipped months before current month
   $skippedMonthsCount = ...;
   
   // Calculate months remaining
   $remainingMonths = ...;
   
   // Distribute skipped amount
   $skippedAmount = $skippedMonthsCount * $baseMonthlyDeduction;
   $carryForwardPerMonth = $skippedAmount / $remainingMonths;
   $deductionAmount = $baseMonthlyDeduction + $carryForwardPerMonth;
   ```

4. **Creates deduction with notes:**
   ```php
   INSERT INTO payroll_deductions 
   (emp_id, deduction, note, month, status)
   VALUES (?, 'Loan Installment', '600.00', '2025-03', 1);
   
   INSERT INTO emp_loan_payments 
   (loan_id, payment_date, amount, payment_method, notes)
   VALUES (123, '2025-03-31', '600.00', 'payroll', 
           'Including 100.00 SAR carry-forward from 2 skipped month(s)');
   ```

## Example Scenarios

### Scenario 1: Employee on 2-Month Unpaid Leave

**Loan Details:**
- Total: 12,000 SAR
- Monthly: 1,000 SAR
- Duration: 12 months (Jan 2025 - Dec 2025)

**Skip Setup:**
- Skip March 2025 (unpaid leave)
- Skip April 2025 (unpaid leave)

**Result:**
- January: 1,000 SAR (normal)
- February: 1,000 SAR (normal)
- March: 0 SAR (SKIPPED)
- April: 0 SAR (SKIPPED)
- May-December (8 months): 1,250 SAR each
  - Calculation: 1,000 + (2,000 skipped / 8 remaining) = 1,250 SAR

### Scenario 2: Mid-Loan Skip

**Loan Details:**
- Total: 10,000 SAR
- Monthly: 500 SAR
- Duration: 20 months

**Skip Setup:**
- Already paid 5 months (2,500 SAR paid)
- Skip month 6
- Resume month 7 onwards

**Result:**
- Months 1-5: 500 SAR each (total 2,500 SAR paid)
- Month 6: 0 SAR (SKIPPED)
- Months 7-20 (14 months): ~535.71 SAR each
  - Remaining balance: 7,500 SAR
  - Carry-forward: 500 SAR from skipped month
  - Total to distribute: 7,500 + 500 = 8,000 SAR
  - Per month: 8,000 / 14 ≈ 571.43 SAR

## Important Notes

### Limitations
- Only works for loans with `deduction_mode = 'automatic'`
- Manual mode loans must be managed individually
- Cannot skip months that already have deductions (warning shown)

### Best Practices
1. **Document skip reasons** - Always provide clear skip_reason for audit trail
2. **Review carry-forward amounts** - Ensure employee can afford increased payments
3. **Communicate with employee** - Inform employee about changed payment schedule
4. **Check remaining months** - Ensure enough months remain to distribute skipped amounts

### Security Considerations
- Only authorized HR/Finance staff should access these APIs
- Add proper authentication/authorization checks to API endpoints
- Log all skip status changes for audit trail

## Troubleshooting

### Issue: Deduction still created despite skip status

**Cause:** Payroll was already generated before skip was set

**Solution:** Delete the deduction manually from payroll, or regenerate payroll for that month

### Issue: Carry-forward amount seems incorrect

**Cause:** Multiple skipped months or mid-loan changes

**Solution:** Verify using GET API to see all months and calculations. Check `emp_loan_payments.notes` for breakdown.

### Issue: Cannot skip a specific month

**Cause:** Month format incorrect or loan is in manual mode

**Solution:** 
- Ensure month_year format is exactly `YYYY-MM` (e.g., `2025-03`)
- Verify loan has `deduction_mode = 'automatic'`

## Future Enhancements

Potential improvements for future versions:

1. **UI Dashboard** - Visual interface to manage skip months
2. **Bulk Skip** - Skip multiple consecutive months in one action
3. **Approval Workflow** - Require manager approval for skip requests
4. **Maximum Skip Limit** - Prevent skipping too many months
5. **Automatic Skip on Vacation** - Auto-skip during long unpaid leaves
6. **Payment Plan Recalculation** - Option to extend loan term instead of increasing monthly amount

## Support

For issues or questions:
- Check application logs: `error_log`
- Review database records: `emp_loan_monthly_status` and `emp_loan_payments`
- Contact development team with loan ID and month details
