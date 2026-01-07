# 🚀 Quick Reference: Loan Payroll Deduction System

## One-Sentence Summary
**When a GM approves a loan, monthly payroll deduction entries are automatically created and deducted from salary during payroll generation.**

---

## The 4-Step Process

### 1️⃣ Loan Approval (GM gives final approval)
```
ajaxLoan.php - approve_loan() [LINE 775]
UPDATE emp_loan SET status = 'approved'
```

### 2️⃣ Automatic Deduction Creation (Triggered immediately)
```
ajaxLoan.php - integrate_loan_to_payroll() [LINE 841-851]
Calls: add_monthly_installment_deduction()
Creates entries in payroll_deductions table for each installment month
```

### 3️⃣ Payroll Generation (Monthly)
```
process_payroll.php - process_payroll()
Reads payroll_deductions entries for the month
Calculates total_deductions
Updates payrolls table with net_salary = gross - deductions
```

### 4️⃣ Result
```
Employee net salary automatically reduced by loan amount ✅
```

---

## Critical Files

| File | Purpose | Key Function |
|------|---------|--------------|
| `includes/ajaxFile/ajaxLoan.php` | Loan approval & deduction setup | `approve_loan()`, `integrate_loan_to_payroll()` |
| `includes/api/process_payroll.php` | Monthly payroll generation | Reads `payroll_deductions`, calculates net |
| `testing/verify_loan_deductions.php` | Check system status | Dashboard view |
| `testing/fix_loan_deductions.php` | Fix missing deductions | Auto-recreate entries |

---

## Database Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `emp_loan` | Loan details | `status`, `monthly_deduction`, `start_date`, `deduction_mode` |
| `payroll_deductions` | Monthly deductions | `emp_id`, `month`, `deduction`, `note` (amount) |
| `payrolls` | Final payroll | `total_deductions`, `net_salary` |

---

## How to Use

### Check System Status
```
1. Open: testing/verify_loan_deductions.php
2. See all approved loans and their deduction status
3. Preview next month's deductions
```

### Fix Missing Deductions
```
1. Open: testing/fix_loan_deductions.php
2. System auto-scans for missing entries
3. Recreates them automatically
```

### Troubleshoot Issue
```
1. Go to verify_loan_deductions.php
2. Check "Data Consistency Checks" section
3. Follow "Action Required" recommendations
```

---

## Common Questions

**Q: When are deductions created?**
A: Automatically when GM gives final approval (status = 'approved')

**Q: How many months of deductions?**
A: Equal to loan's `installments` value (e.g., 12 months = 12 deduction entries)

**Q: Can I skip a month?**
A: Yes, delete the payroll_deductions entry for that month, then payroll won't deduct it

**Q: What if loan was approved manually?**
A: Run fix_loan_deductions.php to create missing entries

**Q: Can I change the monthly amount?**
A: Yes, update payroll_deductions.note for that month before payroll generation

**Q: What if loan has deduction_mode='manual'?**
A: No auto-deductions created. Add manually per month as needed.

---

## Key SQL Queries

### View All Active Loan Deductions for a Month
```sql
SELECT emp_id, deduction, note as amount, month
FROM payroll_deductions
WHERE month = '2025-01' AND deduction LIKE '%Loan%'
ORDER BY emp_id;
```

### Check Deductions for Specific Employee
```sql
SELECT * FROM payroll_deductions
WHERE emp_id = '1574' AND deduction LIKE '%LN-%'
ORDER BY month;
```

### Find Loans Missing Deductions
```sql
SELECT el.inv_no, el.emp_id, el.installments, COUNT(pd.id) as deduction_count
FROM emp_loan el
LEFT JOIN payroll_deductions pd ON el.emp_id = pd.emp_id 
    AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
WHERE el.status = 'approved' AND el.deduction_mode = 'automatic'
GROUP BY el.id
HAVING deduction_count < el.installments;
```

### View Next Month's Total Loan Deductions (by employee)
```sql
SELECT emp_id, COUNT(*) as loans, SUM(CAST(note AS DECIMAL(10,2))) as total
FROM payroll_deductions
WHERE month = '2025-02' AND deduction LIKE '%Loan%'
GROUP BY emp_id;
```

---

## Testing Checklist

- [ ] Create a loan for test employee
- [ ] Approvers approve through all levels
- [ ] GM gives final approval
- [ ] Check payroll_deductions has entries
- [ ] Generate payroll for that month
- [ ] Verify deduction in net salary
- [ ] Check salary slip shows deduction
- [ ] Test with multiple loans in same month
- [ ] Test manual mode (no auto-deductions)

---

## Support

**Check System**: `testing/verify_loan_deductions.php`
**Fix Issues**: `testing/fix_loan_deductions.php`
**Read Full Doc**: `testing/LOAN_PAYROLL_DEDUCTION_FLOW.md`
**Endpoint Details**: `testing/ENDPOINT_MAPPING.md`

---

**Status**: ✅ Fully Operational
**Last Check**: 2025-01-06
