# Precise GPS Geolocation Setup

## Overview
This system now captures **exact GPS coordinates** from users' browsers in addition to IP-based city-level location data.

## How It Works

### 1. **Initial Login** (IP-based, city-level accuracy)
- When user logs in, the server captures their IP address
- IP geolocation returns city/region level coordinates (~5-30km accuracy)
- These are stored in `user_activity_log` table

### 2. **After Login** (Browser GPS, precise accuracy)
- The geolocation capture script (`geolocation-capture.js`) runs automatically
- Browser requests user's permission to access GPS location
- User sees browser prompt: "Allow access to your location?"
- If user allows: precise GPS coordinates are sent to server (~5-30m accuracy)
- Server updates the activity record with exact coordinates
- Map shows precise location instead of city-level

## User Permission Flow

1. User logs in → Server stores IP-based location
2. Dashboard loads → Browser geolocation script runs
3. Browser shows permission prompt (first time only per site)
4. User clicks "Allow" → GPS coordinates captured
5. Coordinates sent to server and saved
6. Map updates with precise marker

## Database Column

A new column stores GPS accuracy:
```sql
ALTER TABLE `user_activity_log` ADD COLUMN `location_accuracy` DECIMAL(5,2) DEFAULT NULL;
```

The accuracy value indicates precision in meters (e.g., 5m = ±5 meter accuracy)

## Implementation Checklist

- [x] Created AJAX endpoint: `includes/ajaxFile/ajaxPreciseLocation.php`
- [x] Created geolocation script: `assets/js/geolocation-capture.js`
- [ ] Add script include to main pages (dashboard, etc.)
- [ ] Run database migration to add `location_accuracy` column
- [ ] Test with browser that has GPS access

## Script Include

Add this line to your dashboard page or footer (before `</body>`):
```html
<script src="assets/js/geolocation-capture.js"></script>
```

## Browser Support

- ✅ Chrome/Edge: Full support
- ✅ Firefox: Full support  
- ✅ Safari: Full support
- ✅ Mobile browsers: Excellent (actual GPS on phones)
- ⚠️ IE: Not supported

## Privacy Notes

- GPS capture only works over HTTPS (browsers enforce this)
- Users must grant explicit permission
- No data is stored without user consent
- Permission is per-site, users can revoke anytime
- Location data is only used for internal tracking

## Testing

1. Open browser DevTools (F12) → Console
2. Login to dashboard
3. Check console for messages like:
   - "GPS Location captured: {latitude: 24.7136, longitude: 46.6753, accuracy: 10}"
   - "GPS coordinates saved to server" (success)
   - "Geolocation error..." (if user denied permission)

4. Go to User Activity page → Login Map
5. Should see marker at exact GPS location (not city center)
