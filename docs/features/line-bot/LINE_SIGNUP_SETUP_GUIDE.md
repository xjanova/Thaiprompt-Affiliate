# LINE Membership Signup System - Setup Guide

## 🎯 Current Status

The LINE Membership Signup system has been **fully implemented** with all code, migrations, seeders, and services in place. However, **database setup is required** to complete the installation.

## ✅ What Has Been Completed

### 1. Code Implementation
- ✅ Database migrations (8 tables)
- ✅ 6 Models with full functionality
- ✅ 3 Core services (Signup, Flex Messages, AI, Rich Menu)
- ✅ 2 Controllers (Public webhook & Admin dashboard)
- ✅ All routes configured (web, api, admin)
- ✅ Admin dashboard with analytics
- ✅ Artisan command for Rich Menu setup
- ✅ Template seeder with 5 beautiful Flex Messages
- ✅ Complete documentation

### 2. Environment Configuration
- ✅ Composer dependencies installed
- ✅ Application key generated
- ✅ .env file configured
- ✅ LineOaSetting model fixed for graceful database handling

### 3. Git Commits
- Commit 1 (a28046a): Initial LINE membership signup implementation
- Commit 2 (ae3f409): Complete AI, Rich Menu, and Admin Dashboard
- Commit 3: LineSignupTemplateSeeder added to DatabaseSeeder
- Commit 4 (pending): Database connection fix

## ⚠️ What Needs To Be Done

### 1. Database Setup (REQUIRED)

The system needs a MySQL/MariaDB database. Choose one of these options:

#### Option A: Using Existing MySQL Server

If you have a MySQL server running:

```bash
# 1. Create database
mysql -u root -p -e "CREATE DATABASE admin_mlmtestthai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Update .env with correct credentials (already configured)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=admin_mlmtestthai
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 3. Run migrations
php artisan migrate --force

# 4. Seed templates
php artisan db:seed --class=LineSignupTemplateSeeder
```

#### Option B: Install MySQL Server

If MySQL is not installed:

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install mysql-server
sudo systemctl start mysql
sudo mysql_secure_installation

# CentOS/RHEL
sudo yum install mysql-server
sudo systemctl start mysqld
sudo mysql_secure_installation

# Then proceed with Option A steps
```

#### Option C: Using Docker

```bash
# Start MySQL container
docker run -d \
  --name mlmtestthai-mysql \
  -e MYSQL_ROOT_PASSWORD=secret \
  -e MYSQL_DATABASE=admin_mlmtestthai \
  -p 3306:3306 \
  mysql:8.0

# Update .env
# DB_PASSWORD=secret

# Run migrations and seeds
php artisan migrate --force
php artisan db:seed --class=LineSignupTemplateSeeder
```

### 2. LINE OA Configuration

```bash
# Update .env with LINE credentials
LINE_CHANNEL_ACCESS_TOKEN=your_channel_access_token
LINE_CHANNEL_SECRET=your_channel_secret
LINE_LIFF_ID=your_liff_id
```

### 3. Setup Rich Menu

```bash
# Create and upload Rich Menu to LINE
php artisan line:setup-signup-richmenu --set-default
```

### 4. Configure Webhook

Update your LINE Developers Console:
- Webhook URL: `https://yourdomain.com/api/webhook/line-membership-signup`
- Enable webhook
- Disable auto-reply messages

## 📊 System Architecture

### Database Tables Created
1. `line_signup_sessions` - Track signup progress
2. `line_signup_step_logs` - Log each step completion
3. `line_signup_conversations` - Store AI chat history
4. `line_signup_templates` - Flex Message templates
5. `line_signup_rewards` - Signup rewards
6. `line_signup_invitations` - Referral invitations
7. `line_signup_analytics` - Analytics data
8. `line_signup_webhook_logs` - Webhook logs

### Services
1. **LineMembershipSignupService** - Core signup logic (7-step flow)
2. **LineSignupFlexMessageService** - Beautiful UI messages
3. **LineSignupAiService** - AI conversation engine
4. **LineSignupRichMenuService** - Rich Menu builder

### Signup Flow (7 Steps)
1. **Welcome** - Introduction and benefits
2. **Name** - Collect full name
3. **Email** - Collect and validate email
4. **Phone** - Collect phone number
5. **OTP** - Verify phone via LINE message
6. **Password** - Set account password
7. **Referral** - Optional referral code
8. **Confirmation** - Create user & affiliate accounts

## 🚀 Quick Start (Once Database is Ready)

```bash
# 1. Run migrations
php artisan migrate --force

# 2. Seed all data (includes LINE signup templates)
php artisan db:seed --force

# 3. Setup Rich Menu
php artisan line:setup-signup-richmenu --set-default

# 4. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Test the system
php artisan serve
# Visit: http://localhost:8000/admin/line-membership-signup
```

## 📖 Documentation

- **Technical README**: `LINE_MEMBERSHIP_SIGNUP_README.md`
- **Usage Guide**: `LINE_SIGNUP_USAGE_GUIDE.md`
- **This Setup Guide**: `LINE_SIGNUP_SETUP_GUIDE.md`

## 🔧 Troubleshooting

### Error: Connection Refused

```bash
# Check if MySQL is running
sudo systemctl status mysql

# Start MySQL
sudo systemctl start mysql
```

### Error: Access Denied

```bash
# Reset MySQL root password
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password';
FLUSH PRIVILEGES;
EXIT;
```

### Error: Database Does Not Exist

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE admin_mlmtestthai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 🎉 Next Steps After Setup

1. **Test Signup Flow**: Send a message to your LINE OA
2. **Customize Templates**: Edit templates in admin dashboard
3. **Configure AI**: Set AI provider in .env
4. **Monitor Analytics**: Check admin dashboard for insights
5. **Setup Rewards**: Configure signup rewards
6. **Create Invitations**: Generate referral links

## 📞 Support

For issues or questions about the LINE Membership Signup system:
1. Check `LINE_SIGNUP_USAGE_GUIDE.md` for detailed usage instructions
2. Review `LINE_MEMBERSHIP_SIGNUP_README.md` for technical details
3. Check Laravel logs: `storage/logs/laravel.log`

---

**Status**: Ready for deployment once database is configured ✨
**Last Updated**: 2025-11-12
**Branch**: `claude/line-membership-signup-ai-011CV44jm1c6wQcdFm8AfUDJ`
