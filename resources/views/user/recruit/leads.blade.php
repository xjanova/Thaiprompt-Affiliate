@extends('layouts.user-arrow-x')

@section('title', 'ผู้มุ่งหวัง (Leads)')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-purple-900 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                        ผู้มุ่งหวัง (Leads)
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        ติดตามและจัดการผู้มุ่งหวังที่เข้าชมหน้า Recruit ของคุณ
                    </p>
                </div>
                <a href="{{ route('user.marketing.recruit.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    กลับไปหน้าจัดการ
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Leads --}}
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-blue-600 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ผู้มุ่งหวังทั้งหมด</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total']) }}</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Leads --}}
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-r from-green-400 to-green-600 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">กำลังติดตาม</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['active']) }}</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-green-400 to-green-600 rounded-xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Converted Leads --}}
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-400 to-purple-600 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">สมัครสมาชิกแล้ว</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['converted']) }}</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Expired Leads --}}
            <div class="relative group">
                <div class="absolute inset-0 bg-gradient-to-r from-gray-400 to-gray-600 rounded-2xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">หมดอายุแล้ว</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['expired']) }}</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-gray-400 to-gray-600 rounded-xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 mb-8 overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex flex-wrap -mb-px">
                    <a href="{{ route('user.marketing.recruit.leads', ['status' => 'all']) }}"
                       class="group inline-flex items-center px-6 py-4 border-b-2 font-medium text-sm transition-all duration-200
                              {{ $currentStatus === 'all' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        ทั้งหมด ({{ number_format($stats['total']) }})
                    </a>
                    <a href="{{ route('user.marketing.recruit.leads', ['status' => 'active']) }}"
                       class="group inline-flex items-center px-6 py-4 border-b-2 font-medium text-sm transition-all duration-200
                              {{ $currentStatus === 'active' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        กำลังติดตาม ({{ number_format($stats['active']) }})
                    </a>
                    <a href="{{ route('user.marketing.recruit.leads', ['status' => 'converted']) }}"
                       class="group inline-flex items-center px-6 py-4 border-b-2 font-medium text-sm transition-all duration-200
                              {{ $currentStatus === 'converted' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        สมัครแล้ว ({{ number_format($stats['converted']) }})
                    </a>
                    <a href="{{ route('user.marketing.recruit.leads', ['status' => 'expired']) }}"
                       class="group inline-flex items-center px-6 py-4 border-b-2 font-medium text-sm transition-all duration-200
                              {{ $currentStatus === 'expired' ? 'border-gray-500 text-gray-600 dark:text-gray-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        หมดอายุ ({{ number_format($stats['expired']) }})
                    </a>
                </nav>
            </div>
        </div>

        {{-- Leads Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if($leads->count() > 0)
                {{-- Desktop Table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    ข้อมูลผู้เข้าชม
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    แหล่งที่มา
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    เวลา
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    สถานะ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($leads as $lead)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <td class="px-6 py-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                @if($lead->status === 'converted' && $lead->mlmMember)
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $lead->mlmMember->user->name ?? 'ไม่ระบุ' }}
                                                    </div>
                                                    <div class="text-sm text-green-600 dark:text-green-400 font-medium">
                                                        ✓ สมัครสมาชิกแล้ว
                                                    </div>
                                                @else
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        ผู้เข้าชมที่ {{ $lead->id }}
                                                    </div>
                                                @endif
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    IP: {{ $lead->ip_address }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($lead->utm_params)
                                            @php
                                                $utm = is_string($lead->utm_params) ? json_decode($lead->utm_params, true) : $lead->utm_params;
                                            @endphp
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ $utm['utm_source'] ?? 'ไม่ระบุ' }}
                                                </span>
                                            </div>
                                            @if(isset($utm['utm_campaign']))
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    แคมเปญ: {{ $utm['utm_campaign'] }}
                                                </div>
                                            @endif
                                        @elseif($lead->referrer_url)
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ Str::limit(parse_url($lead->referrer_url, PHP_URL_HOST) ?? 'Direct', 30) }}
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                Direct
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ $lead->locked_at->format('d/m/Y H:i') }}</span>
                                            @if($lead->status === 'locked' && $lead->expires_at > now())
                                                <span class="text-xs text-green-600 dark:text-green-400 mt-1">
                                                    หมดอายุ: {{ $lead->expires_at->format('d/m/Y H:i') }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    (เหลือ {{ $lead->getTimeRemaining() }})
                                                </span>
                                            @elseif($lead->status === 'converted')
                                                <span class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                                                    สมัครเมื่อ: {{ $lead->converted_at?->format('d/m/Y H:i') ?? '-' }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($lead->status === 'converted')
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-purple-400 to-purple-600 text-white shadow-lg">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                สมัครแล้ว
                                            </span>
                                        @elseif($lead->status === 'locked' && $lead->expires_at > now())
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-green-400 to-green-600 text-white shadow-lg">
                                                <svg class="w-4 h-4 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                </svg>
                                                กำลังติดตาม
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-gray-400 to-gray-600 text-white shadow-lg">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                                หมดอายุ
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($leads as $lead)
                        <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center shadow-lg">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        @if($lead->status === 'converted' && $lead->mlmMember)
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $lead->mlmMember->user->name ?? 'ไม่ระบุ' }}
                                            </div>
                                            <div class="text-xs text-green-600 dark:text-green-400 font-medium">
                                                ✓ สมัครสมาชิกแล้ว
                                            </div>
                                        @else
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                ผู้เข้าชมที่ {{ $lead->id }}
                                            </div>
                                        @endif
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            IP: {{ $lead->ip_address }}
                                        </div>
                                    </div>
                                </div>
                                @if($lead->status === 'converted')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-purple-400 to-purple-600 text-white shadow-lg">
                                        ✓ สมัครแล้ว
                                    </span>
                                @elseif($lead->status === 'locked' && $lead->expires_at > now())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-green-400 to-green-600 text-white shadow-lg">
                                        กำลังติดตาม
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-gray-400 to-gray-600 text-white shadow-lg">
                                        หมดอายุ
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">แหล่งที่มา:</span>
                                    @if($lead->utm_params)
                                        @php
                                            $utm = is_string($lead->utm_params) ? json_decode($lead->utm_params, true) : $lead->utm_params;
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $utm['utm_source'] ?? 'ไม่ระบุ' }}
                                        </span>
                                    @elseif($lead->referrer_url)
                                        <span class="text-gray-900 dark:text-white">
                                            {{ Str::limit(parse_url($lead->referrer_url, PHP_URL_HOST) ?? 'Direct', 20) }}
                                        </span>
                                    @else
                                        <span class="text-gray-900 dark:text-white">Direct</span>
                                    @endif
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">เวลา:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $lead->locked_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @if($lead->status === 'locked' && $lead->expires_at > now())
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">เหลือเวลา:</span>
                                        <span class="text-green-600 dark:text-green-400 font-medium">{{ $lead->getTimeRemaining() }}</span>
                                    </div>
                                @elseif($lead->status === 'converted')
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">สมัครเมื่อ:</span>
                                        <span class="text-purple-600 dark:text-purple-400">{{ $lead->converted_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $leads->links() }}
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-16 px-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 mb-6">
                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        ยังไม่มีผู้มุ่งหวัง
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        เริ่มแชร์ลิงก์ Recruit ของคุณเพื่อเริ่มต้นสร้างรายชื่อผู้มุ่งหวัง
                    </p>
                    <a href="{{ route('user.marketing.recruit.index') }}"
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                        </svg>
                        ดูลิงก์และ QR Code
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
