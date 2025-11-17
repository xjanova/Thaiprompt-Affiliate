@extends('layouts.admin-v3')

@section('title', 'บทสนทนา AI Chatbot')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Premium Header -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 via-cyan-700 to-teal-900 p-10 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20">
                        <i class="fas fa-comments text-4xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg">💬 บทสนทนา AI</h1>
                        <p class="text-blue-100 text-lg font-medium">ติดตามและจัดการการสนทนากับลูกค้าผ่าน AI Chatbot</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.line-bot.ai.index') }}"
               class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/25 text-white rounded-xl hover:glass-fusion transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1">
                <i class="fas fa-arrow-left mr-2"></i>
                <span class="font-semibold">กลับไปตั้งค่า</span>
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="style="background: var(--arrow-x-accent-gradient)" rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">บทสนทนาทั้งหมด</p>
                    <h3 class="text-3xl font-bold">{{ number_format($stats['total_conversations']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-comments text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="style="background: var(--arrow-x-primary-gradient)" rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">บทสนทนาวันนี้</p>
                    <h3 class="text-3xl font-bold">{{ number_format($stats['today_conversations']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-calendar-day text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">กำลังดำเนินการ</p>
                    <h3 class="text-3xl font-bold">{{ number_format($stats['active_conversations']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-spinner text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">ข้อความทั้งหมด</p>
                    <h3 class="text-3xl font-bold">{{ number_format($stats['total_messages']) }}</h3>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center" border border-white/20 dark:border-white/10>
                    <i class="fas fa-envelope text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversations List -->
    <div class="glass-fusion rounded-2xl shadow-xl border border-gray-100 overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-xl font-bold text-gray-900">รายการบทสนทนา</h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($conversations as $conversation)
                <div class="p-6 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold">
                                    {{ substr($conversation->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900">{{ $conversation->user->name ?? 'ผู้ใช้ไม่ระบุชื่อ' }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $conversation->user->email ?? 'ไม่มีอีเมล' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mt-3">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-robot text-purple-500"></i>
                                    {{ $conversation->aiSetting->name ?? 'ไม่ระบุ AI' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-clock text-blue-500"></i>
                                    {{ $conversation->created_at->diffForHumans() }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-comments text-green-500"></i>
                                    {{ $conversation->messages->count() }} ข้อความ
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($conversation->status === 'active')
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">
                                    <span class="w-2 h-2 bg-green-500 rounded-full inline-block mr-1 animate-pulse"></span>
                                    Active
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gray-100/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 rounded-full text-xs font-semibold">
                                    Completed
                                </span>
                            @endif

                            <a href="{{ route('admin.line-bot.ai.conversations.detail', $conversation->id) }}"
                               class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all text-sm font-semibold shadow-md hover:shadow-lg">
                                <i class="fas fa-eye mr-1"></i>ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="w-24 h-24 rounded-full bg-gray-100/50 dark:bg-gray-800/50 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-inbox text-gray-400 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">ยังไม่มีบทสนทนา</h3>
                    <p class="text-gray-600 dark:text-gray-400">เมื่อมีผู้ใช้สนทนากับ AI บทสนทนาจะแสดงที่นี่</p>
                </div>
            @endforelse
        </div>

        @if($conversations->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
