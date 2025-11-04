# TpLicense WordPress Plugin

## ⚠️ Important

This is the WordPress plugin for managing TP-Affiliate licenses. This directory contains the plugin source that should be deployed to:

**Repository:** https://github.com/xjanova/TpLicense.git

## Quick Start

### For Developers

```bash
# Clone this to WordPress plugins directory
git clone https://github.com/xjanova/TpLicense.git /path/to/wordpress/wp-content/plugins/TpLicense

# Activate in WordPress admin
# Go to: Plugins → Activate TpLicense
```

### For Deployment

```bash
# From this directory (_tplicense-plugin)
# Copy to TpLicense repo
cp -r * /path/to/TpLicense-repo/

# Commit and push
cd /path/to/TpLicense-repo/
git add .
git commit -m "Update plugin"
git push origin main
```

## Purpose

This plugin manages licenses for TP-Affiliate installations, including:

- License generation and activation
- IP whitelist management
- Domain validation
- Customer management
- REST API for license validation
- Analytics and reporting

## Documentation

See `ARCHITECTURE.md` in the main repository for complete system documentation.

## Related Repositories

- **xjanova/Thaiprompt-Affiliate** - Main development repository
- **xjanova/TP-Affiliate** - Distribution repository
- **xjanova/TpLicense** - This plugin (deployment target)

---

**Note:** This directory (`_tplicense-plugin/`) exists in the development repo for easy maintenance. Always deploy to the actual TpLicense repository.
