@extends('layouts.user-arrow-x')

@section('title', 'รายละเอียดผู้มุ่งหวัง')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="prospectShare()">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center">
                <a href="{{ route('user.prospects.index') }}"
                   class="mr-4 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">รายละเอียดผู้มุ่งหวัง</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">แชร์ลิงก์เชิญและติดตามสถานะการสมัคร</p>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center space-x-2">
                @if(in_array($prospect->status, ['pending', 'expired']))
                    {{-- Renew Button --}}
                    <form method="POST" action="{{ route('user.prospects.renew', $prospect->id) }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 dark:bg-green-500 text-white font-semibold rounded-lg hover:bg-green-700 dark:hover:bg-green-600 transition shadow-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            ต่ออายุ
                        </button>
                    </form>

                    {{-- Delete Button --}}
                    <form method="POST" action="{{ route('user.prospects.destroy', $prospect->id) }}" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบลิงก์เชิญนี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 dark:bg-red-500 text-white font-semibold rounded-lg hover:bg-red-700 dark:hover:bg-red-600 transition shadow-lg">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            ลบ
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Status & Profile Card --}}
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 dark:from-indigo-600 dark:via-purple-600 dark:to-pink-600 rounded-xl shadow-xl p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-20 w-20">
                    @if($prospect->line_profile_picture)
                        <img class="h-20 w-20 rounded-full ring-4 ring-white/30 shadow-xl"
                             src="{{ $prospect->line_profile_picture }}"
                             alt="{{ $prospect->line_display_name }}">
                    @else
                        <div class="h-20 w-20 rounded-full bg-white/20 flex items-center justify-center ring-4 ring-white/30 shadow-xl">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="ml-5">
                    <h2 class="text-2xl font-bold text-white">
                        {{ $prospect->line_display_name ?? 'รอเริ่มสมัคร' }}
                    </h2>
                    <p class="text-white/80 text-sm mt-1">
                        สร้างเมื่อ: {{ $prospect->created_at->format('d/m/Y H:i น.') }}
                    </p>
                    @if($prospect->line_user_id)
                        <p class="text-white/60 text-xs font-mono mt-1">
                            LINE ID: {{ Str::limit($prospect->line_user_id, 25) }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex flex-col items-start md:items-end space-y-2">
                @php
                    $statusConfig = [
                        'pending' => ['bg' => 'bg-yellow-400', 'text' => 'text-yellow-900', 'label' => 'รอดำเนินการ', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'desc' => 'รอผู้มุ่งหวังคลิกลิงก์'],
                        'in_progress' => ['bg' => 'bg-blue-400', 'text' => 'text-blue-900', 'label' => 'กำลังดำเนินการ', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'desc' => 'อยู่ระหว่างการสมัคร'],
                        'completed' => ['bg' => 'bg-green-400', 'text' => 'text-green-900', 'label' => 'สำเร็จ', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'desc' => 'สมัครสมาชิกเรียบร้อยแล้ว'],
                        'expired' => ['bg' => 'bg-red-400', 'text' => 'text-red-900', 'label' => 'หมดอายุ', 'icon' => 'M6 18L18 6M6 6l12 12', 'desc' => 'ลิงก์หมดอายุแล้ว'],
                    ];
                    $config = $statusConfig[$prospect->status] ?? ['bg' => 'bg-gray-400', 'text' => 'text-gray-900', 'label' => $prospect->status, 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'desc' => ''];
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold {{ $config['bg'] }} {{ $config['text'] }} shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                    </svg>
                    {{ $config['label'] }}
                </span>
                <p class="text-white/70 text-sm">{{ $config['desc'] }}</p>
            </div>
        </div>
    </div>

    @if($prospect->status !== 'completed')
        {{-- Invitation Link Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border-b border-indigo-200 dark:border-indigo-800 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    ลิงก์เชิญของคุณ
                </h2>
            </div>

            <div class="p-6 space-y-6">
                {{-- URL Copy Section --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL ลิงก์เชิญ
                    </label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text"
                               id="invitation-url"
                               value="{{ route('line.signup.invitation', $prospect->referral_token) }}"
                               readonly
                               class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-mono text-sm"
                               x-ref="urlInput">
                        <button @click="copyToClipboard('{{ route('line.signup.invitation', $prospect->referral_token) }}')"
                                class="px-6 py-3 bg-indigo-600 dark:bg-indigo-500 text-white font-semibold rounded-xl hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all shadow-lg hover:shadow-xl flex items-center justify-center whitespace-nowrap">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span x-text="copied ? 'คัดลอกแล้ว!' : 'คัดลอกลิงก์'"></span>
                        </button>
                    </div>
                </div>

                {{-- QR Code Section --}}
                <div class="flex flex-col lg:flex-row gap-6">
                    <div class="flex-1 text-center">
                        <div class="inline-block bg-white dark:bg-gray-700 p-6 rounded-xl shadow-lg border-2 border-gray-100 dark:border-gray-600">
                            <div id="qrcode" class="mx-auto"></div>
                        </div>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            สแกน QR Code เพื่อเปิดลิงก์เชิญ
                        </p>
                        <button @click="downloadQR()"
                                class="mt-2 inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            ดาวน์โหลด QR Code
                        </button>
                    </div>

                    {{-- Share Buttons --}}
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">แชร์ผ่านช่องทางต่างๆ</h3>
                        <div class="grid grid-cols-1 gap-3">
                            <button @click="shareViaLine()"
                                    class="flex items-center justify-center px-6 py-4 bg-[#06C755] hover:bg-[#05B04C] text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] transform">
                                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                                </svg>
                                แชร์ทาง LINE
                            </button>

                            <button @click="shareViaFacebook()"
                                    class="flex items-center justify-center px-6 py-4 bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] transform">
                                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                แชร์ทาง Facebook
                            </button>

                            <button @click="shareViaTwitter()"
                                    class="flex items-center justify-center px-6 py-4 bg-black hover:bg-gray-800 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] transform">
                                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                                แชร์ทาง X (Twitter)
                            </button>

                            <button @click="shareNative()"
                                    x-show="canShare"
                                    class="flex items-center justify-center px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] transform">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                                แชร์ช่องทางอื่นๆ
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Expiration Notice --}}
                @if($prospect->expires_at)
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-yellow-900 dark:text-yellow-100">
                                    ลิงก์นี้จะหมดอายุ: {{ \Carbon\Carbon::parse($prospect->expires_at)->format('d/m/Y H:i น.') }}
                                </p>
                                <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                                    เหลือเวลาอีก {{ \Carbon\Carbon::parse($prospect->expires_at)->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Follow-up Tools --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border-b border-orange-200 dark:border-orange-800 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    เครื่องมือติดตาม
                </h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Mark as Followed Up --}}
                    <form method="POST" action="{{ route('user.prospects.resend', $prospect->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center justify-between p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl hover:bg-orange-100 dark:hover:bg-orange-900/30 transition">
                            <div class="flex items-center">
                                <div class="bg-orange-500 rounded-lg p-2 mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-medium text-orange-900 dark:text-orange-100">บันทึกการติดตาม</p>
                                    <p class="text-xs text-orange-700 dark:text-orange-300">บันทึกว่าได้ติดต่อผู้มุ่งหวังแล้ว</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </form>

                    {{-- Copy Message Template --}}
                    <button @click="copyMessage()"
                            class="w-full flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                        <div class="flex items-center">
                            <div class="bg-blue-500 rounded-lg p-2 mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">คัดลอกข้อความเชิญ</p>
                                <p class="text-xs text-blue-700 dark:text-blue-300">ข้อความพร้อมลิงก์สำหรับส่งต่อ</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>

                @if($prospect->last_reminded_at)
                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            ติดตามล่าสุด: {{ \Carbon\Carbon::parse($prospect->last_reminded_at)->format('d/m/Y H:i น.') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Registered User Info --}}
    @if($prospect->registeredUser)
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 md:p-8">
                <div class="flex items-center mb-6">
                    <div class="bg-green-500 rounded-full p-3 mr-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-green-900 dark:text-green-100">สมัครสมาชิกเรียบร้อยแล้ว!</h2>
                        <p class="text-green-700 dark:text-green-300 text-sm">ผู้มุ่งหวังของคุณได้กลายเป็นสมาชิกในทีมแล้ว</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-16 w-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                            {{ substr($prospect->registeredUser->name, 0, 1) }}
                        </div>
                        <div class="ml-5">
                            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $prospect->registeredUser->name }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $prospect->registeredUser->email }}
                            </div>
                            <div class="mt-2 inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-xs font-medium">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                สมาชิกในทีมของคุณ
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-green-100 dark:bg-green-900/40 rounded-xl">
                    <p class="text-sm text-green-800 dark:text-green-200 flex items-start">
                        <span class="text-2xl mr-3">🎉</span>
                        <span>ยินดีด้วย! การแนะนำของคุณสำเร็จแล้ว คุณจะได้รับค่าคอมมิชชั่นจากการแนะนำครั้งนี้ตามเงื่อนไขของแผนรายได้</span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Prospect Details --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                ข้อมูลรายละเอียด
            </h2>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Referral Token</span>
                    <code class="block mt-1 text-sm bg-gray-200 dark:bg-gray-600 px-3 py-2 rounded-lg font-mono text-gray-800 dark:text-gray-200 break-all">
                        {{ $prospect->referral_token }}
                    </code>
                </div>

                @if($prospect->current_step)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">ขั้นตอนปัจจุบัน</span>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $prospect->current_step }}
                        </p>
                    </div>
                @endif

                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">สร้างเมื่อ</span>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $prospect->created_at->format('d/m/Y H:i น.') }}
                    </p>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">อัพเดทล่าสุด</span>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $prospect->updated_at->format('d/m/Y H:i น.') }}
                    </p>
                </div>

                @if($prospect->click_count)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">จำนวนคลิก</span>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ number_format($prospect->click_count) }} ครั้ง
                        </p>
                    </div>
                @endif

                @if($prospect->last_visit_at)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">เข้าชมล่าสุด</span>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ \Carbon\Carbon::parse($prospect->last_visit_at)->format('d/m/Y H:i น.') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- QR Code Library --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
function prospectShare() {
    return {
        copied: false,
        canShare: typeof navigator.share === 'function',
        invitationUrl: '{{ route('line.signup.invitation', $prospect->referral_token) }}',
        qrCodeInstance: null,

        init() {
            // สร้าง QR Code
            @if($prospect->status !== 'completed')
                this.qrCodeInstance = new QRCode(document.getElementById('qrcode'), {
                    text: this.invitationUrl,
                    width: 200,
                    height: 200,
                    colorDark: '#1F2937',
                    colorLight: '#FFFFFF',
                    correctLevel: QRCode.CorrectLevel.H
                });
            @endif
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 3000);
            });
        },

        copyMessage() {
            const message = `สวัสดีครับ/ค่ะ 👋\n\nมีโอกาสดีๆ มาแนะนำ! มาร่วมเป็นส่วนหนึ่งของทีมเรากันเถอะ\n\n🔗 คลิกลิงก์นี้เพื่อสมัครเลย:\n${this.invitationUrl}\n\nหากมีข้อสงสัยสามารถสอบถามได้เลยนะครับ/ค่ะ 💬`;
            navigator.clipboard.writeText(message).then(() => {
                alert('คัดลอกข้อความเรียบร้อยแล้ว!');
            });
        },

        shareViaLine() {
            const message = encodeURIComponent('มาสมัครสมาชิกกับเราสิ! คลิกลิงก์นี้เลย: ' + this.invitationUrl);
            window.open(`https://line.me/R/msg/text/?${message}`, '_blank');
        },

        shareViaFacebook() {
            const url = encodeURIComponent(this.invitationUrl);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        },

        shareViaTwitter() {
            const text = encodeURIComponent('มาสมัครสมาชิกกับเราสิ!');
            const url = encodeURIComponent(this.invitationUrl);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
        },

        shareNative() {
            if (navigator.share) {
                navigator.share({
                    title: 'เชิญสมัครสมาชิก',
                    text: 'มาสมัครสมาชิกกับเราสิ! คลิกลิงก์นี้เลย',
                    url: this.invitationUrl
                });
            }
        },

        downloadQR() {
            const canvas = document.querySelector('#qrcode canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = 'invitation-qr-code.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            }
        }
    }
}
</script>
@endsection
