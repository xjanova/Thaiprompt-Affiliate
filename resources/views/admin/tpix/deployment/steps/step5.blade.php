@extends('admin.tpix.deployment._wizard-layout')

@section('step-content')
<div x-data="{
    deploying: false,
    verifying: false,
    deploymentResult: null,
    verificationResult: null,
    estimatedGas: null,

    async estimateGas() {
        try {
            const response = await fetch('{{ route('admin.tpix.deployment.api.estimate-gas', $config->slug) }}');
            const data = await response.json();
            if (data.success) {
                this.estimatedGas = data.data;
            }
        } catch (error) {
            console.error('Error estimating gas:', error);
        }
    },

    async deployContract() {
        if (!confirm('ต้องการ Deploy Smart Contract หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
            return;
        }

        this.deploying = true;
        this.deploymentResult = null;

        try {
            const response = await fetch('{{ route('admin.tpix.deployment.step5.deploy', $config->slug) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();
            this.deploymentResult = data;

            if (data.success) {
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }
        } catch (error) {
            this.deploymentResult = {
                success: false,
                message: 'เกิดข้อผิดพลาด: ' + error.message
            };
        } finally {
            this.deploying = false;
        }
    },

    async verifyContract() {
        this.verifying = true;
        this.verificationResult = null;

        try {
            const response = await fetch('{{ route('admin.tpix.deployment.step5.verify', $config->slug) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();
            this.verificationResult = data;

            if (data.success) {
                setTimeout(() => {
                    window.location.href = '{{ route('admin.tpix.deployment.step6', $config->slug) }}';
                }, 2000);
            }
        } catch (error) {
            this.verificationResult = {
                success: false,
                message: 'เกิดข้อผิดพลาด: ' + error.message
            };
        } finally {
            this.verifying = false;
        }
    }
}" x-init="estimateGas()">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
            Step 5: Deploy & Verify
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            Deploy Smart Contract ไปยัง TPIX Blockchain และทำการ Verify
        </p>
    </div>

    {{-- Deployment Summary --}}
    <div class="p-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl mb-8">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            สรุปข้อมูล Token
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">ชื่อเหรียญ</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $config->token_name }}</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">สัญลักษณ์</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $config->token_symbol }}</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Supply</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($config->total_supply) }}</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Decimals</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $config->decimals }}</div>
            </div>
        </div>

        {{-- Contract Features --}}
        @if($config->contract_features)
            <div class="mt-6">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">Contract Features</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($config->contract_features as $feature)
                        <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-sm font-medium">
                            {{ ucfirst($feature) }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Access Control --}}
        @if($config->access_control)
            <div class="mt-4">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Access Control</div>
                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm font-medium">
                    {{ ucwords(str_replace('_', ' ', $config->access_control)) }}
                </span>
            </div>
        @endif
    </div>

    {{-- Gas Estimation --}}
    <div class="p-6 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-2xl mb-8">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
            </svg>
            ค่าใช้จ่ายโดยประมาณ
        </h3>

        <div x-show="estimatedGas" class="space-y-3">
            <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">Estimated Gas</span>
                <span class="font-semibold text-gray-900 dark:text-white" x-text="estimatedGas?.gas || '-'"></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                <span class="text-gray-600 dark:text-gray-400">Gas Price</span>
                <span class="font-semibold text-gray-900 dark:text-white" x-text="estimatedGas?.gasPrice || '-'"></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-yellow-400">
                <span class="text-gray-600 dark:text-gray-400 font-semibold">Total Cost (TPIX)</span>
                <span class="font-bold text-yellow-600 dark:text-yellow-400 text-lg" x-text="estimatedGas?.totalCost || '-'"></span>
            </div>
        </div>

        <div x-show="!estimatedGas" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-600"></div>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">กำลังคำนวณค่า Gas...</p>
        </div>
    </div>

    {{-- Deployer Wallet Info --}}
    <div class="p-6 bg-gradient-to-r from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-2xl mb-8">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
            </svg>
            Deployer Wallet
        </h3>

        @if($config->deployer_address)
            <div class="space-y-3">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Address</div>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-sm font-mono text-gray-900 dark:text-white break-all">{{ $config->deployer_address }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $config->deployer_address }}')"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                @if($config->deployer_private_key)
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Private Key</div>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 text-sm font-mono text-gray-900 dark:text-white">{{ str_repeat('•', 60) }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $config->deployer_private_key }}')"
                                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                    title="Copy Private Key">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    ⚠️ ยังไม่มี Deployer Wallet กรุณาตั้งค่าใน Prerequisites (Step 1)
                </p>
            </div>
        @endif
    </div>

    {{-- Deployment Status --}}
    @if($config->status === 'deployed' && $config->contract_address)
        <div class="p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl mb-8 border-2 border-green-500">
            <h3 class="text-xl font-semibold text-green-900 dark:text-green-100 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                ✅ Deployment สำเร็จ!
            </h3>

            <div class="space-y-4">
                <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Contract Address</div>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-sm font-mono text-gray-900 dark:text-white break-all">{{ $config->contract_address }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $config->contract_address }}')"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                @if($config->deployment_tx_hash)
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Transaction Hash</div>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 text-sm font-mono text-gray-900 dark:text-white break-all">{{ $config->deployment_tx_hash }}</code>
                            <a href="{{ config('tpix.explorer_url') }}/tx/{{ $config->deployment_tx_hash }}"
                               target="_blank"
                               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endif

                <div class="flex gap-3">
                    <a href="{{ config('tpix.explorer_url') }}/address/{{ $config->contract_address }}"
                       target="_blank"
                       class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-center font-semibold transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        ดูใน Block Explorer
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Deploy Button --}}
    @if($config->status !== 'deployed')
        <div class="p-6 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 rounded-2xl mb-8">
            <div class="flex items-start gap-4 mb-6">
                <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-lg font-semibold text-red-900 dark:text-red-100 mb-2">คำเตือนสำคัญ!</h4>
                    <ul class="space-y-2 text-sm text-red-800 dark:text-red-200">
                        <li>• การ Deploy Contract ไม่สามารถแก้ไขหรือย้อนกลับได้</li>
                        <li>• ตรวจสอบข้อมูลทั้งหมดให้ถูกต้องก่อน Deploy</li>
                        <li>• ต้องมี TPIX เพียงพอสำหรับค่า Gas Fee</li>
                        <li>• บันทึก Contract Address และ Private Key ไว้อย่างปลอดภัย</li>
                    </ul>
                </div>
            </div>

            <button @click="deployContract()"
                    :disabled="deploying || !estimatedGas"
                    class="w-full px-8 py-4 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-3">
                <svg x-show="!deploying" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <div x-show="deploying" class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                <span x-text="deploying ? 'กำลัง Deploy Contract...' : 'Deploy Smart Contract'"></span>
            </button>
        </div>
    @endif

    {{-- Deployment Result --}}
    <div x-show="deploymentResult" class="mb-8">
        <div x-show="deploymentResult?.success"
             class="p-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-1">Deployment สำเร็จ!</h4>
                    <p class="text-sm text-green-800 dark:text-green-200" x-text="deploymentResult?.message"></p>
                </div>
            </div>
        </div>

        <div x-show="deploymentResult && !deploymentResult?.success"
             class="p-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-red-900 dark:text-red-100 mb-1">Deployment ล้มเหลว</h4>
                    <p class="text-sm text-red-800 dark:text-red-200" x-text="deploymentResult?.message"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Verify Contract Section --}}
    @if($config->status === 'deployed' && !$config->is_verified)
        <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl mb-8">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Verify Smart Contract
            </h3>

            <p class="text-gray-600 dark:text-gray-400 mb-6">
                การ Verify Contract จะทำให้ Source Code ของ Smart Contract สามารถตรวจสอบได้บน Block Explorer และเพิ่มความน่าเชื่อถือให้กับโปรเจกต์
            </p>

            <button @click="verifyContract()"
                    :disabled="verifying"
                    class="w-full px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-3">
                <svg x-show="!verifying" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div x-show="verifying" class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                <span x-text="verifying ? 'กำลัง Verify Contract...' : 'Verify Contract บน Block Explorer'"></span>
            </button>
        </div>
    @endif

    {{-- Verification Result --}}
    <div x-show="verificationResult" class="mb-8">
        <div x-show="verificationResult?.success"
             class="p-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-1">Verification สำเร็จ!</h4>
                    <p class="text-sm text-green-800 dark:text-green-200" x-text="verificationResult?.message"></p>
                </div>
            </div>
        </div>

        <div x-show="verificationResult && !verificationResult?.success"
             class="p-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-r-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-red-900 dark:text-red-100 mb-1">Verification ล้มเหลว</h4>
                    <p class="text-sm text-red-800 dark:text-red-200" x-text="verificationResult?.message"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-4 pt-6">
        <a href="{{ route('admin.tpix.deployment.step4', $config->slug) }}"
           class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← ย้อนกลับ
        </a>

        @if($config->status === 'deployed' && $config->is_verified)
            <a href="{{ route('admin.tpix.deployment.step6', $config->slug) }}"
               class="flex-1 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
                ถัดไป: DEX Integration →
            </a>
        @else
            <button disabled
                    class="flex-1 px-8 py-3 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-semibold rounded-xl cursor-not-allowed">
                กรุณา Deploy และ Verify ก่อน
            </button>
        @endif
    </div>
</div>
@endsection
