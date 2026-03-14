@extends('layouts.admin-v3')

@section('title', 'จัดการร้านค้า - ตลาดสดไทยพร๊อม')

@section('content')
<div class="space-y-6">
    {{-- ส่วนหัว --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการร้านค้า - ตลาดสดไทยพร๊อม</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการร้านค้าและผู้ขายในระบบตลาดสด</p>
        </div>
        <a href="{{ route('admin.fresh-market.dashboard') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i> กลับแดชบอร์ด
        </a>
    </div>

    {{-- แจ้งเตือน --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    {{-- ตัวกรอง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('admin.fresh-market.sellers') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาชื่อร้านค้า, ชื่อผู้ขาย..."
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                <select name="status"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>ระงับ</option>
                    <option value="unverified" {{ request('status') === 'unverified' ? 'selected' : '' }}>รอยืนยัน</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="inline-flex items-center px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition">
                    <i class="fas fa-search mr-2"></i> ค้นหา
                </button>
                <a href="{{ route('admin.fresh-market.sellers') }}"
                   class="inline-flex items-center px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-redo mr-1"></i> ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- ตารางร้านค้า --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ร้านค้า</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เจ้าของ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สินค้า</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดขาย</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คะแนน</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sellers as $seller)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-4 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400 font-bold text-sm flex-shrink-0">
                                        {{ mb_substr($seller->shop_name, 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $seller->shop_name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">สมัครเมื่อ {{ $seller->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $seller->user->name ?? '-' }}
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-900 dark:text-white font-medium">
                                {{ number_format($seller->total_listings ?? 0) }}
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-gray-900 dark:text-white font-medium">
                                {{ number_format($seller->total_sales ?? 0, 2) }} <span class="text-xs text-gray-500">บาท</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($seller->rating)
                                    <div class="flex items-center justify-center gap-1">
                                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ number_format($seller->rating, 1) }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                @switch($seller->status)
                                    @case('active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            ใช้งาน
                                        </span>
                                        @break
                                    @case('suspended')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            ระงับ
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                            รอยืนยัน
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if($seller->status === 'unverified')
                                        <form method="POST" action="{{ route('admin.fresh-market.sellers.verify', $seller) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition"
                                                    title="ยืนยันร้านค้า">
                                                <i class="fas fa-check mr-1"></i> ยืนยัน
                                            </button>
                                        </form>
                                    @endif

                                    @if($seller->status === 'active')
                                        <form method="POST" action="{{ route('admin.fresh-market.sellers.suspend', $seller) }}"
                                              onsubmit="return confirm('ยืนยันการระงับร้านค้านี้?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition"
                                                    title="ระงับร้านค้า">
                                                <i class="fas fa-ban mr-1"></i> ระงับ
                                            </button>
                                        </form>
                                    @endif

                                    @if($seller->status === 'suspended')
                                        <form method="POST" action="{{ route('admin.fresh-market.sellers.activate', $seller) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition"
                                                    title="เปิดใช้งานร้านค้า">
                                                <i class="fas fa-undo mr-1"></i> เปิดใช้งาน
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-store text-4xl mb-3 block"></i>
                                ไม่พบร้านค้าที่ตรงกับเงื่อนไข
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sellers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $sellers->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
