#!/usr/bin/env python3
"""
Add opening_balance column to emp_vacation_balance INSERT statements in SQL dump.
The opening_balance value equals the available_balance value for each row.
"""
import re
import sys

def process_sql_file(input_file, output_file):
    with open(input_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Fix the INSERT header
    content = content.replace(
        "INSERT INTO `emp_vacation_balance` (`id`, `emp_id`, `vac_id`, `contract_id`, `period_start`, `period_end`, `total_days`, `used_days`, `remaining_balance`, `available_balance`, `carryover_days`, `created_at`, `last_updated`, `updated_at`) VALUES",
        "INSERT INTO `emp_vacation_balance` (`id`, `emp_id`, `vac_id`, `contract_id`, `period_start`, `period_end`, `total_days`, `used_days`, `remaining_balance`, `available_balance`, `opening_balance`, `carryover_days`, `created_at`, `last_updated`, `updated_at`) VALUES"
    )
    
    # Fix each data row - simpler approach: find lines starting with '(' and fix the balance section
    lines = content.split('\n')
    fixed_lines = []
    
    for line in lines:
        # Skip non-data lines
        if not line.strip().startswith('('):
            fixed_lines.append(line)
            continue
        
        # Pattern: find the 4 balance decimals + carryover decimal before either a quoted date or NULL
        # Format: total_days, used_days, remaining_balance, available_balance, carryover_days
        # We need to insert opening_balance (= available_balance) between available and carryover
        
        # Try pattern with quoted timestamp first
        match = re.search(r', (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), \'', line)
        if not match:
            # Try pattern with NULL
            match = re.search(r', (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), NULL', line)
        
        if match:
            total = match.group(1)
            used = match.group(2)
            remaining = match.group(3)
            available = match.group(4)
            carryover = match.group(5)
            
            # Check if opening_balance already exists (6 decimals instead of 5)
            check_pattern_quote = r', (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), \''
            check_pattern_null = r', (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), (\d+\.\d+), NULL'
            
            if re.search(check_pattern_quote, line) or re.search(check_pattern_null, line):
                # Already has opening_balance
                fixed_lines.append(line)
            else:
                # Insert opening_balance
                old_quote = f", {total}, {used}, {remaining}, {available}, {carryover}, '"
                new_quote = f", {total}, {used}, {remaining}, {available}, {available}, {carryover}, '"
                
                old_null = f", {total}, {used}, {remaining}, {available}, {carryover}, NULL"
                new_null = f", {total}, {used}, {remaining}, {available}, {available}, {carryover}, NULL"
                
                if old_quote in line:
                    fixed_lines.append(line.replace(old_quote, new_quote, 1))
                elif old_null in line:
                    fixed_lines.append(line.replace(old_null, new_null, 1))
                else:
                    fixed_lines.append(line)
        else:
            fixed_lines.append(line)
    
    content = '\n'.join(fixed_lines)
    
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"✓ Processed: {input_file} → {output_file}")
    print("  Added opening_balance column to emp_vacation_balance INSERTs")

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Usage: python add_opening_balance_to_dump.py input.sql output.sql")
        sys.exit(1)
    
    process_sql_file(sys.argv[1], sys.argv[2])
