/**
 * Wallet UI Component for Alpine.js
 * Provides reactive wallet connection UI
 */

import { walletConnector } from './wallet-connector.js';

export function walletUI() {
    return {
        // State
        isConnecting: false,
        isConnected: false,
        address: null,
        shortAddress: null,
        network: null,
        chainId: null,
        balance: null,
        error: null,

        // Modal state
        showConnectModal: false,
        showNetworkModal: false,

        // Initialize
        init() {
            // Check if already connected
            this.checkConnection();

            // Setup callbacks
            walletConnector.onAccountChanged = (address) => {
                this.address = address;
                this.shortAddress = walletConnector.formatAddress(address);
                this.loadBalance();
            };
        },

        // Check if wallet is connected
        async checkConnection() {
            if (walletConnector.isConnected) {
                this.isConnected = true;
                this.address = walletConnector.address;
                this.shortAddress = walletConnector.formatAddress(walletConnector.address);
                this.chainId = walletConnector.chainId;
                this.network = walletConnector.getNetworkName(walletConnector.chainId);
                await this.loadBalance();
            }
        },

        // Connect wallet
        async connectWallet() {
            if (!walletConnector.isMetaMaskInstalled()) {
                this.error = 'กรุณาติดตั้ง MetaMask ก่อนใช้งาน';
                window.open('https://metamask.io/download/', '_blank');
                return;
            }

            this.isConnecting = true;
            this.error = null;

            try {
                const result = await walletConnector.connectMetaMask();

                this.isConnected = true;
                this.address = result.address;
                this.shortAddress = walletConnector.formatAddress(result.address);
                this.chainId = result.chainId;
                this.network = result.network;

                await this.loadBalance();

                // Close modal
                this.showConnectModal = false;

                // Show success message
                this.$dispatch('wallet-connected', { address: this.address, network: this.network });

            } catch (error) {
                console.error('Failed to connect:', error);
                this.error = error.message || 'ไม่สามารถเชื่อมต่อกระเป๋าเงินได้';
            } finally {
                this.isConnecting = false;
            }
        },

        // Disconnect wallet
        disconnect() {
            walletConnector.disconnect();
            this.isConnected = false;
            this.address = null;
            this.shortAddress = null;
            this.network = null;
            this.chainId = null;
            this.balance = null;
            this.$dispatch('wallet-disconnected');
        },

        // Load balance
        async loadBalance() {
            if (!this.isConnected) return;

            try {
                const balance = await walletConnector.getBalance();
                this.balance = parseFloat(balance).toFixed(4);
            } catch (error) {
                console.error('Failed to load balance:', error);
            }
        },

        // Switch network
        async switchNetwork(chainId) {
            this.isConnecting = true;
            this.error = null;

            try {
                await walletConnector.switchNetwork(chainId);
                this.chainId = chainId;
                this.network = walletConnector.getNetworkName(chainId);
                await this.loadBalance();
                this.showNetworkModal = false;
            } catch (error) {
                console.error('Failed to switch network:', error);
                this.error = 'ไม่สามารถเปลี่ยนเครือข่ายได้';
            } finally {
                this.isConnecting = false;
            }
        },

        // Sign message
        async signMessage(message) {
            if (!this.isConnected) {
                throw new Error('Wallet not connected');
            }

            try {
                return await walletConnector.signMessage(message);
            } catch (error) {
                console.error('Failed to sign message:', error);
                throw error;
            }
        },

        // Verify and connect to backend
        async verifyAndConnect() {
            if (!this.isConnected) {
                this.error = 'กรุณาเชื่อมต่อกระเป๋าเงินก่อน';
                return;
            }

            this.isConnecting = true;
            this.error = null;

            try {
                // Generate nonce from backend
                const nonceResponse = await fetch('/api/crypto/generate-nonce', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ address: this.address })
                });

                const { nonce, message } = await nonceResponse.json();

                // Sign the message
                const signature = await this.signMessage(message);

                // Verify signature with backend
                const verifyResponse = await fetch('/api/crypto/verify-signature', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        address: this.address,
                        network: this.network,
                        signature: signature,
                        message: message,
                        nonce: nonce
                    })
                });

                const result = await verifyResponse.json();

                if (result.success) {
                    // Redirect to wallet page or reload
                    window.location.href = result.redirect || '/user/crypto-wallet';
                } else {
                    this.error = result.message || 'การยืนยันล้มเหลว';
                }

            } catch (error) {
                console.error('Failed to verify:', error);
                this.error = 'เกิดข้อผิดพลาดในการยืนยัน';
            } finally {
                this.isConnecting = false;
            }
        },

        // Get network badge color
        getNetworkColor(network) {
            const colors = {
                'ethereum': 'bg-blue-500',
                'bsc': 'bg-yellow-500',
                'polygon': 'bg-purple-500',
            };
            return colors[network] || 'bg-gray-500';
        },

        // Get network icon
        getNetworkIcon(network) {
            const icons = {
                'ethereum': '⟠',
                'bsc': '⬡',
                'polygon': '⬢',
            };
            return icons[network] || '⬢';
        },

        // Copy address to clipboard
        copyAddress() {
            if (!this.address) return;

            navigator.clipboard.writeText(this.address).then(() => {
                this.$dispatch('address-copied');
            });
        }
    };
}
