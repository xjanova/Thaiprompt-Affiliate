@extends('layouts.admin-v3')

@section('title', 'จัดการ Hybrid Bot Keywords')

@section('content')
<div class="container-fluid px-4 py-6" x-data="keywordManager">
    {{-- Header Section พร้อม LINE Green theme --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-[#00B900] via-[#00D000] to-[#00E600] p-8 shadow-2xl">
        {{-- Animated Background Pattern --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        {{-- Floating Particles Effect --}}
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-2 h-2 bg-white/30 rounded-full animate-ping"></div>
            <div class="absolute top-20 right-20 w-3 h-3 bg-green-300/40 rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 left-1/3 w-2 h-2 bg-emerald-300/30 rounded-full animate-bounce"></div>
        </div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-xl flex items-center justify-center shadow-xl border border-white/20">
                        <i class="fas fa-key text-white text-3xl drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">🔑 Hybrid Bot Keywords</h1>
                        <p class="text-green-50 text-lg font-medium">จัดการ keywords สำหรับระบบตอบอัตโนมัติ</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs glass-fusion backdrop-blur-sm px-3 py-1 rounded-full text-white font-semibold border border-white/30">
                                Auto-Response • Smart Matching
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.line-bot.keywords.create') }}"
               class="px-8 py-3 glass-fusion backdrop-blur-xl text-white rounded-xl hover:bg-white/20 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 font-bold flex items-center gap-2 border border-white/30">
                <i class="fas fa-plus-circle"></i>
                <span>สร้าง Keyword ใหม่</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages พร้อม animation --}}
    @if(session('success'))
        <div class="mb-6 rounded-2xl glass-fusion backdrop-blur-xl border-2 border-[#00B900] dark:border-[#00E600] p-6 shadow-xl animate-fade-in">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-[#00B900] dark:text-[#00E600] mb-1">สำเร็จ!</h4>
                    <p class="text-gray-800 dark:text-gray-300 text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Statistics Cards พร้อม animated counters --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Keywords --}}
        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Keywords ทั้งหมด</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['total_keywords'] }})">0</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">รายการทั้งหมด</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Active Keywords --}}
        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Keywords ใช้งาน</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['active_keywords'] }})">0</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">กำลังทำงาน</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Inactive Keywords --}}
        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Keywords ปิดใช้งาน</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['inactive_keywords'] }})">0</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">ไม่ใช้งาน</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-red-500 to-orange-600 flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-ban text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Categories --}}
        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">หมวดหมู่</p>
                    <h3 class="text-4xl font-black bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['by_category']->count() }})">0</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">จัดหมวดหมู่</p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-th text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filter พร้อม Alpine.js --}}
    <div class="glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 mb-8 shadow-lg">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                <i class="fas fa-search text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">ค้นหาและกรองข้อมูล</h3>
        </div>
        <form method="GET" class="flex gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" placeholder="ค้นหา Keyword..."
                        value="{{ request('search') }}"
                        x-model="searchQuery"
                        class="w-full pl-12 pr-4 py-3 glass-fusion backdrop-blur-sm border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300">
                </div>
            </div>
            <select name="category"
                    x-model="selectedCategory"
                    class="px-4 py-3 glass-fusion backdrop-blur-sm border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300">
                <option value="">📂 ทั้งหมด</option>
                <option value="faq" @selected(request('category') === 'faq')>❓ FAQ</option>
                <option value="support" @selected(request('category') === 'support')>🎯 Support</option>
                <option value="product" @selected(request('category') === 'product')>🛍️ Product</option>
                <option value="custom" @selected(request('category') === 'custom')>⚙️ Custom</option>
            </select>
            <select name="status"
                    x-model="selectedStatus"
                    class="px-4 py-3 glass-fusion backdrop-blur-sm border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300">
                <option value="">🔄 สถานะทั้งหมด</option>
                <option value="active" @selected(request('status') === 'active')>✅ ใช้งาน</option>
                <option value="inactive" @selected(request('status') === 'inactive')>⛔ ปิดใช้งาน</option>
            </select>
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#009900] hover:to-[#00D000] text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
            @if(request('search') || request('category') || request('status'))
                <a href="{{ route('admin.line-bot.keywords.index') }}" class="px-6 py-3 glass-fusion backdrop-blur-sm border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:shadow-lg transition-all duration-300 font-semibold">
                    <i class="fas fa-times mr-2"></i>ล้างตัวกรอง
                </a>
            @endif
        </form>
    </div>

    {{-- Sort Options --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">เรียงตาม:</span>
            <div class="flex gap-2">
                <button @click="sortBy('name')"
                        :class="currentSort === 'name' ? 'bg-[#00B900] text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-300 hover:shadow-md">
                    <i class="fas fa-sort-alpha-down mr-1"></i>ชื่อ
                </button>
                <button @click="sortBy('priority')"
                        :class="currentSort === 'priority' ? 'bg-[#00B900] text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-300 hover:shadow-md">
                    <i class="fas fa-sort-amount-down mr-1"></i>Priority
                </button>
                <button @click="sortBy('date')"
                        :class="currentSort === 'date' ? 'bg-[#00B900] text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-300 hover:shadow-md">
                    <i class="fas fa-calendar mr-1"></i>วันที่
                </button>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.line-bot.keywords.analytics') }}"
               class="px-4 py-2 glass-fusion backdrop-blur-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-md transition-all duration-300 font-semibold text-sm">
                <i class="fas fa-chart-bar mr-1"></i>Analytics
            </a>
        </div>
    </div>

    {{-- Keywords Grid (Card Layout) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" x-show="viewMode === 'grid'">
        @forelse($keywords as $keyword)
            <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                {{-- Header --}}
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                            <i class="fas fa-key text-white text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ $keyword->keyword }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Priority: {{ $keyword->priority }}</p>
                        </div>
                    </div>
                    @if($keyword->is_active)
                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-[#00B900]/20 text-[#00B900] dark:text-[#00E600]">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    @else
                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300">
                            <i class="fas fa-ban"></i>
                        </span>
                    @endif
                </div>

                {{-- Description --}}
                @if($keyword->description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ $keyword->description }}</p>
                @endif

                {{-- Category Badge --}}
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        @if($keyword->category === 'faq') bg-cyan-100 dark:bg-cyan-500/20 text-cyan-800 dark:text-cyan-300
                        @elseif($keyword->category === 'support') bg-yellow-100 dark:bg-yellow-500/20 text-yellow-800 dark:text-yellow-300
                        @elseif($keyword->category === 'product') bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300
                        @else bg-gray-100 dark:bg-gray-500/20 text-gray-800 dark:text-gray-300
                        @endif">
                        {{ ucfirst($keyword->category) }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-500/20 text-purple-800 dark:text-purple-300">
                        {{ ucfirst($keyword->response_type) }}
                    </span>
                </div>

                {{-- Trigger Words --}}
                <div class="mb-4">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Trigger Words:</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach(array_slice($keyword->trigger_words ?? [], 0, 4) as $trigger)
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 rounded text-xs">
                                {{ $trigger }}
                            </span>
                        @endforeach
                        @if(count($keyword->trigger_words ?? []) > 4)
                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-500/20 text-gray-800 dark:text-gray-300 rounded text-xs">
                                +{{ count($keyword->trigger_words) - 4 }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.line-bot.keywords.edit', $keyword) }}"
                       class="flex-1 px-3 py-2 bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-500/30 transition text-sm font-semibold text-center">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button @click="duplicateKeyword({{ $keyword->id }})"
                            class="flex-1 px-3 py-2 bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-500/30 transition text-sm font-semibold">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button @click="testKeywordQuick('{{ $keyword->keyword }}')"
                            class="flex-1 px-3 py-2 bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-200 dark:hover:bg-green-500/30 transition text-sm font-semibold">
                        <i class="fas fa-flask"></i>
                    </button>
                    <form method="POST" action="{{ route('admin.line-bot.keywords.destroy', $keyword) }}" class="flex-1"
                          onsubmit="return confirm('ยืนยันการลบ Keyword นี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-3 py-2 bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-500/30 transition text-sm font-semibold">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="glass-fusion backdrop-blur-xl rounded-2xl p-12 text-center border border-white/20 dark:border-white/10">
                    <div class="w-24 h-24 rounded-full bg-[#00B900]/20 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-key text-[#00B900] dark:text-[#00E600] text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ไม่มี Keywords</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มสร้าง Keyword แรกของคุณตอนนี้</p>
                    <a href="{{ route('admin.line-bot.keywords.create') }}"
                       class="inline-block px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#009900] hover:to-[#00D000] text-white rounded-lg transition font-bold">
                        <i class="fas fa-plus mr-2"></i>สร้าง Keyword ใหม่
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($keywords->hasPages())
        <div class="mt-8">
            {{ $keywords->links() }}
        </div>
    @endif

    {{-- Test Keyword Section พร้อม Alpine.js --}}
    <div class="mt-8 glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                <i class="fas fa-flask text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-white">🧪 ทดสอบ Keyword</h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">พิมพ์ข้อความเพื่อทดสอบว่า Keyword ไหนจะตรงกับข้อความนี้</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="relative">
                <div class="absolute top-3 left-3 text-gray-400">
                    <i class="fas fa-comment-alt"></i>
                </div>
                <textarea x-model="testMessage"
                          placeholder="พิมพ์ข้อความทดสอบ... เช่น 'คืนเงินได้ไหม?' หรือ 'shipping ขนาดไหน' 💬"
                          class="w-full pl-12 pr-4 py-4 glass-fusion backdrop-blur-sm border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 font-medium"
                          rows="4"></textarea>
            </div>

            <button @click="testKeyword()"
                    :disabled="!testMessage.trim()"
                    :class="testMessage.trim() ? 'opacity-100 cursor-pointer' : 'opacity-50 cursor-not-allowed'"
                    class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#009900] hover:to-[#00D000] text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 font-bold text-lg">
                <i class="fas fa-play mr-2"></i>เริ่มทดสอบ
            </button>

            <div x-show="showTestResult"
                 x-transition
                 class="mt-6 p-6 glass-fusion backdrop-blur-sm rounded-2xl border-2 border-gray-200 dark:border-gray-700 shadow-lg">
                <div x-html="testResultContent"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('keywordManager', () => ({
        searchQuery: '{{ request("search") }}',
        selectedCategory: '{{ request("category") }}',
        selectedStatus: '{{ request("status") }}',
        viewMode: 'grid',
        currentSort: 'name',
        testMessage: '',
        showTestResult: false,
        testResultContent: '',

        // Animated counter for statistics
        animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 20);
        },

        // Sort keywords
        sortBy(type) {
            this.currentSort = type;
            // Implement sorting logic or trigger page reload with sort parameter
            window.location.href = `{{ route('admin.line-bot.keywords.index') }}?sort=${type}&search=${this.searchQuery}&category=${this.selectedCategory}&status=${this.selectedStatus}`;
        },

        // Test keyword
        async testKeyword() {
            if (!this.testMessage.trim()) {
                alert('กรุณาพิมพ์ข้อความทดสอบ');
                return;
            }

            this.showTestResult = true;
            this.testResultContent = '<p class="text-gray-600 dark:text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...</p>';

            try {
                const response = await fetch('{{ route("admin.line-bot.keywords.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: this.testMessage })
                });

                const data = await response.json();

                if (data.success) {
                    if (data.matched) {
                        this.testResultContent = `
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-[#00B900]/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check-circle text-[#00B900] dark:text-[#00E600] text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg text-[#00B900] dark:text-[#00E600] mb-3">พบ Keyword ✓</h3>
                                    <div class="space-y-2 text-sm">
                                        <div><strong class="text-gray-900 dark:text-white">Keyword:</strong> <span class="text-gray-600 dark:text-gray-400">${data.keyword}</span></div>
                                        <div><strong class="text-gray-900 dark:text-white">หมวดหมู่:</strong> <span class="text-gray-600 dark:text-gray-400">${data.category}</span></div>
                                        <div><strong class="text-gray-900 dark:text-white">Priority:</strong> <span class="text-gray-600 dark:text-gray-400">${data.priority}</span></div>
                                        <div><strong class="text-gray-900 dark:text-white">ประเภท:</strong> <span class="text-gray-600 dark:text-gray-400">${data.response_type}</span></div>
                                        <div><strong class="text-gray-900 dark:text-white">Trigger Words:</strong> <span class="text-gray-600 dark:text-gray-400">${data.trigger_words.join(', ')}</span></div>
                                        <div><strong class="text-gray-900 dark:text-white">ข้อความตอบ:</strong> <span class="text-gray-600 dark:text-gray-400">${data.response_text || '(ไม่มี)'}</span></div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        this.testResultContent = `
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg text-blue-800 dark:text-blue-300 mb-2">ไม่พบ Keyword</h3>
                                    <p class="text-gray-600 dark:text-gray-400">ข้อความนี้จะถูกส่งให้ AI provider เพื่อประมวลผล</p>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    this.testResultContent = `<div class="text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>${data.error}</div>`;
                }
            } catch (error) {
                this.testResultContent = `<div class="text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-2"></i>เกิดข้อผิดพลาด: ${error.message}</div>`;
            }
        },

        // Quick test keyword
        testKeywordQuick(keyword) {
            this.testMessage = keyword;
            this.testKeyword();
            // Scroll to test section
            document.querySelector('.mt-8.glass-fusion').scrollIntoView({ behavior: 'smooth' });
        },

        // Duplicate keyword
        async duplicateKeyword(id) {
            if (confirm('ต้องการคัดลอก Keyword นี้?')) {
                window.location.href = `{{ route('admin.line-bot.keywords.index') }}/${id}/clone`;
            }
        }
    }));
});
</script>
@endpush

@vite(['resources/js/app.js'])
@endsection
