@extends('layouts.admin')

@section('title', 'ตลาดบอท')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    {{-- Gradient Header สีส้ม/เหลือง สำหรับ Marketplace --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-amber-600 to-yellow-700 dark:from-orange-900 dark:via-amber-900 dark:to-yellow-950 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/30 animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">ตลาดซื้อขายบอท</h1>
                    <p class="text-orange-100 text-lg">จัดการรายการบอทในตลาด (Marketplace)</p>
                </div>
            </div>

            <a href="{{ route('admin.bot-automation.marketplace.listings.create') }}"
               class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 text-orange-600 dark:text-orange-400 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้างรายการใหม่
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- จำนวนรายการทั้งหมด --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-orange-100 dark:border-orange-900/30 hover:border-orange-300 dark:hover:border-orange-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">จำนวนรายการทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalListings ?? '0' }}</h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span class="text-orange-600 dark:text-orange-400 font-medium">รายการทั้งหมด</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 dark:from-orange-600 dark:to-amber-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- จำนวนการสมัครสมาชิกที่ใช้งาน --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-green-100 dark:border-green-900/30 hover:border-green-300 dark:hover:border-green-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">สมาชิกที่ใช้งานอยู่</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $activeSubscriptions ?? '0' }}</h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span class="text-green-600 dark:text-green-400 font-medium">กำลังใช้งาน</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- รายได้ทั้งหมด --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-blue-100 dark:border-blue-900/30 hover:border-blue-300 dark:hover:border-blue-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">รายได้ทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($totalRevenue ?? 0, 2) }}</h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span class="text-blue-600 dark:text-blue-400 font-medium">ยอดรวม</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 dark:from-blue-600 dark:to-cyan-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- คะแนนเฉลี่ย --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-yellow-100 dark:border-yellow-900/30 hover:border-yellow-300 dark:hover:border-yellow-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">คะแนนเฉลี่ย</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                        {{ number_format($avgRating ?? 0, 1) }}
                        <svg class="w-6 h-6 ml-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">จากรีวิวทั้งหมด</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-yellow-500 to-amber-600 dark:from-yellow-600 dark:to-amber-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        {{-- Card Header --}}
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-3 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    รายการในตลาด
                </h2>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.bot-automation.marketplace.subscriptions.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        ดูการสมัครสมาชิก
                    </a>
                    <a href="{{ route('admin.bot-automation.marketplace.reviews.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        ดูรีวิว
                    </a>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="searchListings"
                           class="pl-10 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300"
                           placeholder="ค้นหารายการ...">
                </div>
                <select id="filterStatus"
                        class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="active">ใช้งาน</option>
                    <option value="pending">รอดำเนินการ</option>
                    <option value="inactive">ไม่ใช้งาน</option>
                </select>
                <select id="filterCategory"
                        class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300">
                    <option value="">หมวดหมู่ทั้งหมด</option>
                    <option value="sales">ฝ่ายขาย</option>
                    <option value="support">ฝ่ายสนับสนุน</option>
                    <option value="marketing">การตลาด</option>
                </select>
            </div>
        </div>

        {{-- Listings Grid --}}
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($listings ?? [] as $listing)
                <div class="group bg-white dark:bg-gray-700 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-200 dark:border-gray-600 hover:border-orange-300 dark:hover:border-orange-600 transform hover:-translate-y-2">
                    {{-- Image Placeholder --}}
                    <div class="h-48 bg-gradient-to-br from-orange-400 to-amber-500 dark:from-orange-700 dark:to-amber-800 flex items-center justify-center relative overflow-hidden">
                        <svg class="w-20 h-20 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <div class="absolute top-3 right-3">
                            @if($listing->status == 'active')
                                <span class="px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full shadow-lg">ใช้งาน</span>
                            @elseif($listing->status == 'pending')
                                <span class="px-3 py-1 bg-yellow-500 text-white text-xs font-semibold rounded-full shadow-lg">รอดำเนินการ</span>
                            @else
                                <span class="px-3 py-1 bg-gray-500 text-white text-xs font-semibold rounded-full shadow-lg">ไม่ใช้งาน</span>
                            @endif
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-5">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-1">
                            {{ $listing->title ?? 'ไม่มีชื่อ' }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                            {{ Str::limit($listing->description ?? 'ไม่มีคำอธิบาย', 100) }}
                        </p>

                        {{-- Price and Rating --}}
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                ${{ number_format($listing->price ?? 0, 2) }}
                            </span>
                            <div class="flex items-center gap-1">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="w-4 h-4 {{ $i < ($listing->rating ?? 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">({{ $listing->reviews_count ?? 0 }})</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2">
                            <a href="{{ route('admin.bot-automation.marketplace.listings.edit', $listing->id ?? 0) }}"
                               class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 dark:from-orange-600 dark:to-amber-700 dark:hover:from-orange-700 dark:hover:to-amber-800 text-white font-medium rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                แก้ไข
                            </a>
                            <button onclick="viewListing({{ $listing->id ?? 0 }})"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                ดู
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-16 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-xl font-medium text-gray-500 dark:text-gray-400 mb-2">ยังไม่มีรายการในตลาด</p>
                        <p class="text-gray-400 dark:text-gray-500 mb-6">สร้างรายการแรกของคุณเพื่อเริ่มต้นขาย!</p>
                        <a href="{{ route('admin.bot-automation.marketplace.listings.create') }}"
                           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            สร้างรายการแรก
                        </a>
                    </div>
                </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if(isset($listings) && method_exists($listings, 'links'))
            <div class="mt-6">
                {{ $listings->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- View Listing Script --}}
<script>
function viewListing(id) {
    // ฟังก์ชันสำหรับดูรายละเอียดรายการ
    console.log('กำลังดูรายการ ID:', id);
    // เพิ่มโค้ดสำหรับแสดง modal หรือ redirect ได้ที่นี่
}
</script>
@endsection
