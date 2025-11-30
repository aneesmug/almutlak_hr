# Employee Profile Report - Testing & Deployment Guide

## Quick Start Guide

### Prerequisites
- PHP 7.4+
- MySQL database with employee data
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Language translation files configured

### Accessing the Report

1. **Authentication**: User must be logged in as valid employee or admin
2. **URL Pattern**: `employee_profile.php?id=<employee_id>`
3. **Auto-Print**: Report automatically opens print dialog on page load
4. **Alternative**: Users can manually click print button

### First Run Checklist

- [ ] Database connection verified
- [ ] All required tables exist (see database section below)
- [ ] Translation keys added to language files
- [ ] Employee avatar images are accessible
- [ ] File upload paths exist for documents
- [ ] Browser set to allow pop-ups for print dialog
- [ ] Print preview shows all sections correctly

---

## Testing Checklist

### Phase 1: Display Testing

#### 1.1 Profile Header Display
- [ ] Avatar image displays correctly (circular, 120px)
- [ ] Employee name shows in white text
- [ ] Job position displays correctly
- [ ] Department shows correctly
- [ ] Employee ID displays with # symbol
- [ ] Status badge displays with correct color (green/red)
- [ ] Blue gradient background visible
- [ ] Section responsive on mobile (single column)

#### 1.2 Quick Stats Dashboard
- [ ] All 4 stat boxes display (Joining, Age, Nationality, Status)
- [ ] Each box has different gradient color
- [ ] Text is white and readable
- [ ] Icons display correctly
- [ ] On mobile: 1 column layout
- [ ] On tablet: 2 column layout
- [ ] On desktop: 4 column layout

#### 1.3 Personal & Employment Details
- [ ] Personal Information column header displays
- [ ] Employment Information column header displays
- [ ] All 7 personal fields display correctly
- [ ] All 8 employment fields display correctly
- [ ] Data formatting is correct (dates, numbers)
- [ ] On mobile: 1 column layout
- [ ] On tablet: 1 column layout
- [ ] On desktop: 2 column layout

#### 1.4 Financial Details
- [ ] Salary Breakdown section displays
- [ ] All salary components display (10 items)
- [ ] Total salary highlighted
- [ ] Bank & Insurance section displays
- [ ] All 7 bank fields display
- [ ] Arabic bank names display if available
- [ ] Currency formatting correct (thousands separator)
- [ ] On mobile: 1 column
- [ ] On desktop: 2 columns

#### 1.5 Assigned Assets
- [ ] Section displays only if assets exist
- [ ] Car information displays if assigned
- [ ] Maker/Model/Year/Plate/Receive date show
- [ ] Other assets table displays
- [ ] Asset names show correctly
- [ ] Serial numbers visible
- [ ] Assignment dates formatted correctly

#### 1.6 Loan History
- [ ] Section displays only if loans exist
- [ ] Table header row displays
- [ ] All 7 columns visible (Amount, Deduction, Balance, Start, End, Type, Status)
- [ ] Balance calculated correctly
- [ ] Loan amount formatted with commas
- [ ] Deduction shows 2 decimal places
- [ ] Start/End dates formatted as "DD MMM YYYY"
- [ ] Type badges show correct color (Emergency=yellow, Regular=info)
- [ ] Status badges show correct color
- [ ] Table scrolls on mobile if needed

#### 1.7 Vacation History
- [ ] Section displays only if vacations exist
- [ ] All 7 columns visible
- [ ] Vacation type (note) displays
- [ ] Dates formatted correctly
- [ ] Days count shows
- [ ] Permit numbers display
- [ ] Status shows (Approved/Completed/Pending)
- [ ] Arrival date displays or "Not Yet"
- [ ] Table scrolls on mobile

#### 1.8 Vacation Balance Summary
- [ ] Section displays only if balance record exists
- [ ] 4 stat boxes display (Allocated, Used, Carried Over, Balance)
- [ ] Colors match (Info/Warning/Secondary/Success)
- [ ] Numbers display correctly
- [ ] Calculation correct: (Allocated + Carried Over - Used)
- [ ] "Days" label shows under each value
- [ ] On mobile: 1 column
- [ ] On tablet: 2 columns
- [ ] On desktop: 4 columns

#### 1.9 Professional Profiles
- [ ] Section displays only if data exists
- [ ] Social media section displays if links exist
- [ ] Social platform names and icons show
- [ ] Portfolio section displays if record exists
- [ ] Skills field shows
- [ ] Certifications field shows
- [ ] Experience field shows
- [ ] Awards field shows
- [ ] On mobile: 1 column
- [ ] On desktop: 2 columns

#### 1.10 End of Service
- [ ] Section displays only if EOS record exists
- [ ] Section has red border (indicator of importance)
- [ ] Resignation date displays
- [ ] Last working day displays
- [ ] Reason displays
- [ ] EOS amount formatted with SAR currency
- [ ] Final settlement formatted correctly
- [ ] Status badge shows (Settled=green, Pending=yellow)
- [ ] On mobile: 1 column
- [ ] On desktop: 2 columns

#### 1.11 Employee Notes
- [ ] Section displays only if notes exist
- [ ] Table header shows (Date, Note)
- [ ] Notes sorted by newest first
- [ ] Dates formatted as "DD, MMM YYYY"
- [ ] Note content displays fully
- [ ] Long notes wrap correctly
- [ ] Deleted notes not shown (is_deleted = 0)

#### 1.12 Documents
- [ ] Section displays only if documents exist
- [ ] File icons display for PDF/Excel/TIF
- [ ] Image thumbnails display for photos
- [ ] Document type label shows
- [ ] Date formatted as "DD-MMM-YY"
- [ ] On desktop: 4 columns per row
- [ ] On tablet: 2 columns per row
- [ ] On mobile: 1 column per row

### Phase 2: Data Accuracy Testing

#### 2.1 Employee Master Data
- [ ] Employee ID matches database
- [ ] Name matches database exactly
- [ ] IQAMA matches and expiry correct
- [ ] Passport matches and expiry correct
- [ ] DOB correct and age calculation accurate
- [ ] Nationality displays correctly
- [ ] Department name correct
- [ ] Section name correct
- [ ] Job position correct
- [ ] Joining date correct
- [ ] Working period calculation accurate
- [ ] Contact info (phone/email) correct

#### 2.2 Financial Data
- [ ] Each salary component matches database
- [ ] Total calculation: sum of all components
- [ ] Bank name matches (including Arabic)
- [ ] IBAN matches and displays correctly
- [ ] GOSI number matches
- [ ] GOSI payment amount correct
- [ ] Insurance number matches
- [ ] Insurance class matches
- [ ] Insurance expiry date correct

#### 2.3 Asset Data
- [ ] Car maker/model correct
- [ ] Car year matches
- [ ] License plate correct
- [ ] Receive date correct
- [ ] Other assets match database
- [ ] Serial numbers correct
- [ ] Assignment dates correct

#### 2.4 Loan Data
- [ ] Loan amount correct
- [ ] Monthly deduction correct
- [ ] Start date correct
- [ ] End date correct
- [ ] Type (emergency/regular) correct
- [ ] Status correct
- [ ] Balance calculation: Total Payable - Total Paid

#### 2.5 Vacation Data
- [ ] Vacation type/note correct
- [ ] Start date correct
- [ ] Return date correct
- [ ] Days calculated correctly
- [ ] Permit number correct
- [ ] Approval status correct
- [ ] Arrival date correct

#### 2.6 Vacation Balance Data
- [ ] Allocated days matches database
- [ ] Used days calculated from vacation history
- [ ] Carried over amount correct
- [ ] Balance calculation: (Allocated + Carried Over - Used)

### Phase 3: Functional Testing

#### 3.1 Print Functionality
- [ ] Print dialog opens automatically on page load
- [ ] Print button works manually
- [ ] Print preview displays correctly
- [ ] All sections visible in print preview
- [ ] Page orientation correct (Portrait)
- [ ] Margins minimal in print
- [ ] Colors display in print preview
- [ ] Can save as PDF successfully

#### 3.2 Browser Compatibility
- [ ] Chrome/Chromium displays correctly
- [ ] Firefox displays correctly
- [ ] Safari displays correctly
- [ ] Edge displays correctly
- [ ] Mobile browser (Safari on iOS) displays
- [ ] Mobile browser (Chrome on Android) displays
- [ ] Print works in all browsers
- [ ] PDF export works in all browsers

#### 3.3 Responsiveness
- [ ] Desktop (1920px): All elements align
- [ ] Laptop (1366px): All elements align
- [ ] Tablet Landscape (1024px): Layouts adjust
- [ ] Tablet Portrait (768px): Single columns where needed
- [ ] Mobile (375px): Single column layout
- [ ] No horizontal scrolling (except tables)
- [ ] Text remains readable at all sizes
- [ ] Images scale appropriately

#### 3.4 Navigation
- [ ] Back button works (if implemented)
- [ ] Internal links work (if any)
- [ ] External links open in new tab (if any)
- [ ] Header navigation accessible
- [ ] Footer accessible

### Phase 4: Localization Testing

#### 4.1 English (LTR)
- [ ] All English labels display
- [ ] Text aligns left-to-right
- [ ] Numbers format correctly (1,000.00)
- [ ] Dates format correctly (DD/MM/YYYY or DD MMM YYYY)
- [ ] No Arabic text visible

#### 4.2 Arabic (RTL)
- [ ] All Arabic labels display
- [ ] Text aligns right-to-left
- [ ] HTML has dir="rtl"
- [ ] Arabic bank names display (from *_ar fields)
- [ ] Arabic department names display (if available)
- [ ] Arabic job titles display (if available)
- [ ] Numbers still format correctly (RTL numbers)
- [ ] Dates format correctly
- [ ] No English text visible (except employee name)

#### 4.3 Translation Keys
- [ ] All expected keys have translations
- [ ] No key names displayed instead of translations
- [ ] RTL text doesn't flow into LTR text
- [ ] Special characters display correctly
- [ ] Unicode characters display correctly

### Phase 5: Performance Testing

#### 5.1 Load Time
- [ ] Page loads in < 3 seconds (with 50 records)
- [ ] Print dialog appears within 2 seconds
- [ ] Images load within 2 seconds
- [ ] No console JavaScript errors
- [ ] No console PHP warnings/errors

#### 5.2 Memory Usage
- [ ] Page doesn't consume excessive memory
- [ ] Multiple pages can open simultaneously
- [ ] Printing multiple pages doesn't cause crashes
- [ ] Large documents (100+ records) still responsive

### Phase 6: Error Handling

#### 6.1 Missing Data
- [ ] Employee not found: redirect to employee list
- [ ] Missing salary record: displays "N/A" or 0
- [ ] Missing avatar: displays default placeholder
- [ ] Missing documents: section hidden
- [ ] Missing loans: section hidden
- [ ] Missing vacation: section hidden
- [ ] Missing portfolio: section hidden
- [ ] Missing EOS: section hidden

#### 6.2 Data Integrity
- [ ] Special characters (quotes, brackets) display correctly
- [ ] Long text wraps without breaking layout
- [ ] Very long names don't overflow
- [ ] Numbers with many decimals format correctly
- [ ] Empty fields show gracefully

### Phase 7: Print Output Testing

#### 7.1 Color Print
- [ ] Blue gradient displays in print
- [ ] Green badges display correctly
- [ ] Red badges display correctly
- [ ] Yellow badges display correctly
- [ ] Text colors print correctly
- [ ] Page numbering appears if multi-page

#### 7.2 Single-Page Format
- [ ] Typical employee fits on 1 page
- [ ] Employee with all data fits on 1-2 pages
- [ ] No content cut off
- [ ] Page breaks occur at appropriate places
- [ ] Sections not split between pages

#### 7.3 Print Quality
- [ ] Text is clear and readable
- [ ] Images are clear
- [ ] Tables are properly formatted
- [ ] Borders visible
- [ ] No blurry elements

#### 7.4 PDF Export
- [ ] PDF exports without errors
- [ ] PDF file is valid
- [ ] PDF displays correctly in PDF reader
- [ ] PDF is searchable
- [ ] PDF contains all pages
- [ ] PDF file size reasonable (< 2MB)

### Phase 8: Security Testing

#### 8.1 Authentication
- [ ] Unauthenticated users redirected to login
- [ ] Session check prevents unauthorized access
- [ ] Only logged-in users can view reports

#### 8.2 Data Access
- [ ] Users can only view their own employee record (if applicable)
- [ ] Admins can view all records
- [ ] No SQL injection vulnerabilities
- [ ] HTML special characters escaped
- [ ] URL parameters validated

#### 8.3 File Security
- [ ] Document files not directly accessible
- [ ] File download links secure
- [ ] No sensitive data in URLs
- [ ] Form data encrypted over HTTPS
- [ ] No sensitive data in browser cache

---

## Deployment Checklist

### Pre-Deployment

- [ ] All testing phases completed successfully
- [ ] Translation keys added to all language files
- [ ] Database backup created
- [ ] Browser compatibility verified
- [ ] Performance benchmarks met
- [ ] Security vulnerabilities fixed
- [ ] Code reviewed by team
- [ ] Documentation complete and accurate
- [ ] User training completed (if applicable)
- [ ] Rollback plan documented

### Deployment Steps

1. **Backup**
   ```bash
   # Backup current files
   cp -r employee_profile.php employee_profile.php.backup
   cp -r style.css style.css.backup
   ```

2. **Upload New Files**
   ```bash
   # Upload enhanced employee_profile.php
   # Upload updated style.css (if modified)
   ```

3. **Database Verification**
   ```sql
   -- Verify all required tables exist
   SELECT * FROM INFORMATION_SCHEMA.TABLES 
   WHERE TABLE_NAME IN ('employees', 'emp_salary', 'emp_vacation_balance', 
                        'emp_loan', 'emp_vacation', 'emp_docu', 'emp_notice',
                        'employee_assets', 'emp_gosi', 'emp_eos', 'portfolio', 
                        'social', 'social_list');
   ```

4. **Clear Cache** (if applicable)
   ```bash
   # Clear application cache
   # Clear CDN cache (if applicable)
   # Restart web server
   ```

5. **Smoke Tests**
   - [ ] Open employee report in browser
   - [ ] Verify all sections display
   - [ ] Print to PDF successfully
   - [ ] Check browser console for errors
   - [ ] Verify database queries execute
   - [ ] Test with multiple employees

6. **User Notification**
   - [ ] Email users about update
   - [ ] Post announcement on dashboard
   - [ ] Provide quick start guide
   - [ ] Provide support contact info

### Post-Deployment

- [ ] Monitor error logs for 24 hours
- [ ] Monitor performance metrics
- [ ] Collect user feedback
- [ ] Fix any reported issues immediately
- [ ] Update documentation as needed
- [ ] Schedule follow-up review (7 days)

---

## Troubleshooting Guide

### Issue: Print Dialog Doesn't Open
**Symptoms**: Page loads but print dialog doesn't appear
**Possible Causes**:
- JavaScript disabled in browser
- Browser prevents pop-ups
- Print function blocked by extension
- Page load error

**Solutions**:
1. Enable JavaScript in browser settings
2. Allow pop-ups for this domain
3. Disable browser extensions temporarily
4. Check browser console for JavaScript errors
5. Try in different browser

### Issue: Sections Not Displaying
**Symptoms**: Some report sections are blank
**Possible Causes**:
- Database query error
- Missing data
- Query timeout
- Table doesn't exist

**Solutions**:
1. Check database for employee records
2. Verify table names in query
3. Check database connection
4. Review PHP error logs
5. Increase PHP execution time

### Issue: Colors Not Printing
**Symptoms**: Print preview shows colors, but printed output is black/white
**Possible Causes**:
- Print settings disabled
- Printer not supporting color
- Print color adjustment disabled

**Solutions**:
1. In print dialog: Check "Background graphics" checkbox
2. In print dialog: Change "Color settings" to "Color"
3. Check printer supports color
4. Update printer drivers
5. Test with different printer

### Issue: Content Overlapping in Print
**Symptoms**: Text overlaps with other elements in printed output
**Possible Causes**:
- Font too large
- Margins too small
- CSS print styles not applied
- Page break issues

**Solutions**:
1. In print dialog: Reduce scaling to 90-95%
2. In print dialog: Increase margins
3. Check CSS media query for print (@media print)
4. Disable print CSS and re-enable
5. Try different paper size or orientation

### Issue: Missing Translations
**Symptoms**: Translation keys displayed instead of actual text (e.g., "employee_id_label")
**Possible Causes**:
- Language file not found
- Translation key not added
- Translation function not defined
- Language not configured

**Solutions**:
1. Check language file exists in correct directory
2. Verify translation key matches exactly (case-sensitive)
3. Verify __() function is defined
4. Check language is selected/configured
5. Clear browser cache and reload

### Issue: Database Connection Error
**Symptoms**: "Database connection error" or blank page
**Possible Causes**:
- Database server down
- Credentials incorrect
- Firewall blocking connection
- Connection timeout

**Solutions**:
1. Verify database server is running
2. Check connection credentials (host, user, password)
3. Ping database server: `ping db-server-ip`
4. Increase connection timeout
5. Check firewall rules
6. Review PHP error logs

### Issue: Images Not Displaying
**Symptoms**: Avatar or document thumbnails show broken image icon
**Possible Causes**:
- File path incorrect
- File doesn't exist
- File permissions wrong
- MIME type incorrect

**Solutions**:
1. Verify file paths are absolute/relative correctly
2. Check files exist in correct directory
3. Check file permissions (should be readable)
4. Verify image format is supported
5. Test with direct file URL

### Issue: Slow Page Load
**Symptoms**: Page takes > 5 seconds to load
**Possible Causes**:
- Database queries too many/complex
- Large files loading
- Network latency
- Server overloaded

**Solutions**:
1. Check database query count (add debug logging)
2. Optimize queries (add indexes, limit results)
3. Reduce image file sizes
4. Enable server-side caching
5. Check server CPU/memory usage
6. Increase PHP memory limit

---

## Support Contacts

| Issue Type | Contact | Response Time |
|------------|---------|----------------|
| Technical Bug | Dev Team | 2 hours |
| Database Issue | DBA Team | 1 hour |
| Performance | Ops Team | 4 hours |
| Access/Permission | Admin Team | 30 minutes |
| Feature Request | Product Team | 24 hours |

---

## Rollback Plan

If critical issues occur post-deployment:

1. **Immediate Rollback** (< 5 minutes)
   ```bash
   # Restore from backup
   cp employee_profile.php.backup employee_profile.php
   cp style.css.backup style.css
   # Restart web server
   ```

2. **Communication**
   - Notify users of issue
   - Provide status updates every 30 minutes
   - Schedule maintenance window if needed

3. **Investigation**
   - Review error logs
   - Identify root cause
   - Create fix
   - Test thoroughly before re-deployment

4. **Re-deployment**
   - Deploy fixed version to staging
   - Conduct smoke tests
   - Deploy to production
   - Verify all systems working

---

## Success Criteria

✅ All sections display correctly for employees with full data
✅ All sections display correctly for employees with partial data
✅ Responsive design works on all breakpoints
✅ Print output fits on 1-2 pages (typical employee)
✅ Print colors display correctly
✅ Localization works for English and Arabic
✅ No JavaScript console errors
✅ No PHP errors in logs
✅ Page loads in < 3 seconds
✅ Database queries execute without errors
✅ Users can print successfully
✅ Users can export to PDF successfully
✅ Security measures in place
✅ User feedback is positive

---

**Document Version**: 1.0
**Last Updated**: [Current Date]
**Status**: Ready for Deployment
