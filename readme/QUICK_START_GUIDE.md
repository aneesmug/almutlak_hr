# Quick Start Guide - Travel Email Feature

## 🚀 5-Minute Setup

### Step 1: Run Database Migration (1 minute)
```bash
cd D:\xampp\htdocs\almutlak\system
mysql -u root almutlak_db < add_traveling_company_email_setting.sql
```

### Step 2: Configure Email Address (1 minute)
```sql
-- Open phpMyAdmin or MySQL command line
UPDATE `app_settings` 
SET `setting_value` = 'your-travel-company@email.com' 
WHERE `setting_name` = 'traveling_company_email';
```

### Step 3: Test Display (1 minute)
1. Open browser
2. Navigate to any vacation with departure/arrival dates
3. Go to: `vacation_report_details.php?id=X&emp_id=Y`
4. **✅ Expected:** You should see "Departure Date" and "Arrival Date" fields

### Step 4: Test Email (2 minutes)
1. Create a test annual fly vacation
2. Add departure and arrival dates
3. Complete approval workflow
4. **✅ Expected:** Email sent to traveling company

### Step 5: Verify (30 seconds)
```bash
# Check logs for confirmation
tail -f D:\xampp\apache\logs\error.log | grep "Travel company email"
```

**Expected log:**
```
[timestamp] Travel company email sent successfully for vacation request: VAC-XXX-XXX
```

---

## ✅ Verification Checklist

- [ ] Database migration completed
- [ ] Email address configured in app_settings
- [ ] Flight dates visible in vacation reports
- [ ] Flight dates visible in vacation history
- [ ] Flight dates visible in vacation listing
- [ ] Email sent on final approval
- [ ] Email received in inbox (check spam folder)

---

## 🎯 What You Get

### Display Features:
✅ Departure Date and Arrival Date shown on 3 pages:
- Vacation Report Details Page
- Vacation Status History Page
- All Vacations Listing Page

### Email Features:
✅ Automatic email with:
- Traveler Name
- Passport Number
- Passport Expiry
- Destination Country
- Departure Date
- Arrival Date
- Reference Number

### Professional Email Design:
✅ Beautiful HTML template
✅ Responsive design
✅ Company branding
✅ Plain text fallback

---

## 📞 Need Help?

### Common Issues:

**Email not sending?**
→ Check: `SELECT * FROM app_settings WHERE setting_name = 'traveling_company_email';`

**Flight dates not showing?**
→ Ensure vacation is type "Fly" and fly_type "annual"

**Still stuck?**
→ See: `TRAVEL_EMAIL_DOCUMENTATION.md` for detailed troubleshooting

---

**That's it! You're ready to go! 🎉**
