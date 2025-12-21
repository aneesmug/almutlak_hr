# Employee Dropdown with Department Information

## ✅ What Was Updated

### 1. **AJAX Handler** (`ajaxGeneralRequest.php`)

Added LEFT JOIN with `department` table:

```php
SELECT e.emp_id, e.name, d.dep_nme as department 
FROM employees e
LEFT JOIN department d ON d.id = e.dept
WHERE e.status = 1
```

Now returns:
```json
{
  "results": [
    {
      "id": "EMP001",
      "text": "John Doe (EMP001) - HR",
      "name": "John Doe",
      "emp_id": "EMP001",
      "department": "HR"
    },
    ...
  ]
}
```

### 2. **Select2 Display** (`view_general_request.php`)

Enhanced with template functions:

**Dropdown List Shows:**
```
John Doe (EMP001) - HR
Jane Smith (EMP002) - Finance
Mike Johnson (EMP003) - IT
```

**Selected Item Shows:**
```
Name (ID) - Department
e.g., John Doe (EMP001) - HR
```

### 3. **Search Functionality**

Now searches across:
- ✅ Employee name
- ✅ Employee ID
- ✅ Department name

## 🎨 Display Format

### In Dropdown (While Typing)
```
┌─────────────────────────────────┐
│ John Doe (EMP001) - HR          │
│ Jane Smith (EMP002) - Finance   │
│ Mike Johnson (EMP003) - IT      │
└─────────────────────────────────┘
```

### Selected (After Choosing)
```
┌─────────────────────────────────┐
│ John Doe (EMP001) - HR          │
└─────────────────────────────────┘
```

## 🧪 Testing

1. Open an approved request
2. Scroll to "Mark Items Delivery" section
3. Click on "Who Received the Items?" field
4. Type to search:
   - By name: "john" → Shows matching employees
   - By ID: "EMP001" → Shows matching employees
   - By department: "HR" → Shows all HR employees

5. Department should display:
   - ✅ In dropdown next to name
   - ✅ In selected value
   - ✅ In search results

## 📊 Database Query

The system now executes:

```sql
SELECT e.emp_id, e.name, d.dep_nme as department 
FROM employees e
LEFT JOIN department d ON d.id = e.dept
WHERE e.status = 1
AND (e.name LIKE '%search%' 
     OR e.emp_id LIKE '%search%'
     OR d.dep_nme LIKE '%search%')
ORDER BY e.name 
LIMIT 50;
```

## 🔍 Search Examples

| Search Term | Shows |
|------------|-------|
| "john" | All employees named John |
| "EMP001" | Employee with ID EMP001 |
| "HR" | All HR department employees |
| "Finance" | All Finance department employees |
| Empty | All active employees (sorted by name) |

## 📋 Data Included

Each employee now includes:
- `id` - Employee ID (emp_id)
- `text` - Full display text with name, ID, and department
- `name` - Employee name
- `emp_id` - Employee ID (duplicate of id)
- `department` - Department name (or null if not assigned)

## ✨ Features

✅ **Department JOIN** - Left join ensures employees without dept still show
✅ **Search by Department** - Can search employees by their department
✅ **Clean Formatting** - Department shown with dash separator
✅ **Null Handling** - Handles employees without assigned department
✅ **Limited Results** - Returns max 50 employees to prevent performance issues
✅ **Sorted Results** - Results sorted by employee name for consistency

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Department showing blank | Employee record has no dept assigned in DB |
| Department not in list | Update employee's dept field in database |
| Search by dept not working | Check department.dep_nme column name |
| No results | Ensure status = 1 for employees |

## 📝 Related Files

- `ajaxGeneralRequest.php` - Modified (get_employees action)
- `view_general_request.php` - Modified (Select2 templates)
- `department` table - Used for LEFT JOIN
- `employees` table - Source of employee data

## ✅ Ready to Use

The dropdown now displays department information and works perfectly with the delivery tracking system!
