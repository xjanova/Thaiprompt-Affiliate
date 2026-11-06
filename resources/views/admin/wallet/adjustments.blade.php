@extends('layouts.admin-v3')

@section('title', 'ประวัติปรับยอดโดยแอดมิน')

@section('content')
{{-- 📋 บันทึกตรวจสอบ (audit trail) ทุกรายการที่แอดมินแตะยอดเงินในกระเป๋า — อ่านอย่างเดียว --}}
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">ประวัติปรับยอดโดยแอดมิน</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm">ใครทำ · เมื่อไร · ยอดก่อน/หลัง · เหตุผล · IP — แก้ไขหรือลบไม่ได้</p>
        </div>
        <a href="{{ route('admin.wallet.all') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-center">
            💼 กระเป๋าผู้ใช้ทั้งหมด
        </a>
    </div>

    {{-- สรุปตามตัวกรอง --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">จำนวนรายการ</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format((int) ($summary->total_count ?? 0)) }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดเพิ่มเข้ากระเป๋า (เติม/คืน/รับโอน)</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">฿{{ number_format((float) ($summary->total_in ?? 0), 2) }}</p>
        </div>
        <div class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอดออกจากกระเป๋า (หัก/โอนออก)</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">฿{{ number_format((float) ($summary->total_out ?? 0), 2) }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">การย้อนรายการนับแยก ไม่อยู่ในสองยอดนี้</p>
        </div>
    </div>

    {{-- ตัวกรอง --}}
    <form method="GET" action="{{ route('admin.wallet.adjustments') }}" class="glass-fusion rounded-xl shadow-lg p-5 border border-white/20 dark:border-white/10 grid grid-cols-1 md:grid-cols-5 gap-4">
        @if(! empty($filters['wallet_id']))
            <input type="hidden" name="wallet_id" value="{{ (int) $filters['wallet_id'] }}">
        @endif
        <div class="md:col-span-2">
            <label for="f_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
            <input id="f_search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อ/อีเมลเจ้าของ, Wallet Address, เหตุผล, ชื่อแอดมิน"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="f_action" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
            <select id="f_action" name="action" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทั้งหมด</option>
                @foreach($actionLabels as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['action'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตั้งแต่วันที่</label>
            <input id="f_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label for="f_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ถึงวันที่</label>
            <input id="f_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div class="md:col-span-5 flex flex-col sm:flex-row gap-2 sm:justify-end">
            @if(array_filter($filters))
                <a href="{{ route('admin.wallet.adjustments') }}" class="px-5 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-center">ล้างตัวกรอง</a>
            @endif
            <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold min-h-[44px]">กรอง</button>
        </div>
    </form>

    {{-- ตาราง --}}
    <div class="glass-fusion rounded-xl shadow-lg border border-white/20 dark:border-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เวลา</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">กระเป๋า / เจ้าของ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ยอดก่อน → หลัง</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เหตุผล</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">แอดมิน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($adjustments as $adj)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition align-top">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                {{ $adj->created_at->format('d/m/Y') }}<br>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $adj->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.wallet.show', $adj->wallet_id) }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ $adj->user->name ?? 'ผู้ใช้ #'.$adj->user_id }}</a>
                                <p class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $adj->wallet->wallet_address ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-900 dark:text-white">{{ $adj->action_label }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right font-bold {{ $adj->is_credit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $adj->is_credit ? '+' : '−' }}฿{{ number_format((float) $adj->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-gray-600 dark:text-gray-300">
                                ฿{{ number_format((float) $adj->balance_before, 2) }} → ฿{{ number_format((float) $adj->balance_after, 2) }}
                                @if($adj->walletTransaction)
                                    <p class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $adj->walletTransaction->transaction_id }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs break-words">{{ $adj->reason }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                {{ $adj->admin_name ?? 'ไม่ทราบ' }}
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $adj->ip_address }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">ยังไม่มีรายการ{{ array_filter($filters) ? 'ที่ตรงกับตัวกรอง' : '' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $adjustments->links() }}</div>
</div>
@endsection
