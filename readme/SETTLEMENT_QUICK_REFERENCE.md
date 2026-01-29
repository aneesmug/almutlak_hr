# Settlement System - Quick Reference Card

## 🚀 START HERE (2-3 minutes)

### What is Settlement System?
Payment processing workflow for completed Vacation & Loan requests with multi-level approvals.

### What's Included?
✅ Database schema (3 new tables)
✅ Backend class (SettlementManager.php)
✅ API endpoint (settlement_handler.php)
✅ Frontend UI (settlement-manager.js)
✅ Complete documentation
✅ Code examples & templates

---

## ⚡ 5-Minute Installation

```bash
# 1. Execute SQL
mysql -u root -p almutlak < sql/settlement_implementation.sql

# 2. Verify tables exist
# SELECT * FROM settlement_chain;

# 3. Files are already in place:
# - includes/SettlementManager.php
# - includes/api/settlement_handler.php  
# - assets/js/settlement-manager.js

# 4. You're done! Now integrate into your code...
```

---

## 🔧 Integration (15 minutes)

### In Vacation Approval Handler:
```php
require_once 'includes/SettlementManager.php';
$mgr = new SettlementManager($pdo, $conDB);
$mgr->createSettlement(
    $vacationInvoiceNo,      // VAC-2026-0001
    'annual_vacation',
    $empId,                   // 5160
    $amount,                  // 5000.00
    $_SESSION['emp_id']       // Created by
);
```

### In Loan Approval Handler:
```php
require_once 'includes/SettlementManager.php';
$mgr = new SettlementManager($pdo, $conDB);
$mgr->createSettlement(
    $loanInvoiceNo,          // LOAN-2026-0001
    'loan_request',
    $empId,                   // 5160
    $loanAmount,              // 5000.00
    $_SESSION['emp_id']       // Created by
);
```

---

## 📋 Essential Commands

### Database Verification
```sql
-- Check tables exist
SHOW TABLES LIKE 'settlement%';

-- Check configuration
SELECT * FROM settlement_chain;

-- Check pending settlements
SELECT * FROM settlement_records WHERE settlement_status = 'pending';
```

### Create Settlement
```php
$result = $settlementMgr->createSettlement(
    'VAC-2026-0001',
    'annual_vacation',
    '5160',
    5000.00,
    '5430'
);
```

### Approve Settlement
```php
$result = $settlementMgr->approveSettlement(
    $settlementId,     // 1
    $approverId,       // 5430
    'Approved'         // Notes
);
```

### Process Payment
```php
$result = $settlementMgr->processPayment(
    $settlementId,           // 1
    'bank_transfer',         // Payment method
    'TRF-REF-123456',       // Reference
    $userId                  // Processed by
);
```

---

## 🎯 Workflow Overview

```
Vacation/Loan Request Approved
         ↓
Settlement Created (PENDING)
         ↓
Level 1 Approver → APPROVE
         ↓
Level 2 Approver → APPROVE
         ↓
Settlement APPROVED
         ↓
Finance → Process Payment (PROCESSED)
         ↓
Complete ✓
```

---

## 📱 Frontend Usage

### Include JavaScript
```html
<script src="assets/js/settlement-manager.js"></script>
```

### Create Settlement
```javascript
settlementManager.createSettlement(
    'VAC-2026-0001',
    'annual_vacation',
    '5160',
    5000.00
);
```

### Show Approval Modal
```javascript
settlementManager.showApproveModal(settlementId);
```

### Show Payment Modal
```javascript
settlementManager.showPaymentModal(settlementId);
```

---

## 🔍 Quick Verification

### File Check
```bash
ls -la includes/SettlementManager.php
ls -la includes/api/settlement_handler.php
ls -la assets/js/settlement-manager.js
```

### PHP Syntax
```bash
php -l includes/SettlementManager.php
php -l includes/api/settlement_handler.php
```

### Database Check
```sql
SELECT COUNT(*) FROM settlement_records;
SELECT COUNT(*) FROM settlement_chain;
SELECT COUNT(*) FROM settlement_approvals;
```

---

## 📊 Status Codes

| Status | Meaning | Next Action |
|--------|---------|------------|
| `pending` | Awaiting approval | Approve/Reject |
| `approved` | All approvals done | Process Payment |
| `processed` | Payment complete | View/Archive |
| `rejected` | Rejected by approver | Can retry |
| `cancelled` | Manually cancelled | Delete |

---

## 🆘 Common Issues

### Settlement not creating
```
✓ Check: SettlementManager.php can be included
✓ Check: Database connected
✓ Check: request_type matches configured types
✓ Check: Employee exists in database
```

### Approver not seeing settlements
```
✓ Check: Approver ID matches current user
✓ Check: Settlement approvals records exist
✓ Check: Approval status = 'pending'
✓ Check: Query is correct
```

### Payment not processing
```
✓ Check: All approvals = 'approved'
✓ Check: settlement_status = 'approved'
✓ Check: Payment method provided
✓ Check: Payment reference provided
```

---

## 📁 File Locations

### Code Files (4)
- `sql/settlement_implementation.sql` - Schema
- `includes/SettlementManager.php` - Backend
- `includes/api/settlement_handler.php` - API
- `assets/js/settlement-manager.js` - Frontend

### Documentation (6)
- `SETTLEMENT_IMPLEMENTATION.md` - Full docs
- `SETTLEMENT_SETUP_GUIDE.md` - Setup steps
- `SETTLEMENT_INTEGRATION_EXAMPLES.php` - Examples
- `SETTLEMENT_CHECKLIST.md` - Checklist
- `SETTLEMENT_COMPLETE_SUMMARY.md` - Summary
- `SETTLEMENT_FILE_MANIFEST.md` - File list

---

## 🎓 Reading Order

1. **This file** (2 min) - Overview
2. `SETTLEMENT_COMPLETE_SUMMARY.md` (10 min) - Details
3. `SETTLEMENT_SETUP_GUIDE.md` (20 min) - Steps
4. `SETTLEMENT_INTEGRATION_EXAMPLES.php` (15 min) - Code
5. `SETTLEMENT_CHECKLIST.md` (ongoing) - Verification

---

## 🔑 Key Methods

### SettlementManager PHP Class
```php
createSettlement($invNo, $type, $empId, $amount, $userId)
getSettlementChain($type)
approveSettlement($id, $approverId, $notes)
rejectSettlement($id, $approverId, $reason)
processPayment($id, $method, $ref, $userId)
getSettlementDetails($id)
getEmployeeSettlements($empId, $status)
```

### settlement-manager.js Class
```javascript
createSettlement(invNo, type, empId, amount)
approveSettlement(id)
rejectSettlement(id)
processPayment(id)
getSettlementDetails(id)
getEmployeeSettlements(empId, status)
showSettlementModal(id)
showApproveModal(id)
showRejectModal(id)
showPaymentModal(id)
```

---

## 📡 API Endpoints

All POST to: `includes/api/settlement_handler.php`

```
action=create_settlement
  └─ request_inv_no, request_type, emp_id, settlement_amount

action=approve_settlement
  └─ settlement_id, approver_id, notes

action=reject_settlement
  └─ settlement_id, approver_id, reason

action=process_payment
  └─ settlement_id, payment_method, payment_reference

action=get_settlement_details
  └─ settlement_id

action=get_employee_settlements
  └─ emp_id, status
```

---

## 💾 Database Tables

### settlement_records
```
id, request_inv_no, request_type, emp_id,
settlement_amount, settlement_status, payment_date,
settlement_approver, payment_reference, notes
```

### settlement_chain
```
id, request_type, approval_level,
approver_role, approver_id, is_active
```

### settlement_approvals
```
id, settlement_id, approval_level, approver_id,
approval_status, approval_date, approval_notes
```

---

## ✅ Success Criteria

Settlement system is working when:
- [ ] SQL executed without errors
- [ ] Tables created in database
- [ ] Settlement created when request approved
- [ ] Approver sees pending settlement
- [ ] Can approve/reject
- [ ] Can process payment
- [ ] Payment date set
- [ ] Original request updated

---

## 🎯 Timeline

| Phase | Time | Activity |
|-------|------|----------|
| Setup | 5 min | Execute SQL |
| Verify | 5 min | Check tables |
| Integrate | 20 min | Add to handlers |
| Test | 30 min | Test workflow |
| Deploy | 10 min | Go live |
| **Total** | **70 min** | Ready! |

---

## 📞 Need Help?

1. **Setup questions** → `SETTLEMENT_SETUP_GUIDE.md`
2. **Code examples** → `SETTLEMENT_INTEGRATION_EXAMPLES.php`
3. **Technical details** → `SETTLEMENT_IMPLEMENTATION.md`
4. **Verification steps** → `SETTLEMENT_CHECKLIST.md`
5. **All details** → `SETTLEMENT_COMPLETE_SUMMARY.md`
6. **File overview** → `SETTLEMENT_FILE_MANIFEST.md`

---

**Status: ✅ READY TO DEPLOY**

Execute SQL → Integrate Code → Test → Go Live

Estimated time: 1-2 hours for complete setup
