@extends('layouts.admin-v3')

@section('title', 'Snake.io Service Monitor')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6" x-data="snakeMonitor()">

    {{-- ═══════════ Header ═══════════ --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 p-6 shadow-xl">
        {{-- พื้นหลัง pattern งู --}}
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 400 200" fill="none">
                <path d="M0,100 Q50,50 100,100 T200,100 T300,100 T400,100" stroke="white" stroke-width="8" fill="none"/>
                <circle cx="380" cy="100" r="15" fill="white"/>
                <circle cx="372" cy="94" r="3" fill="black"/>
            </svg>
        </div>

        <div class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                    🐍 Snake.io Monitor
                </h1>
                <p class="text-green-100 text-sm mt-1">ระบบติดตามเกม Multiplayer แบบ Real-time</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                {{-- สถานะ Service --}}
                <span id="service-status-badge"
                      class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg
                             {{ $status['is_online'] ? 'bg-green-400/90 text-green-900' : 'bg-gray-400/90 text-gray-800' }}">
                    <span class="relative flex h-3 w-3">
                        @if($status['is_online'])
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-600 opacity-75"></span>
                        @endif
                        <span class="relative inline-flex rounded-full h-3 w-3 {{ $status['is_online'] ? 'bg-green-700' : 'bg-gray-600' }}"></span>
                    </span>
                    <span id="status-text">{{ $status['is_online'] ? 'ONLINE' : 'OFFLINE' }}</span>
                </span>

                {{-- ปุ่มตั้งค่าเกม --}}
                <a href="{{ route('admin.games.game-settings.index') }}"
                   class="px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl font-semibold text-sm shadow-lg transition-all duration-200 hover:scale-105 border border-white/30">
                    ⚙️ ตั้งค่าเกม
                </a>

                {{-- ปุ่ม Start/Stop --}}
                @if($status['is_online'])
                <button @click="stopService()"
                        class="px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold text-sm shadow-lg transition-all duration-200 hover:scale-105">
                    ⏹️ หยุด Service
                </button>
                @else
                <button @click="startService()"
                        class="px-5 py-2.5 bg-emerald-400 hover:bg-emerald-500 text-emerald-900 rounded-xl font-semibold text-sm shadow-lg transition-all duration-200 hover:scale-105">
                    ▶️ เริ่ม Service
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════ Stats Cards ═══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- ผู้เล่นออนไลน์ --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl shadow-lg">
                    👥
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">ผู้เล่นออนไลน์</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-players" x-text="stats.totalPlayers">{{ $status['total_players'] }}</p>
                </div>
            </div>
        </div>

        {{-- ห้องที่เปิดอยู่ --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white text-xl shadow-lg">
                    🚪
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">ห้องที่เปิดอยู่</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-rooms" x-text="stats.totalRooms">{{ $status['total_rooms'] }}</p>
                </div>
            </div>
        </div>

        {{-- กิจกรรมน่าสงสัย --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-xl shadow-lg">
                    ⚠️
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">กิจกรรมน่าสงสัย</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-suspicious" x-text="stats.suspicious">{{ count($suspiciousActivities) }}</p>
                </div>
            </div>
        </div>

        {{-- คะแนนสูงสุด --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center text-white text-xl shadow-lg">
                    🏆
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">คะแนนสูงสุด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format(collect($topScores)->first()?->score ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ ผู้เล่นที่กำลังเล่น (Real-time) ═══════════ --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                🎮 ผู้เล่นที่กำลังเล่นอยู่
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300"
                      id="sync-players-count" x-text="syncPlayers.length">{{ count($syncPlayers ?? []) }}</span>
            </h3>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="inline-flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                Real-time Sync — อัปเดตทุก 5 วินาที
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">Player ID</th>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ชื่อ</th>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">สกิน</th>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ตำแหน่ง (X, Z)</th>
                        <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">คะแนน</th>
                        <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">ความยาว</th>
                        <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-semibold">สถานะ</th>
                    </tr>
                </thead>
                <tbody id="sync-players-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-if="syncPlayers.length > 0">
                        <template x-for="sp in syncPlayers" :key="sp.player_id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono text-xs" x-text="(sp.player_id || '').substring(0, 20)"></td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" x-text="sp.player_name || 'Player'"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300" x-text="sp.skin || 'classic'"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono text-xs" x-text="Math.round(sp.position?.x || 0) + ', ' + Math.round(sp.position?.z || 0)"></td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white" x-text="sp.score || 0"></td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300" x-text="sp.length || 5"></td>
                                <td class="px-4 py-3 text-center">
                                    <span x-show="sp.is_alive !== false" class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">🟢 มีชีวิต</span>
                                    <span x-show="sp.is_alive === false" class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">💀 ตาย</span>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <template x-if="syncPlayers.length === 0">
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                                    <span class="text-4xl">🎮</span>
                                    <p class="font-medium">ยังไม่มีผู้เล่นที่กำลังเล่นอยู่</p>
                                    <p class="text-xs">เมื่อมีคนเข้าเล่น จะแสดงข้อมูลที่นี่</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════ ผู้เล่นออนไลน์ + ห้อง (2 คอลัมน์) ═══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ผู้เล่นออนไลน์ --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    👥 ผู้เล่นออนไลน์ (Connected)
                </h3>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ชื่อผู้เล่น</th>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">IP</th>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ห้อง</th>
                            <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">คะแนน</th>
                            <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-semibold">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="players-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($onlinePlayers as $player)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $player['username'] }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $player['ip_address'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $player['room_id'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ $player['score'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <button @click="kickPlayer({{ $player['user_id'] }})"
                                        class="px-3 py-1 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 rounded-lg text-xs font-medium transition">
                                    ⛔ Kick
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                                <span class="text-2xl block mb-1">😴</span>
                                ไม่มีผู้เล่นออนไลน์
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ห้องที่เปิดอยู่ --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    🚪 ห้องที่เปิดอยู่
                </h3>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">Room ID</th>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ชื่อห้อง</th>
                            <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-semibold">ผู้เล่น</th>
                            <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">สร้างเมื่อ</th>
                        </tr>
                    </thead>
                    <tbody id="rooms-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($rooms as $room)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ Str::limit($room['room_id'], 15) }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $room['name'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    {{ $room['current_players'] }}/{{ $room['max_players'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 text-xs">{{ \Carbon\Carbon::parse($room['created_at'])->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                                <span class="text-2xl block mb-1">🏠</span>
                                ยังไม่มีห้องที่เปิดอยู่
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════ Leaderboard + Suspicious (2 คอลัมน์) ═══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- 🏆 Top Scores (Leaderboard) --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    🏆 กระดานผู้นำ (Leaderboard)
                </h3>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-semibold w-12">#</th>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ผู้เล่น</th>
                            <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">คะแนน</th>
                            <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">เวลาเล่น</th>
                            <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">วันที่</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($topScores as $index => $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ $index < 3 ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : '' }}">
                            <td class="px-4 py-3 text-center">
                                @if($index === 0)
                                    <span class="text-xl">🥇</span>
                                @elseif($index === 1)
                                    <span class="text-xl">🥈</span>
                                @elseif($index === 2)
                                    <span class="text-xl">🥉</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400 font-bold">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $entry->user->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-amber-600 dark:text-amber-400">{{ number_format($entry->score) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                @if($entry->playtime_seconds >= 60)
                                    {{ floor($entry->playtime_seconds / 60) }} นาที {{ $entry->playtime_seconds % 60 }} วิ
                                @else
                                    {{ $entry->playtime_seconds }} วินาที
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 text-xs">{{ $entry->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                                <span class="text-2xl block mb-1">🏆</span>
                                ยังไม่มีคะแนนในกระดานผู้นำ
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ⚠️ Suspicious Activities --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    ⚠️ กิจกรรมน่าสงสัย
                </h3>
                <button @click="clearSuspicious()"
                        class="px-3 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 rounded-lg text-xs font-medium transition">
                    🗑️ ล้างทั้งหมด
                </button>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">User ID</th>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ประเภท</th>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">รายละเอียด</th>
                            <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">เวลา</th>
                        </tr>
                    </thead>
                    <tbody id="suspicious-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($suspiciousActivities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono text-xs">{{ $activity['user_id'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                    {{ $activity['type'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs max-w-xs truncate">
                                {{ json_encode($activity['data']) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 text-xs">{{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                                <span class="text-2xl block mb-1">✅</span>
                                ไม่พบกิจกรรมน่าสงสัย
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ Alpine.js Component ═══════════ --}}
<script>
function snakeMonitor() {
    return {
        stats: {
            totalPlayers: {{ $status['total_players'] }},
            totalRooms: {{ $status['total_rooms'] }},
            suspicious: {{ count($suspiciousActivities) }},
        },
        syncPlayers: @json($syncPlayers ?? []),
        refreshInterval: null,

        init() {
            this.startAutoRefresh();
        },

        destroy() {
            this.stopAutoRefresh();
        },

        startAutoRefresh() {
            this.refreshInterval = setInterval(() => this.refreshData(), 5000);
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        },

        async refreshData() {
            try {
                const response = await fetch('/api/admin/games/snake-io/status', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();

                if (data.success) {
                    // อัปเดต stats
                    this.stats.totalPlayers = data.status?.total_players ?? 0;
                    this.stats.totalRooms = data.status?.total_rooms ?? 0;

                    // อัปเดต sync players
                    if (data.sync_players) {
                        this.syncPlayers = data.sync_players;
                    }

                    // อัปเดต status badge
                    this.updateStatusBadge(data.status?.is_online);

                    // อัปเดตตาราง HTML (players + rooms)
                    if (data.players) this.updatePlayersTable(data.players);
                    if (data.rooms) this.updateRoomsTable(data.rooms);
                }
            } catch (error) {
                console.error('Failed to refresh data:', error);
            }
        },

        updateStatusBadge(isOnline) {
            const badge = document.getElementById('service-status-badge');
            const statusText = document.getElementById('status-text');
            if (!badge || !statusText) return;

            if (isOnline) {
                badge.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg bg-green-400/90 text-green-900';
                statusText.textContent = 'ONLINE';
            } else {
                badge.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg bg-gray-400/90 text-gray-800';
                statusText.textContent = 'OFFLINE';
            }
        },

        updatePlayersTable(players) {
            const tbody = document.getElementById('players-body');
            if (!tbody) return;

            if (!players || players.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500"><span class="text-2xl block mb-1">😴</span>ไม่มีผู้เล่นออนไลน์</td></tr>';
                return;
            }

            tbody.innerHTML = players.map(p => `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">${p.username}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">${p.ip_address}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">${p.room_id || '-'}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">${p.score}</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="document.querySelector('[x-data]').__x.$data.kickPlayer(${p.user_id})"
                                class="px-3 py-1 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg text-xs font-medium transition">
                            ⛔ Kick
                        </button>
                    </td>
                </tr>
            `).join('');
        },

        updateRoomsTable(rooms) {
            const tbody = document.getElementById('rooms-body');
            if (!tbody) return;

            if (!rooms || rooms.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500"><span class="text-2xl block mb-1">🏠</span>ยังไม่มีห้องที่เปิดอยู่</td></tr>';
                return;
            }

            tbody.innerHTML = rooms.map(r => `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">${(r.room_id || '').substring(0, 15)}</td>
                    <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">${r.name}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">${r.current_players}/${r.max_players}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 text-xs">${this.timeAgo(r.created_at)}</td>
                </tr>
            `).join('');
        },

        timeAgo(timestamp) {
            const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
            if (seconds < 60) return seconds + ' วินาทีที่แล้ว';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' นาทีที่แล้ว';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' ชม.ที่แล้ว';
            return Math.floor(seconds / 86400) + ' วันที่แล้ว';
        },

        async startService() {
            if (!confirm('เริ่ม Multiplayer Service?')) return;
            try {
                const response = await fetch('/api/admin/games/snake-io/start', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'Unknown'));
                }
            } catch (error) {
                alert('ไม่สามารถเริ่ม Service: ' + error.message);
            }
        },

        async stopService() {
            if (!confirm('หยุด Multiplayer Service? ผู้เล่นจะเปลี่ยนเป็นโหมดออฟไลน์')) return;
            try {
                const response = await fetch('/api/admin/games/snake-io/stop', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'Unknown'));
                }
            } catch (error) {
                alert('ไม่สามารถหยุด Service: ' + error.message);
            }
        },

        async kickPlayer(userId) {
            if (!confirm(`Kick ผู้เล่น #${userId}?`)) return;
            try {
                const response = await fetch(`/api/admin/games/snake-io/kick/${userId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.refreshData();
                }
            } catch (error) {
                alert('ไม่สามารถ Kick ผู้เล่น: ' + error.message);
            }
        },

        async clearSuspicious() {
            if (!confirm('ล้างกิจกรรมน่าสงสัยทั้งหมด?')) return;
            try {
                const response = await fetch('/api/admin/games/snake-io/clear-data', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.stats.suspicious = 0;
                    location.reload();
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        },
    };
}
</script>
@endsection
