# FTI Pak Tax Management - cPanel Upload & Installation Guide

**For Users with NO Coding Knowledge**

This guide walks you through uploading the complete application to your cPanel hosting and getting it running in just a few clicks.

---

## PART 1: Prepare Your Hosting

### 1.1 Create a Database
1. Log into your cPanel
2. Find and click **"MySQL Databases"** or **"Databases"**
3. Under "Create New Database":
   - Database Name: `fti_pak_tax` (or any name you prefer)
   - Click **"Create Database"**
4. Write down the database name

### 1.2 Create a Database User
1. Still in MySQL Databases section, find "MySQL Users"
2. Fill in:
   - Username: `fti_user` (or any name)
   - Password: (create a strong password)
   - Confirm Password
3. Click **"Create User"**
4. Write down: Database User and Password

### 1.3 Add User to Database
1. Under "Add User to Database":
   - User: Select `fti_user`
   - Database: Select `fti_pak_tax`
   - Click **"Add"**
2. Check ALL boxes for permissions
3. Click **"Make Changes"**

---

## PART 2: Upload Files to Server

### 2.1 Download and Extract
1. You should have received a ZIP file: `fti_pak_tax.zip`
2. On your computer, extract (unzip) this file
3. You'll see a folder named `fti_pak_tax` with many files inside

### 2.2 Upload via File Manager (Easiest)
1. Log into cPanel
2. Click **"File Manager"**
3. Click **"public_html"** folder
4. Click **"Upload"** button
5. Select all files from the extracted `fti_pak_tax` folder
   - You can drag and drop them
   - Or click "Select Files" to browse
6. Wait for upload to complete (may take a few minutes)

### 2.2 Alternative: Upload via FTP
1. Download an FTP client (e.g., FileZilla - free)
2. Get FTP credentials from cPanel → FTP Accounts
3. Connect to your server
4. Navigate to `public_html`
5. Drag all files from the extracted folder to `public_html`

---

## PART 3: Configure the Application

### 3.1 Rename Configuration File
1. In File Manager (cPanel), navigate to where you uploaded the files
2. Find the file named `.env.example`
3. Right-click → **Rename** → Change to `.env` → Confirm
   - If you don't see `.env.example`, enable "Show Hidden Files" (usually in settings)

### 3.2 Edit Configuration
1. Right-click `.env` file → **Edit**
2. Find and update these lines:

   **DATABASE SECTION:**
   ```
   DB_DATABASE=fti_pak_tax    (use the database name from Step 1.1)
   DB_USERNAME=fti_user       (use the user from Step 1.2)
   DB_PASSWORD=YourPassword   (use the password from Step 1.2)
   ```

   **EMAIL SECTION (for sending reminders):**
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_FROM_ADDRESS="noreply@yourdomain.com"
   ```
   
   *(Gmail Setup: Go to myaccount.google.com → Security → App Passwords)*

3. Leave other settings as-is for now
4. Click **"Save Changes"**

---

## PART 4: Initialize the Database

### 4.1 Generate App Key
1. In cPanel, click **"Terminal"** (under Advanced)
2. Type this command and press Enter:
   ```
   cd public_html && php artisan key:generate
   ```
3. You should see: `Application key set successfully`

### 4.2 Run Setup Wizard
1. In Terminal, type and press Enter:
   ```
   php artisan app:setup
   ```
2. Follow the prompts:
   - **Admin user name:** (e.g., "Administrator")
   - **Admin email address:** (your email)
   - **Admin password:** (create a strong password - you'll use this to login)
3. Wait for completion

✅ **Your database is now ready!**

---

## PART 5: Configure Automated Tasks (Cron Jobs)

The system runs two background jobs hourly to fetch FBR notices and send reminders.

### 5.1 Add Cron Jobs
1. In cPanel, go to **"Cron Jobs"**
2. Under "Add New Cron Job":

   **JOB 1 - Main Scheduler (Required):**
   - Common Settings: **Every Hour**
   - Command:
     ```
     cd /home/YOUR_USERNAME/public_html && php artisan schedule:run >> /dev/null 2>&1
     ```
   - Click **"Add New Cron Job"**

   *Replace YOUR_USERNAME with your actual cPanel username (you can find it in cPanel dashboard)*

3. **That's it!** The scheduler will automatically run the notice fetching and reminders

---

## PART 6: Access Your Application

### 6.1 Login
1. Open your browser
2. Go to: `http://yourdomain.com`
3. Login with:
   - Email: (the admin email you created)
   - Password: (the admin password you created)

### 6.2 You're In!
You should see the Dashboard with:
- Total Clients counter
- Active Services counter
- Pending Tasks counter
- Recent FBR Notices

---

## COMMON TASKS

### Add Your First Client
1. Click **"Clients"** in left menu
2. Click **"+ Add Client"**
3. Fill in:
   - Name
   - Email
   - Contact Number
   - Status (Individual/AOP/Company)
   - FBR Username & Password (if available)
4. Select Active Services (e.g., Income Tax Return)
5. Click **"Save"**

### Create a Task
1. Click **"Tasks"** → **"+ Create Task"**
2. Fill in task details
3. Assign to team members
4. Set deadline
5. Click **"Save"**

### View FBR Notices
1. Click **"FBR Notices"**
2. Notices will appear automatically (if fetching is configured)
3. Click on notice to see details

### Add a Team Member
1. Click **"Employees"** (Admin only)
2. Click **"+ Add Employee"**
3. Fill in name, email, password
4. Select role (Consultant/Staff)
5. Click **"Save"**

---

## TROUBLESHOOTING

### Database Connection Error
**Error:** "Unable to connect to database"
**Solution:**
1. Go to cPanel → MySQL Databases
2. Verify user permissions are set correctly
3. Double-check DB_USERNAME and DB_PASSWORD in `.env` file

### 500 Error / Blank Page
**Solution:**
1. Check `storage/logs/laravel.log` file in File Manager
2. Look at the error message
3. Most common: Wrong database credentials

### Emails Not Sending
**Solution:**
1. Verify MAIL_USERNAME and MAIL_PASSWORD in `.env`
2. If using Gmail, ensure App Password is used (not regular password)
3. Check if your server allows outgoing email (cPanel may restrict it)

### Cron Jobs Not Running
**Solution:**
1. In cPanel, click Cron Jobs
2. Check "Standard Output" and "Standard Error" logs
3. Verify the path `/home/YOUR_USERNAME/public_html` is correct

---

## IMPORTANT SECURITY NOTES

1. **Backup Your Database** regularly via cPanel → Backups
2. **Keep `.env` File Private** - Never share its contents
3. **Use Strong Passwords** for all accounts
4. **Enable HTTPS** on your domain (usually free via AutoSSL in cPanel)
5. **Hide .env File** - It shouldn't be accessible via browser

---

## SUPPORT

If you encounter issues:
1. Check the troubleshooting section above
2. Look at error logs in cPanel Terminal
3. Ensure all steps were completed in order
4. Verify database credentials are correct

---

## Next: Microsoft Email Integration (Optional)

To enable automated FBR notice fetching from your Microsoft/Outlook email:

1. Create Azure App: https://portal.azure.com
2. Register new application
3. Add permissions: Mail.Read
4. Create client secret
5. Update in app settings:
   - MICROSOFT_CLIENT_ID
   - MICROSOFT_CLIENT_SECRET

*This allows the system to automatically pull FBR emails and display them in a dedicated section.*

---

**Version:** 1.0  
**Last Updated:** April 2, 2026
