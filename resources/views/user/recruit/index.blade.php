{{--
    หน้าแนะนำสมาชิก (Recruit) — จัดการข้อมูลที่แสดง + QR/ลิงก์แชร์ (ธีมโนวาเต็ม)
    - RecruitPageController::index สร้าง customization ให้อัตโนมัติถ้ายังไม่มี
    - ชื่อฟิลด์ custom_name / custom_phone / custom_address / custom_pitch / custom_image / is_active ตรงกับ ::update
    - 🐛 (2026-11-02) is_active เดิมเป็น checkbox เดี่ยว — เอาติ๊กออกแล้วเบราว์เซอร์ไม่ส่งค่า
      = ปิดหน้า Recruit ไม่ได้เลย · ตอนนี้มี hidden is_active=0 นำหน้าเสมอ
--}}
@extends('layouts.user-nova')

@section('title', 'หน้าแนะนำสมาชิก')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $memberCode = $mlmMember?->member_code;
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-rc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Recruit page</div>
            <h1 id="nvm-rc-h">หน้าแนะนำสมาชิก</h1>
            <p class="nvm-ph__lead">แต่งหน้าชวนสมัครของคุณ แล้วแชร์ลิงก์หรือ QR Code ให้เพื่อน ระบบจะติดตามผู้สนใจให้</p>
            <div class="nvm-pills">
                @if($customization->is_active)
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>เปิดใช้งานอยู่</span>
                @else
                    <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ปิดใช้งานอยู่</span>
                @endif
            </div>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="{{ $recruitUrl }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูหน้า Recruit</a>
                <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.marketing.recruit.leads') }}"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>ผู้มุ่งหวัง</a>
                <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.marketing.recruit.analytics') }}"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>สถิติการตลาด</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/mascot/welcome.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

<section class="nvm-tr-stats" aria-label="สถิติหน้า Recruit">
    <div class="nvm-mini"><small>การเข้าชม</small><b class="nvm-num">{{ number_format($stats['total_views']) }}</b><small>+{{ number_format($stats['views_last_7_days']) }} ใน 7 วัน</small></div>
    <div class="nvm-mini"><small>ผู้มุ่งหวัง</small><b class="nvm-num is-gold">{{ number_format($stats['total_leads']) }}</b><small>+{{ number_format($stats['leads_last_7_days']) }} ใน 7 วัน</small></div>
    <div class="nvm-mini"><small>สมาชิกใหม่</small><b class="nvm-num is-in">{{ number_format($stats['total_conversions']) }}</b><small>คนสมัครสำเร็จ</small></div>
    <div class="nvm-mini"><small>อัตราสำเร็จ</small><b class="nvm-num is-info">{{ number_format((float) $stats['conversion_rate'], 1) }}%</b><small>Conversion rate</small></div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-rc-form-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Your details</small><h2 id="nvm-rc-form-h">ข้อมูลที่แสดงบนหน้า Recruit</h2></div></div>

        <form action="{{ route('user.marketing.recruit.update') }}" method="POST" enctype="multipart/form-data" class="nvm-form" x-data="{ sending: false }" @submit="sending = true">
            @csrf
            @method('PUT')

            <div class="nvm-tr-grid">
                <div class="nvm-field">
                    <label class="nvm-label" for="custom_name">ชื่อที่แสดง</label>
                    <input type="text" id="custom_name" name="custom_name" maxlength="255" value="{{ old('custom_name', $customization->custom_name) }}" class="nvm-input @error('custom_name') is-bad @enderror" placeholder="เว้นว่างไว้ = ใช้ชื่อจากโปรไฟล์">
                    @error('custom_name')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="custom_phone">เบอร์โทรติดต่อ</label>
                    <input type="tel" id="custom_phone" name="custom_phone" maxlength="20" value="{{ old('custom_phone', $customization->custom_phone) }}" class="nvm-input @error('custom_phone') is-bad @enderror" placeholder="เบอร์ให้ผู้สนใจโทรหา">
                    @error('custom_phone')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field is-wide">
                    <label class="nvm-label" for="custom_address">ที่อยู่</label>
                    <textarea id="custom_address" name="custom_address" rows="3" class="nvm-input @error('custom_address') is-bad @enderror" placeholder="ที่อยู่ที่ต้องการแสดง (ไม่บังคับ)">{{ old('custom_address', $customization->custom_address) }}</textarea>
                    @error('custom_address')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field is-wide">
                    <label class="nvm-label" for="custom_pitch">คำชักชวนส่วนตัว</label>
                    <textarea id="custom_pitch" name="custom_pitch" rows="5" maxlength="1000" class="nvm-input @error('custom_pitch') is-bad @enderror" placeholder="เขียนคำชวนของคุณเอง (เว้นว่างไว้ = ใช้ข้อความจากเทมเพลต)">{{ old('custom_pitch', $customization->custom_pitch) }}</textarea>
                    @error('custom_pitch')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field is-wide">
                    <label class="nvm-label" for="custom_image">รูปภาพ</label>
                    @if($customization->custom_image)
                        <img class="nvm-tr-thumb" src="{{ $customization->getDisplayImage() }}" alt="รูปปัจจุบัน" width="112" height="112" loading="lazy">
                    @endif
                    <input type="file" id="custom_image" name="custom_image" accept="image/jpeg,image/png,image/gif" class="nvm-input @error('custom_image') is-bad @enderror">
                    <span class="nvm-help">รองรับ JPG, PNG, GIF ขนาดไม่เกิน 2MB</span>
                    @error('custom_image')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field is-wide">
                    <input type="hidden" name="is_active" value="0">
                    <label class="nvm-tr-switch">
                        <span><b>เปิดใช้งานหน้า Recruit</b><small>ปิดไว้ = คนที่เปิดลิงก์จะไม่เห็นหน้าชวนสมัครของคุณ</small></span>
                        <span class="nvm-sw"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customization->is_active))><i></i></span>
                    </label>
                </div>
            </div>

            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="sending"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="sending ? 'กำลังบันทึก…' : 'บันทึกการเปลี่ยนแปลง'">บันทึกการเปลี่ยนแปลง</span></button>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-rc-share-h"
           x-data="nvmTrRecruitShare(@js($recruitUrl), @js((string) $memberCode))">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Share</small><h2 id="nvm-rc-share-h">QR Code และลิงก์แนะนำ</h2></div></div>

        <div class="nvm-tr-share">
            <div class="nvm-qr">
                <div class="nvm-qr__box">
                    <div x-ref="qr"></div>
                    <div class="nvm-qr__miss" x-show="qrFailed" x-cloak>
                        <svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>
                        สร้าง QR ไม่สำเร็จ
                        <small>รีเฟรชหน้าอีกครั้ง หรือคัดลอกลิงก์ด้านล่างแทน</small>
                    </div>
                    <span class="nvm-qr__tag">สแกนเพื่อสมัคร</span>
                </div>
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="downloadQr()" :disabled="qrFailed"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ดาวน์โหลด QR Code</button>
            </div>

            <div>
                <span class="nvm-label">ลิงก์หน้า Recruit</span>
                <div class="nvm-copy nvm-copy--box">
                    <code>{{ $recruitUrl }}</code>
                    <button type="button" @click="nvmCopy(url, 'คัดลอกลิงก์แล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                </div>

                @if($memberCode)
                    <span class="nvm-label nvm-gap-t">รหัสสมาชิก</span>
                    <div class="nvm-copy nvm-copy--box">
                        <code>{{ $memberCode }}</code>
                        <button type="button" @click="nvmCopy(code, 'คัดลอกรหัสสมาชิกแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                    </div>
                @endif

                <span class="nvm-label nvm-gap-t">แชร์ผ่าน</span>
                <div class="nvm-tr-channels">
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="share('line')"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>LINE</button>
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="share('facebook')"><svg class="nvm-ico"><use href="#nvm-i-fb"/></svg>Facebook</button>
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="share('x')"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>X</button>
                </div>
            </div>
        </div>
    </aside>
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    // แชร์หน้า Recruit: สร้าง QR จากลิงก์ (ตัวอักษรละตินล้วน — qrcodejs นับอักษรไทยผิด) + ปุ่มแชร์/ดาวน์โหลด
    function nvmTrRecruitShare(url, code) {
        return {
            url: url,
            code: code,
            qrFailed: false,
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
                link.download = 'recruit-qr-' + (this.code || 'link') + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            },
            share(platform) {
                const message = 'มาร่วมเป็นส่วนหนึ่งกับทีมของฉัน!';
                const links = {
                    line: 'https://line.me/R/msg/text/?' + encodeURIComponent(message + '\n' + this.url),
                    facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(this.url),
                    x: 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(message) + '&url=' + encodeURIComponent(this.url),
                };
                if (links[platform]) window.open(links[platform], '_blank', 'noopener');
            },
        };
    }
</script>
@endpush
