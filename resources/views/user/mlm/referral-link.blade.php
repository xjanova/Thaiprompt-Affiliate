@extends('layouts.user')

@section('title', 'ลิงก์แนะนำสมาชิก')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🎯</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ลิงก์แนะนำสมาชิก</h1>
                <p class="text-pink-100 mt-1">แชร์ลิงก์นี้เพื่อเชิญเพื่อนเข้าร่วม</p>
            </div>
        </div>
    </div>

    <!-- Main Referral Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <!-- Referral URL -->
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span>🔗</span> ลิงก์แนะนำของคุณ
            </h2>
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200">
                <label class="block text-sm font-semibold text-gray-700 mb-2">URL:</label>
                <div class="flex gap-3">
                    <input type="text"
                           id="referralUrl"
                           value="{{ $referralUrl }}"
                           readonly
                           class="flex-1 px-4 py-3 bg-white border-2 border-blue-300 rounded-lg font-mono text-sm">
                    <button onclick="copyUrl()"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-colors">
                        📋 คัดลอก
                    </button>
                </div>
            </div>
        </div>

        <!-- Member Code -->
        <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span>🎫</span> รหัสสมาชิกของคุณ
            </h3>
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-200">
                <div class="flex gap-3">
                    <input type="text"
                           id="memberCode"
                           value="{{ $member->member_code }}"
                           readonly
                           class="flex-1 px-4 py-3 bg-white border-2 border-purple-300 rounded-lg font-mono text-2xl font-bold text-center">
                    <button onclick="copyCode()"
                            class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold transition-colors">
                        📋 คัดลอก
                    </button>
                </div>
            </div>
        </div>

        <!-- QR Code Section -->
        <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 text-center flex items-center justify-center gap-2">
                <span>📱</span> QR Code
            </h3>
            <div class="bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-8 border-2 border-gray-200">
                <div class="flex justify-center mb-4">
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div id="qrcode"></div>
                    </div>
                </div>
                <p class="text-center text-sm text-gray-600">สแกน QR Code เพื่อเข้าสู่หน้าสมัครสมาชิก</p>
                <div class="mt-4 text-center">
                    <button onclick="downloadQR()" class="px-6 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg font-semibold transition-colors">
                        💾 ดาวน์โหลด QR Code
                    </button>
                </div>
            </div>
        </div>

        <!-- Share Buttons -->
        <div>
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span>📤</span> แชร์ผ่าน
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <button onclick="shareViaLine()"
                        class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                    <div class="text-4xl mb-2">💚</div>
                    <div class="font-bold">LINE</div>
                </button>

                <button onclick="shareViaFacebook()"
                        class="bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                    <div class="text-4xl mb-2">📘</div>
                    <div class="font-bold">Facebook</div>
                </button>

                <button onclick="shareViaTwitter()"
                        class="bg-gradient-to-br from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                    <div class="text-4xl mb-2">🐦</div>
                    <div class="font-bold">Twitter</div>
                </button>

                <button onclick="shareViaEmail()"
                        class="bg-gradient-to-br from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                    <div class="text-4xl mb-2">📧</div>
                    <div class="font-bold">Email</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Tips -->
    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-2xl shadow-xl p-6 border border-amber-200">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>💡</span> วิธีใช้งาน
        </h3>
        <ul class="space-y-2 text-sm text-gray-700">
            <li class="flex gap-2">
                <span>✅</span>
                <span>คัดลอกลิงก์หรือ QR Code เพื่อแชร์ให้เพื่อน</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span>เมื่อเพื่อนสมัครผ่านลิงก์นี้ คุณจะได้รับค่าคอมมิชชั่น</span>
            </li>
            <li class="flex gap-2">
                <span>✅</span>
                <span>ตรวจสอบรายชื่อสมาชิกที่แนะนำได้ที่เมนู "ทีมของฉัน"</span>
            </li>
        </ul>
    </div>
</div>

<div id="toast" class="hidden fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    <span id="toastMessage"></span>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const referralUrl = "{{ $referralUrl }}";
const memberCode = "{{ $member->member_code }}";

// Generate QR Code
const qrcode = new QRCode(document.getElementById("qrcode"), {
    text: referralUrl,
    width: 200,
    height: 200,
    colorDark: "#000000",
    colorLight: "#ffffff",
});

function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.classList.add('hidden');
            toast.style.opacity = '1';
        }, 500);
    }, 3000);
}

function copyUrl() {
    const input = document.getElementById('referralUrl');
    input.select();
    navigator.clipboard.writeText(referralUrl).then(() => {
        showToast('✅ คัดลอกลิงก์แล้ว');
    });
}

function copyCode() {
    const input = document.getElementById('memberCode');
    input.select();
    navigator.clipboard.writeText(memberCode).then(() => {
        showToast('✅ คัดลอกรหัสแล้ว');
    });
}

function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    const url = canvas.toDataURL("image/png");
    const link = document.createElement('a');
    link.download = `referral-qr-${memberCode}.png`;
    link.href = url;
    link.click();
    showToast('✅ ดาวน์โหลด QR Code แล้ว');
}

function shareViaLine() {
    const message = `มาร่วมเป็นส่วนหนึ่งกับเรา! 🚀\n${referralUrl}`;
    window.open(`https://line.me/R/msg/text/?${encodeURIComponent(message)}`, '_blank');
}

function shareViaFacebook() {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralUrl)}`, '_blank');
}

function shareViaTwitter() {
    const text = `มาร่วมเป็นส่วนหนึ่งกับเรา! 🚀`;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(referralUrl)}`, '_blank');
}

function shareViaEmail() {
    const subject = 'เชิญเข้าร่วมโปรแกรม MLM';
    const body = `สวัสดี!\n\nฉันอยากเชิญคุณมาร่วมเป็นส่วนหนึ่งกับเรา\n\nสมัครได้ที่: ${referralUrl}\n\nรหัสแนะนำ: ${memberCode}`;
    window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}
</script>
@endpush
@endsection
