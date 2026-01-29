# Settlement System - Implementation Checklist

## Phase 1: Database Setup ✓
- [x] Settlement schema created (settlement_implementation.sql ready)
- [ ] SQL file executed in MySQL database
- [ ] Verify `settlement_records` table created
- [ ] Verify `settlement_chain` table created  
- [ ] Verify `settlement_approvals` table created
- [ ] Verify `emp_vacation` columns added (settlement_status, settlement_amount, settlement_date)
- [ ] Verify `emp_loan` columns added (settlement_status, settlement_amount, settlement_date)
- [ ] Verify default settlement chains configured
- [ ] Test queries work in phpMyAdmin

**Test Query:**
```sql
SHOW TABLES LIKE 'settlement%';
SELECT * FROM settlement_chain;
DESCRIBE emp_vacation;
```

---

## Phase 2: Backend Setup ✓
- [x] SettlementManager.php created in `includes/`
- [x] settlement_handler.php created in `includes/api/`
- [ ] Verify both files readable and syntax correct
- [ ] Test SettlementManager instantiation works
- [ ] Verify API endpoint responds to test requests

**Test:**
```bash
# Check file exists
ls -la includes/SettlementManager.php
ls -la includes/api/settlement_handler.php

# Test PHP syntax
php -l includes/SettlementManager.php
php -l includes/api/settlement_handler.php
```

---

## Phase 3: Frontend Setup ✓
- [x] settlement-manager.js created in `assets/js/`
- [ ] Verify JavaScript file loads without errors
- [ ] Test SweetAlert2 modals work
- [ ] Verify API calls from JS to handler work

**Test:**
```bash
# Check file exists
ls -la assets/js/settlement-manager.js
```

---

## Phase 4: Integration - Vacation Approval
- [ ] Locate your vacation approval handler file
  - Usually: `all_applied_vac.php` or similar
- [ ] Copy code from SETTLEMENT_INTEGRATION_EXAMPLES.php
- [ ] Add settlement creation after final approval
- [ ] Test that settlement is created when vacation approved
- [ ] Verify settlement appears in `settlement_records` table

**Expected Result:**
```
When vacation is approved:
1. Settlement record created in settlement_records table
2. Settlement approvals created for each level in chain
3. First approver notified (optional)
4. Status shown to user: "Settlement created"
```

---

## Phase 5: Integration - Loan Approval
- [ ] Locate your loan approval handler file
  - Usually: `all_applied_loan.php` or similar
- [ ] Copy code from SETTLEMENT_INTEGRATION_EXAMPLES.php
- [ ] Add settlement creation after final approval
- [ ] Test that settlement is created when loan approved
- [ ] Verify settlement appears in `settlement_records` table

**Expected Result:**
```
When loan is approved:
1. Settlement record created in settlement_records table
2. Settlement approvals created for each level in chain
3. Loan amount used as settlement amount
4. Status shown to user: "Loan settlement created"
```

---

## Phase 6: Create Management Pages
- [ ] Create `settlement_approvals.php`
  - Copy template from SETTLEMENT_INTEGRATION_EXAMPLES.php
  - Shows pending approvals for current user
  - Approve/Reject buttons
- [ ] Create `settlement_payment.php`
  - Copy template from SETTLEMENT_INTEGRATION_EXAMPLES.php
  - Shows approved settlements ready for payment
  - Process Payment button
- [ ] Create `settlement_history.php` (optional)
  - List all settlements for logged-in employee
  - Show history with approval dates

**Verify:**
- Pages load without errors
- Shows correct data
- Buttons trigger modals
- User can perform actions

---

## Phase 7: Menu Integration
- [ ] Add Settlement links to main navigation
  - For HR/Finance: "Settlement Approvals" → settlement_approvals.php
  - For Finance: "Process Payments" → settlement_payment.php
  - For all users: "My Settlements" → settlement_history.php

**Example:**
```html
<!-- In includes/main_menu.php or navigation menu -->
<li class="nav-item">
    <a href="settlement_approvals.php" class="nav-link">
        <span class="nav-link-icon d-md-none d-lg-inline-block">
            <i class="fas fa-check"></i>
        </span>
        <span class="nav-link-title">Settlement Approvals</span>
    </a>
</li>
```

---

## Phase 8: Configuration Setup
- [ ] Configure settlement approvers in `settlement_chain` table
  - Set specific approver IDs OR
  - Configure role-based approvers
- [ ] Enable settlement in `app_settings` table
  - `settlement_enable_vacation = 1`
  - `settlement_enable_loan = 1`
- [ ] Configure notification settings
  - `settlement_notification_email = 1`
- [ ] Set auto-create setting
  - `settlement_auto_create = 0` (manual) or `1` (automatic)

**Database Update:**
```sql
-- Set specific approver
UPDATE settlement_chain 
SET approver_id = '5430'
WHERE request_type = 'annual_vacation' AND approval_level = 2;

-- Or verify role-based configuration
SELECT * FROM settlement_chain;
```

---

## Phase 9: Testing

### Test 1: Create Settlement
- [ ] Approve a vacation request
- [ ] Verify settlement record created
- [ ] Check settlement_status = 'pending'
- [ ] Check approval chain created

**Query to Verify:**
```sql
SELECT * FROM settlement_records 
WHERE request_inv_no = 'VAC-2026-0001';

SELECT * FROM settlement_approvals 
WHERE settlement_id = 1 
ORDER BY approval_level;
```

### Test 2: Approve Settlement (Level 1)
- [ ] Login as first approver
- [ ] Go to Settlement Approvals page
- [ ] See pending settlement
- [ ] Click Approve
- [ ] Enter notes
- [ ] Submit

**Expected:**
- Settlement moves to Level 2
- Next approver notified
- Status updates in database

### Test 3: Approve Settlement (Level 2)
- [ ] Login as second approver
- [ ] See settlement from Level 1
- [ ] Approve
- [ ] Settlement marked "approved"

### Test 4: Process Payment
- [ ] Login as Finance user
- [ ] Go to Process Payments page
- [ ] See approved settlement
- [ ] Click Process Payment
- [ ] Select payment method
- [ ] Enter payment reference
- [ ] Submit

**Expected:**
- Settlement marked "processed"
- Payment date set
- emp_vacation/emp_loan updated with settlement status
- Original request marked as settled

### Test 5: Reject Settlement
- [ ] Create new settlement
- [ ] Login as approver
- [ ] Click Reject
- [ ] Enter rejection reason
- [ ] Submit

**Expected:**
- Settlement marked "rejected"
- Employee notified
- Can retry from beginning

---

## Phase 10: User Acceptance Testing

- [ ] Finance team tests settlement workflow
  - Create → Approve → Pay complete cycle
- [ ] HR tests with vacation requests
- [ ] Finance tests with loan requests
- [ ] Verify email notifications sent (if enabled)
- [ ] Check settlement appears in reports
- [ ] Verify amounts are accurate

---

## Phase 11: Documentation & Training

- [ ] Document settlement workflow
- [ ] Create user guide for approvers
- [ ] Create user guide for finance team
- [ ] Document how to configure new request types for settlement
- [ ] Train all users
- [ ] Document troubleshooting steps

---

## Phase 12: Monitoring & Support

- [ ] Monitor settlement processing
- [ ] Track approval times
- [ ] Monitor payment processing
- [ ] Check error logs regularly
- [ ] Create backup of settlement data

**Monitoring Queries:**
```sql
-- Settlements pending longer than 3 days
SELECT * FROM settlement_records 
WHERE settlement_status = 'pending' 
AND DATEDIFF(NOW(), created_at) > 3;

-- Payments not processed
SELECT * FROM settlement_records 
WHERE settlement_status = 'approved' 
AND settlement_date IS NULL;

-- Settlement statistics
SELECT 
    request_type,
    settlement_status,
    COUNT(*) as count,
    SUM(settlement_amount) as total
FROM settlement_records
GROUP BY request_type, settlement_status;
```

---

## Troubleshooting Quick Reference

### Settlement not creating
```
✓ Check settlement_chain has config for request_type
✓ Check employee ID exists
✓ Check settlement not already exists
✓ Check SettlementManager.php loaded correctly
```

### Approvers not showing
```
✓ Verify approver_id exists in employees table
✓ Check approver_role matches employee role
✓ Verify settlement_chain populated
✓ Check SQL SELECT in SettlementManager
```

### Settlement not showing in list
```
✓ Check settlement_status value
✓ Verify current user is approver (approver_id)
✓ Check settlement_approvals records exist
✓ Run: SELECT * FROM settlement_records
```

### Payment not processing
```
✓ Check all approval_status = 'approved'
✓ Verify payment_method is valid
✓ Check payment_reference provided
✓ Verify user is finance (check role)
```

### JavaScript errors
```
✓ Check settlement-manager.js loads
✓ Check SweetAlert2 library loaded
✓ Check jQuery loaded
✓ Open browser console (F12) for errors
```

---

## Rollback Plan (if needed)

If you need to rollback:

```sql
-- Disable settlement system
UPDATE app_settings SET setting_value = '0' 
WHERE setting_name IN ('settlement_enable_vacation', 'settlement_enable_loan');

-- Or drop tables (CAUTION - data loss)
DROP TABLE IF EXISTS settlement_approvals;
DROP TABLE IF EXISTS settlement_chain;
DROP TABLE IF EXISTS settlement_records;

-- Remove columns from vacation/loan tables (CAUTION)
-- ALTER TABLE emp_vacation DROP COLUMN settlement_status;
-- ALTER TABLE emp_vacation DROP COLUMN settlement_amount;
-- ALTER TABLE emp_vacation DROP COLUMN settlement_date;
```

---

## Success Checklist

When ALL items below are complete, Settlement System is LIVE:

- [x] Database schema created
- [x] SettlementManager class ready
- [x] API endpoint ready
- [x] JavaScript UI ready
- [ ] SQL executed in database
- [ ] Vacation approval handler updated
- [ ] Loan approval handler updated
- [ ] settlement_approvals.php created
- [ ] settlement_payment.php created
- [ ] Menu items added
- [ ] Settlement chains configured
- [ ] Test workflow completed (all 5 tests passed)
- [ ] User training completed
- [ ] Go-live approved

---

## Support & Questions

For assistance:
1. Check SETTLEMENT_IMPLEMENTATION.md for detailed docs
2. Check SETTLEMENT_INTEGRATION_EXAMPLES.php for code samples
3. Check SETTLEMENT_SETUP_GUIDE.md for step-by-step instructions
4. Review database tables and queries
5. Check error logs: logs/php_error.log

---

**Status:** ✅ READY FOR IMPLEMENTATION

All components created. Begin with Phase 1 (Database Setup).
