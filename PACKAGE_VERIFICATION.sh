#!/bin/bash

# FTI Pak Tax Management - File Package Verification Checklist
# This script verifies all required files are present before packaging

echo "=================================================="
echo "FTI Pak Tax Management System - Package Verification"
echo "=================================================="
echo ""

ERRORS=0

# Check critical files
check_file() {
    if [ -f "$1" ]; then
        echo "✓ $1"
    else
        echo "✗ MISSING: $1"
        ERRORS=$((ERRORS + 1))
    fi
}

check_dir() {
    if [ -d "$1" ]; then
        echo "✓ $1/"
    else
        echo "✗ MISSING: $1/"
        ERRORS=$((ERRORS + 1))
    fi
}

echo "📦 Core Application Files:"
check_file ".env.example"
check_file "composer.json"
check_file "artisan"
check_file "routes/web.php"

echo ""
echo "📂 Directory Structure:"
check_dir "app/Models"
check_dir "app/Http/Controllers"
check_dir "app/Console/Commands"
check_dir "database/migrations"
check_dir "database/seeders"
check_dir "resources/views"
check_dir "routes"
check_dir "storage"
check_dir "public"

echo ""
echo "🗂️ Models (8 required):"
check_file "app/Models/User.php"
check_file "app/Models/Role.php"
check_file "app/Models/Client.php"
check_file "app/Models/Service.php"
check_file "app/Models/Task.php"
check_file "app/Models/FbrNotice.php"
check_file "app/Models/Reminder.php"
check_file "app/Models/Notification.php"
check_file "app/Models/MicrosoftEmailSettings.php"

echo ""
echo "🎮 Controllers (6 required):"
check_file "app/Http/Controllers/ClientController.php"
check_file "app/Http/Controllers/TaskController.php"
check_file "app/Http/Controllers/EmployeeController.php"
check_file "app/Http/Controllers/FbrNoticeController.php"
check_file "app/Http/Controllers/NotificationController.php"
check_file "app/Http/Controllers/DashboardController.php"

echo ""
echo "⚙️ Console Commands (3 required):"
check_file "app/Console/Commands/ProcessReminderJob.php"
check_file "app/Console/Commands/FetchFbrNotices.php"
check_file "app/Console/Commands/SetupApplication.php"
check_file "app/Console/Kernel.php"

echo ""
echo "🗄️ Database Migrations (12 required):"
check_file "database/migrations/2026_04_02_000001_create_roles_table.php"
check_file "database/migrations/2026_04_02_000002_create_role_user_table.php"
check_file "database/migrations/2026_04_02_000003_create_clients_table.php"
check_file "database/migrations/2026_04_02_000004_create_shareholders_table.php"
check_file "database/migrations/2026_04_02_000005_create_services_table.php"
check_file "database/migrations/2026_04_02_000006_create_client_services_table.php"
check_file "database/migrations/2026_04_02_000007_create_tasks_table.php"
check_file "database/migrations/2026_04_02_000008_create_task_user_table.php"
check_file "database/migrations/2026_04_02_000009_create_fbr_notices_table.php"
check_file "database/migrations/2026_04_02_000010_create_reminders_table.php"
check_file "database/migrations/2026_04_02_000011_create_notifications_table.php"
check_file "database/migrations/2026_04_02_000012_create_microsoft_email_settings_table.php"

echo ""
echo "📚 Seeders (2 required):"
check_file "database/seeders/RoleSeeder.php"
check_file "database/seeders/ServiceSeeder.php"

echo ""
echo "🎨 Views (3 minimum):"
check_file "resources/views/layouts/app.blade.php"
check_file "resources/views/dashboard.blade.php"
check_file "resources/views/clients/index.blade.php"
check_file "resources/views/fbr-notices/index.blade.php"
check_file "resources/views/mini-apps/index.blade.php"

echo ""
echo "📖 Documentation Files:"
check_file "README.md"
check_file "DEPLOYMENT_GUIDE.md"
check_file "CPANEL_UPLOAD_GUIDE.md"
check_file "QUICK_START.md"

echo ""
echo "🔧 Configuration:"
check_file "setup.sh"

echo ""
echo "=================================================="
if [ $ERRORS -eq 0 ]; then
    echo "✅ All files present! Package ready to distribute."
    echo ""
    echo "📦 Package Size:"
    du -sh .
    echo ""
    echo "📋 Ready for:"
    echo "  1. Upload to cPanel"
    echo "  2. Create ZIP distribution"
    echo "  3. Send to client"
else
    echo "⚠️  $ERRORS file(s) missing!"
    echo "Please review and recreate missing files."
fi
echo "=================================================="
