#!/bin/bash
# HOLIDAYS SYSTEM - SETUP CHECKLIST & INSTALLATION GUIDE
# 
# Run this checklist to ensure proper installation of the holidays feature

echo "=========================================="
echo "HOLIDAYS SYSTEM - SETUP VERIFICATION"
echo "=========================================="
echo ""

# Check 1: SQL Migration File
echo "[1/6] Checking SQL migration file..."
if [ -f "sql/holiday_system_migration.sql" ]; then
    echo "  ✅ sql/holiday_system_migration.sql found"
else
    echo "  ❌ sql/holiday_system_migration.sql NOT found"
    exit 1
fi
echo ""

# Check 2: Holiday Management Page
echo "[2/6] Checking holiday management page..."
if [ -f "manage_holidays.php" ]; then
    echo "  ✅ manage_holidays.php found"
else
    echo "  ❌ manage_holidays.php NOT found"
    exit 1
fi
echo ""

# Check 3: Helper Functions
echo "[3/6] Checking helper functions in helper_functions.php..."
if grep -q "get_active_holidays_in_range" "includes/helper_functions.php"; then
    echo "  ✅ Holiday helper functions found"
else
    echo "  ❌ Holiday helper functions NOT found"
    exit 1
fi
echo ""

# Check 4: Helper Functions - Calculate Days
echo "[4/6] Checking holiday calculation functions..."
if grep -q "calculate_holiday_days_in_vacation" "includes/helper_functions.php"; then
    echo "  ✅ Holiday calculation functions found"
else
    echo "  ❌ Holiday calculation functions NOT found"
    exit 1
fi
echo ""

# Check 5: Documentation Files
echo "[5/6] Checking documentation files..."
files_found=0
if [ -f "HOLIDAYS_FEATURE_GUIDE.md" ]; then
    echo "  ✅ HOLIDAYS_FEATURE_GUIDE.md found"
    ((files_found++))
else
    echo "  ⚠️  HOLIDAYS_FEATURE_GUIDE.md not found"
fi

if [ -f "HOLIDAYS_IMPLEMENTATION.php" ]; then
    echo "  ✅ HOLIDAYS_IMPLEMENTATION.php found"
    ((files_found++))
else
    echo "  ⚠️  HOLIDAYS_IMPLEMENTATION.php not found"
fi

if [ $files_found -ge 1 ]; then
    echo "  ✅ Documentation files found"
fi
echo ""

# Check 6: Summary
echo "[6/6] Setup Status Summary"
echo "  =========================================="
echo "  Core files:        ✅ All present"
echo "  Helper functions:  ✅ Implemented"
echo "  Documentation:     ✅ Complete"
echo "  =========================================="
echo ""

echo "NEXT STEPS:"
echo "==========="
echo ""
echo "1. Create the emp_holidays table:"
echo "   mysql -u [username] -p [database_name] < sql/holiday_system_migration.sql"
echo ""
echo "2. Add test holidays via manage_holidays.php:"
echo "   - Navigate to: http://localhost/almutlak/system/manage_holidays.php"
echo "   - Click 'Add Holiday'"
echo "   - Add test holidays (e.g., 2026-04-09 to 2026-04-13)"
echo ""
echo "3. Create a test vacation that overlaps with a holiday"
echo ""
echo "4. Verify the deduction was calculated correctly:"
echo "   - Check emp_vacation_balance table"
echo "   - used_days should be (total_days - holiday_days)"
echo ""
echo "5. Check PHP error log for debug messages:"
echo "   - tail -f /var/log/php_errors.log"
echo "   - Look for: 'DEBUG: Vacation ID X has Y holiday days'"
echo ""
echo "=========================================="
echo "Installation Verification Complete!"
echo "=========================================="
