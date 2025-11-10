@extends('layouts.user')

@section('title', 'รายละเอียดผู้มุ่งหวัง')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="prospectShare()">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">รายละเอียดผู้มุ่งหวัง</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">แชร์ลิงก์เชิญและติดตามสถานะ</p>
            </div>
            <a href="{{ route('user.prospects.index') }}"
               class="px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white font-semibold rounded-lg hover:bg-gray-700 dark:hover:bg-gray-600 transition">
                ← กลับ
            </a>
        </div>
    </div>

    {{-- Status --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-16 w-16">
                    @if($prospect->line_profile_picture)
                        <img class="h-16 w-16 rounded-full ring-4 ring-indigo-100 dark:ring-indigo-900"
                             src="{{ $prospect->line_profile_picture }}"
                             alt="{{ $prospect->line_display_name }}">
                    @else
                        <div class="h-16 w-16 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center ring-4 ring-indigo-100 dark:ring-indigo-900">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="ml-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $prospect->line_display_name ?? 'รอเริ่มสมัคร' }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สร้างเมื่อ: {{ $prospect->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div>
                @php
                    $statusConfig = [
                        'pending' => ['color' => 'yellow', 'label' => 'รอดำเนินการ', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'in_progress' => ['color' => 'indigo', 'label' => 'กำลังดำเนินการ', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        'completed' => ['color' => 'green', 'label' => 'สำเร็จ', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'expired' => ['color' => 'red', 'label' => 'หมดอายุ', 'icon' => 'M6 18L18 6M6 6l12 12'],
                    ];
                    $config = $statusConfig[$prospect->status] ?? ['color' => 'gray', 'label' => $prospect->status, 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-{{ $config['color'] }}-100 dark:bg-{{ $config['color'] }}-900/30 text-{{ $config['color'] }}-800 dark:text-{{ $config['color'] }}-400">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                    </svg>
                    {{ $config['label'] }}
                </span>
            </div>
        </div>
    </div>

    @if($prospect->status !== 'completed')
        {{-- Invitation Link --}}
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg shadow-md p-6 border border-indigo-200 dark:border-indigo-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                ลิงก์เชิญของคุณ
            </h2>

            {{-- URL --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-4 border-2 border-indigo-200 dark:border-indigo-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URL</label>
                <div class="flex items-center space-x-2">
                    <input type="text"
                           id="invitation-url"
                           value="{{ route('line.signup.start', $prospect->referral_token) }}"
                           readonly
                           class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-mono text-sm"
                           x-ref="urlInput">
                    <button @click="copyToClipboard('{{ route('line.signup.start', $prospect->referral_token) }}')"
                            class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition whitespace-nowrap flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-text="copied ? 'คัดลอกแล้ว!' : 'คัดลอก'"></span>
                    </button>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="text-center mb-4">
                <div class="inline-block bg-white p-4 rounded-lg shadow-md">
                    <div id="qrcode"></div>
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">สแกน QR Code เพื่อเปิดลิงก์เชิญ</p>
            </div>

            {{-- Share Buttons --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button @click="shareViaLine()"
                        class="flex items-center justify-center px-4 py-3 bg-[#06C755] hover:bg-[#05B04C] text-white font-semibold rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                    </svg>
                    แชร์ทาง LINE
                </button>

                <button @click="shareViaFacebook()"
                        class="flex items-center justify-center px-4 py-3 bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    แชร์ทาง Facebook
                </button>

                <button @click="shareViaTwitter()"
                        class="flex items-center justify-center px-4 py-3 bg-[#1DA1F2] hover:bg-[#1A91DA] text-white font-semibold rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                    แชร์ทาง Twitter
                </button>
            </div>

            @if($prospect->expires_at)
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-center text-sm text-yellow-800 dark:text-yellow-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ลิงก์นี้จะหมดอายุ: {{ \Carbon\Carbon::parse($prospect->expires_at)->format('d/m/Y H:i') }}
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Registered User Info --}}
    @if($prospect->registeredUser)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                สมัครสมาชิกเรียบร้อยแล้ว!
            </h2>

            <div class="flex items-center">
                <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ substr($prospect->registeredUser->name, 0, 1) }}
                </div>
                <div class="ml-4">
                    <div class="text-lg font-medium text-green-900 dark:text-green-100">
                        {{ $prospect->registeredUser->name }}
                    </div>
                    <div class="text-sm text-green-700 dark:text-green-300">
                        {{ $prospect->registeredUser->email }}
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-green-100 dark:bg-green-900/40 rounded-lg">
                <p class="text-sm text-green-800 dark:text-green-200">
                    🎉 ยินดีด้วย! ผู้มุ่งหวังของคุณได้สมัครสมาชิกเรียบร้อยแล้ว คุณจะได้รับค่าคอมมิชชั่นจากการแนะนำนี้
                </p>
            </div>
        </div>
    @endif

    {{-- Prospect Details --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">รายละเอียด</h2>

        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">Referral Token</span>
                <code class="text-sm bg-gray-200 dark:bg-gray-600 px-3 py-1 rounded font-mono text-gray-800 dark:text-gray-200">
                    {{ $prospect->referral_token }}
                </code>
            </div>

            @if($prospect->current_step)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-sm text-gray-600 dark:text-gray-400">ขั้นตอนปัจจุบัน</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $prospect->current_step }}
                    </span>
                </div>
            @endif

            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $prospect->created_at->format('d/m/Y H:i') }}
                </span>
            </div>

            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm text-gray-600 dark:text-gray-400">อัพเดทล่าสุด</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $prospect->updated_at->format('d/m/Y H:i') }}
                </span>
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
        invitationUrl: '{{ route('line.signup.start', $prospect->referral_token) }}',

        init() {
            // Generate QR Code
            @if($prospect->status !== 'completed')
                new QRCode(document.getElementById('qrcode'), {
                    text: this.invitationUrl,
                    width: 200,
                    height: 200
                });
            @endif
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 2000);
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
        }
    }
}
</script>
@endsection
