{{--
    รหัสกู้คืน 2FA (ธีมโนวาเต็ม)
    $codes มาจาก session flash เท่านั้น = แสดงครั้งเดียวหลังเปิด 2FA / สร้างชุดใหม่ (ไม่รับจาก URL)
    ไม่มีรหัสให้แสดง → บอกจำนวนที่ยังเหลือ ($remaining) แล้วให้สร้างชุดใหม่ (ต้องยืนยันรหัสจากแอปก่อน)
--}}
@extends('layouts.user-nova')

@section('title', 'รหัสกู้คืน 2FA')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $codes = array_values($codes ?? []);
    $remaining = (int) ($remaining ?? 0);
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-2fr-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.two-factor.setup') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตั้งค่า 2FA</a>
            <div class="nvm-eyebrow nvm-latin">Recovery codes</div>
            <h1 id="nvm-2fr-h">รหัสกู้คืน</h1>
            <p class="nvm-ph__lead">กุญแจสำรองเข้าบัญชี เมื่อใช้แอป Authenticator ไม่ได้ — มือถือหาย เปลี่ยนเครื่อง หรือเผลอลบแอป</p>
            <div class="nvm-bal">
                <small>{{ $codes ? 'รหัสชุดใหม่' : 'ยังไม่ได้ใช้' }}</small>
                <b class="nvm-foil nvm-num">{{ $codes ? count($codes) : $remaining }}<span> รหัส</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('key', 'deco/lantern') }}" alt="" width="200" height="200">
    </div>
</section>

@if($codes)
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>เก็บตอนนี้ — ออกจากหน้านี้แล้วจะดูซ้ำไม่ได้</b>
            <p>ระบบเก็บรหัสแบบเข้ารหัส ทีมงานก็ดูให้ไม่ได้ จดใส่กระดาษ ดาวน์โหลด หรือเก็บในตัวจัดการรหัสผ่าน และอย่าเก็บไว้ในมือถือเครื่องเดียวกับแอป</p>
        </div>
    </section>

    <div class="nvm-split" x-data="nvm2faCodes(@js($codes))">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-2fr-list-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Your codes</small><h2 id="nvm-2fr-list-h">รหัสกู้คืนของคุณ</h2></div>
                <span class="nvm-chip nvm-chip--gold nvm-num">{{ count($codes) }} รหัส</span>
            </div>
            <ol class="nvm-2fa-codes">
                @foreach($codes as $code)
                    <li>
                        <code class="nvm-num">{{ $code }}</code>
                        <button type="button" data-code="{{ $code }}" @click="nvmCopy($el.dataset.code, 'คัดลอกรหัสแล้ว')" aria-label="คัดลอกรหัสที่ {{ $loop->iteration }}"><svg class="nvm-ico" aria-hidden="true"><use href="#nvm-i-copy"/></svg></button>
                    </li>
                @endforeach
            </ol>
            <div class="nvm-2fa-codes__acts">
                <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" @click="download()"><i class="fa-solid fa-download" aria-hidden="true"></i>ดาวน์โหลด .txt</button>
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="copyAll()"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอกทั้งหมด</button>
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="print()"><i class="fa-solid fa-print" aria-hidden="true"></i>พิมพ์</button>
            </div>
            <div class="nvm-2fa-done">
                <label class="nvm-check">
                    <input type="checkbox" x-model="saved">
                    <span>ฉันเก็บรหัสทั้ง {{ count($codes) }} รหัสไว้ในที่ปลอดภัยแล้ว</span>
                </label>
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.two-factor.setup') }}" :class="saved ? '' : 'is-off'" :aria-disabled="saved ? 'false' : 'true'" @click="if (! saved) { $event.preventDefault(); nudge(); }">
                    <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>เสร็จสิ้น
                </a>
            </div>
        </section>

        <aside class="nvm-card nvm-box" aria-labelledby="nvm-2fr-how-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How to use</small><h2 id="nvm-2fr-how-h">ใช้รหัสกู้คืนอย่างไร</h2></div></div>
            <ol class="nvm-steps">
                <li>ตอนระบบขอรหัส 2FA ให้กด "ใช้รหัสกู้คืนแทน"</li>
                <li>พิมพ์รหัสที่ยังไม่เคยใช้ 1 รหัส — แต่ละรหัสใช้ได้ครั้งเดียว</li>
                <li>ใช้ไปแล้วหลายรหัส หรือสงสัยว่ามีคนเห็น ให้สร้างชุดใหม่ ชุดเดิมจะใช้ไม่ได้ทันที</li>
            </ol>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ทีมงานไม่เคยขอรหัสกู้คืน ใครขอ = มิจฉาชีพ</p>
        </aside>
    </div>
@else
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ $nvArt('key', 'deco/lantern') }}" alt="" width="86" height="86" loading="lazy">
            <b>ไม่มีรหัสให้แสดง</b>
            รหัสกู้คืนแสดงครั้งเดียวตอนสร้างเท่านั้น@if($remaining > 0) · ตอนนี้ยังเหลือรหัสที่ยังไม่ได้ใช้ {{ $remaining }} รหัส@endif
            <br>หาไม่เจอ? สร้างชุดใหม่ได้ ชุดเดิมจะใช้ไม่ได้อีก
            <div class="nvm-2fa-regen" x-data="{ ask: false, busy: false }" @pageshow.window="busy = false">
                <button type="button" class="nvm-btn nvm-btn--gold" x-show="! ask" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>สร้างรหัสชุดใหม่</button>
                <form method="POST" action="{{ route('user.two-factor.regenerate-recovery-codes') }}" class="nvm-confirm" x-show="ask" x-cloak @submit="busy = true">
                    @csrf
                    <p>รหัสเดิมทั้งหมดจะใช้ไม่ได้ทันที ระบบอาจขอรหัสจากแอปก่อน</p>
                    <div class="nvm-filter__btns">
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false" :disabled="busy">ยกเลิก</button>
                        <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm" :disabled="busy" x-text="busy ? 'กำลังสร้าง…' : 'ยืนยัน สร้างชุดใหม่'">ยืนยัน สร้างชุดใหม่</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', function () {
        // ป้องกันโค้ดแทรกตอนเขียนลงหน้าต่างพิมพ์ (document.write)
        var escHtml = function (v) {
            return String(v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
        };

        window.Alpine.data('nvm2faCodes', function (codes) {
            return {
                codes: codes, saved: false,
                stamp() { return new Date().toLocaleString('th-TH'); },
                text() {
                    return '=== รหัสกู้คืน 2FA · ' + @js(config('app.name', 'Thaiprompt')) + ' ===\n'
                        + 'สร้างเมื่อ: ' + this.stamp() + '\n'
                        + 'แต่ละรหัสใช้ได้ครั้งเดียว · อย่าบอกใคร\n\n'
                        + this.codes.map(function (c, i) { return (i + 1) + '. ' + c; }).join('\n') + '\n';
                },
                copyAll() { window.nvmCopy(this.codes.join('\n'), 'คัดลอกรหัสทั้งหมดแล้ว'); },
                download() {
                    var url = URL.createObjectURL(new Blob([this.text()], { type: 'text/plain;charset=utf-8' }));
                    var a = document.createElement('a');
                    a.href = url; a.download = '2FA-recovery-codes-' + new Date().toISOString().slice(0, 10) + '.txt';
                    document.body.appendChild(a); a.click(); a.remove();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                    window.showNotification('ดาวน์โหลดไฟล์รหัสแล้ว', 'success');
                },
                print() {
                    var w = window.open('', '', 'width=720,height=640');
                    if (! w) { window.showNotification('เบราว์เซอร์บล็อกหน้าต่างพิมพ์ ใช้ปุ่มดาวน์โหลดแทน', 'error'); return; }
                    w.document.write('<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><title>รหัสกู้คืน 2FA</title>'
                        + '<style>body{font-family:sans-serif;padding:32px;color:#111}h1{font-size:20px;border-bottom:2px solid #111;padding-bottom:8px}'
                        + 'ol{columns:2;font:600 17px/2.1 monospace}.w{border:1px solid #c90;background:#fff8e1;padding:10px 14px;border-radius:8px;font-size:13px}</style></head><body>'
                        + '<h1>รหัสกู้คืน 2FA · ' + escHtml(@js(config('app.name', 'Thaiprompt'))) + '</h1>'
                        + '<p>สร้างเมื่อ ' + escHtml(this.stamp()) + '</p>'
                        + '<p class="w">แต่ละรหัสใช้ได้ครั้งเดียว · เก็บเอกสารนี้ในที่ปลอดภัย · ทีมงานไม่เคยขอรหัสเหล่านี้</p>'
                        + '<ol>' + this.codes.map(function (c) { return '<li>' + escHtml(c) + '</li>'; }).join('') + '</ol></body></html>');
                    w.document.close(); w.focus();
                    setTimeout(function () { w.print(); w.close(); }, 250);
                },
                nudge() { window.showNotification('ติ๊กยืนยันว่าเก็บรหัสแล้วก่อน', 'warning'); },
            };
        });
    });
</script>
@endpush
