# 🚀 LINE Membership Signup - Production Deployment Checklist

## 📋 Pre-Deployment Verification

### ✅ Code & Files (All Complete)

#### Services (6 files)
- [x] `LineMembershipSignupService.php` - Core signup logic (7-step flow)
- [x] `LineSignupAiService.php` - AI conversation engine
- [x] `LineSignupAnalyticsService.php` - Analytics and metrics
- [x] `LineSignupFlexMessageService.php` - Flex Message templates
- [x] `LineSignupRichMenuService.php` - Rich Menu management
- [x] `LineSignupService.php` - Legacy service integration

#### Models (7 files)
- [x] `LineSignupSession.php` - Main session tracking
- [x] `LineSignupStepLog.php` - Step completion logs
- [x] `LineSignupConversation.php` - AI chat history
- [x] `LineSignupTemplate.php` - Flex Message templates
- [x] `LineSignupReward.php` - Reward management
- [x] `LineSignupInvitation.php` - Referral system
- [x] `LineSignupFlow.php` - Flow configuration

#### Controllers (4 files)
- [x] `LineMembershipSignupController.php` - Public webhook & pages
- [x] `LineMembershipSignupAdminController.php` - Admin dashboard
- [x] `LineSignupController.php` - Legacy controller
- [x] `LineSignupFlowController.php` - Admin flow management

#### Migrations (2 files)
- [x] `2025_11_08_000002_create_line_signup_flows_table.php`
- [x] `2025_11_12_000001_create_line_membership_signup_system.php`

#### Seeders (1 file)
- [x] `LineSignupTemplateSeeder.php` - Pre-built Flex Message templates

#### Routes (Verified)
- [x] API routes in `routes/api.php` (1 webhook)
- [x] Web routes in `routes/web.php` (5 routes)
- [x] Admin routes in `routes/admin.php` (13 routes)

#### Middleware (Registered)
- [x] `LineSignupThrottle` - Rate limiting for signup endpoints
- [x] Registered in `bootstrap/app.php` as `line.signup.throttle`

#### Documentation (Complete)
- [x] `LINE_MEMBERSHIP_SIGNUP_README.md` - Technical documentation
- [x] `LINE_SIGNUP_SETUP_GUIDE.md` - Setup instructions
- [x] `LINE_SIGNUP_USAGE_GUIDE.md` - Usage guide
- [x] `README.md` - Updated with feature highlights

#### Git Status
- [x] All changes committed (7 commits)
- [x] Pushed to branch: `claude/line-membership-signup-ai-011CV44jm1c6wQcdFm8AfUDJ`
- [x] Ready for deployment

---

## 🗄️ Database Setup Required

### 1. Database Connection
**Status**: ⚠️ NOT CONFIGURED YET

```bash
# Check MySQL is running
sudo systemctl status mysql

# If not installed, install MySQL
sudo apt install mysql-server  # Ubuntu/Debian
sudo yum install mysql-server  # CentOS/RHEL
```

### 2. Create Database
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE admin_mlmtestthai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Grant privileges
mysql -u root -p -e "GRANT ALL PRIVILEGES ON admin_mlmtestthai.* TO 'root'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

### 3. Update .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_mlmtestthai
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### 4. Run Migrations
```bash
# Run all migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

### 5. Seed Templates
```bash
# Seed LINE signup templates
php artisan db:seed --class=LineSignupTemplateSeeder --force

# Or seed all
php artisan db:seed --force
```

---

## ⚙️ LINE OA Configuration

### 1. LINE Developers Console Setup

1. Go to https://developers.line.biz/console/
2. Select your Provider and Channel
3. Go to **Messaging API** tab

### 2. Get Credentials
```env
# Add to .env
LINE_CHANNEL_ACCESS_TOKEN=your_long_lived_channel_access_token
LINE_CHANNEL_SECRET=your_channel_secret
LINE_LIFF_ID=your_liff_id_optional
```

### 3. Configure Webhook
**Webhook URL**:
```
https://your-domain.com/api/webhook/line-membership-signup
```

**Settings**:
- ✅ Enable "Use webhook"
- ✅ Disable "Auto-reply messages" (to use AI)
- ✅ Enable "Webhooks"
- ✅ Add webhook URL
- ✅ Click "Verify" to test

### 4. Setup Rich Menu
```bash
# Create and set as default
php artisan line:setup-signup-richmenu --set-default

# Or force recreate
php artisan line:setup-signup-richmenu --force --set-default
```

---

## 🧪 Testing Checklist

### 1. Basic Health Checks
```bash
# Test application is running
curl http://your-domain.com/up

# Test API route exists
curl -X POST http://your-domain.com/api/webhook/line-membership-signup \
  -H "Content-Type: application/json" \
  -d '{"events":[]}'
```

### 2. Admin Dashboard Access
- [ ] Navigate to `/admin/line-membership-signup`
- [ ] Verify dashboard loads
- [ ] Check analytics widgets display
- [ ] Test session listing
- [ ] Test template management

### 3. LINE OA Testing
- [ ] Add LINE OA as friend
- [ ] Send message "สมัครสมาชิก"
- [ ] Verify bot responds with Welcome message
- [ ] Test step 1 (Name input)
- [ ] Test step 2 (Email input)
- [ ] Test OTP sending
- [ ] Complete full signup flow

### 4. Rich Menu Testing
- [ ] Verify Rich Menu displays
- [ ] Test "สมัครสมาชิก" button
- [ ] Test other menu buttons
- [ ] Verify postback actions work

---

## 🔐 Security Verification

### 1. Environment Variables
- [ ] All secrets in .env (not in code)
- [ ] LINE tokens secured
- [ ] Database credentials secured
- [ ] APP_KEY generated

### 2. Middleware
- [ ] Rate limiting active on webhook
- [ ] CSRF protection enabled
- [ ] Input validation working
- [ ] OTP expiration working (5 min)
- [ ] Max OTP attempts enforced (5 tries)

### 3. Access Control
- [ ] Admin routes protected
- [ ] Webhook signature verification
- [ ] Session token validation
- [ ] User authentication working

---

## 📊 Monitoring Setup

### 1. Logs to Monitor
```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep "LINE Membership"

# Web server logs
tail -f /var/log/nginx/access.log | grep "/api/webhook/line-membership-signup"

# Database queries (if slow query log enabled)
tail -f /var/log/mysql/slow-queries.log
```

### 2. Key Metrics to Track
- [ ] Total signup sessions
- [ ] Completion rate
- [ ] Average completion time
- [ ] Drop-off rate per step
- [ ] OTP success rate
- [ ] Webhook response time

### 3. Admin Dashboard Metrics
- Navigate to `/admin/line-membership-signup` to view:
  - Daily signups chart
  - Step-by-step funnel
  - Recent sessions
  - Top referrers
  - Completion rate trends

---

## 🚀 Deployment Steps

### Option 1: Using deploy.sh (Recommended)
```bash
# Make sure you're on the correct branch
git checkout claude/line-membership-signup-ai-011CV44jm1c6wQcdFm8AfUDJ

# Run deployment
./deploy.sh claude/line-membership-signup-ai-011CV44jm1c6wQcdFm8AfUDJ

# Script will:
# - Enable maintenance mode
# - Backup database
# - Pull latest code
# - Run composer install
# - Run migrations
# - Clear/optimize caches
# - Disable maintenance mode
```

### Option 2: Manual Deployment
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Pull latest code
git pull origin claude/line-membership-signup-ai-011CV44jm1c6wQcdFm8AfUDJ

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Run migrations
php artisan migrate --force

# 5. Seed templates
php artisan db:seed --class=LineSignupTemplateSeeder --force

# 6. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Optimize
php artisan optimize

# 8. Disable maintenance mode
php artisan up
```

---

## ✅ Post-Deployment Verification

### 1. Immediate Checks (First 5 minutes)
- [ ] Application loads without errors
- [ ] Admin dashboard accessible
- [ ] Webhook endpoint responding
- [ ] No PHP errors in logs
- [ ] Database migrations ran successfully

### 2. Functional Tests (First 30 minutes)
- [ ] Complete one full signup via LINE
- [ ] Verify user created in database
- [ ] Check affiliate account created
- [ ] Verify rewards granted
- [ ] Test admin dashboard features

### 3. Monitor (First 24 hours)
- [ ] Check error logs hourly
- [ ] Monitor signup completion rate
- [ ] Track webhook response times
- [ ] Watch for OTP delivery issues
- [ ] Monitor database performance

---

## 🐛 Rollback Plan

### If Issues Occur

#### Quick Rollback
```bash
# 1. Enable maintenance mode
php artisan down

# 2. Rollback code
git reset --hard e60d178  # Previous stable commit

# 3. Rollback database
php artisan migrate:rollback --step=2

# 4. Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 5. Disable maintenance mode
php artisan up
```

#### Database Backup Restore
```bash
# If you backed up before deployment
mysql -u root -p admin_mlmtestthai < backup_20XX_XX_XX.sql
```

---

## 📞 Support & Troubleshooting

### Common Issues

#### 1. OTP Not Sending
**Symptoms**: Users don't receive OTP messages

**Solutions**:
- Check LINE Channel Access Token is valid
- Verify webhook is configured correctly
- Check rate limits (5,000 messages/hour)
- Review logs: `tail -f storage/logs/laravel.log`

#### 2. Rich Menu Not Showing
**Symptoms**: Users don't see the Rich Menu

**Solutions**:
```bash
# Recreate Rich Menu
php artisan line:setup-signup-richmenu --force --set-default

# Verify Rich Menus
curl -X GET https://api.line.me/v2/bot/richmenu/list \
  -H "Authorization: Bearer YOUR_CHANNEL_ACCESS_TOKEN"
```

#### 3. Webhook Not Responding
**Symptoms**: Bot doesn't respond to messages

**Solutions**:
- Check webhook URL in LINE Console
- Verify SSL certificate is valid
- Test webhook manually
- Check nginx/apache logs
- Verify `.env` has correct credentials

#### 4. Database Connection Failed
**Symptoms**: Migration fails or app crashes

**Solutions**:
- Check MySQL is running: `sudo systemctl status mysql`
- Verify `.env` database credentials
- Create database if missing
- Check MySQL grants and permissions

### Log Files
```bash
# Application logs
storage/logs/laravel.log

# Nginx logs
/var/log/nginx/access.log
/var/log/nginx/error.log

# Apache logs (if using Apache)
/var/log/apache2/access.log
/var/log/apache2/error.log

# MySQL logs
/var/log/mysql/error.log
```

---

## 📈 Success Metrics

### Target KPIs (First 30 Days)

- **Signup Completion Rate**: > 60%
- **Average Completion Time**: < 5 minutes
- **OTP Success Rate**: > 95%
- **Daily Active Sessions**: Track and optimize
- **User Satisfaction**: Monitor feedback

---

## 🎓 Training & Documentation

### For Admin Team
- Review `LINE_SIGNUP_USAGE_GUIDE.md`
- Practice using Admin Dashboard
- Learn to interpret Analytics
- Understand how to grant rewards
- Know how to export data

### For Support Team
- Review common issues in this checklist
- Learn signup flow (7 steps)
- Understand OTP process
- Know when to escalate
- Access to logs and monitoring

---

## ✨ Final Checklist

Before marking deployment as COMPLETE:

- [ ] Database configured and migrations run
- [ ] LINE OA credentials configured
- [ ] Webhook URL set in LINE Console
- [ ] Rich Menu created and active
- [ ] At least one successful test signup completed
- [ ] Admin dashboard accessible and functional
- [ ] Monitoring and alerts configured
- [ ] Team trained on new features
- [ ] Documentation distributed
- [ ] Rollback plan tested and ready

---

**Deployment Branch**: `claude/line-membership-signup-ai-011CV44jm1c6wQcdFm8AfUDJ`
**Total Commits**: 7
**Last Commit**: `d311831` - docs: Add LINE Membership Signup feature to main README
**Status**: ✅ Code Complete, ⚠️ Database Setup Required

---

**Created**: 2025-11-12
**Feature**: LINE Membership Signup System with AI
**Version**: 1.0.0
