# FTI Pak Tax Management System - Quick Start Reference

## 🚀 Installation (30 minutes)

### For Non-Technical Users:
1. Upload all files via cPanel File Manager to `public_html`
2. Rename `.env.example` to `.env`
3. Edit `.env`: Add database name, username, password
4. Open Terminal in cPanel and run:
   ```
   php artisan key:generate
   php artisan app:setup
   ```
5. Create cron job in cPanel (every hour):
   ```
   cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
   ```
6. Visit your domain and login

### For Administrators:
- Default login created during setup
- Create employees with roles (Admin/Consultant/Staff)
- Configure Microsoft email integration (optional, for FBR notice auto-fetch)

---

## 📋 Main Features Quick Guide

### **1. Client Management**
- **Add Client:** Clients → + Add Client
- **Manage Services:** Edit client → Select active services + deadlines
- **Add Shareholders:** Link other clients as shareholders with percentage
- **Store Credentials:** FBR, KPRA, SECP passwords auto-encrypted

### **2. Task Assignment**
- **Create Task:** Tasks → + Create Task
- **Assign Users:** Select multiple team members
- **Track Status:** pending → in_progress → completed/overdue
- **Priority:** Set low/medium/high

### **3. FBR Notice Tracking**
- **Auto-fetched:** System pulls FBR emails every hour
- **Categorized:** By tax year (auto-extracted from email)
- **By Section:** Extracted from email subject
- **Escalate:** Mark urgent notices → escalate to manager
- **Assign to Client:** Match notice to client for follow-up

### **4. Service Reminders**
- **Auto-generated:** 7 days, 3 days, 1 day before deadline
- **Overdue Alerts:** Automatically escalate to admin
- **Email + In-App:** Users get both notifications
- **Customizable:** Override deadline for specific client

### **5. Employee Management (Admin Only)**
- **Add Staff:** Employees → + Add Employee
- **Assign Roles:** Admin/Consultant/Staff (different permissions)
- **Team Tasks:** Assign tasks to multiple employees

### **6. Mini Apps**
- **WHT Software:** Withholding tax calculations (coming soon)
- **Extensible:** Add more tools as needed

---

## 🔐 Security Checklist

- [ ] All passwords encrypted in database
- [ ] `.env` file not accessible via web
- [ ] Database credentials changed from default
- [ ] HTTPS enabled on domain
- [ ] Regular backups configured
- [ ] Access logs monitored

---

## ⚙️ Automated Jobs (Run Every Hour)

1. **FBR Notice Fetching**
   - Pulls emails from FBR address
   - Auto-categorizes by section & year
   - Creates notifications

2. **Service Deadline Reminders**
   - Checks upcoming deadlines
   - Sends email + in-app notifications
   - Escalates overdue items

*Configured via cPanel Cron Jobs*

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| 500 Error | Check database credentials in `.env` |
| Can't login | Verify admin user created during setup |
| No emails received | Check SMTP settings in `.env` (Gmail needs App Password) |
| Cron not running | Verify path in cPanel is correct: `/home/username/public_html` |
| Database full | Backup and clean old notices (6+ months) |

---

## 📧 Email Setup

### Gmail:
1. Enable 2FA
2. Create App Password: myaccount.google.com/apppasswords
3. Add to `.env`:
   ```
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=16-char-app-password
   ```

### Office 365:
```
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-email@company.com
MAIL_PASSWORD=your-password
```

---

## 🔗 Microsoft Email Integration (Optional)

To auto-fetch FBR emails:
1. Go to https://portal.azure.com
2. Create Application Registration
3. Add Mail.Read permission
4. Create client secret
5. Update `.env`:
   - MICROSOFT_CLIENT_ID
   - MICROSOFT_CLIENT_SECRET

---

## 📱 Key URLs

- **Dashboard:** `/dashboard`
- **Clients:** `/clients`
- **Tasks:** `/tasks`
- **FBR Notices:** `/fbr-notices`
- **Employees:** `/employees` (Admin only)
- **Mini Apps:** `/mini-apps`
- **Notifications:** `/notifications`

---

## 💾 Database Backups

**Via cPanel:**
1. Backups → Generate Backup
2. Download and store securely
3. Schedule automatic weekly backups

---

## 📞 Support Resources

- **Deployment Guide:** `CPANEL_UPLOAD_GUIDE.md`
- **Full Documentation:** `DEPLOYMENT_GUIDE.md`
- **Error Logs:** `storage/logs/laravel.log`
- **cPanel Logs:** Check via cPanel terminal

---

## 🎯 Best Practices

1. **Create Dashboard Shortcuts:** Bookmark important pages
2. **Regular Backups:** Weekly database backups
3. **Clean Old Data:** Archive completed tasks quarterly
4. **Team Training:** Brief employees on their roles
5. **Credential Rotation:** Change FBR/KPRA passwords quarterly

---

## 🚀 Next Steps

1. ✅ Setup complete
2. ✅ Login as admin
3. Add first client
4. Create service deadlines
5. Invite team members
6. Configure FBR integration
7. Test reminder system

---

**Version:** 1.0 | **Updated:** April 2, 2026
