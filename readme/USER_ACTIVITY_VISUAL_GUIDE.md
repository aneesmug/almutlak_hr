# User Activity Page - Visual Guide

## Dashboard Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  🛡️ User Activity Log                                                   │
│  Track user login sessions, location, device information, and browsing  │
│  details.                                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐│
│  │   👤 Active  │  │ 📅 Today's   │  │  🌍 Unique   │  │  📱 Device   ││
│  │   Sessions   │  │   Logins     │  │  Locations   │  │   Types      ││
│  │              │  │              │  │              │  │              ││
│  │      42      │  │     156      │  │      28      │  │      3       ││
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘│
│                                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Filters:  [All Users ▼]  [All Status ▼]  [All Devices ▼]  [All Loc ▼]│
│                                                                          │
│  [Export ▼]  [Search: ____________]                    Showing 1-25/500 │
│                                                                          │
│  ┌────┬─────────────┬──────────────┬──────────────┬─────────┬──────────┐│
│  │ ID │ User        │ Login Time   │ Logout Time  │Duration │ IP Addr  ││
│  ├────┼─────────────┼──────────────┼──────────────┼─────────┼──────────┤│
│  │501 │Ahmed Salem  │24 Nov, 09:15│24 Nov, 17:30│08:15 hrs│192.168.1│││
│  │500 │Sara Khalid  │24 Nov, 08:42│  [Active]   │  --     │10.0.0.15│││
│  │499 │Mohammed Ali │23 Nov, 14:20│23 Nov, 18:05│03:45 hrs│172.16.2 │││
│  └────┴─────────────┴──────────────┴──────────────┴─────────┴──────────┘│
│                                                                          │
│  ┌──────────────┬──────────────┬──────────────┬──────────┬─────────────┐│
│  │ Location     │ Device       │ Browser      │ OS       │ Screen      ││
│  ├──────────────┼──────────────┼──────────────┼──────────┼─────────────┤│
│  │Riyadh, SA    │Desktop       │Chrome 119    │Windows 10│1920x1080    ││
│  │Jeddah, SA    │Mobile        │Safari 17     │iOS 17.1  │390x844      ││
│  │Dubai, AE     │Desktop       │Firefox 120   │macOS 14  │2560x1440    ││
│  └──────────────┴──────────────┴──────────────┴──────────┴─────────────┘│
│                                                                          │
│  ┌────────────┬─────────────────────────────────────────────────────────┐│
│  │ Status     │ Action                                                  ││
│  ├────────────┼─────────────────────────────────────────────────────────┤│
│  │ Logged Out │ [👁️ Details]                                            ││
│  │ Active ✓   │ [👁️ Details]                                            ││
│  │ Logged Out │ [👁️ Details]                                            ││
│  └────────────┴─────────────────────────────────────────────────────────┘│
│                                                                          │
│  [< Previous]  [1] [2] [3] ... [20]  [Next >]                           │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

## Details Modal (When clicking Details button)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Activity Details                                              [✕]      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Left Column:                      Right Column:                        │
│  ────────────                      ─────────────                        │
│                                                                          │
│  User: ahmed.salem                 Country: Saudi Arabia                │
│  Employee Name: Ahmed Salem        Region/City: Riyadh, Riyadh Province │
│  Login Time: 24 Nov 2025, 09:15   ISP: Saudi Telecom Company (STC)     │
│  Logout Time: 24 Nov 2025, 17:30  Browser: Google Chrome 119.0         │
│  Session Duration: 8 hours, 15 min Operating System: Windows 10        │
│  IP Address: 192.168.1.100         Device: Desktop                      │
│                                    Screen Resolution: 1920x1080         │
│                                                                          │
│  User Agent:                                                            │
│  Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36          │
│  (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36                    │
│                                                                          │
│                                           [Close]                       │
└─────────────────────────────────────────────────────────────────────────┘
```

## Statistics Cards Detail

```
┌─────────────────────────┐
│   👤 Active Sessions    │  ← Blue gradient background
│                         │
│         42              │  ← Large number
│                         │
│  Users currently logged │
│  in to the system       │
└─────────────────────────┘

┌─────────────────────────┐
│  📅 Today's Logins      │  ← Green gradient background
│                         │
│         156             │
│                         │
│  Total login events     │
│  since midnight         │
└─────────────────────────┘

┌─────────────────────────┐
│  🌍 Unique Locations    │  ← Orange gradient background
│                         │
│         28              │
│                         │
│  Different cities       │
│  accessed from          │
└─────────────────────────┘

┌─────────────────────────┐
│  📱 Device Types        │  ← Cyan gradient background
│                         │
│         3               │
│                         │
│  Desktop, Mobile,       │
│  Tablet variations      │
└─────────────────────────┘
```

## Filter Dropdowns

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│ User Filter      │  │ Status Filter    │  │ Device Filter    │  │ Location Filter  │
├──────────────────┤  ├──────────────────┤  ├──────────────────┤  ├──────────────────┤
│ All              │  │ All              │  │ All              │  │ All              │
│ Ahmed Salem      │  │ Active           │  │ Desktop          │  │ Riyadh, SA       │
│ Sara Khalid      │  │ Logged Out       │  │ Mobile           │  │ Jeddah, SA       │
│ Mohammed Ali     │  │ Timeout          │  │ Tablet           │  │ Dubai, AE        │
│ Fatima Hassan    │  └──────────────────┘  └──────────────────┘  │ Dammam, SA       │
│ ...              │                                               └──────────────────┘
└──────────────────┘
```

## Export Options

```
┌────────────────────┐
│ [Export ▼]         │
├────────────────────┤
│ 📄 Copy            │
│ 📊 Excel           │
│ 📝 CSV             │
│ 📕 PDF             │
│ 🖨️  Print           │
└────────────────────┘
```

## Status Badges

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ ✓ Active     │  │ Logged Out   │  │ ⚠ Timeout     │
└──────────────┘  └──────────────┘  └──────────────┘
  Green border      Gray border       Red border
```

## Mobile Responsive View

```
Mobile (< 768px):

┌───────────────────────┐
│  🛡️ User Activity     │
├───────────────────────┤
│ ┌───────────────────┐ │
│ │ 👤 Active Sessions│ │
│ │       42          │ │
│ └───────────────────┘ │
│ ┌───────────────────┐ │
│ │ 📅 Today's Logins │ │
│ │      156          │ │
│ └───────────────────┘ │
│ ┌───────────────────┐ │
│ │ 🌍 Unique Locs    │ │
│ │       28          │ │
│ └───────────────────┘ │
│ ┌───────────────────┐ │
│ │ 📱 Device Types   │ │
│ │        3          │ │
│ └───────────────────┘ │
├───────────────────────┤
│ Filters (stacked)     │
│ [All Users ▼]        │
│ [All Status ▼]       │
├───────────────────────┤
│ Table (scrollable)    │
│ ←──────────────────→  │
└───────────────────────┘
```

## Color Scheme

- **Primary**: #5b73e8 (Blue) - Headers, icons
- **Success**: #1abc9c (Green) - Active status
- **Warning**: #f1b44c (Orange) - Warning states
- **Danger**: #e74c3c (Red) - Timeout status
- **Info**: #50a5f1 (Cyan) - Information
- **Secondary**: #95a5a6 (Gray) - Logged out status

## Icons Used

- 🛡️ `mdi-shield-account-outline` - Page header
- 👤 `mdi-account-check` - Active sessions
- 📅 `mdi-calendar-today` - Today's logins
- 🌍 `mdi-earth` - Locations
- 📱 `mdi-devices` - Device types
- 👁️ `mdi-eye` - View details button
- 📊 `mdi-file-excel` - Excel export
- 📕 `mdi-file-pdf` - PDF export
- 🖨️ `mdi-printer` - Print

## Interactive Features

1. **Hover Effects**: Buttons highlight on hover
2. **Sortable Columns**: Click column headers to sort
3. **Live Search**: Type to filter results instantly
4. **Pagination**: Navigate through pages smoothly
5. **Responsive**: Adapts to all screen sizes
6. **Tooltips**: Hover over icons for explanations
7. **Modal Popups**: Click details for full view
8. **Auto-refresh**: Statistics update automatically

## Data Flow Visualization

```
User Login
    ↓
session_check.php
    ↓
user_activity_logger.php
    ↓
┌─────────────────────────┐
│ Capture Session Data    │
│ - User ID, Employee ID  │
│ - Login timestamp       │
└─────────────────────────┘
    ↓
┌─────────────────────────┐
│ Detect IP & Location    │
│ - Call ip-api.com       │
│ - Get country, city     │
│ - Get ISP, timezone     │
└─────────────────────────┘
    ↓
┌─────────────────────────┐
│ Parse User Agent        │
│ - Detect browser        │
│ - Detect OS             │
│ - Detect device type    │
└─────────────────────────┘
    ↓
┌─────────────────────────┐
│ Insert to Database      │
│ user_activity_log table │
└─────────────────────────┘
    ↓
Store activity_log_id
in PHP session
    ↓
┌─────────────────────────┐
│ JavaScript Executes     │
│ - Capture screen size   │
│ - Send via AJAX         │
│ - Update database       │
└─────────────────────────┘
    ↓
Activity Logged ✓

User Logout
    ↓
logout.php
    ↓
user_activity_logger.php
    ↓
┌─────────────────────────┐
│ Update Database         │
│ - Set logout_time       │
│ - Set status = logout   │
└─────────────────────────┘
    ↓
Session Destroyed
```

---

**Note**: This is a text-based representation. The actual page uses:
- Bootstrap 4 components
- DataTables plugin
- SweetAlert2 for modals
- Custom CSS for styling
- Font Awesome/Material Design icons
