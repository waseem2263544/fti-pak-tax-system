# 📊 FTI Pak Tax Consultancy Management System

**A Complete Web Application for Managing Tax Consultancy Operations**

Built with Laravel, MySQL, and Bootstrap for deployment on cPanel/Shared Hosting.

---

## ✨ What's Included

### Core Modules
✅ **Client Management** - Complete client database with credentials vault  
✅ **Employee Management** - Role-based access control (Admin/Consultant/Staff)  
✅ **Task Assignment** - Create and track tasks for team members  
✅ **FBR Notice Tracking** - Auto-fetch and categorize FBR emails  
✅ **Service Reminders** - Automated deadline notifications (7/3/1 day + overdue)  
✅ **In-App Notifications** - Real-time alerts for reminders, notices, tasks  
✅ **Mini Apps Workspace** - Dedicated space for WHT software and future tools  

### Key Features
- 🔐 **Encrypted Credentials** - All passwords encrypted at rest
- 🤖 **Hourly Automation** - FBR notice fetching + reminder processing
- 📧 **Dual Notifications** - Email + in-app alerts
- 📱 **Responsive UI** - Works on desktop, tablet, mobile
- 🚀 **Zero Config Deployment** - Upload and run on shared hosting
- 👥 **Multi-user System** - Support for unlimited employees + clients
- 📊 **Dashboard Analytics** - Quick overview of key metrics

---

## 📦 What You Get

```
fti_pak_tax/
├── app/                          # Laravel application
│   ├── Console/Commands/         # Cron jobs (FBR fetching, reminders)
│   ├── Http/Controllers/         # All business logic
│   ├── Models/                   # Database models
│   └── Providers/               # Service providers
├── database/
│   ├── migrations/              # Auto-create all tables
│   └── seeders/                 # Default roles & services
├── resources/views/             # HTML templates (Bootstrap)
├── routes/                      # URL routing
├── storage/                     # Logs & cache
├── public/                      # Assets & entry point
│
├── CPANEL_UPLOAD_GUIDE.md       # Step-by-step upload instructions
├── DEPLOYMENT_GUIDE.md          # Full documentation
├── QUICK_START.md               # Quick reference guide
├── setup.sh                     # Auto-setup script
├── .env.example                 # Configuration template
└── composer.json                # PHP dependencies

```

---

## 🚀 Quick Start (3 Steps)

### Step 1️⃣ Upload
- Download the ZIP file
- Extract it
- Upload all files via cPanel File Manager to `public_html`

### Step 2️⃣ Configure
- Rename `.env.example` to `.env`
- Edit `.env` with:
  - Database name, user, password
  - Email server details (Gmail, Office 365, etc.)

### Step 3️⃣ Setup
- Open cPanel Terminal
- Run: `php artisan app:setup`
- Follow the wizard to create admin account

✅ **Done!** Visit your domain and login

---

## 📋 Detailed Guides

| Document | Purpose |
|----------|---------|
| **CPANEL_UPLOAD_GUIDE.md** | Step-by-step for cPanel (non-technical users) |
| **DEPLOYMENT_GUIDE.md** | Complete system documentation |
| **QUICK_START.md** | Feature overview & quick reference |

---

## 🎯 Key Features Explained

### 1. 📋 Client Management
```
- Complete client profiles (name, email, contact, status)
- Status types: Individual, AOP (Association of Persons), Company
- Shareholder relationships (link other clients)
- Multiple active services per client
- Encrypted credential storage (FBR, KPRA, SECP credentials)
- Folder links for document organization
```

### 2. 👥 Employee & Role Management
```
- Three roles: Admin (full access), Consultant (client/task access), Staff (limited)
- Create unlimited employees
- Assign multiple roles to one employee
- Role-based feature access control
```

### 3. ✅ Task Assignment
```
- Create tasks with details & deadlines
- Assign to one or multiple team members
- Track status: pending → in_progress → completed/overdue
- Priority levels: low, medium, high
- Link to specific client for context
```

### 4. 📧 FBR Notice Tracking
```
- Auto-fetches FBR emails hourly (requires Microsoft email integration)
- Auto-categorizes by:
  - Tax Year (extracted from email content)
  - Notice Section (extracted from email subject)
- Status tracking: new, reviewed, resolved, escalated
- Escalate to admin for urgent notices
- Assign notice to client for follow-up
```

### 5. ⏰ Service Deadline Reminders
```
Pre-configured Services:
- Income Tax Return (yearly)
- Sales Tax Return (monthly/quarterly)
- KPRA Return
- Bookkeeping
- Withholding Tax Statements

Features:
- Default deadlines per service (customizable)
- Client-specific deadline overrides
- Automated reminders: 7 days, 3 days, 1 day, overdue
- Email + in-app dual notifications
- Auto-escalation to admin when overdue
- Bulk deadline updates for multiple clients
```

### 6. 🔔 In-App Notification Center
```
- Real-time notification bell with unread count
- Notifications by type: reminder, FBR notice, task, escalation
- Color-coded by priority: low, medium, high
- Mark individual/all as read
- Notification history page
```

### 7. 🧩 Mini Apps Workspace
```
- Dedicated space for WHT (Withholding Tax) software
- Plugin architecture for future tools
- Extensible design for custom applications
```

---

## 🔐 Security Features

✅ **Encrypted Passwords**
- All FBR, KPRA, SECP passwords encrypted using Laravel's encryption
- Decrypted only when displayed to authorized users

✅ **Role-Based Access Control**
- Admin: Full system access
- Consultant: Client & task management
- Staff: Limited task viewing

✅ **Environment Secrets**
- `.env` file excluded from web access
- Database credentials never exposed

✅ **HTTPS Support**
- Configure via cPanel AutoSSL (usually free)

✅ **Audit Trail**
- All notifications logged
- Task status history
- FBR notice tracking

---

## ⚙️ Automated Processes (Hourly)

### 1. FBR Notice Fetching
```
- Connects to Microsoft Graph API
- Fetches emails from FBR sender address
- Parses and categorizes by tax year & section
- Creates high-priority notification
- Auto-assigns to client if name mentioned
```

### 2. Service Deadline Reminders
```
- Checks all client services for upcoming deadlines
- 7 days before: Low-priority reminder
- 3 days before: Medium-priority reminder
- 1 day before: High-priority reminder (URGENT)
- Past deadline: OVERDUE + Escalates to admin
- Sends email + in-app notification
```

---

## 📧 Email Configuration

### Gmail (Recommended)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password (not regular password)
MAIL_ENCRYPTION=tls
```

Get App Password: https://myaccount.google.com/apppasswords

### Office 365
```env
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=your-email@company.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

---

## 💾 Database Schema

### Main Tables
- **clients** - Client information & encrypted credentials
- **shareholders** - Client relationships (many-to-many)
- **services** - Available services (Income Tax, Sales Tax, etc.)
- **client_services** - Client's active services with deadlines
- **tasks** - Team tasks with assignments
- **task_user** - Task assignments (many-to-many)
- **fbr_notices** - FBR email notices with categorization
- **reminders** - Deadline reminders with notification status
- **notifications** - User notifications (in-app)
- **users** - Team members with roles
- **roles** - Admin, Consultant, Staff
- **role_user** - User role assignments (many-to-many)
- **microsoft_email_settings** - OAuth tokens for email integration

---

## 🛠️ System Requirements

✅ PHP 8.1+ (most cPanel hosts have PHP 8.1+)  
✅ MySQL 5.7+ (standard on all hosts)  
✅ Composer (usually pre-installed)  
✅ cPanel with Terminal access  
✅ Outgoing email support (standard)  
✅ 500MB+ disk space  

---

## 📱 Browser Support

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (responsive design)

---

## 🚨 Important Notes

⚠️ **Before Going Live:**
1. Enable HTTPS (cPanel AutoSSL)
2. Change all default passwords
3. Configure backups
4. Test email delivery
5. Verify cron jobs working

⚠️ **Regular Maintenance:**
- Weekly database backups
- Monthly log review
- Clean archived notices (6+ months old)
- Update any deprecated code

⚠️ **Data Protection:**
- Passwords are encrypted but use strong master key
- Don't share `.env` file
- Limit admin access
- Use strong passwords for all accounts

---

## 🔧 Customization & Extension

### Add New Service Type
1. Edit `database/seeders/ServiceSeeder.php`
2. Add new Service::create() entry
3. Run migration

### Add New User Role
1. Create role in database via admin panel
2. Add permissions in controllers
3. Assign to users

### Extend with APIs
- All models have relationships
- Easy to add REST API endpoints
- Documentation available in controllers

---

## 📞 Support

### Included Documentation
- `CPANEL_UPLOAD_GUIDE.md` - Upload instructions
- `DEPLOYMENT_GUIDE.md` - Full documentation
- `QUICK_START.md` - Feature quick reference
- Code comments in all controllers & models

### Troubleshooting
1. Check `storage/logs/laravel.log` for errors
2. Verify database credentials in `.env`
3. Ensure cron jobs configured correctly
4. Test email delivery from cPanel

### Updates & Maintenance
- Keep Laravel version up-to-date
- Monitor security advisories
- Review and archive old data
- Test backups monthly

---

## 📄 License & Terms

This software is provided as-is for use in your tax consultancy firm. 

---

## 🎉 You're Ready!

Follow the **CPANEL_UPLOAD_GUIDE.md** to get started in 30 minutes.

**Version:** 1.0  
**Last Updated:** April 2, 2026  
**Built With:** Laravel 10, Bootstrap 5, MySQL

---

### Next Steps
1. 📖 Read `CPANEL_UPLOAD_GUIDE.md`
2. 📁 Upload files to cPanel
3. ⚙️ Configure `.env`
4. 🚀 Run setup wizard
5. 👤 Create first client
6. 📧 Configure email
7. 🎯 Set service deadlines

**Happy Tax Management! 🎯**
