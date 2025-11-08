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
            const error = new Error('MetaMask ไม่ได้ติดตั้งในเบราว์เซอร์ กรุณาติดตั้งจาก metamask.io');
            error.code = 'METAMASK_NOT_INSTALLED';
            throw error;
        }

        try {
            // Request account access
            const accounts = await window.ethereum.request({
                method: 'eth_requestAccounts'
            });

            // Validate accounts
            if (!accounts || accounts.length === 0) {
                throw new Error('ไม่พบบัญชีในกระเป๋าเงิน กรุณาสร้างบัญชีใน MetaMask ก่อน');
            }

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

            // Enhance error messages
            if (error.code === 4001) {
                error.message = 'คุณปฏิเสธการเชื่อมต่อกับ MetaMask';
            } else if (error.code === -32002) {
                error.message = 'กรุณาเปิด MetaMask และอนุมัติคำขอการเชื่อมต่อ';
            } else if (error.code === -32603) {
                error.message = 'เกิดข้อผิดพลาดภายใน กรุณาลองใหม่อีกครั้ง';
            } else if (!error.message || error.message === '') {
                error.message = 'ไม่สามารถเชื่อมต่อกับ MetaMask ได้';
            }

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
            const error = new Error('กระเป๋าเงินยังไม่ได้เชื่อมต่อ กรุณาเชื่อมต่อก่อน');
            error.code = 'WALLET_NOT_CONNECTED';
            throw error;
        }

        if (!message || message.trim() === '') {
            const error = new Error('ข้อความสำหรับลงชื่อไม่สามารถเป็นค่าว่างได้');
            error.code = 'INVALID_MESSAGE';
            throw error;
        }

        try {
            const signature = await this.signer.signMessage(message);
            return signature;
        } catch (error) {
            console.error('Failed to sign message:', error);

            // Enhance error messages
            if (error.code === 4001) {
                error.message = 'คุณปฏิเสธการลงชื่อข้อความใน MetaMask';
            } else if (error.code === 'ACTION_REJECTED') {
                error.message = 'คุณยกเลิกการลงชื่อข้อความ';
            } else if (!error.message || error.message === '') {
                error.message = 'ไม่สามารถลงชื่อข้อความได้ กรุณาลองใหม่อีกครั้ง';
            }

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
            const error = new Error('ระบบยังไม่พร้อมใช้งาน กรุณาเชื่อมต่อกระเป๋าเงินก่อน');
            error.code = 'PROVIDER_NOT_INITIALIZED';
            throw error;
        }

        try {
            const addr = address || this.address;

            if (!addr) {
                throw new Error('ไม่พบที่อยู่กระเป๋าเงิน');
            }

            const balance = await this.provider.getBalance(addr);
            return ethers.utils.formatEther(balance);
        } catch (error) {
            console.error('Failed to get balance:', error);

            if (!error.message || error.message === '') {
                error.message = 'ไม่สามารถดึงข้อมูลยอดเงินได้ กรุณาลองใหม่อีกครั้ง';
            }

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
            const error = new Error('ไม่พบ MetaMask กรุณาติดตั้งก่อนใช้งาน');
            error.code = 'METAMASK_NOT_FOUND';
            throw error;
        }

        if (!chainId) {
            const error = new Error('กรุณาระบุ Chain ID ของเครือข่าย');
            error.code = 'INVALID_CHAIN_ID';
            throw error;
        }

        try {
            await window.ethereum.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: `0x${chainId.toString(16)}` }],
            });
        } catch (error) {
            console.error('Failed to switch network:', error);

            // This error code indicates that the chain has not been added to MetaMask
            if (error.code === 4902) {
                try {
                    await this.addNetwork(chainId);
                } catch (addError) {
                    addError.message = 'ไม่สามารถเพิ่มเครือข่ายใน MetaMask ได้';
                    throw addError;
                }
            } else if (error.code === 4001) {
                error.message = 'คุณปฏิเสธการเปลี่ยนเครือข่าย';
                throw error;
            } else {
                if (!error.message || error.message === '') {
                    error.message = 'ไม่สามารถเปลี่ยนเครือข่ายได้ กรุณาลองใหม่อีกครั้ง';
                }
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
            const error = new Error(`เครือข่าย ${chainId} ยังไม่รองรับในขณะนี้`);
            error.code = 'UNSUPPORTED_NETWORK';
            throw error;
        }

        try {
            await window.ethereum.request({
                method: 'wallet_addEthereumChain',
                params: [{
                    chainId: `0x${chainId.toString(16)}`,
                    ...network
                }],
            });
        } catch (error) {
            console.error('Failed to add network:', error);

            if (error.code === 4001) {
                error.message = 'คุณปฏิเสธการเพิ่มเครือข่าย';
            } else if (!error.message || error.message === '') {
                error.message = 'ไม่สามารถเพิ่มเครือข่ายใน MetaMask ได้';
            }

            throw error;
        }
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
            const error = new Error('กระเป๋าเงินยังไม่ได้เชื่อมต่อ กรุณาเชื่อมต่อก่อนส่งธุรกรรม');
            error.code = 'WALLET_NOT_CONNECTED';
            throw error;
        }

        // Validate parameters
        if (!to || !ethers.utils.isAddress(to)) {
            const error = new Error('ที่อยู่ผู้รับไม่ถูกต้อง');
            error.code = 'INVALID_RECIPIENT';
            throw error;
        }

        if (!value || parseFloat(value) <= 0) {
            const error = new Error('จำนวนเงินต้องมากกว่า 0');
            error.code = 'INVALID_AMOUNT';
            throw error;
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

            if (error.code === 4001 || error.code === 'ACTION_REJECTED') {
                error.message = 'คุณปฏิเสธการส่งธุรกรรม';
            } else if (error.code === 'INSUFFICIENT_FUNDS') {
                error.message = 'ยอดเงินไม่เพียงพอสำหรับการทำธุรกรรม';
            } else if (!error.message || error.message === '') {
                error.message = 'ไม่สามารถส่งธุรกรรมได้ กรุณาลองใหม่อีกครั้ง';
            }

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
