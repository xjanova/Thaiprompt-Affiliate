{{--
    ตั้งค่าการยืนยันตัวตน 2 ชั้น (ธีมโนวาเต็ม)
    - ยังไม่เปิด: 3 ขั้น ดาวน์โหลดแอป → สร้าง QR → ใส่รหัส
      ตรวจรหัสผ่าน fetch ก่อนส่งฟอร์ม — ถ้าส่งฟอร์มตรงแล้วรหัสผิด หน้าโหลดใหม่ QR หาย กดสร้างใหม่ = secret ใหม่ รายการในแอปที่สแกนไปแล้วใช้ไม่ได้
    - เปิดแล้ว: สรุปสถานะ · รหัสกู้คืน · อุปกรณ์ที่เชื่อถือ · ปิด 2FA
    ปิด 2FA / สร้างรหัสกู้คืนใหม่ ต้องยืนยันรหัสปัจจุบันก่อน (TwoFactorController::requireFreshVerification พาไปหน้ายืนยันเอง)
--}}
@extends('layouts.user-nova')

@section('title', 'ความปลอดภัย 2 ชั้น (2FA)')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $on = (bool) ($status['enabled'] ?? false);
    $codesLeft = (int) ($status['recovery_codes_count'] ?? 0);
    $methodLabel = match ($status['preferred_method'] ?? 'authenticator') {
        'sms' => 'SMS',
        'line' => 'LINE',
        default => 'แอป Authenticator',
    };
    // อุปกรณ์ที่เชื่อถือเก็บเป็น { fingerprint: {name, added_at, last_used_at, ip} }
    $devices = collect($status['trusted_devices'] ?? [])
        ->map(function ($d, $fp) {
            $d = (array) $d;
            $at = fn ($v) => $v ? \Carbon\Carbon::parse($v) : null;

            return [
                'fp' => (string) $fp,
                'name' => $d['name'] ?? 'อุปกรณ์ที่ไม่รู้จัก',
                'ip' => $d['ip'] ?? null,
                'used' => $at($d['last_used_at'] ?? null)?->diffForHumans(),
                'added' => $at($d['added_at'] ?? null)?->format('d/m/Y'),
            ];
        })
        ->values();
    $apps = [
        ['fa-brands fa-google', 'Google Authenticator', 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2', 'https://apps.apple.com/app/google-authenticator/id388497605'],
        ['fa-brands fa-microsoft', 'Microsoft Authenticator', 'https://play.google.com/store/apps/details?id=com.azure.authenticator', 'https://apps.apple.com/app/microsoft-authenticator/id983156458'],
    ];
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-2fa-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Two-factor authentication</div>
            <h1 id="nvm-2fa-h">ความปลอดภัย 2 ชั้น</h1>
            <p class="nvm-ph__lead">นอกจากรหัสผ่าน ต้องใช้รหัส 6 หลักจากแอปในมือถือคุณด้วย ก่อนถอนเงิน โอนเงิน หรือเปลี่ยนข้อมูลสำคัญ</p>
            <div class="nvm-bal">
                <small>สถานะตอนนี้</small>
                <b class="nvm-foil">{{ $on ? 'เปิดใช้งานแล้ว' : 'ยังไม่เปิด' }}</b>
            </div>
            <div class="nvm-pills">
                @if($on)
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ยืนยันด้วย{{ $methodLabel }}</span>
                    <span class="nvm-pill nvm-pill--{{ $codesLeft > 3 ? 'glass' : 'bad' }}">รหัสกู้คืนเหลือ {{ $codesLeft }}</span>
                @else
                    <span class="nvm-pill nvm-pill--warn"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>บัญชีป้องกันด้วยรหัสผ่านอย่างเดียว</span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('shield', 'deco/crystal') }}" alt="" width="200" height="200">
    </div>
</section>

@if($on)
<div class="nvm-stack" x-data="nvm2faManage(@js($devices))">

    <div class="nvm-kpis">
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ $nvArt('shield', 'deco/crystal') }}" alt="" width="48" height="48" loading="lazy">
            <div class="nvm-kpi__lbl">วิธียืนยัน <span class="nvm-kpi__en nvm-latin">Method</span></div>
            <div class="nvm-kpi__val nvm-kpi__val--txt">{{ $methodLabel }}</div>
            <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--ok">เปิดอยู่</span></div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ $nvArt('key', 'deco/lantern') }}" alt="" width="48" height="48" loading="lazy">
            <div class="nvm-kpi__lbl">รหัสกู้คืน <span class="nvm-kpi__en nvm-latin">Recovery</span></div>
            <div class="nvm-kpi__val nvm-num">{{ $codesLeft }}</div>
            <div class="nvm-kpi__foot"><span class="nvm-chip {{ $codesLeft > 3 ? 'nvm-chip--ok' : 'nvm-chip--bad' }}">{{ $codesLeft > 3 ? 'ยังเหลือพอ' : 'ควรสร้างชุดใหม่' }}</span></div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ $nvArt('devices', 'wallet/phone') }}" alt="" width="48" height="48" loading="lazy">
            <div class="nvm-kpi__lbl">อุปกรณ์ที่เชื่อถือ <span class="nvm-kpi__en nvm-latin">Devices</span></div>
            <div class="nvm-kpi__val nvm-num" x-text="devices.length">{{ $devices->count() }}</div>
            <div class="nvm-kpi__foot"><span class="nvm-chip">ไม่ต้องใส่รหัสซ้ำ</span></div>
        </div>
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="48" height="48" loading="lazy">
            <div class="nvm-kpi__lbl">ยืนยันแล้ว <span class="nvm-kpi__en nvm-latin">Verified</span></div>
            <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($status['total_verifications'] ?? 0)) }}<small> ครั้ง</small></div>
            <div class="nvm-kpi__foot"><span class="nvm-chip">{{ ! empty($status['last_verified_at']) ? 'ล่าสุด '.\Carbon\Carbon::parse($status['last_verified_at'])->diffForHumans() : 'ยังไม่เคยใช้' }}</span></div>
        </div>
    </div>

    @if($codesLeft <= 3)
        <a class="nvm-card nvm-alert nvm-alert--bad" href="#nvm-2fa-codes">
            <img class="nvm-alert__img" src="{{ $nvArt('key', 'deco/lantern') }}" alt="" width="56" height="56" loading="lazy">
            <div class="nvm-alert__txt">
                <b>{{ $codesLeft ? 'รหัสกู้คืนเหลือแค่ '.$codesLeft.' รหัส' : 'รหัสกู้คืนหมดแล้ว' }}</b>
                <p>ถ้ามือถือหายหรือลบแอป จะเข้าบัญชีไม่ได้ — สร้างชุดใหม่แล้วเก็บไว้ในที่ปลอดภัย</p>
            </div>
            <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
        </a>
    @endif

    <div class="nvm-split">
        <div class="nvm-stack">
            <section class="nvm-card nvm-box" id="nvm-2fa-codes" aria-labelledby="nvm-2fa-codes-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Recovery codes</small><h2 id="nvm-2fa-codes-h">รหัสกู้คืน</h2></div></div>
                <p class="nvm-desc">ใช้เข้าบัญชีแทนรหัสจากแอป เมื่อมือถือหาย เปลี่ยนเครื่อง หรือลบแอปไป รหัสแต่ละตัวใช้ได้ครั้งเดียว และระบบเก็บแบบเข้ารหัส จึงดูซ้ำไม่ได้</p>
                <div class="nvm-meter nvm-gap-t" role="img" aria-label="รหัสกู้คืนเหลือ {{ $codesLeft }} จาก 10 รหัส">
                    <div class="nvm-meter__bar"><i style="width: {{ min(100, $codesLeft * 10) }}%"></i></div>
                    <small class="nvm-help nvm-num">เหลือ {{ $codesLeft }} รหัสที่ยังไม่ได้ใช้</small>
                </div>
                <div class="nvm-gap-t" x-data="{ ask: false, busy: false }" @pageshow.window="busy = false">
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-show="! ask" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>สร้างรหัสชุดใหม่</button>
                    <form method="POST" action="{{ route('user.two-factor.regenerate-recovery-codes') }}" class="nvm-confirm" x-show="ask" x-cloak @submit="busy = true">
                        @csrf
                        <p>สร้างชุดใหม่แล้ว รหัสเดิมทั้งหมดจะใช้ไม่ได้ทันที ระบบอาจขอรหัสจากแอปก่อน แล้วแสดงรหัสใหม่ให้เก็บครั้งเดียว</p>
                        <div class="nvm-filter__btns">
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false" :disabled="busy">ยกเลิก</button>
                            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm" :disabled="busy" x-text="busy ? 'กำลังสร้าง…' : 'ยืนยัน สร้างชุดใหม่'">ยืนยัน สร้างชุดใหม่</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="nvm-card nvm-box" aria-labelledby="nvm-2fa-dev-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Trusted devices</small><h2 id="nvm-2fa-dev-h">อุปกรณ์ที่เชื่อถือ</h2></div></div>
                <p class="nvm-desc">อุปกรณ์ที่คุณติ๊ก "จำอุปกรณ์นี้" ตอนยืนยัน จะไม่ถูกขอรหัสซ้ำจนกว่าจะหมดอายุ ถ้าเห็นเครื่องที่ไม่รู้จัก ให้ลบทิ้งแล้วเปลี่ยนรหัสผ่าน</p>
                <ul class="nvm-txs nvm-gap-t" x-show="devices.length" @if($devices->isEmpty()) x-cloak @endif>
                    <template x-for="d in devices" :key="d.fp">
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic" aria-hidden="true"><i class="fa-solid" :class="icon(d.name)"></i></span>
                            <div class="nvm-tx__t">
                                <b x-text="d.name"></b>
                                <small class="nvm-num" x-text="[d.used ? 'ใช้ล่าสุด ' + d.used : null, d.added ? 'เพิ่มเมื่อ ' + d.added : null, d.ip].filter(Boolean).join(' · ')"></small>
                            </div>
                            <div class="nvm-tx__r">
                                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="remove(d)" :disabled="busy === d.fp" x-text="busy === d.fp ? 'กำลังลบ…' : 'เลิกเชื่อถือ'">เลิกเชื่อถือ</button>
                            </div>
                        </li>
                    </template>
                </ul>
                <div class="nvm-empty" x-show="! devices.length" @if($devices->isNotEmpty()) x-cloak @endif>
                    <img src="{{ $nvArt('devices', 'wallet/phone') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่มีอุปกรณ์ที่เชื่อถือ</b>
                    ทุกครั้งที่ทำรายการสำคัญ ระบบจะขอรหัสจากแอป
                </div>
                @if($devices->isNotEmpty())
                    <div class="nvm-gap-t" x-show="devices.length" x-data="{ ask: false, busy: false }" @pageshow.window="busy = false">
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-show="! ask" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg>เลิกเชื่อถือทุกเครื่อง</button>
                        <form method="POST" action="{{ route('user.two-factor.remove-all-trusted-devices') }}" class="nvm-confirm" x-show="ask" x-cloak @submit="busy = true">
                            @csrf
                            @method('DELETE')
                            <p>ทุกเครื่องรวมเครื่องนี้ จะถูกขอรหัสจากแอปอีกครั้งในการทำรายการครั้งถัดไป</p>
                            <div class="nvm-filter__btns">
                                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false" :disabled="busy">ยกเลิก</button>
                                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm" :disabled="busy" x-text="busy ? 'กำลังลบ…' : 'ยืนยัน ลบทั้งหมด'">ยืนยัน ลบทั้งหมด</button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>
        </div>

        <aside class="nvm-stack">
            <section class="nvm-card nvm-box" aria-labelledby="nvm-2fa-off-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Turn off</small><h2 id="nvm-2fa-off-h">ปิดการยืนยัน 2 ชั้น</h2></div></div>
                <p class="nvm-desc">ปิดแล้ว ใครได้รหัสผ่านไปก็ถอนหรือโอนเงินได้ทันที ถ้าจะเปลี่ยนมือถือ ให้ปิดแล้วเปิดใหม่ด้วยเครื่องใหม่</p>
                <div class="nvm-gap-t" x-data="{ ask: false, busy: false }" @pageshow.window="busy = false">
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-show="! ask" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ปิด 2FA</button>
                    <form method="POST" action="{{ route('user.two-factor.disable') }}" class="nvm-confirm" x-show="ask" x-cloak @submit="busy = true">
                        @csrf
                        <p>ปิด 2FA? รหัสกู้คืนและอุปกรณ์ที่เชื่อถือจะถูกล้างทั้งหมด ระบบอาจขอรหัสจากแอปก่อนปิด</p>
                        <div class="nvm-filter__btns">
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false" :disabled="busy">ไม่ปิด</button>
                            <button type="submit" class="nvm-btn nvm-btn--bad nvm-btn--sm" :disabled="busy" x-text="busy ? 'กำลังปิด…' : 'ยืนยัน ปิด 2FA'">ยืนยัน ปิด 2FA</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="nvm-card nvm-box" aria-labelledby="nvm-2fa-tip-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Stay safe</small><h2 id="nvm-2fa-tip-h">ใช้ให้ปลอดภัย</h2></div></div>
                <ol class="nvm-steps">
                    <li>ทีมงานไม่เคยขอรหัส 6 หลักหรือรหัสกู้คืนของคุณ ใครขอ = มิจฉาชีพ</li>
                    <li>เก็บรหัสกู้คืนไว้นอกมือถือ เช่น จดใส่กระดาษ หรือในตัวจัดการรหัสผ่าน</li>
                    <li>ติ๊ก "จำอุปกรณ์นี้" เฉพาะเครื่องส่วนตัว ไม่ใช่เครื่องสาธารณะ</li>
                </ol>
                <a class="nvm-btn nvm-btn--line nvm-btn--sm nvm-gap-t" href="{{ route('user.tickets.create') }}"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>มือถือหายและไม่มีรหัสกู้คืน</a>
            </section>
        </aside>
    </div>
</div>
@else
<div class="nvm-split" x-data="nvm2faSetup">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-2fa-on-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Set up in 3 steps</small><h2 id="nvm-2fa-on-h">เปิดใช้ใน 3 ขั้น</h2></div></div>
        <ol class="nvm-steps nvm-steps--lg">
            <li>
                <div>
                    <b>ติดตั้งแอป Authenticator</b>
                    <p>มีแอปอยู่แล้วข้ามขั้นนี้ได้ — ใช้แอปไหนก็ได้ที่รองรับรหัส 6 หลัก (TOTP)</p>
                    <div class="nvm-txs nvm-2fa-apps">
                        @foreach($apps as [$icon, $name, $android, $ios])
                            <div class="nvm-tx">
                                <span class="nvm-tx__ic" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                                <div class="nvm-tx__t"><b>{{ $name }}</b><small>ฟรี · Android / iPhone</small></div>
                                <div class="nvm-tx__r nvm-2fa-apps__get">
                                    <a class="nvm-chip nvm-chip--info" href="{{ $android }}" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-android" aria-hidden="true"></i>Android</a>
                                    <a class="nvm-chip nvm-chip--info" href="{{ $ios }}" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-apple" aria-hidden="true"></i>iPhone</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </li>
            <li>
                <div>
                    <b>สแกน QR ด้วยแอป</b>
                    <p>กด "+" ในแอป แล้วเลือกสแกน QR — สแกนไม่ได้ให้เลือก "ใส่คีย์" แล้วคัดลอกคีย์ด้านล่างไปวาง</p>
                    <div class="nvm-2fa-qr">
                        <div class="nvm-2fa-qr__box" :class="qr ? 'is-on' : ''">
                            <img x-show="qr" x-cloak :src="qrSrc" alt="QR Code สำหรับเพิ่มบัญชีในแอป Authenticator" width="200" height="200">
                            <span x-show="! qr"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg></span>
                        </div>
                        <div class="nvm-2fa-qr__side">
                            <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" x-show="! qr" @click="generate()" :disabled="loading">
                                <svg class="nvm-ico"><use href="#nvm-i-qr"/></svg><span x-text="loading ? 'กำลังสร้าง…' : 'สร้าง QR Code'">สร้าง QR Code</span>
                            </button>
                            <template x-if="secret">
                                <div class="nvm-field">
                                    <span class="nvm-label">คีย์สำหรับใส่เอง</span>
                                    <div class="nvm-copy nvm-copy--box nvm-2fa-secret">
                                        <code x-text="secret.replace(/(.{4})/g, '$1 ').trim()"></code>
                                        <button type="button" @click="nvmCopy(secret, 'คัดลอกคีย์แล้ว')"><svg class="nvm-ico" aria-hidden="true"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                                    </div>
                                    <span class="nvm-help">เผลอรีเฟรชหน้าหลังสแกน? ลบรายการเดิมในแอป แล้วกดสร้าง QR ใหม่</span>
                                </div>
                            </template>
                            <p class="nvm-err" x-show="qrErr" x-cloak x-text="qrErr" role="alert"></p>
                        </div>
                    </div>
                </div>
            </li>
            <li>
                <div>
                    <b>ใส่รหัส 6 หลักจากแอป</b>
                    <p>รหัสในแอปเปลี่ยนทุก 30 วินาที ใช้รหัสที่แสดงอยู่ตอนนี้</p>
                    <form method="POST" action="{{ route('user.two-factor.enable') }}" class="nvm-form" @submit.prevent="submit($el)">
                        @csrf
                        <input type="hidden" name="preferred_method" value="authenticator">
                        <div class="nvm-field">
                            <label class="nvm-sr" for="nvm-2fa-code">รหัส 6 หลักจากแอป</label>
                            <input id="nvm-2fa-code" type="text" name="code" x-model="code" @input="code = code.replace(/\D/g, '').slice(0, 6)"
                                   inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required placeholder="000000"
                                   class="nvm-input nvm-otp @error('code') is-bad @enderror" :disabled="! qr" :class="msg && ! ok ? 'is-bad' : ''">
                            @error('code')<span class="nvm-err">{{ $message }}</span>@enderror
                            <span class="nvm-err" x-show="msg && ! ok" x-cloak x-text="msg" role="alert"></span>
                        </div>
                        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="! qr || code.length !== 6 || busy">
                            <svg class="nvm-ico"><use href="#nvm-i-lock"/></svg><span x-text="busy ? 'กำลังตรวจรหัส…' : 'เปิดใช้ 2FA'">เปิดใช้ 2FA</span>
                        </button>
                    </form>
                </div>
            </li>
        </ol>
    </section>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-2fa-why-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Why</small><h2 id="nvm-2fa-why-h">ทำไมต้องเปิด</h2></div></div>
            <ul class="nvm-txs">
                <li class="nvm-tx"><span class="nvm-tx__ic is-in" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span><div class="nvm-tx__t nvm-tx__t--wrap"><b>รหัสผ่านรั่วก็ยังปลอดภัย</b><small>คนร้ายต้องมีมือถือของคุณด้วยถึงจะเข้าได้</small></div></li>
                <li class="nvm-tx"><span class="nvm-tx__ic is-in" aria-hidden="true"><i class="fa-solid fa-sack-dollar"></i></span><div class="nvm-tx__t nvm-tx__t--wrap"><b>ปกป้องเงินในกระเป๋า</b><small>ถอน โอน และเปลี่ยนบัญชีรับเงิน ต้องใส่รหัสจากแอปก่อน</small></div></li>
                <li class="nvm-tx"><span class="nvm-tx__ic is-in" aria-hidden="true"><i class="fa-solid fa-wifi"></i></span><div class="nvm-tx__t nvm-tx__t--wrap"><b>ใช้ได้แม้ไม่มีเน็ต</b><small>แอปสร้างรหัสในเครื่อง ไม่ต้องรอ SMS</small></div></li>
            </ul>
        </section>
        <section class="nvm-card nvm-box" aria-labelledby="nvm-2fa-know-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Good to know</small><h2 id="nvm-2fa-know-h">ก่อนเริ่ม</h2></div></div>
            <ol class="nvm-steps">
                <li>เปิดเสร็จ ระบบจะแสดงรหัสกู้คืน 10 รหัส ครั้งเดียว — เตรียมที่จดไว้</li>
                <li>เปลี่ยนมือถือ ต้องปิด 2FA ในเครื่องเดิม แล้วตั้งใหม่ในเครื่องใหม่</li>
                <li>ห้ามบอกรหัสหรือคีย์ให้ใคร ทีมงานไม่เคยขอ</li>
            </ol>
        </section>
    </aside>
</div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', function () {
        var csrf = function () { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; };
        // ข้อความผิดพลาดจากเซิร์ฟเวอร์ — 429 (throttle) ตอบเป็นอังกฤษ จึงแปลเอง
        var errText = function (res, data, fallback) {
            if (res.status === 429) return 'ลองบ่อยเกินไป รอสักครู่แล้วลองใหม่';
            if (res.status === 419) return 'หน้านี้เปิดค้างนานเกินไป รีเฟรชหน้าแล้วลองใหม่';
            return (data && data.message) || fallback;
        };

        // ยังไม่เปิด 2FA: สร้าง QR → ตรวจรหัส → ส่งฟอร์มเปิดใช้
        window.Alpine.data('nvm2faSetup', function () {
            return {
                loading: false, qr: null, secret: null, qrErr: '', code: '', busy: false, msg: '', ok: false,
                get qrSrc() { return this.qr ? 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(this.qr) : ''; },
                async generate() {
                    this.loading = true; this.qrErr = '';
                    try {
                        var res = await fetch(@js(route('user.two-factor.generate-authenticator')), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        var data = await res.json().catch(function () { return null; });
                        if (res.ok && data && data.success) { this.qr = data.qr_code_svg; this.secret = data.secret; this.$nextTick(function () { document.getElementById('nvm-2fa-code').focus(); }); return; }
                        this.qrErr = errText(res, data, 'สร้าง QR ไม่สำเร็จ ลองใหม่อีกครั้ง');
                    } catch (e) {
                        this.qrErr = 'เชื่อมต่อไม่ได้ ตรวจอินเทอร์เน็ตแล้วลองใหม่';
                    } finally { this.loading = false; }
                },
                async submit(form) {
                    if (this.busy || this.code.length !== 6) return;
                    this.busy = true; this.msg = ''; this.ok = false;
                    try {
                        var res = await fetch(@js(route('user.two-factor.verify-authenticator-setup')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                            body: JSON.stringify({ code: this.code }),
                        });
                        var data = await res.json().catch(function () { return null; });
                        if (res.ok && data && data.success) { this.ok = true; form.submit(); return; }
                        this.msg = res.status === 422 && data && data.errors ? 'กรอกรหัสให้ครบ 6 หลัก' : errText(res, data, 'รหัสไม่ถูกต้อง ลองรหัสใหม่จากแอป');
                        this.code = '';
                    } catch (e) {
                        this.msg = 'เชื่อมต่อไม่ได้ ตรวจอินเทอร์เน็ตแล้วลองใหม่';
                    }
                    this.busy = false;
                },
            };
        });

        // เปิด 2FA แล้ว: เลิกเชื่อถือทีละเครื่อง (DELETE /trusted-devices/{fingerprint} ตอบ JSON)
        window.Alpine.data('nvm2faManage', function (devices) {
            return {
                devices: devices, busy: null,
                icon: function (name) { return /iPhone|iPad|Android/i.test(name) ? 'fa-mobile-screen' : 'fa-laptop'; },
                async remove(d) {
                    this.busy = d.fp;
                    try {
                        var res = await fetch(@js(route('user.two-factor.remove-trusted-device', ['fingerprint' => '__fp__'])).replace('__fp__', encodeURIComponent(d.fp)), {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                        });
                        var data = await res.json().catch(function () { return null; });
                        if (res.ok && data && data.success) {
                            this.devices = this.devices.filter(function (x) { return x.fp !== d.fp; });
                            window.showNotification('เลิกเชื่อถือ ' + d.name + ' แล้ว', 'success');
                        } else {
                            window.showNotification(errText(res, data, 'ลบไม่สำเร็จ ลองใหม่อีกครั้ง'), 'error');
                        }
                    } catch (e) {
                        window.showNotification('เชื่อมต่อไม่ได้ ตรวจอินเทอร์เน็ตแล้วลองใหม่', 'error');
                    }
                    this.busy = null;
                },
            };
        });
    });
</script>
@endpush
