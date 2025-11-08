/**
 * Crypto Wallet Connector
 * Handles MetaMask and other Web3 wallet connections
 */

import { ethers } from 'ethers';

export class WalletConnector {
    constructor() {
        this.provider = null;
        this.signer = null;
        this.address = null;
        this.chainId = null;
        this.isConnected = false;
    }

    /**
     * Check if MetaMask is installed
     */
    isMetaMaskInstalled() {
        return typeof window.ethereum !== 'undefined';
    }

    /**
     * Connect to MetaMask
     */
    async connectMetaMask() {
        if (!this.isMetaMaskInstalled()) {
            throw new Error('MetaMask is not installed. Please install it from metamask.io');
        }

        try {
            // Request account access
            const accounts = await window.ethereum.request({
                method: 'eth_requestAccounts'
            });

            // Create provider and signer
            this.provider = new ethers.providers.Web3Provider(window.ethereum);
            this.signer = this.provider.getSigner();
            this.address = accounts[0];

            // Get chain ID
            const network = await this.provider.getNetwork();
            this.chainId = network.chainId;

            this.isConnected = true;

            // Setup event listeners
            this.setupEventListeners();

            return {
                address: this.address,
                chainId: this.chainId,
                network: this.getNetworkName(this.chainId)
            };
        } catch (error) {
            console.error('Failed to connect MetaMask:', error);
            throw error;
        }
    }

    /**
     * Setup event listeners for account and chain changes
     */
    setupEventListeners() {
        if (!window.ethereum) return;

        // Account changed
        window.ethereum.on('accountsChanged', (accounts) => {
            if (accounts.length === 0) {
                // User disconnected
                this.disconnect();
            } else {
                this.address = accounts[0];
                this.onAccountChanged(accounts[0]);
            }
        });

        // Chain changed
        window.ethereum.on('chainChanged', (chainId) => {
            // Reload page on chain change as recommended by MetaMask
            window.location.reload();
        });

        // Disconnect
        window.ethereum.on('disconnect', () => {
            this.disconnect();
        });
    }

    /**
     * Disconnect wallet
     */
    disconnect() {
        this.provider = null;
        this.signer = null;
        this.address = null;
        this.chainId = null;
        this.isConnected = false;
    }

    /**
     * Sign message
     */
    async signMessage(message) {
        if (!this.signer) {
            throw new Error('Wallet not connected');
        }

        try {
            const signature = await this.signer.signMessage(message);
            return signature;
        } catch (error) {
            console.error('Failed to sign message:', error);
            throw error;
        }
    }

    /**
     * Verify signature (client-side verification)
     */
    verifySignature(message, signature, expectedAddress) {
        try {
            const recoveredAddress = ethers.utils.verifyMessage(message, signature);
            return recoveredAddress.toLowerCase() === expectedAddress.toLowerCase();
        } catch (error) {
            console.error('Failed to verify signature:', error);
            return false;
        }
    }

    /**
     * Get balance (in ETH/BNB/MATIC)
     */
    async getBalance(address = null) {
        if (!this.provider) {
            throw new Error('Provider not initialized');
        }

        try {
            const addr = address || this.address;
            const balance = await this.provider.getBalance(addr);
            return ethers.utils.formatEther(balance);
        } catch (error) {
            console.error('Failed to get balance:', error);
            throw error;
        }
    }

    /**
     * Get ERC-20 token balance
     */
    async getTokenBalance(tokenAddress, walletAddress = null) {
        if (!this.provider) {
            throw new Error('Provider not initialized');
        }

        try {
            const addr = walletAddress || this.address;

            // ERC-20 ABI for balanceOf
            const abi = [
                'function balanceOf(address owner) view returns (uint256)',
                'function decimals() view returns (uint8)'
            ];

            const contract = new ethers.Contract(tokenAddress, abi, this.provider);

            const [balance, decimals] = await Promise.all([
                contract.balanceOf(addr),
                contract.decimals()
            ]);

            return ethers.utils.formatUnits(balance, decimals);
        } catch (error) {
            console.error('Failed to get token balance:', error);
            throw error;
        }
    }

    /**
     * Switch to specific network
     */
    async switchNetwork(chainId) {
        if (!window.ethereum) {
            throw new Error('MetaMask not found');
        }

        try {
            await window.ethereum.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: `0x${chainId.toString(16)}` }],
            });
        } catch (error) {
            // This error code indicates that the chain has not been added to MetaMask
            if (error.code === 4902) {
                await this.addNetwork(chainId);
            } else {
                throw error;
            }
        }
    }

    /**
     * Add network to MetaMask
     */
    async addNetwork(chainId) {
        const networks = {
            1: {
                chainName: 'Ethereum Mainnet',
                rpcUrls: ['https://ethereum.publicnode.com'],
                nativeCurrency: { name: 'Ether', symbol: 'ETH', decimals: 18 },
                blockExplorerUrls: ['https://etherscan.io']
            },
            56: {
                chainName: 'BNB Smart Chain',
                rpcUrls: ['https://bsc-dataseed.binance.org'],
                nativeCurrency: { name: 'BNB', symbol: 'BNB', decimals: 18 },
                blockExplorerUrls: ['https://bscscan.com']
            },
            137: {
                chainName: 'Polygon Mainnet',
                rpcUrls: ['https://polygon-rpc.com'],
                nativeCurrency: { name: 'MATIC', symbol: 'MATIC', decimals: 18 },
                blockExplorerUrls: ['https://polygonscan.com']
            }
        };

        const network = networks[chainId];
        if (!network) {
            throw new Error(`Network ${chainId} not supported`);
        }

        await window.ethereum.request({
            method: 'wallet_addEthereumChain',
            params: [{
                chainId: `0x${chainId.toString(16)}`,
                ...network
            }],
        });
    }

    /**
     * Get network name from chain ID
     */
    getNetworkName(chainId) {
        const networks = {
            1: 'ethereum',
            56: 'bsc',
            137: 'polygon',
            // Testnets
            5: 'goerli',
            11155111: 'sepolia',
            97: 'bsc-testnet',
            80001: 'mumbai'
        };

        return networks[chainId] || 'unknown';
    }

    /**
     * Get current gas price
     */
    async getGasPrice() {
        if (!this.provider) {
            throw new Error('Provider not initialized');
        }

        try {
            const gasPrice = await this.provider.getGasPrice();
            return ethers.utils.formatUnits(gasPrice, 'gwei');
        } catch (error) {
            console.error('Failed to get gas price:', error);
            throw error;
        }
    }

    /**
     * Estimate gas for transaction
     */
    async estimateGas(transaction) {
        if (!this.provider) {
            throw new Error('Provider not initialized');
        }

        try {
            const gasLimit = await this.provider.estimateGas(transaction);
            return gasLimit.toString();
        } catch (error) {
            console.error('Failed to estimate gas:', error);
            throw error;
        }
    }

    /**
     * Send transaction
     */
    async sendTransaction(to, value, data = '0x') {
        if (!this.signer) {
            throw new Error('Wallet not connected');
        }

        try {
            const tx = await this.signer.sendTransaction({
                to,
                value: ethers.utils.parseEther(value.toString()),
                data
            });

            return tx;
        } catch (error) {
            console.error('Failed to send transaction:', error);
            throw error;
        }
    }

    /**
     * Wait for transaction confirmation
     */
    async waitForTransaction(txHash, confirmations = 1) {
        if (!this.provider) {
            throw new Error('Provider not initialized');
        }

        try {
            const receipt = await this.provider.waitForTransaction(txHash, confirmations);
            return receipt;
        } catch (error) {
            console.error('Failed to wait for transaction:', error);
            throw error;
        }
    }

    /**
     * Callback for account changed (override this)
     */
    onAccountChanged(newAddress) {
        console.log('Account changed to:', newAddress);
    }

    /**
     * Callback for chain changed (override this)
     */
    onChainChanged(newChainId) {
        console.log('Chain changed to:', newChainId);
    }

    /**
     * Format address for display (0x1234...5678)
     */
    formatAddress(address, startChars = 6, endChars = 4) {
        if (!address) return '';
        return `${address.substring(0, startChars)}...${address.substring(address.length - endChars)}`;
    }

    /**
     * Check if address is valid
     */
    isValidAddress(address) {
        return ethers.utils.isAddress(address);
    }
}

// Export singleton instance
export const walletConnector = new WalletConnector();
