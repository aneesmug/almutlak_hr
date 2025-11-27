# Documents GUI - Technical Implementation Reference

## CSS Grid System Details

### Main Container: `.documents-grid`

```css
.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
  padding: 10px 0;
}
```

**Properties:**
- `display: grid`: Enables CSS Grid layout
- `grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))`: 
  - `repeat()`: Repeats column pattern
  - `auto-fill`: Automatically fills columns as space available
  - `minmax(280px, 1fr)`: Each column minimum 280px, maximum 1 fraction of available space
- `gap: 20px`: 20px spacing between grid items
- `padding: 10px 0`: Top/bottom padding

**Result:**
- Desktop (1400px width): ~5 columns
- Tablet (768px width): ~2-3 columns
- Mobile (480px width): 1 column (handled by media query)

---

## Document Card Structure

### Container: `.document-card`

```css
.document-card {
  background: #fff;
  border: 1px solid #e3eaef;
  border-radius: 8px;
  padding: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}
```

**Key Features:**
- `display: flex; flex-direction: column`: Stacks content vertically
- `transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1)`: Smooth animations
- `overflow: hidden`: Prevents content overflow
- `box-shadow`: Subtle depth effect

### Hover State: `.document-card:hover`

```css
.document-card:hover {
  border-color: #3f51b5;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
}
```

**Effects:**
- Border color changes to primary blue
- Shadow increases (depth effect)
- Card lifts 4px (elevation effect)
- All changes animate smoothly

---

## File Type Badge System

### Badge Container: `.file-type-badge`

```css
.file-type-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 8px;
  font-size: 24px;
  color: #fff;
  font-weight: 600;
  flex-shrink: 0;
}
```

**Properties:**
- `flex-shrink: 0`: Prevents badge from shrinking
- `display: flex`: Centers icon inside
- `width/height: 48px`: Fixed square size
- `font-size: 24px`: Large icon

### Color Variants

**PDF (Danger):**
```css
.file-type-badge.badge-danger {
  background: linear-gradient(135deg, #f1556c 0%, #ee3d54 100%);
}
```
- Color progression: Light red (#f1556c) → Dark red (#ee3d54)
- Angle: 135deg (diagonal top-left to bottom-right)

**Excel (Success):**
```css
.file-type-badge.badge-success {
  background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);
}
```
- Color progression: Light green (#51cf66) → Dark green (#37b24d)

**Word (Primary):**
```css
.file-type-badge.badge-primary {
  background: linear-gradient(135deg, #3f51b5 0%, #303f9f 100%);
}
```
- Color progression: Light blue (#3f51b5) → Dark blue (#303f9f)

**Images (Info):**
```css
.file-type-badge.badge-info {
  background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
}
```
- Color progression: Cyan (#00bcd4) → Dark cyan (#00acc1)

**Archives (Warning):**
```css
.file-type-badge.badge-warning {
  background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
}
```
- Color progression: Yellow (#ffc107) → Gold (#ffb300)

**Other Files (Secondary):**
```css
.file-type-badge.badge-secondary {
  background: linear-gradient(135deg, #98a6ad 0%, #7a8a97 100%);
}
```
- Color progression: Gray (#98a6ad) → Dark gray (#7a8a97)

---

## Document Preview Section

### Preview Container: `.document-preview`

```css
.document-preview {
  margin-bottom: 12px;
  border-radius: 6px;
  overflow: hidden;
  background: #f8f9fa;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-grow: 1;
}
```

**Properties:**
- `flex-grow: 1`: Expands to fill available space
- `min-height: 120px`: Minimum 120px tall
- `overflow: hidden`: Prevents content overflow
- Light gray background for visual separation

### Image Preview: `.preview-image img`

```css
.preview-image img {
  max-width: 100%;
  max-height: 100%;
  border-radius: 4px;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.preview-image img:hover {
  transform: scale(1.05);
}
```

**Hover Effect:**
- Scales image to 105% size
- Smooth transition over 0.3s
- Creates zoom effect

### Icon Preview: `.preview-icon.bg-danger`

```css
.preview-icon {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
  color: #fff;
  opacity: 0.8;
}

.preview-icon.bg-danger {
  background: linear-gradient(135deg, rgba(241, 85, 108, 0.2) 0%, 
                              rgba(238, 61, 84, 0.2) 100%);
  color: #f1556c;
}
```

**Color Variants:**
- `.bg-danger`: Red gradient background
- `.bg-success`: Green gradient background
- `.bg-primary`: Blue gradient background
- `.bg-info`: Cyan gradient background
- `.bg-warning`: Yellow gradient background
- `.bg-secondary`: Gray gradient background

**Icon Display:**
- Font size: 48px (large icons)
- Color: White (#fff) or file type color
- Opacity: 0.8 (subtle appearance)

---

## Document Information Section

### Type Label: `.document-type`

```css
.document-type {
  font-size: 14px;
  font-weight: 600;
  color: #313a46;
  margin: 0 0 4px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
```

**Styling:**
- Bold text: `font-weight: 600`
- Uppercase: `text-transform: uppercase`
- Letter spacing: 0.5px (professional look)
- Color: Dark gray (#313a46)

### Category: `.document-category`

```css
.document-category {
  font-size: 13px;
  color: #7a8a97;
  margin: 0 0 8px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
```

**Features:**
- Single line: `white-space: nowrap`
- Truncation: `text-overflow: ellipsis`
- Subdued color: #7a8a97 (gray)

### Metadata: `.document-meta`

```css
.document-meta {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  font-size: 12px;
  color: #98a6ad;
}

.document-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.document-meta i {
  font-size: 11px;
}
```

**Layout:**
- Flexbox with 12px gap
- Wraps on small screens
- Small text: 12px font size
- Icons: 11px size

**Metadata Items:**
1. **Date**: `📅 (icon) 15 Nov 2025`
2. **Time**: `🕐 (icon) 14:30`

---

## Action Buttons

### Button Container: `.document-actions`

```css
.document-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.document-actions .btn {
  flex: 1;
  padding: 6px 8px;
  font-size: 12px;
  border-radius: 4px;
  transition: all 0.2s ease;
  border: none;
  font-weight: 500;
}
```

**Properties:**
- `margin-top: auto`: Pushes buttons to bottom
- `flex: 1`: Equal width buttons
- Small padding: 6px 8px (compact look)

### View Button: `.document-actions .btn-view`

```css
.document-actions .btn-view {
  background: #e3f2fd;
  color: #3f51b5;
}

.document-actions .btn-view:hover {
  background: #bbdefb;
  color: #303f9f;
}
```

**Colors:**
- Default: Light blue background, primary text
- Hover: Darker blue background, darker text

### Download Button: `.document-actions .btn-download`

```css
.document-actions .btn-download {
  background: #e8f5e9;
  color: #51cf66;
}

.document-actions .btn-download:hover {
  background: #c8e6c9;
  color: #37b24d;
}
```

**Colors:**
- Default: Light green background, success text
- Hover: Darker green background, darker text

### Delete Button: `.btn-delete-doc`

```css
.btn-delete-doc {
  background: transparent;
  border: none;
  color: #98a6ad;
  font-size: 18px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s ease;
  opacity: 0.6;
}

.btn-delete-doc:hover {
  background: rgba(241, 85, 108, 0.1);
  color: #f1556c;
  opacity: 1;
}
```

**Hover Effect:**
- Background: Light red tint (10% opacity)
- Color: Changes to red (#f1556c)
- Opacity: Increases to full opacity

---

## Responsive Media Queries

### Tablet Breakpoint (≤ 768px)

```css
@media (max-width: 768px) {
  .documents-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }
}
```

**Changes:**
- Minimum column width: 200px (down from 280px)
- Gap: 16px (down from 20px)
- Result: ~2 columns on most tablets

### Mobile Breakpoint (≤ 576px)

```css
@media (max-width: 576px) {
  .documents-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .document-card {
    padding: 12px;
  }
  
  .document-actions {
    flex-direction: column;
  }
}
```

**Changes:**
- Grid: Single column (100% width)
- Card padding: 12px (reduced from 16px)
- Buttons: Stack vertically
- Gap: 12px (reduced from 20px)

---

## Dark Theme Adaptations

### Dark Card: `.document-card` (dark theme)

```css
.document-card {
  background: #2a2e31;
  border: 1px solid #3f4449;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.document-card:hover {
  border-color: #5c6eff;
  box-shadow: 0 8px 16px rgba(92, 110, 255, 0.2);
}
```

**Dark Colors:**
- Background: #2a2e31 (dark gray)
- Border: #3f4449 (darker gray)
- Shadow: Stronger opacity (0.3)
- Hover shadow: Blue-tinted (from primary color)

### Dark Preview: `.preview-icon.bg-danger` (dark theme)

```css
.preview-icon.bg-danger {
  background: linear-gradient(135deg, rgba(241, 85, 108, 0.3) 0%, 
                              rgba(238, 61, 84, 0.3) 100%);
  color: #f1556c;
}
```

**Adjustments:**
- Opacity: 0.3 (higher than light theme 0.2)
- Ensures visible against dark backgrounds

### Dark Buttons: `.document-actions .btn-view` (dark theme)

```css
.document-actions .btn-view {
  background: rgba(92, 110, 255, 0.15);
  color: #5c6eff;
}

.document-actions .btn-view:hover {
  background: rgba(92, 110, 255, 0.25);
  color: #7c8eff;
}
```

**Adjustments:**
- Transparent backgrounds with opacity
- Primary color adapted for dark theme (#5c6eff instead of #3f51b5)
- Higher opacity on hover (0.25)

---

## File Type Detection

### PHP Logic

```php
$file_type_map = [
    'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'danger', 'label' => 'PDF'],
    'xls' => ['icon' => 'fa-file-excel', 'color' => 'success', 'label' => 'Excel'],
    'xlsx' => ['icon' => 'fa-file-excel', 'color' => 'success', 'label' => 'Excel'],
    'doc' => ['icon' => 'fa-file-word', 'color' => 'primary', 'label' => 'Word'],
    'docx' => ['icon' => 'fa-file-word', 'color' => 'primary', 'label' => 'Word'],
    'jpg' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
    'jpeg' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
    'png' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
    'gif' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
    'zip' => ['icon' => 'fa-file-archive', 'color' => 'warning', 'label' => 'Archive'],
    'rar' => ['icon' => 'fa-file-archive', 'color' => 'warning', 'label' => 'Archive'],
    'txt' => ['icon' => 'fa-file-text', 'color' => 'secondary', 'label' => 'Text'],
];

$file_info = $file_type_map[$docu_ext_get] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'File'];
```

**Fallback:**
- If extension not in map: Uses generic 'fa-file' icon
- Default color: Secondary (gray)
- Default label: 'File'

---

## Image Preview vs Icon Display

### Condition

```php
<?php if (in_array($docu_ext_get, ['JPG', 'JPEG', 'PNG', 'GIF'])): ?>
    <!-- Show image preview -->
    <img src="./assets/emp_documents/<?= $attachment_get ?>">
<?php else: ?>
    <!-- Show icon -->
    <i class="fa <?= $file_info['icon'] ?>"></i>
<?php endif; ?>
```

**Logic:**
- Image formats (JPG, JPEG, PNG, GIF): Show actual image
- All other formats: Show file type icon
- Images are clickable to display in popup
- Icons are visual indicators only

---

## Performance Optimizations

1. **CSS Grid**: Native browser implementation (no JavaScript)
2. **Transitions**: GPU-accelerated (transform, opacity)
3. **Icon Usage**: Font Awesome (one font file)
4. **Image Lazy Loading**: Optional enhancement
5. **Minimal DOM**: No unnecessary wrapper elements
6. **Efficient Selectors**: Direct class targeting

---

## Browser Support

| Feature | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| CSS Grid | 57+ | 52+ | 10.1+ | 16+ |
| Flexbox | 29+ | 28+ | 9+ | 12+ |
| Gradients | 26+ | 16+ | 6.1+ | 12+ |
| Transitions | 26+ | 16+ | 9+ | 12+ |
| Transform | 26+ | 16+ | 9+ | 12+ |

**Result**: Works on all modern browsers released in last 5+ years

---

## Integration with Existing Systems

### AJAX Delete Handler

Uses existing `deleteAjax` class:
```html
<button class="deleteAjax" data-id='...' data-tbl='emp_docu' 
    data-file='1' data-column='path'>
```

**Endpoint**: Existing AJAX handler in jQuery app
**Method**: POST
**Action**: Deletes from emp_docu table, removes file

### File Download Handler

```html
<a href="./downloadFile.php?file=./assets/emp_documents/<?= $attachment_get ?>">
```

**Endpoint**: `downloadFile.php`
**Method**: GET
**Action**: Triggers file download with proper headers

### Document Popup Display

```javascript
onclick="javascript:displayPopup('./assets/emp_documents/<?= $attachment_get ?>')"
```

**Function**: Existing `displayPopup()` function
**Method**: Opens document/image in lightbox or new tab
**Supports**: Images, PDFs, and other document types

---

## Maintenance Notes

### CSS File Locations
- Light theme: `assets/css/style_cl.css` (starts ~line 5262)
- Dark theme: `assets/css/style_dark.css` (starts ~line 5689)

### HTML Implementation
- View Employee: `view_employee.php` (lines 445-1148)
- Profile: `profile.php` (lines 1408-1472)

### Database Table
- Table: `emp_docu`
- Fields: id, emp_id, path, docu_ext, docu_typ, created_at

### Related Files
- `includes/functions.php`: Utility functions
- `assets/js/jquery.app.js`: Main jQuery
- `includes/ajaxFile/`: AJAX handlers

---

## Troubleshooting

### Cards Not Displaying in Grid
1. Check CSS files loaded in browser
2. Verify `.documents-grid` class applied
3. Check for CSS overrides in browser DevTools

### Hover Effects Not Working
1. Verify CSS transitions loaded
2. Check for conflicting CSS
3. Ensure `transition` property not disabled

### Images Not Loading
1. Verify `assets/emp_documents/` directory exists
2. Check file permissions
3. Verify file path in database matches actual location

### Colors Not Applied
1. Check badge color classes applied
2. Verify dark/light theme CSS loaded
3. Clear browser cache

