@extends('layouts.admin-v3')

@section('title', 'Broadcast Messages')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header Section พร้อม Animated Background --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 p-8 shadow-2xl">
        {{-- Animated Background Pattern --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30"></div>

        {{-- Floating Particles Effect --}}
        <div class="absolute inset-0">
            <div class="absolute top-10 left-20 w-2 h-2 bg-white/30 rounded-full animate-ping"></div>
            <div class="absolute top-32 right-32 w-3 h-3 bg-cyan-300/40 rounded-full animate-pulse"></div>
            <div class="absolute bottom-16 left-1/4 w-2 h-2 bg-teal-300/30 rounded-full animate-bounce"></div>
        </div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20">
                        <i class="fas fa-bullhorn text-white text-3xl drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg tracking-tight">📢 Broadcast Messages</h1>
                        <p class="text-emerald-100 text-lg font-medium">ส่งข้อความถึงผู้ใช้หลายคนพร้อมกัน</p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs glass-fusion backdrop-blur-sm px-3 py-1 rounded-full text-white font-semibold border border-white/30">
                                Mass Messaging • Scheduled Send
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.line-bot.broadcast.create') }}"
               class="px-8 py-3 bg-gradient-to-r from-white to-cyan-50 text-cyan-700 rounded-xl hover:from-cyan-50 hover:to-white transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 font-bold flex items-center gap-2">
                <i class="fas fa-plus-circle"></i>
                <span>สร้าง Broadcast ใหม่</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages พร้อม animation --}}
    @if(session('success'))
        <div class="mb-6 rounded-2xl glass-fusion border-2 border-green-200 dark:border-green-800 p-6 shadow-xl animate-fade-in">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-green-900 dark:text-green-100 mb-1">สำเร็จ!</h4>
                    <p class="text-green-800 dark:text-green-300 text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Broadcasts List พร้อม V3 Design --}}
    <div class="space-y-6">
        @forelse($broadcasts as $broadcast)
            <div class="group glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br
                                @if($broadcast->status === 'completed') from-green-500 to-emerald-600
                                @elseif($broadcast->status === 'sending') from-blue-500 to-cyan-600
                                @elseif($broadcast->status === 'scheduled') from-yellow-500 to-orange-600
                                @else from-gray-500 to-gray-600
                                @endif
                                flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas
                                    @if($broadcast->status === 'completed') fa-check-circle
                                    @elseif($broadcast->status === 'sending') fa-paper-plane
                                    @elseif($broadcast->status === 'scheduled') fa-clock
                                    @else fa-file-alt
                                    @endif
                                    text-white text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $broadcast->name }}</h3>
                                <span class="inline-block mt-1 px-4 py-1 rounded-full text-xs font-bold shadow-sm
                                    @if($broadcast->status === 'completed') bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/40 dark:to-emerald-900/40 text-green-800 dark:text-green-300
                                    @elseif($broadcast->status === 'sending') bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900/40 dark:to-cyan-900/40 text-blue-800 dark:text-blue-300
                                    @elseif($broadcast->status === 'scheduled') bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900/40 dark:to-orange-900/40 text-yellow-800 dark:text-yellow-300
                                    @else bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-300
                                    @endif">
                                    <i class="fas fa-circle text-xs mr-1 animate-pulse"></i>{{ strtoupper($broadcast->status) }}
                                </span>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">{{ Str::limit($broadcast->message, 150) }}</p>

                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-2 glass-fusion px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                                <i class="fas fa-users text-cyan-500"></i>
                                <span class="font-semibold">Target:</span> {{ ucfirst($broadcast->target_type) }}
                            </span>
                            @if($broadcast->scheduled_at)
                                <span class="flex items-center gap-2 glass-fusion px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-clock text-orange-500"></i>
                                    {{ $broadcast->scheduled_at->format('M d, Y H:i') }}
                                </span>
                            @endif
                            @if($broadcast->sent_count > 0)
                                <span class="flex items-center gap-2 glass-fusion px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <i class="fas fa-check text-green-500"></i>
                                    <span class="font-semibold">Sent:</span> {{ number_format($broadcast->sent_count) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 ml-4">
                        @if($broadcast->status === 'draft' || $broadcast->status === 'scheduled')
                            <form method="POST" action="{{ route('admin.line-bot.broadcast.send', $broadcast->id) }}" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('ยืนยันการส่ง broadcast นี้เลยหรือไม่?')"
                                        class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-sm font-bold flex items-center gap-2 whitespace-nowrap">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>ส่งเลย</span>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.line-bot.broadcast.show', $broadcast->id) }}"
                           class="px-6 py-2.5 glass-fusion border-2 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300 hover:border-blue-400 dark:hover:border-blue-500 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg text-sm font-bold flex items-center gap-2 whitespace-nowrap">
                            <i class="fas fa-eye"></i>
                            <span>ดูรายละเอียด</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-fusion rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-16 text-center">
                <div class="w-32 h-32 rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 flex items-center justify-center mx-auto mb-8 shadow-xl">
                    <i class="fas fa-bullhorn text-emerald-500 dark:text-emerald-400 text-5xl"></i>
                </div>
                <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-3">ยังไม่มี Broadcast</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-8 text-lg">เริ่มสร้าง broadcast message แรกของคุณเลย!</p>
                <a href="{{ route('admin.line-bot.broadcast.create') }}"
                   class="inline-block px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 font-bold text-lg">
                    <i class="fas fa-plus-circle mr-2"></i>สร้าง Broadcast ใหม่
                </a>
            </div>
        @endforelse
    </div>

    @if($broadcasts->hasPages())
        <div class="mt-8">{{ $broadcasts->links() }}</div>
    @endif
</div>
@endsection
