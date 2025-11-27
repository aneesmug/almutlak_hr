## HR Operations Resignation Approval Workflow Implementation

### Overview
Implemented a comprehensive multi-step approval wizard for HR Operations that shows complete employee resignation information, exit interview responses, and replacement requirements.

### Files Modified

#### 1. `assets/js/resignationApproval.js`
**Changes:**
- Modified `openResignationApprovalWizard()` to detect approval level and route to appropriate wizard
  - **Level 1 (Direct Supervisor)**: Skips to replacement information only
  - **Level 2 (HR Operations)**: Shows complete wizard with all information
  - **Level 3+ (HR Payroll)**: Shows summary wizard

**New Functions Added:**

- `fetchExitInterviewData(resignationId, callback)` - Retrieves exit interview responses from backend
- `showHRStep1EmployeeInfo(data)` - Displays employee info, resignation info, and exit interview Q&A
  - Shows 9 exit interview questions with employee responses
  - Shows employee's proposed last working day (read-only)
  - Provides input field for HR to set their own last working day
  - Next/Reject buttons

- `showHRStep2ReplacementInfo(data)` - Replacement information question
  - Radio buttons: No / Yes
  - Back/Next/Cancel buttons

- `showHRStep3ReplacementDetails(data)` - Replacement details form
  - 6 required fields:
    1. Job Title of the Replacement
    2. Job Description
    3. Experience
    4. Certificate
    5. Academic Achievement
    6. Date of Joining
  - Back/Approve/Reject buttons

- `showHRFinalApprovalConfirmation(data, replacementData)` - Final confirmation
  - Shows replacement requirement status
  - Back/Approve/Reject buttons

- `submitHRApproval(resignationId, replacementData)` - Submits approval to backend
  - Captures HR's last working day if provided
  - Includes replacement data if needed

#### 2. `includes/ajaxFile/ajaxResignation.php`
**Changes:**
- Fixed `get_approval_level` response format to match frontend expectations
  - Changed from `['type' => 'success', 'data' => [...]]`
  - To: `['success' => true, 'approval_level' => ...]`

- Updated `get_exit_interview` response format
  - Returns exit interview data as associative array with keys: q1_reasons, q2_support, q3_tools, etc.
  - Changed response to use `success: true` format

#### 3. `assets/css/style.css`
**New CSS Classes Added:**
- `.resignation-approval-wizard` - Main wrapper for wizard content
- `.resignation-approval-wizard.hr-operations-view` - HR-specific styling with max-height and scroll
- `.wizard-section` - Section dividers with bottom border
- `.section-title` - Section headings with icons
- `.info-table` - Information display table styling
- `.info-table .label` - Table labels (40% width)
- `.info-table .value` - Table values
- `.exit-interview-responses` - Container for exit interview Q&A
- `.question-response` - Individual question/answer styling with background
- `.resignation-wizard-popup` - SweetAlert2 popup customization

#### 4. `db_updates/translations_hr_operations_resignation.sql` (NEW)
**New Translation Keys Added:**
All keys with English and Arabic translations:
- Exit Interview: `exit_interview_questions`
- Last Working Day: `last_working_day_employee`, `last_working_day_hr`
- Replacement Fields: `job_title`, `job_description`, `experience`, `certificate`, `academic_achievement`, `date_of_joining`
- Form Placeholders: All associated placeholder translations
- Actions: `approve`, `reject`, `back`, `next`, `cancel`, `processing`, `please_wait`

### Workflow Flow

#### HR Operations (Level 2) Approval:
```
Step 1: Employee & Resignation Information
├─ Employee ID, ID/Iqama, Name, Designation, Department (Read-only)
├─ Last Workday by Employee (Read-only)
├─ Last Workday by HR (Input field - optional)
└─ Exit Interview Q&A (All 9 questions with responses)

      ↓ NEXT / ← BACK ↓ REJECT

Step 2: Replacement Information
├─ Do you need a replacement employee?
├─ ☐ NO
└─ ☐ YES

      ↓ NEXT / ← BACK ↓ CANCEL

Step 3 (If YES): Replacement Details
├─ 1. Job Title
├─ 2. Job Description
├─ 3. Experience
├─ 4. Certificate
├─ 5. Academic Achievement
├─ 6. Date of Joining

      ↓ APPROVE / ← BACK ↓ REJECT

FINAL: Approval Confirmation
└─ Confirm approval and submit

Step 3 (If NO): Final Confirmation
└─ Confirm approval without replacement needed
```

### Key Features

✅ **Role-Based Workflow**
- Direct Supervisors see only replacement information page
- HR Operations see complete wizard with all employee details and exit interview responses
- HR Payroll see summary view

✅ **Exit Interview Integration**
- Displays all 9 exit interview questions with employee responses
- Read-only view in approval wizard
- Shows employee feedback to decision makers

✅ **HR Control Over Last Working Day**
- Employee proposes date
- HR can override with their own date
- Flexible end date management

✅ **Replacement Requirement Tracking**
- Optional replacement needs identification
- Detailed replacement job posting form
- 6-field comprehensive job specification

✅ **Bilingual Support**
- All UI strings translated (English & Arabic)
- Translation SQL file provided
- Ready for RTL layout

### Database Integration

**Backend Handler:** `includes/ajaxFile/ajaxResignation.php`
- Existing endpoint: `get_approval_level` - Determines current user's approval level
- Existing endpoint: `get_exit_interview` - Retrieves exit interview responses
- Existing endpoint: `approve_resignation` - Handles approval submission

### SQL Translation Installation

Run this to add all new translation keys:
```bash
mysql -h localhost -u root -p"admin123" almutlak_db < db_updates/translations_hr_operations_resignation.sql
```

### Testing Checklist

- [ ] Direct Supervisor approval flow (skips to replacement info)
- [ ] HR Operations approval flow (shows all info including exit interview)
- [ ] Exit interview questions display correctly
- [ ] HR last working day input field works
- [ ] Replacement details form validates all fields
- [ ] Reject action works from any step
- [ ] Back navigation works correctly
- [ ] Translations display for EN and AR
- [ ] Approval submission updates database
- [ ] Browser notifications sent to relevant users

### Notes

- Exit interview field name mapping: `q1_reasons`, `q2_support`, `q3_tools`, `q4_manager`, `q5_growth`, `q6_compensation`, `q7_different`, `q8_recommend`, `q9_additional`
- HR last working day is stored in a separate field (to be configured in backend approval handler)
- All radio buttons and form inputs are properly validated before submission
