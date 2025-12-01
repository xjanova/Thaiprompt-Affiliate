@extends('layouts.app')

@section('content')
{{-- หน้า Game Arcade - V3 Premium Design --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 py-12 relative overflow-hidden">

    {{-- พื้นหลังแบบ Gaming --}}
    <div class="absolute inset-0 pointer-events-none">
        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-5"
             style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px);
                    background-size: 50px 50px;"></div>

        {{-- Glow Effects --}}
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-cyan-600/20 rounded-full blur-[120px] animate-pulse" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        {{-- Header --}}
        <div class="text-center mb-12">
            <div class="inline-block mb-4">
                <div class="flex items-center justify-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-2xl flex items-center justify-center shadow-2xl shadow-cyan-500/30 animate-bounce">
                        <span class="text-3xl">🎮</span>
                    </div>
                </div>
            </div>

            <h1 class="text-5xl md:text-7xl font-black mb-4" style="font-family: 'Orbitron', sans-serif;">
                <span class="bg-gradient-to-r from-cyan-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                    GAME ARCADE
                </span>
            </h1>
            <p class="text-xl text-gray-300">เล่นเกมสนุกๆ และปลดล็อคยานพิเศษ!</p>
        </div>

        {{-- Special Featured Section: Tarot --}}
        <div class="mb-12 max-w-7xl mx-auto">
            <div class="relative group">
                {{-- Glow Border Animation --}}
                <div class="absolute -inset-1 bg-gradient-to-r from-purple-500 via-pink-500 to-amber-500 rounded-3xl blur-md opacity-50 group-hover:opacity-100 transition-opacity duration-500 animate-pulse"></div>

                {{-- Card Content --}}
                <a href="{{ route('tarot.index') }}"
                   class="relative block bg-gradient-to-br from-slate-900/95 via-purple-900/90 to-slate-900/95 backdrop-blur-xl rounded-3xl overflow-hidden border border-white/10">
                    <div class="flex flex-col md:flex-row items-center">
                        {{-- Left: Visual --}}
                        <div class="w-full md:w-1/3 p-8 flex justify-center items-center relative">
                            <div class="relative">
                                {{-- Mystical Ring --}}
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-48 h-48 border-2 border-purple-400/30 rounded-full animate-spin-slow"></div>
                                    <div class="absolute w-40 h-40 border border-pink-400/20 rounded-full animate-spin-slow-reverse"></div>
                                </div>
                                {{-- Crystal Ball --}}
                                <div class="relative z-10 w-32 h-32 bg-gradient-to-br from-purple-600 via-pink-500 to-amber-500 rounded-3xl flex items-center justify-center shadow-2xl shadow-purple-500/50 transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                                    <span class="text-6xl filter drop-shadow-[0_0_30px_rgba(168,85,247,0.8)]">🔮</span>
                                </div>
                            </div>
                        </div>

                        {{-- Right: Info --}}
                        <div class="w-full md:w-2/3 p-8">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-900 text-xs font-bold rounded-full shadow-lg">
                                    ✨ พิเศษ
                                </span>
                                <span class="px-3 py-1 bg-purple-500/20 border border-purple-500/40 text-purple-300 text-xs font-medium rounded-full">
                                    Mystical
                                </span>
                            </div>

                            <h2 class="text-3xl md:text-4xl font-black text-white mb-3 group-hover:text-purple-200 transition-colors">
                                🎴 ไพ่ทาโร่ต์ Mystical
                            </h2>

                            <p class="text-purple-200/80 text-lg mb-4 leading-relaxed">
                                ค้นพบคำตอบจากจักรวาล ไขความลึกลับแห่งชะตา ด้วยไพ่ทาโร่ต์ 78 ใบ
                                แบบ 3D Interactive พร้อม AI ตีความอัจฉริยะ
                            </p>

                            <div class="flex flex-wrap gap-4 mb-4">
                                <div class="flex items-center gap-2 text-sm text-purple-300/70">
                                    <span class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">🎴</span>
                                    <span>78 ไพ่ครบชุด</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-purple-300/70">
                                    <span class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center">✨</span>
                                    <span>3D Animation</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-purple-300/70">
                                    <span class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center">🧠</span>
                                    <span>AI ตีความ</span>
                                </div>
                            </div>

                            <div class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl text-white font-bold shadow-lg shadow-purple-500/30 group-hover:shadow-purple-500/50 transition-all">
                                <span>เริ่มทำนายดวง</span>
                                <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Section Title for Games --}}
        <div class="flex items-center gap-4 mb-8 max-w-7xl mx-auto">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-500/50 to-transparent"></div>
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <span>🎮</span>
                <span>เกมส์อื่นๆ</span>
            </h2>
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-purple-500/50 to-transparent"></div>
        </div>

        {{-- Games Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
            @forelse($games as $game)
                <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl overflow-hidden shadow-2xl transform hover:scale-105 transition-all duration-300 border-2 border-purple-500/30 hover:border-purple-500">
                    <!-- Thumbnail -->
                    <div class="h-48 bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center relative overflow-hidden">
                        @if($game->thumbnail)
                            <img src="{{ $game->thumbnail }}" alt="{{ $game->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-8xl">🚀</div>
                        @endif

                        <!-- Category Badge -->
                        <div class="absolute top-4 right-4 bg-black/70 px-3 py-1 rounded-full text-xs font-bold text-cyan-400 uppercase">
                            {{ $game->category }}
                        </div>

                        <!-- Level Required Badge -->
                        @if($game->min_level_required > 1)
                            <div class="absolute top-4 left-4 bg-yellow-500 px-3 py-1 rounded-full text-xs font-bold text-black">
                                LV {{ $game->min_level_required }}+
                            </div>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-white mb-2">{{ $game->name }}</h3>
                        <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $game->description }}</p>

                        @auth
                            @if(isset($userProgress[$game->id]))
                                <div class="bg-black/30 rounded-lg p-3 mb-4">
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-gray-400">Best Score:</span>
                                            <span class="text-cyan-400 font-bold ml-1">
                                                {{ number_format($userProgress[$game->id]->highest_score) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Best Wave:</span>
                                            <span class="text-purple-400 font-bold ml-1">
                                                {{ $userProgress[$game->id]->highest_wave }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Plays:</span>
                                            <span class="text-green-400 font-bold ml-1">
                                                {{ $userProgress[$game->id]->total_plays }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Kills:</span>
                                            <span class="text-red-400 font-bold ml-1">
                                                {{ number_format($userProgress[$game->id]->total_kills) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endauth

                        <!-- Play Button -->
                        <a href="{{ route('games.show', $game->slug) }}"
                           class="block w-full bg-gradient-to-r from-cyan-500 to-purple-600 hover:from-cyan-400 hover:to-purple-500 text-white font-bold py-3 px-6 rounded-lg text-center transition-all duration-300 shadow-lg hover:shadow-cyan-500/50">
                            @auth
                                ▶️ PLAY NOW
                            @else
                                @if($game->requires_auth)
                                    🔒 LOGIN TO PLAY
                                @else
                                    ▶️ PLAY NOW
                                @endif
                            @endauth
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12">
                    <p class="text-gray-400 text-xl">ยังไม่มีเกม กรุณาเพิ่มเกมใน Database</p>
                </div>
            @endforelse
        </div>

        @guest
            <div class="mt-12 text-center bg-gradient-to-r from-purple-900/50 to-pink-900/50 rounded-2xl p-8 max-w-2xl mx-auto border-2 border-purple-500/30">
                <h3 class="text-2xl font-bold text-white mb-4">🎯 เข้าสู่ระบบเพื่อปลดล็อคเพิ่มเติม!</h3>
                <p class="text-gray-300 mb-6">
                    เข้าสู่ระบบเพื่อบันทึกความคืบหน้า, ปลดล็อคยานและอาวุธพิเศษ, และติดอันดับ Leaderboard!
                </p>
                <div class="flex gap-4 justify-center">
                    <a href="{{ route('login') }}" class="bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-3 px-8 rounded-lg">
                        เข้าสู่ระบบ
                    </a>
                    <a href="{{ route('register') }}" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 px-8 rounded-lg">
                        สมัครสมาชิก
                    </a>
                </div>
            </div>
        @endguest
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&display=swap');

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* หมุนช้าๆ สำหรับ Tarot Ring */
    @keyframes spin-slow {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin-slow {
        animation: spin-slow 20s linear infinite;
    }
    .animate-spin-slow-reverse {
        animation: spin-slow 15s linear infinite reverse;
    }
</style>
@endsection
