{{-- resources/views/admin/accounting/flowaccount/settings.blade.php --}}
{{-- หน้าตั้งค่า FlowAccount Integration --}}

@extends('layouts.admin')

@section('title', 'ตั้งค่า FlowAccount')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="flowAccountSettings()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    ตั้งค่า FlowAccount
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    จัดการการเชื่อมต่อและการซิงค์ข้อมูลกับ FlowAccount
                </p>
            </div>
            <a href="{{ route('admin.accounting.flowaccount.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Settings --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Connection Status Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        สถานะการเชื่อมต่อ
                    </h2>
                </div>
                <div class="p-6">
                    @if($connection && $connection->isConnected())
                        <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-3"></div>
                                <div>
                                    <p class="font-medium text-green-800 dark:text-green-200">เชื่อมต่อแล้ว</p>
                                    <p class="text-sm text-green-600 dark:text-green-400">
                                        Token หมดอายุ: {{ $connection->token_expires_at?->format('d/m/Y H:i') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                            <form action="{{ route('admin.accounting.flowaccount.disconnect') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        onclick="return confirm('ต้องการยกเลิกการเชื่อมต่อหรือไม่?')"
                                        class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                    ยกเลิกการเชื่อมต่อ
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                                <div>
                                    <p class="font-medium text-yellow-800 dark:text-yellow-200">ยังไม่ได้เชื่อมต่อ</p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400">กรุณาเชื่อมต่อกับ FlowAccount</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.accounting.flowaccount.connect.form') }}"
                               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                เชื่อมต่อ
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sync Settings --}}
            @if($connection && $connection->isConnected())
            <form action="{{ route('admin.accounting.flowaccount.settings.save') }}" method="POST">
                @csrf
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-6 h-6 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            การตั้งค่าการซิงค์
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Auto Sync Toggle --}}
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">ซิงค์อัตโนมัติ</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">ซิงค์ข้อมูลอัตโนมัติทุก 1 ชั่วโมง</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="auto_sync" value="1"
                                       {{ ($connection->auto_sync ?? false) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        {{-- Sync Types --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">เลือกข้อมูลที่จะซิงค์</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @php
                                    $syncSettings = $connection->sync_settings ?? [];
                                @endphp

                                {{-- Contacts --}}
                                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="sync_contacts" value="1"
                                           {{ ($syncSettings['sync_contacts'] ?? true) ? 'checked' : '' }}
                                           class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">ผู้ติดต่อ</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">ลูกค้าและผู้จำหน่าย</p>
                                    </div>
                                </label>

                                {{-- Products --}}
                                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="sync_products" value="1"
                                           {{ ($syncSettings['sync_products'] ?? true) ? 'checked' : '' }}
                                           class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">สินค้า/บริการ</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">รายการสินค้าและบริการ</p>
                                    </div>
                                </label>

                                {{-- Invoices --}}
                                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="sync_invoices" value="1"
                                           {{ ($syncSettings['sync_invoices'] ?? true) ? 'checked' : '' }}
                                           class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">ใบแจ้งหนี้</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">ใบแจ้งหนี้และใบกำกับภาษี</p>
                                    </div>
                                </label>

                                {{-- Expenses --}}
                                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="sync_expenses" value="1"
                                           {{ ($syncSettings['sync_expenses'] ?? true) ? 'checked' : '' }}
                                           class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">ค่าใช้จ่าย</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">รายจ่ายและใบซื้อ</p>
                                    </div>
                                </label>

                                {{-- Quotations --}}
                                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="sync_quotations" value="1"
                                           {{ ($syncSettings['sync_quotations'] ?? true) ? 'checked' : '' }}
                                           class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">ใบเสนอราคา</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Quotations</p>
                                    </div>
                                </label>

                                {{-- Receipts --}}
                                <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" name="sync_receipts" value="1"
                                           {{ ($syncSettings['sync_receipts'] ?? true) ? 'checked' : '' }}
                                           class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">ใบเสร็จรับเงิน</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Receipts</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Save Button --}}
                        <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="submit"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                บันทึกการตั้งค่า
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- API Configuration Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        การตั้งค่า API
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Environment</span>
                        <span class="px-3 py-1 text-sm rounded-full {{ ($config['use_sandbox'] ?? true) ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' }}">
                            {{ ($config['use_sandbox'] ?? true) ? 'Sandbox' : 'Production' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">API Version</span>
                        <span class="text-gray-900 dark:text-white font-medium">v3-alpha</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Culture</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $config['culture'] ?? 'th-TH' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Timeout</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $config['timeout'] ?? 30 }} วินาที</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            @if($connection && $connection->isConnected())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        การดำเนินการด่วน
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <form action="{{ route('admin.accounting.flowaccount.sync') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            ซิงค์ข้อมูลทั้งหมด
                        </button>
                    </form>

                    <button type="button"
                            @click="testConnection()"
                            :disabled="testing"
                            class="w-full px-4 py-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition flex items-center justify-center disabled:opacity-50">
                        <svg x-show="!testing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg x-show="testing" class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="testing ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
                    </button>

                    <a href="{{ route('admin.accounting.flowaccount.logs') }}"
                       class="w-full px-4 py-3 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        ดูประวัติการซิงค์
                    </a>
                </div>
            </div>
            @endif

            {{-- Help Card --}}
            <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg overflow-hidden text-white">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-2">ต้องการความช่วยเหลือ?</h3>
                    <p class="text-blue-100 text-sm mb-4">
                        ดูเอกสาร API และวิธีการใช้งาน FlowAccount
                    </p>
                    <a href="https://developers.flowaccount.com/api-reference"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        FlowAccount API Docs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function flowAccountSettings() {
    return {
        testing: false,

        async testConnection() {
            this.testing = true;
            try {
                const response = await fetch('{{ route("admin.accounting.flowaccount.test") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('การเชื่อมต่อสำเร็จ!');
                } else {
                    alert('การเชื่อมต่อล้มเหลว: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.testing = false;
            }
        }
    }
}
</script>
@endpush
@endsection
