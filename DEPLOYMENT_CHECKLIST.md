# 📋 Production Deployment Checklist

เอกสารนี้เป็น checklist สำหรับการ deploy โปรเจค ThaiPrompt Marketplace ไปยัง production server

---

## 🔧 Pre-Deployment (ก่อน Deploy)

### Server Preparation
- [ ] เช็ค server requirements (PHP 8.1+, MySQL 8.0+, Redis, Nginx)
- [ ] ติดตั้ง software ที่จำเป็นทั้งหมด
- [ ] สร้าง database และ user
- [ ] ตั้งค่า firewall (UFW)
- [ ] สร้าง SSH key authentication
- [ ] สร้าง deployer user
- [ ] ตั้งค่า sudo permissions สำหรับ deployer

### Domain & SSL
- [ ] Point domain A record ไปที่ server IP
- [ ] ตรวจสอบ DNS propagation
- [ ] ติดตั้ง SSL certificate (Let's Encrypt)
- [ ] ทดสอบ HTTPS
- [ ] Force HTTPS redirect

### Configuration Files
- [ ] Clone repository ไปยัง `/var/www/thaiprompt`
- [ ] Copy `.env.example` เป็น `.env`
- [ ] แก้ไขค่าใน `.env` ให้ถูกต้อง
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_URL` (ใช้ domain จริง)
  - [ ] Database credentials
  - [ ] Redis configuration
  - [ ] Mail configuration
  - [ ] Payment gateway keys (Stripe, PromptPay)
  - [ ] Line OA credentials
  - [ ] AWS S3 (ถ้าใช้)
- [ ] Generate application key: `php artisan key:generate`

### Dependencies
- [ ] รัน `composer install --optimize-autoloader --no-dev`
- [ ] รัน `npm ci`
- [ ] รัน `npm run build`
- [ ] ตรวจสอบว่า `public/build` มีไฟล์

### Database
- [ ] รัน migrations: `php artisan migrate --force`
- [ ] Seed initial data (ถ้าจำเป็น): `php artisan db:seed --class=InitialDataSeeder`
- [ ] สร้าง admin user แรก
- [ ] ตรวจสอบ database structure

### File Permissions
- [ ] `sudo chown -R deployer:www-data /var/www/thaiprompt`
- [ ] `sudo chmod -R 775 /var/www/thaiprompt/storage`
- [ ] `sudo chmod -R 775 /var/www/thaiprompt/bootstrap/cache`
- [ ] สร้าง storage link: `php artisan storage:link`

### Web Server Configuration
- [ ] สร้างไฟล์ Nginx config `/etc/nginx/sites-available/thaiprompt`
- [ ] Enable site: `sudo ln -s /etc/nginx/sites-available/thaiprompt /etc/nginx/sites-enabled/`
- [ ] ทดสอบ config: `sudo nginx -t`
- [ ] Reload Nginx: `sudo systemctl reload nginx`

### Queue & Scheduler
- [ ] ติดตั้ง Supervisor
- [ ] สร้างไฟล์ config `/etc/supervisor/conf.d/thaiprompt.conf`
- [ ] Start workers: `sudo supervisorctl reread && sudo supervisorctl update`
- [ ] ตั้งค่า cron สำหรับ scheduler
- [ ] ทดสอบว่า queue worker ทำงาน

---

## 🚀 Deployment Process

### Initial Deployment
- [ ] รัน `php artisan config:cache`
- [ ] รัน `php artisan route:cache`
- [ ] รัน `php artisan view:cache`
- [ ] รัน `php artisan event:cache`
- [ ] Clear application cache: `php artisan cache:clear`
- [ ] Restart PHP-FPM: `sudo systemctl restart php8.1-fpm`

### Testing
- [ ] เข้าถึงเว็บไซต์ผ่าน browser
- [ ] ทดสอบ login
- [ ] ทดสอบ registration
- [ ] ทดสอบ file upload
- [ ] ทดสอบ payment gateway (Sandbox mode)
- [ ] ทดสอบ email sending
- [ ] ทดสอบ queue job execution
- [ ] ตรวจสอบ console errors

### Monitoring Setup
- [ ] ตั้งค่า Laravel Telescope (optional)
- [ ] ตั้งค่า log monitoring
- [ ] ตั้งค่า uptime monitoring (UptimeRobot)
- [ ] ตั้งค่า error tracking (Sentry)
- [ ] ตั้งค่า performance monitoring

---

## 🔄 Subsequent Deployments

### Pre-deployment Checks
- [ ] รัน tests locally: `php artisan test`
- [ ] Build assets locally: `npm run build`
- [ ] ตรวจสอบ changelog
- [ ] Backup database
- [ ] แจ้งผู้ใช้เกี่ยวกับ maintenance (ถ้าจำเป็น)

### Deployment Steps
- [ ] เปิด maintenance mode: `php artisan down`
- [ ] รัน deployment script: `bash deploy.sh`
- [ ] ตรวจสอบ logs: `tail -f storage/logs/laravel.log`
- [ ] ปิด maintenance mode: `php artisan up`

### Post-deployment Checks
- [ ] ทดสอบเว็บไซต์
- [ ] ตรวจสอบ error logs
- [ ] ตรวจสอบ queue status
- [ ] Monitor server resources (CPU, Memory)
- [ ] ยืนยันกับ stakeholders

---

## 🔐 Security Checklist

### Application Security
- [ ] `APP_DEBUG=false` ใน production
- [ ] `APP_ENV=production`
- [ ] Session timeout configured
- [ ] CSRF protection enabled
- [ ] XSS protection enabled
- [ ] SQL injection protection (ใช้ Eloquent ORM)
- [ ] Rate limiting configured
- [ ] File upload validation

### Server Security
- [ ] SSH key authentication only (disable password login)
- [ ] Firewall configured (UFW/iptables)
  - [ ] Allow port 22 (SSH)
  - [ ] Allow port 80 (HTTP)
  - [ ] Allow port 443 (HTTPS)
  - [ ] Block all other ports
- [ ] Fail2ban installed และ configured
- [ ] Regular security updates: `sudo apt update && sudo apt upgrade`
- [ ] Strong passwords everywhere
- [ ] Database user มี privileges เฉพาะที่จำเป็น

### Environment Variables
- [ ] `.env` ไม่ถูก track ใน git
- [ ] ไม่มี sensitive data ใน code
- [ ] API keys ปลอดภัย
- [ ] Database passwords strong

---

## 💾 Backup Strategy

### Database Backups
- [ ] Automated daily backups
- [ ] ทดสอบ restore process
- [ ] เก็บ backups ที่ remote location
- [ ] Retention policy (เก็บ 30 วัน)

### File Backups
- [ ] Backup `storage/app` directory
- [ ] Backup uploaded files
- [ ] Backup configuration files
- [ ] ทดสอบ restore process

---

## 📊 Performance Optimization

### Application Level
- [ ] OPcache enabled
- [ ] Route caching enabled
- [ ] Config caching enabled
- [ ] View caching enabled
- [ ] Query optimization
- [ ] Eager loading relationships
- [ ] Database indexing

### Server Level
- [ ] PHP-FPM optimized
- [ ] MySQL query cache
- [ ] Redis caching
- [ ] Nginx gzip compression
- [ ] Static asset caching headers
- [ ] CDN setup (optional)

### Monitoring
- [ ] Setup Laravel Horizon (ถ้าใช้ Redis queue)
- [ ] Monitor slow queries
- [ ] Monitor memory usage
- [ ] Monitor disk space
- [ ] Setup alerts

---

## 🔧 GitHub Actions Setup

### Repository Secrets
ตั้งค่า secrets ใน GitHub repository (Settings > Secrets and variables > Actions):

- [ ] `SERVER_HOST` - IP address ของ server
- [ ] `SERVER_USERNAME` - Username สำหรับ SSH (deployer)
- [ ] `SSH_PRIVATE_KEY` - Private key สำหรับ SSH
- [ ] `SERVER_PORT` - SSH port (default: 22)

### Auto-deployment
- [ ] Push ไปยัง `main` branch จะ auto-deploy
- [ ] ตรวจสอบ Actions tab หลัง push
- [ ] ตรวจสอบ deployment logs

---

## 📱 Notification Setup

### Monitoring Alerts
- [ ] Email notifications สำหรับ downtime
- [ ] Slack/Discord webhook สำหรับ deployments
- [ ] Error notifications (Sentry)
- [ ] Performance alerts

---

## 🐛 Troubleshooting

### Common Issues

#### 500 Internal Server Error
- [ ] ตรวจสอบ `storage/logs/laravel.log`
- [ ] ตรวจสอบ `/var/log/nginx/error.log`
- [ ] ตรวจสอบ file permissions
- [ ] Clear cache: `php artisan cache:clear`

#### Database Connection Failed
- [ ] ตรวจสอบ `.env` credentials
- [ ] ทดสอบ: `php artisan db:show`
- [ ] ตรวจสอบ MySQL status: `sudo systemctl status mysql`

#### Queue Not Working
- [ ] ตรวจสอบ: `sudo supervisorctl status`
- [ ] Restart: `sudo supervisorctl restart thaiprompt-worker:*`
- [ ] ดู logs: `tail -f storage/logs/worker.log`

#### High Server Load
- [ ] ตรวจสอบ processes: `htop`
- [ ] ตรวจสอบ slow queries
- [ ] ตรวจสอบ queue jobs
- [ ] Scale up resources ถ้าจำเป็น

---

## 📝 Post-Deployment Documentation

- [ ] Update deployment log
- [ ] Document any issues encountered
- [ ] Update runbook
- [ ] Notify team members
- [ ] Update monitoring dashboards

---

## ✅ Final Checklist

- [ ] Application accessible via HTTPS
- [ ] No errors in logs
- [ ] Database migrations completed
- [ ] Queue workers running
- [ ] Cron jobs scheduled
- [ ] Backups configured
- [ ] Monitoring active
- [ ] Security measures in place
- [ ] Performance optimized
- [ ] Documentation updated

---

## 🎉 Deployment Complete!

เมื่อทำทุกขั้นตอนเสร็จสิ้นแล้ว:

1. ✅ Application พร้อมใช้งาน
2. 📊 Monitoring systems active
3. 🔐 Security measures in place
4. 💾 Backups configured
5. 🚀 Ready for production traffic

---

## 📞 Emergency Contacts

**Server Issues:**
- Hosting provider support
- DevOps team

**Application Issues:**
- Development team lead
- Backend developers

**Security Issues:**
- Security team
- System administrator

---

## 🔗 Related Documents

- [DEPLOYMENT.md](./DEPLOYMENT.md) - Detailed deployment guide
- [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md) - Installation instructions
- [CONFIGURATION.md](./CONFIGURATION.md) - Configuration details
- [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) - API documentation
- [SECURITY_ENHANCEMENTS.md](./SECURITY_ENHANCEMENTS.md) - Security features

---

**Last Updated:** 2025-10-27
**Version:** 1.1.0
