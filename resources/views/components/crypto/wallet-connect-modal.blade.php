<!-- Wallet Connect Modal Component -->
<div x-data="walletUI()" x-init="init()" @wallet-connected.window="console.log('Wallet connected:', $event.detail)">
    <!-- Connect Wallet Button -->
    <button
        @click="showConnectModal = true"
        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-lg shadow-lg transition-all duration-200 transform hover:scale-105">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span>เชื่อมต่อกระเป๋าเงินภายนอก</span>
    </button>

    <!-- Connection Status (when connected) -->
    <div x-show="isConnected" class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium text-green-700 dark:text-green-400">เชื่อมต่อแล้ว</span>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-mono" x-text="shortAddress"></span>
                    <button @click="copyAddress()" class="ml-2 text-green-600 hover:text-green-700">
                        📋
                    </button>
                </p>
                <div class="mt-1 flex items-center space-x-2">
                    <span :class="getNetworkColor(network)" class="px-2 py-0.5 text-xs text-white rounded-full" x-text="network?.toUpperCase()"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400" x-show="balance">
                        Balance: <span x-text="balance"></span>
                    </span>
                </div>
            </div>
            <button @click="disconnect()" class="text-red-600 hover:text-red-700 text-sm">
                ตัดการเชื่อมต่อ
            </button>
        </div>
    </div>

    <!-- Connect Modal -->
    <div x-show="showConnectModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showConnectModal = false">

        <!-- Backdrop -->
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div @click="showConnectModal = false"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80"
                 x-show="showConnectModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                 x-show="showConnectModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="bg-white dark:bg-gray-800 px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            เชื่อมต่อกระเป๋าเงิน
                        </h3>
                        <button @click="showConnectModal = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        เลือกกระเป๋าเงินที่ต้องการเชื่อมต่อ
                    </p>

                    <!-- Error Message -->
                    <div x-show="error"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 dark:border-red-700 text-red-700 dark:text-red-400 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium" x-text="error"></p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="error = null" class="inline-flex text-red-400 hover:text-red-500 focus:outline-none">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Wallet Options -->
                    <div class="space-y-3">
                        <!-- MetaMask -->
                        <button @click="connectWallet()"
                                :disabled="isConnecting"
                                class="w-full flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 hover:from-orange-100 hover:to-amber-100 dark:hover:from-orange-900/30 dark:hover:to-amber-900/30 border-2 border-orange-200 dark:border-orange-700 rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-2xl">
                                    🦊
                                </div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">MetaMask</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">เชื่อมต่อผ่าน MetaMask</p>
                                </div>
                            </div>
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <!-- WalletConnect (Coming Soon) -->
                        <button disabled
                                class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 border-2 border-gray-200 dark:border-gray-600 rounded-xl opacity-50 cursor-not-allowed">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-2xl">
                                    🔗
                                </div>
                                <div class="ml-4 text-left">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">WalletConnect</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">เร็วๆ นี้</p>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Info Box -->
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                        <h5 class="flex items-center text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            ข้อมูลสำคัญ
                        </h5>
                        <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                            <li>• เราจะไม่เก็บ private key ของคุณ</li>
                            <li>• คุณสามารถตัดการเชื่อมต่อได้ตลอดเวลา</li>
                            <li>• รองรับ Ethereum, BSC และ Polygon</li>
                        </ul>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="isConnecting" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-90 dark:bg-opacity-90 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent"></div>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">กำลังเชื่อมต่อ...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verify and Connect Button (shown after MetaMask connected) -->
    <div x-show="isConnected && !isConnecting"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="mt-4">
        <button @click="verifyAndConnect()"
                class="w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-semibold rounded-lg shadow-lg transition-all duration-200 transform hover:scale-105">
            <span class="flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ยืนยันและเชื่อมต่อกับระบบ
            </span>
        </button>
    </div>

    <!-- Alert when not connected but trying to verify -->
    <div x-show="!isConnected && !showConnectModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="mt-4 p-4 bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 dark:border-yellow-700 text-yellow-700 dark:text-yellow-400 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">
                    กรุณาคลิกปุ่ม "เชื่อมต่อกระเป๋าเงินภายนอก" ด้านบนเพื่อเริ่มต้นเชื่อมต่อ
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
