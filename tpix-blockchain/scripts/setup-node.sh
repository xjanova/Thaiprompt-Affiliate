#!/bin/bash

###############################################################################
# TPIX Blockchain Node Setup Script
# This script sets up a TPIX blockchain node with all necessary configurations
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
TPIX_HOME="${TPIX_HOME:-$HOME/.tpix}"
CHAIN_ID=7000
NETWORK_NAME="TPIX Network"
DATA_DIR="$TPIX_HOME/data"
CONFIG_DIR="$TPIX_HOME/config"

echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}TPIX Blockchain Node Setup${NC}"
echo -e "${GREEN}=====================================${NC}"

# Check if Polygon Edge is installed
if ! command -v polygon-edge &> /dev/null; then
    echo -e "${YELLOW}Polygon Edge not found. Installing...${NC}"

    # Detect OS
    OS=$(uname -s)
    ARCH=$(uname -m)

    if [ "$OS" == "Linux" ]; then
        if [ "$ARCH" == "x86_64" ]; then
            wget https://github.com/0xPolygon/polygon-edge/releases/latest/download/polygon-edge_linux_amd64.tar.gz
            tar -xzf polygon-edge_linux_amd64.tar.gz
            sudo mv polygon-edge /usr/local/bin/
            rm polygon-edge_linux_amd64.tar.gz
        else
            echo -e "${RED}Unsupported architecture: $ARCH${NC}"
            exit 1
        fi
    elif [ "$OS" == "Darwin" ]; then
        if [ "$ARCH" == "arm64" ]; then
            wget https://github.com/0xPolygon/polygon-edge/releases/latest/download/polygon-edge_darwin_arm64.tar.gz
            tar -xzf polygon-edge_darwin_arm64.tar.gz
            sudo mv polygon-edge /usr/local/bin/
            rm polygon-edge_darwin_arm64.tar.gz
        else
            wget https://github.com/0xPolygon/polygon-edge/releases/latest/download/polygon-edge_darwin_amd64.tar.gz
            tar -xzf polygon-edge_darwin_amd64.tar.gz
            sudo mv polygon-edge /usr/local/bin/
            rm polygon-edge_darwin_amd64.tar.gz
        fi
    else
        echo -e "${RED}Unsupported OS: $OS${NC}"
        exit 1
    fi

    echo -e "${GREEN}Polygon Edge installed successfully!${NC}"
fi

# Create directories
echo -e "${YELLOW}Creating directories...${NC}"
mkdir -p "$DATA_DIR"
mkdir -p "$CONFIG_DIR"
mkdir -p "$TPIX_HOME/logs"

# Generate validator keys
echo -e "${YELLOW}Generating validator keys...${NC}"
polygon-edge secrets init --data-dir "$DATA_DIR"

# Copy genesis configuration
echo -e "${YELLOW}Setting up genesis configuration...${NC}"
cp ../config/genesis.json "$CONFIG_DIR/genesis.json"
cp ../config/chain-spec.json "$CONFIG_DIR/chain-spec.json"

# Generate genesis with premined balance
echo -e "${YELLOW}Generating genesis block...${NC}"

# Get validator address
VALIDATOR_ADDRESS=$(polygon-edge secrets output --data-dir "$DATA_DIR" | grep "Public key (address)" | awk '{print $NF}')

echo -e "${GREEN}Validator address: $VALIDATOR_ADDRESS${NC}"

# Create genesis with premined TPIX
polygon-edge genesis \
    --consensus ibft \
    --ibft-validators-prefix-path "$DATA_DIR" \
    --bootnode "/dns4/node1/tcp/1478/p2p/16Uiu2HAmJxxH1tScDX2rLGSU9exnuvZKNM9SoK3v315azp68DLPW" \
    --premine 0x0000000000000000000000000000000000000000:0x5F5E1000000000000000000000 \
    --premine $VALIDATOR_ADDRESS:0x56BC75E2D63100000 \
    --epoch-size 100000 \
    --block-time 2s \
    --chain-id $CHAIN_ID \
    --name "$NETWORK_NAME" \
    --block-gas-limit 30000000 \
    --pos

# Save configuration
cat > "$CONFIG_DIR/node-config.json" <<EOF
{
  "chainId": $CHAIN_ID,
  "networkName": "$NETWORK_NAME",
  "dataDir": "$DATA_DIR",
  "validatorAddress": "$VALIDATOR_ADDRESS",
  "rpcAddr": "0.0.0.0:8545",
  "wsAddr": "0.0.0.0:8546",
  "grpcAddr": "0.0.0.0:9632",
  "libp2pAddr": "0.0.0.0:1478",
  "prometheusAddr": "0.0.0.0:5001",
  "blockGasLimit": 30000000,
  "blockTime": 2
}
EOF

echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}Node setup completed successfully!${NC}"
echo -e "${GREEN}=====================================${NC}"
echo -e "${YELLOW}Configuration saved to: $CONFIG_DIR${NC}"
echo -e "${YELLOW}Data directory: $DATA_DIR${NC}"
echo -e "${YELLOW}Validator address: $VALIDATOR_ADDRESS${NC}"
echo ""
echo -e "${GREEN}To start the node, run:${NC}"
echo -e "${YELLOW}./start-node.sh${NC}"
