@extends('layouts.user-v4')

@section('title', 'ลิงก์แนะนำ MLM')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="{}">

    {{-- ── หัวข้อ ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-share-nodes" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ลิงก์แนะนำของคุณ</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">แชร์ลิงก์นี้เพื่อเชิญเพื่อนเข้าร่วม</div>
            </div>
        </div>
    </div>

    {{-- ── ลิงก์แนะนำ + รหัสสมาชิก + QR ─────────────────────── --}}
    <div class="tp-card" style="padding:22px; display:flex; flex-direction:column; gap:18px;">

        {{-- ลิงก์แนะนำ --}}
        <div>
            <div style="font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:7px;">📎 URL ของคุณ</div>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                <code class="tp-num" style="flex:1; min-width:200px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm); font-size:12.5px; color:var(--ink); word-break:break-all;">{{ $referralUrl }}</code>
                <button type="button" onclick="copyReferralUrl()" class="tp-btn tp-btn-primary"><i class="fas fa-copy"></i> คัดลอก</button>
            </div>
        </div>

        {{-- รหัสสมาชิก --}}
        <div>
            <div style="font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:7px;">🆔 รหัสสมาชิกของคุณ</div>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                <div class="tp-num" style="flex:1; min-width:200px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm); font-size:22px; font-weight:800; color:var(--deep1);">{{ $member->member_code }}</div>
                <button type="button" onclick="copyMemberCode()" class="tp-btn"><i class="fas fa-copy"></i> คัดลอก</button>
            </div>
        </div>

        {{-- QR Code --}}
        <div style="padding-top:4px;">
            <div style="font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:10px; text-align:center;">QR Code สำหรับแชร์</div>
            <div style="display:flex; justify-content:center;">
                <div style="background:#fff; padding:18px; border-radius:18px; box-shadow:var(--raise);">
                    <div id="qrcode" style="min-width:200px; min-height:200px; display:flex; align-items:center; justify-content:center;">
                        <div id="qrcode-loading" style="text-align:center; color:#888;">
                            <i class="fas fa-circle-notch fa-spin" style="font-size:32px; color:#d98e3f;"></i>
                            <div style="font-size:12px; margin-top:8px;">กำลังสร้าง QR Code...</div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="qrcode-error" class="hidden" style="margin-top:14px; text-align:center;">
                <div style="color:#d9534f; font-size:12.5px; margin-bottom:8px;">ไม่สามารถสร้าง QR Code ได้</div>
                <button type="button" onclick="generateQRCode()" class="tp-btn tp-btn-sm">ลองใหม่</button>
            </div>
            <div style="text-align:center; font-size:12px; color:var(--ink2); margin-top:12px;">สแกน QR Code เพื่อเข้าสู่หน้าสมัครสมาชิก</div>
            <div style="display:flex; justify-content:center; margin-top:12px;">
                <button id="download-qr" onclick="downloadQRCode()" class="tp-btn hidden" style="align-items:center; gap:7px;"><i class="fas fa-download"></i> ดาวน์โหลด QR Code</button>
            </div>
        </div>
    </div>

    {{-- ── แชร์ผ่าน ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">📤 แชร์ผ่าน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px;">
            <button type="button" onclick="shareViaLine()" class="tp-card tp-card-hover" style="border:0; cursor:pointer; font-family:inherit; display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:22px; background:#06c755;">💚</span>
                <span style="font-size:13px; font-weight:700;">LINE</span>
            </button>
            <button type="button" onclick="shareViaFacebook()" class="tp-card tp-card-hover" style="border:0; cursor:pointer; font-family:inherit; display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:22px; background:#1877f2;">📘</span>
                <span style="font-size:13px; font-weight:700;">Facebook</span>
            </button>
            <button type="button" onclick="shareViaTwitter()" class="tp-card tp-card-hover" style="border:0; cursor:pointer; font-family:inherit; display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:22px; background:#1d9bf0;">🐦</span>
                <span style="font-size:13px; font-weight:700;">Twitter</span>
            </button>
            <button type="button" onclick="shareViaEmail()" class="tp-card tp-card-hover" style="border:0; cursor:pointer; font-family:inherit; display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:22px; background:#6b7280;">📧</span>
                <span style="font-size:13px; font-weight:700;">Email</span>
            </button>
        </div>
    </div>

    {{-- ── เคล็ดลับ ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💡 เคล็ดลับการแนะนำ</div>
        <div style="display:flex; flex-direction:column; gap:12px;">
            @php
                $tips = [
                    ['แชร์ในช่องทางที่เหมาะสม', 'เลือกช่องทางที่กลุ่มเป้าหมายของคุณใช้งานมากที่สุด'],
                    ['อธิบายประโยชน์ที่จะได้รับ', 'บอกเล่าประสบการณ์และผลลัพธ์ที่คุณได้รับจากโปรแกรม'],
                    ['ติดตามและให้คำแนะนำ', 'ช่วยเหลือและให้คำปรึกษากับสมาชิกใหม่ที่เข้าร่วม'],
                ];
            @endphp
            @foreach($tips as [$head, $desc])
                <div style="display:flex; gap:11px; align-items:flex-start;">
                    <span class="tp-tile" style="width:30px; height:30px; border-radius:9px; font-size:13px; background:rgba(90,160,126,.18);"><i class="fas fa-check" style="color:#5aa07e;"></i></span>
                    <div>
                        <div style="font-weight:700; font-size:13.5px;">{{ $head }}</div>
                        <div style="font-size:12.5px; color:var(--ink2);">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const referralUrl = @json($referralUrl);
const memberCode = @json($member->member_code);
let qrCodeInstance = null;

function showToast(message) {
    if (window.showNotification) window.showNotification(message, 'success');
}

function generateQRCode(retryCount = 0) {
    const maxRetries = 10;
    const retryDelay = 300;
    const qrcodeContainer = document.getElementById('qrcode');
    const loadingEl = document.getElementById('qrcode-loading');
    const errorEl = document.getElementById('qrcode-error');
    const downloadBtn = document.getElementById('download-qr');
    if (errorEl) errorEl.classList.add('hidden');

    if (typeof QRCode === 'undefined') {
        if (retryCount < maxRetries) {
            setTimeout(() => generateQRCode(retryCount + 1), retryDelay);
            return;
        }
        useFallbackQRCode();
        return;
    }
    try {
        if (loadingEl) loadingEl.remove();
        if (qrCodeInstance) { qrCodeInstance.clear(); qrcodeContainer.innerHTML = ''; }
        qrCodeInstance = new QRCode(qrcodeContainer, {
            text: referralUrl, width: 200, height: 200,
            colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H
        });
        if (downloadBtn) { downloadBtn.classList.remove('hidden'); downloadBtn.classList.add('flex'); }
    } catch (e) {
        useFallbackQRCode();
    }
}

function useFallbackQRCode() {
    const qrcodeContainer = document.getElementById('qrcode');
    const loadingEl = document.getElementById('qrcode-loading');
    const errorEl = document.getElementById('qrcode-error');
    const downloadBtn = document.getElementById('download-qr');
    if (loadingEl) loadingEl.remove();
    const encodedUrl = encodeURIComponent(referralUrl);
    const qrServerUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodedUrl + '&format=png';
    const img = document.createElement('img');
    img.src = qrServerUrl;
    img.alt = 'QR Code';
    img.width = 200; img.height = 200; img.style.borderRadius = '8px';
    img.id = 'qrcode-image'; img.crossOrigin = 'anonymous';
    img.onload = function () {
        qrcodeContainer.innerHTML = '';
        qrcodeContainer.appendChild(img);
        if (downloadBtn) { downloadBtn.classList.remove('hidden'); downloadBtn.classList.add('flex'); }
    };
    img.onerror = function () {
        if (errorEl) errorEl.classList.remove('hidden');
        qrcodeContainer.innerHTML = '<p style="color:#888;font-size:12px;">ไม่สามารถโหลด QR Code ได้</p>';
    };
}

function downloadQRCode() {
    const qrcodeContainer = document.getElementById('qrcode');
    const canvas = qrcodeContainer.querySelector('canvas');
    const img = qrcodeContainer.querySelector('img');
    let dataUrl;
    if (canvas) {
        dataUrl = canvas.toDataURL('image/png');
    } else if (img) {
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = img.width; tempCanvas.height = img.height;
        tempCanvas.getContext('2d').drawImage(img, 0, 0);
        dataUrl = tempCanvas.toDataURL('image/png');
    }
    if (dataUrl) {
        const link = document.createElement('a');
        link.download = 'qrcode-' + memberCode + '.png';
        link.href = dataUrl; link.click();
        showToast('ดาวน์โหลด QR Code สำเร็จ');
    } else {
        showToast('ไม่สามารถดาวน์โหลด QR Code ได้');
    }
}

document.addEventListener('DOMContentLoaded', function () { setTimeout(generateQRCode, 100); });

function copyReferralUrl() {
    navigator.clipboard.writeText(referralUrl).then(() => showToast('คัดลอกลิงก์แนะนำแล้ว')).catch(() => showToast('คัดลอกไม่สำเร็จ'));
}
function copyMemberCode() {
    navigator.clipboard.writeText(memberCode).then(() => showToast('คัดลอกรหัสสมาชิกแล้ว')).catch(() => showToast('คัดลอกไม่สำเร็จ'));
}
function shareViaLine() {
    const message = 'มาร่วมเป็นส่วนหนึ่งกับเรา! 🚀\n' + referralUrl;
    window.open('https://line.me/R/msg/text/?' + encodeURIComponent(message), '_blank');
}
function shareViaFacebook() {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(referralUrl), '_blank');
}
function shareViaTwitter() {
    const text = 'มาร่วมเป็นส่วนหนึ่งกับเรา! 🚀';
    window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(referralUrl), '_blank');
}
function shareViaEmail() {
    const subject = 'เชิญเข้าร่วมโปรแกรม MLM';
    const body = 'สวัสดี!\n\nฉันอยากเชิญคุณมาร่วมเป็นส่วนหนึ่งกับเรา\n\nสมัครได้ที่: ' + referralUrl + '\n\nรหัสแนะนำ: ' + memberCode;
    window.location.href = 'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
}
</script>
@endpush
@endsection
