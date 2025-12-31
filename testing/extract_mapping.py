#!/usr/bin/env python3
import re

# Read the SQL file
with open('sql/almutlak_hr_db_29122025_live.sql', 'r', encoding='utf8') as f:
    content = f.read()

# ===== EXTRACT NON-EMPLOYEE USERS FROM admin_login =====
# Find ALL INSERT statements for admin_login
admin_pattern = r'INSERT INTO `admin_login`[^;]*VALUES\s*\((.*?)\);'
admin_matches = list(re.finditer(admin_pattern, content, re.DOTALL))

non_emp_users = {}

for admin_match in admin_matches:
    values_str = admin_match.group(1)
    # Split records carefully by ),\n(
    records = re.split(r'\),\s*\(', values_str)
    
    for record in records:
        # Extract all fields (quoted strings and numbers)
        all_values = []
        for m in re.finditer(r"'([^']*)'|(\d+)|NULL", record):
            if m.group(1) is not None:
                all_values.append(m.group(1))
            elif m.group(2) is not None:
                all_values.append(m.group(2))
            else:
                all_values.append(None)
        
        # admin_login structure: id(0), emp_id(1), id_iqama(2), fullname(3), username(4), user_type(5), ...
        if len(all_values) >= 6:
            emp_id = all_values[1]  # emp_id
            user_type = all_values[5]  # user_type
            
            if user_type and user_type != 'employee' and emp_id:
                non_emp_users[emp_id] = user_type

print(f"Found {len(non_emp_users)} non-employee users from admin_login")
print("\n=== NON-EMPLOYEE USERS ===")
for emp_id in sorted(non_emp_users.keys()):
    print(f"  {emp_id}: {non_emp_users[emp_id]}")

# ===== EXTRACT EMPLOYEES COMP_NO MAPPING =====
emp_pattern = r'INSERT INTO `employees`[^;]*VALUES\s*\((.*?)\);'
emp_matches = list(re.finditer(emp_pattern, content, re.DOTALL))

emp_comp_map = {}

for emp_match in emp_matches:
    values_str = emp_match.group(1)
    # Split records
    records = re.split(r'\),\s*\(', values_str)
    
    for record in records:
        # Extract all field values  
        all_fields = []
        for m in re.finditer(r"'([^']*)'|(\d+(?:\.\d+)?)|NULL", record):
            if m.group(1) is not None:
                all_fields.append(m.group(1))
            elif m.group(2) is not None:
                all_fields.append(m.group(2))
            else:
                all_fields.append(None)
        
        # employees structure (from CREATE TABLE):
        # id(0), name(1), emp_id(2), iqama(3), iqama_exp(4), iqama_exp_g(5), mobile(6), 
        # passport_number(7), passport_exp(8), email(9), c_email(10), emg_mobile(11), 
        # emg_name(12), salary(13), dept(14), sectin_nme(15), emptype(16), supervisor_id(17), 
        # country(18), vacation_days(19), joining_date(20), fly(21), bank_name(22), iban(23), 
        # note(24), ter_note(25), ter_date(26), dob(27), dob_h(28), vac_period(29), sex(30), 
        # blood_type(31), actual_job(32), mar_status(33), t_shirt_size(34), emp_sup_type(35), 
        # comp_no(36) <-- THIS IS WHAT WE NEED
        if len(all_fields) >= 37:
            emp_id = all_fields[2]  # emp_id at position 2
            comp_no = all_fields[36]  # comp_no at position 36 (0-indexed)
            
            if emp_id and comp_no:
                emp_comp_map[emp_id] = comp_no

print(f"\nFound {len(emp_comp_map)} employee records with comp_no mapping")

# ===== MATCH NON-EMP USERS WITH THEIR COMPANY =====
print("\n=== MATCHING USERS WITH COMPANIES ===")
missing = []
matched = {}

for emp_id in sorted(non_emp_users.keys()):
    if emp_id in emp_comp_map:
        comp_no = emp_comp_map[emp_id]
        matched[emp_id] = comp_no
        print(f"  emp_id {emp_id}: comp_no {comp_no}")
    else:
        missing.append(emp_id)
        print(f"  emp_id {emp_id}: NOT FOUND IN EMPLOYEES TABLE")

print(f"\nMatched: {len(matched)}, Missing: {len(missing)}")

if missing:
    print("\nMissing emp_ids:")
    for emp_id in missing:
        print(f"  {emp_id}")

# ===== GENERATE UPDATE STATEMENTS =====
print("\n=== UPDATE STATEMENTS ===")
updates = []

for emp_id in sorted(matched.keys()):
    comp_no = matched[emp_id]
    sql = f"UPDATE `admin_login` SET `allowed_companies` = '[{comp_no}]' WHERE `emp_id` = '{emp_id}' AND `user_type` != 'employee';"
    updates.append(sql)
    print(sql)

# Write to file
with open('UPDATE_ADMIN_LOGIN_ALLOWED_COMPANIES_CORRECTED.sql', 'w') as f:
    f.write("-- CORRECTED: Updated statements with verified company assignments\n")
    f.write(f"-- Generated from emp_id to comp_no mapping\n")
    f.write(f"-- Total updates: {len(updates)}\n\n")
    for sql in updates:
        f.write(sql + "\n")

print(f"\nWrote {len(updates)} UPDATE statements to UPDATE_ADMIN_LOGIN_ALLOWED_COMPANIES_CORRECTED.sql")
