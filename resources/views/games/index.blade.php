@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-indigo-900 py-12">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-6xl font-black text-white mb-4" style="font-family: 'Orbitron', sans-serif;">
                🎮 GAME ARCADE
            </h1>
            <p class="text-xl text-gray-300">เล่นเกมสนุกๆ และปลดล็อคยานพิเศษ!</p>
        </div>

        <!-- Games Grid -->
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
</style>
@endsection
