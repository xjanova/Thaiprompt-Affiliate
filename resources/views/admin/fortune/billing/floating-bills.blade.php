@extends('layouts.admin')

@section('title', 'จัดการบิลลอย')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📋 จัดการบิลลอย
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                บิลที่รับเงินแล้วแต่ยังไม่สามารถระบุตัวตนผู้ใช้ได้
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('admin.fortune.billing.index') }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                ← กลับหน้าจัดการบิล
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-orange-100 text-sm mb-1">บิลลอยทั้งหมด</div>
            <div class="text-2xl font-bold">{{ $stats['total_count'] }} รายการ</div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-red-100 text-sm mb-1">ยอดเงินรวม</div>
            <div class="text-2xl font-bold">฿{{ number_format($stats['total_amount'], 2) }}</div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-yellow-100 text-sm mb-1">วันนี้</div>
            <div class="text-2xl font-bold">{{ $stats['today_count'] }} รายการ</div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg p-5 text-white">
            <div class="text-amber-100 text-sm mb-1">ยอดวันนี้</div>
            <div class="text-2xl font-bold">฿{{ number_format($stats['today_amount'], 2) }}</div>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4 mb-6">
        <div class="flex items-start">
            <span class="text-2xl mr-3">💡</span>
            <div>
                <h4 class="font-semibold text-orange-800 dark:text-orange-200 mb-1">บิลลอยคืออะไร?</h4>
                <p class="text-orange-700 dark:text-orange-300 text-sm">
                    บิลลอยเกิดจากการที่ระบบได้รับแจ้งเตือนการโอนเงิน (SMS) แต่ไม่สามารถจับคู่กับผู้ใช้ที่รอชำระเงินได้
                    อาจเกิดจาก: ผู้ใช้โอนเงินผิดจำนวน, โอนก่อนสร้างคำสั่งซื้อ, หรือ SMS มาช้ากว่าที่ผู้ใช้รอ
                </p>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาจากชื่อผู้โอน, ธนาคาร, หรือชื่อ Facebook..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                🔍 ค้นหา
            </button>
            @if(request('search'))
                <a href="{{ route('admin.fortune.billing.floating-bills') }}"
                   class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    ล้าง
                </a>
            @endif
        </form>
    </div>

    {{-- Floating Bills Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่ชำระ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ข้อมูลผู้โอน
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จำนวนเงิน
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ธนาคาร
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ข้อมูล Facebook
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Assign ให้ผู้ใช้
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($floatingBills as $bill)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div>{{ $bill->paid_at?->format('d/m/Y') ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $bill->paid_at?->format('H:i:s') ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <div class="font-medium">{{ $bill->sender_info ?? 'ไม่ระบุ' }}</div>
                                @if($bill->smsNotification)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        SMS ID: {{ $bill->sms_notification_id }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="font-bold text-green-600 dark:text-green-400">
                                    ฿{{ number_format($bill->amount_paid, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $bill->sender_bank ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                @if($bill->facebook_user_name)
                                    <div class="font-medium">{{ $bill->facebook_user_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $bill->facebook_user_id }}</div>
                                @else
                                    <span class="text-gray-400">ไม่มีข้อมูล</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <form action="{{ route('admin.fortune.billing.assign', $bill) }}" method="POST" class="flex items-center gap-2 justify-end">
                                    @csrf
                                    <select name="user_id" required
                                            class="text-sm px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="">เลือกผู้ใช้...</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition">
                                        Assign
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-5xl mb-4">🎉</div>
                                <div class="text-gray-500 dark:text-gray-400">
                                    ไม่มีบิลลอย - ระบบจับคู่การชำระเงินได้ครบทุกรายการ
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($floatingBills->hasPages())
        <div class="mt-6">
            {{ $floatingBills->appends(request()->query())->links() }}
        </div>
    @endif

    {{-- Tips Section --}}
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-3">💡 เคล็ดลับการจัดการบิลลอย</h4>
        <ul class="list-disc list-inside text-blue-700 dark:text-blue-300 text-sm space-y-2">
            <li>ตรวจสอบชื่อผู้โอนเทียบกับชื่อ Facebook ของผู้ใช้ที่รอชำระเงิน</li>
            <li>เช็คยอดเงินที่โอนเทียบกับราคาที่ตั้งไว้ (เช่น 49.xx บาท)</li>
            <li>ติดต่อผู้ใช้ผ่าน Facebook Messenger เพื่อยืนยันการโอนเงิน</li>
            <li>หากไม่พบเจ้าของ อาจเป็นการโอนผิด ควรเก็บข้อมูลไว้อย่างน้อย 30 วัน</li>
        </ul>
    </div>
</div>
@endsection
