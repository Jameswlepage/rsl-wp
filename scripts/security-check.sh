#!/bin/bash

# RSL Licensing Security Check Script
# Automated security analysis for the plugin

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}RSL Licensing Security Analysis${NC}"
echo "=================================="

ISSUES_FOUND=0

# Function to report security issue
report_issue() {
    local severity="$1"
    local message="$2"
    local file="$3"
    
    case $severity in
        "HIGH")
            echo -e "${RED}🚨 HIGH: $message${NC}"
            if [ -n "$file" ]; then echo "   File: $file"; fi
            ISSUES_FOUND=$((ISSUES_FOUND + 1))
            ;;
        "MEDIUM")
            echo -e "${YELLOW}⚠️ MEDIUM: $message${NC}"
            if [ -n "$file" ]; then echo "   File: $file"; fi
            ;;
        "LOW")
            echo -e "${BLUE}ℹ️ LOW: $message${NC}"
            if [ -n "$file" ]; then echo "   File: $file"; fi
            ;;
    esac
}

# Function to report security pass
report_pass() {
    echo -e "${GREEN}✅ $1${NC}"
}

echo ""
echo "🔍 Scanning for security vulnerabilities..."
echo ""

# 1. SQL Injection Check
echo "1. Checking for SQL injection vulnerabilities..."
if grep -r "\$wpdb->query\|->get_var\|->get_row\|->get_results" includes/ --include="*.php" | grep -v "prepare\|%s\|%d"; then
    report_issue "HIGH" "Potential SQL injection - non-prepared statements found" 
else
    report_pass "No SQL injection vulnerabilities found"
fi

# 2. XSS Prevention Check
echo ""
echo "2. Checking for XSS vulnerabilities..."
if grep -r "echo.*\$_\|print.*\$_" includes/ --include="*.php" | grep -v "esc_\|sanitize_\|wp_kses"; then
    report_issue "HIGH" "Potential XSS vulnerability - unescaped output found"
else
    report_pass "No XSS vulnerabilities found"
fi

# 3. Direct File Access Protection
echo ""
echo "3. Checking for direct file access protection..."
UNPROTECTED_FILES=$(find includes/ -name "*.php" -exec grep -L "defined.*ABSPATH\|!defined.*ABSPATH" {} \;)
if [ -n "$UNPROTECTED_FILES" ]; then
    report_issue "MEDIUM" "Files missing ABSPATH protection"
    echo "$UNPROTECTED_FILES"
else
    report_pass "All PHP files protected against direct access"
fi

# 4. Nonce Verification Check
echo ""
echo "4. Checking AJAX nonce verification..."
AJAX_HANDLERS=$(grep -r "wp_ajax_" includes/ --include="*.php" | cut -d: -f1 | sort -u)
for file in $AJAX_HANDLERS; do
    if [ -f "$file" ]; then
        if ! grep -q "wp_verify_nonce\|check_ajax_referer" "$file"; then
            report_issue "MEDIUM" "AJAX handler may be missing nonce verification" "$file"
        fi
    fi
done

if [ -z "$AJAX_HANDLERS" ]; then
    report_pass "No AJAX handlers found"
else
    report_pass "AJAX nonce verification checked"
fi

# 5. Hardcoded Credentials Check
echo ""
echo "5. Checking for hardcoded credentials..."
if grep -ri "password.*=.*['\"][^'\"]*['\"]" includes/ --include="*.php" | grep -v "wp_generate_password\|password_hash\|password_verify"; then
    report_issue "HIGH" "Potential hardcoded passwords found"
else
    report_pass "No hardcoded credentials found"
fi

# 6. File Upload Security
echo ""
echo "6. Checking file upload security..."
if grep -r "move_uploaded_file\|\$_FILES" includes/ --include="*.php"; then
    if ! grep -r "wp_check_filetype\|sanitize_file_name" includes/ --include="*.php"; then
        report_issue "HIGH" "File upload without proper validation found"
    else
        report_pass "File upload validation present"
    fi
else
    report_pass "No file upload functionality found"
fi

# 7. Capability Checks
echo ""
echo "7. Checking capability/permission controls..."
ADMIN_FUNCTIONS=$(grep -r "add_menu_page\|add_submenu_page\|wp_ajax_" includes/ --include="*.php" | cut -d: -f1 | sort -u)
for file in $ADMIN_FUNCTIONS; do
    if [ -f "$file" ]; then
        if ! grep -q "current_user_can\|manage_options\|capability" "$file"; then
            report_issue "MEDIUM" "Admin function may be missing capability checks" "$file"
        fi
    fi
done

if [ -z "$ADMIN_FUNCTIONS" ]; then
    report_pass "No admin functions found"
else
    report_pass "Capability checks verified"
fi

# 8. JWT Security Check
echo ""
echo "8. Checking JWT implementation security..."
if grep -r "jwt\|JWT" includes/ --include="*.php"; then
    # Check for hardcoded secrets
    if grep -r "jwt.*secret.*=" includes/ --include="*.php" | grep -v "get_option\|wp_generate_password"; then
        report_issue "HIGH" "Potential hardcoded JWT secret found"
    else
        report_pass "JWT secret properly managed"
    fi
    
    # Check for algorithm confusion
    if grep -r "alg.*none\|algorithm.*none" includes/ --include="*.php"; then
        report_issue "HIGH" "JWT 'none' algorithm usage found (algorithm confusion vulnerability)"
    else
        report_pass "JWT algorithm properly restricted"
    fi
else
    report_pass "No JWT implementation found"
fi

# 9. OAuth Security Check
echo ""
echo "9. Checking OAuth implementation security..."
if grep -r "oauth\|OAuth" includes/ --include="*.php"; then
    # Check for client secret exposure
    if grep -r "client_secret.*=>" includes/ --include="*.php" | grep -v "client_secret_hash"; then
        report_issue "HIGH" "Potential client secret exposure in responses"
    else
        report_pass "OAuth client secrets properly protected"
    fi
    
    # Check for rate limiting
    if grep -r "rate.*limit\|rateLimit" includes/ --include="*.php"; then
        report_pass "Rate limiting implementation found"
    else
        report_issue "MEDIUM" "OAuth endpoints may be missing rate limiting"
    fi
else
    report_pass "No OAuth implementation found"
fi

# 10. Database Security
echo ""
echo "10. Checking database security..."
if grep -r "CREATE TABLE\|ALTER TABLE" includes/ --include="*.php"; then
    if ! grep -r "charset_collate\|\$wpdb->get_charset_collate" includes/ --include="*.php"; then
        report_issue "LOW" "Database tables may not use proper charset/collation"
    else
        report_pass "Database charset/collation properly configured"
    fi
else
    report_pass "No database table creation found"
fi

# Summary
echo ""
echo "=================================="
echo -e "${BLUE}Security Analysis Summary${NC}"
echo "=================================="

if [ $ISSUES_FOUND -eq 0 ]; then
    echo -e "${GREEN}🎉 No security issues found!${NC}"
    echo "The plugin appears to follow WordPress security best practices."
    exit 0
else
    echo -e "${RED}⚠️ Found $ISSUES_FOUND potential security issue(s)${NC}"
    echo ""
    echo "Please review and address the issues above before deploying."
    echo "High-priority issues should be fixed immediately."
    exit 1
fi