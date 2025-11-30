# Employee Profile Report - Visual Layout Guide

This document provides a visual representation of the enhanced employee profile report layout.

## Page Structure Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║                                                            ║ │
│  ║              PROFILE HEADER (Blue Gradient)               ║ │
│  ║                                                            ║ │
│  ║  [Avatar]  Employee Name             Employee ID  Status  ║ │
│  ║    Image   Job Position               #12345      Active  ║ │
│  ║            Department - Section                           ║ │
│  ║                                                            ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ┌─────────────┬──────────────┬──────────────┬──────────────┐  │
│  │  Joining    │    Age       │ Nationality  │   Status     │  │
│  │   Date      │    Years     │   Country    │   Active     │  │
│  │ [Calendar]  │ [Birthday]   │ [Flag]       │ [Badge]      │  │
│  └─────────────┴──────────────┴──────────────┴──────────────┘  │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      PERSONAL & EMPLOYMENT DETAILS                        ║ │
│  ╟────────────────────────┬─────────────────────────────────╢ │
│  ║  PERSONAL INFO         │  EMPLOYMENT INFO               ║ │
│  ║  ─────────────────     │  ──────────────────            ║ │
│  ║  Employee ID: #12345   │  Department: Sales             ║ │
│  ║  IQAMA ID: 123456789   │  Section: Regional             ║ │
│  ║  IQAMA Exp: 01/01/2026 │  Job Position: Manager         ║ │
│  ║  Passport: AS123456    │  Date Hired: 15/01/2020        ║ │
│  ║  Passport Exp: N/A     │  Working Period: 4 years 2 mo  ║ │
│  ║  DOB: 01/01/1985 (39y) │  Contract: 2 Years            ║ │
│  ║  Nationality: Saudi    │  Mobile: +966-123-4567         ║ │
│  ║                        │  Email: name@company.com        ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      FINANCIAL DETAILS                                    ║ │
│  ╟────────────────────────┬─────────────────────────────────╢ │
│  ║  SALARY BREAKDOWN      │  BANK & INSURANCE              ║ │
│  ║  ──────────────────    │  ─────────────────             ║ │
│  ║  Basic Salary: 5,000   │  Bank: NCB Saudi               ║ │
│  ║  Housing: 1,500        │  IBAN: SA12-1234-5678...       ║ │
│  ║  Transport: 1,000      │  GOSI No: 987654321            ║ │
│  ║  Food: 500             │  GOSI Payment: 450             ║ │
│  ║  Fuel: 300             │  Insurance No: INS123456        ║ │
│  ║  Telephone: 100        │  Insurance Class: A             ║ │
│  ║  Other: 200            │  Insurance Exp: 01/01/2026      ║ │
│  ║  ──────────────────    │                                 ║ │
│  ║  TOTAL: 8,600 SAR      │                                 ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      ASSIGNED ASSETS                                      ║ │
│  ╟────────────────────────┬─────────────────────────────────╢ │
│  ║  COMPANY CAR           │  OTHER ASSETS                   ║ │
│  ║  ───────────────       │  ─────────────                  ║ │
│  ║  Maker/Model: BMW 3    │  Laptop - LT001 - 01/01/2020    ║ │
│  ║  Year: 2020            │  Phone - PH002 - 15/02/2020     ║ │
│  ║  Plate: SA-1234        │  Tablet - TB003 - 01/03/2020    ║ │
│  ║  Receive: 01/01/2020   │                                 ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      LOAN HISTORY                                         ║ │
│  ╟──────────┬────────┬────────┬──────────┬──────────┬────────╢ │
│  ║ Amount   │ Deduct │ Balance│ Start    │ End      │ Status ║ │
│  ╠──────────┼────────┼────────┼──────────┼──────────┼────────╣ │
│  ║ 5,000    │ 500    │ 2,000  │ 01/01/20 │ 01/01/21 │ PAID   ║ │
│  ║ 3,000    │ 300    │ 1,200  │ 15/02/20 │ 15/02/21 │ ACTIVE ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      VACATION HISTORY                                     ║ │
│  ╟────────┬─────────┬─────────┬────┬────────┬──────┬────────╢ │
│  ║ Type   │ Start   │ Return  │Days│ Permit │Status│ Arrived║ │
│  ╠────────┼─────────┼─────────┼────┼────────┼──────┼────────╣ │
│  ║ Annual │ 01/06/20│ 15/06/20│ 15 │ P-1234 │ APPR │ 15/06 ║ │
│  ║ Sick   │ 01/01/21│ 03/01/21│ 3  │ P-1235 │ COMP │ 03/01 ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      VACATION BALANCE SUMMARY                             ║ │
│  ┌──────────────┬──────────────┬──────────────┬──────────────┐ │
│  │  Allocated   │     Used     │  Carried     │   Balance    │ │
│  │     20       │      18      │      5       │      7       │ │
│  │    Days      │     Days     │    Days      │     Days     │ │
│  └──────────────┴──────────────┴──────────────┴──────────────┘ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      PROFESSIONAL PROFILES                                ║ │
│  ╟────────────────────────┬─────────────────────────────────╢ │
│  ║  SOCIAL MEDIA          │  PORTFOLIO                      ║ │
│  ║  ──────────────        │  ──────────                     ║ │
│  ║  LinkedIn: linkedin... │  Skills: Management, Sales      ║ │
│  ║  Twitter: @username    │  Certifications: PMP, SIX SIGMA ║ │
│  ║  Facebook: profile...  │  Experience: 10 years IT        ║ │
│  ║                        │  Awards: Employee of Year 2023  ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      END OF SERVICE (if applicable)                       ║ │
│  ╟────────────────────────┬─────────────────────────────────╢ │
│  ║  Resignation: 01/01/24 │  EOS Amount: 25,000 SAR         ║ │
│  ║  Last Working: 31/01/24│  Final Settlement: 25,000 SAR   ║ │
│  ║  Reason: Personal      │  Status: SETTLED                ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      EMPLOYEE NOTES                                       ║ │
│  ╟────────────────┬───────────────────────────────────────────╢ │
│  ║ Date           │ Note                                      ║ │
│  ╠────────────────┼───────────────────────────────────────────╣ │
│  ║ 15/01/2024     │ Promoted to Senior Manager               ║ │
│  ║ 01/12/2023     │ Completed leadership training            ║ │
│  ║ 15/06/2023     │ Excellent performance review             ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
│  ╔════════════════════════════════════════════════════════════╗ │
│  ║      EMPLOYEE DOCUMENTS                                   ║ │
│  ║  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ║ │
│  ║  │   PDF    │  │  EXCEL   │  │  IMAGE   │  │  CERT    │  ║ │
│  ║  │ Resume   │  │Payslips  │  │ Photo    │  │Degree    │  ║ │
│  ║  │15/01/2024│  │15/01/2024│  │15/01/2024│  │15/01/2024│  ║ │
│  ║  └──────────┘  └──────────┘  └──────────┘  └──────────┘  ║ │
│  ╚════════════════════════════════════════════════════════════╝ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

Print Page: A4 (210mm × 297mm)
Layout: Single Column
Status: Optimized for single-page printing
```

## Color Legend

| Color | Use | Hex Value |
|-------|-----|-----------|
| 🔵 Blue Gradient | Primary header, profile section | #007bff → #17a2b8 |
| 🟢 Green | Active status, success | #27ae60 |
| 🔴 Red | Terminated status, critical | #e74c3c |
| 🟡 Yellow | Vacation, pending, warning | #f7b731 |
| ⚫ Dark Gray | Headers, table borders | #333, #999 |
| ⚪ Light Gray | Background, alt rows | #f8f9fa, #f9f9f9 |

## Section Breakdown

### 1. Profile Header (Blue Gradient)
- **Height**: ~140px
- **Layout**: Flexbox with 4 columns
- **Content**: Avatar, Name/Title, Employee ID, Status Badge
- **Colors**: White text on blue gradient
- **Print**: Converts to light gray background

### 2. Quick Stats Dashboard
- **Height**: ~100px
- **Layout**: 4 responsive columns
- **Items**: Joining Date, Age, Nationality, Status
- **Colors**: Different gradients for each stat box
- **Print**: Maintains color differentiation

### 3. Personal & Employment Details
- **Height**: Variable (~250-350px)
- **Layout**: 2-column responsive grid
- **Left Column**: Personal information
- **Right Column**: Employment information
- **Font**: Small tables with consistent styling

### 4. Financial Details
- **Height**: Variable (~300px)
- **Layout**: 2-column responsive grid
- **Left Column**: Salary breakdown (9-10 items)
- **Right Column**: Bank and insurance info (7 items)
- **Styling**: Highlighted total row

### 5. Assigned Assets (if available)
- **Height**: Variable (~150-250px)
- **Layout**: 2-column (Car + Other Assets)
- **Content**: Car details and assigned equipment table
- **Display**: Only if employee has assets

### 6. Loan History (if available)
- **Height**: Variable (~50px per row + header)
- **Layout**: Full-width table
- **Columns**: 7 columns with badges
- **Styling**: Color-coded balance and status

### 7. Vacation History (if available)
- **Height**: Variable (~50px per row + header)
- **Layout**: Full-width table
- **Columns**: 7 columns
- **Styling**: Standard table styling

### 8. Vacation Balance Summary (if available)
- **Height**: ~120px
- **Layout**: 4-column stat display (similar to quick stats)
- **Content**: Allocated, Used, Carried Over, Balance
- **Colors**: Info, Warning, Secondary, Success

### 9. Professional Profiles (if available)
- **Height**: Variable (~150-250px)
- **Layout**: 2-column (Social Media + Portfolio)
- **Content**: Social links and portfolio info
- **Display**: Only if data exists

### 10. End of Service (if applicable)
- **Height**: Variable (~120-150px)
- **Layout**: 2-column
- **Border**: Red border to indicate importance
- **Colors**: Red text for header
- **Display**: Only if EOS record exists

### 11. Employee Notes (if available)
- **Height**: Variable (~50px per note + header)
- **Layout**: Full-width table
- **Columns**: 2 columns (Date + Note)
- **Sorting**: Newest first

### 12. Employee Documents (if available)
- **Height**: Variable (~120px per row)
- **Layout**: Grid of document cards
- **Responsive**: 4 per row (desktop), 2 per row (tablet), 1 per row (mobile)
- **Content**: File icon/thumbnail + document type + date

## Responsive Behavior

### Desktop (≥1200px)
```
Profile Header (full width)
Stat Dashboard (4 columns)
Personal & Employment (2 columns)
Financial Details (2 columns)
Assets (2 columns if available)
Loans (full width table)
Vacations (full width table)
Vacation Balance (4 columns)
Professional Profiles (2 columns if available)
End of Service (2 columns if applicable)
Notes (full width table)
Documents (4 per row)
```

### Tablet (768px - 991px)
```
Profile Header (full width)
Stat Dashboard (2 columns)
Personal & Employment (2 columns → 1 column)
Financial Details (2 columns → 1 column)
Assets (2 columns → 1 column)
Loans (scrollable table)
Vacations (scrollable table)
Vacation Balance (2 columns)
Professional Profiles (1 column)
End of Service (1 column)
Notes (scrollable table)
Documents (2 per row)
```

### Mobile (<768px)
```
Profile Header (full width, smaller)
Stat Dashboard (1 column)
Personal & Employment (1 column)
Financial Details (1 column)
Assets (1 column)
Loans (horizontal scroll)
Vacations (horizontal scroll)
Vacation Balance (1 column)
Professional Profiles (1 column)
End of Service (1 column)
Notes (1 column)
Documents (1 per row)
```

## Print Layout

### Paper
- **Size**: A4 (210mm × 297mm)
- **Margins**: Minimal (0.5" recommended)
- **Orientation**: Portrait
- **Color**: Color (for status badges)

### Font Sizes (Print Mode)
- **Body**: 11px
- **Table**: 10px
- **Headers**: 12-14px
- **Titles**: 15-16px

### Page Break Behavior
- **Page Break Inside**: Avoided for all `.card-box` elements
- **Section Margins**: Reduced to 0.75rem
- **Table Margins**: Reduced to 0.5rem
- **Expected Pages**: 1-2 (depending on employee data)

### Color Preservation
- **Print Color Adjust**: `exact` for all colored elements
- **Gradient Sections**: Convert to light gray
- **Badges**: Maintain exact color
- **Table Headers**: Black background with white text

## Common Print Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| Content split between pages | Page breaks in middle of section | Enable "page-break-inside: avoid" |
| Colors not printing | Print settings | Enable "Background graphics" |
| Text overlapping | Font too large | Reduce print font size to 11px |
| Margins too large | Printer settings | Set margins to 0.5" or minimum |
| Tables not fitting | Column widths | Enable "Scale to fit" or landscape |

## Accessibility Features

- **Semantic HTML**: Proper heading hierarchy (h2, h3, h4, h5)
- **Color Contrast**: All text meets WCAG AA standards
- **Table Structure**: Proper thead/tbody with th elements
- **Labels**: All inputs have associated labels
- **Badges**: Include text (not just color)
- **Images**: All images have alt text
- **Text Scaling**: Readable at 200% zoom

## Mobile-First Approach

All breakpoints use mobile-first CSS:
1. Base styles for mobile (< 768px)
2. Tablet breakpoint: 768px
3. Desktop breakpoint: 992px
4. Large desktop: 1200px

Each breakpoint uses `@media (min-width: Xpx)` for progressive enhancement.

---

**Document Version**: 1.0
**Last Updated**: [Current Date]
**Status**: Complete
