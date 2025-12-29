# Asset Clearance Modal Implementation

## Overview
When an assigned employee (asset checker) tries to approve a vacation request, they will see a special modal for asset clearance instead of the normal approval modal.

## Changes Made

### 1. Backend Changes (ajaxVacation.php)

#### Status Update
- Changed asset checker status from **'pending'** to **'awaiting'** when initially assigned
- Asset checker will only transition to 'pending' when it's their turn in the approval chain

#### New Endpoints

##### `checkAssetCheckerStatus`
- **Purpose**: Check if current user is an asset checker for a vacation request
- **Parameters**: 
  - `vacation_id`: The vacation ID to check
- **Response**: 
  ```json
  {
    "status": "success",
    "is_asset_checker": true/false,
    "approver_status": "pending" | "awaiting" | null
  }
  ```
- **Location**: Lines 3392-3440

##### `processAssetClearance`
- **Purpose**: Process and save the asset clearance decision made by the asset checker
- **Parameters**:
  - `vacation_id`: The vacation ID
  - `asset_decision`: 'assets_received' or 'employee_keeps_assets'
  - `clearance_comment`: Optional notes about the decision (max 500 chars)
- **Response**: Standard JSON response with success/error
- **Location**: Lines 3447-3505
- **Action**: Calls `handle_approval_action()` to move the approval chain forward and saves comments

### 2. Frontend Changes (all_applied_vac.php)

#### Asset Clearance Modal Functions

##### `showAssetClearanceModal()`
- **Location**: Lines 937-997
- **Purpose**: Display the asset clearance modal with two options
- **Modal Content**:
  - Radio button: "Assets Received" - Employee returned all company assets
  - Radio button: "Employee Keeps Assets" - Employee will keep assets during vacation
  - Optional notes textarea (max 500 characters with counter)
- **Features**:
  - Modal cannot be dismissed (allowOutsideClick: false)
  - Validation ensures an option is selected
  - Character counter for notes

##### `processAssetClearance()`
- **Location**: Lines 998-1024
- **Purpose**: Send the asset clearance decision to backend via AJAX
- **Behavior**: 
  - Sends decision and notes to `processAssetClearance` endpoint
  - Shows success/error alert
  - Reloads page on success

#### Modified `approveRequest()`
- **Location**: Lines 1039-1077
- **New Logic**:
  1. First checks if current user is an asset checker with pending status
  2. If yes, calls `showAssetClearanceModal()` instead of normal approval flow
  3. If no, proceeds with normal approval flow (Finance Manager, Payer, etc.)
  4. Uses async:false AJAX call to block execution until check completes

## Workflow

### Manager Perspective
1. Manager approves vacation and selects asset checker
2. Asset checker is added to approval chain with status = 'awaiting'
3. Asset checker receives browser + email notification

### Asset Checker Perspective
1. Asset checker sees vacation in "My Pending" queue (when status becomes 'pending')
2. Clicks "Approve" button
3. System checks: "Is this user an asset checker with pending status?"
4. **YES**: Asset clearance modal appears instead of normal approval modal
5. Asset checker selects:
   - ✓ "Assets Received" - Employee returned all assets
   - ⚠ "Employee Keeps Assets" - Employee keeping assets during vacation
6. Optionally adds notes about the decision
7. Clicks "Confirm Clearance"
8. Decision is recorded and approval chain moves to next step

## Technical Details

### Status Flow
```
Asset Checker Added (awaiting)
        ↓
Turn comes in chain (status → pending)
        ↓
User tries to approve
        ↓
System detects is_asset_checker = true && status = pending
        ↓
Shows asset clearance modal
        ↓
User selects decision + notes
        ↓
Decision recorded, approval moves forward
```

### Database Fields
- **request_approvers.status**: Changed from 'pending' to 'awaiting' for asset checkers
- **approval_comments**: Stores the asset clearance decision as comment
- No new database tables required

### Localization Keys
- `asset_clearance`
- `asset_clearance_required`
- `asset_status`
- `assets_received`
- `employee_keeps_assets`
- `clearance_notes`
- `add_notes_about_asset_decision`
- `confirm_clearance`
- `please_select_asset_status`
- `error_processing_request`

## Files Modified
1. [includes/ajaxFile/ajaxVacation.php](includes/ajaxFile/ajaxVacation.php#L1463-L1470)
   - Changed asset_checker status to 'awaiting'
   - Added checkAssetCheckerStatus endpoint
   - Added processAssetClearance endpoint

2. [all_applied_vac.php](all_applied_vac.php#L937-L1077)
   - Added showAssetClearanceModal function
   - Added processAssetClearance function
   - Modified approveRequest to check for asset checker status
   - Added asset clearance check at beginning of approval flow

## Testing Checklist
- [ ] Manager assigns asset checker (status should be 'awaiting' in DB)
- [ ] Asset checker sees vacation in pending queue
- [ ] Clicking approve shows asset clearance modal (not normal approval)
- [ ] Can select "Assets Received"
- [ ] Can select "Employee Keeps Assets"
- [ ] Can add optional notes
- [ ] Submission records decision and moves approval forward
- [ ] Next approver is notified
- [ ] Asset decision appears in approval comments
- [ ] Encashed vacations skip asset clearance (vac_type check)
