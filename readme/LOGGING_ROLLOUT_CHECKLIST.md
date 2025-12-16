# Activity Logging Rollout Checklist

This checklist guides systematic integration of activity logging across the Al-Mutlak WMS system.

---

## Phase 1: CLEANUP & SETUP ⚙️
**Remove legacy components and validate foundation**

- [ ] Remove deprecated `includes/activity_logger.php` (old schema with user_editor, pg_id columns)
- [ ] Search entire project for references to old ActivityLogger and verify all use init.php version
- [ ] Test `view_activity_logs.php` admin page to ensure it displays logs correctly
- [ ] Run `test_activity_logger.php` to verify logging system works end-to-end
- [ ] Read `ACTIVITY_LOGGING_GUIDE.md` to understand all available methods
- [ ] Review `ACTIVITY_LOGGING_TEMPLATES.php` for code patterns

---

## Phase 2: AUTHENTICATION LOGGING 🔐
**Log all login/logout activities for security audit trail**

### Files to Update:

#### `includes/login_src.php` (Login Handler)
- [ ] After successful login query, add `ActivityLogger::logLogin($empid, $fname);`
- [ ] Before existing redirect, call the logging function
- [ ] Verify log appears in `view_activity_logs.php` with action_type=LOGIN

#### `logout.php` (Logout Handler)
- [ ] At start of logout process, call `ActivityLogger::logLogout();`
- [ ] Should capture user_id and user_name from $_SESSION
- [ ] Verify log appears with action_type=LOGOUT

#### `login.php` (Optional - Failed Login Tracking)
- [ ] On failed login attempt, optionally log with severity=WARNING, status=FAILED
- [ ] Use `ActivityLogger::log()` with custom parameters for failed attempts
- [ ] Helps identify brute force attempts

**Acceptance Criteria:**
- Every successful login creates an activity log entry
- Every logout creates an activity log entry
- All logs show correct user_id and user_name
- Logs appear within seconds in admin viewer

---

## Phase 3: EMPLOYEE MANAGEMENT 👥
**Log all employee CRUD operations**

### Files to Update:

#### `add_new_employee.php` (CREATE Employee)
- [ ] After successful INSERT query (after `mysqli_insert_id`), add logging
- [ ] Use `ActivityLogger::logCreate()` with module='Employee'
- [ ] Capture all inserted fields in new_values array: [name, dept, salary, position, email, phone, etc.]
- [ ] Verify log shows CREATE action with complete employee data in new_values

#### `edit_employee.php` (UPDATE Employee)
- [ ] Before UPDATE query, fetch existing data with SELECT
- [ ] After successful UPDATE, add logging
- [ ] Use `ActivityLogger::logUpdate()` with module='Employee'
- [ ] Compare old_values vs new_values - should show only changed fields in view
- [ ] Verify log shows UPDATE action with old/new field values

#### Employee Delete Endpoint (AJAX or Form Handler)
- [ ] Identify where employee deletion happens (may be in AJAX file or form handler)
- [ ] Before DELETE query, fetch complete employee record
- [ ] After successful DELETE, add logging
- [ ] Use `ActivityLogger::logDelete()` with module='Employee'
- [ ] Verify log shows DELETE action with deleted employee's complete data in old_values

#### `all_employee_list.php` (Optional - View List)
- [ ] Can add generic view logging when page loads: `ActivityLogger::logView('Employee', 'all_employee_list.php', 0, 'Viewed employee list')`
- [ ] Helps track who accesses employee data

**Acceptance Criteria:**
- New employees create CREATE logs
- Edited employees create UPDATE logs with before/after values
- Deleted employees create DELETE logs with full record data
- All logs show module='Employee' and correct table_name='employees'
- User_id and user_name correctly populated from session

---

## Phase 4: CUSTOMER MANAGEMENT 🏢
**Log all customer CRUD operations**

### Files to Update:

#### `add_customer.php` (CREATE)
- [ ] After INSERT, add `ActivityLogger::logCreate('Customer', ...)`
- [ ] Capture: name, contact_person, phone, email, address, city, country, etc.

#### `edit_customer.php` (UPDATE)
- [ ] Before/after UPDATE, add `ActivityLogger::logUpdate('Customer', ...)`
- [ ] Compare old vs new customer details

#### Customer Delete Endpoint (DELETE)
- [ ] After DELETE, add `ActivityLogger::logDelete('Customer', ...)`

#### `all_customers.php` (Optional - View)
- [ ] Optional: Add view logging on page load

**Acceptance Criteria:**
- All CRUD operations logged for customers
- Logs show module='Customer' and table_name='customers'
- Same quality as Employee logging

---

## Phase 5: VEHICLE/CAR MANAGEMENT 🚗
**Log all vehicle CRUD operations**

### Files to Update:

#### `add_car.php` (CREATE)
- [ ] Add logging after INSERT with module='Vehicle'

#### `edit_car.php` (UPDATE)
- [ ] Add logging with old/new values

#### Car Delete Endpoint (DELETE)
- [ ] Add logging with complete vehicle data

#### Related Files:
- [ ] `add_car_doc.php` - Log vehicle document uploads
- [ ] `add_car_driv.php` - Log driver assignments

**Acceptance Criteria:**
- All vehicle operations logged
- Logs show module='Vehicle' and table_name='vehicles' (or similar)

---

## Phase 6: MACHINE/ASSET MANAGEMENT 🏭
**Log all machine/asset CRUD operations**

### Files to Update:

#### `add_machine.php` (CREATE)
- [ ] Add logging with module='Machine' or 'Asset'

#### `edit_machine.php` (UPDATE)
- [ ] Add logging with old/new values

#### Machine Delete (DELETE)
- [ ] Add logging with complete machine data

#### Related Files:
- [ ] `add_mac_transfer.php` - Log machine transfers between locations
- [ ] Use `logUpdate()` with table_name='machine_transfers' or similar

**Acceptance Criteria:**
- All machine/asset operations logged
- Machine transfers logged separately

---

## Phase 7: LOCATION MANAGEMENT 📍
**Log all location CRUD operations**

### Files to Update:

#### `add_location.php` (CREATE)
- [ ] Add logging with module='Location'

#### `edit_location.php` (UPDATE)
- [ ] Add logging with old/new values

#### Location Delete (DELETE)
- [ ] Add logging

#### Related Files:
- [ ] `add_location_contract.php` - Log location contracts

**Acceptance Criteria:**
- All location operations logged
- Location contracts logged separately if applicable

---

## Phase 8: VACATION/LEAVE MANAGEMENT 🏖️
**Log all vacation application and approval operations**

### Files to Update:

#### `apply_vac_emp_dept.php` (SUBMIT Application)
- [ ] After INSERT vacation request, add logging
- [ ] Use `ActivityLogger::logSubmit('Vacation', ...)`
- [ ] Module='Vacation', table_name='emp_vacation'

#### `emp_vac.php` (Optional - View)
- [ ] Optional: Log when employee views own vacation details

#### Vacation Approval Endpoint
- [ ] Identify file handling vacation approvals (may be AJAX in `includes/ajaxFile/`)
- [ ] After approval UPDATE, add `ActivityLogger::logApproval('Vacation', ..., 'approved', ...)`
- [ ] After rejection UPDATE, add `ActivityLogger::logApproval('Vacation', ..., 'rejected', ...)`
- [ ] Capture approval notes/reasons in description

#### `all_applied_vac.php` (Optional - View List)
- [ ] Optional: Log page access for audit

**Acceptance Criteria:**
- All vacation submissions logged with action_type=SUBMIT
- All approvals logged with action_type=APPROVE
- All rejections logged with action_type=REJECT
- Approval reasons captured in description field
- Old_values shows application data before approval

---

## Phase 9: LOAN MANAGEMENT 💰
**Log all loan application and approval operations**

### Files to Update:

#### Loan Application Page
- [ ] Find file handling loan applications (search for `INSERT INTO` in loan-related files)
- [ ] After INSERT, add `ActivityLogger::logSubmit('Loan', ...)`

#### Loan Approval Endpoint
- [ ] Find file handling loan approvals/rejections
- [ ] After approval, add `ActivityLogger::logApproval('Loan', ..., 'approved', ...)`
- [ ] After rejection, add `ActivityLogger::logApproval('Loan', ..., 'rejected', ...)`

#### `all_applied_loan.php` (Optional - View)
- [ ] Optional: Log page access

**Acceptance Criteria:**
- All loan submissions logged
- All loan approvals/rejections logged with action_type=APPROVE/REJECT
- Approval/rejection reasons captured

---

## Phase 10: USER MANAGEMENT 👨‍💼
**Log all system user CRUD operations (distinct from employees)**

### Files to Update:

#### `add_user.php` or User Creation Endpoint
- [ ] Add `ActivityLogger::logCreate('User', ...)`
- [ ] Module='User', table_name='users'

#### `edit_user.php` or User Edit Endpoint
- [ ] Add `ActivityLogger::logUpdate('User', ...)`

#### User Delete Endpoint (likely AJAX)
- [ ] Add `ActivityLogger::logDelete('User', ...)`

**Acceptance Criteria:**
- All user CRUD operations logged
- Logs show module='User' distinct from 'Employee'
- User_id and user_name in logs show WHO made changes

---

## Phase 11: CRITICAL AJAX ENDPOINTS 🔌
**Log all critical AJAX operations in `includes/ajaxFile/`**

### Files to Review & Update:

Search `includes/ajaxFile/` directory for files handling:

- [ ] **Bulk Updates** - Any files doing UPDATE in loop
  - Add logging for each record updated
  - Use `ActivityLogger::logUpdate()` in loop

- [ ] **Bulk Deletes** - Any files doing DELETE operations
  - Add logging for each record deleted
  - Use `ActivityLogger::logDelete()` in loop

- [ ] **Approvals** - approve_request.php, approve_vacation.php, etc.
  - Add `ActivityLogger::logApproval()` after UPDATE

- [ ] **Status Changes** - Files changing record status
  - Use `ActivityLogger::logUpdate()` with old status vs new status

- [ ] **Critical Operations** - Any sensitive operations (disable user, change salary, etc.)
  - Add logging with severity='CRITICAL' if status changes fail

**Acceptance Criteria:**
- All AJAX operations that modify data have logging
- AJAX logs show same quality as form-based operations
- Can track who made bulk changes and when

---

## Phase 12: REPORTS & EXPORTS 📊
**Log all report generation and export operations**

### Files to Update:

Search for report/export files:

- [ ] Files using headers for download (Content-Disposition)
- [ ] Files doing bulk data fetch for export
- [ ] Add `ActivityLogger::logExport()` before serving download

#### Specific Files:
- [ ] `download_loan_history_template.php` - Log template download
- [ ] Payroll export files - Log payroll export with record count
- [ ] Any `downloadFile.php` - Log file downloads
- [ ] `asset_return_report.php` - Log report access

**Acceptance Criteria:**
- All exports logged with action_type=EXPORT
- Record count captured
- User and timestamp logged

---

## Phase 13: BULK IMPORTS 📥
**Log all bulk import operations**

### Files to Update:

Search for files handling file uploads and imports:

- [ ] Find all `$_FILES` processing
- [ ] Add `ActivityLogger::logImport()` after processing
- [ ] Capture record count imported

#### Specific Files:
- [ ] `check_and_fix_loan_deductions.php` - Log if importing/processing data
- [ ] `create_loan_deductions.php` - Log loan deduction creation
- [ ] Any bulk update files

**Acceptance Criteria:**
- All imports logged with action_type=IMPORT
- Record count captured
- User and timestamp logged

---

## Phase 14: VALIDATION & TESTING 🧪

### For Each Phase Completed:

- [ ] Open `view_activity_logs.php` in admin account
- [ ] Perform the operation (e.g., add employee)
- [ ] Verify log entry appears within 2 seconds
- [ ] Check all fields populated:
  - user_id and user_name (correct)
  - action_type (CREATE, UPDATE, DELETE, etc.)
  - module (Employee, Customer, etc.)
  - page (add_employee.php, edit_employee.php, etc.)
  - record_id (correct ID)
  - old_values and new_values (valid JSON, complete data)
  - created_at timestamp (current time)
  - ip_address and user_agent (populated)
- [ ] Test filters in view_activity_logs.php:
  - Filter by module - should show only that module's logs
  - Filter by action_type - should show only that action
  - Filter by user - should show only that user's actions
  - Filter by date range - should show only that range
- [ ] Test "View Details" button in table:
  - Should show before/after values for updates
  - Should show complete record for creates
  - Should show deleted data for deletes

### Create Test Cases:

- [ ] Test 1: Create a new record in module - verify CREATE log
- [ ] Test 2: Edit the record - verify UPDATE log with old/new values
- [ ] Test 3: Delete the record - verify DELETE log
- [ ] Test 4: Approve/reject a request - verify APPROVE/REJECT log
- [ ] Test 5: Export data - verify EXPORT log
- [ ] Test 6: Multiple operations in sequence - verify all logged separately

### Performance Check:

- [ ] Verify `view_activity_logs.php` loads in < 2 seconds with 1000+ logs
- [ ] Verify filters execute in < 1 second
- [ ] Check database size: activity_log table should grow ~10-20MB per month (depending on usage)
- [ ] Verify no noticeable slowdown in application due to logging

---

## Phase 15: OPTIONAL ENHANCEMENTS 🚀

- [ ] Implement `ActivityLogger::cleanOldLogs(365)` as cron job (keeps only 1 year of logs)
- [ ] Add export functionality to `view_activity_logs.php` (CSV/Excel of filtered logs)
- [ ] Add charts/graphs to activity dashboard (logs per day, most active users, etc.)
- [ ] Create detailed audit report for management (who accessed what, when)
- [ ] Add alert system for critical operations (send email on high-risk actions)
- [ ] Implement log tampering detection (verify logs haven't been modified)
- [ ] Create compliance report generator (SOX, audit trail proof)

---

## Implementation Progress Tracker

### Completed Phases:
- [x] Phase 1: Cleanup & Setup (Infrastructure ready)

### In Progress:
- [ ] Phase 2: Authentication Logging
- [ ] Phase 3: Employee Management
- [ ] Phase 4: Customer Management

### Not Started:
- [ ] Phase 5: Vehicle Management
- [ ] Phase 6: Machine Management
- [ ] Phase 7: Location Management
- [ ] Phase 8: Vacation Management
- [ ] Phase 9: Loan Management
- [ ] Phase 10: User Management
- [ ] Phase 11: Critical AJAX
- [ ] Phase 12: Reports & Exports
- [ ] Phase 13: Bulk Imports
- [ ] Phase 14: Validation & Testing
- [ ] Phase 15: Optional Enhancements

---

## Key Files Reference

- **Core Logging System:**
  - `includes/init.php` - ActivityLogger class (all methods)
  - `view_activity_logs.php` - Admin viewer for all logs

- **Documentation:**
  - `ACTIVITY_LOGGING_GUIDE.md` - Complete method reference
  - `ACTIVITY_LOGGING_IMPLEMENTATION_GUIDE.md` - Patterns and best practices
  - `ACTIVITY_LOGGING_TEMPLATES.php` - Copy-paste code templates

- **Testing:**
  - `test_activity_logger.php` - Verify logging works

- **Deprecated (Remove):**
  - `includes/activity_logger.php` - Old implementation (DELETE THIS)

---

## Support & Troubleshooting

### Log Entry Not Appearing:

1. Check that activity_log table exists and has correct schema (16 columns)
2. Verify `ActivityLogger::logCreate()` etc. are called with correct parameters
3. Open `test_activity_logger.php` and verify basic logging works
4. Check PHP error logs for exceptions
5. Verify user has permission to insert into activity_log table

### Old Values/New Values Empty:

1. Ensure you're passing arrays to `logCreate()` and `logUpdate()`
2. Arrays must contain actual data, not empty
3. Check that SELECT query before UPDATE fetches the correct old values
4. Verify JSON encoding works (test with `json_encode()` in PHP)

### Performance Issues:

1. If view_activity_logs.php loads slowly:
   - Run cleanup: `ActivityLogger::cleanOldLogs(90)` to remove old logs
   - Add database index on `created_at` column for faster filtering
2. If application slows down after adding logging:
   - Check that logging calls are not in loops (batch operations)
   - Consider logging only critical operations

### Questions?

- Review `ACTIVITY_LOGGING_GUIDE.md` for all method signatures
- Check `ACTIVITY_LOGGING_TEMPLATES.php` for code examples
- Run `test_activity_logger.php` to verify system works
- Review logs in `view_activity_logs.php` to see what's captured

---

## Completion Checklist

When all phases complete, you will have:

✅ Complete audit trail of all user actions
✅ Security log of all logins/logouts
✅ CRUD audit for all business-critical operations
✅ Approval/rejection history for all workflows
✅ Export/import tracking for data operations
✅ Admin dashboard to view and search all activity
✅ Compliance-ready logs for audits and investigations
✅ Historical data for investigating issues

**Estimated Timeline:**
- Phase 1-2 (Setup + Auth): 1-2 hours
- Phase 3-4 (Employee + Customer): 2-3 hours
- Phase 5-7 (Vehicles + Machines + Locations): 2-3 hours
- Phase 8-10 (Vacation + Loan + Users): 2-3 hours
- Phase 11-13 (AJAX + Exports + Imports): 2-3 hours
- Phase 14 (Testing): 2-3 hours
- **Total: 15-20 hours for full project integration**

Start with Phase 2 (Authentication) to demonstrate value, then roll out remaining phases systematically.
