@extends('admin.tpix.deployment._wizard-layout')

@section('step-content')
<div x-data="{
    checking: false,
    results: null,
    checkPrerequisites() {
        this.checking = true;
        fetch('{{ route('admin.tpix.deployment.api.check-prerequisites') }}')
            .then(res => res.json())
            .then(data => {
                this.results = data.data;
                this.checking = false;
            })
            .catch(err => {
                console.error(err);
                this.checking = false;
                alert('เกิดข้อผิดพลาดในการตรวจสอบ');
            });
    }
}" x-init="checkPrerequisites()">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
            Step 1: ตรวจสอบความพร้อม
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            ระบบจะตรวจสอบความพร้อมของ RPC Node, Wallet, Services และ Environment อัตโนมัติ
        </p>
    </div>

    {{-- Loading State --}}
    <div x-show="checking" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-600 mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400 font-medium">กำลังตรวจสอบระบบ...</p>
    </div>

    {{-- Results --}}
    <div x-show="!checking && results" class="space-y-6">
        {{-- RPC Node --}}
        <div class="p-6 rounded-2xl border-2 transition-colors duration-300"
             :class="results?.rpc_node?.passed ? 'bg-green-50 dark:bg-green-900/20 border-green-500' : 'bg-red-50 dark:bg-red-900/20 border-red-500'">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg x-show="results?.rpc_node?.passed" class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <svg x-show="!results?.rpc_node?.passed" class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-2" :class="results?.rpc_node?.passed ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100'">
                        🌐 RPC Node Connection
                    </h3>
                    <p class="text-sm mb-2" :class="results?.rpc_node?.passed ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'"
                       x-text="results?.rpc_node?.message"></p>
                    <template x-if="results?.rpc_node?.details">
                        <div class="text-xs space-y-1" :class="results?.rpc_node?.passed ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
                            <div>Block Number: <strong x-text="results.rpc_node.details.block_number"></strong></div>
                            <div>Chain ID: <strong x-text="results.rpc_node.details.chain_id"></strong></div>
                            <div>RPC URL: <strong x-text="results.rpc_node.details.rpc_url"></strong></div>
                        </div>
                    </template>
                    <template x-if="!results?.rpc_node?.passed && results?.rpc_node?.solution">
                        <div class="mt-2 p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg text-xs text-yellow-900 dark:text-yellow-200">
                            💡 <strong>แนะนำ:</strong> <span x-text="results.rpc_node.solution"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Wallet --}}
        <div class="p-6 rounded-2xl border-2 transition-colors duration-300"
             :class="results?.wallet?.passed ? 'bg-green-50 dark:bg-green-900/20 border-green-500' : 'bg-red-50 dark:bg-red-900/20 border-red-500'">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg x-show="results?.wallet?.passed" class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <svg x-show="!results?.wallet?.passed" class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-2" :class="results?.wallet?.passed ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100'">
                        👛 Deployer Wallet
                    </h3>
                    <p class="text-sm mb-2" :class="results?.wallet?.passed ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'"
                       x-text="results?.wallet?.message"></p>
                    <template x-if="results?.wallet?.details">
                        <div class="text-xs space-y-1" :class="results?.wallet?.passed ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
                            <div>Address: <strong class="font-mono" x-text="results.wallet.details.address"></strong></div>
                            <div>Balance: <strong x-text="results.wallet.details.balance"></strong></div>
                        </div>
                    </template>
                    <template x-if="!results?.wallet?.passed && results?.wallet?.solution">
                        <div class="mt-2 p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg text-xs text-yellow-900 dark:text-yellow-200">
                            💡 <strong>แนะนำ:</strong> <span x-text="results.wallet.solution"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Services --}}
        <div class="p-6 rounded-2xl border-2 transition-colors duration-300"
             :class="results?.services?.passed ? 'bg-green-50 dark:bg-green-900/20 border-green-500' : 'bg-red-50 dark:bg-red-900/20 border-red-500'">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg x-show="results?.services?.passed" class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <svg x-show="!results?.services?.passed" class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-2" :class="results?.services?.passed ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100'">
                        ⚙️ Backend Services
                    </h3>
                    <p class="text-sm mb-3" :class="results?.services?.passed ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'"
                       x-text="results?.services?.message"></p>
                    <template x-if="results?.services?.services">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <template x-for="(service, name) in results.services.services" :key="name">
                                <div class="p-2 rounded-lg text-xs"
                                     :class="service.running ? 'bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-200' : 'bg-red-100 dark:bg-red-900/30 text-red-900 dark:text-red-200'">
                                    <div class="font-semibold capitalize" x-text="name"></div>
                                    <div x-text="service.message"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Environment --}}
        <div class="p-6 rounded-2xl border-2 transition-colors duration-300"
             :class="results?.environment?.passed ? 'bg-green-50 dark:bg-green-900/20 border-green-500' : 'bg-red-50 dark:bg-red-900/20 border-red-500'">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg x-show="results?.environment?.passed" class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <svg x-show="!results?.environment?.passed" class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-2" :class="results?.environment?.passed ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100'">
                        🖥️ Environment
                    </h3>
                    <p class="text-sm mb-3" :class="results?.environment?.passed ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'"
                       x-text="results?.environment?.message"></p>
                    <template x-if="results?.environment?.checks">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                            <template x-for="(check, name) in results.environment.checks" :key="name">
                                <div class="p-2 rounded-lg"
                                     :class="check.passed ? 'bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-200' : 'bg-red-100 dark:bg-red-900/30 text-red-900 dark:text-red-200'">
                                    <div class="font-semibold capitalize" x-text="name.replace('_', ' ')"></div>
                                    <div x-text="check.value || check.required || 'OK'"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <form method="POST" action="{{ route('admin.tpix.deployment.step1.save', $config->slug) }}" class="flex gap-4 pt-6">
            @csrf

            <button type="button" @click="checkPrerequisites()"
                    class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                ตรวจสอบอีกครั้ง
            </button>

            <button type="submit"
                    :disabled="!results?.all_passed"
                    class="flex-1 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg transition-all duration-300 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="results?.all_passed ? 'hover:shadow-xl hover:scale-105' : ''">
                ถัดไป: Token Configuration →
            </button>
        </form>

        {{-- Warning if not all passed --}}
        <div x-show="!results?.all_passed" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-r-xl">
            <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                ⚠️ <strong>กรุณาแก้ไขปัญหาก่อนดำเนินการต่อ</strong> - Prerequisites ต้องผ่านทั้งหมดก่อนจึงจะสามารถไปขั้นตอนถัดไปได้
            </p>
        </div>
    </div>

</div>
@endsection
