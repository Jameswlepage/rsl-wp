#!/bin/bash

# RSL Licensing Test Runner Script
# Comprehensive test execution with reporting

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test configuration
COVERAGE_DIR="coverage"
LOGS_DIR="test-logs"
REPORT_FILE="test-report.html"

# Create directories
mkdir -p "$COVERAGE_DIR" "$LOGS_DIR"

echo -e "${BLUE}RSL Licensing Plugin - Test Suite Runner${NC}"
echo "=================================================="

# Function to print section header
print_header() {
    echo -e "\n${YELLOW}$1${NC}"
    echo "$(printf '=%.0s' $(seq 1 ${#1}))"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check dependencies
print_header "Checking Dependencies"

if ! command_exists php; then
    echo -e "${RED}Error: PHP is not installed${NC}"
    exit 1
fi

if ! command_exists composer; then
    echo -e "${RED}Error: Composer is not installed${NC}"
    exit 1
fi

echo -e "${GREEN}✓ PHP: $(php --version | head -n1)${NC}"
echo -e "${GREEN}✓ Composer: $(composer --version)${NC}"

# Install dependencies
print_header "Installing Dependencies"
composer install --dev --no-interaction --quiet

if [ ! -f "vendor/bin/phpunit" ]; then
    echo -e "${RED}Error: PHPUnit not found in vendor/bin${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Dependencies installed${NC}"

# Function to run test suite
run_test_suite() {
    local suite_name="$1"
    local suite_path="$2"
    local log_file="$LOGS_DIR/${suite_name}.log"
    
    echo -e "\n${BLUE}Running $suite_name tests...${NC}"
    
    if vendor/bin/phpunit --testsuite="$suite_name" --log-junit="$LOGS_DIR/${suite_name}-junit.xml" > "$log_file" 2>&1; then
        echo -e "${GREEN}✓ $suite_name tests passed${NC}"
        return 0
    else
        echo -e "${RED}✗ $suite_name tests failed${NC}"
        echo "Check log file: $log_file"
        return 1
    fi
}

# Function to run tests with coverage
run_tests_with_coverage() {
    echo -e "\n${BLUE}Running all tests with coverage...${NC}"
    
    if command_exists xdebug; then
        echo "Xdebug detected - generating coverage report"
        vendor/bin/phpunit --coverage-html="$COVERAGE_DIR" --coverage-clover="$COVERAGE_DIR/clover.xml" --log-junit="$LOGS_DIR/full-junit.xml" > "$LOGS_DIR/full-coverage.log" 2>&1
    else
        echo "Xdebug not available - running tests without coverage"
        vendor/bin/phpunit --log-junit="$LOGS_DIR/full-junit.xml" > "$LOGS_DIR/full.log" 2>&1
    fi
}

# Parse command line arguments
COVERAGE_ONLY=false
SUITE_ONLY=""
PERFORMANCE_ONLY=false
QUICK_MODE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --coverage)
            COVERAGE_ONLY=true
            shift
            ;;
        --suite=*)
            SUITE_ONLY="${1#*=}"
            shift
            ;;
        --performance)
            PERFORMANCE_ONLY=true
            shift
            ;;
        --quick)
            QUICK_MODE=true
            shift
            ;;
        --help)
            echo "RSL Licensing Test Runner"
            echo ""
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --coverage      Run with coverage report only"
            echo "  --suite=NAME    Run specific test suite (unit|integration|security|performance)"
            echo "  --performance   Run performance tests only"
            echo "  --quick         Run quick smoke tests only"
            echo "  --help          Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                          # Run all tests"
            echo "  $0 --suite=unit            # Run unit tests only"
            echo "  $0 --coverage              # Run with coverage"
            echo "  $0 --performance           # Run performance benchmarks"
            echo "  $0 --quick                 # Run quick smoke tests"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Main test execution
START_TIME=$(date +%s)

if [ "$COVERAGE_ONLY" = true ]; then
    print_header "Running Tests with Coverage"
    run_tests_with_coverage
elif [ "$PERFORMANCE_ONLY" = true ]; then
    print_header "Running Performance Tests"
    run_test_suite "performance" "tests/performance"
elif [ -n "$SUITE_ONLY" ]; then
    print_header "Running $SUITE_ONLY Test Suite"
    run_test_suite "$SUITE_ONLY" "tests/$SUITE_ONLY"
elif [ "$QUICK_MODE" = true ]; then
    print_header "Running Quick Smoke Tests"
    # Run a subset of fast tests
    vendor/bin/phpunit --group=smoke --stop-on-failure > "$LOGS_DIR/smoke.log" 2>&1 || {
        echo -e "${RED}✗ Smoke tests failed${NC}"
        exit 1
    }
    echo -e "${GREEN}✓ Smoke tests passed${NC}"
else
    # Run all test suites
    print_header "Running Complete Test Suite"
    
    FAILED_SUITES=()
    
    # Unit tests
    if ! run_test_suite "unit" "tests/unit"; then
        FAILED_SUITES+=("unit")
    fi
    
    # Integration tests
    if ! run_test_suite "integration" "tests/integration"; then
        FAILED_SUITES+=("integration")
    fi
    
    # Security tests
    if ! run_test_suite "security" "tests/security"; then
        FAILED_SUITES+=("security")
    fi
    
    # Performance tests (optional, can be slow)
    echo -e "\n${YELLOW}Performance tests are optional and may take longer.${NC}"
    read -p "Run performance tests? [y/N]: " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        if ! run_test_suite "performance" "tests/performance"; then
            FAILED_SUITES+=("performance")
        fi
    fi
    
    # Summary
    print_header "Test Results Summary"
    
    if [ ${#FAILED_SUITES[@]} -eq 0 ]; then
        echo -e "${GREEN}✓ All test suites passed!${NC}"
    else
        echo -e "${RED}✗ Failed test suites: ${FAILED_SUITES[*]}${NC}"
        echo "Check individual log files in $LOGS_DIR/ for details"
        exit 1
    fi
fi

# Generate test report
print_header "Generating Test Report"

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

cat > "$REPORT_FILE" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>RSL Licensing Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 10px; border-radius: 5px; }
        .success { color: green; }
        .failure { color: red; }
        .section { margin: 20px 0; padding: 10px; border-left: 3px solid #ccc; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RSL Licensing Plugin - Test Report</h1>
        <p>Generated: $(date)</p>
        <p>Duration: ${DURATION} seconds</p>
    </div>
EOF

# Add coverage information if available
if [ -f "$COVERAGE_DIR/index.html" ]; then
    echo "<div class='section'><h2>Coverage Report</h2><p>Coverage report generated at <a href='$COVERAGE_DIR/index.html'>$COVERAGE_DIR/index.html</a></p></div>" >> "$REPORT_FILE"
fi

# Add log files
echo "<div class='section'><h2>Log Files</h2><ul>" >> "$REPORT_FILE"
for log_file in "$LOGS_DIR"/*.log; do
    if [ -f "$log_file" ]; then
        echo "<li><a href='$log_file'>$(basename "$log_file")</a></li>" >> "$REPORT_FILE"
    fi
done
echo "</ul></div>" >> "$REPORT_FILE"

echo "</body></html>" >> "$REPORT_FILE"

echo -e "${GREEN}✓ Test report generated: $REPORT_FILE${NC}"

# Final output
print_header "Test Execution Complete"
echo "Duration: ${DURATION} seconds"
echo "Logs directory: $LOGS_DIR/"
echo "Report file: $REPORT_FILE"

if [ -f "$COVERAGE_DIR/index.html" ]; then
    echo "Coverage report: $COVERAGE_DIR/index.html"
fi

echo -e "\n${GREEN}Test execution completed successfully!${NC}"