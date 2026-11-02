{{--
    ลิงก์แนะนำ MLM + QR (ธีมโนวาเต็ม) — MlmDashboardController::referral
    ตัวแปร: $member, $referralUrl (หน้าสมัครแบบเพิ่มเพื่อน LINE OA ก่อน · ?ref=รหัสสมาชิก)
    - QR วาดในเบราว์เซอร์ด้วย qrcodejs (ลิงก์เป็น ASCII ล้วน) — เลิกส่งลิงก์ไปให้ api.qrserver.com วาดแทนแล้ว
--}}
@extends('layouts.user-nova')

@section('title', 'ลิงก์แนะนำ MLM')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $me = auth()->user();
    $initial = mb_strtoupper(mb_substr(trim((string) $me->name) ?: 'U', 0, 1));
    $lineText = "มาร่วมเป็นส่วนหนึ่งกับเรา!\n".$referralUrl;
    $mailBody = "สวัสดี!\n\nขอชวนมาร่วมทีมกับเรา สมัครได้ที่: ".$referralUrl."\n\nรหัสแนะนำ: ".$member->member_code;
    $shares = [
        ['line', 'LINE', 'https://line.me/R/msg/text/?'.rawurlencode($lineText)],
        ['fb', 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($referralUrl)],
        ['send', 'X (Twitter)', 'https://twitter.com/intent/tweet?text='.rawurlencode('มาร่วมเป็นส่วนหนึ่งกับเรา!').'&url='.rawurlencode($referralUrl)],
        ['bell', 'อีเมล', 'mailto:?subject='.rawurlencode('ชวนมาร่วมทีม').'&body='.rawurlencode($mailBody)],
    ];
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page" x-data="nvmMlReferralQr(@js($referralUrl), @js($member->member_code))">

<section class="nvm-ph" aria-labelledby="nvm-mlr-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Invite friends</div>
            <h1 id="nvm-mlr-h">ลิงก์แนะนำของคุณ</h1>
            <p class="nvm-ph__lead">แชร์ลิงก์หรือให้เพื่อนสแกน QR เพื่อนที่สมัครจะอยู่ในทีมของคุณอัตโนมัติ</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/gift.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box nvm-qr" aria-label="QR Code ลิงก์แนะนำ">
        <div class="nvm-qr__who">
            <span class="nvm-avatar">{{ $initial }}</span>
            <b>{{ $me->name }}</b>
            <small class="nvm-num">รหัสแนะนำ {{ $member->member_code }}</small>
        </div>

        <div class="nvm-qr__box">
            <div x-ref="qr" role="img" aria-label="QR Code สำหรับสมัครสมาชิกผ่านลิงก์แนะนำ"></div>
            <span class="nvm-qr__tag">สแกนเพื่อสมัคร</span>
        </div>
        <p class="nvm-err nvm-gap-t" x-show="qrError" x-cloak x-text="qrError"></p>

        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--gold" @click="download()" :disabled="! ready"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>บันทึกรูป QR</button>
            <button type="button" class="nvm-btn nvm-btn--line" @click="share()"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>แชร์</button>
        </div>
    </section>

    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlr-l-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Your link</small><h2 id="nvm-mlr-l-h">ลิงก์และรหัสแนะนำ</h2></div></div>
            <div class="nvm-field">
                <span class="nvm-label">ลิงก์สมัครสมาชิก</span>
                <div class="nvm-copy nvm-copy--box">
                    <code>{{ $referralUrl }}</code>
                    <button type="button" @click="nvmCopy(url, 'คัดลอกลิงก์แนะนำแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                </div>
            </div>
            <div class="nvm-field nvm-gap-t">
                <span class="nvm-label">รหัสสมาชิก (กรอกตอนสมัครแทนลิงก์ได้)</span>
                <div class="nvm-copy nvm-copy--box">
                    <code class="nvm-num">{{ $member->member_code }}</code>
                    <button type="button" @click="nvmCopy(code, 'คัดลอกรหัสสมาชิกแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                </div>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlr-s-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Share</small><h2 id="nvm-mlr-s-h">แชร์ผ่าน</h2></div></div>
            <div class="nvm-ml-shares">
                @foreach($shares as [$icon, $label, $href])
                    <a href="{{ $href }}" @if(! str_starts_with($href, 'mailto:')) target="_blank" rel="noopener" @endif>
                        <svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg>{{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlr-t-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-mlr-t-h">เคล็ดลับการแนะนำ</h2></div></div>
            <ol class="nvm-steps">
                <li>เลือกช่องทางที่เพื่อนของคุณใช้งานมากที่สุด</li>
                <li>เล่าประสบการณ์จริงของคุณ ไม่สัญญารายได้เกินจริง</li>
                <li>ช่วยเหลือและให้คำแนะนำสมาชิกใหม่หลังสมัคร</li>
            </ol>
        </section>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
/**
 * QR ลิงก์แนะนำ — วาด/บันทึกรูป/แชร์
 */
function nvmMlReferralQr(url, code) {
    return {
        url: url,
        code: code,
        ready: false,
        qrError: '',

        init() {
            this.$nextTick(() => this.draw());
        },

        draw() {
            const box = this.$refs.qr;
            if (typeof QRCode === 'undefined') {
                this.qrError = 'โหลดตัวสร้าง QR ไม่สำเร็จ รีเฟรชหน้าอีกครั้ง หรือใช้ปุ่มคัดลอกลิงก์แทน';
                return;
            }
            box.innerHTML = '';
            try {
                new QRCode(box, { text: this.url, width: 464, height: 464, colorDark: '#0b1633', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
                this.ready = true;
            } catch (e) {
                this.qrError = 'สร้าง QR ไม่สำเร็จ ใช้ปุ่มคัดลอกลิงก์แทน';
            }
        },

        download() {
            const canvas = this.$refs.qr.querySelector('canvas');
            if (! canvas) { window.showNotification('ยังสร้าง QR ไม่เสร็จ', 'error'); return; }
            const a = document.createElement('a');
            a.download = 'qrcode-' + this.code + '.png';
            a.href = canvas.toDataURL('image/png');
            a.click();
            window.showNotification('บันทึกรูป QR แล้ว', 'success');
        },

        share() {
            if (navigator.share) {
                navigator.share({ title: 'มาร่วมทีมกับฉัน', text: 'สมัครสมาชิกผ่านลิงก์แนะนำของฉัน', url: this.url }).catch(() => {});
                return;
            }
            window.nvmCopy(this.url, 'คัดลอกลิงก์แนะนำแล้ว');
        },
    };
}
</script>
@endpush
