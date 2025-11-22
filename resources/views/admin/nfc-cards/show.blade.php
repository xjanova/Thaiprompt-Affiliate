@extends('layouts.admin-v3')

@section('title', 'รายละเอียดบัตร NFC')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header with Actions --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.nfc-cards.index') }}"
               class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <i class="fas fa-credit-card text-blue-600 dark:text-blue-400"></i>
                    รายละเอียดบัตร NFC
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 font-mono">
                    {{ $nfcCard->card_number }}
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.nfc-cards.edit', $nfcCard) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                <i class="fas fa-edit"></i>
                แก้ไข
            </a>
            <a href="{{ route('admin.nfc-cards.topup-form', $nfcCard) }}"
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                <i class="fas fa-money-bill-wave"></i>
                เติมเงิน
            </a>
            @if(!$nfcCard->is_paired)
                <a href="{{ route('admin.nfc-cards.pair-form', $nfcCard) }}"
                   class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <i class="fas fa-link"></i>
                    จับคู่กับผู้ใช้
                </a>
            @endif
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition
             x-init="setTimeout(() => show = false, 5000)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-700 dark:text-green-400 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Main Card Info Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Card Details --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-600"></i>
                ข้อมูลบัตร
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Card Number --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">เลขบัตร</label>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-mono font-bold text-gray-900 dark:text-white">
                            {{ $nfcCard->card_number }}
                        </span>
                        <button onclick="navigator.clipboard.writeText('{{ $nfcCard->card_number }}')"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                title="คัดลอก">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                {{-- Card Name --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">ชื่อบัตร</label>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $nfcCard->card_name ?? '-' }}
                    </div>
                </div>

                {{-- Card Type --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">ประเภทบัตร</label>
                    <div>
                        @if($nfcCard->card_type === 'standard')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <i class="fas fa-star mr-2"></i>
                                มาตรฐาน
                            </span>
                        @elseif($nfcCard->card_type === 'premium')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                <i class="fas fa-gem mr-2"></i>
                                พรีเมียม
                            </span>
                        @elseif($nfcCard->card_type === 'vip')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                <i class="fas fa-crown mr-2"></i>
                                วีไอพี
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">สถานะ</label>
                    <div>
                        @if($nfcCard->status === 'active')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                <i class="fas fa-check-circle mr-2"></i>
                                ใช้งานอยู่
                            </span>
                        @elseif($nfcCard->status === 'inactive')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <i class="fas fa-times-circle mr-2"></i>
                                ไม่ใช้งาน
                            </span>
                        @elseif($nfcCard->status === 'blocked')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                <i class="fas fa-ban mr-2"></i>
                                บล็อก
                            </span>
                        @elseif($nfcCard->status === 'suspended')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                <i class="fas fa-pause-circle mr-2"></i>
                                พักการใช้งาน
                            </span>
                        @elseif($nfcCard->status === 'expired')
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                <i class="fas fa-clock mr-2"></i>
                                หมดอายุ
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Issued Date --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">วันที่ออกบัตร</label>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $nfcCard->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $nfcCard->created_at->diffForHumans() }}
                    </div>
                </div>

                {{-- Expires At --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">วันหมดอายุ</label>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if($nfcCard->expires_at)
                            {{ $nfcCard->expires_at->format('d/m/Y') }}
                            @if($nfcCard->expires_at->isPast())
                                <span class="text-xs text-red-600 dark:text-red-400">
                                    <i class="fas fa-exclamation-circle"></i>
                                    หมดอายุแล้ว
                                </span>
                            @endif
                        @else
                            <span class="text-gray-500 dark:text-gray-400">ไม่กำหนด</span>
                        @endif
                    </div>
                </div>

                {{-- Last Used --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">ใช้งานล่าสุด</label>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if($nfcCard->last_used_at)
                            {{ $nfcCard->last_used_at->format('d/m/Y H:i') }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $nfcCard->last_used_at->diffForHumans() }}
                            </div>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">ยังไม่เคยใช้งาน</span>
                        @endif
                    </div>
                </div>

                {{-- Paired Status --}}
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">สถานะการจับคู่</label>
                    <div>
                        @if($nfcCard->is_paired)
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                <i class="fas fa-link mr-2"></i>
                                จับคู่แล้ว
                            </span>
                        @else
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <i class="fas fa-unlink mr-2"></i>
                                ยังไม่ได้จับคู่
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($nfcCard->notes)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400 block mb-2">
                        <i class="fas fa-sticky-note mr-1"></i>
                        หมายเหตุ
                    </label>
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 text-gray-700 dark:text-gray-300">
                        {{ $nfcCard->notes }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Balance & User Info --}}
        <div class="space-y-6">
            {{-- Balance Card --}}
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-green-100">ยอดเงินคงเหลือ</h3>
                    <i class="fas fa-wallet text-2xl text-white/30"></i>
                </div>
                <div class="text-4xl font-bold mb-2">
                    ฿{{ number_format($nfcCard->balance, 2) }}
                </div>
                @if($nfcCard->credit_limit > 0)
                    <div class="text-sm text-green-100">
                        วงเงินเครดิต: ฿{{ number_format($nfcCard->credit_limit, 2) }}
                    </div>
                @endif
            </div>

            {{-- User Info --}}
            @if($nfcCard->user)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-user text-purple-600"></i>
                        ผู้ถือบัตร
                    </h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($nfcCard->user->name, 0, 2)) }}
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ $nfcCard->user->name }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $nfcCard->user->email }}
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('admin.users.show', $nfcCard->user) }}"
                       class="block w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors text-center">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        ดูโปรไฟล์ผู้ใช้
                    </a>

                    @if($nfcCard->paired_at)
                        <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                            จับคู่เมื่อ: {{ $nfcCard->paired_at->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 text-center">
                    <i class="fas fa-user-slash text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">บัตรนี้ยังไม่ได้จับคู่กับผู้ใช้</p>
                    <a href="{{ route('admin.nfc-cards.pair-form', $nfcCard) }}"
                       class="inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-link mr-2"></i>
                        จับคู่ตอนนี้
                    </a>
                </div>
            @endif

            {{-- Issuer Info --}}
            @if($nfcCard->issuer)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-user-tie text-blue-600"></i>
                        ผู้ออกบัตร
                    </h3>
                    <div class="text-sm space-y-1">
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ $nfcCard->issuer->name }}
                        </div>
                        <div class="text-gray-600 dark:text-gray-400">
                            {{ $nfcCard->issuer->email }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Statistics Section (ถ้ามี) --}}
    @if(isset($statistics) && !empty($statistics))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="fas fa-chart-bar text-green-600"></i>
                สถิติการใช้งาน
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($statistics as $key => $value)
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ is_numeric($value) ? number_format($value) : $value }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent Transactions --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-history text-orange-600"></i>
                ธุรกรรมล่าสุด
            </h2>
        </div>

        @if($recentTransactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ประเภท</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">จำนวน</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">สถานะ</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentTransactions as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $transaction->type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                                    <span class="{{ $transaction->amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $transaction->amount >= 0 ? '+' : '' }}฿{{ number_format($transaction->amount, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            สำเร็จ
                                        </span>
                                    @elseif($transaction->status === 'pending')
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                            รอดำเนินการ
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                            ล้มเหลว
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm"
                                            title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-receipt text-6xl mb-4 text-gray-300 dark:text-gray-600"></i>
                <p class="text-lg">ยังไม่มีธุรกรรม</p>
                <p class="text-sm">บัตรนี้ยังไม่เคยมีการทำธุรกรรมใดๆ</p>
            </div>
        @endif
    </div>
</div>
@endsection
