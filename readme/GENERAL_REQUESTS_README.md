# General Request System - Implementation Guide

## Overview
A comprehensive request management system that allows non-employee users to request items/services from various departments (IT, HR, Transportation, Finance, Maintenance, Admin, etc.). The system integrates seamlessly with the existing approval workflow infrastructure.

## System Features

### Core Functionality
- ✅ **Multi-Department Support**: Users can submit requests to IT, HR, Transportation, Finance, Maintenance, Admin, and other departments
- ✅ **Item Management**: Add multiple items per request with specifications, quantities, and types
- ✅ **Priority Levels**: Low, Medium, High, and Urgent priority options
- ✅ **File Attachments**: Upload multiple files (PDF, Images, Word, Excel) as supporting documents
- ✅ **Approval Workflow**: Utilizes existing `request_approvers` and `approval_request_types` tables
- ✅ **Role-Based Access**: Different views for administrators, managers, and regular users
- ✅ **Status Tracking**: Draft, Pending Approval, Approved, and Rejected statuses
- ✅ **SweetAlert2 Integration**: Modern, user-friendly alerts and confirmations
- ✅ **RTL Support**: Full Arabic/English language support

## Database Structure

### Tables Created

#### 1. `general_requests` (Main Table)
```sql
- id (PRIMARY KEY)
- inv_no (Request number, indexed)
- request_title (Title/subject)
- department_to (Target department)
- request_category (Equipment, Service, Supplies, etc.)
- priority (low, medium, high, urgent)
- description (Additional details)
- emp_id (Requesting employee, indexed)
- emp_name (Requester name)
- user_dept (Requester department)
- current_status (draft, pending_approval, approved, rejected, indexed)
- current_approval_level (Current approval step)
- created_at, updated_at (Timestamps)
```

#### 2. `general_request_items` (Items Table)
```sql
- id (PRIMARY KEY)
- request_inv_no (Links to general_requests, indexed)
- item_name (Item name)
- item_type (Category/type of item)
- quantity (Number of items)
- specifications (Additional specs/notes)
- created_at (Timestamp)
```

#### 3. `general_request_attachments` (Attachments Table)
```sql
- id (PRIMARY KEY)
- request_inv_no (Links to general_requests, indexed)
- attachment (Filename)
- docu_ext (File extension)
- created_at (Timestamp)
```

#### 4. Integration with Existing Tables
- Uses `approval_request_types` table with entry: `('General Request', 'general_requests')`
- Uses `request_approvers` table for approval chain management
- Uses `smt_request_status` table for status logging

## Files Created

### 1. Database Setup
**File**: `sql/general_requests_table.sql`
- Creates all necessary tables
- Adds entry to `approval_request_types`
- Includes proper indexes and foreign key relationships

### 2. Frontend Pages

#### a) New Request Form
**File**: `new_general_request.php`
- **Purpose**: Create new general requests
- **Features**:
  - Dynamic item rows with add/remove functionality
  - Department-specific item type suggestions
  - File upload with validation
  - Real-time form validation
  - Auto-generated request numbers (format: GR{emp_id}{timestamp})
- **Access**: Non-employee users only

#### b) Requests Listing
**File**: `all_general_requests.php`
- **Purpose**: View all general requests with filtering
- **Features**:
  - DataTables with server-side processing
  - Status-based filtering (Draft, Pending Approval, Approved, Rejected)
  - Search functionality
  - Export to Excel, PDF, Print
  - Role-based visibility
  - Delete functionality with SweetAlert2 confirmation
- **Access**: Non-employee users only

#### c) Request Details/View
**File**: `view_general_request.php`
- **Purpose**: View and manage individual requests
- **Features**:
  - Complete request details display
  - Items list with specifications
  - Attachment downloads
  - Approval timeline visualization
  - Draft submission with approver selection
  - Approve/Reject actions for approvers
  - Print functionality
  - Status badges with color coding
- **Access**: Request creator, approvers, administrators

### 3. Backend/AJAX Handlers

#### a) DataTable Handler
**File**: `includes/ajaxFile/generalRequestAjaxTbl.php`
- **Purpose**: Server-side processing for requests listing
- **Features**:
  - Pagination, sorting, searching
  - Role-based filtering
  - Approval chain JOIN queries
  - Status filtering
  - Action buttons generation

#### b) Form Submission Handler
**File**: `includes/ajaxFile/ajaxGeneralRequest.php`
- **Purpose**: Handle AJAX operations
- **Operations**:
  - `create_general_request`: Create new request with items and attachments
  - `delete_general_request`: Delete request with all related data
- **Features**:
  - Transaction management
  - File upload handling
  - Permission validation
  - Activity logging integration
  - Error handling with JSON responses

## Installation Steps

### Step 1: Database Setup
```sql
-- Run the SQL file to create tables
SOURCE sql/general_requests_table.sql;

-- OR execute via phpMyAdmin/MySQL client
-- Import: sql/general_requests_table.sql
```

### Step 2: Create Upload Directory
```bash
mkdir -p assets/general_request_attachments
chmod 777 assets/general_request_attachments
```

### Step 3: Verify File Permissions
```bash
# Ensure PHP files are readable
chmod 644 new_general_request.php
chmod 644 all_general_requests.php
chmod 644 view_general_request.php
chmod 644 includes/ajaxFile/generalRequestAjaxTbl.php
chmod 644 includes/ajaxFile/ajaxGeneralRequest.php
```

### Step 4: Update Menu (Optional)
Add menu entry to `includes/main_menu.php`:
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

## Usage Guide

### For Requesters

#### Creating a New Request
1. Navigate to **General Requests** menu
2. Click **"New Request"** button
3. Fill in request details:
   - Request Title (required)
   - Target Department (required)
   - Priority Level (required)
   - Category (required)
   - Description (optional)
4. Add items:
   - Click **"Add Item"** button
   - Enter item name, type, quantity, and specifications
   - Add multiple items as needed
5. Upload attachments (optional):
   - Select one or more files (PDF, Images, Word, Excel)
6. Click **"Submit Request"**

#### Submitting for Approval (Draft → Pending Approval)
1. Open the request in Draft status
2. Select approvers from your department managers
3. Click **"Submit for Approval"**
4. First approver receives notification

### For Approvers

#### Reviewing and Approving Requests
1. Receive browser notification for pending request
2. Open request from **General Requests** list
3. Review request details and items
4. Add optional note
5. Click **"Approve"** or **"Reject"**
6. Next approver is automatically notified (if approved)

### For Administrators

#### Managing All Requests
- View all requests regardless of department
- Delete any request at any status
- Monitor approval progress
- Export data for reporting

## Integration with Existing Systems

### Approval Workflow
The system uses the existing approval infrastructure:
- `approval_request_types` table stores the request type mapping
- `request_approvers` table manages the approval chain
- `helper_functions.php` functions:
  - `save_approval_chain()`
  - `handle_approval_action()`
  - `getEmployeeDetails()`
  - `create_browser_notification()`

### Activity Logging
If `ActivityLogger` class is available:
- Request creation is logged
- Request deletion is logged
- Approval actions are logged via helper functions

### Email Notifications
Uses existing email infrastructure:
- SMTP settings from `settings` table
- PHPMailer integration
- Template-based emails for approvals

## Department-Specific Item Types

### IT Department
- Laptop, Desktop, Monitor, Keyboard, Mouse
- Printer, Printer Ink/Toner
- Network Equipment
- Software License

### Transportation
- Vehicle, Mobile Phone, SIM Card
- Fuel Card, GPS Device

### HR Department
- Stationery, Office Supplies
- Furniture, ID Card, Badge

### Finance
- Calculator, Receipt Book
- Document Folder, Safe/Locker

### Maintenance
- Tools, Equipment
- Safety Gear, Cleaning Supplies

### Admin
- Furniture, Office Equipment
- Decoration

## Security Features

### Access Control
- **User Type Restriction**: Only non-employee users can access the system
- **Creator Permissions**: Only request creators can edit/delete draft requests
- **Approver Validation**: Only assigned approvers can approve/reject specific requests
- **Administrator Override**: Administrators have full access to all operations

### Data Validation
- Server-side input sanitization using `mysqli_real_escape_string()`
- File upload validation (type, size)
- Required field validation
- Transaction rollback on errors

### File Upload Security
- Allowed extensions whitelist
- Unique filename generation using MD5 hash
- Separate upload directory outside webroot-accessible paths
- Extension-based file type validation

## Troubleshooting

### Common Issues

#### 1. "Request not found" Error
- **Cause**: Invalid request ID or deleted request
- **Solution**: Check URL parameter, ensure request exists in database

#### 2. File Upload Fails
- **Cause**: Directory permissions or size limits
- **Solution**: 
  ```bash
  chmod 777 assets/general_request_attachments
  # Check php.ini for upload_max_filesize and post_max_size
  ```

#### 3. DataTable Shows No Data
- **Cause**: AJAX endpoint error or database connection issue
- **Solution**: 
  - Check browser console for errors
  - Verify database connection in `includes/db.php`
  - Check `generalRequestAjaxTbl.php` for SQL errors

#### 4. Approval Chain Not Working
- **Cause**: Missing entry in `approval_request_types` table
- **Solution**:
  ```sql
  INSERT INTO `approval_request_types` (`type_name`, `main_table_name`) 
  VALUES ('General Request', 'general_requests');
  ```

#### 5. SweetAlert2 Not Working
- **Cause**: CDN connection issue or script loading order
- **Solution**: Verify internet connection or download SweetAlert2 locally

## API Endpoints (AJAX)

### generalRequestAjaxTbl.php (DataTable)
**Method**: POST
**Parameters**:
- `draw`, `start`, `length` (DataTables params)
- `user_type`, `user_dept`, `emptype`, `emp_id` (User context)
- `status` (Filter by status)
- `search` (Search term)

**Response**: JSON with DataTables format

### ajaxGeneralRequest.php (Operations)

#### Create Request
**Parameters**:
- `action`: "create_general_request"
- `inv_no`, `request_title`, `department_to`, `request_category`, `priority`
- `description`, `emp_name`, `user_dept`
- `items[]`: Array of item data
- `attachments[]`: File upload array

**Response**:
```json
{
  "success": true|false,
  "message": "...",
  "inv_no": "GR54302512161234"
}
```

#### Delete Request
**Parameters**:
- `action`: "delete_general_request"
- `inv_no`: Request number

**Response**:
```json
{
  "success": true|false,
  "message": "..."
}
```

## Performance Considerations

### Database Optimization
- Indexes on `inv_no`, `emp_id`, `current_status` columns
- JOIN optimization for approval chain queries
- Use of `GROUP BY` to avoid duplicate rows

### Frontend Optimization
- Server-side DataTables processing
- Lazy loading of approval chain data
- Minimal DOM manipulation

### File Storage
- Files stored with hashed names to avoid conflicts
- Separate directory for easy backup/cleanup
- Extension-based organization possible

## Future Enhancements

### Potential Features
- [ ] Email notifications for status changes
- [ ] Request templates for common items
- [ ] Bulk request creation
- [ ] Mobile-responsive improvements
- [ ] Request history/audit trail
- [ ] Department-specific approval rules
- [ ] Budget tracking integration
- [ ] Recurring request scheduling
- [ ] Request fulfillment tracking
- [ ] Analytics dashboard

### Technical Improvements
- [ ] PDO migration for database operations
- [ ] API endpoint documentation
- [ ] Unit tests for AJAX handlers
- [ ] Code documentation (PHPDoc)
- [ ] Performance profiling

## Support and Maintenance

### Regular Maintenance Tasks
1. **Weekly**: Review pending requests and approval bottlenecks
2. **Monthly**: Clean up old attachments if needed
3. **Quarterly**: Audit user permissions and access logs
4. **Yearly**: Archive old requests for performance

### Backup Recommendations
```bash
# Database backup
mysqldump -u username -p database_name general_requests general_request_items general_request_attachments > general_requests_backup.sql

# File backup
tar -czf general_request_attachments_backup.tar.gz assets/general_request_attachments/
```

## Credits
- **Developer**: GitHub Copilot (Claude Sonnet 4.5)
- **Framework**: Al-Mutlak WMS (Legacy PHP System)
- **UI Libraries**: Bootstrap 4, DataTables, SweetAlert2, Select2
- **Date Created**: December 16, 2025

## Version History
- **v1.0.0** (2025-12-16): Initial release
  - Core request creation and management
  - Approval workflow integration
  - File attachment support
  - Role-based access control

---

**Note**: This system is designed to integrate seamlessly with the existing Al-Mutlak WMS infrastructure. All approval workflow functions, authentication, and helper utilities are reused from the core system.
