# Employee Supervisor Management Page Guide

## Overview
A new administrative page has been created to manage and update employee vacation approvers (direct supervisors). This page allows HR administrators to search for employees and reassign their direct supervisors when needed.

## File Location
`manage_employee_supervisors.php`

## Access Control
Only the following roles can access this page:
- Administrator
- HR_Senior_BP
- HR_Operations
- HR_Supervisor
- HR_Recruitment
- HR_Team
- HR_Team_Manager
- HR_Manager

## Features

### 1. Employee Search
- Search employees by name or employee ID
- Minimum 2 characters required for search
- Shows matching employees with their designation and ID
- Displays only active employees (status = 1)
- Real-time AJAX search results

### 2. Employee Selection
- Click on any employee from search results to select them
- Display shows:
  - Employee name and ID
  - Employee designation
  - Current vacation approver information

### 3. Supervisor Management
- View current vacation approver for selected employee
- Click "Change Supervisor" button to select a new approver
- SweetAlert2 modal shows all available supervisors
- Visual indicator for current supervisor (green highlight)
- Shows supervisor name, designation, and user type

### 4. Change Supervisor
- Select new supervisor from the modal list
- Supervisor list includes all employees with admin login access
- Previous supervisor for comparison
- Confirmation button to update

### 5. Activity Logging
- All supervisor changes are logged to the activity_logger
- Log entry includes:
  - Employee name
  - Old supervisor ID
  - New supervisor name and ID
  - Timestamp and user who made the change
  - Description: "Changed vacation approver for [Employee Name] from ID [Old] to [New Name] (ID [New])"

### 6. Success Notification
- SweetAlert2 success message confirms the change
- Shows new supervisor's name
- No page reload required - data updates dynamically

## User Interface Components

### Search Section
```
Search Employee: [Input field]
↓
List of matching employees (max 20 results)
```

### Selected Employee Info Box
```
Selected Employee:
- Name and ID
- Designation

Current Vacation Approver:
- Name and designation
- User type
```

### Action Buttons
- **Change Supervisor**: Opens selection modal
- **Clear**: Resets the form and clears all selections

## Technical Implementation

### AJAX Endpoints
The page handles multiple AJAX actions:

1. **search_employees**
   - Input: search term (min 2 chars)
   - Output: JSON array of matching employees
   - Query: Searches name and emp_id fields

2. **get_supervisors**
   - Input: emp_id
   - Output: JSON with supervisor list and current supervisor ID
   - Returns all employees with admin_login access

3. **update_supervisor**
   - Input: emp_id, supervisor_id
   - Output: JSON success/error message
   - Updates: employees.supervisor_id
   - Logs: Activity logger entry

### Database Tables Modified
- `employees` - supervisor_id field updated

### Database Tables Queried
- `employees` - to get employee and supervisor data
- `admin_login` - to filter potential supervisors (only users with admin access)
- `activity_logger` - to log the change (via ActivityLogger class)

## JavaScript Libraries Used
- jQuery - AJAX requests and DOM manipulation
- SweetAlert2 - Success/error messages and supervisor selection modal
- Bootstrap - Styling and layout

## CSS Classes
- `.supervisor-info-box` - Information display boxes with blue left border
- `.supervisor-item` - Selectable supervisor items with hover effects
- `.supervisor-item.current` - Green highlight for current supervisor
- `.supervisor-item.selected` - Blue highlight for selected supervisor

## Error Handling
- Invalid search terms (< 2 characters)
- Employee not found
- Invalid employee or supervisor ID
- Database update failures
- All errors display via SweetAlert2 error modal

## Menu Integration
Added to main menu under "Employee's" section:
- **Label**: "Manage Supervisors"
- **Icon**: fa-users-gear
- **Visibility**: HR administrators only
- **Translations**: Uses `__('manage_supervisors', 'Manage Supervisors')` with fallback

## Language Support
The page uses the translation system with fallback defaults:
- All interface text uses `__()` function
- Default English translations provided
- RTL support via `dir="rtl"` attribute
- Can be expanded by adding translations to language files

## Safety Features

### SQL Injection Prevention
- All user inputs escaped using `escape_string()` or prepared statements
- JSON responses prevent XSS attacks
- HTML escaping on output

### Authorization
- Role-based access control at page entry
- Permission check before employee supervisor change
- ActivityLogger tracks all modifications

### Data Validation
- Employee existence verification
- Supervisor existence verification
- Input length validation
- Type validation on numeric IDs

## Performance Considerations
- AJAX search limits results to 20 employees
- Search results cached in browser
- No page reload for supervisor change
- Efficient database queries with proper indexing

## Troubleshooting

### Search Not Working
- Ensure employee has status = 1 (active)
- Verify at least 2 characters in search field
- Check browser console for AJAX errors

### Supervisor Not Appearing in List
- Employee must have admin_login access
- Verify employee is not filtered out by role
- Check database for corrupted admin_login records

### Changes Not Saving
- Check database permissions
- Verify employee and supervisor IDs are valid
- Check activity_logger table for errors

## Future Enhancements
- Bulk supervisor updates
- Department-based supervisor filtering
- Approval chain preview
- Supervisor change history/audit trail
- Email notification to new supervisor
