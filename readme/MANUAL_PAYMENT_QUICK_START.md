# Manual Loan Payment Feature - Quick Start Guide

## 🎯 Feature Overview
The manual loan payment feature allows HR staff and employees to record loan payments made outside the automated payroll system, with proof of payment documentation.

---

## 📋 Prerequisites

### System Requirements
- ✅ Active loan with status = 'approved'
- ✅ Remaining balance > 0
- ✅ Payment proof document (PDF, JPG, PNG, DOC, DOCX)
- ✅ Maximum file size: 10MB

### User Access
- HR Staff (full access)
- Employees (can view their own loans)

---

## 🚀 Quick Start: Recording a Manual Payment

### Step 1: Access Employee Profile
1. Navigate to: **Employees → View Employee**
2. Select the employee with an active loan
3. Click on the **"Loan Details"** tab

### Step 2: View Loan Summary
You'll see a comprehensive summary with:
- **Left Column:** Loan details (invoice, type, amount, installments, dates)
- **Right Column:** Payment summary (total, paid, remaining)

### Step 3: Click "Add Manual Payment"
The green button appears below the remaining balance (only if balance > 0)

### Step 4: Fill the Payment Form
**Required Fields:**
- **Payment Date:** Default is today (can change)
- **Payment Amount:** Enter amount in SAR (must be ≤ remaining balance)
- **Payment Proof:** Upload document (PDF/Image/DOC)

**Optional Fields:**
- **Receipt Number:** Optional reference number
- **Note:** Any additional information

### Step 5: Submit
- Click **"Submit Payment"** button
- Wait for validation and upload
- Success message will show amount and updated balance
- Page automatically reloads

### Step 6: Verify
Check the **"Repayment History"** table to see:
- ✅ New payment record
- ✅ Green "Manual" badge
- ✅ Payment proof link
- ✅ Updated remaining balance

---

## 📊 Understanding the Loan Summary

### Loan Details Section (Left)
```
┌─────────────────────────────────┐
│ 📄 LOAN DETAILS                 │
├─────────────────────────────────┤
│ Invoice Number: LN-20251211-... │
│ Loan Type: [End of Service]    │
│ Approved Amount: 50,000.00 SAR │
│ Installments: 24 months         │
│ Monthly Deduction: 2,083.33 SAR│
│ Start Date: 01 Jan, 2025        │
│ End Date: 31 Dec, 2026          │
└─────────────────────────────────┘
```

### Payment Summary Section (Right)
```
┌─────────────────────────────────┐
│ 💰 PAYMENT SUMMARY              │
├─────────────────────────────────┤
│ Total Loan Amount               │
│   50,000.00 SAR                 │
├─────────────────────────────────┤
│ Total Paid                      │
│   15,000.00 SAR                 │
├─────────────────────────────────┤
│ Remaining Balance               │
│   35,000.00 SAR                 │
├─────────────────────────────────┤
│ [Add Manual Payment] (Button)   │
└─────────────────────────────────┘
```

---

## 🎨 Payment Method Badges

In the payment history table, you'll see color-coded badges:

| Badge | Color | Icon | Meaning |
|-------|-------|------|---------|
| **Manual** | 🟢 Green | ✋ Hand | Manually recorded payment |
| **Payroll** | 🔵 Blue | 📅 Calendar | Automated payroll deduction |
| **Auto** | 🔷 Light Blue | ⚙️ Gear | System-generated payment |

---

## ⚠️ Important Rules & Validations

### Payment Amount
- ✅ Must be **greater than 0**
- ✅ Must be **less than or equal to** remaining balance
- ❌ Cannot overpay (will reject with error message)

### File Upload
- ✅ **Allowed formats:** PDF, JPG, JPEG, PNG, DOC, DOCX
- ✅ **Maximum size:** 10MB
- ❌ Other formats will be rejected
- ❌ Files over 10MB will be rejected

### Payment Date
- ✅ Any valid date in `YYYY-MM-DD` format
- ✅ Can be past, present, or future dates
- ⚠️ Default is today's date

### Automatic Status Updates
- When **remaining balance reaches 0**, loan status automatically changes to **'paid'**
- Manual payment button will **hide** once loan is fully paid

---

## 📁 Where Files Are Stored

### Directory Structure
```
assets/
├── loan_manual_payments/          ← Manual payment proofs
│   ├── manual_payment_11_1733941234.pdf
│   └── manual_payment_12_1733941567.jpg
├── loan_receipts/                 ← Disbursement proofs
└── loan_payment_proofs/           ← Approval proofs (Level 7)
```

### File Naming Convention
```
manual_payment_{loan_id}_{timestamp}.{extension}
```

Example:
- `manual_payment_11_1733941234.pdf` → Loan ID 11, uploaded at timestamp 1733941234

---

## 🔍 Troubleshooting

### Problem: Button not showing
**Possible Causes:**
- Loan is fully paid (remaining balance = 0)
- Loan status is not 'approved'
- You're viewing wrong employee

**Solution:**
1. Check loan status in summary
2. Verify remaining balance > 0
3. Refresh the page

---

### Problem: "File too large" error
**Cause:** File exceeds 10MB limit

**Solution:**
1. Compress the file (use online PDF compressor)
2. Convert to lower quality image
3. Split into multiple pages if possible

---

### Problem: "Invalid file type" error
**Cause:** Uploaded file format not allowed

**Solution:**
1. Convert to PDF (recommended)
2. Use JPG or PNG for images
3. Avoid formats like BMP, GIF, TIFF

---

### Problem: "Amount exceeds balance" error
**Possible Causes:**
- Another payment was made concurrently
- Entering more than remaining balance

**Solution:**
1. Refresh the page to see latest balance
2. Enter amount ≤ remaining balance shown
3. Contact admin if balance seems incorrect

---

### Problem: Payment uploaded but not visible
**Possible Causes:**
- Page didn't reload
- Browser cache

**Solution:**
1. Hard refresh: `Ctrl + F5` (Windows) or `Cmd + Shift + R` (Mac)
2. Clear browser cache
3. Check payment history table

---

## 🎓 Example Scenarios

### Scenario 1: Part Payment
**Situation:** Employee paid 5,000 SAR of their 50,000 SAR loan

**Steps:**
1. Open employee loan details
2. Click "Add Manual Payment"
3. Enter:
   - Amount: `5000`
   - Date: `2025-12-11`
   - Receipt: `RECEIPT-12345` (optional)
   - Upload: Bank transfer proof (PDF)
   - Note: `Bank transfer - partial payment`
4. Submit
5. Result: Remaining balance updated to 45,000 SAR

---

### Scenario 2: Final Payment
**Situation:** Employee paying off remaining 10,000 SAR

**Steps:**
1. Open employee loan details
2. Click "Add Manual Payment"
3. Enter:
   - Amount: `10000` (exact remaining balance)
   - Date: `2025-12-15`
   - Upload: Payment receipt
4. Submit
5. Result:
   - Remaining balance: 0 SAR
   - Loan status: **'paid'**
   - Manual payment button: **Hidden**

---

### Scenario 3: Multiple Payments Same Day
**Situation:** Employee made 2 separate payments today

**Steps:**
1. Record first payment (e.g., 3,000 SAR)
2. Wait for page reload
3. Click "Add Manual Payment" again
4. Record second payment (e.g., 2,000 SAR)
5. Result: Both payments appear in history table

---

## 📞 Support & Documentation

### Files to Check
- **Feature Documentation:** `MANUAL_LOAN_PAYMENT_FEATURE.md`
- **Test Script:** `test_manual_payment_feature.php`
- **Backend Code:** `includes/ajaxFile/ajaxLoan.php` (line 846)
- **Frontend Code:** `view_employee.php` (lines 785-862, 1830-1941)

### Running Tests
```
http://your-domain/test_manual_payment_feature.php
```

This will show:
- ✅ Directory structure status
- ✅ Database schema validation
- ✅ Code implementation checks
- ✅ PHP configuration
- ✅ Sample active loans

---

## 🔐 Security Features

### Built-in Protections
- ✅ **SQL Injection Prevention:** All queries use prepared statements
- ✅ **File Type Validation:** Whitelist-based, rejects unsafe files
- ✅ **File Size Limit:** Maximum 10MB enforced
- ✅ **Ownership Verification:** Users can only pay their own loans (or HR staff)
- ✅ **Race Condition Prevention:** Database row locking prevents concurrent issues
- ✅ **Transaction Safety:** Rollback on errors ensures data integrity
- ✅ **File Cleanup:** Orphaned files deleted on transaction failure

### Best Practices
- Always upload **original payment proof** documents
- Use **clear receipt numbers** for tracking
- Add **descriptive notes** for future reference
- Verify **remaining balance** after each payment

---

## 📈 Reporting & Analytics

### Available Data
From the payment history table, you can track:
- Payment dates
- Payment amounts
- Payment methods (Manual/Payroll/Auto)
- Receipt numbers
- Proof documents
- Payment notes

### Export Options
- Use browser print: `Ctrl + P`
- DataTables built-in export (if configured)
- Direct database queries for reports

---

## ✅ Checklist: Before Recording Payment

- [ ] Loan is in 'approved' status
- [ ] Remaining balance is accurate
- [ ] Payment amount is correct
- [ ] Payment proof document is ready
- [ ] File is under 10MB
- [ ] File format is allowed (PDF/JPG/PNG/DOC)
- [ ] Receipt number (if available)
- [ ] Note describing payment (if needed)

---

## 📝 Best Practices

### For HR Staff
1. **Verify payment first** before recording
2. **Keep original receipts** in physical file
3. **Use consistent receipt numbering**
4. **Add notes** for audit trail
5. **Double-check amounts** before submitting

### For System Administrators
1. **Regular backups** of `assets/loan_manual_payments/`
2. **Monitor disk space** (files accumulate over time)
3. **Archive old payments** annually
4. **Review error logs** for upload issues
5. **Test file uploads** periodically

---

**Last Updated:** December 2025  
**Version:** 1.0  
**Status:** Production
