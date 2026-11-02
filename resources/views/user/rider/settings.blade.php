{{--
    ตั้งค่าไรเดอร์ (user.rider.settings) — ยานพาหนะ, ความชอบงาน, ความยินยอมแชร์ตำแหน่ง, การแจ้งเตือน · ธีมโนวาเต็ม
    Controller: User\RiderController@settings
    ตัวแปร: rider, riderData, vehicleTypes, jobTypeOptions, preferences{job_types, radius_km, min_fee}, updateUrl, consentUrl, pageTitle
    ฟอร์ม POST user.rider.settings.update: phone, vehicle_type, vehicle_plate, vehicle_brand, vehicle_color,
          preferred_radius_km, preferred_min_fee, preferred_job_types[]
    ความยินยอม POST user.rider.consent {location_consent=1|0} (ถอนไม่ได้ระหว่างมีงาน)
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'ตั้งค่าไรเดอร์')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $rd = $riderData;
    $isApproved = in_array($rider->status, ['approved', 'suspended'], true);
    $hasActiveJob = !empty($rd['active_job_id']);
    $hasConsent = (bool) ($rd['permissions']['location_consent'] ?? false);
    $selectedVehicle = old('vehicle_type', $rider->vehicle_type ?: 'motorcycle');
    $radius = old('preferred_radius_km', $preferences['radius_km']);
    $selectedJobTypes = (array) old('preferred_job_types', $preferences['job_types'] ?? []);
    $settingsCfg = [
        'vehicle' => $selectedVehicle,
        'initialVehicle' => (string) $rider->vehicle_type,
        'useRadius' => $radius !== null && $radius !== '',
        'radius' => $radius !== null && $radius !== '' ? (float) $radius : 5,
    ];
    $notifyOn = !empty($rd['permissions']['notification']);
    $gpsOn = !empty($rd['permissions']['gps']);
@endphp

@section('content')
<div class="nvm-stack">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'settings'])

    <section class="nvm-ph" aria-labelledby="nvm-rd-set-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <div class="nvm-eyebrow nvm-latin">Rider settings</div>
                <h1 id="nvm-rd-set-h">ตั้งค่าไรเดอร์</h1>
                <p class="nvm-ph__lead">ยานพาหนะ งานที่อยากรับ ความเป็นส่วนตัว และการแจ้งเตือน</p>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/account/key.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    @if($errors->any())
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt"><b>บันทึกไม่สำเร็จ</b><p>{{ $errors->first() }}</p></div>
        </section>
    @endif

    {{-- ── ฟอร์มหลัก ─────────────────────────────────────────── --}}
    <form method="POST" action="{{ $updateUrl }}" class="nvm-form" x-data="rdSettings({{ \Illuminate\Support\Js::from($settingsCfg) }})"
          x-on:submit="guard($event)">
        @csrf

        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-veh-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Vehicle & contact</small><h2 id="nvm-rd-veh-h">ยานพาหนะและการติดต่อ</h2></div></div>
            <div class="nvm-form">
                <div class="nvm-field">
                    <label for="phone" class="nvm-label">เบอร์โทรศัพท์ที่ลูกค้าและร้านติดต่อได้ <em>*</em></label>
                    <input id="phone" type="tel" name="phone" inputmode="tel" autocomplete="tel" maxlength="15" required
                           class="nvm-input {{ $errors->has('phone') ? 'is-bad' : '' }}" value="{{ old('phone', $rider->phone) }}" placeholder="0812345678">
                    @error('phone')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <span class="nvm-label" id="nvm-rd-vt">ประเภทยานพาหนะ <em>*</em></span>
                    <div class="nvm-opts nvm-rd-opts" role="radiogroup" aria-labelledby="nvm-rd-vt">
                        @foreach($vehicleTypes as $value => $label)
                            <label class="nvm-opt">
                                <input type="radio" name="vehicle_type" value="{{ $value }}" x-model="vehicle" @checked($selectedVehicle === $value) @disabled($hasActiveJob && $value !== $rider->vehicle_type)>
                                <span class="nvm-opt__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaVehicleIcon($value) }}"/></svg></span>
                                <b>{{ $label }}</b>
                            </label>
                        @endforeach
                    </div>
                    @error('vehicle_type')<span class="nvm-err">{{ $message }}</span>@enderror
                    @if($hasActiveJob)
                        <span class="nvm-help">เปลี่ยนยานพาหนะได้หลังส่งงานปัจจุบันเสร็จ</span>
                    @endif
                </div>

                @if($isApproved)
                    <div class="nvm-status is-warn" x-show="vehicle !== initialVehicle" x-cloak>
                        <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                        <span>เปลี่ยนยานพาหนะแล้วต้องรอทีมงานตรวจเอกสารใหม่ ระหว่างนั้นรับงานไม่ได้
                            <small x-show="vehicle === 'motorcycle' || vehicle === 'car'">และต้องอัปโหลดใบขับขี่ + ทะเบียนรถของคันใหม่</small>
                        </span>
                    </div>
                @endif

                <div class="nvm-rd-grid nvm-rd-grid--3">
                    <div class="nvm-field">
                        <label for="vehicle_plate" class="nvm-label">ทะเบียนรถ <em x-show="vehicle === 'motorcycle' || vehicle === 'car'">*</em></label>
                        <input id="vehicle_plate" type="text" name="vehicle_plate" maxlength="20" class="nvm-input {{ $errors->has('vehicle_plate') ? 'is-bad' : '' }}"
                               value="{{ old('vehicle_plate', $rider->vehicle_plate) }}" placeholder="เช่น 1กข 1234 กรุงเทพฯ"
                               :required="vehicle === 'motorcycle' || vehicle === 'car'">
                        @error('vehicle_plate')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="nvm-field">
                        <label for="vehicle_brand" class="nvm-label">ยี่ห้อ/รุ่น</label>
                        <input id="vehicle_brand" type="text" name="vehicle_brand" maxlength="100" class="nvm-input" value="{{ old('vehicle_brand', $rider->vehicle_brand) }}" placeholder="เช่น Honda Wave">
                        @error('vehicle_brand')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="nvm-field">
                        <label for="vehicle_color" class="nvm-label">สีรถ</label>
                        <input id="vehicle_color" type="text" name="vehicle_color" maxlength="50" class="nvm-input" value="{{ old('vehicle_color', $rider->vehicle_color) }}" placeholder="เช่น แดง">
                        @error('vehicle_color')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-pref-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Job preferences</small><h2 id="nvm-rd-pref-h">งานที่อยากรับ</h2></div></div>
            <p class="nvm-desc">ระบบจะเสนองานที่ตรงกับความชอบของคุณก่อน — ไม่ตั้งค่า = รับทุกงานตามปกติ</p>

            <div class="nvm-form nvm-gap-t">
                <div class="nvm-field">
                    <label class="nvm-check">
                        <input type="checkbox" x-model="useRadius">
                        <span class="nvm-label">กำหนดรัศมีรับงานเอง</span>
                    </label>
                    <div x-show="useRadius" x-cloak class="nvm-field">
                        <div class="nvm-rd-range-row">
                            <span>1 กม.</span>
                            <span class="nvm-chip nvm-chip--gold nvm-num" x-text="'ไม่เกิน ' + radius + ' กม.'"></span>
                            <span>50 กม.</span>
                        </div>
                        <input type="range" name="preferred_radius_km" min="1" max="50" step="1" class="nvm-rd-range" x-model.number="radius" :disabled="!useRadius" aria-label="รัศมีรับงาน (กิโลเมตร)">
                    </div>
                    @error('preferred_radius_km')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <label for="preferred_min_fee" class="nvm-label">ค่าส่งขั้นต่ำที่อยากรับ (บาท)</label>
                    <input id="preferred_min_fee" type="number" name="preferred_min_fee" min="0" max="1000" step="any" inputmode="decimal" class="nvm-input nvm-rd-fee"
                           value="{{ old('preferred_min_fee', $preferences['min_fee'] !== null ? rtrim(rtrim(number_format((float) $preferences['min_fee'], 2, '.', ''), '0'), '.') : '') }}" placeholder="เว้นว่าง = รับทุกราคา">
                    @error('preferred_min_fee')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>

                <div class="nvm-field">
                    <span class="nvm-label" id="nvm-rd-jt">ประเภทงาน (เลือกได้หลายอย่าง · ไม่เลือก = รับทุกประเภท)</span>
                    <div class="nvm-opts nvm-rd-opts" role="group" aria-labelledby="nvm-rd-jt">
                        @foreach($jobTypeOptions as $value => $label)
                            <label class="nvm-opt">
                                <input type="checkbox" name="preferred_job_types[]" value="{{ $value }}" @checked(in_array($value, $selectedJobTypes, true))>
                                <span class="nvm-opt__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaJobIcon($value) }}"/></svg></span>
                                <b>{{ $label }}</b>
                            </label>
                        @endforeach
                    </div>
                    @error('preferred_job_types')<span class="nvm-err">{{ $message }}</span>@enderror
                    @error('preferred_job_types.*')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <div class="nvm-rd-sticky">
            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="saving">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="saving ? 'กำลังบันทึก…' : 'บันทึกการตั้งค่า'">บันทึกการตั้งค่า</span>
            </button>
        </div>
    </form>

    <div class="nvm-rd-2">
        {{-- ── ความเป็นส่วนตัว: แชร์ตำแหน่งกับลูกค้า ─────────────── --}}
        <section class="nvm-card nvm-box" id="consent" aria-labelledby="nvm-rd-cs-h" x-data="{ ask: false }" @keydown.escape.window="ask = false">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Location sharing</small><h2 id="nvm-rd-cs-h">แชร์ตำแหน่งกับลูกค้าระหว่างส่งงาน</h2></div></div>
            <p class="nvm-desc">ลูกค้าเห็นตำแหน่งของคุณเฉพาะออเดอร์ของเขา ตั้งแต่รับงานจนส่งของเสร็จ แล้วหยุดแชร์อัตโนมัติ — ต้องยินยอมก่อนรับงาน</p>
            @if($hasConsent)
                <div class="nvm-status nvm-gap-t">
                    <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                    <span>ยินยอมแล้ว<small class="nvm-num">เมื่อ {{ $ui::date($rd['location_consent_at'] ?? null) }}</small></span>
                </div>
                <form method="POST" action="{{ $consentUrl }}" x-ref="withdrawForm" class="nvm-gap-t">
                    @csrf
                    <input type="hidden" name="location_consent" value="0">
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = true" @disabled($hasActiveJob)>
                        <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ถอนความยินยอม
                    </button>
                    @if($hasActiveJob)
                        <span class="nvm-help">ถอนได้หลังส่งงานปัจจุบันเสร็จ</span>
                    @endif
                </form>

                {{-- ยืนยันก่อนถอน (ถอนแล้วรับงานใหม่ไม่ได้) --}}
                <div class="nvm-modal" x-show="ask" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-rd-wd-h" @click.self="ask = false">
                    <div class="nvm-card nvm-modal__box">
                        <img src="{{ asset('images/nova/account/shield.webp') }}" alt="" width="110" height="110">
                        <h2 id="nvm-rd-wd-h">ถอนความยินยอม?</h2>
                        <p>ถอนความยินยอมแล้วจะรับงานใหม่ไม่ได้จนกว่าจะยินยอมอีกครั้ง</p>
                        <div class="nvm-filter__btns">
                            <button type="button" class="nvm-btn nvm-btn--line" @click="ask = false">ยกเลิก</button>
                            <button type="button" class="nvm-btn nvm-btn--bad" @click="ask = false; $refs.withdrawForm.submit()">ยืนยันถอน</button>
                        </div>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ $consentUrl }}" class="nvm-form nvm-gap-t">
                    @csrf
                    <input type="hidden" name="location_consent" value="1">
                    <label class="nvm-check">
                        <input type="checkbox" required>
                        <span>ฉันเข้าใจและยินยอมแชร์ตำแหน่งกับลูกค้าระหว่างส่งงาน</span>
                    </label>
                    <div><button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ยินยอม</button></div>
                </form>
            @endif
        </section>

        {{-- ── การแจ้งเตือน ─────────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-nt-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Notifications</small><h2 id="nvm-rd-nt-h">การแจ้งเตือน</h2></div></div>
            <div class="nvm-txs nvm-tks">
                <div class="nvm-tx">
                    <span class="nvm-tx__ic {{ $notifyOn ? 'is-in' : 'is-due' }}"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg></span>
                    <div class="nvm-tx__t nvm-tx__t--wrap">
                        <b>แจ้งเตือนงานใหม่ในแอปไทยพร้อม</b>
                        <small>{{ $notifyOn ? 'เปิดอยู่ — คุณจะได้รับแจ้งเตือนทันทีเมื่อมีงานใกล้คุณ' : 'ยังไม่ได้อนุญาต — เปิดแอปไทยพร้อม เมนูไรเดอร์ แล้วกดอนุญาตการแจ้งเตือน' }}</small>
                    </div>
                    <div class="nvm-tx__r"><span class="nvm-chip {{ $notifyOn ? 'nvm-chip--ok' : 'nvm-chip--warn' }}">{{ $notifyOn ? 'เปิด' : 'ปิด' }}</span></div>
                </div>
                <div class="nvm-tx">
                    <span class="nvm-tx__ic {{ $gpsOn ? 'is-in' : 'is-due' }}"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg></span>
                    <div class="nvm-tx__t nvm-tx__t--wrap">
                        <b>สิทธิ์ตำแหน่ง (GPS) ในแอป</b>
                        <small>ใช้ค้นหางานใกล้คุณและให้ลูกค้าติดตามการส่ง — เปิดรับงานและส่งตำแหน่งได้ในแอป Thai Prompt APP เท่านั้น</small>
                    </div>
                    <div class="nvm-tx__r"><span class="nvm-chip {{ $gpsOn ? 'nvm-chip--ok' : 'nvm-chip--warn' }}">{{ $gpsOn ? 'อนุญาต' : 'ยังไม่อนุญาต' }}</span></div>
                </div>
                <a class="nvm-tx" href="{{ route('user.notifications.index') }}">
                    <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg></span>
                    <div class="nvm-tx__t nvm-tx__t--wrap"><b>กล่องแจ้งเตือน</b><small>ดูแจ้งเตือนงาน ผลตรวจเอกสาร และรายได้ย้อนหลัง</small></div>
                    <div class="nvm-tx__r"><svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></div>
                </a>
                <a class="nvm-tx" href="{{ route('user.email.preferences') }}">
                    <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg></span>
                    <div class="nvm-tx__t nvm-tx__t--wrap"><b>การแจ้งเตือนทางอีเมล</b><small>เลือกเรื่องที่ต้องการรับทางอีเมล</small></div>
                    <div class="nvm-tx__r"><svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></div>
                </a>
            </div>
        </section>
    </div>

    {{-- ── ข้อมูลผู้สมัคร (อ่านอย่างเดียว) ─────────────────────── --}}
    <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-info-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Rider profile</small><h2 id="nvm-rd-info-h">ข้อมูลไรเดอร์</h2></div></div>
        <dl class="nvm-kv">
            <dt>ชื่อ-นามสกุล</dt><dd>{{ $rider->full_name }}</dd>
            <dt>เลขบัตรประชาชน</dt><dd class="nvm-num">{{ $rd['id_card_number_masked'] ?? '—' }}</dd>
            <dt>สถานะ</dt><dd>{{ $rd['status_text'] ?? $rider->status_text }}</dd>
            <dt>เป็นไรเดอร์ตั้งแต่</dt><dd class="nvm-num">{{ $ui::date($rd['approved_at'] ?? ($rd['created_at'] ?? null), false) }}</dd>
        </dl>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>ต้องการแก้ชื่อหรือเลขบัตรประชาชน กรุณา<a href="{{ route('user.tickets.create') }}" class="nvm-link">ติดต่อทีมงาน</a>พร้อมเอกสารยืนยัน</span></p>
    </section>
</div>
@endsection

@push('scripts')
    <script>
    window.rdSettings = function (cfg) {
        return {
            vehicle: cfg.vehicle,
            initialVehicle: cfg.initialVehicle,
            useRadius: !!cfg.useRadius,
            radius: cfg.radius,
            saving: false,
            // กันกดบันทึกซ้ำระหว่างรอ (ฟอร์มส่งแบบปกติ แล้วเซิร์ฟเวอร์ redirect กลับพร้อมข้อความ)
            guard(event) {
                if (this.saving) { event.preventDefault(); return; }
                this.saving = true;
            }
        };
    };
    </script>
@endpush
