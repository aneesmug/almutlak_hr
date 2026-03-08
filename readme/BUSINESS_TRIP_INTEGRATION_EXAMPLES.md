# Business Trip Button - Integration Examples

## 🎯 Where to Add the Button

The Business Trip button should be added near the vacation application button in your system. Here are the most common locations:

---

## Example 1: Employee Profile Page (profile.php)

Find the section where vacation/leave buttons are displayed:

```php
<?php
// In profile.php, find the section with action buttons
// Usually around "My Documents" or "My Requests" section

// ORIGINAL CODE:
?>
<div class="profile-actions">
    <h4><?php echo __('my_requests'); ?></h4>
    <button class="btn btn-info" onclick="openVacationApplyModal(<?php echo $empid; ?>, <?php echo $dept_id; ?>, '<?php echo $country; ?>')">
        <i class="fa fa-calendar"></i> <?php echo __('apply_vacation'); ?>
    </button>
</div>

<?php
// ADD THIS BELOW IT:
?>
<div class="profile-actions">
    <button class="btn btn-primary" onclick="openBusinessTripApplyModal(<?php echo $empid; ?>, <?php echo $dept_id; ?>, false)">
        <i class="fa fa-plane"></i> Apply Business Trip
    </button>
</div>
<?php
```

---

## Example 2: Employee Dashboard (dashboard.php)

```php
<?php
// In the "Quick Actions" or "My Requests" section

echo '<div class="action-buttons" style="margin: 15px 0;">';

// Existing vacation button
echo '<button class="btn btn-info" onclick="openVacationApplyModal(' . $empid . ', ' . $dept . ', \'' . $country . '\')">';
echo '<i class="fa fa-calendar"></i> Vacation';
echo '</button>';

// ADD NEW BUSINESS TRIP BUTTON
echo '<button class="btn btn-primary" onclick="openBusinessTripApplyModal(' . $empid . ', ' . $dept . ', false)">';
echo '<i class="fa fa-plane"></i> Business Trip';
echo '</button>';

echo '</div>';
?>
```

---

## Example 3: More Menu (Dropdown)

```html
<!-- Add to your "More" dropdown menu in header/navbar -->

<div class="dropdown-menu">
    <a href="#" onclick="openVacationApplyModal(<?php echo $empid; ?>, ...)">
        <i class="fa fa-calendar"></i> Apply Vacation
    </a>
    
    <!-- ADD NEW ITEM -->
    <a href="#" onclick="openBusinessTripApplyModal(<?php echo $empid; ?>, ...)">
        <i class="fa fa-plane"></i> Apply Business Trip
    </a>
    
    <div class="dropdown-divider"></div>
    <a href="my_vacations.php">
        <i class="fa fa-list"></i> My Vacations
    </a>
</div>
```

---

## Example 4: Employee Card/Modal

In `employee_profile.js` or similar:

```javascript
// Build employee action buttons
function buildEmployeeActions(emp_id, dept_id) {
    let html = '<div class="employee-actions">';
    
    // Vacation button
    html += '<button class="btn btn-info" onclick="openVacationApplyModal(' + emp_id + ', ' + dept_id + ', \'\')">';
    html += '<i class="fa fa-calendar"></i> Vacation';
    html += '</button>';
    
    // ADD BUSINESS TRIP BUTTON
    html += '<button class="btn btn-primary" onclick="openBusinessTripApplyModal(' + emp_id + ', ' + dept_id + ', false)">';
    html += '<i class="fa fa-plane"></i> Business Trip';
    html += '</button>';
    
    html += '</div>';
    return html;
}
```

---

## Example 5: Bootstrap Card Layout

```html
<div class="card">
    <div class="card-header">
        <h5>My Requests</h5>
    </div>
    <div class="card-body">
        <div class="btn-group-vertical w-100">
            <button class="btn btn-outline-info text-left" onclick="openVacationApplyModal(...)">
                <span class="float-left"><i class="fa fa-calendar"></i> Apply Vacation</span>
                <span class="badge badge-info float-right">5 Days</span>
            </button>
            
            <!-- NEW BUSINESS TRIP BUTTON -->
            <button class="btn btn-outline-primary text-left" onclick="openBusinessTripApplyModal(...)">
                <span class="float-left"><i class="fa fa-plane"></i> Apply Business Trip</span>
                <span class="badge badge-primary float-right">New</span>
            </button>
            
            <button class="btn btn-outline-success text-left" onclick="...">
                <span class="float-left"><i class="fa fa-money"></i> Apply Loan</span>
            </button>
        </div>
    </div>
</div>
```

---

## Example 6: Navbar/Header Integration

```html
<!-- In header.php or main navigation -->

<div class="navbar-menu">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="profile.php">
                <i class="fa fa-user"></i> Profile
            </a>
        </li>
        
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                <i class="fa fa-files-o"></i> My Requests
            </a>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="javascript:" onclick="openVacationApplyModal(<?php echo $_SESSION['empid']; ?>, ...)">
                    <i class="fa fa-calendar"></i> Apply Vacation
                </a>
                
                <!-- NEW BUSINESS TRIP -->
                <a class="dropdown-item" href="javascript:" onclick="openBusinessTripApplyModal(<?php echo $_SESSION['empid']; ?>, ...)">
                    <i class="fa fa-plane"></i> Apply Business Trip
                </a>
                
                <a class="dropdown-item" href="javascript:" onclick="openLoanModal(<?php echo $_SESSION['empid']; ?>, ...)">
                    <i class="fa fa-money"></i> Apply Loan
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="all_applied_vac.php">
                    <i class="fa fa-list"></i> My Vacations
                </a>
            </div>
        </li>
    </ul>
</div>
```

---

## Button Styling Options

### Option 1: Solid Colors (Recommended)
```html
<button class="btn btn-primary" onclick="openBusinessTripApplyModal(...)">
    <i class="fa fa-plane"></i> Business Trip
</button>
```

### Option 2: Outline Style
```html
<button class="btn btn-outline-primary" onclick="openBusinessTripApplyModal(...)">
    <i class="fa fa-plane"></i> Business Trip
</button>
```

### Option 3: Size Variations
```html
<!-- Large -->
<button class="btn btn-primary btn-lg" onclick="openBusinessTripApplyModal(...)">
    <i class="fa fa-plane"></i> Business Trip
</button>

<!-- Small -->
<button class="btn btn-primary btn-sm" onclick="openBusinessTripApplyModal(...)">
    <i class="fa fa-plane"></i> Business Trip
</button>

<!-- Block (full width) -->
<button class="btn btn-primary btn-block" onclick="openBusinessTripApplyModal(...)">
    <i class="fa fa-plane"></i> Business Trip
</button>
```

---

## Complete Integration Example

Here's a complete example showing how to integrate Business Trip alongside other employee requests:

```php
<?php
// In employee profile page (profile.php or similar)
$emp_id = $emprow['empid'];
$emp_dept = $emprow['dept'];
$emp_country = $emprow['country'];
?>

<!-- Employee Action Buttons Section -->
<div class="card mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fa fa-tasks"></i> My Requests
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Vacation Button -->
            <div class="col-md-6 mb-2">
                <button class="btn btn-info btn-block" 
                        onclick="openVacationApplyModal(<?php echo $emp_id; ?>, <?php echo $emp_dept; ?>, '<?php echo $emp_country; ?>')">
                    <i class="fa fa-calendar"></i> 
                    <?php echo __('apply_vacation'); ?>
                </button>
            </div>
            
            <!-- BUSINESS TRIP BUTTON (NEW) -->
            <div class="col-md-6 mb-2">
                <button class="btn btn-primary btn-block" 
                        onclick="openBusinessTripApplyModal(<?php echo $emp_id; ?>, <?php echo $emp_dept; ?>, false)">
                    <i class="fa fa-plane"></i> 
                    Apply Business Trip
                </button>
            </div>
            
            <!-- Loan Button -->
            <div class="col-md-6 mb-2">
                <button class="btn btn-warning btn-block" 
                        onclick="openLoanModal(<?php echo $emp_id; ?>, <?php echo $emp_dept; ?>)">
                    <i class="fa fa-money"></i> 
                    <?php echo __('apply_loan'); ?>
                </button>
            </div>
            
            <!-- Other Requests -->
            <div class="col-md-6 mb-2">
                <button class="btn btn-secondary btn-block" 
                        onclick="openGeneralRequestModal(<?php echo $emp_id; ?>)">
                    <i class="fa fa-file-text"></i> 
                    General Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Employee Information Display -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fa fa-info-circle"></i> My Details
        </h5>
    </div>
    <div class="card-body">
        <!-- Employee details here -->
    </div>
</div>
```

---

## JavaScript Parameters Explained

```javascript
openBusinessTripApplyModal(empid, deptId, country)

// Parameters:
// empid     : Employee ID (integer) - Used to identify employee
// deptId    : Department ID (integer) - Used for supervisor lookup
// country   : Boolean - Reserved for future use (pass 'false')

// Example:
openBusinessTripApplyModal(5430, 10, false)  // ✓ Correct
openBusinessTripApplyModal($_SESSION['empid'], $dept_id, false)  // ✓ Via PHP
```

---

## Testing After Integration

### Quick Test
1. Save the page after adding button
2. Refresh page in browser
3. Locate "Apply Business Trip" button
4. Click button
5. Modal should open with form

### Full Test
1. Fill form with test data
2. Click "Register"
3. Success message should appear
4. Page should reload
5. Check database: `SELECT * FROM emp_business_trip;`

---

## Troubleshooting Integration

### Button doesn't appear
**Check:**
- Page is saved properly
- Button HTML is syntactically correct
- `onclick` parameter has correct emp_id

### Button doesn't open modal
**Check:**
- JavaScript console for errors
- `jquery.app.js` is loaded
- `openBusinessTripApplyModal` function exists
- App has read permission on JSON files

### Form doesn't load
**Check:**
- Browser dev tools (F12) → Console tab for errors
- AJAX endpoint `ajaxBusinessTrip.php` is accessible
- Network request to AJAX handler succeeds
- Database connection working

---

## Best Practices

✅ **Place consistently**: Put near vacation/leave buttons
✅ **Use consistent styling**: Match existing button styles
✅ **Icon usage**: Always use `<i class="fa fa-plane"></i>` icon
✅ **Label clarity**: Use "Apply Business Trip" label
✅ **Mobile friendly**: Ensure buttons are touch-friendly (min 44px height)
✅ **Permission checks**: Consider permission checks before displaying

---

## Permission Checks (Optional)

```php
<?php
// Only show button if employee is eligible
if (can_apply_business_trip($emp_id)) {
    ?>
    <button class="btn btn-primary" onclick="openBusinessTripApplyModal(...)">
        <i class="fa fa-plane"></i> Apply Business Trip
    </button>
    <?php
}

// Function to check eligibility
function can_apply_business_trip($emp_id) {
    // Add your business logic here
    // Examples:
    // - Check if employee has supervisor assigned
    // - Check if employee is still active
    // - Check if employee has submitted too many recent trips
    return true; // Default: allow all
}
?>
```

---

**Integration Guide Complete!**  
Choose the example that best fits your system design and modify as needed.
