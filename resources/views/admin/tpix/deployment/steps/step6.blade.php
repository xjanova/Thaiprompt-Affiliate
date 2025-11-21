@extends('admin.tpix.deployment._wizard-layout')

@section('step-content')
<div x-data="{
    creatingPool: false,
    addingLiquidity: false,
    poolCreated: {{ $config->dex_pool_address ? 'true' : 'false' }},
    poolResult: null,
    liquidityResult: null,

    // Liquidity amounts
    tpixAmount: {{ old('tpix_amount', 1000) }},
    tokenAmount: {{ old('token_amount', 1000000) }},

    // Calculated values
    get initialPrice() {
        if (this.tpixAmount <= 0 || this.tokenAmount <= 0) return 0;
        return (this.tpixAmount / this.tokenAmount).toFixed(8);
    },

    get tokenPerTPIX() {
        if (this.tpixAmount <= 0 || this.tokenAmount <= 0) return 0;
        return (this.tokenAmount / this.tpixAmount).toFixed(2);
    },

    get tpixPerToken() {
        if (this.tpixAmount <= 0 || this.tokenAmount <= 0) return 0;
        return (this.tpixAmount / this.tokenAmount).toFixed(8);
    },

    async createPool() {
        if (!confirm('ต้องการสร้าง Liquidity Pool หรือไม่?')) {
            return;
        }

        this.creatingPool = true;
        this.poolResult = null;

        try {
            const response = await fetch('{{ route('admin.tpix.deployment.step6.create-pool', $config->slug) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    tpix_amount: this.tpixAmount,
                    token_amount: this.tokenAmount
                })
            });

            const data = await response.json();
            this.poolResult = data;

            if (data.success) {
                this.poolCreated = true;
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }
        } catch (error) {
            this.poolResult = {
                success: false,
                message: 'เกิดข้อผิดพลาด: ' + error.message
            };
        } finally {
            this.creatingPool = false;
        }
    },

    async addLiquidity() {
        if (!confirm('ต้องการเพิ่ม Liquidity หรือไม่?')) {
            return;
        }

        this.addingLiquidity = true;
        this.liquidityResult = null;

        try {
            const response = await fetch('{{ route('admin.tpix.deployment.step6.add-liquidity', $config->slug) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    tpix_amount: this.tpixAmount,
                    token_amount: this.tokenAmount
                })
            });

            const data = await response.json();
            this.liquidityResult = data;

            if (data.success) {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            this.liquidityResult = {
                success: false,
                message: 'เกิดข้อผิดพลาด: ' + error.message
            };
        } finally {
            this.addingLiquidity = false;
        }
    }
}">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
            Step 6: DEX Integration
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            สร้าง Liquidity Pool และเพิ่ม Initial Liquidity สำหรับการ Swap
        </p>
    </div>

    {{-- Token Info Summary --}}
    <div class="p-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl mb-8">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
            </svg>
            ข้อมูล Token
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Token</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $config->token_symbol }}</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Contract Address</div>
                <div class="text-xs font-mono text-gray-900 dark:text-white truncate">{{ $config->contract_address }}</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Supply</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($config->total_supply) }}</div>
            </div>
        </div>
    </div>

    {{-- Pool Status --}}
    @if($config->dex_pool_address)
        <div class="p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl mb-8 border-2 border-green-500">
            <h3 class="text-xl font-semibold text-green-900 dark:text-green-100 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                ✅ Liquidity Pool สร้างสำเร็จ!
            </h3>

            <div class="space-y-4">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Pool Address</div>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-sm font-mono text-gray-900 dark:text-white break-all">{{ $config->dex_pool_address }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $config->dex_pool_address }}')"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                @if($config->initial_liquidity)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Initial TPIX</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($config->initial_liquidity['tpix_amount'] ?? 0, 2) }} TPIX
                            </div>
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Initial {{ $config->token_symbol }}</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($config->initial_liquidity['token_amount'] ?? 0, 2) }} {{ $config->token_symbol }}
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex gap-3">
                    <a href="{{ config('tpix.dex_url') }}/pool/{{ $config->dex_pool_address }}"
                       target="_blank"
                       class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-center font-semibold transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        ดู Pool บน DEX
                    </a>
                    <a href="{{ config('tpix.dex_url') }}/swap?outputCurrency={{ $config->contract_address }}"
                       target="_blank"
                       class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl text-center font-semibold transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        ทดสอบ Swap
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Create Pool Form --}}
        <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl mb-8">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z"></path>
                </svg>
                สร้าง Liquidity Pool
            </h3>

            <div class="space-y-6">
                {{-- TPIX Amount --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวน TPIX <span class="text-red-500">*</span>
                    </label>
                    <input type="number" x-model="tpixAmount" step="0.01" min="0"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                  focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition"
                           placeholder="1000" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        💡 แนะนำ: เริ่มต้นที่ 1,000 - 10,000 TPIX
                    </p>
                </div>

                {{-- Token Amount --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        จำนวน {{ $config->token_symbol }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" x-model="tokenAmount" step="1" min="0"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                  focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition"
                           placeholder="1000000" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        💡 แนะนำ: 5-20% ของ Total Supply สำหรับ Initial Liquidity
                    </p>
                </div>

                {{-- Price Calculation --}}
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border-2 border-purple-300 dark:border-purple-600">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">ราคาเริ่มต้น</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">1 TPIX =</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="tokenPerTPIX + ' {{ $config->token_symbol }}'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">1 {{ $config->token_symbol }} =</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="tpixPerToken + ' TPIX'"></span>
                        </div>
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-600 flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Initial Price:</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400" x-text="initialPrice + ' TPIX'"></span>
                        </div>
                    </div>
                </div>

                {{-- Warning --}}
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-r-xl">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1 text-sm text-yellow-800 dark:text-yellow-200">
                            <p class="font-semibold mb-1">คำเตือน:</p>
                            <ul class="space-y-1">
                                <li>• ราคาเริ่มต้นจะถูกกำหนดโดยอัตราส่วนที่คุณใส่</li>
                                <li>• ตรวจสอบว่ามี TPIX และ Token เพียงพอใน Wallet</li>
                                <li>• การสร้าง Pool ใช้ค่า Gas Fee</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Create Pool Button --}}
                <button @click="createPool()"
                        :disabled="creatingPool || tpixAmount <= 0 || tokenAmount <= 0"
                        class="w-full px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-3">
                    <svg x-show="!creatingPool" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <div x-show="creatingPool" class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                    <span x-text="creatingPool ? 'กำลังสร้าง Pool...' : 'สร้าง Liquidity Pool'"></span>
                </button>
            </div>
        </div>
    @endif

    {{-- Pool Creation Result --}}
    <div x-show="poolResult" class="mb-8">
        <div x-show="poolResult?.success"
             class="p-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-1">Pool สร้างสำเร็จ!</h4>
                    <p class="text-sm text-green-800 dark:text-green-200" x-text="poolResult?.message"></p>
                </div>
            </div>
        </div>

        <div x-show="poolResult && !poolResult?.success"
             class="p-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-red-900 dark:text-red-100 mb-1">Pool สร้างล้มเหลว</h4>
                    <p class="text-sm text-red-800 dark:text-red-200" x-text="poolResult?.message"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Liquidity Result --}}
    <div x-show="liquidityResult" class="mb-8">
        <div x-show="liquidityResult?.success"
             class="p-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-1">เพิ่ม Liquidity สำเร็จ!</h4>
                    <p class="text-sm text-green-800 dark:text-green-200" x-text="liquidityResult?.message"></p>
                </div>
            </div>
        </div>

        <div x-show="liquidityResult && !liquidityResult?.success"
             class="p-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-red-900 dark:text-red-100 mb-1">เพิ่ม Liquidity ล้มเหลว</h4>
                    <p class="text-sm text-red-800 dark:text-red-200" x-text="liquidityResult?.message"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Trading Guide --}}
    @if($config->dex_pool_address)
        <div class="p-6 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-2xl mb-8">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                คู่มือการใช้งาน
            </h3>

            <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                <div class="flex items-start gap-2">
                    <span class="font-semibold text-cyan-600">1.</span>
                    <span>ทดสอบการ Swap โดยไปที่ DEX และลอง Swap TPIX เป็น {{ $config->token_symbol }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="font-semibold text-cyan-600">2.</span>
                    <span>แชร์ Pool Address และ Contract Address ให้ผู้ใช้เพื่อเพิ่ม Token ใน Wallet</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="font-semibold text-cyan-600">3.</span>
                    <span>สามารถเพิ่ม Liquidity เพิ่มเติมได้ทุกเมื่อผ่าน DEX Interface</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="font-semibold text-cyan-600">4.</span>
                    <span>ตรวจสอบ Trading Volume และ Liquidity ผ่าน DEX Analytics</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex gap-4 pt-6">
        <a href="{{ route('admin.tpix.deployment.step5', $config->slug) }}"
           class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← ย้อนกลับ
        </a>

        @if($config->dex_pool_address)
            <a href="{{ route('admin.tpix.deployment.step7', $config->slug) }}"
               class="flex-1 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
                ถัดไป: Listing & Marketing →
            </a>
        @else
            <button disabled
                    class="flex-1 px-8 py-3 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-semibold rounded-xl cursor-not-allowed">
                กรุณาสร้าง Pool ก่อน
            </button>
        @endif
    </div>
</div>
@endsection
