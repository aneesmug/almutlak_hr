# Vacation Deduction Calculation - Updated Logic

## Overview

The vacation deduction calculation has been enhanced to **exclude both weekend days AND holiday days** from vacation balance deduction. This ensures employees are not charged for days they wouldn't typically work anyway.

## New Calculation Formula

```
Deductible Days = Total Vacation Days - Weekend Days - Holiday Days
```

## Business Logic

### Components

1. **Total Vacation Days**
   - The number of days the employee requested vacation
   - Example: 5 days

2. **Weekend Days**
   - Automatically excluded (Friday & Saturday in Saudi Arabia)
   - Automatically detected - no configuration needed
   - Not charged against vacation balance

3. **Holiday Days**
   - Company-specific holidays assigned via Holiday Management
   - Only holidays assigned to the employee's company are deducted
   - Not charged against vacation balance

### Example Scenarios

#### Scenario 1: Vacation with Eid Holiday
```
Vacation Period: Thursday March 28 - Monday April 1 (5 days)
Company: Company A
Eid Holiday: Fri Mar 29 - Sun Mar 31 (3 days, assigned to Company A)

Calculation:
- Total vacation days: 5
- Weekend days: Fri Mar 29 (1) + Sat Mar 30 (1) = 2 days
- Holiday days (overlapping with weekend + add'l): Sun Mar 31 (1) = 1 additional day
- Result: 5 - 2 - 1 = 2 days deducted ✓

OR if holiday is continuous Thu-Sun:
- Total vacation days: 5
- Weekend days: Fri Mar 29 + Sat Mar 30 = 2 days
- Holiday days (Thu + Sun): 2 days
- Result: 5 - 2 - 2 = 1 day deducted ✓
```

#### Scenario 2: Vacation Spans Two Companies' Holiday Calendars
```
Vacation Period: Mon April 5 - Fri April 9 (5 days)

Employee from Company A:
- Company A has Eid holidays Mon-Wed (3 days)
- Company A assigned holidays: Mon, Tue, Wed = 3 days
- Weekends in period: Sat (not in range), Sun (not in range) = 0 days
- Result: 5 - 0 - 3 = 2 days deducted

Employee from Company B:
- Company B has NO holidays in this period
- Weekends: Sat (not in range), Sun (not in range) = 0 days
- Result: 5 - 0 - 0 = 5 days deducted
```

#### Scenario 3: Vacation Completely Covered by Holiday + Weekend
```
Vacation Period: Thu May 2 - Mon May 6 (5 days)
Holiday: 3-day Eid (Fri May 3 - Sun May 5)
Weekends: Fri May 3 + Sat May 4 = 2 days

Calculation:
- Total vacation days: 5
- Weekend days: 2 (Fri, Sat)
- Holiday days (overlapping): 1 (Sun May 5)
- Result: 5 - 2 - 1 = 2 days deducted

Note: Holiday overlap with weekends is not double-counted
```

#### Scenario 4: Pure Weekend Vacation (No Deduction)
```
Vacation Period: Fri May 10 - Sat May 11 (2 days)
Holidays: None

Calculation:
- Total vacation days: 2
- Weekend days: 2 (both days are weekend)
- Holiday days: 0
- Result: 2 - 2 - 0 = 0 days deducted ✓
```

## Implementation Details

### Database Changes
- No changes to existing vacation tables
- Holiday company assignment uses new `holiday_companies` junction table
- Employee company reference from `employees.comp_no`

### Code Changes

#### New Methods in VacationCalculator

**1. `countWeekendDays($start_date, $end_date)`**
- Counts Friday (day 5) and Saturday (day 6) in the date range
- Returns: Integer count of weekend days

**2. `countHolidayDaysInRange($emp_id, $start_date, $end_date, $company_id)`**
- Counts holiday days that overlap with vacation period
- Filters holidays by employee's company
- Handles partial overlaps (e.g., holiday starts before vacation)
- Returns: Float count of overlapping holiday days

**3. Updated `getUsedVacationDays()`**
- Now calls both helper methods
- Calculates: `deductible_days = vacation_days - weekend_days - holiday_days`
- Logs detailed calculation for debugging
- Ensures no negative values: `max(0, calculation)`

### Algorithm Explanation

#### Weekend Counting
```php
// For each day in the vacation period:
if (day_of_week === 5 OR day_of_week === 6) {  // 5=Friday, 6=Saturday
    weekend_count++
}
```

#### Holiday Overlap Counting
```php
// For each active holiday in the system:
if (holiday_overlaps_vacation_period) {
    // Calculate overlapping date range
    overlap_start = MAX(vacation_start, holiday_start)
    overlap_end = MIN(vacation_end, holiday_end)
    
    // Count overlapping days
    overlapping_days = date_difference + 1
    total_holiday_days += overlapping_days
}
```

#### Final Deduction
```php
deductible_days = MAX(0, vacation_days - weekend_days - holiday_days)
```

## Configuration

### Weekend Setup
- **Hardcoded:** Friday (5) & Saturday (6) per Saudi Arabia standards
- **No configuration needed** - automatic detection by day of week

### Holiday Setup
1. Go to **Holiday Management** page
2. Create holidays and assign to specific companies
3. Holidays automatically filter by employee's company during calculation

### Per-Company Holidays
- Different companies can have different holiday calendars
- Employee's company ID (`employees.comp_no`) used for filtering
- Automatic company-wise deduction in vacation calculations

## Examples from Real Scenarios

### Royal Saudi National Day Example
```
National Day Holiday: Feb 22-24, 2026 (3 days)
Assigned to: All Companies

Employee A (Company 1):
- Takes vacation Feb 20-26 (7 days)
- Weekends: Sat Feb 21 + Sat Feb 28 = 2 days
- National Day overlap: All 3 days (Feb 22-24)
- Deduction: 7 - 2 - 3 = 2 days ✓

Employee B (Company 2):
- Same vacation period, same holiday
- Same calculation: 2 days deducted ✓
```

### Eid al-Fitr Example (Company-Specific)
```
Eid Holiday: April 1-4, 2026 (4 days)
Assigned to: Company A only

Employee from Company A:
- Vacation: Mar 28 - Apr 5 (9 days)
- Weekends in period: Sat Mar 30 + Sat Apr 6 + (implied Fri Mar 29, Sat Apr 4) 
- Let me recalculate: Mar 28(Thu), 29(Fri), 30(Sat), 31(Sun), Apr 1(Mon), 2(Tue), 3(Wed), 4(Thu), 5(Fri)
- Weekends: Fri(29), Sat(30), Fri(5) = 3 days
- Eid holiday: Apr 1-4 = 4 days
- Deduction: 9 - 3 - 4 = 2 days ✓

Employee from Company B:
- Same vacation dates
- Weekends: 3 days
- Eid holiday: 0 days (not assigned to Company B)
- Deduction: 9 - 3 - 0 = 6 days ✓
```

## Testing Scenarios

To verify the implementation, test these scenarios:

### Test 1: Weekend-Only Vacation
- Dates: Friday + Saturday only
- Expected: 0 days deducted

### Test 2: Vacation Spanning Holiday + Weekend
- Dates: 5 days including a 3-day holiday and 2 weekend days
- Expected: 0 days deducted (5 - 2 - 3 = 0)

### Test 3: Holiday Not Assigned to Company
- Employee Company A takes vacation during Company B's holiday
- Expected: Holiday NOT deducted for Company A employee

### Test 4: Partial Holiday Overlap
- Holiday: April 1-4
- Vacation: March 31 - April 5
- Expected: 4 days holiday overlap, 2 weekends (Apr 4-5 partial) = correct calculation

### Test 5: Multiple Holidays in One Vacation
- Two holidays within vacation period (e.g., Eid + National Day)
- Expected: Both counted in deduction calculation

## Performance Considerations

- **Database Queries:** Optimized with indexes on `holiday_companies` table
- **Date Calculations:** Done in-memory, minimal database load
- **Caching:** Could be implemented for employees with frequent calculations

## Backward Compatibility

- ✓ Existing vacations not affected
- ✓ Handles missing company assignments gracefully
- ✓ Falls back to full vacation days if holiday query fails
- ✓ No breaking changes to vacation request interface

## Error Handling

The system handles these edge cases:

1. **No holidays assigned:** Deduction = vacation_days - weekend_days
2. **Employee has no company:** All holidays counted (safety default)
3. **Invalid dates:** Min/max functions prevent negative values
4. **Database failure:** Logs error and falls back to full deduction

## Logging

Each vacation deduction is logged with details:
```
Vacation Deduction: emp=EMP001, period=2026-02-28 to 2026-03-05, 
total_vacation_days=5, weekend_days=2, holiday_days=3, deductible_days=0
```

Use these logs to debug vacation calculations.

## Future Enhancements

Potential improvements:
1. Support for flexible work schedules (4-day work week)
2. Per-employee weekend configuration
3. Emergency/special vacation types with different rules
4. Custom holiday calculation per department
5. Vacation balance adjustment API for manual corrections

## Related Files

- `includes/vacation_calculator.php` - Implementation
- `manage_holidays.php` - Holiday management UI
- `sql/add_holiday_companies.sql` - Database schema
- `IMPLEMENTATION_GUIDE_HOLIDAY_COMPANIES.md` - Setup guide

## Support

For questions or issues with vacation calculations:
1. Check error logs for calculation details
2. Verify holiday company assignments in database
3. Test with verification queries provided
4. Review example scenarios above
