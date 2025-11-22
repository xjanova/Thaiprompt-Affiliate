@extends('layouts.admin')

@section('title', 'ยืนยันการชำระเงิน - TPIX Deployment')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                💳 ยืนยันการชำระเงิน
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                กรุณาตรวจสอบรายละเอียดและยืนยันการชำระเงินก่อน Deploy Token
            </p>
        </div>

        {{-- Wallet Connection Status --}}
        <div class="mb-6">
            @php
                $pricingService = app(\App\Services\TPIX\TpixPricingService::class);
                $pricing = $pricingService->calculateTotalPrice($config);
                $requiredTPIX = $pricing['total'];
            @endphp
            <x-tpix.wallet-connector
                :requiredAmount="$requiredTPIX"
                :showBalance="true"
                :required="true"
            />
        </div>

        <div x-data="paymentConfirmation({{ json_encode($pricing) }}, {{ $requiredTPIX }})">
            {{-- Token Information Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 mb-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="text-3xl">🪙</span>
                    ข้อมูล Token ที่จะ Deploy
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อ Token</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $config->token_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Symbol</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $config->token_symbol }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Supply</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($config->total_supply) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Network</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            TPIX Chain ({{ $config->network }})
                        </p>
                    </div>
                </div>

                @if($config->contract_features && count($config->contract_features) > 0)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Features ที่เลือก</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($config->contract_features as $feature)
                        <span class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm font-medium">
                            ✓ {{ ucfirst($feature) }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Pricing Breakdown Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 mb-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="text-3xl">💰</span>
                    สรุปค่าบริการ
                </h2>

                <x-tpix.pricing-calculator
                    :config="$config"
                    :showBreakdown="true"
                />
            </div>

            {{-- Payment Agreement --}}
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-3xl p-6 mb-6 border-2 border-yellow-500">
                <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-200 mb-4 flex items-center gap-2">
                    ⚠️ ข้อตกลงการชำระเงิน
                </h3>
                <div class="space-y-3 text-sm text-yellow-800 dark:text-yellow-300">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               x-model="agreedToTerms"
                               class="mt-1 w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                        <span>
                            ฉันเข้าใจและยอมรับว่า:
                            <ul class="mt-2 ml-5 space-y-1 list-disc">
                                <li>การชำระเงินจะทำผ่าน Web3 Wallet ด้วย TPIX Token เท่านั้น</li>
                                <li>ค่า Gas Fee จะแยกจากค่าบริการและคำนวณเองโดย TPIX Chain</li>
                                <li>หาก Deploy สำเร็จแต่ Verify ล้มเหลว ระบบจะดำเนินการ Verify ให้อีกครั้ง</li>
                                <li>หาก Deploy ล้มเหลว จะได้รับเงินคืน 80% (หัก 20% ค่าดำเนินการ)</li>
                                <li>ไม่สามารถยกเลิกหรือขอคืนเงินได้หลังจาก Deploy สำเร็จแล้ว</li>
                            </ul>
                        </span>
                    </label>
                </div>
            </div>

            {{-- Payment Action Buttons --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
                {{-- Status Messages --}}
                <div x-show="statusMessage" x-cloak class="mb-6">
                    <div :class="{
                        'bg-green-50 dark:bg-green-900/20 border-green-500 text-green-800 dark:text-green-200': statusType === 'success',
                        'bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800 dark:text-red-200': statusType === 'error',
                        'bg-blue-50 dark:bg-blue-900/20 border-blue-500 text-blue-800 dark:text-blue-200': statusType === 'info'
                    }" class="p-4 rounded-xl border-l-4">
                        <p x-text="statusMessage"></p>
                    </div>
                </div>

                {{-- Transaction Hash Display --}}
                <div x-show="txHash" x-cloak class="mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-500">
                        <p class="text-sm text-blue-800 dark:text-blue-200 mb-2">Transaction Hash:</p>
                        <div class="flex items-center gap-2">
                            <code class="text-xs bg-white dark:bg-gray-900 px-3 py-2 rounded flex-1 overflow-x-auto" x-text="txHash"></code>
                            <button @click="copyTxHash()" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm">
                                Copy
                            </button>
                        </div>
                        <a :href="`https://explorer.tpix.io/tx/${txHash}`" target="_blank"
                           class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                            ดูบน Block Explorer →
                        </a>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4">
                    <button @click="cancelPayment()"
                            :disabled="processing"
                            class="flex-1 px-8 py-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        ← ย้อนกลับ
                    </button>

                    <button @click="processPayment()"
                            :disabled="!canProceedPayment()"
                            class="flex-1 px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-lg">
                        <span x-show="!processing">💳 ชำระเงิน {{ number_format($requiredTPIX, 2) }} TPIX</span>
                        <span x-show="processing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>กำลังประมวลผล...</span>
                        </span>
                    </button>
                </div>

                {{-- Helper Text --}}
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        💡 <strong>คำแนะนำ:</strong>
                        <span x-show="!window.ethereum">กรุณาเชื่อมต่อ Web3 Wallet ก่อนชำระเงิน</span>
                        <template x-if="window.ethereum && !agreedToTerms">
                            กรุณายอมรับข้อตกลงด้านบนก่อนดำเนินการ
                        </template>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับ Payment Confirmation
 */
function paymentConfirmation(pricing, requiredAmount) {
    return {
        pricing: pricing,
        requiredAmount: requiredAmount,
        agreedToTerms: false,
        processing: false,
        statusMessage: '',
        statusType: 'info',
        txHash: '',

        /**
         * ตรวจสอบว่าสามารถดำเนินการชำระเงินได้หรือไม่
         */
        canProceedPayment() {
            // ต้องเชื่อมต่อ wallet
            if (!window.ethereum) {
                return false;
            }

            // ต้องยอมรับข้อตกลง
            if (!this.agreedToTerms) {
                return false;
            }

            // ต้องไม่กำลังประมวลผล
            if (this.processing) {
                return false;
            }

            return true;
        },

        /**
         * ประมวลผลการชำระเงิน
         */
        async processPayment() {
            if (!this.canProceedPayment()) {
                return;
            }

            this.processing = true;
            this.statusMessage = 'กำลังเตรียมการชำระเงิน...';
            this.statusType = 'info';

            try {
                // 1. ตรวจสอบ wallet connection
                const accounts = await window.ethereum.request({ method: 'eth_accounts' });
                if (accounts.length === 0) {
                    throw new Error('กรุณาเชื่อมต่อ Web3 Wallet ก่อน');
                }
                const walletAddress = accounts[0];

                // 2. ตรวจสอบ Chain ID
                const chainId = await window.ethereum.request({ method: 'eth_chainId' });
                if (chainId !== '0x1389') { // 5001
                    this.statusMessage = 'กำลัง Switch ไป TPIX Chain...';
                    await window.ethereum.request({
                        method: 'wallet_switchEthereumChain',
                        params: [{ chainId: '0x1389' }],
                    });
                }

                // 3. ตรวจสอบ TPIX Balance
                this.statusMessage = 'กำลังตรวจสอบยอด TPIX...';
                const balance = await window.ethereum.request({
                    method: 'eth_getBalance',
                    params: [walletAddress, 'latest']
                });
                const balanceTPIX = parseInt(balance, 16) / 1e18;

                if (balanceTPIX < this.requiredAmount) {
                    throw new Error(`TPIX ไม่เพียงพอ! ต้องการ ${this.requiredAmount} TPIX แต่มีเพียง ${balanceTPIX.toFixed(2)} TPIX`);
                }

                // 4. สร้าง Payment Transaction
                this.statusMessage = 'กำลังสร้าง Transaction...';
                const serviceWallet = '{{ config("tpix-pricing.service_fee_wallet", "0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb") }}';
                const amountWei = '0x' + Math.floor(this.requiredAmount * 1e18).toString(16);

                const txHash = await window.ethereum.request({
                    method: 'eth_sendTransaction',
                    params: [{
                        from: walletAddress,
                        to: serviceWallet,
                        value: amountWei,
                        gas: '0x5208', // 21000
                    }],
                });

                this.txHash = txHash;
                this.statusMessage = 'Transaction ส่งสำเร็จ! กำลังรอ Confirmation...';
                this.statusType = 'success';

                // 5. บันทึกข้อมูลการชำระเงินผ่าน Laravel
                const response = await fetch('{{ route("admin.tpix.deployment.payment.process", $config->slug) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        wallet_address: walletAddress,
                        payment_tx_hash: txHash,
                        payment_amount: this.requiredAmount,
                        pricing_breakdown: this.pricing.breakdown,
                        subtotal_amount: this.pricing.subtotal,
                        discount_amount: this.pricing.discount,
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    this.statusMessage = '✅ ชำระเงินสำเร็จ! กำลัง Redirect ไปหน้า Deploy...';
                    this.statusType = 'success';

                    // Redirect ไปหน้า Deploy หลัง 2 วินาที
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.tpix.deployment.step", [$config->slug, 5]) }}';
                    }, 2000);
                } else {
                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }

            } catch (error) {
                console.error('Payment Error:', error);
                this.statusMessage = '❌ เกิดข้อผิดพลาด: ' + (error.message || 'Unknown error');
                this.statusType = 'error';
                this.processing = false;
            }
        },

        /**
         * ยกเลิกการชำระเงิน
         */
        cancelPayment() {
            if (confirm('ยืนยันการยกเลิกการชำระเงิน?')) {
                window.location.href = '{{ route("admin.tpix.deployment.step", [$config->slug, 4]) }}';
            }
        },

        /**
         * Copy Transaction Hash
         */
        async copyTxHash() {
            try {
                await navigator.clipboard.writeText(this.txHash);
                alert('Copy Transaction Hash สำเร็จ!');
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        },
    };
}
</script>
@endpush
@endsection
