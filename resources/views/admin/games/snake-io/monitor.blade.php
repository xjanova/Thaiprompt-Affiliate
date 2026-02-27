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
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg"
                      :class="serviceOnline ? 'bg-green-400/90 text-green-900' : 'bg-gray-400/90 text-gray-800'">
                    <span class="relative flex h-3 w-3">
                        <span x-show="serviceOnline" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-600 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3" :class="serviceOnline ? 'bg-green-700' : 'bg-gray-600'"></span>
                    </span>
                    <span x-text="serviceOnline ? 'ONLINE' : 'OFFLINE'"></span>
                </span>

                {{-- ปุ่มตั้งค่าเกม --}}
                <a href="{{ route('admin.games.game-settings.index') }}"
                   class="px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl font-semibold text-sm shadow-lg transition-all duration-200 hover:scale-105 border border-white/30">
                    ⚙️ ตั้งค่าเกม
                </a>

                {{-- ปุ่ม Start/Stop --}}
                <button x-show="serviceOnline" @click="stopService()"
                        class="px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold text-sm shadow-lg transition-all duration-200 hover:scale-105">
                    ⏹️ หยุด Service
                </button>
                <button x-show="!serviceOnline" @click="startService()"
                        class="px-5 py-2.5 bg-emerald-400 hover:bg-emerald-500 text-emerald-900 rounded-xl font-semibold text-sm shadow-lg transition-all duration-200 hover:scale-105">
                    ▶️ เริ่ม Service
                </button>

                {{-- Refresh indicator --}}
                <span class="text-xs text-white/60" x-show="!isTabActive">💤 พัก (tab ไม่ active)</span>
            </div>
        </div>
    </div>

    {{-- ═══════════ Server Limits & Stats Cards ═══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- ผู้เล่นออนไลน์ + limit --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl shadow-lg">
                    👥
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">ผู้เล่นออนไลน์</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span x-text="stats.totalPlayers">0</span>
                        <span class="text-sm font-normal text-gray-400" x-show="limits.maxTotalPlayers > 0">/ <span x-text="limits.maxTotalPlayers">200</span></span>
                    </p>
                </div>
            </div>
            {{-- Progress bar --}}
            <div x-show="limits.maxTotalPlayers > 0" class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="h-2 rounded-full transition-all duration-500"
                     :class="playerUsagePercent > 80 ? 'bg-red-500' : (playerUsagePercent > 50 ? 'bg-amber-500' : 'bg-blue-500')"
                     :style="'width: ' + Math.min(playerUsagePercent, 100) + '%'"></div>
            </div>
        </div>

        {{-- ห้องที่เปิดอยู่ + limit --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white text-xl shadow-lg">
                    🚪
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">ห้องที่เปิดอยู่</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span x-text="stats.totalRooms">0</span>
                        <span class="text-sm font-normal text-gray-400" x-show="limits.maxRooms > 0">/ <span x-text="limits.maxRooms">20</span></span>
                    </p>
                </div>
            </div>
            {{-- Progress bar --}}
            <div x-show="limits.maxRooms > 0" class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="h-2 rounded-full transition-all duration-500"
                     :class="roomUsagePercent > 80 ? 'bg-red-500' : (roomUsagePercent > 50 ? 'bg-amber-500' : 'bg-green-500')"
                     :style="'width: ' + Math.min(roomUsagePercent, 100) + '%'"></div>
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
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.suspicious">{{ count($suspiciousActivities) }}</p>
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

    {{-- ═══════════ ห้องที่เปิดอยู่ (จาก SyncService) ═══════════ --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                🚪 ห้องที่เปิดอยู่
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300"
                      x-text="rooms.length">0</span>
                <span class="text-xs font-normal text-gray-400" x-show="limits.maxPlayersPerRoom > 0">(สูงสุด <span x-text="limits.maxPlayersPerRoom"></span> คน/ห้อง)</span>
            </h3>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="inline-flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                อัปเดตทุก <span x-text="isTabActive ? '5' : '30'"></span> วินาที
            </div>
        </div>
        <div class="overflow-x-auto">
            <template x-if="rooms.length > 0">
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="room in rooms" :key="room.room_id">
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg">🏠</span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white font-mono" x-text="room.room_id"></p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold"
                                      :class="room.player_count >= limits.maxPlayersPerRoom ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'">
                                    <span x-text="room.player_count"></span>/<span x-text="limits.maxPlayersPerRoom || '∞'"></span> คน
                                </span>
                            </div>
                            {{-- รายชื่อผู้เล่นในห้อง --}}
                            <div class="flex flex-wrap gap-2 ml-8">
                                <template x-for="p in room.players" :key="p.player_id">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs"
                                          :class="p.is_alive !== false ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'">
                                        <span x-text="p.is_alive !== false ? '🟢' : '💀'"></span>
                                        <span x-text="p.player_name" class="font-medium"></span>
                                        <span class="text-gray-400">—</span>
                                        <span x-text="p.score" class="font-bold"></span>pts
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="rooms.length === 0">
                <div class="px-4 py-12 text-center">
                    <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                        <span class="text-4xl">🏠</span>
                        <p class="font-medium">ยังไม่มีห้องที่เปิดอยู่</p>
                        <p class="text-xs">เมื่อมีคนเข้าเล่น จะสร้างห้องอัตโนมัติ</p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ═══════════ ผู้เล่นที่กำลังเล่น (Real-time + Pagination) ═══════════ --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                🎮 ผู้เล่นทั้งหมด
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300"
                      x-text="syncPlayers.length">0</span>
            </h3>
            <div class="flex items-center gap-3">
                {{-- Pagination info --}}
                <span class="text-xs text-gray-500 dark:text-gray-400" x-show="totalPlayerPages > 1">
                    หน้า <span x-text="playerPage"></span> / <span x-text="totalPlayerPages"></span>
                </span>
                {{-- Pagination buttons --}}
                <div class="flex items-center gap-1" x-show="totalPlayerPages > 1">
                    <button @click="playerPage = Math.max(1, playerPage - 1)"
                            :disabled="playerPage <= 1"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 disabled:opacity-30 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        ◀ ก่อน
                    </button>
                    <button @click="playerPage = Math.min(totalPlayerPages, playerPage + 1)"
                            :disabled="playerPage >= totalPlayerPages"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 disabled:opacity-30 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        ถัดไป ▶
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ชื่อ</th>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ห้อง</th>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">สกิน</th>
                        <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-semibold">ตำแหน่ง</th>
                        <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">คะแนน</th>
                        <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-semibold">ความยาว</th>
                        <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-semibold">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-if="paginatedPlayers.length > 0">
                        <template x-for="sp in paginatedPlayers" :key="sp.player_id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" x-text="sp.player_name || 'Player'"></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs" x-text="sp.room_id || 'default'"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs" x-text="sp.skin || 'classic'"></td>
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
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
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
        // ข้อมูล stats
        stats: {
            totalPlayers: {{ $status['total_players'] ?? 0 }},
            totalRooms: {{ $status['total_rooms'] ?? 0 }},
            suspicious: {{ count($suspiciousActivities) }},
        },

        // ข้อมูลจาก SyncService
        syncPlayers: @json($syncPlayers ?? []),
        rooms: @json($syncRooms ?? []),

        // Server limits (camelCase สำหรับ JS)
        limits: {
            maxRooms: {{ $limits['max_rooms'] ?? 20 }},
            maxTotalPlayers: {{ $limits['max_total_players'] ?? 200 }},
            maxPlayersPerRoom: {{ $limits['max_players_per_room'] ?? 30 }},
        },

        // Service status
        serviceOnline: {{ ($status['is_online'] ?? false) ? 'true' : 'false' }},

        // Pagination
        playerPage: 1,
        playersPerPage: 20,

        // Auto-refresh
        refreshInterval: null,
        isTabActive: true,
        refreshActiveMs: 5000,   // 5 วินาทีเมื่อ tab active
        refreshInactiveMs: 30000, // 30 วินาทีเมื่อ tab ไม่ active

        // Computed: ผู้เล่นที่แสดงในหน้าปัจจุบัน
        get paginatedPlayers() {
            const start = (this.playerPage - 1) * this.playersPerPage;
            return this.syncPlayers.slice(start, start + this.playersPerPage);
        },

        // Computed: จำนวนหน้า
        get totalPlayerPages() {
            return Math.max(1, Math.ceil(this.syncPlayers.length / this.playersPerPage));
        },

        // Computed: % การใช้งานผู้เล่น
        get playerUsagePercent() {
            if (!this.limits.maxTotalPlayers || this.limits.maxTotalPlayers <= 0) return 0;
            return Math.round((this.stats.totalPlayers / this.limits.maxTotalPlayers) * 100);
        },

        // Computed: % การใช้งานห้อง
        get roomUsagePercent() {
            if (!this.limits.maxRooms || this.limits.maxRooms <= 0) return 0;
            return Math.round((this.stats.totalRooms / this.limits.maxRooms) * 100);
        },

        init() {
            this.startAutoRefresh();
            this.setupVisibilityListener();
        },

        destroy() {
            this.stopAutoRefresh();
        },

        /**
         * ตั้งค่า Visibility API — ลด refresh เมื่อ tab ไม่ active
         */
        setupVisibilityListener() {
            document.addEventListener('visibilitychange', () => {
                this.isTabActive = !document.hidden;
                // รีสตาร์ท interval ด้วยความถี่ใหม่
                this.stopAutoRefresh();
                this.startAutoRefresh();
            });
        },

        startAutoRefresh() {
            const interval = this.isTabActive ? this.refreshActiveMs : this.refreshInactiveMs;
            this.refreshInterval = setInterval(() => this.refreshData(), interval);
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },

        /**
         * ดึงข้อมูลใหม่จาก API (ไม่ใช้ innerHTML — ใช้ reactive data)
         */
        async refreshData() {
            try {
                const response = await fetch('/api/admin/games/snake-io/status', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) return;
                const data = await response.json();

                if (data.success) {
                    // อัปเดต stats (reactive — Alpine.js จะอัปเดต DOM อัตโนมัติ)
                    this.stats.totalPlayers = data.status?.total_players ?? 0;
                    this.stats.totalRooms = data.status?.total_rooms ?? 0;

                    // อัปเดต sync players
                    if (data.sync_players) {
                        this.syncPlayers = data.sync_players;
                    }

                    // อัปเดต rooms
                    if (data.rooms) {
                        this.rooms = data.rooms;
                    }

                    // อัปเดต limits
                    if (data.limits) {
                        this.limits = {
                            maxRooms: data.limits.max_rooms ?? 20,
                            maxTotalPlayers: data.limits.max_total_players ?? 200,
                            maxPlayersPerRoom: data.limits.max_players_per_room ?? 30,
                        };
                    }

                    // อัปเดต service status
                    this.serviceOnline = data.status?.is_online ?? false;

                    // ป้องกัน playerPage เกินจำนวนจริง
                    if (this.playerPage > this.totalPlayerPages) {
                        this.playerPage = Math.max(1, this.totalPlayerPages);
                    }
                }
            } catch (error) {
                console.warn('[Monitor] ดึงข้อมูลล้มเหลว:', error.message);
            }
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
                    this.serviceOnline = true;
                    this.refreshData();
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
                    this.serviceOnline = false;
                    this.refreshData();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'Unknown'));
                }
            } catch (error) {
                alert('ไม่สามารถหยุด Service: ' + error.message);
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
