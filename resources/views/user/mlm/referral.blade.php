@extends('layouts.user-arrow-x')

@section('title', 'ลิงก์แนะนำ MLM')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🔗</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ลิงก์แนะนำของคุณ</h1>
                <p class="text-blue-100 mt-1">แชร์ลิงก์นี้เพื่อเชิญเพื่อนเข้าร่วม</p>
            </div>
        </div>
    </div>

    <!-- Referral Link Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span>📎</span> ลิงก์แนะนำของคุณ
        </h2>

        <!-- Referral URL Display -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200 mb-6">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">URL ของคุณ:</label>
                    <div class="bg-white rounded-lg p-4 font-mono text-sm break-all border border-blue-300">
                        {{ $referralUrl }}
                    </div>
                </div>
                <button onclick="copyReferralUrl()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-colors flex-shrink-0">
                    📋 คัดลอก
                </button>
            </div>
        </div>

        <!-- Member Code -->
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-200 mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">รหัสสมาชิกของคุณ:</label>
            <div class="flex items-center gap-4">
                <div class="flex-1 bg-white rounded-lg p-4 font-mono text-2xl font-bold text-purple-600 border border-purple-300">
                    {{ $member->member_code }}
                </div>
                <button onclick="copyMemberCode()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold transition-colors flex-shrink-0">
                    📋 คัดลอก
                </button>
            </div>
        </div>

        <!-- QR Code -->
        <div class="bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl p-6 border-2 border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">QR Code สำหรับแชร์</h3>
            <div class="flex justify-center">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div id="qrcode" class="mx-auto"></div>
                </div>
            </div>
            <p class="text-center text-sm text-gray-600 mt-4">สแกน QR Code เพื่อเข้าสู่หน้าสมัครสมาชิก</p>
        </div>
    </div>

    <!-- Share Options -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>📤</span> แชร์ผ่าน
        </h2>
        <div class="grid md:grid-cols-4 gap-4">
            <!-- LINE -->
            <button onclick="shareViaLine()" class="bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                <div class="text-4xl mb-2">💚</div>
                <div class="font-bold">LINE</div>
            </button>

            <!-- Facebook -->
            <button onclick="shareViaFacebook()" class="bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                <div class="text-4xl mb-2">📘</div>
                <div class="font-bold">Facebook</div>
            </button>

            <!-- Twitter -->
            <button onclick="shareViaTwitter()" class="bg-gradient-to-br from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                <div class="text-4xl mb-2">🐦</div>
                <div class="font-bold">Twitter</div>
            </button>

            <!-- Email -->
            <button onclick="shareViaEmail()" class="bg-gradient-to-br from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl p-6 transition-all shadow-lg hover:shadow-xl">
                <div class="text-4xl mb-2">📧</div>
                <div class="font-bold">Email</div>
            </button>
        </div>
    </div>

    <!-- Tips -->
    <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl shadow-xl p-6 border border-yellow-200">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>💡</span> เคล็ดลับการแนะนำ
        </h3>
        <div class="space-y-3">
            <div class="flex gap-3">
                <span class="text-2xl flex-shrink-0">✅</span>
                <div>
                    <div class="font-semibold text-gray-800">แชร์ในช่องทางที่เหมาะสม</div>
                    <div class="text-sm text-gray-600">เลือกช่องทางที่กลุ่มเป้าหมายของคุณใช้งานมากที่สุด</div>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="text-2xl flex-shrink-0">✅</span>
                <div>
                    <div class="font-semibold text-gray-800">อธิบายประโยชน์ที่จะได้รับ</div>
                    <div class="text-sm text-gray-600">บอกเล่าประสบการณ์และผลลัพธ์ที่คุณได้รับจากโปรแกรม</div>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="text-2xl flex-shrink-0">✅</span>
                <div>
                    <div class="font-semibold text-gray-800">ติดตามและให้คำแนะนำ</div>
                    <div class="text-sm text-gray-600">ช่วยเหลือและให้คำปรึกษากับสมาชิกใหม่ที่เข้าร่วม</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="hidden fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    <span id="toastMessage"></span>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const referralUrl = "{{ $referralUrl }}";
const memberCode = "{{ $member->member_code }}";

// Generate QR Code
new QRCode(document.getElementById("qrcode"), {
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

function copyReferralUrl() {
    navigator.clipboard.writeText(referralUrl).then(() => {
        showToast('✅ คัดลอกลิงก์แนะนำแล้ว');
    }).catch(() => {
        alert('ไม่สามารถคัดลอกได้');
    });
}

function copyMemberCode() {
    navigator.clipboard.writeText(memberCode).then(() => {
        showToast('✅ คัดลอกรหัสสมาชิกแล้ว');
    }).catch(() => {
        alert('ไม่สามารถคัดลอกได้');
    });
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
