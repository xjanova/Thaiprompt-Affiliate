@extends('layouts.admin-v3')

@section('title', 'ประวัติ SMS Notifications')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📨 ประวัติ SMS Notifications
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                ดูรายการ SMS ที่ได้รับจากอุปกรณ์ทั้งหมด
            </p>
        </div>
        <a href="{{ route('admin.smschecker.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    {{-- ตัวกรอง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('admin.smschecker.notifications') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Ref, ผู้ส่ง, Device ID..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ รอจับคู่</option>
                    <option value="matched" {{ request('status') === 'matched' ? 'selected' : '' }}>🔗 จับคู่แล้ว</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>✅ ยืนยัน</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>❌ ปฏิเสธ</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>⏰ หมดอายุ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ธนาคาร</label>
                <select name="bank"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($banks as $code => $name)
                        <option value="{{ $code }}" {{ request('bank') === $code ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                <select name="type"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>💰 เงินเข้า</option>
                    <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>💸 เงินออก</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.smschecker.notifications') }}"
                   class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    {{-- ตาราง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ธนาคาร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวนเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ส่ง/ผู้รับ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">อุปกรณ์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Transaction</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($notifications as $notification)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            #{{ $notification->id }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $notification->bank }}
                        </td>
                        <td class="px-6 py-4">
                            @if($notification->type === 'credit')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">💰 เงินเข้า</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300 rounded-full">💸 เงินออก</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right {{ $notification->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $notification->type === 'credit' ? '+' : '-' }}{{ number_format($notification->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ Str::limit($notification->sender_or_receiver, 20) ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-400">
                            {{ $notification->reference_number ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-400">
                            {{ Str::limit($notification->device_id, 15) }}
                        </td>
                        <td class="px-6 py-4">
                            @switch($notification->status)
                                @case('pending')
                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">⏳ รอจับคู่</span>
                                    @break
                                @case('matched')
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded-full">🔗 จับคู่แล้ว</span>
                                    @break
                                @case('confirmed')
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">✅ ยืนยัน</span>
                                    @break
                                @case('rejected')
                                    <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-full">❌ ปฏิเสธ</span>
                                    @break
                                @case('expired')
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-full">⏰ หมดอายุ</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            @if($notification->matched_transaction_id)
                                <span class="text-blue-600 dark:text-blue-400 font-mono">#{{ $notification->matched_transaction_id }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            📭 ยังไม่มี SMS notifications
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
