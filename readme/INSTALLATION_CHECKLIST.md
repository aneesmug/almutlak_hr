# General Request System - Installation Checklist

## Pre-Installation Requirements
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ or MariaDB 10.4+
- [ ] Existing Al-Mutlak WMS system running
- [ ] User with database CREATE privileges
- [ ] SweetAlert2 CDN access or local copy

## Database Installation
- [x] Run `sql/install_general_requests.sql` in MySQL
  ```bash
  mysql -u username -p database_name < sql/install_general_requests.sql
  ```
- [ ] Verify tables created:
  - `general_requests`
  - `general_request_items`
  - `general_request_attachments`
- [ ] Verify `approval_request_types` entry exists
  ```sql
  SELECT * FROM approval_request_types WHERE main_table_name = 'general_requests';
  ```

## File System Setup
- [x] Upload directory created: `assets/general_request_attachments/`
- [ ] Set directory permissions (Linux/Mac only):
  ```bash
  chmod 777 assets/general_request_attachments
  ```
- [ ] Verify all PHP files are in place:
  - [x] `new_general_request.php` (root)
  - [x] `all_general_requests.php` (root)
  - [x] `view_general_request.php` (root)
  - [x] `includes/ajaxFile/generalRequestAjaxTbl.php`
  - [x] `includes/ajaxFile/ajaxGeneralRequest.php`

## SQL Files
- [x] `sql/general_requests_table.sql` (detailed version)
- [x] `sql/install_general_requests.sql` (quick install)

## Documentation
- [x] `GENERAL_REQUESTS_README.md` (comprehensive guide)
- [x] This checklist file

## Optional Configuration

### Menu Integration
Add to `includes/main_menu.php`:
```php
<?php if ($user_type != 'employee'): ?>
<li>
    <a href="all_general_requests.php">
        <i class="mdi mdi-file-document-box-multiple"></i>
        <span><?=__('general_requests', 'General Requests')?></span>
    </a>
</li>
<?php endif; ?>
```

### Language Translations
Add to your translation files (if using custom translations):

**English** (`lang/en.php`):
```php
'general_requests' => 'General Requests',
'create_general_request' => 'Create General Request',
'view_general_request' => 'View General Request',
'all_general_requests' => 'All General Requests',
'request_title' => 'Request Title',
'target_department' => 'Target Department',
'request_category' => 'Request Category',
'requested_items' => 'Requested Items',
// ... add more as needed
```

## Testing Checklist

### Basic Functionality
- [ ] Create new request as non-employee user
- [ ] Add multiple items to request
- [ ] Upload attachments (PDF, images, documents)
- [ ] Submit request (save as draft)
- [ ] View request details
- [ ] Submit draft for approval
- [ ] Approve request as department manager
- [ ] Reject request and verify notifications
- [ ] Delete draft request
- [ ] Search and filter requests

### Permission Testing
- [ ] Employee users cannot access general requests
- [ ] Non-employee users can access
- [ ] Only creator can edit draft requests
- [ ] Only assigned approver can approve/reject
- [ ] Administrator can delete any request

### Error Handling
- [ ] Form validation works (required fields)
- [ ] File type validation works
- [ ] Invalid request ID shows error
- [ ] Permission denied shows appropriate message
- [ ] Database errors are caught gracefully

## Verification Steps

### 1. Database Verification
```sql
-- Check table structure
DESCRIBE general_requests;
DESCRIBE general_request_items;
DESCRIBE general_request_attachments;

-- Check approval type entry
SELECT * FROM approval_request_types WHERE main_table_name = 'general_requests';

-- Check for any test data
SELECT COUNT(*) as total_requests FROM general_requests;
```

### 2. File Permissions (Linux/Mac)
```bash
ls -la assets/ | grep general_request_attachments
# Should show: drwxrwxrwx or drwxr-xr-x
```

### 3. PHP Error Checking
```bash
# Check for PHP syntax errors
php -l new_general_request.php
php -l all_general_requests.php
php -l view_general_request.php
php -l includes/ajaxFile/generalRequestAjaxTbl.php
php -l includes/ajaxFile/ajaxGeneralRequest.php
```

### 4. Browser Console Testing
- [ ] No JavaScript errors in console
- [ ] AJAX requests return valid JSON
- [ ] DataTable loads successfully
- [ ] SweetAlert2 modals display correctly

## Troubleshooting

### Issue: Cannot create request
**Check**:
- PHP session is active
- User is logged in
- User type is not 'employee'
- Database connection is working

### Issue: File upload fails
**Check**:
- Directory exists: `assets/general_request_attachments/`
- Directory has write permissions
- PHP `upload_max_filesize` is sufficient (default 2MB)
- PHP `post_max_size` is sufficient

### Issue: Approval chain not working
**Check**:
- Entry exists in `approval_request_types` table
- `helper_functions.php` is loaded
- Functions `save_approval_chain()` and `handle_approval_action()` exist

### Issue: DataTable shows no data
**Check**:
- Browser console for errors
- AJAX endpoint: `includes/ajaxFile/generalRequestAjaxTbl.php`
- Database connection in `includes/db.php`
- SQL query errors (check error log)

## Post-Installation Tasks

### Security Review
- [ ] Review file upload security settings
- [ ] Verify SQL injection prevention (all inputs sanitized)
- [ ] Check XSS prevention (all outputs escaped)
- [ ] Review access control logic

### Performance Optimization
- [ ] Add indexes if needed (already included in schema)
- [ ] Monitor query performance
- [ ] Consider pagination limits for large datasets

### Backup Strategy
- [ ] Include new tables in backup script
- [ ] Backup upload directory regularly
- [ ] Document restore procedure

## Support

For issues or questions, refer to:
- `GENERAL_REQUESTS_README.md` - Comprehensive documentation
- Existing Al-Mutlak WMS documentation
- System administrator

## Completion Sign-Off

Installation completed by: ___________________

Date: ___________________

Tested by: ___________________

Date: ___________________

Approved by: ___________________

Date: ___________________

---

**Next Steps After Installation**:
1. Train users on the new system
2. Create user documentation/guide
3. Set up monitoring for request volume
4. Plan for future enhancements
