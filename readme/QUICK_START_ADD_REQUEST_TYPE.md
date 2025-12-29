# Quick Start Guide - Add New Request Type

## In 3 Steps 🚀

### Step 1️⃣ Navigate
1. Open **Application Settings** (app_seetings.php)
2. Click the **"Approval"** tab on the left sidebar

### Step 2️⃣ Create
1. Click the green **"Add New Request Type"** button (top-right)
2. Fill in:
   - **Request Type ID:** `travel_request` (lowercase, underscores only)
   - **Request Type Name:** `Travel Request`
   - **Description:** `Handles employee travel and business trip approvals` (optional)
3. Click **"Create"**

### Step 3️⃣ Configure
1. Your new type appears as a card
2. Click **"Add Approver"** to build the approval chain
3. Select roles and add them in sequence

---

## 📝 Example: Travel Request Type

| Field | Value |
|-------|-------|
| **Request Type ID** | `travel_request` |
| **Request Type Name** | Travel Request |
| **Description** | Employee travel approvals and expense management |

**Approval Chain Example:**
- Level 1: Direct Supervisor
- Level 2: HR Senior BP
- Level 3: Finance Manager
- Level 4: General Manager

---

## ✅ Validation Rules

### Request Type ID
```
✓ travel_request
✓ business_trip
✓ training_approval
✗ TravelRequest (uppercase)
✗ Travel-Request (hyphen)
✗ Travel Request (space)
```

---

## 🔄 What Happens Next?

After creation, your new request type:
1. ✅ Appears in the Approval Chain Configuration
2. ✅ Gets an empty approval chain
3. ✅ Can have approvers added
4. ✅ Is saved to the database
5. ✅ Persists on page refresh
6. ✅ Can be used in your application code

---

## 💡 Use Cases

Common custom request types you might create:

| Request Type | Description |
|--------------|-------------|
| `travel_request` | Travel and business trip approvals |
| `training_request` | Employee training programs |
| `equipment_request` | IT/office equipment procurement |
| `leave_special` | Special types of leave |
| `advance_salary` | Salary advance requests |
| `policy_exception` | Policy exception approvals |
| `vendor_approval` | Vendor onboarding |

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| Button not visible | Ensure you're logged in as Admin |
| ID not accepted | Use lowercase letters and underscores only |
| Type doesn't appear | Refresh the page |
| Error message appears | Check browser console for details |
| Can't add approvers | Create the type first, then add approvers |

---

## 📚 More Info

- Full documentation: [docs/APPROVAL_CHAIN_CUSTOM_REQUEST_TYPES.md](docs/APPROVAL_CHAIN_CUSTOM_REQUEST_TYPES.md)
- Technical details: [docs/APPROVAL_CHAIN_CONFIGURATION.md](docs/APPROVAL_CHAIN_CONFIGURATION.md)
- Implementation guide: [APPROVAL_CHAIN_IMPLEMENTATION.md](APPROVAL_CHAIN_IMPLEMENTATION.md)

---

**That's it! You can now create as many custom request types as you need!** 🎉
