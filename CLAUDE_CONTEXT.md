# Claude AI Context for TP-Affiliate Ecosystem

## ⚠️ IMPORTANT: Read This First

When working on **ANY** feature related to the TP-Affiliate ecosystem, you **MUST** read the following documents **BEFORE** starting work:

1. **ARCHITECTURE.md** - Complete system architecture
2. **This file (CLAUDE_CONTEXT.md)** - Working guidelines
3. **docs/ECOSYSTEM.md** - Detailed ecosystem documentation

---

## Repository Overview

### This Repository: xjanova/Thaiprompt-Affiliate

**Type:** Development Repository (Private)

**Your role:** You are working in the **DEVELOPMENT** repository.

**Related repositories:**
- **xjanova/TP-Affiliate** - Distribution repository (deploy target)
- **xjanova/TpLicense** - WordPress license management plugin

---

## Before Starting Any Task

### 1. Identify the Scope

Ask yourself:
- Does this affect the **license system**? → Read TpLicense documentation
- Does this affect **installation**? → Check install.sh and setup wizard
- Does this affect **updates**? → Review update flow in ARCHITECTURE.md
- Does this involve **API calls**? → Check API endpoints documentation
- Is this for **distribution**? → Review deployment process

### 2. Check Related Systems

**License-related tasks:**
```bash
# Files to check:
- config/license.php
- app/Services/LicenseService.php
- app/Console/Commands/License*.php
- ARCHITECTURE.md (API Endpoints section)
```

**Version/Update tasks:**
```bash
# Files to check:
- config/version.php
- app/Services/VersionService.php
- app/Console/Commands/UpdateCommand.php
- VERSION
- CHANGELOG.md
```

**Installation tasks:**
```bash
# Files to check:
- install.sh (in distribution repo)
- app/Http/Controllers/Auth/SetupController.php
- .installation/ directory
```

### 3. Read API Documentation

If the task involves communication with the license server:

**API Base URL:** `https://xman4289.com/wp-json/tp-license/v1/`

**Available Endpoints:**
- `POST /activate` - Activate license
- `POST /validate` - Validate license
- `POST /deactivate` - Deactivate license
- `POST /check-ip` - Check IP whitelist

**Request Format Example:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com",
  "ip": "123.45.67.89",
  "installation_id": "uuid-xxxx-xxxx"
}
```

See ARCHITECTURE.md for complete API documentation.

---

## Common Task Guidelines

### Task: Modify License System

**BEFORE:**
1. Read `ARCHITECTURE.md` → "Security Flow" section
2. Read `ARCHITECTURE.md` → "API Endpoints" section
3. Check if TpLicense plugin needs updates too
4. Review `app/Services/LicenseService.php`

**DURING:**
- Maintain compatibility with existing license keys
- Preserve offline mode capability
- Keep developer mode functional
- Test with both valid and invalid licenses

**AFTER:**
- Update documentation in ARCHITECTURE.md
- Add changelog entry
- Create migration guide if needed
- Test license activation flow

**Example:**
```php
// GOOD: Always check developer mode first
if (config('license.developer_mode')) {
    return ['valid' => true, 'developer_mode' => true];
}

// Then proceed with normal validation
```

---

### Task: Add New Feature

**BEFORE:**
1. Check if feature requires license validation
2. Determine if it needs to be in distribution repo
3. Review if WordPress plugin needs updates
4. Check version compatibility

**DURING:**
- Follow existing patterns in codebase
- Add configuration in appropriate config file
- Consider backwards compatibility
- Think about customer installations

**AFTER:**
- Add to CHANGELOG.md
- Update VERSION if needed
- Create migration if database changes
- Test on fresh installation
- Document in README or wiki

---

### Task: Update Installation Process

**BEFORE:**
1. Read `ARCHITECTURE.md` → "Installation Security" section
2. Review current `install.sh` (in TP-Affiliate repo)
3. Check `app/Http/Controllers/Auth/SetupController.php`
4. Review `.installation/requirements.json`

**CRITICAL CHECKS:**
- IP validation must happen BEFORE download
- License activation must happen DURING setup
- Admin credentials must be set securely
- Database seeding must be optional
- Checksums must be verified

**AFTER:**
- Test installation on clean server
- Test with invalid license
- Test with non-whitelisted IP
- Update installation documentation
- Update .installation/requirements.json if needed

---

### Task: Create New Release/Version

**BEFORE:**
1. Ensure all tests pass
2. Review CHANGELOG.md
3. Check VERSION file
4. Verify all migrations are included

**PROCESS:**
```bash
# 1. Bump version
php artisan app:bump-version [major|minor|patch]

# 2. Update CHANGELOG.md
# Add new version section with changes

# 3. Commit changes
git add VERSION CHANGELOG.md package.json
git commit -m "chore: bump version to X.X.X"

# 4. Run deployment script
./scripts/deploy-to-distribution.sh

# This will:
# - Build assets
# - Push to TP-Affiliate repo
# - Create git tag
# - Create GitHub release
```

**AFTER:**
- Verify release on GitHub (TP-Affiliate repo)
- Test update process from previous version
- Notify customers of update
- Monitor for issues

---

### Task: Modify Update System

**BEFORE:**
1. Read `ARCHITECTURE.md` → "Update Security" section
2. Review `app/Console/Commands/UpdateCommand.php`
3. Review `app/Services/VersionService.php`
4. Check GitHub API integration

**CRITICAL:**
- License must be valid to update
- Backup must be created before update
- Maintenance mode must be enabled
- Migrations must run successfully
- Rollback must be possible

**PROCESS:**
```php
// Update flow MUST follow this order:
1. Validate license
2. Enable maintenance mode
3. Backup database
4. Update code (git pull)
5. Install dependencies
6. Run migrations
7. Optimize application
8. Disable maintenance mode
```

**AFTER:**
- Test update from N-1 version
- Test update failure scenarios
- Test rollback procedure
- Document any breaking changes

---

### Task: Work with IP Validation

**BEFORE:**
1. Read `ARCHITECTURE.md` → "Security Flow" section
2. Understand TpLicense plugin structure
3. Review `app/Services/LicenseService.php`

**UNDERSTANDING:**
```
IP Validation Flow:
1. Customer requests installation
2. install.sh gets server IP
3. Checks IP with TpLicense API
4. TpLicense checks wp_tp_license_ip_whitelist table
5. If allowed → proceed
6. If not → installation fails
```

**IMPORTANT:**
- IP whitelist is managed in WordPress admin
- Multiple IPs can be whitelisted per license
- IP changes require admin approval
- Installation fails immediately if IP not allowed

**CODE PATTERN:**
```php
// In installation script
$response = Http::post($licenseApiUrl . '/check-ip', [
    'license_key' => $licenseKey,
    'ip' => $serverIp,
]);

if (!$response->json()['allowed']) {
    throw new Exception('IP not authorized for this license');
}
```

---

### Task: Database Changes

**BEFORE:**
1. Check if migration exists
2. Consider backwards compatibility
3. Think about customer installations

**DURING:**
```bash
# Create migration
php artisan make:migration create_new_table

# IMPORTANT: Make migrations reversible
public function up() {
    // Create/modify
}

public function down() {
    // Rollback changes
}
```

**CRITICAL:**
- Never delete columns (deprecate instead)
- Always provide default values
- Test migration on existing data
- Create seeder if needed

**AFTER:**
- Add to CHANGELOG
- Update seeders if needed
- Document database changes
- Test fresh installation
- Test update from previous version

---

### Task: Add Configuration Option

**BEFORE:**
- Decide: config file or .env?
- Check if it affects customers
- Consider security implications

**PATTERN:**
```php
// In config file (e.g., config/license.php)
'new_option' => env('LICENSE_NEW_OPTION', 'default_value'),

// In .env.example (for distribution)
LICENSE_NEW_OPTION=default_value

// In code
$value = config('license.new_option');
```

**DISTRIBUTION:**
- Add to `.env.example`
- Document in README
- Add to ARCHITECTURE.md if significant
- Don't break existing installations

---

## File Modification Guidelines

### Files That Affect Distribution

These files are copied to TP-Affiliate repo:

```
✓ Can modify freely (will be deployed):
- app/**/*.php (application code)
- config/**/*.php (configuration)
- database/migrations/*.php
- database/seeders/*.php
- public/**/* (assets)
- resources/**/* (views, js, css)
- routes/**/*.php

⚠️ Modify with caution (affects customers):
- .env.example (customer template)
- VERSION (triggers new release)
- CHANGELOG.md (shown to customers)
- composer.json (dependencies)
- package.json (npm dependencies)

✗ Never in distribution:
- .env (your local config)
- .git/ (git history)
- tests/ (development only)
- .github/ (workflows)
- node_modules/
- vendor/
```

### Files That Affect TpLicense Plugin

If you modify these, TpLicense plugin may need updates:

```
- config/license.php (API URL, settings)
- app/Services/LicenseService.php (API calls)
- API request/response format
```

**Action Required:**
1. Note the changes needed in TpLicense
2. Create issue in TpLicense repo
3. Coordinate updates
4. Test integration after both repos updated

---

## Testing Guidelines

### Before Committing

```bash
# Run tests
php artisan test

# Check code style
./vendor/bin/pint

# Build assets
npm run build

# Test license commands
php artisan license:check
php artisan app:check-update
```

### Test Scenarios

**License System:**
- ✓ Valid license
- ✓ Invalid license
- ✓ Expired license
- ✓ Non-whitelisted IP
- ✓ Developer mode
- ✓ Offline mode

**Installation:**
- ✓ Fresh installation
- ✓ Invalid license key
- ✓ Wrong IP address
- ✓ Missing requirements
- ✓ Database errors

**Updates:**
- ✓ Update to newer version
- ✓ Update with invalid license
- ✓ Update with migration
- ✓ Update rollback

---

## Communication Patterns

### With License Server (TpLicense)

**Always include:**
```php
[
    'license_key' => config('license.license_key'),
    'domain' => request()->getHost(),
    'ip' => request()->ip(),
    'installation_id' => $this->installationId,
]
```

**Handle responses:**
```php
if ($response->successful() && $response->json()['success']) {
    // Success path
    $license = $response->json()['license'];
} else {
    // Error path
    $error = $response->json()['code'] ?? 'unknown';
    $message = $response->json()['message'] ?? 'Error occurred';
}
```

**Cache results:**
```php
// Cache validation for 7 days
$cacheKey = 'license_validation_' . md5($licenseKey);
Cache::put($cacheKey, $result, config('license.cache_duration'));
```

---

## Common Pitfalls

### ❌ DON'T

1. **Don't** skip license validation in critical features
```php
// BAD
public function criticalFeature() {
    // Directly execute without checking license
}
```

2. **Don't** commit license keys or sensitive data
```php
// BAD
'license_key' => 'XXXX-XXXX-XXXX-XXXX', // Hardcoded!
```

3. **Don't** break backwards compatibility without migration
```php
// BAD - This breaks existing installations
Schema::table('users', function($table) {
    $table->dropColumn('old_column'); // Customer data lost!
});
```

4. **Don't** modify VERSION without updating CHANGELOG
```bash
# BAD
echo "2.0.0" > VERSION  # No changelog entry!
```

5. **Don't** deploy to distribution without testing
```bash
# BAD
git push distribution main  # Without testing!
```

### ✅ DO

1. **Do** check license before critical operations
```php
// GOOD
public function criticalFeature() {
    $validation = $this->licenseService->validate();
    if (!$validation['valid']) {
        abort(403, 'Valid license required');
    }
    // Proceed
}
```

2. **Do** use environment variables
```php
// GOOD
'license_key' => env('LICENSE_KEY'),
```

3. **Do** create migrations for database changes
```php
// GOOD - Reversible migration
public function up() {
    Schema::table('users', function($table) {
        $table->string('new_column')->nullable();
    });
}

public function down() {
    Schema::table('users', function($table) {
        $table->dropColumn('new_column');
    });
}
```

4. **Do** update VERSION and CHANGELOG together
```bash
# GOOD
php artisan app:bump-version patch
# Then update CHANGELOG.md
# Then commit both files
```

5. **Do** test before deploying
```bash
# GOOD
php artisan test
php artisan app:optimize
npm run build
./scripts/deploy-to-distribution.sh
```

---

## Emergency Procedures

### License Server Down

**Symptom:** Customers cannot activate/validate licenses

**Solution:**
1. Check xman4289.com server status
2. Enable offline mode temporarily (if safe):
   ```php
   // In customer's .env
   LICENSE_ALLOW_OFFLINE=true
   ```
3. Cached validations still work for 7 days
4. Restore license server ASAP

### Invalid Update Released

**Symptom:** Customers report errors after update

**Solution:**
1. Identify the issue
2. Create hotfix branch
3. Fix the bug
4. Release patch version immediately:
   ```bash
   php artisan app:bump-version patch
   ./scripts/deploy-to-distribution.sh
   ```
5. Notify customers
6. Post-mortem: update testing procedures

### Database Migration Failed

**Symptom:** Update fails during migration

**Solution:**
1. Customer should have automatic backup
2. Guide customer to rollback:
   ```bash
   git checkout v{previous_version}
   composer install
   php artisan migrate:rollback
   php artisan up  # Disable maintenance
   ```
3. Fix migration in development
4. Release patch version
5. Test migration path thoroughly

---

## Documentation Standards

### Code Comments

```php
/**
 * Validate license with server
 *
 * This method contacts the TpLicense WordPress plugin
 * to validate the current license key.
 *
 * @return array ['valid' => bool, 'license' => array, ...]
 * @throws \Exception if network error occurs
 */
public function validate(): array
{
    // Implementation
}
```

### CHANGELOG Format

```markdown
## [1.145.0] - 2025-11-05

### Added
- New feature: IP whitelist management in admin panel
- API endpoint for bulk license activation

### Changed
- Improved license validation caching (7 days → 14 days)
- Updated installer to check PHP 8.2 compatibility

### Fixed
- Fixed license expiration notification not showing
- Fixed update button not working on some servers

### Security
- Added rate limiting to license validation endpoint
- Improved IP validation security
```

### README Updates

When adding significant features, update README with:
- Feature description
- Configuration options
- Usage examples
- Screenshots (if UI changes)

---

## Environment-Specific Guidelines

### Development Environment

```env
# .env for development
LICENSE_DEVELOPER_MODE=true
LICENSE_ALLOW_OFFLINE=true
APP_DEBUG=true
```

**You can:**
- Skip license checks
- Work offline
- Use demo data
- Test without restrictions

### Customer Environment (Production)

```env
# .env for customers
LICENSE_KEY=XXXX-XXXX-XXXX-XXXX
LICENSE_DEVELOPER_MODE=false
LICENSE_ALLOW_OFFLINE=false
APP_DEBUG=false
```

**Must have:**
- Valid license key
- Whitelisted IP
- Production settings
- Optimized code

---

## Quick Reference

### Important Commands

```bash
# License management
php artisan license:activate {key}
php artisan license:check
php artisan license:status
php artisan license:deactivate

# Version management
php artisan app:version
php artisan app:bump-version [major|minor|patch]

# Update system
php artisan app:check-update
php artisan app:update [version] [--force] [--no-backup]

# Data management
php artisan db:seed
php artisan app:reset-demo-data

# Deployment
./scripts/deploy-to-distribution.sh
```

### Important Files to Check

```bash
# Configuration
config/license.php       # License settings
config/version.php       # Version settings
.env.example            # Environment template

# Services
app/Services/LicenseService.php    # License logic
app/Services/VersionService.php    # Version logic

# Commands
app/Console/Commands/License*.php  # License commands
app/Console/Commands/Update*.php   # Update commands

# Documentation
ARCHITECTURE.md         # System architecture
CHANGELOG.md           # Version history
VERSION                # Current version
```

### TpLicense Plugin Files

```bash
# WordPress plugin structure
wp-content/plugins/TpLicense/
├── tp-license.php                 # Main file
├── includes/api/
│   └── class-license-api.php      # REST API
└── includes/core/
    ├── class-license-validator.php # Validation logic
    └── class-ip-manager.php        # IP whitelist
```

---

## Questions to Ask Before Starting

### License-Related Tasks

1. Does this affect license validation?
2. Does this require TpLicense plugin changes?
3. Will existing licenses still work?
4. How does this work in developer mode?
5. What happens if license server is down?

### Installation-Related Tasks

1. Does this affect the installer?
2. What happens on existing installations?
3. Do we need database migration?
4. How do we handle errors during installation?
5. Is this IP-restricted?

### Update-Related Tasks

1. Is this a breaking change?
2. Do we need to bump major/minor/patch version?
3. How do customers update to this?
4. Can we rollback if something goes wrong?
5. Do we need to notify customers?

### Distribution-Related Tasks

1. Should this go to distribution repo?
2. Do we need to update .env.example?
3. Are there new dependencies?
4. How big is the download size impact?
5. Do we need documentation?

---

## Final Checklist Before Committing

- [ ] Read relevant sections of ARCHITECTURE.md
- [ ] Tests pass (`php artisan test`)
- [ ] Code formatted (`./vendor/bin/pint`)
- [ ] Assets built (`npm run build`)
- [ ] CHANGELOG.md updated (if needed)
- [ ] VERSION bumped (if needed)
- [ ] .env.example updated (if new config)
- [ ] Documentation updated
- [ ] Backwards compatibility checked
- [ ] TpLicense plugin impact assessed
- [ ] Security implications considered

---

## Getting Help

### Within This Codebase

1. Check ARCHITECTURE.md first
2. Check this file (CLAUDE_CONTEXT.md)
3. Check docs/ directory
4. Read code comments
5. Check git history

### External Resources

- TpLicense repo: https://github.com/xjanova/TpLicense
- Distribution repo: https://github.com/xjanova/TP-Affiliate
- License server: https://xman4289.com

### When in Doubt

**Always ask the user** before:
- Making breaking changes
- Modifying license system
- Changing API contract
- Deploying to distribution
- Bumping major version

---

**Remember:** This is a commercial product used by customers. Every change affects real businesses. Test thoroughly, document clearly, and maintain backwards compatibility whenever possible.

---

**Last Updated:** 2025-11-04
**For Claude Code Version:** Latest
**Maintained By:** Development Team
