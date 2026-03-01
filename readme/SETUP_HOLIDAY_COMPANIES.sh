#!/bin/bash
#
# Holiday Company Assignment System - Setup Script
# 
# This script sets up the company-wise holiday assignment system
# It creates the necessary database tables and indexes
#

echo "================================"
echo "Holiday Company Assignment Setup"
echo "================================"
echo ""

# Note: This is a bash script for reference. 
# You need to execute the SQL commands in PHPMyAdmin or mysql CLI

echo "Step 1: Run the database migration SQL..."
echo "Execute the SQL commands from: sql/add_holiday_companies.sql"
echo ""

echo "Step 2: Files that have been updated:"
echo "  - manage_holidays.php (added company-wise assignment UI)"
echo "  - includes/vacation_calculator.php (filters holidays by company)"
echo ""

echo "Step 3: Upload changes to your server:"
echo "  - Backup existing files first!"
echo "  - Replace manage_holidays.php"
echo "  - Replace includes/vacation_calculator.php"
echo ""

echo "Step 4: Run database migration:"
echo "  - Open PHPMyAdmin or use mysql CLI"
echo "  - Execute SQL from sql/add_holiday_companies.sql"
echo ""

echo "Step 5: Optional - Backfill existing holidays:"
echo "  - If you have existing holidays and want them applied to all companies,"
echo "    run the backfill SQL (uncomment in add_holiday_companies.sql)"
echo ""

echo "Step 6: Test the implementation:"
echo "  1. Go to manage_holidays.php"
echo "  2. Create a new holiday and select multiple companies"
echo "  3. Edit an existing holiday to assign companies"
echo "  4. Test vacation deduction for different company employees"
echo ""

echo "================================"
echo "Setup Complete!"
echo "================================"
