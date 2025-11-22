{{--
    TPIX Wallet Connector Component

    Component สำหรับเชื่อมต่อกระเป๋า Web3/TPIX
    รองรับ MetaMask, WalletConnect, และ TPIX Wallet

    Props:
    - required (bool): บังคับต้องเชื่อม wallet ก่อนดำเนินการต่อ
    - showBalance (bool): แสดงยอด TPIX balance
--}}

@props([
    'required' => true,
    'showBalance' => true,
    'requiredAmount' => 0, // จำนวน TPIX ที่ต้องการ
])

<div x-data="tpixWallet({{ $required ? 'true' : 'false' }}, {{ $requiredAmount }})"
     x-init="init()"
     class="relative">

    {{-- Wallet Not Connected --}}
    <div x-show="!connected" x-cloak>
        <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl border-2 border-purple-300 dark:border-purple-700">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>

                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        เชื่อมต่อกระเป๋า TPIX
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        คุณต้องเชื่อมต่อกระเป๋า Web3 เพื่อชำระค่าบริการด้วย TPIX tokens
                    </p>

                    {{-- Connect Buttons --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {{-- MetaMask --}}
                        <button @click="connectMetaMask()"
                                :disabled="connecting"
                                class="flex items-center justify-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-purple-500 hover:shadow-lg transition disabled:opacity-50">
                            <img src="/images/wallets/metamask.svg" alt="MetaMask" class="w-6 h-6" onerror="this.style.display='none'">
                            <span class="font-semibold text-gray-900 dark:text-white">MetaMask</span>
                        </button>

                        {{-- WalletConnect --}}
                        <button @click="connectWalletConnect()"
                                :disabled="connecting"
                                class="flex items-center justify-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-purple-500 hover:shadow-lg transition disabled:opacity-50">
                            <img src="/images/wallets/walletconnect.svg" alt="WalletConnect" class="w-6 h-6" onerror="this.style.display='none'">
                            <span class="font-semibold text-gray-900 dark:text-white">WalletConnect</span>
                        </button>

                        {{-- TPIX Wallet --}}
                        <button @click="connectTpixWallet()"
                                :disabled="connecting"
                                class="flex items-center justify-center gap-3 px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:shadow-lg transition disabled:opacity-50">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 2a6 6 0 110 12 6 6 0 010-12z"></path>
                            </svg>
                            <span class="font-semibold">TPIX Wallet</span>
                        </button>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="connecting" x-cloak class="mt-4 flex items-center gap-2 text-purple-600 dark:text-purple-400">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-600"></div>
                        <span class="text-sm">กำลังเชื่อมต่อ...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Wallet Connected --}}
    <div x-show="connected" x-cloak>
        <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border-2 border-green-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                    {{-- Wallet Icon --}}
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">เชื่อมต่อแล้ว</span>
                            <span class="px-2 py-0.5 bg-green-500 text-white text-xs rounded-full" x-text="walletType"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <code class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate" x-text="formatAddress(address)"></code>
                            <button @click="copyAddress()"
                                    class="p-1 hover:bg-green-100 dark:hover:bg-green-900/30 rounded transition"
                                    title="คัดลอก Address">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Balance Display --}}
                <div x-show="{{ $showBalance ? 'true' : 'false' }}" class="flex items-center gap-4 ml-4">
                    <div class="text-right">
                        <div class="text-xs text-gray-500 dark:text-gray-400">ยอดคงเหลือ</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-1">
                            <span x-text="formatBalance(balance)"></span>
                            <span class="text-sm text-purple-600">TPIX</span>
                        </div>
                    </div>

                    {{-- Refresh Balance --}}
                    <button @click="refreshBalance()"
                            :disabled="refreshing"
                            class="p-2 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition"
                            title="รีเฟรชยอดเงิน">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400"
                             :class="{ 'animate-spin': refreshing }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>

                    {{-- Disconnect Button --}}
                    <button @click="disconnect()"
                            class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">
                        ตัดการเชื่อมต่อ
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Insufficient TPIX Balance Warning --}}
    <div x-show="connected && requiredAmount > 0 && !hasEnoughTPIX()" x-cloak class="mt-4">
        <div class="p-6 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-2xl border-2 border-yellow-500">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>

                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-2">
                        ⚠️ TPIX ไม่เพียงพอ
                    </h3>
                    <div class="space-y-2 text-sm text-yellow-800 dark:text-yellow-200 mb-4">
                        <div class="flex justify-between">
                            <span>ยอด TPIX ปัจจุบัน:</span>
                            <span class="font-bold" x-text="formatBalance(balance) + ' TPIX'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>ต้องการ:</span>
                            <span class="font-bold text-orange-600 dark:text-orange-400" x-text="formatBalance(requiredAmount) + ' TPIX'"></span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-yellow-300 dark:border-yellow-700">
                            <span>ขาดอีก:</span>
                            <span class="font-bold text-red-600 dark:text-red-400" x-text="formatBalance(getShortage()) + ' TPIX'"></span>
                        </div>
                    </div>

                    <a :href="getSwapUrl()"
                       target="_blank"
                       class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-700 hover:to-orange-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span>Swap เหรียญเป็น TPIX ที่ DEX</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>

                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-3 text-center">
                        💡 หลังจาก Swap เสร็จแล้ว กลับมากดปุ่ม "รีเฟรชยอดเงิน" ด้านบน
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Error Message --}}
    <div x-show="error" x-cloak class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-xl">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <div>
                <h4 class="font-semibold text-red-900 dark:text-red-100">เกิดข้อผิดพลาด</h4>
                <p class="text-sm text-red-800 dark:text-red-200" x-text="error"></p>
            </div>
            <button @click="error = null" class="ml-auto text-red-600 hover:text-red-800">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
/**
 * TPIX Wallet Alpine.js Component
 */
function tpixWallet(required = true, requiredAmount = 0) {
    return {
        // State
        connected: false,
        connecting: false,
        refreshing: false,
        address: null,
        balance: 0,
        chainId: null,
        walletType: null,
        error: null,
        requiredAmount: requiredAmount,

        // Constants
        TPIX_CHAIN_ID: '0x1389', // 5001 in hex (TPIX Chain ID)
        TPIX_RPC_URL: '{{ config('tpix.rpc_url', 'https://rpc.tpix.io') }}',
        DEX_URL: '{{ config('tpix.dex_url', 'https://dex.tpix.io') }}',

        /**
         * Initialize component
         */
        init() {
            // ตรวจสอบว่าเคยเชื่อมต่อไว้หรือไม่
            const savedAddress = localStorage.getItem('tpix_wallet_address');
            const savedType = localStorage.getItem('tpix_wallet_type');

            if (savedAddress && savedType) {
                this.autoConnect(savedAddress, savedType);
            }

            // Listen for account changes
            if (window.ethereum) {
                window.ethereum.on('accountsChanged', (accounts) => {
                    if (accounts.length === 0) {
                        this.disconnect();
                    } else {
                        this.address = accounts[0];
                        this.refreshBalance();
                    }
                });

                window.ethereum.on('chainChanged', () => {
                    window.location.reload();
                });
            }
        },

        /**
         * Auto-connect จาก saved session
         */
        async autoConnect(address, type) {
            try {
                this.address = address;
                this.walletType = type;
                this.connected = true;
                await this.refreshBalance();
            } catch (error) {
                console.error('Auto-connect failed:', error);
                this.disconnect();
            }
        },

        /**
         * Connect MetaMask
         */
        async connectMetaMask() {
            if (typeof window.ethereum === 'undefined') {
                this.error = 'กรุณาติดตั้ง MetaMask ก่อนใช้งาน';
                window.open('https://metamask.io/download/', '_blank');
                return;
            }

            this.connecting = true;
            this.error = null;

            try {
                // Request accounts
                const accounts = await window.ethereum.request({
                    method: 'eth_requestAccounts'
                });

                if (accounts.length === 0) {
                    throw new Error('ไม่พบ account');
                }

                this.address = accounts[0];
                this.walletType = 'MetaMask';

                // Switch to TPIX Chain
                await this.switchToTpixChain();

                // Get balance
                await this.refreshBalance();

                // Save session
                localStorage.setItem('tpix_wallet_address', this.address);
                localStorage.setItem('tpix_wallet_type', 'MetaMask');

                this.connected = true;
            } catch (error) {
                console.error('MetaMask connection error:', error);
                this.error = error.message || 'ไม่สามารถเชื่อมต่อ MetaMask ได้';
            } finally {
                this.connecting = false;
            }
        },

        /**
         * Connect WalletConnect
         */
        async connectWalletConnect() {
            this.error = 'WalletConnect กำลังพัฒนา...';
            // TODO: Implement WalletConnect
        },

        /**
         * Connect TPIX Wallet
         */
        async connectTpixWallet() {
            this.error = 'TPIX Wallet กำลังพัฒนา...';
            // TODO: Implement TPIX Wallet
        },

        /**
         * Switch to TPIX Chain
         */
        async switchToTpixChain() {
            try {
                await window.ethereum.request({
                    method: 'wallet_switchEthereumChain',
                    params: [{ chainId: this.TPIX_CHAIN_ID }],
                });
            } catch (switchError) {
                // Chain not added, try to add it
                if (switchError.code === 4902) {
                    await this.addTpixChain();
                } else {
                    throw switchError;
                }
            }
        },

        /**
         * Add TPIX Chain to wallet
         */
        async addTpixChain() {
            await window.ethereum.request({
                method: 'wallet_addEthereumChain',
                params: [{
                    chainId: this.TPIX_CHAIN_ID,
                    chainName: 'TPIX Blockchain',
                    nativeCurrency: {
                        name: 'TPIX',
                        symbol: 'TPIX',
                        decimals: 18
                    },
                    rpcUrls: [this.TPIX_RPC_URL],
                    blockExplorerUrls: ['{{ config('tpix.explorer_url', 'https://explorer.tpix.io') }}']
                }]
            });
        },

        /**
         * Refresh balance
         */
        async refreshBalance() {
            if (!this.address) return;

            this.refreshing = true;

            try {
                const balance = await window.ethereum.request({
                    method: 'eth_getBalance',
                    params: [this.address, 'latest']
                });

                // Convert from wei to TPIX (18 decimals)
                this.balance = parseInt(balance, 16) / 1e18;
            } catch (error) {
                console.error('Error fetching balance:', error);
                this.balance = 0;
            } finally {
                this.refreshing = false;
            }
        },

        /**
         * Disconnect wallet
         */
        disconnect() {
            this.connected = false;
            this.address = null;
            this.balance = 0;
            this.walletType = null;
            this.chainId = null;

            localStorage.removeItem('tpix_wallet_address');
            localStorage.removeItem('tpix_wallet_type');
        },

        /**
         * Copy address to clipboard
         */
        async copyAddress() {
            try {
                await navigator.clipboard.writeText(this.address);
                // TODO: Show toast notification
                console.log('Address copied!');
            } catch (error) {
                console.error('Copy failed:', error);
            }
        },

        /**
         * Format address (0x1234...5678)
         */
        formatAddress(address) {
            if (!address) return '';
            return `${address.slice(0, 6)}...${address.slice(-4)}`;
        },

        /**
         * Format balance
         */
        formatBalance(balance) {
            return new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(balance);
        },

        /**
         * ตรวจสอบว่ามี TPIX เพียงพอหรือไม่
         */
        hasEnoughTPIX() {
            if (this.requiredAmount <= 0) return true;
            return this.balance >= this.requiredAmount;
        },

        /**
         * คำนวณจำนวน TPIX ที่ขาด
         */
        getShortage() {
            if (this.hasEnoughTPIX()) return 0;
            return this.requiredAmount - this.balance;
        },

        /**
         * สร้าง URL สำหรับ Swap ไปยัง DEX
         */
        getSwapUrl() {
            // สร้าง URL ไปยัง DEX พร้อม pre-fill จำนวน TPIX ที่ต้องการ
            const shortage = this.getShortage();
            return `${this.DEX_URL}/swap?outputCurrency=TPIX&outputAmount=${shortage}`;
        },
    };
}
</script>
