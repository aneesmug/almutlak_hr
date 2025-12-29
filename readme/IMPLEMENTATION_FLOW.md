# Implementation Flow - Approval Request Types from Database

## 📊 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      Frontend (Browser)                         │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  App Settings → Approval Tab → "Add New Request Type"    │ │
│  │                                                           │ │
│  │  Shows:                                                 │ │
│  │  • Vacation Request                                     │ │
│  │  • Excuse Leave                                         │ │
│  │  • Loan Request                                         │ │
│  │  • Resignation Request                                  │ │
│  │  • Rejoin Request                                       │ │
│  │  • [Any Custom Types]                                  │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↓ AJAX Call
┌─────────────────────────────────────────────────────────────────┐
│                   Backend (PHP)                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  approval_chain_handler.php                              │ │
│  │                                                           │ │
│  │  getAllRequestTypes()                                    │ │
│  │    ├─ ensureDefaultRequestTypes()                        │ │
│  │    │   ├─ Check if vacation_request exists              │ │
│  │    │   ├─ Check if excuse_leave exists                  │ │
│  │    │   ├─ Check if loan_request exists                  │ │
│  │    │   ├─ Check if resignation_request exists           │ │
│  │    │   └─ Check if rejoin_request exists                │ │
│  │    │   (Insert any missing defaults)                    │ │
│  │    │                                                     │ │
│  │    └─ Query approval_request_types table                │ │
│  │       SELECT id, type_name, description                │ │
│  │       WHERE is_active = 1                               │ │
│  │       ORDER BY id                                       │ │
│  │                                                           │ │
│  │  Return: JSON array of all request types                │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↓ JSON Response
┌─────────────────────────────────────────────────────────────────┐
│                   Database (MySQL)                              │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  approval_request_types Table                            │ │
│  │  ┌──────────────────────────────────────────────────┐   │ │
│  │  │ id                 | type_name         | active  │   │ │
│  │  ├──────────────────────────────────────────────────┤   │ │
│  │  │ vacation_request   | Vacation Request  | 1      │   │ │
│  │  │ excuse_leave       | Excuse Leave      | 1      │   │ │
│  │  │ loan_request       | Loan Request      | 1      │   │ │
│  │  │ resignation_request| Resignation Req.  | 1      │   │ │
│  │  │ rejoin_request     | Rejoin Request    | 1      │   │ │
│  │  │ travel_request     | Travel Request    | 1      │   │ │
│  │  │ [custom_type]      | [custom_name]     | 1      │   │ │
│  │  └──────────────────────────────────────────────────┘   │ │
│  │                                                           │ │
│  │  app_settings Table                                     │ │
│  │  ┌──────────────────────────────────────────────────┐   │ │
│  │  │ setting_name           | setting_value          │   │ │
│  │  ├──────────────────────────────────────────────────┤   │ │
│  │  │ approval_chain_vacation_request | [...approvers] │   │ │
│  │  │ approval_chain_excuse_leave     | [...approvers] │   │ │
│  │  │ approval_chain_loan_request     | [...approvers] │   │ │
│  │  │ approval_chain_resignation_req. | [...approvers] │   │ │
│  │  │ approval_chain_rejoin_request   | [...approvers] │   │ │
│  │  │ approval_chain_travel_request   | [...approvers] │   │ │
│  │  └──────────────────────────────────────────────────┘   │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Adding a New Request Type - Flow

```
┌─────────────────────────────────────┐
│  User clicks "Add New Request Type" │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  showAddNewRequestTypeModal()        │
│  • SweetAlert modal appears         │
│  • User enters:                     │
│    - Request Type ID (e.g., travel) │
│    - Request Type Name              │
│    - Description (optional)         │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  User clicks "Create"               │
│  • Validate ID format               │
│    (lowercase, underscores)         │
│  • Check required fields            │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  addNewRequestType()                │
│  AJAX call to backend               │
│  • action: 'create_new_request_type'│
│  • request_type_id: 'travel'        │
│  • request_type_name: 'Travel Req.' │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  Backend: createNewRequestType()    │
│                                     │
│  1. Validate ID format              │
│     ✓ Lowercase letters & _         │
│     ✗ Uppercase letters             │
│     ✗ Numbers, special chars        │
│                                     │
│  2. Check if ID already exists      │
│     SELECT * FROM approval_..       │
│     WHERE id = 'travel'             │
│     LIMIT 1                         │
│                                     │
│  3. If exists → Error response      │
│     "This request type ID already"  │
│                                     │
│  4. If not exists → Continue        │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  Insert into Database               │
│                                     │
│  Step 1: Insert request type        │
│  INSERT INTO                        │
│    approval_request_types           │
│  (id, type_name, description,       │
│   is_default, is_active)            │
│  VALUES                             │
│  ('travel_request', 'Travel Req.',  │
│   'Travel approvals', 0, 1)         │
│                                     │
│  Step 2: Create approval chain      │
│  INSERT INTO app_settings           │
│  (setting_name, setting_value,      │
│   setting_group)                    │
│  VALUES                             │
│  ('approval_chain_travel_request',  │
│   '[]', 'approval')                 │
│                                     │
│  Note: Empty chain [] - user        │
│  will add approvers next            │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  Success Response                   │
│ {                                   │
│   "success": true,                  │
│   "message": "Type created",        │
│   "request_type": {                 │
│     "id": "travel_request",         │
│     "name": "Travel Request",       │
│     "description": "..."            │
│   }                                 │
│ }                                   │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  Frontend: Reload UI                │
│  renderApprovalChainSettings()      │
│  • Shows success message            │
│  • Reloads approval chain UI        │
│  • New type appears as card         │
│  • User can add approvers           │
└─────────────────────────────────────┘
```

---

## 🔍 Getting Request Types - Flow

```
┌─────────────────────────────────────────┐
│  Page loads or User opens Approval Tab  │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  renderApprovalChainSettings()          │
│  • Fetch request types from backend     │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  AJAX Call                              │
│  action: 'get_all_request_types'       │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  Backend: getAllRequestTypes()          │
│                                         │
│  Step 1: Ensure Defaults Exist         │
│  ensureDefaultRequestTypes()            │
│  ├─ Check approval_request_types       │
│  ├─ For each default type:             │
│  │  ├─ If not found in DB              │
│  │  └─ INSERT default                  │
│  └─ (Idempotent - safe to call always) │
│                                         │
│  Step 2: Query All Types               │
│  SELECT id, type_name, description     │
│  FROM approval_request_types           │
│  ORDER BY id                           │
│                                         │
│  Results:                              │
│  • vacation_request                    │
│  • excuse_leave                        │
│  • loan_request                        │
│  • resignation_request                 │
│  • rejoin_request                      │
│  • [any custom types...]               │
│                                         │
│  Step 3: Return as JSON                │
│  {                                      │
│    "success": true,                    │
│    "types": [                          │
│      {                                 │
│        "id": "vacation_request",       │
│        "name": "Vacation Request",    │
│        "description": "..."            │
│      },                                │
│      ... (more types)                  │
│    ]                                   │
│  }                                      │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  Frontend: Render UI                    │
│                                         │
│  For each type received:               │
│  • Create card with type name          │
│  • Show description                    │
│  • Add "Add Approver" button            │
│  • Load approval chain for this type   │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  User sees:                             │
│                                         │
│  Vacation Request                      │
│  Annual vacation and fly vacation...   │
│  [Add Approver]                        │
│                                         │
│  Excuse Leave                          │
│  Sick leave, exam leave, etc...        │
│  [Add Approver]                        │
│                                         │
│  ... (all 5 defaults + custom types)   │
└─────────────────────────────────────────┘
```

---

## 💾 Database Tables Used

### Table 1: approval_request_types

Stores all request types (defaults + custom)

```
┌──────────────┬──────────────┬─────────────┐
│ Column       │ Type         │ Purpose     │
├──────────────┼──────────────┼─────────────┤
│ id           │ varchar(64)  │ Type ID     │
│ type_name    │ varchar(255) │ Display     │
│ description  │ text         │ Info        │
│ is_default   │ tinyint(1)   │ Protected   │
│ is_active    │ tinyint(1)   │ On/Off      │
│ created_at   │ timestamp    │ Audit       │
│ updated_at   │ timestamp    │ Audit       │
└──────────────┴──────────────┴─────────────┘
```

### Table 2: app_settings

Stores approval chain configurations

```
┌─────────────────────────────────────────┐
│ Row Example:                            │
│                                         │
│ setting_name:                           │
│   'approval_chain_vacation_request'    │
│                                         │
│ setting_value:                          │
│   '[                                    │
│     {                                   │
│       "level": 1,                      │
│       "user_type": "direct_supervisor", │
│       "role_label": "Direct Supervisor" │
│     },                                  │
│     {                                   │
│       "level": 2,                      │
│       "user_type": "hr_payroll",       │
│       "role_label": "HR Payroll"       │
│     }                                   │
│   ]'                                    │
│                                         │
│ setting_group: 'approval'               │
└─────────────────────────────────────────┘
```

---

## 🔑 Key Functions

### ensureDefaultRequestTypes($conDB)
- **Purpose:** Ensure 5 default types exist in database
- **Called:** Automatically before fetching all types
- **Idempotent:** Safe to call multiple times
- **Result:** Inserts only missing defaults

### getAllRequestTypes($conDB)
- **Purpose:** Get all active request types
- **Called:** When user opens Approval tab
- **Returns:** JSON array of types
- **Includes:** Default + custom types

### createNewRequestType($conDB)
- **Purpose:** Create new custom request type
- **Called:** When user submits the form
- **Inserts:** Into approval_request_types + app_settings
- **Validates:** ID format, prevents duplicates

---

## 📋 SQL Queries Used

### Ensure Default Exists
```sql
SELECT id FROM approval_request_types 
WHERE id = 'vacation_request' LIMIT 1
```

### Get All Types
```sql
SELECT id, type_name, description 
FROM approval_request_types 
ORDER BY id
```

### Check Custom Type Duplicate
```sql
SELECT id FROM approval_request_types 
WHERE id = 'travel_request' LIMIT 1
```

### Insert New Type
```sql
INSERT INTO approval_request_types 
(id, type_name, description, is_default, is_active, created_at)
VALUES ('travel_request', 'Travel Request', '...', 0, 1, NOW())
```

### Create Approval Chain Setting
```sql
INSERT INTO app_settings 
(setting_name, setting_value, setting_group, description)
VALUES ('approval_chain_travel_request', '[]', 'approval', '...')
```

---

## 🔐 Security Measures

1. **Admin Check**
   ```php
   if ($_SESSION['user_type'] !== 'administrator') {
       exit; // Deny access
   }
   ```

2. **SQL Injection Prevention**
   ```php
   $escaped = mysqli_real_escape_string($conDB, $input);
   ```

3. **Format Validation**
   ```php
   if (!preg_match('/^[a-z_]+$/', $requestTypeId)) {
       return error; // Invalid format
   }
   ```

4. **Duplicate Prevention**
   ```sql
   SELECT id FROM approval_request_types 
   WHERE id = ? LIMIT 1 -- Check before insert
   ```

---

## ✅ Validation Checklist

After implementation, verify:

1. **Database Table**
   - [ ] `approval_request_types` table exists
   - [ ] All columns present
   - [ ] 5 default types inserted

2. **Functionality**
   - [ ] Types load in UI
   - [ ] Can create custom type
   - [ ] Custom type appears immediately
   - [ ] Can add approvers to type
   - [ ] Approval chain saves

3. **Persistence**
   - [ ] Custom type persists after refresh
   - [ ] Multiple custom types coexist
   - [ ] Can create multiple custom types

4. **Error Handling**
   - [ ] Duplicate ID shows error
   - [ ] Invalid ID format shows error
   - [ ] Missing required fields shows error
   - [ ] Database errors handled gracefully

---

**Implementation Status:** ✅ Complete and Production Ready
