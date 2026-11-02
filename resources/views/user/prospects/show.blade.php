{{--
    รายละเอียดผู้มุ่งหวัง + แชร์ลิงก์เชิญ (ธีมโนวาเต็ม)
    - MlmProspectController::show ดึงด้วย sponsor_mlm_member_id ของสมาชิกคนนี้ (ของคนอื่น = 404)
    - 🐛 (2026-11-02) เดิมอ่านคอลัมน์ที่ไม่มีจริง (line_profile_picture, expires_at, last_reminded_at,
      current_step, click_count, last_visit_at) → รูป/วันหมดอายุ/จำนวนคลิกไม่เคยขึ้น
      ตอนนี้ใช้คอลัมน์จริง: line_picture_url, locked_until, conversation_step, visit_count, last_visited_at
    - 🔒 ไม่โชว์อีเมลของผู้ที่สมัครแล้ว และไม่โชว์ LINE user ID ของผู้มุ่งหวัง
--}}
@extends('layouts.user-nova')

@section('title', 'รายละเอียดผู้มุ่งหวัง')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    [$stLabel, $stPill, $stDesc] = match ($prospect->status) {
        'pending' => ['รอดำเนินการ', 'warn', 'รอผู้มุ่งหวังคลิกลิงก์'],
        'in_progress' => ['กำลังดำเนินการ', 'glass', 'อยู่ระหว่างการสมัคร'],
        'completed' => ['สำเร็จ', 'ok', 'สมัครสมาชิกเรียบร้อยแล้ว'],
        'expired' => ['หมดอายุ', 'bad', 'ลิงก์หมดอายุแล้ว ต่ออายุได้'],
        'cancelled' => ['ยกเลิก', 'bad', ''],
        default => [$prospect->status, 'glass', ''],
    };
    $canManage = in_array($prospect->status, ['pending', 'expired'], true);
    $lockActive = $prospect->is_locked && $prospect->locked_until && $prospect->locked_until->isFuture();
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-pps-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.prospects.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ผู้มุ่งหวังของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Prospect</div>
            <h1 id="nvm-pps-h">{{ $prospect->line_display_name ?? 'รอเริ่มสมัคร' }}</h1>
            <p class="nvm-ph__lead">สร้างลิงก์เมื่อ <span class="nvm-num">{{ $thDate($prospect->created_at) }}</span>@if($stDesc) · {{ $stDesc }}@endif</p>
            <div class="nvm-pills"><span class="nvm-pill nvm-pill--{{ $stPill }}">{{ $stLabel }}</span></div>
            @if($canManage)
                <div class="nvm-hero__actions">
                    <form method="POST" action="{{ route('user.prospects.renew', $prospect->id) }}">
                        @csrf
                        <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ต่ออายุลิงก์</button>
                    </form>
                    <form method="POST" action="{{ route('user.prospects.destroy', $prospect->id) }}" x-data @submit="if (! confirm('ลบลิงก์เชิญนี้?')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="nvm-btn nvm-btn--ghost nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบลิงก์</button>
                    </form>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">
        @if($prospect->status !== 'completed')
            <section class="nvm-card nvm-box" aria-labelledby="nvm-pps-share-h" x-data="nvmTrProspectShare(@js($invitationUrl))">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Invitation</small><h2 id="nvm-pps-share-h">ลิงก์เชิญของคุณ</h2></div></div>

                <div class="nvm-tr-share">
                    <div class="nvm-qr">
                        <div class="nvm-qr__box">
                            <div x-ref="qr"></div>
                            <div class="nvm-qr__miss" x-show="qrFailed" x-cloak>
                                <svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>
                                สร้าง QR ไม่สำเร็จ
                                <small>รีเฟรชหน้าอีกครั้ง หรือคัดลอกลิงก์แทน</small>
                            </div>
                            <span class="nvm-qr__tag">สแกนเพื่อสมัคร</span>
                        </div>
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="downloadQr()" :disabled="qrFailed"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ดาวน์โหลด QR Code</button>
                    </div>

                    <div>
                        <span class="nvm-label">URL ลิงก์เชิญ</span>
                        <div class="nvm-copy nvm-copy--box">
                            <code>{{ $invitationUrl }}</code>
                            <button type="button" @click="nvmCopy(url, 'คัดลอกลิงก์แล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                        </div>

                        <span class="nvm-label nvm-gap-t">แชร์ผ่าน</span>
                        <div class="nvm-tr-channels">
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="share('line')"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>LINE</button>
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="share('facebook')"><svg class="nvm-ico"><use href="#nvm-i-fb"/></svg>Facebook</button>
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="share('x')"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>X</button>
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-show="canShare" x-cloak @click="shareNative()"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>ช่องทางอื่น</button>
                        </div>

                        @if($lockActive)
                            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ผู้มุ่งหวังคนนี้ถูกล็อกเป็นของคุณถึง <span class="nvm-num">{{ $thDate($prospect->locked_until) }}</span> (เหลือ {{ $prospect->locked_until->locale('th')->diffForHumans(null, true) }})</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="nvm-card nvm-box" aria-labelledby="nvm-pps-tool-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Follow up</small><h2 id="nvm-pps-tool-h">เครื่องมือติดตาม</h2></div></div>
                <div class="nvm-tr-tools">
                    <form method="POST" action="{{ route('user.prospects.resend', $prospect->id) }}">
                        @csrf
                        <button type="submit" class="nvm-tr-tool">
                            <span class="nvm-tx__ic is-in"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg></span>
                            <span><b>บันทึกการติดตาม</b><small>บันทึกว่าได้ติดต่อผู้มุ่งหวังแล้ว</small></span>
                        </button>
                    </form>
                    <button type="button" class="nvm-tr-tool" x-data @click="nvmCopy(@js("สวัสดีครับ/ค่ะ\n\nมีโอกาสดีๆ มาแนะนำ! มาร่วมเป็นส่วนหนึ่งของทีมเรากันเถอะ\n\nคลิกลิงก์นี้เพื่อสมัครเลย:\n".$invitationUrl."\n\nหากมีข้อสงสัยสอบถามได้เลยนะครับ/ค่ะ"), 'คัดลอกข้อความเชิญแล้ว')">
                        <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg></span>
                        <span><b>คัดลอกข้อความเชิญ</b><small>ข้อความพร้อมลิงก์ สำหรับส่งต่อ</small></span>
                    </button>
                </div>
            </section>
        @endif

        @if($prospect->registeredUser)
            <section class="nvm-card nvm-alert nvm-alert--ok" role="status">
                <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
                <div class="nvm-alert__txt">
                    <b>สมัครสมาชิกเรียบร้อยแล้ว!</b>
                    <p>{{ $prospect->registeredUser->name }} เข้าร่วมทีมของคุณแล้ว@if($prospect->registered_at) เมื่อ <span class="nvm-num">{{ $thDate($prospect->registered_at) }}</span>@endif · คุณจะได้รับค่าคอมมิชชั่นตามเงื่อนไขของแผนรายได้</p>
                </div>
            </section>
        @endif
    </div>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-pps-info-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Details</small><h2 id="nvm-pps-info-h">ข้อมูลรายละเอียด</h2></div></div>
        <div class="nvm-tr-profile">
            <span class="nvm-avatar">
                @if($prospect->line_picture_url)
                    <img src="{{ $prospect->line_picture_url }}" alt="" loading="lazy">
                @else
                    <svg class="nvm-ico"><use href="#nvm-i-line"/></svg>
                @endif
            </span>
            <div>
                <b>{{ $prospect->line_display_name ?? 'ยังไม่มีคนกดลิงก์' }}</b>
                <small>{{ $stLabel }}</small>
            </div>
        </div>
        <dl class="nvm-kv nvm-gap-t">
            <dt>สร้างเมื่อ</dt><dd class="nvm-num">{{ $thDate($prospect->created_at) }}</dd>
            <dt>อัพเดทล่าสุด</dt><dd class="nvm-num">{{ $thDate($prospect->updated_at) }}</dd>
            <dt>เปิดลิงก์</dt><dd class="nvm-num">{{ number_format((int) $prospect->visit_count) }} ครั้ง</dd>
            @if($prospect->clicked_at)
                <dt>กดลิงก์ครั้งแรก</dt><dd class="nvm-num">{{ $thDate($prospect->clicked_at) }}</dd>
            @endif
            @if($prospect->last_visited_at)
                <dt>เข้าชมล่าสุด</dt><dd class="nvm-num">{{ $thDate($prospect->last_visited_at) }}</dd>
            @endif
            @if($prospect->conversation_step)
                <dt>ขั้นตอนปัจจุบัน</dt><dd>{{ $prospect->conversation_step }}</dd>
            @endif
            <dt>Token</dt><dd><code class="nvm-tr-token">{{ $prospect->referral_token }}</code></dd>
        </dl>
    </aside>
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    // แชร์ลิงก์เชิญ: QR จาก URL (ละตินล้วน) + ปุ่มแชร์/ดาวน์โหลด
    function nvmTrProspectShare(url) {
        return {
            url: url,
            qrFailed: false,
            canShare: typeof navigator.share === 'function',
            init() {
                if (typeof QRCode === 'undefined') { this.qrFailed = true; return; }
                try {
                    new QRCode(this.$refs.qr, { text: this.url, width: 464, height: 464, colorDark: '#0b1633', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
                } catch (e) { this.qrFailed = true; }
            },
            downloadQr() {
                const canvas = this.$refs.qr.querySelector('canvas');
                if (! canvas) { window.showNotification('ยังไม่มี QR ให้ดาวน์โหลด', 'error'); return; }
                const link = document.createElement('a');
                link.download = 'invitation-qr-code.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            },
            share(platform) {
                const message = 'มาสมัครสมาชิกกับเราสิ! คลิกลิงก์นี้เลย';
                const links = {
                    line: 'https://line.me/R/msg/text/?' + encodeURIComponent(message + ': ' + this.url),
                    facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(this.url),
                    x: 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(message) + '&url=' + encodeURIComponent(this.url),
                };
                if (links[platform]) window.open(links[platform], '_blank', 'noopener');
            },
            shareNative() {
                if (navigator.share) navigator.share({ title: 'เชิญสมัครสมาชิก', text: 'มาสมัครสมาชิกกับเราสิ!', url: this.url }).catch(function () {});
            },
        };
    }
</script>
@endpush
