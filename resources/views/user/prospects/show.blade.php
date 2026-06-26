@extends('layouts.user-v4')

@section('title', 'รายละเอียดผู้มุ่งหวัง')

@php
    use Illuminate\Support\Str;

    [$stLabel, $stColor, $stDesc] = match ($prospect->status) {
        'pending' => ['รอดำเนินการ', '#e0a52e', 'รอผู้มุ่งหวังคลิกลิงก์'],
        'in_progress' => ['กำลังดำเนินการ', '#5689b8', 'อยู่ระหว่างการสมัคร'],
        'completed' => ['สำเร็จ', '#5aa07e', 'สมัครสมาชิกเรียบร้อยแล้ว'],
        'expired' => ['หมดอายุ', '#d9534f', 'ลิงก์หมดอายุแล้ว'],
        default => [$prospect->status, 'var(--ink2)', ''],
    };
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:1000px; margin-inline:auto;" x-data="prospectShare()">

    {{-- หัวข้อ + ปุ่มจัดการ --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <a href="{{ route('user.prospects.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:22px;"><i class="fas fa-user-check" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:180px;">
                <h1 style="font-size:clamp(18px,4vw,24px); font-weight:800; margin:0;">รายละเอียดผู้มุ่งหวัง</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">แชร์ลิงก์เชิญและติดตามสถานะการสมัคร</div>
            </div>
            @if(in_array($prospect->status, ['pending', 'expired']))
                <div style="display:flex; gap:8px;">
                    <form method="POST" action="{{ route('user.prospects.renew', $prospect->id) }}">
                        @csrf
                        <button type="submit" class="tp-btn"><i class="fas fa-rotate"></i> ต่ออายุ</button>
                    </form>
                    <form method="POST" action="{{ route('user.prospects.destroy', $prospect->id) }}" onsubmit="return confirm('ลบลิงก์เชิญนี้?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="tp-btn" style="color:#d9534f;"><i class="fas fa-trash"></i> ลบ</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- สถานะ + โปรไฟล์ --}}
    <div class="tp-card" style="padding:20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
        <div style="display:flex; align-items:center; gap:14px;">
            @if($prospect->line_profile_picture)
                <img src="{{ $prospect->line_profile_picture }}" alt="" style="width:64px; height:64px; border-radius:50%; object-fit:cover; box-shadow:var(--raise);">
            @else
                <span class="tp-tile" style="width:64px; height:64px; border-radius:50%; font-size:26px;"><i class="fab fa-line" style="color:#06c755;"></i></span>
            @endif
            <div>
                <div style="font-size:20px; font-weight:800;">{{ $prospect->line_display_name ?? 'รอเริ่มสมัคร' }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:2px;">สร้างเมื่อ: {{ $prospect->created_at->format('d/m/Y H:i น.') }}</div>
                @if($prospect->line_user_id)<div class="tp-num" style="font-size:11px; color:var(--ink2);">LINE ID: {{ Str::limit($prospect->line_user_id, 25) }}</div>@endif
            </div>
        </div>
        <div style="text-align:right;">
            <span class="tp-pill" style="color:#fff; background:{{ $stColor }}; font-size:12.5px; padding:6px 14px;">{{ $stLabel }}</span>
            @if($stDesc)<div style="font-size:12px; color:var(--ink2); margin-top:6px;">{{ $stDesc }}</div>@endif
        </div>
    </div>

    @if($prospect->status !== 'completed')
        {{-- ลิงก์เชิญ + QR + แชร์ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:14px;">🔗 ลิงก์เชิญของคุณ</div>

            <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">URL ลิงก์เชิญ</label>
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px;">
                <input type="text" id="invitation-url" value="{{ $invitationUrl }}" readonly x-ref="urlInput" class="tp-input tp-num" style="flex:1; min-width:200px; font-size:12.5px;">
                <button @click="copyToClipboard(invitationUrl)" class="tp-btn tp-btn-primary"><i class="fas fa-copy"></i> <span x-text="copied ? 'คัดลอกแล้ว!' : 'คัดลอกลิงก์'"></span></button>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:20px;">
                {{-- QR --}}
                <div style="text-align:center; flex:1; min-width:220px;">
                    <div style="display:inline-block; background:#fff; padding:18px; border-radius:18px; box-shadow:var(--raise);">
                        <div id="qrcode" style="margin:0 auto;"></div>
                    </div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:10px;">สแกน QR Code เพื่อเปิดลิงก์เชิญ</div>
                    <button @click="downloadQR()" class="tp-btn tp-btn-sm" style="margin-top:8px;"><i class="fas fa-download"></i> ดาวน์โหลด QR Code</button>
                </div>

                {{-- แชร์ --}}
                <div style="flex:1; min-width:220px;">
                    <div style="font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:10px;">แชร์ผ่านช่องทางต่างๆ</div>
                    <div style="display:flex; flex-direction:column; gap:9px;">
                        <button @click="shareViaLine()" class="tp-btn" style="justify-content:flex-start; background:#06c755; color:#fff;"><i class="fab fa-line"></i> แชร์ทาง LINE</button>
                        <button @click="shareViaFacebook()" class="tp-btn" style="justify-content:flex-start; background:#1877f2; color:#fff;"><i class="fab fa-facebook"></i> แชร์ทาง Facebook</button>
                        <button @click="shareViaTwitter()" class="tp-btn" style="justify-content:flex-start; background:#1d9bf0; color:#fff;"><i class="fab fa-x-twitter"></i> แชร์ทาง X (Twitter)</button>
                        <button @click="shareNative()" x-show="canShare" class="tp-btn tp-btn-primary" style="justify-content:flex-start;"><i class="fas fa-share-nodes"></i> แชร์ช่องทางอื่นๆ</button>
                    </div>
                </div>
            </div>

            @if($prospect->expires_at)
                <div style="margin-top:18px; padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm); font-size:12.5px;">
                    <i class="fas fa-clock" style="color:#e0a52e;"></i>
                    ลิงก์นี้หมดอายุ: <strong>{{ \Carbon\Carbon::parse($prospect->expires_at)->format('d/m/Y H:i น.') }}</strong>
                    <span style="color:var(--ink2);">(เหลืออีก {{ \Carbon\Carbon::parse($prospect->expires_at)->diffForHumans(null, true) }})</span>
                </div>
            @endif
        </div>

        {{-- เครื่องมือติดตาม --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:14px;">🔔 เครื่องมือติดตาม</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px;">
                <form method="POST" action="{{ route('user.prospects.resend', $prospect->id) }}">
                    @csrf
                    <button type="submit" style="width:100%; border:0; cursor:pointer; font-family:inherit; text-align:left; padding:14px; border-radius:14px; box-shadow:var(--inset-sm); background:transparent; display:flex; align-items:center; gap:11px;">
                        <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px; background:#e0a52e;"><i class="fas fa-comment-dots" style="color:#fff;"></i></span>
                        <span><span style="display:block; font-weight:700; font-size:13px; color:var(--ink);">บันทึกการติดตาม</span><span style="display:block; font-size:11.5px; color:var(--ink2);">บันทึกว่าได้ติดต่อผู้มุ่งหวังแล้ว</span></span>
                    </button>
                </form>
                <button @click="copyMessage()" style="width:100%; border:0; cursor:pointer; font-family:inherit; text-align:left; padding:14px; border-radius:14px; box-shadow:var(--inset-sm); background:transparent; display:flex; align-items:center; gap:11px;">
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px; background:#5689b8;"><i class="fas fa-clipboard" style="color:#fff;"></i></span>
                    <span><span style="display:block; font-weight:700; font-size:13px; color:var(--ink);">คัดลอกข้อความเชิญ</span><span style="display:block; font-size:11.5px; color:var(--ink2);">ข้อความพร้อมลิงก์สำหรับส่งต่อ</span></span>
                </button>
            </div>
            @if($prospect->last_reminded_at)
                <div style="margin-top:12px; font-size:12px; color:var(--ink2);"><i class="fas fa-clock"></i> ติดตามล่าสุด: {{ \Carbon\Carbon::parse($prospect->last_reminded_at)->format('d/m/Y H:i น.') }}</div>
            @endif
        </div>
    @endif

    {{-- สมัครสำเร็จ --}}
    @if($prospect->registeredUser)
        <div class="tp-card" style="padding:20px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                <span class="tp-tile" style="width:44px; height:44px; border-radius:13px; font-size:18px; background:#5aa07e;"><i class="fas fa-circle-check" style="color:#fff;"></i></span>
                <div>
                    <div style="font-weight:800; font-size:16px;">สมัครสมาชิกเรียบร้อยแล้ว!</div>
                    <div style="font-size:12.5px; color:var(--ink2);">ผู้มุ่งหวังของคุณได้กลายเป็นสมาชิกในทีมแล้ว</div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:14px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:50%; font-size:22px; background:#5aa07e; color:#fff;">{{ mb_substr($prospect->registeredUser->name, 0, 1) }}</span>
                <div>
                    <div style="font-size:17px; font-weight:700;">{{ $prospect->registeredUser->name }}</div>
                    <div style="font-size:12.5px; color:var(--ink2);">{{ $prospect->registeredUser->email }}</div>
                    <span class="tp-pill" style="margin-top:5px; color:#fff; background:#5aa07e;">สมาชิกในทีมของคุณ</span>
                </div>
            </div>
            <div style="margin-top:14px; padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm); font-size:12.5px; color:var(--ink2);">🎉 ยินดีด้วย! การแนะนำของคุณสำเร็จ คุณจะได้รับค่าคอมมิชชั่นตามเงื่อนไขของแผนรายได้</div>
        </div>
    @endif

    {{-- ข้อมูลรายละเอียด --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">📄 ข้อมูลรายละเอียด</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
            <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                <div style="font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.3px;">Referral Token</div>
                <code class="tp-num" style="display:block; margin-top:5px; font-size:12px; word-break:break-all;">{{ $prospect->referral_token }}</code>
            </div>
            @if($prospect->current_step)
                <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase;">ขั้นตอนปัจจุบัน</div><div style="font-size:13px; font-weight:600; margin-top:5px;">{{ $prospect->current_step }}</div></div>
            @endif
            <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase;">สร้างเมื่อ</div><div class="tp-num" style="font-size:13px; font-weight:600; margin-top:5px;">{{ $prospect->created_at->format('d/m/Y H:i น.') }}</div></div>
            <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase;">อัพเดทล่าสุด</div><div class="tp-num" style="font-size:13px; font-weight:600; margin-top:5px;">{{ $prospect->updated_at->format('d/m/Y H:i น.') }}</div></div>
            @if($prospect->click_count)
                <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase;">จำนวนคลิก</div><div class="tp-num" style="font-size:13px; font-weight:600; margin-top:5px;">{{ number_format($prospect->click_count) }} ครั้ง</div></div>
            @endif
            @if($prospect->last_visit_at)
                <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase;">เข้าชมล่าสุด</div><div class="tp-num" style="font-size:13px; font-weight:600; margin-top:5px;">{{ \Carbon\Carbon::parse($prospect->last_visit_at)->format('d/m/Y H:i น.') }}</div></div>
            @endif
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function prospectShare() {
    return {
        copied: false,
        canShare: typeof navigator.share === 'function',
        invitationUrl: @json($invitationUrl),
        qrCodeInstance: null,
        init() {
            @if($prospect->status !== 'completed')
                if (typeof QRCode !== 'undefined' && document.getElementById('qrcode')) {
                    this.qrCodeInstance = new QRCode(document.getElementById('qrcode'), {
                        text: this.invitationUrl, width: 200, height: 200,
                        colorDark: '#1F2937', colorLight: '#FFFFFF', correctLevel: QRCode.CorrectLevel.H
                    });
                }
            @endif
        },
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 3000);
                if (window.showNotification) window.showNotification('คัดลอกลิงก์แล้ว', 'success');
            });
        },
        copyMessage() {
            const message = 'สวัสดีครับ/ค่ะ 👋\n\nมีโอกาสดีๆ มาแนะนำ! มาร่วมเป็นส่วนหนึ่งของทีมเรากันเถอะ\n\n🔗 คลิกลิงก์นี้เพื่อสมัครเลย:\n' + this.invitationUrl + '\n\nหากมีข้อสงสัยสอบถามได้เลยนะครับ/ค่ะ 💬';
            navigator.clipboard.writeText(message).then(() => {
                if (window.showNotification) window.showNotification('คัดลอกข้อความเรียบร้อยแล้ว', 'success');
            });
        },
        shareViaLine() {
            window.open('https://line.me/R/msg/text/?' + encodeURIComponent('มาสมัครสมาชิกกับเราสิ! คลิกลิงก์นี้เลย: ' + this.invitationUrl), '_blank');
        },
        shareViaFacebook() {
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(this.invitationUrl), '_blank');
        },
        shareViaTwitter() {
            window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent('มาสมัครสมาชิกกับเราสิ!') + '&url=' + encodeURIComponent(this.invitationUrl), '_blank');
        },
        shareNative() {
            if (navigator.share) {
                navigator.share({ title: 'เชิญสมัครสมาชิก', text: 'มาสมัครสมาชิกกับเราสิ! คลิกลิงก์นี้เลย', url: this.invitationUrl });
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
