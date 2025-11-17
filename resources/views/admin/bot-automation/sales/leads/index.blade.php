@extends('layouts.admin-v3')

@section('title', 'ลีดการขาย')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="fixed top-20 right-4 z-50">
        <div class="relative inline-block" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 glass-fusion dark:bg-gray-800 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl shadow-sm hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span x-text="language === 'th' ? 'ไทย' : language === 'en' ? 'English' : language === 'zh' ? '中文' : '日本語'" class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300"></span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 dark:border-gray-700 py-2 z-50" border border-white/20 dark:border-white/10
                style="display: none;"
            >
                <button @click="language = 'th'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇹🇭</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">ไทย (Thai)</span>
                </button>
                <button @click="language = 'en'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">English</span>
                </button>
                <button @click="language = 'zh'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇨🇳</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">中文 (Chinese)</span>
                </button>
                <button @click="language = 'ja'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇯🇵</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">日本語 (Japanese)</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>ลีดการขาย</h1>
            <a href="{{ route('admin.bot-automation.sales.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl shadow-lg transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span data-translate>กลับไปแดชบอร์ด</span>
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Card 1: Total Leads -->
            <div class="bg-gradient-to-br from-orange-500 to-amber-500 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-orange-100 text-sm font-medium uppercase mb-2" data-translate>ลีดทั้งหมด</p>
                        <p class="text-3xl font-bold">{{ $totalLeads ?? '0' }}</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 2: Hot Leads -->
            <div class="bg-gradient-to-br from-red-500 to-orange-500 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-red-100 text-sm font-medium uppercase mb-2" data-translate>ลีดร้อนแรง</p>
                        <p class="text-3xl font-bold">{{ $hotLeads ?? '0' }}</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 3: New This Week -->
            <div class="bg-gradient-to-br from-amber-500 to-yellow-500 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-amber-100 text-sm font-medium uppercase mb-2" data-translate>ใหม่สัปดาห์นี้</p>
                        <p class="text-3xl font-bold">{{ $weeklyLeads ?? '0' }}</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 4: Avg Lead Score -->
            <div class="bg-gradient-to-br from-yellow-600 to-orange-400 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-200">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-yellow-100 text-sm font-medium uppercase mb-2" data-translate>คะแนนลีดเฉลี่ย</p>
                        <p class="text-3xl font-bold">{{ number_format($avgLeadScore ?? 0, 0) }}</p>
                    </div>
                    <div class="glass-fusion rounded-xl p-3" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lead Management -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>จัดการลีด</h3>
            </div>
            <div class="p-6">
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div>
                        <input type="text" class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition" placeholder="ค้นหาลีด..." id="searchLeads" data-translate-placeholder>
                    </div>
                    <div>
                        <select class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition" id="filterStatus">
                            <option value=""><span data-translate>สถานะทั้งหมด</span></option>
                            <option value="new"><span data-translate>ใหม่</span></option>
                            <option value="contacted"><span data-translate>ติดต่อแล้ว</span></option>
                            <option value="qualified"><span data-translate>ผ่านคุณสมบัติ</span></option>
                            <option value="converted"><span data-translate>แปลงแล้ว</span></option>
                            <option value="lost"><span data-translate>สูญเสีย</span></option>
                        </select>
                    </div>
                    <div>
                        <select class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition" id="filterScore">
                            <option value=""><span data-translate>คะแนนทั้งหมด</span></option>
                            <option value="hot"><span data-translate>ร้อนแรง (80-100)</span></option>
                            <option value="warm"><span data-translate>อุ่น (50-79)</span></option>
                            <option value="cold"><span data-translate>เย็น (0-49)</span></option>
                        </select>
                    </div>
                    <div>
                        <select class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition" id="filterBot">
                            <option value=""><span data-translate>บอททั้งหมด</span></option>
                            @foreach($bots ?? [] as $bot)
                            <option value="{{ $bot->id }}">{{ $bot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="date" class="w-full px-4 py-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition" id="filterDate">
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="leadsTable">
                        <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>ไอดี</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>ชื่อ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>อีเมล</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>บอท</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>คะแนน</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>สถานะ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>มูลค่า</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>สร้างเมื่อ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider" data-translate>การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody class="glass-fusion dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($leads ?? [] as $lead)
                            <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $lead->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $lead->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                    {{ $lead->email ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $lead->bot_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $lead->score >= 80 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($lead->score >= 50 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-200') }}">
                                        {{ $lead->score ?? '0' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($lead->status == 'converted')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" data-translate>แปลงแล้ว</span>
                                    @elseif($lead->status == 'qualified')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" data-translate>ผ่านคุณสมบัติ</span>
                                    @elseif($lead->status == 'contacted')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200" data-translate>ติดต่อแล้ว</span>
                                    @elseif($lead->status == 'new')
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200" data-translate>ใหม่</span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-200">{{ ucfirst($lead->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                    ${{ number_format($lead->value ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ isset($lead->created_at) ? $lead->created_at->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.bot-automation.sales.leads.show', $lead->id ?? 0) }}" class="inline-flex items-center px-3 py-2 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded-xl transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <button onclick="contactLead({{ $lead->id }})" class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-xl transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <p class="text-lg font-medium" data-translate>ไม่พบลีด</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(isset($leads) && method_exists($leads, 'links'))
                <div class="mt-6">
                    {{ $leads->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ฟังก์ชันติดต่อลีด
function contactLead(leadId) {
    console.log('Contacting lead:', leadId);
    // เพิ่มฟังก์ชันติดต่อลีดที่นี่
}

// เพิ่ม Google Translate Script
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        includedLanguages: 'th,en,zh-CN,ja',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
    }, 'google_translate_element');
}

// โหลด Google Translate API
(function() {
    var gtScript = document.createElement('script');
    gtScript.type = 'text/javascript';
    gtScript.async = true;
    gtScript.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    document.body.appendChild(gtScript);
})();

// ฟังก์ชันแปลภาษา
function translatePage(targetLang) {
    const elements = document.querySelectorAll('[data-translate]');

    elements.forEach(element => {
        const text = element.textContent.trim();
        if (text && targetLang !== 'th') {
            fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=th&tl=${targetLang}&dt=t&q=${encodeURIComponent(text)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data[0] && data[0][0]) {
                        element.textContent = data[0][0][0];
                    }
                })
                .catch(error => console.error('Translation error:', error));
        }
    });
}

// ฟังก์ชัน Alpine.js สำหรับเปลี่ยนภาษา
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value === 'en' ? 'en' : value === 'zh' ? 'zh-CN' : 'ja');
        } else {
            location.reload();
        }
    });
});
</script>
@endpush
@endsection
