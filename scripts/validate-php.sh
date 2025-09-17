#!/bin/bash

# PHP Validation Script for Terpedia Plugin
# This script validates PHP syntax before committing

set -e

echo "🔍 Validating PHP syntax for Terpedia Plugin..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

# Check if PHP is available
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed or not in PATH"
    exit 1
fi

# Get PHP version
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2)
echo "🐘 Using PHP version: $PHP_VERSION"

# Check main plugin file
echo "📄 Checking main plugin file: terpedia.php"
if php -l terpedia.php; then
    print_status "terpedia.php syntax is valid"
else
    print_error "PHP syntax error in terpedia.php"
    exit 1
fi

# Check all PHP files in includes directory
echo "📁 Checking PHP files in includes directory..."
ERRORS_FOUND=0

for file in includes/*.php; do
    if [ -f "$file" ]; then
        echo "  Checking: $file"
        if php -l "$file"; then
            print_status "$file syntax is valid"
        else
            print_error "PHP syntax error in $file"
            ERRORS_FOUND=1
        fi
    fi
done

# Check PHP files in blocks directory (if any)
if [ -d "blocks" ]; then
    echo "📁 Checking PHP files in blocks directory..."
    while IFS= read -r -d '' file; do
        echo "  Checking: $file"
        if php -l "$file"; then
            print_status "$file syntax is valid"
        else
            print_error "PHP syntax error in $file"
            ERRORS_FOUND=1
        fi
    done < <(find blocks -name "*.php" -print0)
fi

# Check for common WordPress coding standards issues
echo "🔍 Checking for common WordPress coding standards issues..."

# Check for missing ABSPATH checks
echo "  Checking for ABSPATH security checks..."
MISSING_ABSPATH=0
for file in includes/*.php; do
    if [ -f "$file" ]; then
        if ! grep -q "if (!defined('ABSPATH'))" "$file"; then
            print_warning "$file is missing ABSPATH security check"
            MISSING_ABSPATH=1
        fi
    fi
done

# Check for proper file headers
echo "  Checking for proper file headers..."
for file in includes/*.php; do
    if [ -f "$file" ]; then
        if ! head -n 10 "$file" | grep -q "@package"; then
            print_warning "$file is missing @package documentation"
        fi
    fi
done

# Summary
echo ""
echo "📊 Validation Summary:"
if [ $ERRORS_FOUND -eq 0 ]; then
    print_status "All PHP files have valid syntax"
else
    print_error "Found PHP syntax errors. Please fix them before committing."
    exit 1
fi

if [ $MISSING_ABSPATH -eq 0 ]; then
    print_status "All files have proper ABSPATH security checks"
else
    print_warning "Some files are missing ABSPATH security checks"
fi

echo ""
print_status "PHP validation completed successfully!"
echo "🚀 Ready to commit!"

