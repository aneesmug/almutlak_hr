# Employee Rejoin Approval System - Visual Guide

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                   EMPLOYEE REJOIN SYSTEM                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  EMPLOYEE SIDE                 │         SUPERVISOR SIDE        │
│  ───────────────────           │         ────────────────       │
│                                │                                │
│  1. Employee on Vacation       │                                │
│     (fly = 1)                  │                                │
│         │                      │                                │
│         ├─► Click "Rejoin"     │                                │
│         │      Button          │                                │
│         │         │            │                                │
│         └────────►│◄───────────┼── Supervisor Notified         │
│                   │            │  (rejoin_notifications)       │
│              Modal Opens       │                                │
│              (Date Picker)     │                                │
│                   │            │                                │
│         2. Select Date         │                                │
│            (±3 days max        │   3. Check Dashboard          │
│            from planned)       │    (rejoin_approvals.php)     │
│                   │            │         │                     │
│         3. Add Reason          │         ├─► Review Request    │
│            (optional)          │         │                     │
│                   │            │   4. Make Decision:           │
│         4. Submit              │      ┌─────────────┐          │
│            Request             │      │             │          │
│                   │            │      ▼             ▼          │
│  ┌────────────────┴──────────┬─┼──────────────────────┐        │
│  │                           │ │                      │        │
│  │   rejoin_requests         │ │  ACTIONS             │        │
│  │   (status=pending)        │ │  ──────────         │        │
│  │                           │ │  ✓ Approve         │        │
│  └───────────────────────────┼─┤  ✓ Adjust (±3d)   │        │
│        REQUEST LOGGED        │ │  ✓ Reject         │        │
│                              │ │                      │        │
│                              │ └──────────────────────┘        │
│                              │                                │
│                              ▼                                │
│  ┌───────────────────────────────────────────────┐           │
│  │  DECISION OUTCOME                             │           │
│  ├───────────────────────────────────────────────┤           │
│  │                                               │           │
│  │  APPROVED:                                    │           │
│  │  └─ Rejoin date locked                       │           │
│  │     Date saved to rejoin_final_date          │           │
│  │     Status: approved                         │           │
│  │                                               │           │
│  │  ADJUSTED (±3 days):                         │           │
│  │  └─ Employee can change date within window  │           │
│  │     Window: requested±3 days                │           │
│  │     Status: adjusted                        │           │
│  │     ▼                                       │           │
│  │     Employee selects final date             │           │
│  │     ▼                                       │           │
│  │     Status changed to: approved             │           │
│  │                                               │           │
│  │  REJECTED:                                    │           │
│  │  └─ Request denied with reason              │           │
│  │     HR involvement required                 │           │
│  │     Employee resubmits after resolution     │           │
│  │                                               │           │
│  └───────────────────────────────────────────────┘           │
│                              │                                │
│                              ▼                                │
│                    UPDATE emp_vacation TABLE                 │
│                    ├─ rejoin_final_date                      │
│                    ├─ rejoin_request_status                  │
│                    ├─ rejoin_final_confirmed_at              │
│                    └─ Other status fields                    │
│                                                              │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│ EMPLOYEE INITIATES REJOIN                                      │
└──────────────────────┬───────────────────────────────────────────┘
                       │
                       ▼
            ┌──────────────────────┐
            │ submitRejoinRequest()│
            │ (frontend function)  │
            └─────────┬────────────┘
                      │
         ┌────────────┴────────────┐
         │ Validate Input:         │
         │ • Date format OK?       │
         │ • Date ≤ planned+3 day? │
         │ • Vacation ID valid?    │
         └────────────┬────────────┘
                      │ (if validation passes)
                      ▼
         ┌────────────────────────┐
         │ submitRejoinAjax()     │
         │ Send to AJAX endpoint  │
         └─────────┬──────────────┘
                   │
      URL: ajaxVacation.php
      Type: submitRejoinRequest
                   │
                   ▼
    ┌──────────────────────────────────┐
    │ ajaxType = 'submitRejoinRequest' │
    │ (Backend AJAX Handler)           │
    └─────────┬──────────────────────┘
              │
      ┌───────┴───────┐
      │ Database      │
      │ Operations:   │
      │               │
      ├─ INSERT INTO  │
      │   rejoin_     │
      │   requests    │
      │               │
      ├─ UPDATE emp_  │
      │   vacation    │
      │               │
      └─ INSERT INTO  │
         rejoin_      │
         notifications│
              │
              ▼
    ┌──────────────────────────────────┐
    │ Return JSON Response             │
    │ status: 'success'                │
    │ message: 'Request submitted'    │
    └──────────────────────────────────┘
              │
              ▼
    ┌──────────────────────────────────┐
    │ Frontend Receives Response        │
    │ • Show success alert             │
    │ • Refresh page                   │
    └──────────────────────────────────┘
```

## Supervisor Approval Flow

```
┌─────────────────────────────────────────────────────┐
│ SUPERVISOR REVIEWS REQUEST                          │
└────────────────────┬────────────────────────────────┘
                     │
                     ▼
         ┌───────────────────────┐
         │ rejoin_approvals.php  │
         │ (Dashboard)           │
         └─────────┬─────────────┘
                   │
         ┌─────────┴──────────┐
         │ Load Pending       │
         │ Requests via API:  │
         │ get_rejoin_        │
         │ requests.php       │
         └─────────┬──────────┘
                   │
                   ▼
     ┌──────────────────────────┐
     │ Display in Table:        │
     │ • Employee Name          │
     │ • Requested Date         │
     │ • Planned Date           │
     │ • Reason                 │
     │ • Review Button          │
     └────────┬─────────────────┘
              │
              ▼
    ┌──────────────────────────┐
    │ Supervisor Clicks Review │
    └────────┬─────────────────┘
             │
    approveRejoinRequest()
             │
             ▼
    ┌────────────────────────────┐
    │ Modal Opens:               │
    │ ┌──────────────────────┐   │
    │ │ Rejoin Date: ...     │   │
    │ │ Action:              │   │
    │ │ ○ Approve            │   │
    │ │ ○ Adjust (±3 days)   │   │
    │ │ ○ Reject             │   │
    │ │                      │   │
    │ │ Approval Note: _____ │   │
    │ │ [Submit]             │   │
    │ └──────────────────────┘   │
    └────────┬─────────────────┘
             │
    ┌────────┴────────┬──────────────┐
    │                 │              │
    ▼                 ▼              ▼
  APPROVE          ADJUST          REJECT
    │                 │              │
    ├─ Lock date      ├─ Set Window  ├─ Record
    │  (±0 days)      │  (±3 days)   │  Reason
    │                 │              │
    ├─ Status:        ├─ Status:     ├─ Status:
    │  approved       │  adjusted    │  rejected
    │                 │              │
    └─►processRejoin  └─►processRejoin└─►processRejoin
       Approval()        Approval()      Approval()
       │                 │              │
       └─────────┬───────┴──────┬───────┘
                 │              │
            AJAX Handler:        │
            ajaxType =           │
            'processRejoin       │
             Approval'           │
                 │               │
                 ▼               │
         ┌──────────────────┐    │
         │ Update Database: │    │
         │ • rejoin_        │    │
         │   requests       │    │
         │ • emp_vacation   │    │
         │ • Audit log      │    │
         │                  │    │
         │ status = ...     │    │
         │ updated_by = ..  │    │
         │ timestamp = ...  │    │
         └────────┬─────────┘    │
                  │              │
                  └──────┬───────┘
                         │
                         ▼
            ┌──────────────────────┐
            │ Return to Supervisor │
            │ & Employee via Email │
            │ (Future: Email       │
            │  notifications)      │
            └──────────────────────┘
```

## Database Entity Relationship

```
┌──────────────────────┐
│   employees          │
├──────────────────────┤
│ emp_id (PK)         │
│ name                │
│ reports_to          │◄──┐ (Supervisor)
│ dept                │   │
│ status              │   │
│ ...                 │   │
└────────┬────────────┘   │
         │                │
         │ 1:M            │
         │                │
         ▼                │
    ┌──────────────────────────┐
    │  emp_vacation            │
    ├──────────────────────────┤
    │ id (PK)                 │
    │ emp_id (FK)◄────────────┤
    │ date (start)            │
    │ return_date (planned)   │
    │ fly                     │
    │ rejoin_request_status   │◄───┐
    │ rejoin_requested_date   │    │
    │ rejoin_requested_at     │    │ 1:1
    │ rejoin_approved_date    │    │
    │ rejoin_approved_by      │◄──┐│
    │ rejoin_approved_at      │   ││
    │ rejoin_adjustment_...   │   ││
    │ rejoin_final_date       │   ││
    │ rejoin_final_confirmed  │   ││
    │ ...                     │   ││
    └────────┬────────────────┘   ││
             │                    ││
             │ 1:M                ││
             │                    ││
             ▼                    ││
    ┌──────────────────────────┐  ││
    │ rejoin_requests          │  ││
    ├──────────────────────────┤  ││
    │ id (PK)                 │  ││
    │ vacation_id (FK)────────┼──┘│
    │ emp_id (FK)────────────┬┼───┘
    │ requested_rejoin_date   │
    │ requested_reason        │
    │ requested_by_emp_id     │
    │ requested_at            │
    │                         │
    │ status                  │
    │ approved_by_emp_id      │
    │ approved_at             │
    │ approval_note           │
    │                         │
    │ rejection_reason        │
    │                         │
    │ adjustment_allowed      │
    │ adjustment_from_date    │
    │ adjustment_to_date      │
    │ adjustment_reason_text  │
    │ adjustment_submitted_at │
    │                         │
    │ final_approved_date     │
    │ final_approved_at       │
    └────────┬────────────────┘
             │
             │ 1:M
             │
             ▼
    ┌──────────────────────────┐
    │ rejoin_notifications     │
    ├──────────────────────────┤
    │ id (PK)                 │
    │ rejoin_request_id (FK)  │
    │ emp_id (FK)             │
    │ supervisor_emp_id (FK)  │
    │ notification_type       │
    │ is_read                 │
    │ read_at                 │
    │ created_at              │
    └──────────────────────────┘

Legend:
PK = Primary Key
FK = Foreign Key
1:M = One-to-Many
1:1 = One-to-One
```

## Status State Machine

```
                    ┌──────────────┐
                    │   START      │
                    │ (No Request) │
                    └───────┬──────┘
                            │
                   Employee Submits
                      Request
                            │
                            ▼
                    ┌──────────────┐
                    │   PENDING    │◄─────┐
                    │              │      │
                    │ Awaiting     │      │
                    │ Supervisor   │      │
                    │ Review       │      │
                    └───┬────┬───┬─┘      │
                        │    │   │       │
                        │    │   └───────┼─── HR Override (rare)
        ┌───────────────┘    │           │
        │                    │
    Approve           Adjust  │
        │                │    │
        │                │    Reject
        │                │    │
        ▼                ▼    ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │ APPROVED     │  │ ADJUSTED     │  │ REJECTED     │
    │              │  │              │  │              │
    │ Date locked  │  │ Employee can │  │ Needs HR     │
    │              │  │ change ±3 d  │  │ Resolution   │
    │ Status: OK   │  │              │  │              │
    │              │  │ Waiting for  │  │ Status:      │
    └──────────────┘  │ Employee     │  │ Blocked      │
         │            │              │  │              │
         │            └───────┬───────┘  └──────┬───────┘
         │                    │                 │
         │            Employee Selects    HR Resolves
         │                  Date               Issue
         │                    │                 │
         │                    ▼                 │
         │            ┌──────────────┐          │
         │            │ APPROVED     │          │
         │            │ (after adj)  │          │
         │            │              │          │
         │            │ Final date   │          │
         │            │ locked       │          │
         │            └──────────────┘          │
         │                    │                 │
         └────────┬───────────┘                 │
                  │                            │
            Process Payroll                    │
            (rejoin_final_date                 │
             used for salary calc)             │
                  │                            │
                  ▼                            │
            ┌──────────────┐                   │
            │  COMPLETED   │◄──────────────────┘
            │              │
            │ Rejoin       │
            │ Processed    │
            └──────────────┘
```

## Timeline Example

```
December 2024

EMP Employee on vacation
E----E----|----E---------------E  (Vacation Period)
          |     
    Plan. Return Date: Dec 13
          |
          Dec 12 ◄───► Employee requests rejoin for Dec 15
               (1 day before)
               
          │
          ▼ Dec 12, 2:00 PM
     ┌─────────────────────────┐
     │ Employee Submits:       │
     │ Rejoin Date: Dec 15     │
     │ Reason: Traffic delay   │
     └─────────────────────────┘
          │
          ▼ Notification Sent
     ┌─────────────────────────┐
     │ Supervisor Notified     │
     │ (rejoin_notifications)  │
     └─────────────────────────┘
          │
          ▼ Dec 12, 3:30 PM
     ┌─────────────────────────┐
     │ Supervisor Reviews:     │
     │ Planned: Dec 13         │
     │ Requested: Dec 15 (+2)  │
     │ Reason: Traffic delay   │
     │                         │
     │ Decision: ADJUST        │
     │ (Allow ±3 days)         │
     └─────────────────────────┘
          │
          ▼ Dec 13 (Morning)
     ┌─────────────────────────┐
     │ Employee Notified:      │
     │ Can adjust Dec 12-18    │
     │ (±3 days from Dec 15)   │
     └─────────────────────────┘
          │
          ▼ Dec 13, 10:00 AM
     ┌─────────────────────────┐
     │ Employee Adjusts:       │
     │ New Date: Dec 14        │
     │ (Changed mind, back early)
     └─────────────────────────┘
          │
          ▼ Status: APPROVED
     ┌─────────────────────────┐
     │ Rejoin Date Locked:     │
     │ Dec 14, 2024            │
     │                         │
     │ Saved to:               │
     │ rejoin_final_date       │
     └─────────────────────────┘
          │
          ▼ Dec 14
     ┌─────────────────────────┐
     │ Employee Rejoins Work   │
     │ Payroll processes with  │
     │ Dec 14 as final date    │
     │ (not planned Dec 13)    │
     └─────────────────────────┘
```

## Module Interaction

```
┌──────────────────────────────────────────────────────────┐
│                 REJOIN APPROVAL SYSTEM                   │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  User Interface Layer                                   │
│  ─────────────────────────────────────────────────     │
│  ┌───────────────────┐          ┌──────────────────┐  │
│  │ view_employee.php │          │rejoin_approv.php │  │
│  │ (Employee Side)   │          │(Supervisor Side) │  │
│  │                   │          │                  │  │
│  │ submitRejoin..()  │          │ View Dashboard   │  │
│  │ approveRejoin.()  │          │ Review Request   │  │
│  │ processRejoin..() │          │ Make Decision    │  │
│  └────────┬──────────┘          └────────┬─────────┘  │
│           │                             │              │
│           │ AJAX Calls                 │              │
│           │                             │              │
└───────────┼─────────────────────────────┼──────────────┘
            │                             │
┌───────────┼─────────────────────────────┼──────────────┐
│  API/AJAX Layer                                        │
│  ────────────────────────────────────────────────     │
│           │                             │              │
│           ▼                             ▼              │
│  ┌──────────────────────────┐  ┌──────────────────┐  │
│  │   ajaxVacation.php       │  │ get_rejoin_      │  │
│  │   (AJAX Handlers)        │  │ requests.php     │  │
│  │                          │  │ (Get API)        │  │
│  │ • submitRejoinRequest    │  │                  │  │
│  │ • processRejoinApproval  │  │ Returns:         │  │
│  │ • submitAdjustedRejoin   │  │ • Pending        │  │
│  │                          │  │ • Approved       │  │
│  │ (Process & Validate)     │  │ • Rejected       │  │
│  └────────┬─────────────────┘  └────────┬─────────┘  │
│           │                            │              │
│           │ Database Operations        │              │
│           │                            │              │
└───────────┼────────────────────────────┼──────────────┘
            │                            │
┌───────────┼────────────────────────────┼──────────────┐
│ Database Layer                                         │
│ ──────────────────────────────────────────────────   │
│          │                            │               │
│          ▼                            ▼               │
│  ┌──────────────────┐      ┌──────────────────┐      │
│  │ rejoin_requests  │      │ emp_vacation     │      │
│  │                  │      │                  │      │
│  │ • id             │      │ • id             │      │
│  │ • emp_id         │      │ • emp_id         │      │
│  │ • vacation_id    │      │ • rejoin_*       │      │
│  │ • requested_date │      │   (rejoin cols)  │      │
│  │ • status         │      │                  │      │
│  │ • approved_by    │      │ (All vacation    │      │
│  │ • approval_note  │      │  data + rejoin)  │      │
│  │ • ...            │      │                  │      │
│  └──────────────────┘      └──────────────────┘      │
│          │                            │               │
│          └────────────────┬───────────┘               │
│                           │                          │
│                   ┌──────────────────┐               │
│                   │ rejoin_notif.    │               │
│                   │                  │               │
│                   │ • rejoin_req_id  │               │
│                   │ • supervisor_id  │               │
│                   │ • is_read        │               │
│                   │ • created_at     │               │
│                   └──────────────────┘               │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

**Document Version**: 1.0
**Created**: December 2025
**Purpose**: Visual reference for system architecture and workflows
