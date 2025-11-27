## HR Operations Resignation Approval Wizard - UPDATED

### Overview
Updated the HR Operations approval workflow to display a 3-step wizard showing information provided by the direct manager, without asking HR to re-enter replacement data.

### New Workflow for HR Operations (Level 2)

```
Step 1: Employee & Resignation Information
├─ Employee Information (Read-only)
│  ├─ Employee ID
│  ├─ ID/Iqama
│  ├─ Employee Name
│  ├─ Designation
│  └─ Department
├─ Resignation Information
│  ├─ Last Workday by Employee (Read-only)
│  └─ Last Workday by HR (Editable input - optional)
└─ Buttons: NEXT / REJECT

        ↓ NEXT ↑ REJECT

Step 2: Exit Interview Questions
├─ Display all 9 exit interview Q&A
│  ├─ Q1: Main reasons for leaving
│  ├─ Q2: Felt supported by management
│  ├─ Q3: Had tools & resources
│  ├─ Q4: Manager's leadership style
│  ├─ Q5: Growth opportunities
│  ├─ Q6: Compensation & benefits
│  ├─ Q7: Wished different
│  ├─ Q8: Recommend company
│  └─ Q9: Additional comments
└─ Buttons: NEXT / BACK / REJECT

        ↓ NEXT ↑ BACK ↓ REJECT

Step 3: Replacement Information (from Direct Manager)
├─ Display YES/NO status
├─ If NO:
│  └─ "No replacement employee is needed for this position."
├─ If YES:
│  ├─ Replacement Job Details (Read-only):
│  ├─ 1. Job Title
│  ├─ 2. Job Description (with text formatting)
│  ├─ 3. Experience
│  ├─ 4. Certificate
│  ├─ 5. Academic Achievement
│  └─ 6. Date of Joining
└─ Buttons: APPROVE / BACK / REJECT
```

### Files Modified

#### 1. `assets/js/resignationApproval.js`

**Functions Updated/Added:**

- `showHRStep1EmployeeInfo(data)` - UPDATED
  - Shows employee info + resignation info with HR last working day input
  - Next/Reject buttons
  - Triggers Step 2 (Exit Interview)

- `showHRStep2ExitInterview(data)` - NEW
  - Displays all 9 exit interview questions with employee responses
  - Back/Next/Reject buttons
  - Fetches replacement data when moving to Step 3

- `fetchReplacementData(resignationId, callback)` - NEW
  - Calls backend to retrieve replacement data entered by direct manager
  - Data includes: needs_replacement flag + all 6 job specification fields

- `showHRStep3ReplacementSummary(data)` - NEW
  - Displays replacement information as READ-ONLY summary
  - Shows "YES" with all 6 fields, or "NO" with explanatory message
  - Back/Approve/Reject buttons
  - No data entry - HR only reviews what manager entered

**Removed Functions:**
- `showHRStep2ReplacementInfo()` - REMOVED (no longer asks HR to input)
- `showHRStep3ReplacementDetails()` - REMOVED (no longer asks HR to input)
- `showHRFinalApprovalConfirmation()` - REMOVED (merged into Step 3)

#### 2. `includes/ajaxFile/ajaxResignation.php`

**New Handler Added:**

- `get_replacement_data` - NEW endpoint
  - Fetches `needs_replacement` and `replacement_data` from `emp_resignations` table
  - Parses JSON from `replacement_data` field
  - Returns data structure: `{ needs_replacement: 0/1, job_title: "...", job_description: "...", ... }`

#### 3. `assets/css/style.css`

**New CSS Classes Added:**
- `.replacement-summary-view` - Container for replacement summary display
- `.replacement-summary-view .alert` - Alert styling (info/warning/secondary)
- `.replacement-summary-view .alert-info` - Blue alert for "YES" replacement
- `.replacement-summary-view .alert-warning` - Yellow alert for "NO" replacement
- `.replacement-summary-view .alert-secondary` - Gray alert for missing info
- `.replacement-details-card` - Card containing replacement job details
- `.replacement-details-card .card-title` - Title styling
- `.replacement-details-card .info-table` - Table layout for fields
- `.replacement-details-card .info-table tr td.value pre` - Preformatted text for job description

#### 4. `db_updates/translations_hr_operations_resignation.sql`

**New Translation Keys Added:**
- `replacement_job_details` - "Replacement Job Details" / "تفاصيل وظيفة الموظف البديل"
- `replacement_info_from_manager` - "Information provided by Direct Manager" / "المعلومات المقدمة من المدير المباشر"
- `no_replacement_needed` - "No replacement employee is needed..." / "لا توجد حاجة لموظف بديل..."
- `replacement_info_not_available` - "Replacement Information Not Available" / "معلومات الاستبدال غير متاحة"
- `no_replacement_info` - "Replacement information was not provided..." / "لم توفر معلومات الاستبدال..."

### Key Improvements

✅ **Simplified HR Workflow**
- HR no longer re-enters replacement data
- Focuses on review and approval only
- Cleaner user experience

✅ **Single Source of Truth**
- Manager enters replacement once
- HR reviews the same data
- Reduces data entry errors

✅ **Better Information Flow**
- Step 1: Review employee info
- Step 2: Review exit interview feedback
- Step 3: Review replacement decision
- Logical progression

✅ **Read-Only Display**
- All manager-entered data shown as read-only
- HR can only approve/reject, not modify
- Maintains data integrity

✅ **Flexible Display**
- Handles YES/NO replacement scenarios
- Shows detailed fields only if YES
- Handles missing data gracefully

### Data Flow

1. **Direct Manager** (Level 1):
   - Enters replacement info in Step 2
   - Data saved to `emp_resignations.replacement_data` (JSON)
   - Sets `needs_replacement` flag

2. **HR Operations** (Level 2):
   - Step 1: Views employee & resignation info
   - Step 2: Views exit interview Q&A
   - Step 3: Fetches manager's replacement data via `get_replacement_data`
   - Reviews and approves/rejects

3. **Backend** (`ajaxResignation.php`):
   - `get_replacement_data`: Retrieves and parses manager-entered data
   - `approve_resignation`: Processes approval (updates `request_approvers`)

### Database Queries

Backend fetches replacement data:
```sql
SELECT `needs_replacement`, `replacement_data` FROM `emp_resignations` 
WHERE `id` = {resignation_id} LIMIT 1
```

JSON structure in `replacement_data`:
```json
{
  "job_title": "Sales Manager",
  "job_description": "...",
  "experience": "5-7 years",
  "certificate": "...",
  "academic_achievement": "Bachelor degree",
  "date_of_joining": "2025-12-15"
}
```

### Translation Installation

```bash
mysql -h localhost -u root -p"admin123" almutlak_db < db_updates/translations_hr_operations_resignation.sql
```

### Testing Checklist

- [ ] HR Step 1 displays employee and resignation info correctly
- [ ] HR Step 1 "Next" button proceeds to Step 2
- [ ] HR Step 2 displays all 9 exit interview Q&A
- [ ] HR Step 2 "Next" button fetches replacement data and proceeds to Step 3
- [ ] HR Step 2 "Back" button returns to Step 1
- [ ] HR Step 3 shows replacement data as read-only (not editable)
- [ ] HR Step 3 displays YES status with all 6 fields when applicable
- [ ] HR Step 3 displays NO status with message when applicable
- [ ] HR Step 3 displays message when no replacement data found
- [ ] HR Step 3 "Approve" button submits approval
- [ ] HR Step 3 "Back" button returns to Step 2
- [ ] HR Step 3 "Reject" button prompts for rejection reason
- [ ] Reject action works from any step
- [ ] Translations display for EN and AR
- [ ] Job description with line breaks displays correctly in pre tag

### Notes

- No data modification by HR in Step 3 - review only
- Manager data preserved exactly as entered
- HR can set their own last working day (optional)
- Rejection available at any step
- All manager fields shown with proper formatting (pre-wrap for descriptions)
