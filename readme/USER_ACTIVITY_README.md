# User Activity Tracking System

## Overview
This system tracks comprehensive user login activity including location, device information, browser details, IP addresses, and session duration. It provides a powerful dashboard for monitoring user access patterns and security.

## Features

### 📊 Activity Tracking
- **Login/Logout Times**: Precise timestamps for all user sessions
- **Session Duration**: Automatic calculation of session length
- **IP Address Tracking**: Captures user IP with proxy detection
- **Geographic Location**: Country, region, city, timezone, and ISP information
- **Device Information**: Type (Desktop/Mobile/Tablet), screen resolution
- **Browser Details**: Browser name, version, and full user agent
- **Operating System**: OS name and version detection

### 🎯 Dashboard Features
- **Real-time Statistics**: Active sessions, today's logins, unique locations, device types
- **Advanced Filtering**: Filter by user, status, device type, or location
- **Export Options**: Excel, CSV, PDF, and Print functionality
- **Detailed View**: Click any activity to see complete session details
- **Server-side Processing**: Fast performance even with large datasets

## Installation

### Step 1: Create Database Table
Run the SQL file to create the `user_activity_log` table:

```sql
-- Run this in phpMyAdmin or MySQL console
source sql/create_user_activity_log.sql;
```

Or execute manually:
```bash
mysql -u root -p almutlak_hr < sql/create_user_activity_log.sql
```

### Step 2: Verify File Structure
Ensure these files are in place:

```
system/
├── user_activity.php                         # Main activity page
├── logout.php                                 # Updated with logout logging
├── includes/
│   ├── user_activity_logger.php              # Core logging functions
│   ├── activity_tracking_script.php          # Client-side tracking
│   ├── session_check.php                     # Updated with auto-logging
│   └── ajaxFile/
│       └── ajaxUserActivity.php              # AJAX data handler
└── sql/
    └── create_user_activity_log.sql          # Database schema
```

### Step 3: Add Screen Resolution Tracking (Optional)
Include the tracking script in your main layout/footer:

```php
<!-- Add to footer.php or main template -->
<?php include("./includes/activity_tracking_script.php"); ?>
```

### Step 4: Add Menu Link
Add the following to your `main_menu.php` or navigation:

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

## Usage

### Accessing the Activity Log
1. Navigate to `user_activity.php`
2. View the dashboard with real-time statistics
3. Use filters to narrow down results
4. Click "Details" button on any row for complete information
5. Export data using the Export dropdown

### Automatic Tracking
The system automatically logs:
- ✅ User login (via `session_check.php`)
- ✅ User logout (via `logout.php`)
- ✅ Screen resolution (via JavaScript on page load)
- ✅ Session timeout (marked as 'timeout' status)

### Manual Queries
You can also query the database directly:

```sql
-- Get all active sessions
SELECT * FROM user_activity_log WHERE status = 'active';

-- Get today's logins
SELECT * FROM user_activity_log WHERE DATE(login_time) = CURDATE();

-- Get logins by country
SELECT country, COUNT(*) as count 
FROM user_activity_log 
WHERE country IS NOT NULL 
GROUP BY country 
ORDER BY count DESC;

-- Get most used devices
SELECT device_type, COUNT(*) as count 
FROM user_activity_log 
GROUP BY device_type 
ORDER BY count DESC;
```

## API Endpoints

### AJAX Handlers (`ajaxUserActivity.php`)

#### Get Activity Log (DataTables)
```javascript
$.ajax({
    url: './includes/ajaxFile/ajaxUserActivity.php',
    type: 'POST',
    data: { ajaxType: 'get_activity_log' }
});
```

#### Get Statistics
```javascript
$.ajax({
    url: './includes/ajaxFile/ajaxUserActivity.php',
    type: 'POST',
    data: { ajaxType: 'get_statistics' }
});
```

#### Get Activity Details
```javascript
$.ajax({
    url: './includes/ajaxFile/ajaxUserActivity.php',
    type: 'POST',
    data: { 
        ajaxType: 'get_activity_details',
        activity_id: 123
    }
});
```

## Database Schema

```sql
user_activity_log
├── id (Primary Key)
├── user_id (Foreign Key to admin_login)
├── emp_id (Employee ID)
├── username
├── login_time
├── logout_time
├── ip_address
├── country, country_code
├── region, city
├── latitude, longitude
├── timezone, isp
├── browser, browser_version
├── os, os_version
├── device_type
├── screen_width, screen_height
├── user_agent (Full UA string)
├── session_id
├── status (active/logged_out/timeout)
└── created_at
```

## Geolocation API

The system uses **ip-api.com** for geolocation:
- ✅ Free for non-commercial use
- ✅ No API key required
- ✅ 45 requests/minute limit
- ✅ Supports IPv4 and IPv6

**Note**: For production with high traffic, consider:
- MaxMind GeoIP2 (paid, local database)
- IPStack (paid API)
- IP2Location (paid/free tiers)

## Security Considerations

### Privacy
- User activity data is sensitive
- Restrict access to administrators only
- Consider GDPR compliance for EU users
- Implement data retention policies

### Performance
- Table can grow large over time
- Consider archiving old records:

```sql
-- Archive records older than 1 year
INSERT INTO user_activity_log_archive 
SELECT * FROM user_activity_log 
WHERE login_time < DATE_SUB(NOW(), INTERVAL 1 YEAR);

DELETE FROM user_activity_log 
WHERE login_time < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### IP Address Accuracy
- Proxy/VPN users may show incorrect locations
- Corporate networks may show ISP location, not user location
- Local/private IPs won't have geolocation data

## Troubleshooting

### Activity not being logged
1. Check if `user_activity_log` table exists
2. Verify `session_check.php` includes the logging code
3. Check PHP error logs for database errors
4. Ensure proper permissions on `includes/` folder

### Geolocation not working
1. Check if server can make outbound HTTP requests
2. Verify `curl` is enabled in PHP
3. Check firewall/proxy settings
4. Test manually: `http://ip-api.com/json/YOUR_IP`

### Screen resolution showing N/A
1. Ensure `activity_tracking_script.php` is included
2. Check browser console for JavaScript errors
3. Verify AJAX endpoint is accessible

## Customization

### Change Geolocation Provider
Edit `includes/user_activity_logger.php`:

```php
function getLocationFromIP($ip) {
    // Replace with your preferred API
    $url = "https://api.your-provider.com/lookup/{$ip}";
    // ... process response
}
```

### Add Custom Fields
1. Alter table schema
2. Update logging function in `user_activity_logger.php`
3. Update AJAX handler to return new fields
4. Modify DataTable columns in `user_activity.php`

### Change Auto-logout Tracking
Edit `session_check.php` timeout handling:

```php
// Mark as timeout instead of destroying immediately
require_once __DIR__ . '/includes/user_activity_logger.php';
logUserLogout($conDB, 'timeout');
```

## Support & Maintenance

- **Created**: November 2025
- **Database**: MySQL 5.7+
- **PHP**: 7.4+
- **Dependencies**: jQuery, DataTables, SweetAlert2

For issues or enhancements, contact the development team.
