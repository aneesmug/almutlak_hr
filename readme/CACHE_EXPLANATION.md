# Translation Cache - How It Works

## Database Storage: PERSISTENT (Data Keeps Forever)

✅ **All translated values are KEPT in the database permanently**
- Once a translation is saved, it stays there
- Data is NOT automatically deleted
- More users = more cached translations = faster system
- Cache grows over time (good for performance)

## Three-Level Caching System:

### 1. **Session Cache** (Per-Request)
- Fastest: < 0.1ms
- Lives for current page/session only
- Checks: `if (isset($_SESSION[$cache_key]))`
- Used: Within same user session

### 2. **Database Cache** (Persistent)
- Fast: < 1ms
- Lives forever (until manually deleted)
- Stored in: `translation_cache` table
- Used: Shared by ALL users, ALL sessions
- **This is where data accumulates over time**

### 3. **Google Translate API** (Last Resort)
- Slowest: 1-2 seconds
- Only called when translation NOT in session or database
- Result automatically saved to both caches for future use

## When Translations Get Saved:

❌ **NOT automatically** when you just load a page
❌ **NOT unless** a page actually calls `getDisplayName()` or `auto_translate_text()`
✅ **YES when:**
  - You load a **REPORT** that uses `getDisplayName()` in AJAX
  - You call translation functions in code
  - You manually trigger translation

## Current Status:

✓ Table exists with 8 columns
✓ Database is writable
✓ Test insert works: "Ahmed" → "أحمد"
✓ Data persists in database

**Next:** Load report pages to trigger actual translations

## To View Current Cache:

Run this command:
```php
php quick_check.php
```

Or in database:
```sql
SELECT COUNT(*) FROM translation_cache;
SELECT * FROM translation_cache ORDER BY created_at DESC LIMIT 10;
```

## Cleanup (Optional - Manual Only):

Data will keep growing. To clean old data after 90 days:
```sql
DELETE FROM translation_cache WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

**But you don't need to do this** - cache is beneficial and doesn't consume much space.
