# Quick Start Guide - User Activity Tracking

## 🚀 Installation (3 Simple Steps)

### Step 1: Run Setup Script
Open your browser and navigate to:
```
http://localhost/almutlak/system/setup_user_activity.php
```

You should see:
- ✓ Table created successfully
- ✓ Indexes created
- ✓ Setup complete message

### Step 2: Add Menu Link
Edit `includes/main_menu.php` and add this code in the admin section:

```php
<?php if ($is_system_admin || $isHR): ?>
<li>
    <a href="user_activity.php">
        <i class="mdi mdi-shield-account-outline"></i>
        <span><?= __('user_activity_log') ?? 'User Activity' ?></span>
    </a>
</li>
<?php endif; ?>
```

### Step 3: Test It
1. **Logout** from your current session
2. **Login** again (this will create your first activity log)
3. Navigate to **User Activity** from the menu
4. You should see your login activity!

## ✅ Verification Checklist

After installation, verify these:

- [ ] **Table Created**: Check phpMyAdmin → `user_activity_log` table exists
- [ ] **Menu Visible**: See "User Activity" link in navigation
- [ ] **Activity Logged**: Login and check database has new record
- [ ] **Page Loads**: `user_activity.php` shows dashboard with statistics
- [ ] **Statistics Show**: Cards display counts (Active Sessions, Today's Logins, etc.)
- [ ] **Table Populates**: See your login activity in the table
- [ ] **Details Work**: Click "Details" button shows full information
- [ ] **Screen Resolution**: Check if width/height are captured
- [ ] **Logout Tracked**: Logout and verify logout_time is recorded

## 🎯 Quick Test Scenarios

### Test 1: Basic Login Tracking
```
1. Login with any user
2. Go to user_activity.php
3. Check if today's login appears in table
4. Verify IP address is shown
5. Check if location is detected (may show "Local/Private Network" for localhost)
```

### Test 2: Logout Tracking
```
1. Note your current active session
2. Click Logout
3. Login again
4. Go to user_activity.php
5. Previous session should show logout time and "Logged Out" status
```

### Test 3: Multiple Users
```
1. Login as User A
2. Logout
3. Login as User B
4. Go to user_activity.php
5. Filter by user dropdown
6. Both users' activities should be visible
```

### Test 4: Export Functionality
```
1. Go to user_activity.php
2. Click "Export" dropdown
3. Click "Excel"
4. Excel file should download with all activity data
```

### Test 5: Details Modal
```
1. Click "Details" button on any activity row
2. Modal should popup showing:
   - Full user information
   - Complete location data
   - Browser and OS details
   - Screen resolution
   - Full user agent string
```

## 🔧 Troubleshooting

### Problem: Table not created
**Solution**: Run this SQL manually in phpMyAdmin:
```sql
-- Copy content from sql/create_user_activity_log.sql
```

### Problem: Activity not logged on login
**Check**:
1. `includes/session_check.php` has the logging code
2. `includes/user_activity_logger.php` file exists
3. Check PHP error log for database errors

### Problem: Location shows "Unknown"
**Reason**: 
- Using localhost (127.0.0.1) - geolocation doesn't work for local IPs
- Firewall blocking outbound requests to ip-api.com
**Solution**: Test from external IP or wait until production deployment

### Problem: Screen resolution shows "N/A"
**Check**:
1. JavaScript console for errors
2. `includes/activity_tracking_script.php` is included in pages
3. AJAX endpoint is accessible

### Problem: "Details" button not working
**Check**:
1. SweetAlert2 library is loaded
2. JavaScript console for errors
3. AJAX endpoint returns valid JSON

### Problem: Statistics cards show 0
**Reason**: No activity logged yet
**Solution**: 
1. Login/logout a few times
2. Refresh the page
3. Check database has records

## 📊 Database Quick Checks

### Check if table exists:
```sql
SHOW TABLES LIKE 'user_activity_log';
```

### View all activities:
```sql
SELECT * FROM user_activity_log ORDER BY login_time DESC LIMIT 10;
```

### Check today's logins:
```sql
SELECT COUNT(*) FROM user_activity_log WHERE DATE(login_time) = CURDATE();
```

### Check active sessions:
```sql
SELECT * FROM user_activity_log WHERE status = 'active';
```

### View your latest activity:
```sql
SELECT * FROM user_activity_log 
WHERE username = 'YOUR_USERNAME' 
ORDER BY login_time DESC LIMIT 1;
```

## 🎨 Customization Quick Tips

### Change card colors:
Edit `user_activity.php`, find:
```css
.mini-stat.bg-primary   /* Blue - Active Sessions */
.mini-stat.bg-success   /* Green - Today's Logins */
.mini-stat.bg-warning   /* Orange - Unique Locations */
.mini-stat.bg-info      /* Cyan - Device Types */
```

### Change rows per page:
Edit `user_activity.php`, find:
```javascript
pageLength: 25,  // Change to 10, 50, 100, etc.
```

### Add more statistics:
Edit `includes/ajaxFile/ajaxUserActivity.php` → `getStatistics()` function

### Change geolocation provider:
Edit `includes/user_activity_logger.php` → `getLocationFromIP()` function

## 📱 Mobile Testing

1. Open on mobile browser: `http://your-ip/almutlak/system/user_activity.php`
2. Check statistics cards stack vertically
3. Table should be scrollable horizontally
4. Filters should work on mobile
5. Details modal should be responsive

## 🔐 Security Notes

**Who can access?**
- System administrators (`$is_system_admin`)
- HR personnel (`$isHR`)

**To restrict further**, edit `user_activity.php`:
```php
// Add at the top after session_check
if (!$is_system_admin) {
    header("Location: dashboard.php");
    exit();
}
```

**To delete old records** (privacy/performance):
```sql
-- Delete records older than 1 year
DELETE FROM user_activity_log 
WHERE login_time < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

## 📈 Performance Tips

For large databases (>100,000 records):

1. **Add composite indexes**:
```sql
ALTER TABLE user_activity_log 
ADD INDEX idx_user_date (user_id, login_time);
```

2. **Archive old data monthly**:
```sql
CREATE TABLE user_activity_log_archive LIKE user_activity_log;

INSERT INTO user_activity_log_archive 
SELECT * FROM user_activity_log 
WHERE login_time < DATE_SUB(NOW(), INTERVAL 6 MONTH);

DELETE FROM user_activity_log 
WHERE login_time < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

3. **Optimize table regularly**:
```sql
OPTIMIZE TABLE user_activity_log;
```

## 🎯 Success Indicators

You'll know it's working when:

✅ Every login creates a new record in `user_activity_log`
✅ Dashboard shows correct real-time statistics
✅ Location is detected (or shows "Local" for localhost)
✅ Browser and OS are correctly identified
✅ Screen resolution is captured (after page loads)
✅ Logout updates the record with logout time
✅ Filters work and narrow down results
✅ Export creates downloadable files
✅ Details modal shows complete information

## 📞 Need Help?

1. **Check logs**: Look in PHP error log
2. **Browser console**: Check for JavaScript errors
3. **Network tab**: Verify AJAX requests are successful
4. **Database**: Ensure table and records exist
5. **Permissions**: Verify file/folder permissions

## 🎉 Next Steps After Setup

1. **Monitor for a week** - Let data accumulate
2. **Analyze patterns** - Look for unusual access
3. **Set retention policy** - Decide how long to keep logs
4. **Add to backup** - Include table in backup routine
5. **Train staff** - Show HR how to use the dashboard
6. **Review security** - Check for suspicious activities

---

**Installation Time**: ~5 minutes
**First Data**: Immediately after next login
**Full Features**: Available instantly

Enjoy your new User Activity Tracking System! 🚀
