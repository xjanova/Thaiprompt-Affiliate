@extends('layouts.admin')

@section('title', 'รายละเอียดการสมัครสมาชิก')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    {{-- Gradient Header --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-amber-600 to-yellow-700 dark:from-orange-900 dark:via-amber-900 dark:to-yellow-950 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">รายละเอียดการสมัครสมาชิก</h1>
                    <p class="text-orange-100 text-lg">ข้อมูลการสมัครสมาชิก #{{ $subscription->id ?? 'N/A' }}</p>
                </div>
            </div>

            <a href="{{ route('admin.bot-automation.marketplace.subscriptions.index') }}"
               class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 text-orange-600 dark:text-orange-400 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปรายการ
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Subscription Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ข้อมูลการสมัคร
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">รหัสการสมัคร</label>
                            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">#{{ $subscription->id ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">สถานะ</label>
                            <div class="mt-1">
                                @if(isset($subscription->status))
                                    @if($subscription->status == 'active')
                                        <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">ใช้งาน</span>
                                    @elseif($subscription->status == 'expired')
                                        <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">หมดอายุ</span>
                                    @elseif($subscription->status == 'cancelled')
                                        <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">ยกเลิก</span>
                                    @else
                                        <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">{{ ucfirst($subscription->status) }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">ผู้ใช้</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $subscription->user_name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subscription->user_email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">รายการ</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $subscription->listing_title ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subscription->listing_category ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">วันเริ่มต้น</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ isset($subscription->start_date) ? date('d F Y', strtotime($subscription->start_date)) : 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">วันหมดอายุ</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ isset($subscription->end_date) ? date('d F Y', strtotime($subscription->end_date)) : 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">จำนวนเงิน</label>
                            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($subscription->amount ?? 0, 2) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">รอบการเรียกเก็บเงิน</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst($subscription->billing_cycle ?? 'monthly') }}</p>
                        </div>
                    </div>

                    @if(isset($subscription->notes) && $subscription->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">หมายเหตุ</label>
                        <p class="mt-2 text-gray-700 dark:text-gray-300">{{ $subscription->notes }}</p>
                    </div>
                    @endif

                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <p>สร้างเมื่อ: {{ isset($subscription->created_at) ? $subscription->created_at->format('d M Y H:i') : 'N/A' }}</p>
                                <p>อัพเดทล่าสุด: {{ isset($subscription->updated_at) ? $subscription->updated_at->format('d M Y H:i') : 'N/A' }}</p>
                            </div>
                            @if(isset($subscription->status) && $subscription->status == 'active')
                            <div class="flex gap-2">
                                <button onclick="cancelSubscription()"
                                        class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 text-white font-medium rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    ยกเลิก
                                </button>
                                <button onclick="renewSubscription()"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    ต่ออายุ
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment History --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        ประวัติการชำระเงิน
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">วันที่</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">จำนวน</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">สถานะ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">วิธีชำระ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($paymentHistory ?? [] as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ isset($payment->date) ? date('d M Y', strtotime($payment->date)) : 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">${{ number_format($payment->amount ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($payment->status == 'completed')
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">สำเร็จ</span>
                                    @elseif($payment->status == 'failed')
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">ล้มเหลว</span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $payment->transaction_id ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($payment->method ?? 'N/A') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ไม่มีประวัติการชำระเงิน</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">การดำเนินการเร็ว</h3>
                </div>
                <div class="p-4">
                    <div class="space-y-2">
                        <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ส่งอีเมลถึงผู้ใช้</span>
                        </a>
                        <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ดูใบแจ้งหนี้</span>
                        </a>
                        <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ดาวน์โหลดใบเสร็จ</span>
                        </a>
                        <a href="#" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">แก้ไขการสมัคร</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Usage Statistics --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">สถิติการใช้งาน</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">API Calls ทั้งหมด:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $subscription->api_calls ?? '0' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">ใช้ครั้งสุดท้าย:</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ isset($subscription->last_used) ? date('d M Y', strtotime($subscription->last_used)) : 'ยังไม่เคยใช้' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">บอทที่ใช้งาน:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $subscription->active_bots ?? '0' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">พื้นที่ใช้งาน:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $subscription->storage_used ?? '0' }} MB</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script>
function cancelSubscription() {
    if (confirm('คุณต้องการยกเลิกการสมัครสมาชิกนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        // ส่งคำขอยกเลิก
        console.log('กำลังยกเลิกการสมัครสมาชิก');
        // เพิ่มโค้ดสำหรับส่ง API request ได้ที่นี่
    }
}

function renewSubscription() {
    if (confirm('คุณต้องการต่ออายุการสมัครสมาชิกนี้ใช่หรือไม่?')) {
        // ส่งคำขอต่ออายุ
        console.log('กำลังต่ออายุการสมัครสมาชิก');
        // เพิ่มโค้ดสำหรับส่ง API request ได้ที่นี่
    }
}
</script>
@endsection
