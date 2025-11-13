#!/bin/bash

###############################################################################
# TPIX Blockchain Node Start Script
# This script starts a TPIX blockchain node
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
TPIX_HOME="${TPIX_HOME:-$HOME/.tpix}"
DATA_DIR="$TPIX_HOME/data"
CONFIG_DIR="$TPIX_HOME/config"
LOG_DIR="$TPIX_HOME/logs"
LOG_FILE="$LOG_DIR/node-$(date +%Y%m%d-%H%M%S).log"

echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}Starting TPIX Blockchain Node${NC}"
echo -e "${GREEN}=====================================${NC}"

# Check if node is already running
if [ -f "$TPIX_HOME/node.pid" ]; then
    PID=$(cat "$TPIX_HOME/node.pid")
    if ps -p $PID > /dev/null 2>&1; then
        echo -e "${RED}Node is already running with PID: $PID${NC}"
        exit 1
    fi
fi

# Check if setup was completed
if [ ! -f "$CONFIG_DIR/node-config.json" ]; then
    echo -e "${RED}Node not configured. Please run setup-node.sh first.${NC}"
    exit 1
fi

# Load configuration
VALIDATOR_ADDRESS=$(jq -r '.validatorAddress' "$CONFIG_DIR/node-config.json")
CHAIN_ID=$(jq -r '.chainId' "$CONFIG_DIR/node-config.json")

echo -e "${YELLOW}Chain ID: $CHAIN_ID${NC}"
echo -e "${YELLOW}Validator Address: $VALIDATOR_ADDRESS${NC}"
echo -e "${YELLOW}Data Directory: $DATA_DIR${NC}"
echo -e "${YELLOW}Log File: $LOG_FILE${NC}"

# Start the node
echo -e "${GREEN}Starting node...${NC}"

polygon-edge server \
    --data-dir "$DATA_DIR" \
    --chain genesis.json \
    --grpc-address 0.0.0.0:9632 \
    --libp2p 0.0.0.0:1478 \
    --jsonrpc 0.0.0.0:8545 \
    --prometheus 0.0.0.0:5001 \
    --block-gas-target 30000000 \
    --max-peers 40 \
    --max-inbound-peers 32 \
    --max-outbound-peers 8 \
    --log-level INFO \
    --seal \
    > "$LOG_FILE" 2>&1 &

NODE_PID=$!
echo $NODE_PID > "$TPIX_HOME/node.pid"

sleep 3

# Check if node is running
if ps -p $NODE_PID > /dev/null; then
    echo -e "${GREEN}=====================================${NC}"
    echo -e "${GREEN}Node started successfully!${NC}"
    echo -e "${GREEN}=====================================${NC}"
    echo -e "${YELLOW}PID: $NODE_PID${NC}"
    echo -e "${YELLOW}RPC Endpoint: http://localhost:8545${NC}"
    echo -e "${YELLOW}WebSocket: ws://localhost:8546${NC}"
    echo -e "${YELLOW}GRPC: localhost:9632${NC}"
    echo -e "${YELLOW}Metrics: http://localhost:5001${NC}"
    echo ""
    echo -e "${GREEN}To check logs:${NC}"
    echo -e "${YELLOW}tail -f $LOG_FILE${NC}"
    echo ""
    echo -e "${GREEN}To stop the node:${NC}"
    echo -e "${YELLOW}./stop-node.sh${NC}"
else
    echo -e "${RED}Failed to start node. Check logs at: $LOG_FILE${NC}"
    rm -f "$TPIX_HOME/node.pid"
    exit 1
fi
