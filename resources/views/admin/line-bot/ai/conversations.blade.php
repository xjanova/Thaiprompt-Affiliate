@extends('layouts.admin-v3')

@section('title', 'บทสนทนา AI Chatbot')

@push('styles')
@vite(['resources/css/app.css'])
<style>
/* Fade In Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

/* Counter Animation */
@keyframes countUp {
    from {
        transform: scale(0.5);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-count-up {
    animation: countUp 0.6s ease-out;
}

/* Pulse Animation */
@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse-slow {
    animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
@endpush

@push('scripts')
@vite(['resources/js/app.js'])
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    searchQuery: '',
    filterStatus: 'all',
    filterProvider: '',
    showFilters: false,

    get filteredConversations() {
        let filtered = [...document.querySelectorAll('.conversation-item')];

        filtered.forEach(item => {
            const userName = item.dataset.userName?.toLowerCase() || '';
            const aiName = item.dataset.aiName?.toLowerCase() || '';
            const status = item.dataset.status || '';
            const provider = item.dataset.provider?.toLowerCase() || '';
            const searchLower = this.searchQuery.toLowerCase();

            let show = true;

            // Search filter
            if (this.searchQuery && !userName.includes(searchLower) && !aiName.includes(searchLower)) {
                show = false;
            }

            // Status filter
            if (this.filterStatus !== 'all' && status !== this.filterStatus) {
                show = false;
            }

            // Provider filter
            if (this.filterProvider && !provider.includes(this.filterProvider.toLowerCase())) {
                show = false;
            }

            item.style.display = show ? '' : 'none';
        });
    }
}" @keyup.escape="showFilters = false">
    <!-- Premium LINE-Themed Header -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-10 shadow-2xl shadow-green-500/30 animate-fade-in">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30"></div>

        <div class="relative flex items-center justify-between flex-wrap gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/30">
                        <i class="fas fa-comments text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg">💬 บทสนทนา AI</h1>
                        <p class="text-white/95 text-lg font-medium">ติดตามและจัดการการสนทนากับลูกค้าผ่าน AI Chatbot</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="showFilters = !showFilters"
                        class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:border-white/50 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1">
                    <i class="fas fa-filter mr-2"></i>
                    <span class="font-semibold">ฟิลเตอร์</span>
                </button>
                <a href="{{ route('admin.line-bot.ai.index') }}"
                   class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:border-white/50 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span class="font-semibold">กลับไปตั้งค่า</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards with Animated Counters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">บทสนทนาทั้งหมด</p>
                    <h3 class="text-3xl font-bold animate-count-up">{{ number_format($stats['total_conversations']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-comments text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-600 to-cyan-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 animate-fade-in" style="animation-delay: 0.1s">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">บทสนทนาวันนี้</p>
                    <h3 class="text-3xl font-bold animate-count-up">{{ number_format($stats['today_conversations']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-calendar-day text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 animate-fade-in" style="animation-delay: 0.2s">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">กำลังดำเนินการ</p>
                    <h3 class="text-3xl font-bold animate-count-up">{{ number_format($stats['active_conversations']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-spinner text-2xl animate-pulse-slow"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-600 to-pink-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 animate-fade-in" style="animation-delay: 0.3s">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">ข้อความทั้งหมด</p>
                    <h3 class="text-3xl font-bold animate-count-up">{{ number_format($stats['total_messages']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-envelope text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div x-show="showFilters"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-4"
         class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search Input -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-2"></i>ค้นหา
                </label>
                <input type="text"
                       x-model="searchQuery"
                       @input="filteredConversations"
                       placeholder="ชื่อผู้ใช้, AI name..."
                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-circle mr-2"></i>สถานะ
                </label>
                <select x-model="filterStatus"
                        @change="filteredConversations"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all">
                    <option value="all">ทั้งหมด</option>
                    <option value="active">กำลังดำเนินการ</option>
                    <option value="completed">เสร็จสิ้น</option>
                </select>
            </div>

            <!-- Provider Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-robot mr-2"></i>AI Provider
                </label>
                <input type="text"
                       x-model="filterProvider"
                       @input="filteredConversations"
                       placeholder="OpenAI, Anthropic..."
                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-all">
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex justify-end gap-3 mt-4">
            <button @click="searchQuery = ''; filterStatus = 'all'; filterProvider = ''; filteredConversations()"
                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-xl transition-all duration-300 font-semibold">
                <i class="fas fa-redo mr-2"></i>รีเซ็ต
            </button>
            <button @click="showFilters = false"
                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition-all duration-300 font-semibold shadow-lg">
                <i class="fas fa-check mr-2"></i>ปิด
            </button>
        </div>
    </div>

    <!-- Conversations List with Card Layout -->
    <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-5 border-b-2 border-gray-200 dark:border-slate-700 bg-gradient-to-r from-gray-50 to-white dark:from-slate-800 dark:to-slate-700">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                    รายการบทสนทนา
                </h2>
                <span class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-full text-sm font-bold shadow-lg">
                    {{ $conversations->total() }} รายการ
                </span>
            </div>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-slate-700">
            @forelse($conversations as $conversation)
                <div class="conversation-item p-6 hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-cyan-50/50 dark:hover:from-slate-700/50 dark:hover:to-slate-600/50 transition-all duration-300 transform hover:scale-[1.01]"
                     data-user-name="{{ $conversation->user->name ?? '' }}"
                     data-ai-name="{{ $conversation->aiSetting->name ?? '' }}"
                     data-status="{{ $conversation->status }}"
                     data-provider="{{ $conversation->aiSetting->provider ?? '' }}">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="relative">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center text-white font-bold text-xl shadow-lg">
                                        {{ substr($conversation->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    @if($conversation->status === 'active')
                                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-slate-800 animate-pulse"></div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-1">
                                        {{ $conversation->user->name ?? 'ผู้ใช้ไม่ระบุชื่อ' }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-envelope mr-1"></i>
                                        {{ $conversation->user->email ?? 'ไม่มีอีเมล' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span class="flex items-center gap-2 px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg font-medium">
                                    <i class="fas fa-robot"></i>
                                    {{ $conversation->aiSetting->name ?? 'ไม่ระบุ AI' }}
                                </span>
                                <span class="flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg font-medium">
                                    <i class="fas fa-clock"></i>
                                    {{ $conversation->created_at->diffForHumans() }}
                                </span>
                                <span class="flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg font-medium">
                                    <i class="fas fa-comments"></i>
                                    {{ $conversation->messages->count() }} ข้อความ
                                </span>
                                @if($conversation->aiSetting)
                                    <span class="flex items-center gap-2 px-3 py-1.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded-lg font-medium">
                                        <i class="fas fa-microchip"></i>
                                        {{ $conversation->aiSetting->provider }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-3">
                            @if($conversation->status === 'active')
                                <span class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-full text-sm font-bold shadow-lg flex items-center gap-2">
                                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                    Active
                                </span>
                            @else
                                <span class="px-4 py-2 bg-gradient-to-r from-gray-400 to-gray-500 text-white rounded-full text-sm font-bold shadow-lg">
                                    Completed
                                </span>
                            @endif

                            <a href="{{ route('admin.line-bot.ai.conversations.detail', $conversation->id) }}"
                               class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition-all duration-300 text-sm font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center gap-2">
                                <i class="fas fa-eye"></i>
                                <span>ดูรายละเอียด</span>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-16 text-center">
                    <div class="w-32 h-32 mx-auto bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-3xl flex items-center justify-center mb-6 shadow-xl transform hover:scale-105 transition-all duration-300">
                        <i class="fas fa-comments text-6xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-3">ยังไม่มีบทสนทนา</h3>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">บทสนทนาจะแสดงที่นี่เมื่อมีผู้ใช้แชทกับ AI Bot</p>
                    <a href="{{ route('admin.line-bot.ai.index') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition-all duration-300 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <i class="fas fa-cog"></i>
                        <span>ตั้งค่า AI Bot</span>
                    </a>
                </div>
            @endforelse
        </div>

        @if($conversations->hasPages())
            <div class="px-6 py-5 border-t-2 border-gray-200 dark:border-slate-700 bg-gradient-to-r from-gray-50 to-white dark:from-slate-800 dark:to-slate-700">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
