#!/bin/bash

###############################################################################
# TPIX Blockchain Node Stop Script
# This script stops a running TPIX blockchain node
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
TPIX_HOME="${TPIX_HOME:-$HOME/.tpix}"
PID_FILE="$TPIX_HOME/node.pid"

echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}Stopping TPIX Blockchain Node${NC}"
echo -e "${GREEN}=====================================${NC}"

# Check if node is running
if [ ! -f "$PID_FILE" ]; then
    echo -e "${YELLOW}No PID file found. Node may not be running.${NC}"
    exit 0
fi

PID=$(cat "$PID_FILE")

if ! ps -p $PID > /dev/null 2>&1; then
    echo -e "${YELLOW}Node is not running (PID: $PID not found)${NC}"
    rm -f "$PID_FILE"
    exit 0
fi

echo -e "${YELLOW}Stopping node (PID: $PID)...${NC}"

# Try graceful shutdown first
kill -TERM $PID

# Wait for process to stop (max 30 seconds)
for i in {1..30}; do
    if ! ps -p $PID > /dev/null 2>&1; then
        echo -e "${GREEN}Node stopped successfully!${NC}"
        rm -f "$PID_FILE"
        exit 0
    fi
    sleep 1
    echo -n "."
done

echo ""
echo -e "${YELLOW}Graceful shutdown failed. Forcing shutdown...${NC}"

# Force kill if graceful shutdown failed
kill -KILL $PID

sleep 2

if ! ps -p $PID > /dev/null 2>&1; then
    echo -e "${GREEN}Node stopped successfully (forced)!${NC}"
    rm -f "$PID_FILE"
    exit 0
else
    echo -e "${RED}Failed to stop node${NC}"
    exit 1
fi
