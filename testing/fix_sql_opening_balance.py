#!/usr/bin/env python3
"""
Script to add opening_balance column to SQL INSERT statements.
The opening_balance value should equal the available_balance value.
"""
import re

def fix_sql_row(line):
    """Add opening_balance after available_balance in a SQL INSERT row."""
    # Skip headers and non-data lines
    if not line.strip().startswith('('):
        # Check if it's a header line that needs opening_balance
        if '`available_balance`, `carryover' in line:
            return line.replace('`available_balance`, `carryover', 
                              '`available_balance`, `opening_balance`, `carryover')
        return line
    
    # Parse the row: id, emp_id, vac_id, contract_id, dates..., 
    # total_days, used_days, remaining_balance, available_balance, [opening_balance?], carryover_days, timestamps
    
    # Pattern: find the 4 consecutive decimal values (balance fields) before carryover
    # Format: ..., total_days, used_days, remaining_balance, available_balance, carryover, ...
    # We need to insert opening_balance between available_balance and carryover
    
    # Count how many balance values exist after dates and before timestamps
    # Split by commas and reconstruct
    parts = re.split(r',\s*', line)
    
    # Find the balance section (4 consecutive decimals)
    # After contract_id(3) and two dates (4,5), we have:
    # parts[6] = total_days
    # parts[7] = used_days
    # parts[8] = remaining_balance
    # parts[9] = available_balance
    # parts[10] = carryover (should be) OR opening_balance if already exists
    
    # Check if opening_balance already exists (11 parts between id and first timestamp)
    # Count parts: id, emp_id, vac_id, contract, date, date, 4 balances + opening?, carryover, 3 timestamps
    # Without opening: id, emp, vac, contract, 2 dates = 6, then 4 decimals + carryover + 3 timestamps = 14 total data parts
    # With opening: id, emp, vac, contract, 2 dates = 6, then 5 decimals + carryover + 3 timestamps = 15 total data parts
    
    # Better approach: check if part[10] has a timestamp pattern
    if len(parts) > 10:
        # Check if parts[10] looks like a date string
        if "'" in parts[10] and ('2025' in parts[10] or '2026' in parts[10]):
            # opening_balance already present, skip this row
            return line
        elif parts[10].replace('.', '').replace('-', '').strip('()').strip().isdigit():
            # parts[10] is carryover (numeric), insert opening_balance before it
            available_balance = parts[9].strip()
            parts.insert(10, available_balance)
            return ', '.join(parts)
    
    return line


# Read the SQL from standard input or file
input_file = 'untitled:Untitled-1'  # This won't work, need actual path

print("This script processes SQL INSERT statements to add opening_balance column.")
print("Usage: Provide SQL content via stdin or modify script to read from file.")
print("\nTo use:")
print("1. Save your SQL to a temp file")
print("2. Run: python fix_sql_opening_balance.py < input.sql > output.sql")
