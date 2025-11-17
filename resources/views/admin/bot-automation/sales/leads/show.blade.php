@extends('layouts.admin-v3')

@section('title', 'รายละเอียดลีด')

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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>รายละเอียดลีด</h1>
            <a href="{{ route('admin.bot-automation.sales.leads.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl shadow-lg transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span data-translate>กลับไปหน้าลีด</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Lead Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Lead Information Card -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ข้อมูลลีด</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>ชื่อ</label>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $lead->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>อีเมล</label>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $lead->email ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>โทรศัพท์</label>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $lead->phone ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>บริษัท</label>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $lead->company ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>สถานะ</label>
                                <div>
                                    @if(isset($lead->status))
                                        @if($lead->status == 'converted')
                                            <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-xl bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" data-translate>แปลงแล้ว</span>
                                        @elseif($lead->status == 'qualified')
                                            <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-xl bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200" data-translate>ผ่านคุณสมบัติ</span>
                                        @elseif($lead->status == 'contacted')
                                            <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-xl bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200" data-translate>ติดต่อแล้ว</span>
                                        @elseif($lead->status == 'new')
                                            <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200" data-translate>ใหม่</span>
                                        @else
                                            <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-xl bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-200">{{ ucfirst($lead->status) }}</span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>คะแนนลีด</label>
                                <div>
                                    <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-xl {{ isset($lead->score) && $lead->score >= 80 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : (isset($lead->score) && $lead->score >= 50 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-900 dark:text-white dark:bg-gray-700 dark:text-gray-200') }}">
                                        {{ $lead->score ?? '0' }}/100
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>บอท</label>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $lead->bot_name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>แหล่งที่มา</label>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $lead->source ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>มูลค่าโดยประมาณ</label>
                                <p class="text-base font-semibold text-green-600 dark:text-green-400">${{ number_format($lead->value ?? 0, 2) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>สร้างเมื่อ</label>
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ isset($lead->created_at) ? $lead->created_at->format('M d, Y h:i A') : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>หมายเหตุ</label>
                            <p class="text-base text-gray-900 dark:text-white">{{ $lead->notes ?? 'ไม่มีหมายเหตุ' }}</p>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <div class="flex flex-wrap gap-3">
                                <button onclick="updateLead()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white rounded-xl shadow-lg transition-all duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    <span data-translate>อัพเดทลีด</span>
                                </button>
                                <button onclick="convertLead()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl shadow-lg transition-all duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span data-translate>ทำเครื่องหมายว่าแปลงแล้ว</span>
                                </button>
                                <button onclick="deleteLead()" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl shadow-lg transition-all duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span data-translate>ลบ</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interaction History -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ประวัติการโต้ตอบ</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            @forelse($interactions ?? [] as $interaction)
                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-500 rounded-full flex items-center justify-center text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h6 class="text-base font-semibold text-gray-900 dark:text-white mb-1">{{ $interaction->title ?? 'การโต้ตอบ' }}</h6>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-2">{{ $interaction->description ?? 'ไม่มีคำอธิบาย' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 dark:text-gray-400">{{ isset($interaction->created_at) ? $interaction->created_at->diffForHumans() : 'N/A' }}</p>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400" data-translate>ไม่มีประวัติการโต้ตอบ</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Quick Actions & Stats -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>การดำเนินการด่วน</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <button onclick="sendEmail()" class="w-full flex items-center gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-xl transition-colors duration-200 text-left">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium" data-translate>ส่งอีเมล</span>
                            </button>

                            <button onclick="makeCall()" class="w-full flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 rounded-xl transition-colors duration-200 text-left">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium" data-translate>โทรออก</span>
                            </button>

                            <button onclick="scheduleFollowup()" class="w-full flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 rounded-xl transition-colors duration-200 text-left">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium" data-translate>กำหนดการติดตาม</span>
                            </button>

                            <button onclick="addNote()" class="w-full flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-xl transition-colors duration-200 text-left">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium" data-translate>เพิ่มหมายเหตุ</span>
                            </button>

                            <button onclick="assignTo()" class="w-full flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-xl transition-colors duration-200 text-left">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium" data-translate>มอบหมายให้</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lead Statistics -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>สถิติลีด</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>การโต้ตอบทั้งหมด:</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $lead->interactions_count ?? '0' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>ติดต่อล่าสุด:</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ isset($lead->last_contact) ? date('M d, Y', strtotime($lead->last_contact)) : 'ไม่เคย' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>วันในไปป์ไลน์:</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $lead->days_in_pipeline ?? '0' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>โอกาสการแปลง:</span>
                                <span class="text-sm font-bold text-orange-600 dark:text-orange-400">{{ $lead->conversion_probability ?? '0' }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ฟังก์ชันอัพเดทลีด
function updateLead() {
    console.log('Updating lead');
}

// ฟังก์ชันแปลงลีด
function convertLead() {
    if (confirm('ทำเครื่องหมายลีดนี้ว่าแปลงแล้วหรือไม่?')) {
        console.log('Converting lead');
    }
}

// ฟังก์ชันลบลีด
function deleteLead() {
    if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบลีดนี้? การกระทำนี้ไม่สามารถยกเลิกได้')) {
        console.log('Deleting lead');
    }
}

// ฟังก์ชันส่งอีเมล
function sendEmail() {
    console.log('Sending email');
}

// ฟังก์ชันโทรออก
function makeCall() {
    console.log('Making call');
}

// ฟังก์ชันกำหนดการติดตาม
function scheduleFollowup() {
    console.log('Scheduling follow-up');
}

// ฟังก์ชันเพิ่มหมายเหตุ
function addNote() {
    console.log('Adding note');
}

// ฟังก์ชันมอบหมาย
function assignTo() {
    console.log('Assigning lead');
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
