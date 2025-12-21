# Debugging Employees List Issue

## Quick Troubleshooting Steps

### 1. **Test AJAX Endpoint Directly**

Open browser console (F12) and run:

```javascript
fetch('./includes/ajaxFile/ajaxGeneralRequest.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'action=get_employees&search='
})
.then(r => r.json())
.then(d => console.log('Employees:', d));
```

Expected result should show:
```javascript
{results: [{id: "EMP001", text: "John Doe (EMP001)"}, ...]}
```

### 2. **Check Browser Console for Errors**

1. Open view_general_request.php on an approved request
2. Press F12 (Developer Tools)
3. Click "Console" tab
4. Look for:
   - Red errors
   - "Select2 search term:" messages
   - "Select2 results:" messages

### 3. **Verify Employees Table Has Data**

Open MySQL/phpMyAdmin and run:

```sql
SELECT emp_id, name, status FROM employees WHERE status = 1 LIMIT 5;
```

Should return employees with status = 1

### 4. **Check Request Status**

On the request page, verify status shows **"APPROVED"** (not "pending_approval")

- Only approved requests show delivery section
- Delivery section only visible when `current_status === 'approved'`

### 5. **Clear Select2 Cache**

If Select2 was initialized before, it might be cached:

```javascript
// Reset Select2
$('#receivedBy').select2('destroy');

// Then reinitialize
location.reload();
```

## What Was Fixed

✅ **AJAX Access Control**: Employees endpoint is now accessible to all users
✅ **Select2 Initialization**: Uses ID selector (#receivedBy) for direct element targeting
✅ **Console Logging**: Added debug messages to track what's happening
✅ **POST Method**: Explicitly set to POST for AJAX call
✅ **Error Handling**: Better error messages for troubleshooting

## Expected Flow

1. **Page Load**
   - Select2 initializes after 500ms delay
   - Looks for #receivedBy element
   - Binds AJAX to employee search

2. **User Clicks Dropdown**
   - Sends AJAX request to get_employees
   - Server returns employee list
   - Select2 displays results

3. **User Types/Searches**
   - Sends search term to server
   - Filters employees by name or ID
   - Shows matching employees

## Files Modified

1. **ajaxGeneralRequest.php**
   - Allow get_employees action for all users
   - Added error checking
   - Better JSON response

2. **view_general_request.php**
   - Improved Select2 initialization
   - Added console logging
   - Fixed timing issue

## How to Test

1. Create a general request
2. Submit for approval
3. Approve all levels
4. View the request
5. Scroll to "Mark Items Delivery" section
6. Click "Who Received the Items?" field
7. Should see employee search dropdown
8. Type employee name or ID
9. Results should appear

## If Still Not Working

Check console output for:

```
✓ "Select2 initialized" - Init works
✓ "Select2 search term: ..." - Search sent
✓ "Select2 results: ..." - Results received
✗ Network tab shows error - AJAX failed
✗ No #receivedBy element - DOM not ready
```

Common issues:

| Issue | Solution |
|-------|----------|
| Dropdown appears empty | Employees table has no records |
| Dropdown doesn't open | Select2 not initialized |
| AJAX 404 error | Check file path is correct |
| AJAX 500 error | Check PHP error logs |

## Manual Test Query

```sql
-- Check if employees exist
SELECT COUNT(*) as total_employees FROM employees WHERE status = 1;

-- Check specific employee
SELECT emp_id, name FROM employees WHERE emp_id = 'EMP001' AND status = 1;
```
