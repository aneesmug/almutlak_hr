# Vacation Balance Cron Update - Usage Modes Guide

## Overview
The `cron_update_vacation_balances.php` script now has three distinct operating modes to prevent duplicate same-day updates while allowing flexibility for missing record checks and emergency recalculations.

---

## Mode 0: Normal (Default)
**Prevents duplicate updates on the same day**

### When to Use
- For scheduled daily cron jobs (recommended at 01:00 AM)
- For automatic balance synchronization
- Default behavior when no flags are provided

### Behavior
- ✅ Runs once per day maximum
- ✅ Updates all active employee balances
- ✅ Sets `last_updated = yesterday` to enable daily accrual accumulation
- ❌ Rejects any subsequent runs on the same calendar day with message: "ALREADY UPDATED TODAY"
- ❌ Logs all subsequent attempts

### Command
```bash
# CLI
php cron_update_vacation_balances.php

# Browser
http://localhost/almutlak/system/cron_update_vacation_balances.php
```

### Example Output
```
========== ALREADY UPDATED TODAY ==========
Last update: 2026-02-01 09:10:47
Records Updated: 433
...
```

---

## Mode 1: Check Missing Only
**Updates values without resetting the daily counter**

### When to Use
- To check for newly registered employees and create missing balance records
- To refresh existing balance values after partial data loss
- To sync values mid-day without preventing tomorrow's normal run
- Can run multiple times on the same day

### Behavior
- ✅ Bypasses the "already updated today" check
- ✅ Creates missing balance records for newly registered employees
- ✅ Refreshes all existing balance values with live calculations
- ✅ Updates balances but **DOES NOT change** `last_updated` field
- ✅ Prevents resetting the daily cron counter
- ✅ Allows Mode 0 to still run as normal run

### Command
```bash
# CLI
php cron_update_vacation_balances.php --force=1

# Browser
http://localhost/almutlak/system/cron_update_vacation_balances.php?force=1
```

### Example Output
```
========== VACATION BALANCE UPDATE RESULTS ==========
Mode: check_missing (force_level=1)
Updated last_updated field: NO
Total Employees: 433
Records Updated: 433
...
```

---

## Mode 2: Full Bypass
**Complete override - resets everything including last_updated**

### When to Use
- For emergency recalculations across all employees
- For testing and validation purposes
- When you need to force a complete resync including timestamp reset
- To reinitialize the daily counter (rare cases)

### Behavior
- ✅ Bypasses ALL once-per-day checks
- ✅ Updates all active employee balances
- ✅ **DOES update** `last_updated = yesterday` to reset accrual counter
- ✅ Can run multiple times per day
- ✅ Sets tomorrow's cron to run fresh daily accrual calculations
- ❌ Should be used sparingly - use for emergency only

### Command
```bash
# CLI
php cron_update_vacation_balances.php --force=2

# Browser
http://localhost/almutlak/system/cron_update_vacation_balances.php?force=2
```

### Example Output
```
========== VACATION BALANCE UPDATE RESULTS ==========
Mode: full_bypass (force_level=2)
Updated last_updated field: YES
Total Employees: 433
Records Updated: 433
...
```

---

## Comparison Table

| Feature | Mode 0 (Normal) | Mode 1 (Check Missing) | Mode 2 (Full Bypass) |
|---------|-----------------|----------------------|----------------------|
| **Prevents duplicate same-day runs** | ✅ YES | ❌ NO | ❌ NO |
| **Updates balance values** | ✅ YES | ✅ YES | ✅ YES |
| **Updates last_updated timestamp** | ✅ YES | ❌ NO | ✅ YES |
| **Can run multiple times/day** | ❌ NO (1st time only) | ✅ YES | ✅ YES |
| **Creates missing records** | ✅ YES | ✅ YES | ✅ YES |
| **Recommended frequency** | Once daily (01:00 AM) | As needed for fixes | Emergency only |
| **Resets accrual counter** | ✅ YES | ❌ NO | ✅ YES |

---

## Daily Workflow Example

### Scenario: February 1, 2026

**09:10 AM - Mode 0 (Normal) runs:**
```
✅ SUCCESS
- Updates all 433 employees
- Sets last_updated = 2026-01-31 (yesterday)
- Enables tomorrow's daily accrual calculations
```

**09:47 AM - Employee reports data issue - Run Mode 1:**
```
✅ SUCCESS  
- Checks for missing balance records
- Refreshes all values
- last_updated stays 2026-01-31
- Normal Mode 0 run already counted as today's update
```

**10:00 AM - Try Mode 0 again:**
```
❌ REJECTED
"ALREADY UPDATED TODAY"
- Prevents duplicate synchronization
- last_updated remains 2026-01-31
```

**11:00 AM - Critical data loss - Run Mode 2:**
```
✅ SUCCESS (EMERGENCY ONLY)
- Force recalculates everything
- Updates last_updated = 2026-01-31 (resets)
- Next normal run: tomorrow at 01:00 AM
```

---

## Decision Tree

```
Need to run cron?
├─ Is it the first run today? → YES → Use Mode 0 (Normal)
│
├─ Already ran Mode 0 today AND need to check missing records?
│  └─ Use Mode 1 (Check Missing Only)
│
└─ Emergency/Testing/Complete reset needed?
   └─ Use Mode 2 (Full Bypass)
```

---

## Key Points to Remember

1. **Mode 0 is the default** - Use for scheduled daily runs
2. **Mode 1 preserves the daily counter** - Use when Mode 0 already ran
3. **Mode 2 resets everything** - Use only in emergencies
4. **last_updated always starts at yesterday** in Mode 0 and Mode 2 to enable daily accrual
5. **Mode 1 is the "safe" option** for mid-day corrections without affecting daily rhythm
6. **All modes update the accrual calculation**

---

## Troubleshooting

### "ALREADY UPDATED TODAY" error
- **If you need to sync:**  Use `--force=1` (Mode 1)
- **If it's an emergency:**  Use `--force=2` (Mode 2)
- **If you just missed the message:** Wait until tomorrow for Mode 0

### Balances not increasing by expected daily amount
- Check that last_updated was set to yesterday's date
- Verify `cron_update_vacation_balances.php` ran successfully (check logs)
- Check vacation_balance_history table for last entry timestamp

### Need to verify which mode just ran
- Check the JSON report: `cron_logs/last_vacation_update_report.json`
- Look for: `"force_mode"` field to see which mode was used
- Look for: `"updated_last_updated"` field to see if timestamp changed

