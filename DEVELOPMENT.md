# 🔧 Development Guide - TP-Affiliate

## การเริ่มต้นพัฒนา

### Requirements
- PHP 8.1+
- Composer
- Node.js & NPM (optional)
- SQLite/MySQL

### Setup Development Environment

> **⚠️ Note:** This is a private repository. You need to authenticate first.
> See [AUTHENTICATION.md](AUTHENTICATION.md) for details.

```bash
# 1. Clone repository (requires authentication)
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Create database
touch database/database.sqlite

# 5. Run migrations
php artisan migrate

# 6. Set permissions
chmod -R 775 storage bootstrap/cache

# 7. Run development server
php artisan serve
```

## โครงสร้างโปรเจกต์

### Models
- `User` - ผู้ใช้ระบบ
- `Affiliate` - ข้อมูล Affiliate
- `Commission` - คอมมิชชั่น
- `Setting` - ตั้งค่าระบบ

### Controllers

#### Frontend Controllers
- `HomeController` - หน้าแรกและหน้า public

#### Auth Controllers
- `LoginController` - Login/Logout
- `RegisterController` - สมัครสมาชิก
- `SetupController` - Setup ครั้งแรก

#### Admin Controllers
- `DashboardController` - แดชบอร์ด
- `UserController` - จัดการผู้ใช้
- `AffiliateController` - จัดการ Affiliates
- `CommissionController` - จัดการคอมมิชชั่น
- `SettingsController` - ตั้งค่าระบบ

### Routes
- `routes/web.php` - Frontend & Auth routes
- `routes/admin.php` - Admin routes
- `routes/api.php` - API routes
- `routes/console.php` - Artisan commands

### Views
- `resources/views/layouts/` - Layout templates
- `resources/views/frontend/` - Frontend views
- `resources/views/auth/` - Auth views
- `resources/views/admin/` - Admin views

## Database Schema

### users
- id, name, email, password
- role (user/admin/super_admin)
- is_super_admin (boolean)
- affiliate_id (foreign key)

### affiliates
- id, user_id, parent_id
- referral_code (unique)
- level, total_referrals, total_earnings
- status (active/inactive/suspended)

### commissions
- id, affiliate_id, user_id
- order_id, amount, percentage
- type (direct/indirect/bonus)
- status (pending/approved/rejected/paid)
- approved_at, paid_at

### settings
- id, key, value
- type (string/boolean/integer/float/json)
- group (general/affiliate/commission)

## การพัฒนาฟีเจอร์ใหม่

### 1. สร้าง Model & Migration

```bash
php artisan make:model Product -m
```

### 2. สร้าง Controller

```bash
php artisan make:controller Admin/ProductController --resource
```

### 3. เพิ่ม Routes

```php
// routes/admin.php
Route::resource('products', ProductController::class);
```

### 4. สร้าง Views

```
resources/views/admin/products/
├── index.blade.php
├── create.blade.php
├── edit.blade.php
└── show.blade.php
```

## Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter TestName
```

## Code Style

- ใช้ PSR-12 coding standard
- ใช้ Laravel best practices
- Comment เป็นภาษาไทยหรือ ภาษาอังกฤษก็ได้

## Git Workflow

```bash
# Create feature branch
git checkout -b feature/new-feature

# Commit changes
git add .
git commit -m "Add new feature"

# Push to remote
git push origin feature/new-feature

# Create Pull Request
```

## Debugging

### Enable Debug Mode
```
APP_DEBUG=true
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Performance Optimization

### Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database Optimization
- ใช้ indexes ที่เหมาะสม
- ใช้ eager loading (`with()`) เพื่อป้องกัน N+1 queries
- ใช้ pagination สำหรับข้อมูลจำนวนมาก

## Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Setup SSL/TLS
- [ ] Configure backup strategy
- [ ] Setup monitoring

## API Development

### Create API Endpoint

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::get('/affiliates', [AffiliateController::class, 'index']);
});
```

### Response Format

```json
{
    "success": true,
    "data": {},
    "message": "Success"
}
```

## Troubleshooting

### Common Issues

1. **Permission Denied**
```bash
chmod -R 775 storage bootstrap/cache
```

2. **Database Connection Error**
```bash
# Check .env file
# Verify database file exists
touch database/database.sqlite
```

3. **Route Not Found**
```bash
php artisan route:clear
php artisan cache:clear
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev)

---

Happy Coding! 🚀
