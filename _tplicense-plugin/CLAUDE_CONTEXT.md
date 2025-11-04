# Claude AI Context for TpLicense WordPress Plugin

## ⚠️ IMPORTANT: Related Repositories

This plugin is part of the TP-Affiliate ecosystem. Before making changes, you **MUST** read:

1. **Main Repository:** xjanova/Thaiprompt-Affiliate
   - Read: `ARCHITECTURE.md` - Complete system architecture
   - Read: `CLAUDE_CONTEXT.md` - Development guidelines

2. **Distribution Repository:** xjanova/TP-Affiliate
   - This is where customers download the app

3. **This Repository:** xjanova/TpLicense (WordPress Plugin)
   - License management system
   - Provides REST API for license validation

---

## Purpose

This WordPress plugin manages licenses for TP-Affiliate installations. It provides:

- License generation and management
- IP whitelist for installation control
- REST API endpoints for license validation
- Customer management
- Analytics and reporting

---

## API Endpoints

**Base URL:** `https://xman4289.com/wp-json/tp-license/v1/`

### 1. POST /activate
Activate a license for a domain

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com",
  "ip": "123.45.67.89",
  "installation_id": "uuid-xxxxx",
  "php_version": "8.1.0",
  "laravel_version": "11.0"
}
```

### 2. POST /validate
Validate an active license

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com",
  "ip": "123.45.67.89",
  "installation_id": "uuid-xxxxx"
}
```

### 3. POST /deactivate
Deactivate a license

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "example.com"
}
```

### 4. POST /check-ip
Check if IP is whitelisted for a license

**Request:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "ip": "123.45.67.89"
}
```

---

## Database Schema

### Tables

1. `wp_tp_licenses` - License records
2. `wp_tp_license_activations` - Active installations
3. `wp_tp_license_ip_whitelist` - IP whitelist
4. `wp_tp_customers` - Customer information
5. `wp_tp_license_activity_log` - Activity logging

See `includes/Core/Database.php` for complete schema.

---

## Important Guidelines

### When Modifying API Endpoints

1. **Check Laravel App First**
   - Read `app/Services/LicenseService.php` in main repo
   - Ensure API contract compatibility
   - Don't break existing installations

2. **Test Integration**
   - Test with Laravel app
   - Test all response codes
   - Test error scenarios

3. **Update Documentation**
   - Update ARCHITECTURE.md in main repo
   - Document any API changes
   - Add changelog entry

### When Adding Features

1. **Consider Impact**
   - Will this affect existing licenses?
   - Do customers need to update?
   - Is migration needed?

2. **Security First**
   - Validate all inputs
   - Sanitize outputs
   - Log important actions

3. **Performance**
   - API endpoints must be fast (<1s)
   - Use caching where appropriate
   - Index database queries

---

## Development Workflow

### Local Development

```bash
# Clone to WordPress plugins directory
git clone https://github.com/xjanova/TpLicense.git /path/to/wp-content/plugins/TpLicense

# Activate in WordPress admin
wp plugin activate TpLicense

# Test API endpoints
curl -X POST https://your-site.test/wp-json/tp-license/v1/check-ip \
  -H "Content-Type: application/json" \
  -d '{"license_key":"TEST-TEST-TEST-TEST","ip":"127.0.0.1"}'
```

### Testing

1. **Unit Tests** (if available)
   ```bash
   composer test
   ```

2. **Integration Tests**
   - Test with Laravel app
   - Test all API endpoints
   - Test IP whitelist functionality

3. **Manual Tests**
   - Create license in WordPress admin
   - Add IP to whitelist
   - Test activation from Laravel app

---

## Common Tasks

### Add New API Endpoint

1. Create endpoint class in `includes/Api/`
2. Register route in `includes/Api/RestApi.php`
3. Update main repo's `LicenseService.php`
4. Update ARCHITECTURE.md
5. Test integration

### Modify Database Schema

1. Update `includes/Core/Database.php`
2. Create migration/upgrade routine
3. Test on fresh install
4. Test on existing install
5. Document changes

### Change IP Validation Logic

1. Update `includes/Core/IpManager.php`
2. Update `includes/Api/ActivationEndpoint.php`
3. Test with whitelisted IP
4. Test with non-whitelisted IP
5. Update main repo if needed

---

## Deployment

```bash
# From development repo
cd /path/to/Thaiprompt-Affiliate/_tplicense-plugin

# Copy to TpLicense repo
cp -r * /path/to/TpLicense-repo/

# Commit and push
cd /path/to/TpLicense-repo/
git add .
git commit -m "Update plugin"
git push origin main
```

---

## Emergency Procedures

### API Endpoint Down

1. Check WordPress site status
2. Check error logs
3. Disable problematic endpoint temporarily
4. Fix and redeploy

### IP Whitelist Issue

1. Temporarily disable IP whitelist:
   ```php
   update_option('tp_license_enable_ip_whitelist', false);
   ```
2. Add customer IP manually in database
3. Re-enable IP whitelist

---

## Questions Before Starting

1. Does this change affect the Laravel app?
2. Do existing licenses need migration?
3. Will this break existing installations?
4. Is the API contract maintained?
5. Is documentation updated?

---

## Contact

- Main Development: See xjanova/Thaiprompt-Affiliate repository
- WordPress Plugin Issues: Create issue in this repository

---

**Last Updated:** 2025-11-04
**Related Documentation:** See ARCHITECTURE.md in main repository
