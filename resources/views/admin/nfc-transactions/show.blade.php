{{--
    NFC Transaction Detail - Admin Panel

    หน้าแสดงรายละเอียดธุรกรรม NFC
    - ข้อมูลธุรกรรมครบถ้วน
    - ข้อมูลบัตร NFC และผู้ใช้
    - ข้อมูลเครื่องอ่านและสถานที่
    - รายละเอียดการชำระเงิน
    - ข้อมูลทางเทคนิค (IP, Device, Metadata)

    @version 3.0.0
    @since 2025-11-23
--}}

@extends('layouts.admin')

@section('title', 'รายละเอียดธุรกรรม NFC')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                    📜 รายละเอียดธุรกรรม NFC
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    ข้อมูลธุรกรรม #{{ $nfcTransaction->id }}
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.nfc-transactions.index') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับรายการ
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Transaction Overview --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-receipt"></i>
                        ข้อมูลธุรกรรม
                    </h2>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Transaction ID --}}
                    <div class="flex justify-between items-start border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Transaction ID</p>
                            <p class="text-lg font-mono font-bold text-gray-900 dark:text-white">{{ $nfcTransaction->transaction_id }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            @php
                                $statusConfig = [
                                    'pending' => ['color' => 'yellow', 'icon' => 'fa-clock', 'label' => 'รอดำเนินการ'],
                                    'processing' => ['color' => 'blue', 'icon' => 'fa-spinner', 'label' => 'กำลังดำเนินการ'],
                                    'completed' => ['color' => 'green', 'icon' => 'fa-check-circle', 'label' => 'สำเร็จ'],
                                    'failed' => ['color' => 'red', 'icon' => 'fa-times-circle', 'label' => 'ล้มเหลว'],
                                    'cancelled' => ['color' => 'gray', 'icon' => 'fa-ban', 'label' => 'ยกเลิก'],
                                    'refunded' => ['color' => 'purple', 'icon' => 'fa-undo-alt', 'label' => 'คืนเงินแล้ว'],
                                ];
                                $statusConf = $statusConfig[$nfcTransaction->status] ?? ['color' => 'gray', 'icon' => 'fa-question', 'label' => $nfcTransaction->status];
                            @endphp
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold bg-{{ $statusConf['color'] }}-100 dark:bg-{{ $statusConf['color'] }}-900/30 text-{{ $statusConf['color'] }}-700 dark:text-{{ $statusConf['color'] }}-400">
                                <i class="fas {{ $statusConf['icon'] }}"></i>
                                {{ $statusConf['label'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Receipt Number --}}
                    @if($nfcTransaction->receipt_number)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">เลขที่ใบเสร็จ</p>
                            <p class="text-lg font-mono font-semibold text-gray-900 dark:text-white">{{ $nfcTransaction->receipt_number }}</p>
                        </div>
                    @endif

                    {{-- Type & Amount --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ประเภทธุรกรรม</p>
                            @php
                                $typeConfig = [
                                    'payment' => ['icon' => 'fa-shopping-cart', 'color' => 'blue', 'label' => 'ชำระเงิน'],
                                    'topup' => ['icon' => 'fa-plus-circle', 'color' => 'green', 'label' => 'เติมเงิน'],
                                    'refund' => ['icon' => 'fa-undo', 'color' => 'yellow', 'label' => 'คืนเงิน'],
                                    'transfer' => ['icon' => 'fa-exchange-alt', 'color' => 'purple', 'label' => 'โอนเงิน'],
                                    'verification' => ['icon' => 'fa-check-circle', 'color' => 'teal', 'label' => 'ยืนยันบัตร'],
                                ];
                                $typeConf = $typeConfig[$nfcTransaction->type] ?? ['icon' => 'fa-question', 'color' => 'gray', 'label' => $nfcTransaction->type];
                            @endphp
                            <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-bold bg-{{ $typeConf['color'] }}-100 dark:bg-{{ $typeConf['color'] }}-900/30 text-{{ $typeConf['color'] }}-700 dark:text-{{ $typeConf['color'] }}-400">
                                <i class="fas {{ $typeConf['icon'] }}"></i>
                                {{ $typeConf['label'] }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">จำนวนเงิน</p>
                            <p class="text-2xl font-bold {{ in_array($nfcTransaction->type, ['payment']) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ in_array($nfcTransaction->type, ['payment']) ? '-' : '+' }}฿{{ number_format($nfcTransaction->amount, 2) }}
                            </p>
                        </div>
                    </div>

                    {{-- Balance --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ยอดคงเหลือก่อนทำรายการ</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">฿{{ number_format($nfcTransaction->balance_before, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ยอดคงเหลือหลังทำรายการ</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">฿{{ number_format($nfcTransaction->balance_after, 2) }}</p>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($nfcTransaction->description)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">รายละเอียด</p>
                            <p class="text-gray-900 dark:text-white">{{ $nfcTransaction->description }}</p>
                        </div>
                    @endif

                    {{-- Status Message --}}
                    @if($nfcTransaction->status_message)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ข้อความสถานะ</p>
                            <p class="text-gray-900 dark:text-white">{{ $nfcTransaction->status_message }}</p>
                        </div>
                    @endif

                    {{-- Error Message --}}
                    @if($nfcTransaction->error_message)
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <p class="text-sm text-red-600 dark:text-red-400 font-medium mb-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                ข้อความข้อผิดพลาด
                            </p>
                            <p class="text-red-700 dark:text-red-300">{{ $nfcTransaction->error_message }}</p>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if($nfcTransaction->notes)
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <p class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1">
                                <i class="fas fa-sticky-note mr-1"></i>
                                หมายเหตุ
                            </p>
                            <p class="text-blue-700 dark:text-blue-300">{{ $nfcTransaction->notes }}</p>
                        </div>
                    @endif

                    {{-- Timestamps --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">วันที่สร้าง</p>
                            <p class="text-gray-900 dark:text-white">
                                <i class="fas fa-calendar mr-1"></i>
                                {{ $nfcTransaction->created_at->format('d/m/Y H:i:s') }}
                            </p>
                        </div>
                        @if($nfcTransaction->completed_at)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">วันที่สำเร็จ</p>
                                <p class="text-green-600 dark:text-green-400">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    {{ $nfcTransaction->completed_at->format('d/m/Y H:i:s') }}
                                </p>
                            </div>
                        @endif
                        @if($nfcTransaction->failed_at)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">วันที่ล้มเหลว</p>
                                <p class="text-red-600 dark:text-red-400">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    {{ $nfcTransaction->failed_at->format('d/m/Y H:i:s') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Location & Device Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-700 dark:to-cyan-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-map-marker-alt"></i>
                        ข้อมูลสถานที่และอุปกรณ์
                    </h2>
                </div>

                <div class="p-6 space-y-4">
                    @if($nfcTransaction->location)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">สถานที่</p>
                            <p class="text-gray-900 dark:text-white">
                                <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                {{ $nfcTransaction->location }}
                            </p>
                        </div>
                    @endif

                    @if($nfcTransaction->ip_address)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">IP Address</p>
                            <p class="text-gray-900 dark:text-white font-mono">{{ $nfcTransaction->ip_address }}</p>
                        </div>
                    @endif

                    @if($nfcTransaction->user_agent)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">User Agent</p>
                            <p class="text-gray-900 dark:text-white text-sm break-all">{{ $nfcTransaction->user_agent }}</p>
                        </div>
                    @endif

                    @if($nfcTransaction->device_info)
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">ข้อมูลอุปกรณ์</p>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                <pre class="text-xs text-gray-800 dark:text-gray-300 overflow-x-auto">{{ json_encode($nfcTransaction->device_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Security & Verification --}}
            @if($nfcTransaction->encryption_verified || $nfcTransaction->verification_signature)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-700 dark:to-emerald-700 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-shield-alt"></i>
                            ความปลอดภัยและการตรวจสอบ
                        </h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">การเข้ารหัสถูกตรวจสอบ</p>
                                <p class="text-lg font-semibold {{ $nfcTransaction->encryption_verified ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                    <i class="fas fa-{{ $nfcTransaction->encryption_verified ? 'check-circle' : 'times-circle' }} mr-2"></i>
                                    {{ $nfcTransaction->encryption_verified ? 'ตรวจสอบแล้ว' : 'ยังไม่ได้ตรวจสอบ' }}
                                </p>
                            </div>
                            @if($nfcTransaction->verification_attempts)
                                <div class="text-right">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">จำนวนครั้งที่พยายาม</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $nfcTransaction->verification_attempts }}</p>
                                </div>
                            @endif
                        </div>

                        @if($nfcTransaction->verification_signature)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">ลายเซ็นการตรวจสอบ</p>
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    <p class="text-xs font-mono text-gray-800 dark:text-gray-300 break-all">{{ $nfcTransaction->verification_signature }}</p>
                                </div>
                            </div>
                        @endif

                        @if($nfcTransaction->card_data_hash)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Hash ข้อมูลบัตร</p>
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                                    <p class="text-xs font-mono text-gray-800 dark:text-gray-300 break-all">{{ $nfcTransaction->card_data_hash }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Metadata --}}
            @if($nfcTransaction->metadata)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-600 to-gray-700 dark:from-gray-700 dark:to-gray-800 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-database"></i>
                            ข้อมูลเพิ่มเติม (Metadata)
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                            <pre class="text-xs text-gray-800 dark:text-gray-300 overflow-x-auto">{{ json_encode($nfcTransaction->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- NFC Card Info --}}
            @if($nfcTransaction->nfcCard)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fas fa-credit-card"></i>
                            บัตร NFC
                        </h3>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">หมายเลขบัตร</p>
                            <p class="text-lg font-mono font-bold text-gray-900 dark:text-white">{{ $nfcTransaction->nfcCard->card_number }}</p>
                        </div>

                        @if($nfcTransaction->nfcCard->card_name)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ชื่อบัตร</p>
                                <p class="text-gray-900 dark:text-white">{{ $nfcTransaction->nfcCard->card_name }}</p>
                            </div>
                        @endif

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.nfc-cards.show', $nfcTransaction->nfcCard) }}"
                               class="block text-center px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white rounded-lg transition">
                                <i class="fas fa-eye mr-2"></i>
                                ดูข้อมูลบัตร
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- User Info --}}
            @if($nfcTransaction->user)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-700 dark:to-cyan-700 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fas fa-user"></i>
                            ผู้ใช้
                        </h3>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ชื่อ</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $nfcTransaction->user->name }}</p>
                        </div>

                        @if($nfcTransaction->user->email)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">อีเมล</p>
                                <p class="text-gray-900 dark:text-white">{{ $nfcTransaction->user->email }}</p>
                            </div>
                        @endif

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.users.show', $nfcTransaction->user) }}"
                               class="block text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition">
                                <i class="fas fa-user mr-2"></i>
                                ดูข้อมูลผู้ใช้
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- NFC Reader Info --}}
            @if($nfcTransaction->nfcReader)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-700 dark:to-emerald-700 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fas fa-tablet-alt"></i>
                            เครื่องอ่าน NFC
                        </h3>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ชื่อเครื่องอ่าน</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $nfcTransaction->nfcReader->name }}</p>
                        </div>

                        @if($nfcTransaction->nfcReader->location)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">สถานที่ติดตั้ง</p>
                                <p class="text-gray-900 dark:text-white">{{ $nfcTransaction->nfcReader->location }}</p>
                            </div>
                        @endif

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.nfc-readers.show', $nfcTransaction->nfcReader) }}"
                               class="block text-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition">
                                <i class="fas fa-tablet-alt mr-2"></i>
                                ดูข้อมูลเครื่องอ่าน
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Related Transactions --}}
            @if($nfcTransaction->paymentTransaction || $nfcTransaction->walletTransaction)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-red-600 dark:from-orange-700 dark:to-red-700 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fas fa-link"></i>
                            ธุรกรรมที่เกี่ยวข้อง
                        </h3>
                    </div>

                    <div class="p-6 space-y-4">
                        @if($nfcTransaction->paymentTransaction)
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Payment Transaction</p>
                                <p class="text-sm font-mono text-gray-900 dark:text-white">#{{ $nfcTransaction->payment_transaction_id }}</p>
                            </div>
                        @endif

                        @if($nfcTransaction->walletTransaction)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Wallet Transaction</p>
                                <p class="text-sm font-mono text-gray-900 dark:text-white">#{{ $nfcTransaction->wallet_transaction_id }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
