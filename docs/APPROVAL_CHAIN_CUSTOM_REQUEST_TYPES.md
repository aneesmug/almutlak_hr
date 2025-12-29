# Approval Chain - Custom Request Types Feature

## Overview
This document describes the new feature that allows administrators to dynamically add custom approval chain request types from the frontend without modifying code.

## What's New

### Button: "Add New Request Type"
A new button has been added to the **Approval Chain Configuration** section in **Application Settings** → **Approval** tab.

**Location:** Top-right corner of the "Approval Chain Configuration" panel  
**Icon:** Plus icon (+)  
**Color:** Green (success)

## How to Use

### Step 1: Click "Add New Request Type" Button
Navigate to:
- **Application Settings** (app_seetings.php)
- Click **Approval** tab in the left sidebar
- Click the green **"Add New Request Type"** button

### Step 2: Fill in Request Type Details

A modal dialog will appear with three fields:

1. **Request Type ID** (Required)
   - Format: lowercase letters and underscores only
   - Examples: `travel_request`, `business_trip`, `training_request`, `equipment_request`
   - This ID will be used internally to store the approval chain settings

2. **Request Type Name** (Required)
   - Display name for the request type
   - Examples: "Travel Request", "Business Trip", "Training Request"
   - This appears in the UI

3. **Description** (Optional)
   - Brief description of what this request type is for
   - Examples: "Employee travel and business trip approvals"

### Step 3: Create and Configure

After clicking **"Create"**:
- The new request type will be added to your approval chain configuration
- It will appear as a new card in the Approval Chain Configuration section
- You can now add approvers to this custom request type by clicking its **"Add Approver"** button

## Example Workflow

### Example: Adding a "Travel Request" Type

1. Click **"Add New Request Type"**
2. Fill in:
   - **Request Type ID:** `travel_request`
   - **Request Type Name:** `Travel Request`
   - **Description:** `Employee travel approvals and expense management`
3. Click **"Create"**
4. New "Travel Request" card appears with:
   - "Add Approver" button
   - Empty approval chain (no steps configured yet)
5. Click **"Add Approver"** to start building the approval chain
6. Add approvers in sequence (e.g., Direct Supervisor → HR Manager → Finance Officer)

## Technical Implementation

### Files Modified

1. **app_seetings.php**
   - Added **"Add New Request Type"** button
   - Added `showAddNewRequestTypeModal()` function
   - Added `addNewRequestType()` function
   - Modified `renderApprovalChainSettings()` to fetch types from database
   - Added `renderApprovalChainUI()` to render dynamically loaded types

2. **includes/approval_chain_handler.php**
   - Added `get_all_request_types` action handler
   - Added `create_new_request_type` action handler
   - Added `getAllRequestTypes()` function
   - Added `createNewRequestType()` function

### Database Changes
No database schema changes required. Uses existing `app_settings` table:
- Setting names follow pattern: `approval_chain_{type_id}`
- Setting group: `approval`
- Setting type: `json`

## Default Request Types
The system comes with these built-in request types (cannot be deleted):
1. **Vacation Request** - Annual and fly vacations
2. **Excuse Leave** - Sick leave, exam leave, etc.
3. **Loan Request** - Employee loan applications
4. **Resignation Request** - Employee resignations
5. **Rejoin Request** - Rejoining after resignation

## Custom Request Types
Any additional types you create are custom and appear below the default types.

## Validation Rules

### Request Type ID Validation
- ✓ Lowercase letters (a-z)
- ✓ Underscores (_)
- ✗ Uppercase letters
- ✗ Numbers
- ✗ Special characters (except underscore)
- ✗ Spaces

**Valid examples:**
- `travel_request`
- `business_trip`
- `training_approval`
- `equipment_request`

**Invalid examples:**
- `TravelRequest` (uppercase)
- `Travel-Request` (hyphen)
- `Travel Request` (space)
- `travel_request_2` (numbers)

### Error Handling
- Duplicate IDs are prevented with message: "This request type ID already exists"
- Invalid format is caught before submission
- All inputs are sanitized before database insertion

## Integration with Your Application

### Using Custom Request Types in Code

Once you create a custom request type (e.g., `travel_request`), you can use it in your application code:

```php
// Fetch the approval chain settings
$settingName = "approval_chain_travel_request";
$query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '{$settingName}' LIMIT 1");

if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    $chain = json_decode($row['setting_value'], true);
    // Use $chain to build approval workflow
}
```

### Creating Requests with Custom Types

When creating a new request with your custom type:

```php
// Create approval request
$inv_no = "TRVL-" . date('Ymd') . "-" . $emp_id . "-" . substr(md5(time()), 0, 4);

// Get request type ID for 'travel_request'
$typeQuery = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'travel_request' LIMIT 1");
$typeId = ($typeQuery && mysqli_num_rows($typeQuery) > 0) ? mysqli_fetch_assoc($typeQuery)['id'] : null;

// Build approval chain from config
$settingName = "approval_chain_travel_request";
$query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '{$settingName}' LIMIT 1");
$approversConfig = json_decode(mysqli_fetch_assoc($query)['setting_value'], true);

// Create approval records in request_approvers table
foreach ($approversConfig as $step) {
    // Find approver by user_type and insert into request_approvers
}
```

## Limitations & Notes

- **Cannot delete default request types** - The 5 built-in types are permanent
- **Custom types are deletable** - Future feature can add delete capability
- **Approver roles are the same** - All request types use the same pool of available approver roles
- **No dynamic request table** - Custom request types still need their own database tables if storing request data

## Future Enhancements

Possible future improvements:
- [ ] Delete custom request types
- [ ] Edit request type name/description
- [ ] Duplicate existing approval chain as template
- [ ] Export/import approval chain configurations
- [ ] Role-based visibility (only show relevant types to users)
- [ ] Default chain templates for common request types

## Troubleshooting

### "This request type ID already exists"
- The ID is already used by another request type
- Choose a different ID

### "Request Type ID must contain only lowercase letters and underscores"
- Check for uppercase letters, numbers, or special characters
- Use underscores instead of spaces or hyphens

### "Failed to create request type"
- Check database connection
- Verify `app_settings` table exists
- Check user has admin privileges

### New request type not appearing
- Refresh the page
- Clear browser cache
- Check network tab for AJAX errors in browser console

## Support

For questions or issues, check:
- [APPROVAL_CHAIN_CONFIGURATION.md](APPROVAL_CHAIN_CONFIGURATION.md) - Full configuration guide
- [APPROVAL_CHAIN_IMPLEMENTATION.md](../APPROVAL_CHAIN_IMPLEMENTATION.md) - Technical implementation details
