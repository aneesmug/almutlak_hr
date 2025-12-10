# Employee Rejoin Approval System - Implementation Checklist

## Pre-Implementation

- [ ] Backup current database
- [ ] Backup current files (view_employee.php, ajaxVacation.php, emp_top_info.php)
- [ ] Review system architecture (SYSTEM_DIAGRAMS.md)
- [ ] Notify supervisors about new approval workflow

## Database Setup

- [ ] Run migration: `includes/migrations/add_rejoin_approval_system.php`
- [ ] Verify `rejoin_requests` table created
- [ ] Verify `rejoin_notifications` table created
- [ ] Verify `emp_vacation` table has rejoin columns:
  - [ ] `rejoin_request_status`
  - [ ] `rejoin_requested_date`
  - [ ] `rejoin_requested_at`
  - [ ] `rejoin_approved_date`
  - [ ] `rejoin_approved_by`
  - [ ] `rejoin_approved_at`
  - [ ] `rejoin_adjustment_allowed`
  - [ ] `rejoin_adjustment_from_date`
  - [ ] `rejoin_adjustment_to_date`
  - [ ] `rejoin_adjustment_reason`
  - [ ] `rejoin_final_date`
  - [ ] `rejoin_final_confirmed_at`

## File Updates

### view_employee.php
- [ ] New functions added:
  - [ ] `submitRejoinRequest()`
  - [ ] `submitRejoinAjax()`
  - [ ] `approveRejoinRequest()`
  - [ ] `processRejoinApproval()`
- [ ] `returnVacationRequest()` updated to use new system
- [ ] All functions properly indented
- [ ] No syntax errors

### includes/emp_top_info.php
- [ ] Rejoin button call updated with emp_id and emp_name
- [ ] Old function call replaced correctly

### includes/ajaxFile/ajaxVacation.php
- [ ] New AJAX handlers added:
  - [ ] `submitRejoinRequest`
  - [ ] `processRejoinApproval`
  - [ ] `submitAdjustedRejoinDate`
- [ ] Proper error handling
- [ ] Database transactions implemented
- [ ] No syntax errors

## New Files Created

- [ ] `rejoin_approvals.php` - Supervisor dashboard
- [ ] `includes/api/get_rejoin_requests.php` - API endpoint
- [ ] `includes/migrations/add_rejoin_approval_system.php` - Migration script
- [ ] `REJOIN_SETUP_GUIDE.php` - Setup instructions
- [ ] `REJOIN_SYSTEM_DOCUMENTATION.md` - Full documentation
- [ ] `IMPLEMENTATION_SUMMARY.md` - Summary of changes
- [ ] `SYSTEM_DIAGRAMS.md` - Visual diagrams
- [ ] This checklist

## Configuration

### Employee Records
- [ ] Verify all employees have `reports_to` field populated
- [ ] `reports_to` must point to direct supervisor's emp_id
- [ ] Check at least 5 employee records for accuracy

### Permissions
- [ ] Ensure HR users have access to rejoin approvals
- [ ] Ensure supervisors can see their team's requests
- [ ] Ensure admins can override any decision

## Testing Phase 1: Employee Submission

### Setup Test Data
- [ ] Create test employee account (test_emp_001)
- [ ] Create supervisor account (test_super_001)
- [ ] Set supervisor as reports_to for test employee
- [ ] Create approved vacation record with fly=1

### Test Employee Workflow
- [ ] Login as test employee
- [ ] Navigate to view_employee.php
- [ ] Click "Rejoin" button
- [ ] Verify modal appears with:
  - [ ] Date picker
  - [ ] Planned return date shown
  - [ ] Reason field
  - [ ] Submit button
- [ ] Select rejoin date (planned + 2 days)
- [ ] Add optional reason
- [ ] Click Submit
- [ ] Verify success message
- [ ] Check database:
  - [ ] Record created in `rejoin_requests`
  - [ ] Status = 'pending'
  - [ ] `emp_vacation.rejoin_request_status` = 'pending'

### Test Validation
- [ ] Try selecting date > 3 days after planned
- [ ] Verify error message shows
- [ ] Try submitting without date
- [ ] Verify validation message shows
- [ ] Try selecting date before planned
- [ ] Verify cannot select before planned date

## Testing Phase 2: Supervisor Approval

### Dashboard Access
- [ ] Login as supervisor
- [ ] Navigate to `rejoin_approvals.php`
- [ ] Verify page loads without errors
- [ ] Check pending requests tab shows test request

### Approval Interface
- [ ] Verify request displays correctly
- [ ] Click "Review" button
- [ ] Modal opens with:
  - [ ] Employee name shown
  - [ ] Rejoin date shown
  - [ ] Three action options (Approve/Adjust/Reject)
  - [ ] Approval note field

### Test Approval
- [ ] Select "Approve"
- [ ] Add optional note
- [ ] Click Submit
- [ ] Verify success message
- [ ] Check database:
  - [ ] `rejoin_requests.status` = 'approved'
  - [ ] `rejoin_requests.approved_by_emp_id` set
  - [ ] `rejoin_requests.final_approved_date` set
  - [ ] `emp_vacation.rejoin_final_date` set

### Test Adjustment
- [ ] Create another test request
- [ ] In supervisor approval, select "Adjust"
- [ ] Verify adjustment window shown (±3 days)
- [ ] Add reason
- [ ] Submit
- [ ] Verify adjustment window set correctly

### Test Employee Adjustment Selection
- [ ] Login as employee
- [ ] Check for adjustment notification (future: email)
- [ ] Access adjustment interface
- [ ] Select date within window
- [ ] Verify date accepted and status changes to approved

### Test Rejection
- [ ] Create another test request
- [ ] In supervisor approval, select "Reject"
- [ ] Verify rejection reason field appears
- [ ] Enter reason
- [ ] Submit
- [ ] Check database:
  - [ ] `rejoin_requests.status` = 'rejected'
  - [ ] Rejection reason saved

## Testing Phase 3: Data Integrity

### Database Consistency
- [ ] Verify all timestamps are accurate
- [ ] Verify user IDs recorded correctly
- [ ] Check no orphaned records in rejoin_notifications
- [ ] Verify foreign keys working (try delete employee)

### Multi-User Scenarios
- [ ] Multiple employees submit requests
- [ ] Multiple supervisors approve different employees
- [ ] Verify no cross-contamination of data
- [ ] Verify each supervisor only sees their reports

### Edge Cases
- [ ] Employee tries to submit twice for same vacation
- [ ] Supervisor approval on already-approved request
- [ ] Try accessing another supervisor's requests
- [ ] Test with special characters in notes
- [ ] Test with very long notes

## Testing Phase 4: Integration

### With Payroll System
- [ ] Verify rejoin_final_date doesn't interfere with payroll
- [ ] Confirm employee can't submit while payroll processing
- [ ] Check salary calculations use correct dates

### With Vacation System
- [ ] Verify rejoin request doesn't affect vacation days calculation
- [ ] Confirm vacation balance remains accurate
- [ ] Test emergency vacation with rejoin request

### With Reporting
- [ ] Verify rejoin data appears in employee reports
- [ ] Check approval history can be exported
- [ ] Test filtering by rejoin status

## Performance Testing

- [ ] Load test with 100+ rejoin requests
- [ ] Dashboard refresh performance (30-second interval)
- [ ] Query performance on rejoin_requests table
- [ ] Check database indexes are being used

## Security Testing

### Permission Validation
- [ ] Non-supervisor can't approve requests
- [ ] Employee can't access other employee's requests
- [ ] Admin can override all decisions
- [ ] Regular user can't access admin functions

### Input Validation
- [ ] SQL injection attempts fail safely
- [ ] XSS attempts in notes are escaped
- [ ] Date format validation works
- [ ] File upload validation (if added)

### Data Access
- [ ] Sensitive data not exposed in API responses
- [ ] Audit trail cannot be modified
- [ ] Deletion is properly cascaded

## Documentation

- [ ] Update employee handbook with process
- [ ] Create quick reference guide for supervisors
- [ ] Add to HR training materials
- [ ] Notify all users of new system
- [ ] Document any customizations made

## Deployment

### Pre-Deployment
- [ ] All tests passing
- [ ] All documentation complete
- [ ] Backups verified
- [ ] Rollback plan documented

### Deployment Steps
- [ ] Run database migration in production
- [ ] Deploy updated PHP files
- [ ] Deploy new PHP files
- [ ] Clear any caches
- [ ] Verify URLs accessible

### Post-Deployment
- [ ] Monitor error logs
- [ ] Check database for issues
- [ ] Verify functionality in production
- [ ] Notify users system is live
- [ ] Monitor for first week

## User Training

### For Employees
- [ ] How to submit rejoin request
- [ ] How to respond to adjustment
- [ ] How to view approval status
- [ ] What happens if rejected

### For Supervisors
- [ ] How to access dashboard
- [ ] How to review requests
- [ ] How to approve/adjust/reject
- [ ] How to use adjustment notes
- [ ] Deadline expectations

### For HR/Admin
- [ ] How to override decisions
- [ ] How to view full audit trail
- [ ] How to handle disputes
- [ ] How to generate reports

## Monitoring

### Daily
- [ ] Check for any error logs
- [ ] Review pending request count
- [ ] Monitor approval response time

### Weekly
- [ ] Review approval statistics
- [ ] Check for any system issues
- [ ] Verify database performance

### Monthly
- [ ] Generate approval report
- [ ] Analyze rejection reasons
- [ ] Review system efficiency
- [ ] Plan improvements

## Post-Implementation Issues

### Common Issues Log
- [ ] Issue: __________  → Solution: __________
- [ ] Issue: __________  → Solution: __________
- [ ] Issue: __________  → Solution: __________

### Feedback Collection
- [ ] Employee feedback
- [ ] Supervisor feedback
- [ ] HR feedback
- [ ] System performance feedback

## Go-Live Readiness

### Final Checks (Do before go-live)
- [ ] All tests passing: ☐ YES
- [ ] Database backup verified: ☐ YES
- [ ] Rollback plan ready: ☐ YES
- [ ] User training complete: ☐ YES
- [ ] Documentation finalized: ☐ YES
- [ ] Support team briefed: ☐ YES
- [ ] Monitoring setup complete: ☐ YES

### Sign-Off
- [ ] Project Manager: _________________ Date: _______
- [ ] Database Admin: __________________ Date: _______
- [ ] System Administrator: ____________ Date: _______
- [ ] HR Manager: ____________________ Date: _______

## Version Control

- **System Version**: 1.0
- **Release Date**: December 2025
- **Database Version**: 1.0
- **API Version**: 1.0
- **Last Updated**: December 2025

## Support Contacts

- **Technical Support**: ___________________
- **HR Contact**: ___________________
- **System Administrator**: ___________________
- **Backup Contact**: ___________________

---

## Implementation Notes

Use this section to document any custom configurations or modifications made:

```
Notes:
______________________________________________________________________

______________________________________________________________________

______________________________________________________________________

______________________________________________________________________

______________________________________________________________________
```

## Approval Sign-Off

**Project Approved By:**

Name: _____________________ Title: _____________ Date: _______

Signature: _____________________________

**Technical Review:**

Name: _____________________ Title: _____________ Date: _______

Signature: _____________________________

**Go-Live Approval:**

Name: _____________________ Title: _____________ Date: _______

Signature: _____________________________

---

**Checklist Version**: 1.0
**Last Updated**: December 2025
**Status**: READY FOR IMPLEMENTATION
