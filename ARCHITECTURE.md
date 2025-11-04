# TP-Affiliate System Architecture

## ภาพรวมของระบบ (Ecosystem Overview)

ระบบ TP-Affiliate ประกอบด้วย 3 repositories หลักที่ทำงานร่วมกัน:

```
┌─────────────────────────────────────────────────────────────────┐
│                    TP-Affiliate Ecosystem                        │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────┐    ┌──────────────────────────┐
│  Development Repo        │    │  License Server          │
│  Thaiprompt-Affiliate    │───▶│  TpLicense (WordPress)   │
│  (Private)               │    │  (Private)               │
└──────────────────────────┘    └──────────────────────────┘
           │                              │
           │ Deploy                       │ Validate
           ▼                              ▼
┌──────────────────────────┐    ┌──────────────────────────┐
│  Distribution Repo       │    │  Customer Installation   │
│  TP-Affiliate            │───▶│  (End User Server)       │
│  (Public/Private)        │    │                          │
└──────────────────────────┘    └──────────────────────────┘
```

---

## Repository Structure

### 1. **xjanova/Thaiprompt-Affiliate** (Development Repository)

**Purpose:** Main development repository สำหรับทีมพัฒนา

**Location:** `https://github.com/xjanova/Thaiprompt-Affiliate`

**Access:** Private

**Contains:**
- Full source code พร้อม development tools
- Git history แบบเต็มรูปแบบ
- Development configuration files
- Testing และ debugging tools
- Scripts สำหรับ deploy ไป distribution repo

**Key Files:**
```
Thaiprompt-Affiliate/
├── app/                    # Laravel application
├── config/                 # Configuration files
│   ├── license.php        # License configuration
│   └── version.php        # Version configuration
├── scripts/
│   └── deploy-to-distribution.sh  # Deploy script
├── .github/
│   └── workflows/
│       └── deploy-distribution.yml  # Auto-deploy workflow
├── ARCHITECTURE.md         # This file
├── CLAUDE_CONTEXT.md       # Claude AI context
└── docs/
    └── ECOSYSTEM.md        # Ecosystem documentation
```

**Branches:**
- `main` - Production-ready code
- `develop` - Development branch
- `feature/*` - Feature branches
- `claude/*` - Claude AI working branches

---

### 2. **xjanova/TP-Affiliate** (Distribution Repository)

**Purpose:** Repository สำหรับลูกค้าที่ซื้อระบบ (ต้องสร้างใหม่)

**Location:** `https://github.com/xjanova/TP-Affiliate`

**Access:** Private (แนะนำ) หรือ Public

**Contains:**
- Optimized/compiled code
- install.sh - Installation script
- Production-ready configuration
- CHANGELOG.md - Version history
- README.md - Installation guide
- ไม่มี sensitive files (.env, credentials, etc.)

**Key Files:**
```
TP-Affiliate/
├── app/                    # Laravel application (compiled)
├── public/                 # Public assets (minified)
├── install.sh             # Installation script
├── CHANGELOG.md           # Version changelog
├── VERSION                # Current version
├── README.md              # Installation guide
└── .installation/
    ├── requirements.json  # System requirements
    └── checksums.json     # File integrity checksums
```

**Update Flow:**
```
Developer push to main
    ↓
GitHub Actions triggered
    ↓
Run tests & build
    ↓
Deploy to TP-Affiliate repo
    ↓
Create release with changelog
    ↓
Customers can update
```

---

### 3. **xjanova/TpLicense** (License Management Plugin)

**Purpose:** WordPress plugin สำหรับจัดการ licenses

**Location:** `https://github.com/xjanova/TpLicense`

**Access:** Private

**Technology:** WordPress Plugin (PHP)

**Contains:**
- License management system
- REST API endpoints สำหรับ validation
- Admin dashboard สำหรับจัดการ licenses
- Customer management
- IP whitelist management
- Analytics และ reporting

**Key Files:**
```
TpLicense/
├── tp-license.php          # Main plugin file
├── includes/
│   ├── api/
│   │   ├── class-license-api.php       # REST API endpoints
│   │   ├── class-activation-api.php    # Activation API
│   │   └── class-validation-api.php    # Validation API
│   ├── admin/
│   │   ├── class-admin-dashboard.php   # Admin UI
│   │   ├── class-license-manager.php   # License CRUD
│   │   └── class-customer-manager.php  # Customer CRUD
│   ├── core/
│   │   ├── class-license-validator.php # Validation logic
│   │   ├── class-ip-manager.php        # IP whitelist
│   │   └── class-encryption.php        # Encryption helpers
│   └── database/
│       └── class-schema.php            # Database schema
├── README.md
└── CLAUDE_CONTEXT.md
```

**Database Tables:**
```sql
wp_tp_licenses
├── id
├── license_key (unique)
├── customer_id
├── product_id
├── status (active/inactive/expired)
├── expires_at
├── max_activations
├── created_at
└── updated_at

wp_tp_license_activations
├── id
├── license_id
├── domain
├── ip_address
├── installation_id
├── activated_at
└── last_validated_at

wp_tp_license_ip_whitelist
├── id
├── license_id
├── ip_address
├── description
├── created_at
└── updated_at

wp_tp_customers
├── id
├── name
├── email
├── company
├── created_at
└── updated_at
```

---

## API Endpoints (TpLicense WordPress Plugin)

### Base URL
```
https://xman4289.com/wp-json/tp-license/v1/
```

### Endpoints

#### 1. **License Activation**
```
POST /activate
```

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com",
  "ip": "123.45.67.89",
  "installation_id": "uuid-xxxx-xxxx",
  "php_version": "8.1.0",
  "laravel_version": "11.0"
}
```

**Response (Success):**
```json
{
  "success": true,
  "activation": {
    "id": 123,
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com",
    "activated_at": "2025-11-04 10:00:00"
  },
  "license": {
    "status": "active",
    "expires_at": "2026-11-04",
    "customer": {
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

**Response (Error - IP Not Allowed):**
```json
{
  "success": false,
  "code": "ip_not_allowed",
  "message": "Your IP address is not authorized for this license"
}
```

#### 2. **License Validation**
```
POST /validate
```

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com",
  "ip": "123.45.67.89",
  "installation_id": "uuid-xxxx-xxxx"
}
```

**Response:**
```json
{
  "success": true,
  "license": {
    "key": "XXXX-XXXX-XXXX-XXXX",
    "status": "active",
    "domain": "example.com",
    "expires_at": "2026-11-04",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "activation_count": 1,
    "max_activations": 1,
    "created_at": "2025-11-04"
  }
}
```

#### 3. **License Deactivation**
```
POST /deactivate
```

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "License deactivated successfully"
}
```

#### 4. **Check IP Whitelist**
```
POST /check-ip
```

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "ip": "123.45.67.89"
}
```

**Response:**
```json
{
  "success": true,
  "allowed": true,
  "message": "IP is whitelisted"
}
```

---

## Security Flow

### Installation Security

```
Customer downloads install.sh
    ↓
Run install.sh
    ↓
Check system requirements
    ↓
Get server IP address
    ↓
Check IP with License Server ──▶ TpLicense WordPress Plugin
    ↓                                 ↓
    ├─ IP Not Allowed ──────────────▶ Installation Failed
    │
    └─ IP Allowed
        ↓
    Download from TP-Affiliate repo
        ↓
    Verify checksums
        ↓
    Install Laravel app
        ↓
    Run Setup Wizard
        ↓
    Enter License Key
        ↓
    Activate License ──────────────▶ TpLicense API
        ↓                               ↓
    License Activated                Validate & Store
        ↓
    Seed demo data (optional)
        ↓
    Setup Admin credentials
        ↓
    Installation Complete
```

### Runtime License Validation

```
Every 24 hours (configurable)
    ↓
Laravel App: LicenseService::validate()
    ↓
POST to TpLicense API /validate
    ↓
TpLicense checks:
    ├─ License exists?
    ├─ License active?
    ├─ Domain matches?
    ├─ IP in whitelist?
    ├─ Not expired?
    └─ Installation ID matches?
    ↓
Return validation result
    ↓
Cache for 7 days (configurable)
```

### Update Security

```
Admin clicks "Check Update"
    ↓
Validate License first
    ↓
    ├─ Invalid ──▶ Cannot update
    │
    └─ Valid
        ↓
    Check GitHub for new version
        ↓
    Show changelog
        ↓
    Admin clicks "Update"
        ↓
    Enable maintenance mode
        ↓
    Backup database
        ↓
    git fetch from TP-Affiliate repo
        ↓
    git checkout v{new_version}
        ↓
    composer install
        ↓
    php artisan migrate
        ↓
    php artisan optimize
        ↓
    Disable maintenance mode
        ↓
    Update complete
```

---

## Data Flow

### Customer Purchase Flow

```
Customer purchases on xman4289.com
    ↓
WooCommerce order created
    ↓
TpLicense Plugin:
    ├─ Generate unique license key
    ├─ Create customer record
    ├─ Create license record
    └─ Send email with:
        ├─ License key
        ├─ Download link (install.sh)
        └─ Installation guide
    ↓
Customer receives email
```

### Installation Data Flow

```
install.sh downloads from:
    ↓
TP-Affiliate/releases/latest/install.sh
    ↓
Install process connects to:
    ├─ TpLicense API (IP check, activation)
    └─ TP-Affiliate repo (download code)
    ↓
Installation creates:
    ├─ .env file (with license key)
    ├─ storage/app/installation_id.txt
    └─ Database with seeded data
```

### Update Data Flow

```
Admin UI checks:
    ↓
GitHub API: TP-Affiliate/releases/latest
    ↓
Compare with VERSION file
    ↓
If new version:
    ├─ Show in admin dashboard
    ├─ Display changelog
    └─ Enable update button
    ↓
Update process:
    ├─ Validate license
    ├─ Pull from TP-Affiliate repo
    ├─ Run migrations
    └─ Clear caches
```

---

## Version Management

### Versioning Strategy

**Semantic Versioning:** `MAJOR.MINOR.PATCH`

Example: `1.144.0`

- **MAJOR:** Breaking changes
- **MINOR:** New features, backwards compatible
- **PATCH:** Bug fixes

### Version Files

**Development Repo:**
```
VERSION              # Current version number
package.json         # NPM version
CHANGELOG.md         # Full changelog
```

**Distribution Repo:**
```
VERSION              # Same as development
CHANGELOG.md         # User-facing changelog
```

### Changelog Format

```markdown
# Changelog

All notable changes to TP-Affiliate will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [1.145.0] - 2025-11-05

### Added
- New feature X
- New feature Y

### Changed
- Improved feature Z

### Fixed
- Bug fix A
- Bug fix B

### Security
- Security patch C

## [1.144.0] - 2025-11-04
...
```

---

## Configuration Files

### Development Repo: `config/license.php`

```php
return [
    'api_url' => env('LICENSE_API_URL', 'https://xman4289.com/wp-json/tp-license/v1'),
    'license_key' => env('LICENSE_KEY'),
    'installation_id' => env('LICENSE_INSTALLATION_ID'),
    'developer_mode' => env('LICENSE_DEVELOPER_MODE', false),
    'cache_duration' => env('LICENSE_CACHE_DURATION', 604800), // 7 days
];
```

### Development Repo: `config/version.php`

```php
return [
    'current' => env('APP_VERSION', file_get_contents(base_path('VERSION'))),
    'repository' => [
        'owner' => 'xjanova',
        'name' => 'TP-Affiliate',  // Distribution repo
        'api_url' => 'https://api.github.com/repos/xjanova/TP-Affiliate',
    ],
];
```

### Distribution Repo: `.installation/requirements.json`

```json
{
  "php": {
    "min_version": "8.1.0",
    "extensions": [
      "pdo",
      "mbstring",
      "openssl",
      "json",
      "curl",
      "gd"
    ]
  },
  "database": {
    "supported": ["mysql", "mariadb"],
    "min_version": {
      "mysql": "5.7.0",
      "mariadb": "10.3.0"
    }
  },
  "server": {
    "min_memory": "256M",
    "min_disk_space": "1G"
  }
}
```

---

## Deployment Process

### Manual Deployment

```bash
# In development repo
cd /path/to/Thaiprompt-Affiliate

# Run deployment script
./scripts/deploy-to-distribution.sh

# Script will:
# 1. Run tests
# 2. Build assets (npm run build)
# 3. Bump version
# 4. Update CHANGELOG
# 5. Commit changes
# 6. Create git tag
# 7. Push to TP-Affiliate repo
# 8. Create GitHub release
```

### Automated Deployment (GitHub Actions)

```yaml
# .github/workflows/deploy-distribution.yml
name: Deploy to Distribution
on:
  push:
    tags:
      - 'v*'
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - Checkout code
      - Run tests
      - Build assets
      - Deploy to TP-Affiliate repo
      - Create release
```

---

## Environment Variables

### Development (.env)

```env
LICENSE_DEVELOPER_MODE=true
LICENSE_API_URL=https://xman4289.com/wp-json/tp-license/v1
```

### Production (.env) - Customer Installation

```env
LICENSE_KEY=XXXX-XXXX-XXXX-XXXX
LICENSE_INSTALLATION_ID=uuid-generated-on-install
LICENSE_API_URL=https://xman4289.com/wp-json/tp-license/v1
LICENSE_DEVELOPER_MODE=false
```

---

## Error Handling

### License Errors

| Error Code | Description | User Action |
|------------|-------------|-------------|
| `no_license` | No license key found | Activate license |
| `invalid_license` | Invalid license key | Check license key |
| `expired_license` | License expired | Renew license |
| `domain_mismatch` | Domain doesn't match | Contact support |
| `ip_not_allowed` | IP not whitelisted | Contact support |
| `max_activations` | Too many activations | Deactivate others |
| `network_error` | Cannot connect to server | Check connection |

### Installation Errors

| Error | Description | Solution |
|-------|-------------|----------|
| `PHP_VERSION_LOW` | PHP version too old | Upgrade PHP |
| `EXTENSION_MISSING` | Required extension missing | Install extension |
| `DATABASE_ERROR` | Cannot connect to database | Check credentials |
| `PERMISSION_DENIED` | File permission error | Fix permissions |
| `IP_CHECK_FAILED` | IP validation failed | Contact support |

---

## Testing Strategy

### Development Repo Tests

```bash
# Unit tests
php artisan test

# Feature tests
php artisan test --testsuite=Feature

# License service tests
php artisan test --filter=LicenseServiceTest
```

### TpLicense Plugin Tests

```bash
# WordPress plugin tests
cd TpLicense
composer test

# API endpoint tests
composer test -- --filter=ApiTest
```

---

## Monitoring & Analytics

### TpLicense Dashboard Shows:

- Total active licenses
- Total activations
- License expiration calendar
- Popular IP addresses
- Failed validation attempts
- Revenue analytics

### Laravel App Sends:

- Validation requests (for analytics)
- Update checks (version tracking)
- Error reports (optional, with consent)

---

## Security Considerations

1. **License Key Protection**
   - Never commit license keys to git
   - Store in .env (gitignored)
   - Encrypt in database

2. **API Security**
   - Rate limiting on all endpoints
   - IP-based throttling
   - Request signing (optional)

3. **Code Protection**
   - Distribution repo doesn't contain sensitive code
   - Optional: PHP obfuscation for critical files
   - Checksum verification

4. **IP Whitelist**
   - Customers must request IP changes
   - Admin approval required
   - Audit log of all changes

---

## Backup & Recovery

### Installation Backup

```bash
# Before every update
php artisan app:update
    └─ Automatic database backup to storage/app/backups/
```

### License Backup

- License data stored in WordPress database
- Regular WordPress backups include license data
- Export functionality in TpLicense admin

---

## Support & Maintenance

### Customer Support Flow

```
Customer has issue
    ↓
Check license status (TpLicense admin)
    ├─ View activation details
    ├─ Check validation history
    └─ View error logs
    ↓
Provide solution:
    ├─ Add IP to whitelist
    ├─ Extend license
    ├─ Reset activation
    └─ Deactivate/reactivate
```

### Maintenance Tasks

**Daily:**
- Monitor failed validations
- Check for license expirations

**Weekly:**
- Review activation patterns
- Update IP whitelists

**Monthly:**
- Generate revenue reports
- Clean old validation logs

---

## Future Enhancements

1. **Multi-site Support**
   - Single license for multiple domains
   - Subdomain support

2. **Add-on System**
   - Separate licenses for add-ons
   - Dependency management

3. **Automatic Updates**
   - Background updates
   - Rollback capability

4. **Analytics Dashboard**
   - Usage statistics
   - Feature adoption tracking

5. **White-label Option**
   - Custom branding
   - Separate license tier

---

## Getting Started (For Developers)

### Setup Development Environment

```bash
# Clone development repo
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Enable developer mode (skip license check)
# Add to .env:
LICENSE_DEVELOPER_MODE=true

# Migrate and seed
php artisan migrate
php artisan db:seed

# Start development server
php artisan serve
npm run dev
```

### Setup TpLicense Plugin

```bash
# Clone plugin repo
git clone https://github.com/xjanova/TpLicense.git

# Move to WordPress plugins directory
mv TpLicense /path/to/wordpress/wp-content/plugins/

# Activate in WordPress admin
# Go to Plugins → Activate TpLicense
```

---

## Claude AI Integration

See `CLAUDE_CONTEXT.md` for detailed instructions on how Claude should work with these repositories.

---

## Contact & Support

- **Development Team:** development@xman4289.com
- **Customer Support:** support@xman4289.com
- **Documentation:** https://github.com/xjanova/Thaiprompt-Affiliate/wiki

---

**Last Updated:** 2025-11-04
**Version:** 1.144.0
