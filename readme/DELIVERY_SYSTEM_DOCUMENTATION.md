# Delivery & Completion System for General Requests

## Overview
This feature adds complete delivery tracking and item fulfillment management to the General Request system. After a request is approved, authorized users can mark items as delivered, pending, or canceled, and specify which employee received the items.

## Features

### 1. **Two-Step Delivery Process**
   - **Step 1**: Select which employee received the items using Select2 dropdown
   - **Step 2**: Mark each item individually as:
     - ✅ **Delivered** - Item was successfully delivered
     - ⏱️ **Pending** - Item is still waiting to be delivered
     - ❌ **Canceled** - Item delivery was canceled

### 2. **Employee Selection (Select2)**
   - Dynamic employee dropdown with search
   - Shows employee name and ID
   - Fetches employees from the `employees` table with status = 1
   - AJAX-powered search for easy selection

### 3. **Item-Level Tracking**
   - Each item in the request can be tracked individually
   - Real-time status display:
     - Green badge for delivered items
     - Yellow badge for pending items
     - Red badge for canceled items

### 4. **Automatic Status Update**
   - When all items are marked as delivered, request status automatically changes to "completed"
   - Delivery completion date is recorded

### 5. **Delivery History**
   - View who received items and when
   - Display complete delivery details on completed requests
   - Show item-by-item delivery status

## Database Schema

### New Tables

#### `general_request_deliveries`
```sql
CREATE TABLE `general_request_deliveries` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_inv_no VARCHAR(50) NOT NULL,
    received_by VARCHAR(20) NOT NULL,  -- Employee ID
    delivery_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Modified Tables

#### `general_request_items` - Added Columns
```sql
ALTER TABLE `general_request_items` ADD:
    delivery_status VARCHAR(20) NULL  -- 'pending', 'delivered', 'canceled'
    delivery_id INT NULL              -- Reference to deliveries table
```

#### `general_requests` - Added Columns
```sql
ALTER TABLE `general_requests` ADD:
    completed_at DATETIME NULL        -- When all items were delivered
```

## Implementation Files

### Modified/Created Files:

1. **view_general_request.php**
   - Added delivery section UI
   - Shows delivery form when request status is 'approved'
   - Displays delivery history when status is 'completed'
   - Includes Select2 initialization for employee selection

2. **ajaxGeneralRequest.php**
   - Added `get_employees` action - Searches and returns employees via Select2
   - Added `mark_delivery` action - Updates item delivery statuses
   - Handles database transactions for data consistency

3. **migration_delivery_system.sql**
   - SQL migration file to set up required tables and columns

## Usage

### For Users

1. **View Approved Request**
   - Navigate to an approved general request
   - Scroll to "Mark Items Delivery" section

2. **Select Recipient**
   - Click "Who Received the Items?" field
   - Search for and select an employee
   - Select2 will dynamically search the employees table

3. **Mark Item Statuses**
   - For each item, select one of three options:
     - Radio button for "Delivered" (✓)
     - Radio button for "Pending" (⏱)
     - Radio button for "Canceled" (✗)

4. **Submit**
   - Click "Update Delivery Status" button
   - System will process and update all items
   - Page reloads to show completion status

5. **View History**
   - Once completed, delivery details are displayed
   - Shows who received items and when
   - Shows item-by-item delivery status

### API Endpoints

#### Get Employees (AJAX)
```
POST: /includes/ajaxFile/ajaxGeneralRequest.php
Action: get_employees
Parameters:
  - search: String (optional, for filtering)
Response:
  {
    results: [
      { id: "emp_id", text: "Employee Name (emp_id)" },
      ...
    ]
  }
```

#### Mark Delivery (AJAX)
```
POST: /includes/ajaxFile/ajaxGeneralRequest.php
Action: mark_delivery
Parameters:
  - inv_no: Request invoice number
  - received_by: Employee ID who received items
  - items: Array of item IDs with status (delivered/pending/canceled)
Response:
  {
    success: true/false,
    message: "Success message or error"
  }
```

## Status Flow

```
Draft → Pending Approval → Approved → [Delivery Tracking] → Completed
                          
When approved:
- Users can update delivery status for each item
- Select employee who received items

When all items marked delivered:
- Request status automatically changes to "completed"
- Delivery date recorded
- Completion is locked (read-only view)
```

## Validation

- **Recipient Required**: Must select an employee before submitting
- **All Items Required**: All items must have a delivery status selected
- **Status Constraints**: Only approved requests can have delivery status updated
- **Data Integrity**: Uses database transactions to ensure consistency

## UI/UX Features

- **Color-Coded Badges**: 
  - 🟢 Green = Delivered
  - 🟡 Yellow = Pending
  - 🔴 Red = Canceled

- **Real-Time Feedback**: SweetAlert2 notifications for success/error
- **Loading States**: Shows spinner while processing
- **Responsive Design**: Works on desktop and mobile
- **Print-Friendly**: Delivery section excluded from print (no-print class)

## Security

- Request status validation before allowing delivery updates
- Only approved requests can be marked for delivery
- Non-employee users only (employee role excluded)
- Employee data fetched from secure database queries
- CSRF protection via form structure

## Future Enhancements

- PDF reports of delivery confirmations
- Email notifications to recipients
- Bulk delivery updates for multiple items
- Delivery signature/photo proof
- Partial delivery tracking (e.g., 2 of 5 items delivered)
- Delivery return/rejection management

## Troubleshooting

### Select2 Not Loading
- Ensure Select2 JS/CSS are properly loaded
- Check browser console for errors
- Verify `/plugins/select2/` path exists

### Delivery Status Not Saving
- Check that request status is "approved"
- Verify employee ID exists in employees table
- Check MySQL error logs for constraint violations

### Employee List Not Showing
- Verify employees table has records with status = 1
- Check AJAX response in browser network tab
- Ensure employee emp_id field exists

## Database Migration

Run the migration SQL file to set up required tables:

```bash
mysql -u username -p database_name < migration_delivery_system.sql
```

Or execute via phpMyAdmin or MySQL workbench.
