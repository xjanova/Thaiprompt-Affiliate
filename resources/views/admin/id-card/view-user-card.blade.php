@extends('layouts.admin-v3')

@section('title', 'บัตรของ ' . $user->name)

@section('content')
{{--
/**
 * Admin View User ID Card
 *
 * หน้าดูบัตรประจำตัวของสมาชิกที่ระบุ
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

@php
    $rankLevel = $currentRank?->level ?? 1;
    $rankName = $currentRank?->name ?? 'Bronze';
    $rankNameTh = $currentRank?->name_th ?? 'สำริด';
    $rankColor = $currentRank?->color ?? '#CD7F32';
    $rankBadge = $currentRank?->badge_icon ?? '🥉';
    $rankStars = $currentRank?->stars ?? 1;
@endphp

<div class="container mx-auto px-4 py-8 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ url()->previous() }}"
               class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <i class="fas fa-id-card text-purple-600"></i>
                    บัตรประจำตัวของ {{ $user->name }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    ระดับ: {{ $rankNameTh }} (Level {{ $rankLevel }})
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.id-card.designer', ['rank_level' => $rankLevel]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-paint-brush"></i>
                แก้ไขดีไซน์
            </a>
        </div>
    </div>

    {{-- User Info Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <div class="flex items-center gap-6">
            {{-- ใช้ component เพื่อความสอดคล้องทั้งระบบ --}}
            <x-user-avatar :user="$user" size="xl" :ring="false" class="shadow-lg" />
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                <p class="text-gray-500">{{ $user->email }}</p>
                <div class="flex items-center gap-4 mt-2 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">
                        <i class="fas fa-id-badge mr-1"></i>
                        {{ $user->member_number ?? 'TP' . str_pad($user->id, 6, '0', STR_PAD_LEFT) }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        <i class="fas fa-star mr-1 text-yellow-400"></i>
                        {{ number_format($user->rank_points ?? 0) }} คะแนน
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        <i class="fas fa-calendar mr-1"></i>
                        สมาชิกตั้งแต่ {{ $user->created_at->locale('th')->translatedFormat('d M Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ID Card Display --}}
    <div class="flex justify-center">
        <div class="relative" id="id-card-container">
            {{-- ID Card --}}
            <div id="virtual-id-card" class="relative w-[450px] h-[280px] rounded-2xl overflow-hidden shadow-2xl transform transition-all duration-500 hover:scale-[1.02]
                {{ $rankLevel >= 8 ? 'animate-glow-legend' : '' }}
                {{ $rankLevel >= 6 ? 'ring-4 ring-yellow-400/50' : '' }}">

                {{-- Background ตามระดับ Rank --}}
                @include('user.partials.id-card-background', ['rankLevel' => $rankLevel, 'rankColor' => $rankColor])

                {{-- Card Content --}}
                <div class="absolute inset-0 p-6 flex flex-col justify-between z-10">
                    {{-- Header Section --}}
                    <div class="flex justify-between items-start">
                        {{-- Logo & Title --}}
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center shadow-lg">
                                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-8 h-8 object-contain"
                                     onerror="this.src='https://ui-avatars.com/api/?name=TP&background=8B5CF6&color=fff&bold=true'">
                            </div>
                            <div>
                                <div class="text-white/80 text-xs font-medium tracking-wider uppercase">Member ID Card</div>
                                <div class="text-white text-lg font-bold tracking-wide">TP Affiliate</div>
                            </div>
                        </div>

                        {{-- Rank Badge --}}
                        <div class="text-center">
                            <div class="text-4xl mb-1 {{ $rankLevel >= 5 ? 'animate-bounce-slow' : '' }}">
                                {{ $rankBadge }}
                            </div>
                            <div class="text-white text-xs font-bold tracking-wider uppercase
                                {{ $rankLevel >= 8 ? 'animate-pulse' : '' }}">
                                {{ $rankName }}
                            </div>
                        </div>
                    </div>

                    {{-- Member Info --}}
                    <div class="flex items-end justify-between">
                        {{-- Profile & Name --}}
                        <div class="flex items-center gap-4">
                            {{-- Profile Picture with rank border --}}
                            <div class="relative">
                                <div class="w-20 h-20 rounded-xl overflow-hidden shadow-xl
                                    {{ $rankLevel >= 8 ? 'ring-4 ring-yellow-400 animate-pulse-slow' : '' }}
                                    {{ $rankLevel >= 6 && $rankLevel < 8 ? 'ring-3 ring-white/50' : '' }}
                                    {{ $rankLevel >= 4 && $rankLevel < 6 ? 'ring-2 ring-white/30' : '' }}">
                                    {{-- ใช้ profile_picture_url accessor พร้อม onerror fallback --}}
                                    <img src="{{ $user->profile_picture_url }}"
                                         alt="{{ $user->name }}"
                                         class="w-full h-full object-cover"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">
                                </div>
                                {{-- Stars badge --}}
                                <div class="absolute -bottom-2 -right-2 bg-black/40 backdrop-blur-md rounded-full px-2 py-0.5">
                                    <div class="flex text-yellow-400 text-xs">
                                        @for($i = 0; $i < min($rankStars, 5); $i++)
                                            <i class="fas fa-star"></i>
                                        @endfor
                                        @if($rankStars > 5)
                                            <span class="ml-1 text-white">+{{ $rankStars - 5 }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Name & Details --}}
                            <div class="space-y-1">
                                <div class="text-white font-bold text-xl tracking-wide drop-shadow-lg">
                                    {{ Str::limit($user->name, 20) }}
                                </div>
                                <div class="text-white/70 text-sm font-medium">
                                    <i class="fas fa-award mr-1"></i>
                                    ระดับ {{ $rankNameTh }}
                                </div>
                                @if($user->member_number)
                                <div class="text-white/60 text-xs font-mono tracking-widest">
                                    ID: {{ $user->member_number }}
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- QR Code & Member Since --}}
                        <div class="text-right">
                            <div class="bg-white rounded-lg p-1.5 shadow-lg inline-block mb-2">
                                <div class="w-14 h-14 bg-gray-100 flex items-center justify-center text-xs text-gray-400">
                                    <i class="fas fa-qrcode text-2xl text-gray-400"></i>
                                </div>
                            </div>
                            <div class="text-white/60 text-xs">
                                เป็นสมาชิกตั้งแต่
                            </div>
                            <div class="text-white/90 text-sm font-semibold">
                                {{ $user->created_at->locale('th')->translatedFormat('M Y') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Decorative Elements ตามระดับ --}}
                @include('user.partials.id-card-decorations', ['rankLevel' => $rankLevel])

                {{-- Holographic Overlay (Platinum ขึ้นไป) --}}
                @if($rankLevel >= 4)
                <div class="absolute inset-0 pointer-events-none z-20 opacity-30
                    {{ $rankLevel >= 6 ? 'animate-holographic' : 'animate-shimmer-slow' }}">
                    <div class="absolute inset-0 bg-gradient-to-br from-transparent via-white/20 to-transparent
                        transform -translate-x-full animate-shimmer"></div>
                </div>
                @endif
            </div>

            {{-- Card Shadow --}}
            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-[90%] h-8 bg-black/20 dark:bg-black/40 blur-xl rounded-full
                {{ $rankLevel >= 6 ? 'w-[95%] h-10' : '' }}
                {{ $rankLevel >= 8 ? 'w-full h-12 animate-pulse-slow' : '' }}"></div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
            <div class="text-5xl mb-3">{{ $rankBadge }}</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $rankNameTh }}</h3>
            <p class="text-sm text-gray-500">ระดับปัจจุบัน</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
            <div class="text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                {{ number_format($user->rank_points ?? 0) }}
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">คะแนนสะสม</h3>
            <p class="text-sm text-gray-500">Rank Points</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
            <div class="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">
                {{ $user->created_at->diffInDays(now()) }}
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">วันที่เป็นสมาชิก</h3>
            <p class="text-sm text-gray-500">ตั้งแต่ {{ $user->created_at->locale('th')->translatedFormat('d M Y') }}</p>
        </div>
    </div>
</div>

<style>
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes glow-legend {
        0%, 100% { box-shadow: 0 0 30px rgba(236, 72, 153, 0.5), 0 0 60px rgba(139, 92, 246, 0.3); }
        50% { box-shadow: 0 0 50px rgba(236, 72, 153, 0.7), 0 0 100px rgba(139, 92, 246, 0.5); }
    }

    .animate-shimmer {
        animation: shimmer 3s infinite;
    }

    .animate-shimmer-slow {
        animation: shimmer 5s infinite;
    }

    .animate-glow-legend {
        animation: glow-legend 3s ease-in-out infinite;
    }

    .animate-bounce-slow {
        animation: float 3s ease-in-out infinite;
    }

    .animate-pulse-slow {
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
</style>
@endsection
