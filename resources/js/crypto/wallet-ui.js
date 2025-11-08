/**
 * Wallet UI Component for Alpine.js
 * Provides reactive wallet connection UI
 */

import { walletConnector } from './wallet-connector.js';
import { toast } from './toast-notifications.js';

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
            // Validation: Check if MetaMask is installed
            if (!walletConnector.isMetaMaskInstalled()) {
                this.error = 'ไม่พบ MetaMask ในเบราว์เซอร์ของคุณ';
                toast.error('กรุณาติดตั้ง MetaMask Extension ก่อนใช้งาน', 7000);

                // Ask user to install MetaMask
                setTimeout(() => {
                    toast.confirm(
                        'คุณต้องการเปิดหน้าดาวน์โหลด MetaMask หรือไม่?',
                        () => {
                            window.open('https://metamask.io/download/', '_blank');
                        }
                    );
                }, 500);
                return;
            }

            this.isConnecting = true;
            this.error = null;
            toast.info('กำลังเชื่อมต่อกับ MetaMask...', 3000);

            try {
                const result = await walletConnector.connectMetaMask();

                // Validation: Check if we got valid address
                if (!result || !result.address) {
                    throw new Error('ไม่สามารถรับที่อยู่กระเป๋าเงินได้');
                }

                this.isConnected = true;
                this.address = result.address;
                this.shortAddress = walletConnector.formatAddress(result.address);
                this.chainId = result.chainId;
                this.network = result.network;

                await this.loadBalance();

                // Close modal
                this.showConnectModal = false;

                // Show success message
                toast.success(`เชื่อมต่อกระเป๋าเงินสำเร็จ! (${this.shortAddress})`, 5000);
                this.$dispatch('wallet-connected', { address: this.address, network: this.network });

            } catch (error) {
                console.error('Failed to connect:', error);

                // Better error messages based on error type
                let errorMessage = 'ไม่สามารถเชื่อมต่อกระเป๋าเงินได้';

                if (error.code === 4001) {
                    errorMessage = 'คุณปฏิเสธการเชื่อมต่อกับ MetaMask';
                } else if (error.code === -32002) {
                    errorMessage = 'กรุณาเปิด MetaMask และอนุมัติการเชื่อมต่อ';
                } else if (error.message) {
                    errorMessage = error.message;
                }

                this.error = errorMessage;
                toast.error(errorMessage, 7000);
            } finally {
                this.isConnecting = false;
            }
        },

        // Disconnect wallet
        disconnect() {
            toast.confirm(
                'คุณต้องการตัดการเชื่อมต่อกระเป๋าเงินหรือไม่?',
                () => {
                    walletConnector.disconnect();
                    this.isConnected = false;
                    this.address = null;
                    this.shortAddress = null;
                    this.network = null;
                    this.chainId = null;
                    this.balance = null;
                    this.$dispatch('wallet-disconnected');
                    toast.info('ตัดการเชื่อมต่อกระเป๋าเงินเรียบร้อยแล้ว', 3000);
                }
            );
        },

        // Load balance
        async loadBalance() {
            if (!this.isConnected) {
                toast.warning('กรุณาเชื่อมต่อกระเป๋าเงินก่อน', 3000);
                return;
            }

            try {
                const balance = await walletConnector.getBalance();
                this.balance = parseFloat(balance).toFixed(4);
            } catch (error) {
                console.error('Failed to load balance:', error);
                toast.error('ไม่สามารถโหลดยอดเงินได้', 3000);
            }
        },

        // Switch network
        async switchNetwork(chainId) {
            // Validation: Check if wallet is connected
            if (!this.isConnected) {
                toast.warning('กรุณาเชื่อมต่อกระเป๋าเงินก่อนเปลี่ยนเครือข่าย', 5000);
                return;
            }

            this.isConnecting = true;
            this.error = null;
            toast.info('กำลังเปลี่ยนเครือข่าย...', 3000);

            try {
                await walletConnector.switchNetwork(chainId);
                this.chainId = chainId;
                this.network = walletConnector.getNetworkName(chainId);
                await this.loadBalance();
                this.showNetworkModal = false;
                toast.success(`เปลี่ยนเครือข่ายเป็น ${this.network.toUpperCase()} สำเร็จ`, 4000);
            } catch (error) {
                console.error('Failed to switch network:', error);

                let errorMessage = 'ไม่สามารถเปลี่ยนเครือข่ายได้';
                if (error.code === 4001) {
                    errorMessage = 'คุณปฏิเสธการเปลี่ยนเครือข่าย';
                } else if (error.code === 4902) {
                    errorMessage = 'เครือข่ายนี้ยังไม่ถูกเพิ่มใน MetaMask';
                }

                this.error = errorMessage;
                toast.error(errorMessage, 5000);
            } finally {
                this.isConnecting = false;
            }
        },

        // Sign message
        async signMessage(message) {
            // Validation: Check if wallet is connected
            if (!this.isConnected) {
                const errorMsg = 'กรุณาเชื่อมต่อกระเป๋าเงินก่อนลงชื่อข้อความ';
                toast.error(errorMsg, 5000);
                throw new Error(errorMsg);
            }

            // Validation: Check if message is provided
            if (!message || message.trim() === '') {
                const errorMsg = 'ข้อความที่จะลงชื่อไม่สามารถเป็นค่าว่างได้';
                toast.error(errorMsg, 5000);
                throw new Error(errorMsg);
            }

            try {
                return await walletConnector.signMessage(message);
            } catch (error) {
                console.error('Failed to sign message:', error);

                let errorMessage = 'ไม่สามารถลงชื่อข้อความได้';
                if (error.code === 4001) {
                    errorMessage = 'คุณปฏิเสธการลงชื่อข้อความ';
                }

                toast.error(errorMessage, 5000);
                throw new Error(errorMessage);
            }
        },

        // Verify and connect to backend
        async verifyAndConnect() {
            // Validation: Check if wallet is connected
            if (!this.isConnected) {
                const errorMsg = 'กรุณาเชื่อมต่อกระเป๋าเงินก่อนยืนยัน';
                this.error = errorMsg;
                toast.error(errorMsg, 5000);
                return;
            }

            // Validation: Check if address exists
            if (!this.address) {
                const errorMsg = 'ไม่พบที่อยู่กระเป๋าเงิน กรุณาเชื่อมต่อใหม่อีกครั้ง';
                this.error = errorMsg;
                toast.error(errorMsg, 5000);
                return;
            }

            // Validation: Check if network is supported
            const supportedNetworks = ['ethereum', 'bsc', 'polygon'];
            if (!supportedNetworks.includes(this.network)) {
                const errorMsg = `เครือข่าย ${this.network} ยังไม่รองรับในขณะนี้ กรุณาเปลี่ยนเป็น Ethereum, BSC หรือ Polygon`;
                this.error = errorMsg;
                toast.error(errorMsg, 7000);
                return;
            }

            this.isConnecting = true;
            this.error = null;
            toast.info('กำลังสร้างข้อความสำหรับลงชื่อ...', 3000);

            try {
                // Validation: Check CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken || !csrfToken.content) {
                    throw new Error('ไม่พบ CSRF token กรุณาโหลดหน้าใหม่อีกครั้ง');
                }

                // Generate nonce from backend
                const nonceResponse = await fetch('/api/crypto/generate-nonce', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({ address: this.address })
                });

                // Validation: Check nonce response
                if (!nonceResponse.ok) {
                    const errorData = await nonceResponse.json().catch(() => ({}));
                    throw new Error(errorData.message || 'ไม่สามารถสร้าง nonce ได้');
                }

                const nonceData = await nonceResponse.json();

                // Validation: Check nonce data
                if (!nonceData.nonce || !nonceData.message) {
                    throw new Error('ข้อมูล nonce ไม่ถูกต้อง');
                }

                toast.info('กรุณาลงชื่อข้อความใน MetaMask...', 5000);

                // Sign the message
                const signature = await this.signMessage(nonceData.message);

                // Validation: Check signature
                if (!signature) {
                    throw new Error('ไม่สามารถลงชื่อข้อความได้');
                }

                toast.info('กำลังยืนยันลายเซ็นกับเซิร์ฟเวอร์...', 3000);

                // Verify signature with backend
                const verifyResponse = await fetch('/api/crypto/verify-signature', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content
                    },
                    body: JSON.stringify({
                        address: this.address,
                        network: this.network,
                        signature: signature,
                        message: nonceData.message,
                        nonce: nonceData.nonce
                    })
                });

                // Validation: Check verify response
                if (!verifyResponse.ok) {
                    const errorData = await verifyResponse.json().catch(() => ({}));
                    throw new Error(errorData.message || 'การยืนยันล้มเหลว');
                }

                const result = await verifyResponse.json();

                if (result.success) {
                    toast.success('ยืนยันตัวตนสำเร็จ! กำลังเปลี่ยนหน้า...', 3000);
                    // Redirect to wallet page or reload
                    setTimeout(() => {
                        window.location.href = result.redirect || '/user/crypto-wallet';
                    }, 1000);
                } else {
                    this.error = result.message || 'การยืนยันล้มเหลว';
                    toast.error(this.error, 7000);
                }

            } catch (error) {
                console.error('Failed to verify:', error);

                let errorMessage = 'เกิดข้อผิดพลาดในการยืนยัน';
                if (error.message) {
                    errorMessage = error.message;
                } else if (error.code === 4001) {
                    errorMessage = 'คุณปฏิเสธการลงชื่อข้อความ';
                }

                this.error = errorMessage;
                toast.error(errorMessage, 7000);
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
            // Validation: Check if address exists
            if (!this.address) {
                toast.warning('ไม่มีที่อยู่กระเป๋าเงินให้คัดลอก', 3000);
                return;
            }

            navigator.clipboard.writeText(this.address).then(() => {
                toast.success('คัดลอกที่อยู่กระเป๋าเงินแล้ว', 2000);
                this.$dispatch('address-copied');
            }).catch((error) => {
                console.error('Failed to copy address:', error);
                toast.error('ไม่สามารถคัดลอกที่อยู่ได้', 3000);
            });
        }
    };
}
