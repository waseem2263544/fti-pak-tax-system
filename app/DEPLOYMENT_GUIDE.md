# FTI Pak Tax Consultancy Management System

A comprehensive Laravel-based tax consultancy management system with client management, task assignment, FBR notice tracking, automated reminders, and employee role management.

## Features

### 1. Client Management
- Complete client profiles with contact details
- Status tracking (Individual/AOP/Company)
- Shareholder relationships (lookup other clients as shareholders)
- Multiple active services per client
- Secure credential storage (encrypted FBR, KPRA, SECP credentials)
- Folder links for document organization

### 2. Employee & Role Management
- Three roles: Admin, Consultant, Staff
- Role-based access control (RBAC)
- Employee profile management
- Task assignment capabilities

### 3. Task Assignment System
- Create tasks with details and deadlines
- Assign tasks to multiple users
- Status tracking (pending, in_progress, completed, overdue)
- Priority levels (low, medium, high)
- Client-linked tasks

### 4. FBR Notice Tracking
- Automated hourly email fetching from Microsoft email via Graph API
- Auto-categorization by tax year and notice section (extracted from subject)
- Notice status tracking (new, reviewed, resolved, escalated)
- Escalation to admin users
- Client assignment for notices

### 5. Service Deadline & Reminder System
- Pre-configured services: Income Tax Return, Sales Tax Return, KPRA Return, Bookkeeping, Withholding Tax
- Customizable default deadlines per service
- Client-specific deadline overrides
- Automated hourly reminder processing
- Multi-level reminders (7 days, 3 days, 1 day, overdue)
- Overdue task escalation to managers

### 6. In-App Notification Center
- Real-time notifications for reminders, FBR notices, tasks
- Email + in-app dual notifications
- Priority-based color coding
- Mark as read functionality
- Unread notification counter

### 7. Mini Apps Workspace
- Dedicated space for WHT software and future tools
- Plugin-ready architecture

## System Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or compatible
- Composer
- cPanel/Shared Hosting support

## Installation on Shared Hosting (cPanel)

### Step 1: Upload Files to Server
1. Download and extract the project ZIP file
2. Connect to your server via FTP or File Manager in cPanel
3. Navigate to the public_html directory (or your domain folder)
4. Upload all files to public_html

### Step 2: Create Database
1. Log in to cPanel
2. Go to MySQL Databases
3. Create a new database (e.g., `fti_pak_tax`)
4. Create a new MySQL user (e.g., `fti_user`)
5. Add the user to the database with all privileges
6. Note down database name, user, and password

### Step 3: Configure Environment
1. In the uploaded folder, locate `.env.example` and make a copy named `.env`
2. Edit `.env` and fill in:
   - `DB_DATABASE=` (your database name)
   - `DB_USERNAME=` (your database user)
   - `DB_PASSWORD=` (your database password)
   - `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` (Gmail or your email provider SMTP)
   - `APP_KEY=` (keep empty, will be generated)

3. Generate application key:
   - Go to cPanel Terminal (Advanced > Terminal)
   - Navigate to your project folder: `cd /home/username/public_html`
   - Run: `php artisan key:generate`

### Step 4: Run Setup Wizard
1. In terminal, run: `php artisan app:setup`
2. Follow prompts to:
   - Create database tables (migrations)
   - Seed default roles and services
   - Create admin user account

### Step 5: Configure Cron Jobs
1. In cPanel, go to Cron Jobs
2. Add two cron jobs running **every hour**:

   **Job 1 - FBR Notice Fetching:**
   ```
   cd /home/username/public_html && php artisan app:fetch-fbr-notices
   ```

   **Job 2 - Reminder Processing:**
   ```
   cd /home/username/public_html && php artisan app:process-reminders
   ```

   **Job 3 - Laravel Scheduler (required for both jobs):**
   ```
   cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
   ```

   Frequency: Every hour

### Step 6: Microsoft Email Integration (Optional but Recommended)
To enable automated FBR notice fetching:

1. Go to [Microsoft Azure Portal](https://portal.azure.com)
2. Create a new Application Registration
3. Configure OAuth2 permissions (Mail.Read)
4. Get Client ID and Client Secret
5. Update `.env`:
   - `MICROSOFT_CLIENT_ID=` (your client ID)
   - `MICROSOFT_CLIENT_SECRET=` (your secret)
   - `MICROSOFT_REDIRECT_URI=` (your domain + /auth/microsoft/callback)

6. As admin, set up email integration in the application settings

## Usage

### Login
1. Visit: `http://yourdomain.com`
2. Use the admin credentials created during setup

### Adding Clients
1. Go to Clients → Add Client
2. Fill in all required details
3. Select active services and set deadlines
4. Add shareholders (lookup from existing clients)
5. Store credentials securely (auto-encrypted)

### Creating Tasks
1. Go to Tasks → Create Task
2. Assign to one or multiple team members
3. Link to client if applicable
4. Set priority and deadline

### Managing Employees
1. Go to Employees (Admin only)
2. Create new employee with role assignment
3. Assign to multiple roles as needed

### FBR Notice Tracking
1. Go to FBR Notices
2. Filter by status, tax year, or section
3. View notice details and assign to client if needed
4. Escalate to manager if urgent
5. Update status as reviewed/resolved

### Setting Service Deadlines
1. Edit Client → Active Services
2. For each service, set specific deadline date
3. System will auto-generate reminders before deadline
4. Overdue items auto-escalate with red flag

## Email Configuration

### Gmail Setup
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. In `.env`:
   - `MAIL_USERNAME=` (your Gmail)
   - `MAIL_PASSWORD=` (your app password)

### Other SMTP Providers
Update `.env` with your provider's:
- MAIL_HOST
- MAIL_PORT (usually 587 for TLS)
- MAIL_USERNAME
- MAIL_PASSWORD
- MAIL_ENCRYPTION (tls or ssl)

## Troubleshooting

### Database Connection Error
- Verify DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD in `.env`
- Ensure user has all database privileges
- Test credentials in cPanel MySQL interface

### Cron Jobs Not Running
- Check cPanel Cron Job Logs
- Verify correct path to project folder
- Ensure permissions allow script execution
- Try running command manually in terminal

### Emails Not Sending
- Test SMTP credentials in cPanel Email Accounts
- Check Mail_FROM_ADDRESS in `.env`
- Verify port (587 for TLS, 465 for SSL)
- Check server firewall/SSL settings

### FBR Notices Not Fetching
- Verify Microsoft Graph API credentials
- Check access token validity
- Ensure Mail.Read permission is granted
- Check application logs: `storage/logs/laravel.log`

## Security Notes

- All passwords (FBR, KPRA, SECP) are encrypted before storage
- Use strong admin password
- Keep `.env` file secure (not committed to version control)
- Regularly backup database
- Enable HTTPS on your domain

## File Structure

```
/
├── app/                   # Application logic
│   ├── Console/Commands/  # Cron jobs (fetch-fbr-notices, process-reminders)
│   ├── Http/Controllers/  # All controllers
│   └── Models/            # Database models
├── database/
│   ├── migrations/        # All table definitions
│   └── seeders/          # Default data (roles, services)
├── routes/               # URL routing
├── resources/views/      # HTML templates
├── .env.example          # Configuration template
└── public/               # Publicly accessible files

```

## API Endpoints (JSON)

- `GET /notifications/unread-count` - Get unread notification count
- `GET /notifications/latest` - Get latest 10 notifications
- `POST /notifications/{notification}/read` - Mark as read
- `POST /tasks/{task}/status` - Update task status
- `POST /fbr-notices/{notice}/escalate` - Escalate notice

## Support & Maintenance

For issues or feature requests, maintain the following:
- Database backups: Daily via cPanel
- Log monitoring: `storage/logs/laravel.log`
- Cron job verification: Weekly
- Security patches: Keep Laravel updated

## Future Enhancements

- WhatsApp notifications
- Document storage integration
- Compliance calendar
- Automated company registration filing
- Payment processing module
- Audit log tracking
- REST API for mobile apps

---

**Version:** 1.0.0  
**Last Updated:** April 2, 2026
