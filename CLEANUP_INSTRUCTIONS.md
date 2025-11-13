# Cleanup Instructions

## To delete the test vacation and test the new system:

1. Open **phpMyAdmin** or MySQL command line
2. Select database: `almutlak_db`
3. Run this SQL:

```sql
DELETE FROM emp_vacation WHERE request_inv_no = 'LV-20251112-2539-7e50';
```

## System Overview

Now you have **TWO SEPARATE APPLICATION FORMS**:

### 1. Leave Application (Short-term leaves)
- **Button Class:** `.applyLeaveRequest`
- **AJAX Type:** `applyLeave`
- **ID Prefix:** `LV-`
- **Types Available:**
  - Sick Leave
  - Maternity Leave
  - Business Trip
  - Compensatory Leave
  - Other Leave

### 2. Annual Vacation Application
- **Button Class:** `.applyvacationAtter`
- **AJAX Type:** `applyVacation`
- **ID Prefix:** `VAC-`
- **Types Available:**
  - Fly (Annual/Emergency)
  - Local Vacation
  - Encashed

## Testing Steps

1. Delete the test vacation using the SQL above
2. Apply for **Leave** (e.g., Sick Leave) → Should generate `LV-YYYYMMDD-EMPID-XXXX`
3. Apply for **Annual Vacation** (e.g., Fly - Annual) → Should generate `VAC-YYYYMMDD-EMPID-XXXX`

## What Was Fixed

✅ Removed "Annual Vacation" from Leave dropdown (it belongs to the Vacation form)
✅ Leave applications now ALWAYS use `LV-` prefix
✅ Vacation applications already use `VAC-` prefix
✅ Simplified the active vacation check (now checks for ANY active request)
✅ Updated error messages to show correct request type based on prefix
