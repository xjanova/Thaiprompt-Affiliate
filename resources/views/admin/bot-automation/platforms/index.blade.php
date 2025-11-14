@extends('layouts.admin')

@section('title', 'แพลตฟอร์มบอท')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="absolute top-0 right-0 z-10 mt-4 mr-4">
        <div class="relative inline-block" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span x-text="language === 'th' ? 'ไทย' : language === 'en' ? 'English' : language === 'zh' ? '中文' : '日本語'" class="text-sm font-medium text-gray-700 dark:text-gray-300"></span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-50"
                style="display: none;"
            >
                <button @click="language = 'th'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇹🇭</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">ไทย (Thai)</span>
                </button>
                <button @click="language = 'en'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">English</span>
                </button>
                <button @click="language = 'zh'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇨🇳</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">中文 (Chinese)</span>
                </button>
                <button @click="language = 'ja'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇯🇵</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">日本語 (Japanese)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>แพลตฟอร์มบอท</h1>
            <a href="{{ route('admin.bot-automation.platforms.connect') }}"
               class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-700 to-slate-700 hover:from-gray-800 hover:to-slate-800 text-white rounded-lg transition shadow-md hover:shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span data-translate>เชื่อมต่อแพลตฟอร์มใหม่</span>
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Platforms -->
            <div class="relative overflow-hidden rounded-xl shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-500 to-slate-500"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm opacity-90 font-medium mb-1" data-translate>แพลตฟอร์มทั้งหมด</p>
                            <p class="text-3xl font-bold">{{ $totalPlatforms ?? '0' }}</p>
                        </div>
                        <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center text-sm opacity-90">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                        </svg>
                        <span data-translate>จำนวนแพลตฟอร์มที่เชื่อมต่อ</span>
                    </div>
                </div>
            </div>

            <!-- Connected -->
            <div class="relative overflow-hidden rounded-xl shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-500 to-gray-500"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm opacity-90 font-medium mb-1" data-translate>เชื่อมต่อแล้ว</p>
                            <p class="text-3xl font-bold">{{ $connectedPlatforms ?? '0' }}</p>
                        </div>
                        <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center text-sm opacity-90">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span data-translate>แพลตฟอร์มที่พร้อมใช้งาน</span>
                    </div>
                </div>
            </div>

            <!-- Pending -->
            <div class="relative overflow-hidden rounded-xl shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-600 to-slate-400"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm opacity-90 font-medium mb-1" data-translate>รอดำเนินการ</p>
                            <p class="text-3xl font-bold">{{ $pendingPlatforms ?? '0' }}</p>
                        </div>
                        <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center text-sm opacity-90">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        <span data-translate>รอการตั้งค่า</span>
                    </div>
                </div>
            </div>

            <!-- Total Messages -->
            <div class="relative overflow-hidden rounded-xl shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-600 to-gray-400"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm opacity-90 font-medium mb-1" data-translate>ข้อความทั้งหมด</p>
                            <p class="text-3xl font-bold">{{ number_format($totalMessages ?? 0) }}</p>
                        </div>
                        <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center text-sm opacity-90">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path>
                        </svg>
                        <span data-translate>จากทุกแพลตฟอร์ม</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connected Platforms -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>แพลตฟอร์มที่เชื่อมต่อ</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($platforms ?? [] as $platform)
                    <div class="bg-white dark:bg-gray-800 border-l-4 {{ $platform->status == 'connected' ? 'border-green-500' : 'border-gray-400 dark:border-gray-600' }} rounded-lg shadow-md hover:shadow-xl transition">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <i class="fab fa-{{ strtolower($platform->name ?? '') }} text-2xl
                                            @if(strtolower($platform->name ?? '') === 'facebook') text-blue-600
                                            @elseif(strtolower($platform->name ?? '') === 'line') text-green-600
                                            @elseif(strtolower($platform->name ?? '') === 'telegram') text-cyan-600
                                            @elseif(strtolower($platform->name ?? '') === 'slack') text-purple-600
                                            @elseif(strtolower($platform->name ?? '') === 'whatsapp') text-green-600
                                            @elseif(strtolower($platform->name ?? '') === 'discord') text-indigo-600
                                            @else text-gray-600 dark:text-gray-400
                                            @endif
                                        "></i>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $platform->name ?? 'N/A' }}</h3>
                                    </div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                        @if($platform->status == 'connected')
                                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @else
                                            bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif
                                    ">
                                        <span data-translate>{{ ucfirst($platform->status ?? 'N/A') }}</span>
                                    </span>
                                </div>
                                <div class="relative" x-data="{ dropdownOpen: false }">
                                    <button @click="dropdownOpen = !dropdownOpen"
                                            class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>
                                    <div x-show="dropdownOpen"
                                         @click.away="dropdownOpen = false"
                                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 z-50"
                                         style="display: none;">
                                        <a href="#" onclick="editPlatform({{ $platform->id }})"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span data-translate>แก้ไข</span>
                                        </a>
                                        <a href="#" onclick="viewSettings({{ $platform->id }})"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <span data-translate>ตั้งค่า</span>
                                        </a>
                                        <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                        @if($platform->status == 'connected')
                                        <a href="#" onclick="disconnectPlatform({{ $platform->id }})"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-amber-600 dark:text-amber-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                            </svg>
                                            <span data-translate>ยกเลิกการเชื่อมต่อ</span>
                                        </a>
                                        @else
                                        <a href="#" onclick="connectPlatform({{ $platform->id }})"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            <span data-translate>เชื่อมต่อ</span>
                                        </a>
                                        @endif
                                        <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                        <a href="#" onclick="deletePlatform({{ $platform->id }})"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span data-translate>ลบ</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                {{ $platform->description ?? 'ไม่มีคำอธิบาย' }}
                            </p>

                            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1" data-translate>บอท</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $platform->bots_count ?? '0' }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1" data-translate>ข้อความ</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($platform->messages_count ?? 0) }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1" data-translate>ผู้ใช้</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $platform->users_count ?? '0' }}</p>
                                </div>
                            </div>

                            @if(isset($platform->last_sync))
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span data-translate>ซิงค์ล่าสุด</span>: {{ $platform->last_sync->diffForHumans() }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-4" data-translate>ยังไม่มีแพลตฟอร์มที่เชื่อมต่อ</p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mb-6" data-translate>เชื่อมต่อแพลตฟอร์มแรกของคุณเพื่อเริ่มต้นใช้งาน</p>
                        <a href="{{ route('admin.bot-automation.platforms.connect') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-700 to-slate-700 hover:from-gray-800 hover:to-slate-800 text-white rounded-lg transition shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span data-translate>เชื่อมต่อแพลตฟอร์ม</span>
                        </a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Available Platforms -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>แพลตฟอร์มที่พร้อมใช้งาน</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Facebook Messenger -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 text-center hover:shadow-lg transition">
                        <i class="fab fa-facebook-messenger text-5xl text-blue-600 mb-3"></i>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>Facebook Messenger</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400" data-translate>แพลตฟอร์มแชท</p>
                    </div>

                    <!-- LINE -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 text-center hover:shadow-lg transition">
                        <i class="fab fa-line text-5xl text-green-600 mb-3"></i>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>LINE</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400" data-translate>แอปส่งข้อความ</p>
                    </div>

                    <!-- Telegram -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 text-center hover:shadow-lg transition">
                        <i class="fab fa-telegram text-5xl text-cyan-600 mb-3"></i>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>Telegram</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400" data-translate>แอปส่งข้อความ</p>
                    </div>

                    <!-- Slack -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 text-center hover:shadow-lg transition">
                        <i class="fab fa-slack text-5xl text-purple-600 mb-3"></i>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>Slack</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400" data-translate>การสื่อสารทีม</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Translate Script -->
<script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            includedLanguages: 'th,en,zh-CN,ja',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<div id="google_translate_element" style="display: none;"></div>

<script>
// ฟังก์ชันแปลภาษา
document.addEventListener('alpine:init', () => {
    Alpine.data('translationController', () => ({
        language: 'th',
        init() {
            this.$watch('language', value => this.translatePage(value));
        },
        translatePage(targetLang) {
            const elements = document.querySelectorAll('[data-translate]');

            if (targetLang === 'th') {
                // รีเซ็ตกลับเป็นภาษาไทย
                location.reload();
                return;
            }

            elements.forEach(element => {
                const textToTranslate = element.textContent.trim();
                if (!textToTranslate) return;

                // เก็บข้อความต้นฉบับ
                if (!element.dataset.originalText) {
                    element.dataset.originalText = textToTranslate;
                }

                // แปลภาษา
                this.translateText(textToTranslate, 'th', targetLang)
                    .then(translatedText => {
                        element.textContent = translatedText;
                    })
                    .catch(error => {
                        console.error('Translation error:', error);
                    });
            });
        },
        async translateText(text, sourceLang, targetLang) {
            try {
                const response = await fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=${sourceLang}&tl=${targetLang}&dt=t&q=${encodeURIComponent(text)}`);
                const data = await response.json();
                return data[0][0][0];
            } catch (error) {
                console.error('Translation API error:', error);
                return text;
            }
        }
    }));
});

// ฟังก์ชันจัดการแพลตฟอร์ม
function editPlatform(id) {
    // ไปหน้าแก้ไขการตั้งค่า
    window.location.href = `/admin/bot-automation/platforms/${id}/edit`;
}

function viewSettings(id) {
    // ไปหน้าดูรายละเอียดและการตั้งค่า
    window.location.href = `/admin/bot-automation/platforms/${id}`;
}

function connectPlatform(platform) {
    // ไปหน้าเชื่อมต่อแพลตฟอร์ม
    window.location.href = `/admin/bot-automation/platforms/connect/${platform}`;
}

function disconnectPlatform(id) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกการเชื่อมต่อแพลตฟอร์มนี้?')) {
        // สร้าง form และ submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/bot-automation/platforms/${id}`;

        // เพิ่ม CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfInput);

        // เพิ่ม method spoofing สำหรับ DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }
}

function deletePlatform(id) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบแพลตฟอร์มนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        // ใช้ฟังก์ชันเดียวกับ disconnect
        disconnectPlatform(id);
    }
}
</script>
@endsection
