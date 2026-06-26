@extends('layouts.user-v4')

@section('title', 'ลิงก์แนะนำสมาชิก')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:880px; margin:0 auto;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-link" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ลิงก์แนะนำสมาชิก</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">แชร์ลิงก์นี้เพื่อเชิญเพื่อนเข้าร่วม</div>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('user.mlm.dashboard'))
            <a href="{{ route('user.mlm.dashboard') }}" class="tp-icon-btn" title="กลับหน้า MLM"><i class="fas fa-arrow-left"></i></a>
            @endif
        </div>
    </div>

    {{-- ── การ์ดหลัก: ลิงก์ + รหัส + QR + แชร์ ───────────────── --}}
    <div class="tp-card" style="padding:24px;">

        {{-- ลิงก์แนะนำ --}}
        <div style="margin-bottom:26px;">
            <div class="tp-section-h" style="margin-bottom:12px;">🔗 ลิงก์แนะนำของคุณ</div>
            <div style="padding:18px; border-radius:16px; box-shadow:var(--inset-sm);">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:8px;">URL:</label>
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <input type="text"
                           id="referralUrl"
                           value="{{ $referralUrl }}"
                           readonly
                           class="tp-input tp-num"
                           style="flex:1; min-width:200px; font-size:13px;">
                    <button onclick="copyUrl()" type="button" class="tp-btn tp-btn-primary">
                        <i class="fas fa-copy"></i> คัดลอก
                    </button>
                </div>
            </div>
        </div>

        {{-- รหัสสมาชิก --}}
        <div style="margin-bottom:26px;">
            <div class="tp-section-h" style="margin-bottom:12px;">🎫 รหัสสมาชิกของคุณ</div>
            <div style="padding:18px; border-radius:16px; box-shadow:var(--inset-sm);">
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <input type="text"
                           id="memberCode"
                           value="{{ $member->member_code }}"
                           readonly
                           class="tp-input tp-num"
                           style="flex:1; min-width:200px; font-size:22px; font-weight:800; text-align:center; letter-spacing:1px; color:var(--deep1);">
                    <button onclick="copyCode()" type="button" class="tp-btn tp-btn-primary">
                        <i class="fas fa-copy"></i> คัดลอก
                    </button>
                </div>
            </div>
        </div>

        {{-- QR Code --}}
        <div style="margin-bottom:26px;">
            <div class="tp-section-h" style="margin-bottom:12px; text-align:center;">📱 QR Code</div>
            <div style="padding:24px; border-radius:16px; box-shadow:var(--inset-sm);">
                <div style="display:flex; justify-content:center; margin-bottom:16px;">
                    <div style="background:#fff; padding:20px; border-radius:16px; box-shadow:var(--inset);">
                        <div id="qrcode" style="display:flex; align-items:center; justify-content:center; min-width:200px; min-height:200px;">
                            {{-- Loading state --}}
                            <div id="qrcode-loading" style="text-align:center;">
                                <div style="display:inline-block;"><i class="fas fa-spinner fa-spin" style="font-size:34px; color:var(--accent1);"></i></div>
                                <p style="font-size:13px; color:var(--ink2); margin-top:8px;">กำลังสร้าง QR Code...</p>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Error message (hidden by default) --}}
                <div id="qrcode-error" style="display:none; text-align:center; margin-bottom:16px;">
                    <p style="color:#d9534f; font-size:13px; margin-bottom:10px;">ไม่สามารถสร้าง QR Code ได้</p>
                    <button onclick="generateQRCode()" type="button" class="tp-btn tp-btn-sm">ลองใหม่</button>
                </div>
                <p style="text-align:center; font-size:13px; color:var(--ink2);">สแกน QR Code เพื่อเข้าสู่หน้าสมัครสมาชิก</p>
                <div style="margin-top:16px; text-align:center;">
                    <button id="download-qr" onclick="downloadQR()" type="button" class="tp-btn tp-btn-sm" style="display:none;">
                        💾 ดาวน์โหลด QR Code
                    </button>
                </div>
            </div>
        </div>

        {{-- ปุ่มแชร์ --}}
        <div>
            <div class="tp-section-h" style="margin-bottom:12px;">📤 แชร์ผ่าน</div>
            @php
                $shareButtons = [
                    ['shareViaLine()',     '💚', 'LINE',     '#5aa07e'],
                    ['shareViaFacebook()', '📘', 'Facebook', '#5689b8'],
                    ['shareViaTwitter()',  '🐦', 'Twitter',  '#3aa3d9'],
                    ['shareViaEmail()',    '📧', 'Email',    'var(--ink2)'],
                ];
            @endphp
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px;">
                @foreach($shareButtons as [$action, $emoji, $label, $color])
                    <button onclick="{{ $action }}" type="button" class="tp-card tp-card-hover"
                            style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:18px 10px; cursor:pointer; border:none; background:transparent;">
                        <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:24px; background:color-mix(in srgb, {{ $color }} 18%, transparent);">{{ $emoji }}</span>
                        <span style="font-size:13px; font-weight:700; color:var(--ink);">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── วิธีใช้งาน ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <div class="tp-section-h" style="margin-bottom:12px;">💡 วิธีใช้งาน</div>
        <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--ink2);">
            <li style="display:flex; gap:9px; align-items:flex-start;">
                <span style="color:#5aa07e; flex-shrink:0;">✅</span>
                <span>คัดลอกลิงก์หรือ QR Code เพื่อแชร์ให้เพื่อน</span>
            </li>
            <li style="display:flex; gap:9px; align-items:flex-start;">
                <span style="color:#5aa07e; flex-shrink:0;">✅</span>
                <span>เมื่อเพื่อนสมัครผ่านลิงก์นี้ คุณจะได้รับค่าคอมมิชชั่น</span>
            </li>
            <li style="display:flex; gap:9px; align-items:flex-start;">
                <span style="color:#5aa07e; flex-shrink:0;">✅</span>
                <span>ตรวจสอบรายชื่อสมาชิกที่แนะนำได้ที่เมนู "ทีมของฉัน"</span>
            </li>
        </ul>
    </div>
</div>

@push('scripts')
{{-- ใช้ CDN ที่ถูกต้องสำหรับ qrcodejs --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const referralUrl = @json($referralUrl);
const memberCode = @json($member->member_code);

// ตัวแปร global เก็บ QR code instance
let qrCodeInstance = null;

/**
 * สร้าง QR Code พร้อม retry mechanism
 * รอให้ library โหลดเสร็จก่อนสร้าง QR Code
 */
function generateQRCode(retryCount = 0) {
    const maxRetries = 10;
    const retryDelay = 300; // 300ms

    const qrcodeContainer = document.getElementById('qrcode');
    const loadingEl = document.getElementById('qrcode-loading');
    const errorEl = document.getElementById('qrcode-error');
    const downloadBtn = document.getElementById('download-qr');

    // ซ่อน error message
    if (errorEl) errorEl.style.display = 'none';

    // ตรวจสอบว่า QRCode library โหลดแล้วหรือยัง
    if (typeof QRCode === 'undefined') {
        if (retryCount < maxRetries) {
            console.log(`QRCode library ยังไม่พร้อม, รอ... (${retryCount + 1}/${maxRetries})`);
            setTimeout(() => generateQRCode(retryCount + 1), retryDelay);
            return;
        } else {
            // ลองใช้ fallback (QR Server API)
            console.warn('QRCode library โหลดไม่สำเร็จ, ใช้ fallback');
            useFallbackQRCode();
            return;
        }
    }

    try {
        // ล้าง container ก่อนสร้างใหม่
        if (loadingEl) loadingEl.remove();

        // ล้าง QR code เดิม (ถ้ามี)
        if (qrCodeInstance) {
            qrCodeInstance.clear();
            qrcodeContainer.innerHTML = '';
        }

        // สร้าง QR Code ใหม่
        qrCodeInstance = new QRCode(qrcodeContainer, {
            text: referralUrl,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // แสดงปุ่มดาวน์โหลด
        if (downloadBtn) {
            downloadBtn.style.display = 'inline-flex';
        }

        console.log('สร้าง QR Code สำเร็จ');
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการสร้าง QR Code:', error);
        useFallbackQRCode();
    }
}

/**
 * Fallback ใช้ QR Server API สร้าง QR Code (ไม่ต้องใช้ library)
 * Google Charts API ถูก deprecated แล้ว จึงใช้ api.qrserver.com แทน
 */
function useFallbackQRCode() {
    const qrcodeContainer = document.getElementById('qrcode');
    const loadingEl = document.getElementById('qrcode-loading');
    const errorEl = document.getElementById('qrcode-error');
    const downloadBtn = document.getElementById('download-qr');

    if (loadingEl) loadingEl.remove();

    // สร้าง QR Code ด้วย QR Server API (ฟรีและยังใช้งานได้)
    const encodedUrl = encodeURIComponent(referralUrl);
    const qrServerUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodedUrl}&format=png`;

    // สร้าง img element
    const img = document.createElement('img');
    img.src = qrServerUrl;
    img.alt = 'QR Code สำหรับลิงก์แนะนำ';
    img.width = 200;
    img.height = 200;
    img.style.borderRadius = '8px';
    img.id = 'qrcode-image';
    img.crossOrigin = 'anonymous'; // สำหรับดาวน์โหลด

    img.onload = function() {
        qrcodeContainer.innerHTML = '';
        qrcodeContainer.appendChild(img);
        if (downloadBtn) {
            downloadBtn.style.display = 'inline-flex';
        }
        console.log('สร้าง QR Code ด้วย QR Server API สำเร็จ');
    };

    img.onerror = function() {
        // แสดง error message
        if (errorEl) {
            errorEl.style.display = 'block';
        }
        qrcodeContainer.innerHTML = '<p style="color:var(--ink2); font-size:13px;">ไม่สามารถโหลด QR Code ได้</p>';
        console.error('ไม่สามารถโหลด QR Code จาก QR Server API');
    };
}

// รอให้ DOM พร้อมก่อนสร้าง QR Code
document.addEventListener('DOMContentLoaded', function() {
    // รอเพิ่มอีกนิดให้ library โหลดเสร็จ
    setTimeout(generateQRCode, 100);
});

function notify(message, type) {
    if (window.showNotification) {
        window.showNotification(message, type || 'success');
    }
}

function copyUrl() {
    const input = document.getElementById('referralUrl');
    input.select();
    navigator.clipboard.writeText(referralUrl).then(() => {
        notify('คัดลอกลิงก์แล้ว', 'success');
    }).catch(() => {
        notify('คัดลอกไม่สำเร็จ', 'error');
    });
}

function copyCode() {
    const input = document.getElementById('memberCode');
    input.select();
    navigator.clipboard.writeText(memberCode).then(() => {
        notify('คัดลอกรหัสแล้ว', 'success');
    }).catch(() => {
        notify('คัดลอกไม่สำเร็จ', 'error');
    });
}

function downloadQR() {
    const qrcodeContainer = document.getElementById('qrcode');
    const canvas = qrcodeContainer.querySelector('canvas');
    const img = qrcodeContainer.querySelector('img');

    let dataUrl;

    if (canvas) {
        // QRCode.js สร้าง canvas
        dataUrl = canvas.toDataURL('image/png');
    } else if (img) {
        // Fallback ใช้ img จาก QR Server API
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = img.width;
        tempCanvas.height = img.height;
        const ctx = tempCanvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        dataUrl = tempCanvas.toDataURL('image/png');
    }

    if (dataUrl) {
        const link = document.createElement('a');
        link.download = `referral-qr-${memberCode}.png`;
        link.href = dataUrl;
        link.click();
        notify('ดาวน์โหลด QR Code แล้ว', 'success');
    } else {
        notify('ไม่สามารถดาวน์โหลด QR Code ได้', 'error');
    }
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
