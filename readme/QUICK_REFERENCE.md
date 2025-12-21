# Quick Reference - Delivery Modal System

## For Users

### Approving & Delivering Requests

**Step 1: Request is Approved**
```
You see: "Ready for Delivery" section with "Deliver Items" button
Action: Click the green button
```

**Step 2: Modal Opens**
```
What you'll see:
- Employee selector (search dropdown)
- Items list with delivery status options
- Optional file attachment area
```

**Step 3: Complete the Form**
```
1. Select employee who received items
   → Click dropdown → Type to search → Click to select
   
2. For each item, choose status:
   ✓ Delivered (item received)
   ⏱ Pending (not received yet)
   ✕ Canceled (item cancelled)
   
3. Optional: Upload attachment
   → Drag file into box OR click to browse
```

**Step 4: Submit**
```
Click "✓ Submit Delivery"
→ System processes
→ Page refreshes
→ Shows "Delivery Completed"
```

### Viewing Completed Delivery

**When request is completed:**
```
You see: "Delivery Completed" section with "View Delivery Details" button
Action: Click the button
Result: Modal shows delivery summary
```

---

## For Developers

### Adding the Modal to Another Page

```javascript
// Include at bottom of page:
<script>
function showDeliveryModal() {
    // Copy entire function from view_general_request.php
    // Lines 1489-1540
}

function displayFileName(fileInput) {
    // Copy entire function from view_general_request.php
    // Lines 1542-1558
}

function submitDelivery(inv_no) {
    // Copy entire function from view_general_request.php
    // Lines 1560-1620
}
</script>

// Call from button:
<button onclick="showDeliveryModal()">Deliver</button>
```

### Customizing the Modal

**Change button text:**
```javascript
confirmButtonText: '<i class="mdi mdi-check"></i> Custom Text',
cancelButtonText: 'Custom Cancel',
```

**Change colors:**
```javascript
confirmButtonColor: '#your-color',
```

**Change width:**
```javascript
width: '800px',  // Change from 700px
```

**Add more fields:**
```html
// Add before closing form div in modalContent
let customField = `
    <div style="margin-bottom: 20px;">
        <label>Your Label</label>
        <input type="text" id="your_field">
    </div>
`;
```

### Modifying Submission Data

**Add custom field to FormData:**
```javascript
formData.append('custom_field', $('#your_field').val());
```

**In backend (ajaxGeneralRequest.php):**
```php
$custom_value = $_POST['custom_field'] ?? null;
// Process accordingly
```

### Styling Customization

**Change item styling:**
```javascript
itemsHtml += `
    <div style="your-custom-styles">
        ${item.item_name}
    </div>
`;
```

**Change employee selector styling:**
```javascript
employeeHtml = `
    <div style="your-custom-styles">
        <select id="modal_receivedBySelect">
        </select>
    </div>
`;
```

### JavaScript Events

**After modal opens:**
```javascript
didOpen: function(modal) {
    // Your code here
    console.log('Modal opened');
}
```

**On form submission:**
```javascript
.then((result) => {
    if (result.isConfirmed) {
        // Your code here
    }
})
```

---

## For Designers

### Modal Appearance Settings

| Setting | Value | Location |
|---------|-------|----------|
| Modal Width | 700px | `width: '700px'` |
| Modal Theme | Light | Uses Bootstrap styling |
| Button Color (Submit) | Green (#28a745) | `confirmButtonColor` |
| Button Color (Cancel) | Gray | Default |
| Item Text Color | Dark (#2c3e50) | `color: '#2c3e50'` |
| Success Color | Green (#28a745) | Badge `badge-success` |
| Pending Color | Yellow (#ffc107) | Badge `badge-warning` |
| Canceled Color | Red (#dc3545) | Badge `badge-danger` |
| Background Color | Light (#f8f9fa) | Various divs |
| Border Color | Light (#dee2e6) | Various divs |

### Icon Changes

```javascript
// Change modal title icon:
'<i class="mdi mdi-your-icon"></i> Delivery Details'

// Change button icons:
'<i class="mdi mdi-your-icon"></i> Text'

// Available MDI icons:
// mdi-truck-delivery (current)
// mdi-package
// mdi-check-circle
// mdi-clock-outline
// mdi-close-circle
// mdi-cloud-upload
// etc.
```

### Color Themes

**Green Theme (Current)**
- Submit Button: #28a745
- Delivered Badge: #28a745
- Highlights: Green tints

**Blue Theme**
```javascript
confirmButtonColor: '#2196F3',
// Change badge styles to blue
```

**Purple Theme**
```javascript
confirmButtonColor: '#9c27b0',
// Change badge styles to purple
```

---

## Common Issues & Solutions

### Problem: Select2 Not Working
**Solution**: 
- Check Select2 library is loaded
- Verify AJAX endpoint is accessible
- Check browser console for errors

### Problem: Modal Won't Close
**Solution**:
- Check for JavaScript errors
- Verify SweetAlert2 is loaded
- Try clearing browser cache

### Problem: File Upload Not Working
**Solution**:
- Check file size is < 5MB
- Verify file type is allowed
- Check FormData is building correctly

### Problem: Employee Not Saving
**Solution**:
- Verify hidden input has value
- Check AJAX response format
- Verify database permissions

### Problem: Modal Styling Off
**Solution**:
- Check Bootstrap CSS is loaded
- Verify no CSS conflicts
- Check viewport/responsive design

---

## Quick Links

- **Main File**: `view_general_request.php`
- **AJAX Handler**: `includes/ajaxFile/ajaxGeneralRequest.php`
- **Guide**: `DELIVERY_MODAL_GUIDE.md`
- **Visual**: `DELIVERY_MODAL_VISUAL.md`
- **Notes**: `IMPLEMENTATION_NOTES.md`

---

## Version Info

**Version**: 2.0 (Modal-based)
**Created**: January 31, 2025
**Status**: Production Ready
**Compatibility**: PHP 7.4+, jQuery 3.6+, SweetAlert2 11+

---

## Support

For issues or questions:
1. Check documentation files above
2. Review console errors
3. Check network tab in DevTools
4. Verify database is accessible
5. Test on staging environment first

**Emergency Rollback**: 
- Revert to previous `view_general_request.php` from version control
- Clear browser cache
- Test before deploying again
