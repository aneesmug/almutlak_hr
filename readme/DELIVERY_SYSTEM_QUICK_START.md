# Quick Start - Delivery System

## Step-by-Step Implementation Guide

### Phase 1: Database Setup (5 minutes)
1. Copy `migration_delivery_system.sql` content
2. Run in MySQL/phpMyAdmin
3. Tables created automatically

### Phase 2: Code Deployment (Already Done)
✅ Modified files:
- `view_general_request.php` - Added UI and JavaScript
- `includes/ajaxFile/ajaxGeneralRequest.php` - Added AJAX handlers

### Phase 3: Testing

#### Test 1: Create and Approve Request
1. Create a new general request
2. Add items and attachments
3. Submit for approval
4. Approve all levels

#### Test 2: Delivery Tracking
1. View approved request
2. Scroll to "Mark Items Delivery" section
3. Click employee dropdown - should show search
4. Type employee name - should filter results
5. Select an employee
6. Select delivery status for each item:
   - Click "Delivered" radio for some items
   - Click "Pending" for others
   - Try "Canceled" status
7. Click "Update Delivery Status"
8. Should see success message
9. Page reloads showing completed status

#### Test 3: Verify Delivery Record
1. View same request again
2. Should show "Delivery Completed" section
3. Should display employee name and date
4. Items should show their delivery status badges

## Key Features Implemented

### Feature 1: Employee Select2
- Searches employees table dynamically
- Shows "Name (ID)" format
- AJAX-powered with 250ms debounce
- Minimum 0 characters (shows all on focus)

### Feature 2: Multi-Item Status
- Three status options per item:
  - Delivered ✓ (Green)
  - Pending ⏱ (Yellow)
  - Canceled ✗ (Red)
- Radio button selection
- Visual indicators

### Feature 3: Auto-Completion
- When all items = "delivered"
- Request status → "completed"
- Records completion timestamp
- Locks form (read-only)

### Feature 4: Delivery History
- Shows who received items
- Shows when delivered
- Item-by-item status breakdown

## File Locations

```
/view_general_request.php          ← Main UI
/includes/ajaxFile/ajaxGeneralRequest.php   ← AJAX handlers
/migration_delivery_system.sql     ← Database schema
/DELIVERY_SYSTEM_DOCUMENTATION.md  ← Full docs
/DELIVERY_SYSTEM_QUICK_START.md    ← This file
```

## API Reference

### Get Employees
```
Action: get_employees
Method: AJAX POST
Search: Employee name or ID
Returns: [{ id, text }, ...]
```

### Mark Delivery
```
Action: mark_delivery
Method: AJAX POST
Params: inv_no, received_by, items[]
Validates: Approved status required
Returns: { success, message }
```

## Status Codes

| Status | Display | Icon | Color |
|--------|---------|------|-------|
| delivered | Delivered | ✓ | Green |
| pending | Pending | ⏱ | Yellow |
| canceled | Canceled | ✗ | Red |

## Common Issues & Fixes

### Issue: Select2 not showing
**Fix**: Check that `/plugins/select2/js/select2.min.js` is loaded

### Issue: Employees not appearing
**Fix**: Verify employees table exists and has status=1 records

### Issue: Form won't submit
**Fix**: Ensure all items have status selected

### Issue: Delivery not saving
**Fix**: Check request status is "approved" (not "pending_approval")

## Testing Checklist

- [ ] Request created and items added
- [ ] Request submitted for approval
- [ ] All approval levels passed
- [ ] Delivery section visible
- [ ] Employee dropdown works
- [ ] Employee search filters results
- [ ] Can select delivery status for items
- [ ] Submit button works
- [ ] Success message appears
- [ ] Page reloads
- [ ] Delivery details visible
- [ ] Status badges show correctly
- [ ] Completed request shows read-only delivery

## Support

For detailed documentation, see: `DELIVERY_SYSTEM_DOCUMENTATION.md`

For technical support, check:
1. Browser console for JavaScript errors
2. Network tab for AJAX responses
3. MySQL error logs
4. PHP error logs

## Version
- Delivery System v1.0
- Compatible with: General Requests Module
- Database: MySQL 5.7+
- PHP: 7.4+
