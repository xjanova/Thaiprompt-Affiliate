#!/bin/bash

# Web Access Diagnostic Script for Laravel
# This script collects diagnostic information to analyze web server access issues

echo "=================================================="
echo "Laravel Web Access Diagnostic Report"
echo "Generated: $(date)"
echo "=================================================="
echo ""

# Detect the installation path
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "📁 Current Script Location: $SCRIPT_DIR"
echo ""

# Function to print section headers
print_section() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

# Function to check command availability
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 1. Basic System Information
print_section "1. SYSTEM INFORMATION"
echo "Hostname: $(hostname)"
echo "OS: $(cat /etc/os-release 2>/dev/null | grep PRETTY_NAME | cut -d= -f2 | tr -d '"')"
echo "Kernel: $(uname -r)"
echo "Current User: $(whoami)"
echo "Current Working Directory: $(pwd)"

# 2. Web Server Detection and Status
print_section "2. WEB SERVER DETECTION"

if command_exists nginx; then
    echo "✓ Nginx detected"
    echo "Nginx Version: $(nginx -v 2>&1)"
    echo "Nginx Status:"
    systemctl status nginx --no-pager -l 2>/dev/null || service nginx status 2>/dev/null || echo "Unable to check nginx status"
    echo ""
    echo "Nginx Configuration Test:"
    nginx -t 2>&1
else
    echo "✗ Nginx not found"
fi

echo ""

if command_exists apache2 || command_exists httpd; then
    APACHE_CMD=$(command_exists apache2 && echo "apache2" || echo "httpd")
    echo "✓ Apache detected"
    echo "Apache Version: $($APACHE_CMD -v 2>&1 | head -1)"
    echo "Apache Status:"
    systemctl status $APACHE_CMD --no-pager -l 2>/dev/null || service $APACHE_CMD status 2>/dev/null || echo "Unable to check Apache status"
    echo ""
    echo "Apache Configuration Test:"
    $APACHE_CMD -t 2>&1
else
    echo "✗ Apache not found"
fi

# 3. PHP Configuration
print_section "3. PHP CONFIGURATION"

if command_exists php; then
    echo "PHP Version: $(php -v | head -1)"
    echo "PHP CLI User: $(ps aux | grep 'php' | grep -v grep | head -1 | awk '{print $1}')"
    echo ""
    echo "PHP-FPM Status:"
    systemctl status php*-fpm --no-pager -l 2>/dev/null || echo "PHP-FPM status not available"
    echo ""
    echo "Important PHP Settings:"
    php -i | grep -E "display_errors|error_reporting|open_basedir|disable_functions" 2>/dev/null || echo "Unable to retrieve PHP info"
else
    echo "✗ PHP not found in PATH"
fi

# 4. Directory and File Permissions
print_section "4. DIRECTORY & FILE PERMISSIONS"

echo "Project Root:"
ls -lah "$SCRIPT_DIR" | head -10

echo ""
echo "Public Directory:"
if [ -d "$SCRIPT_DIR/public" ]; then
    ls -lah "$SCRIPT_DIR/public" | head -10
else
    echo "✗ public/ directory not found!"
fi

echo ""
echo "Storage Directory:"
if [ -d "$SCRIPT_DIR/storage" ]; then
    ls -lah "$SCRIPT_DIR/storage"
else
    echo "✗ storage/ directory not found!"
fi

echo ""
echo "Bootstrap Cache Directory:"
if [ -d "$SCRIPT_DIR/bootstrap/cache" ]; then
    ls -lah "$SCRIPT_DIR/bootstrap/cache"
else
    echo "✗ bootstrap/cache/ directory not found!"
fi

echo ""
echo "Ownership Summary:"
echo "Project Root: $(stat -c '%U:%G' "$SCRIPT_DIR" 2>/dev/null || stat -f '%Su:%Sg' "$SCRIPT_DIR" 2>/dev/null)"
echo "Public: $(stat -c '%U:%G' "$SCRIPT_DIR/public" 2>/dev/null || stat -f '%Su:%Sg' "$SCRIPT_DIR/public" 2>/dev/null)"
echo "Storage: $(stat -c '%U:%G' "$SCRIPT_DIR/storage" 2>/dev/null || stat -f '%Su:%Sg' "$SCRIPT_DIR/storage" 2>/dev/null)"
echo "Bootstrap/Cache: $(stat -c '%U:%G' "$SCRIPT_DIR/bootstrap/cache" 2>/dev/null || stat -f '%Su:%Sg' "$SCRIPT_DIR/bootstrap/cache" 2>/dev/null)"

# 5. Critical Files Check
print_section "5. CRITICAL FILES CHECK"

echo "Checking index.php:"
if [ -f "$SCRIPT_DIR/public/index.php" ]; then
    echo "✓ public/index.php exists"
    ls -lh "$SCRIPT_DIR/public/index.php"
    echo "Permissions: $(stat -c '%a' "$SCRIPT_DIR/public/index.php" 2>/dev/null || stat -f '%A' "$SCRIPT_DIR/public/index.php" 2>/dev/null)"
else
    echo "✗ public/index.php NOT FOUND!"
fi

echo ""
echo "Checking .htaccess:"
if [ -f "$SCRIPT_DIR/public/.htaccess" ]; then
    echo "✓ public/.htaccess exists"
    ls -lh "$SCRIPT_DIR/public/.htaccess"
    echo "Permissions: $(stat -c '%a' "$SCRIPT_DIR/public/.htaccess" 2>/dev/null || stat -f '%A' "$SCRIPT_DIR/public/.htaccess" 2>/dev/null)"
    echo ""
    echo "Content:"
    cat "$SCRIPT_DIR/public/.htaccess"
else
    echo "✗ public/.htaccess NOT FOUND!"
fi

echo ""
echo "Checking .env:"
if [ -f "$SCRIPT_DIR/.env" ]; then
    echo "✓ .env exists"
    ls -lh "$SCRIPT_DIR/.env"
    echo "Permissions: $(stat -c '%a' "$SCRIPT_DIR/.env" 2>/dev/null || stat -f '%A' "$SCRIPT_DIR/.env" 2>/dev/null)"
    echo ""
    echo "Key Environment Variables (sanitized):"
    grep -E "^APP_ENV=|^APP_DEBUG=|^APP_URL=|^DB_CONNECTION=" "$SCRIPT_DIR/.env" 2>/dev/null || echo "Unable to read .env"
else
    echo "✗ .env NOT FOUND!"
fi

# 6. Laravel Storage Structure
print_section "6. LARAVEL STORAGE STRUCTURE"

echo "Checking required storage directories:"
for dir in "storage/framework/sessions" "storage/framework/views" "storage/framework/cache" "storage/logs" "storage/app/public"; do
    if [ -d "$SCRIPT_DIR/$dir" ]; then
        echo "✓ $dir exists ($(stat -c '%a %U:%G' "$SCRIPT_DIR/$dir" 2>/dev/null || stat -f '%A %Su:%Sg' "$SCRIPT_DIR/$dir" 2>/dev/null))"
    else
        echo "✗ $dir NOT FOUND!"
    fi
done

# 7. Web Server Configuration Files
print_section "7. WEB SERVER CONFIGURATION"

echo "Searching for Nginx site configurations:"
for conf_dir in "/etc/nginx/sites-enabled" "/etc/nginx/conf.d"; do
    if [ -d "$conf_dir" ]; then
        echo "Found configs in $conf_dir:"
        grep -l "member123.thaiprompt.online\|thaiprompt" "$conf_dir"/* 2>/dev/null || echo "No matching configurations found"
        echo ""
        for conf_file in "$conf_dir"/*; do
            if [ -f "$conf_file" ] && grep -q "member123.thaiprompt.online\|$(basename $SCRIPT_DIR)" "$conf_file" 2>/dev/null; then
                echo "Configuration: $conf_file"
                cat "$conf_file"
                echo ""
            fi
        done
    fi
done

echo ""
echo "Searching for Apache site configurations:"
for conf_dir in "/etc/apache2/sites-enabled" "/etc/httpd/conf.d" "/etc/httpd/sites-enabled"; do
    if [ -d "$conf_dir" ]; then
        echo "Found configs in $conf_dir:"
        grep -l "member123.thaiprompt.online\|thaiprompt" "$conf_dir"/* 2>/dev/null || echo "No matching configurations found"
        echo ""
        for conf_file in "$conf_dir"/*; do
            if [ -f "$conf_file" ] && grep -q "member123.thaiprompt.online\|$(basename $SCRIPT_DIR)" "$conf_file" 2>/dev/null; then
                echo "Configuration: $conf_file"
                cat "$conf_file"
                echo ""
            fi
        done
    fi
done

# 8. Document Root Verification
print_section "8. DOCUMENT ROOT VERIFICATION"

echo "Checking if web server points to public/ directory..."
if command_exists nginx; then
    echo "Nginx Document Roots:"
    grep -r "root.*$(basename $SCRIPT_DIR)" /etc/nginx/sites-enabled/ /etc/nginx/conf.d/ 2>/dev/null || echo "No Nginx document root found for this project"
fi

if command_exists apache2 || command_exists httpd; then
    echo "Apache Document Roots:"
    grep -r "DocumentRoot.*$(basename $SCRIPT_DIR)" /etc/apache2/sites-enabled/ /etc/httpd/conf.d/ /etc/httpd/sites-enabled/ 2>/dev/null || echo "No Apache document root found for this project"
fi

# 9. Recent Error Logs
print_section "9. RECENT WEB SERVER ERROR LOGS"

echo "Nginx Error Logs (last 20 lines):"
if [ -f "/var/log/nginx/error.log" ]; then
    tail -20 /var/log/nginx/error.log 2>/dev/null || echo "Unable to read nginx error log"
else
    echo "Nginx error log not found at standard location"
fi

echo ""
echo "Apache Error Logs (last 20 lines):"
for log in "/var/log/apache2/error.log" "/var/log/httpd/error_log"; do
    if [ -f "$log" ]; then
        tail -20 "$log" 2>/dev/null || echo "Unable to read $log"
        break
    fi
done

echo ""
echo "Laravel Logs (last 30 lines):"
if [ -f "$SCRIPT_DIR/storage/logs/laravel.log" ]; then
    tail -30 "$SCRIPT_DIR/storage/logs/laravel.log" 2>/dev/null || echo "Unable to read Laravel log"
else
    echo "Laravel log not found"
fi

# 10. Laravel Specific Checks
print_section "10. LARAVEL SPECIFIC CHECKS"

echo "Checking Laravel installation:"
if [ -f "$SCRIPT_DIR/artisan" ]; then
    echo "✓ artisan file exists"
    echo "Laravel Version:"
    php "$SCRIPT_DIR/artisan" --version 2>/dev/null || echo "Unable to run artisan command"
    echo ""
    echo "Route Cache:"
    [ -f "$SCRIPT_DIR/bootstrap/cache/routes-v7.php" ] && echo "✓ Routes cached" || echo "✗ Routes not cached"
    echo "Config Cache:"
    [ -f "$SCRIPT_DIR/bootstrap/cache/config.php" ] && echo "✓ Config cached" || echo "✗ Config not cached"
    echo "View Cache:"
    [ -d "$SCRIPT_DIR/storage/framework/views" ] && [ "$(ls -A $SCRIPT_DIR/storage/framework/views)" ] && echo "✓ Views cached" || echo "✗ Views not cached"
else
    echo "✗ artisan file not found"
fi

# 11. SELinux Check (if applicable)
print_section "11. SELINUX STATUS"

if command_exists getenforce; then
    echo "SELinux Status: $(getenforce)"
    if [ "$(getenforce)" != "Disabled" ]; then
        echo ""
        echo "⚠️  SELinux is enabled. Checking contexts:"
        ls -Z "$SCRIPT_DIR/public" 2>/dev/null || echo "Unable to check SELinux contexts"
    fi
else
    echo "SELinux not installed/available"
fi

# 12. Network and Port Check
print_section "12. NETWORK & PORT STATUS"

echo "Listening Web Server Ports:"
netstat -tlnp 2>/dev/null | grep -E ':80|:443' || ss -tlnp 2>/dev/null | grep -E ':80|:443' || echo "Unable to check listening ports"

echo ""
echo "Active Web Server Processes:"
ps aux | grep -E 'nginx|apache2|httpd|php-fpm' | grep -v grep

# 13. Summary and Recommendations
print_section "13. QUICK DIAGNOSIS SUMMARY"

ISSUES_FOUND=0

# Check critical issues
if [ ! -f "$SCRIPT_DIR/public/index.php" ]; then
    echo "❌ CRITICAL: public/index.php not found"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
fi

if [ ! -d "$SCRIPT_DIR/storage" ]; then
    echo "❌ CRITICAL: storage/ directory not found"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
fi

if [ ! -f "$SCRIPT_DIR/.env" ]; then
    echo "❌ CRITICAL: .env file not found"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
fi

# Check permissions
if [ -d "$SCRIPT_DIR/storage" ]; then
    STORAGE_PERMS=$(stat -c '%a' "$SCRIPT_DIR/storage" 2>/dev/null || stat -f '%A' "$SCRIPT_DIR/storage" 2>/dev/null)
    if [ "$STORAGE_PERMS" -lt "755" ] 2>/dev/null; then
        echo "⚠️  WARNING: storage/ directory permissions may be too restrictive ($STORAGE_PERMS)"
        ISSUES_FOUND=$((ISSUES_FOUND + 1))
    fi
fi

if [ -d "$SCRIPT_DIR/bootstrap/cache" ]; then
    CACHE_PERMS=$(stat -c '%a' "$SCRIPT_DIR/bootstrap/cache" 2>/dev/null || stat -f '%A' "$SCRIPT_DIR/bootstrap/cache" 2>/dev/null)
    if [ "$CACHE_PERMS" -lt "755" ] 2>/dev/null; then
        echo "⚠️  WARNING: bootstrap/cache/ directory permissions may be too restrictive ($CACHE_PERMS)"
        ISSUES_FOUND=$((ISSUES_FOUND + 1))
    fi
fi

if [ "$ISSUES_FOUND" -eq 0 ]; then
    echo "✅ No critical issues detected in basic checks"
    echo ""
    echo "Next steps:"
    echo "1. Review web server configuration (section 7)"
    echo "2. Check error logs (section 9) for specific errors"
    echo "3. Verify document root points to public/ directory (section 8)"
    echo "4. If SELinux is enabled, check contexts (section 11)"
fi

echo ""
echo "=================================================="
echo "Diagnostic Report Complete"
echo "=================================================="
echo ""
echo "📋 To save this report to a file, run:"
echo "   bash diagnose-web-access.sh > diagnostic-report.txt 2>&1"
echo ""
