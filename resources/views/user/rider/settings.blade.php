{{--
    ตั้งค่าไรเดอร์ (user.rider.settings) — ยานพาหนะ, ความชอบงาน, ความยินยอมแชร์ตำแหน่ง, การแจ้งเตือน
    Controller: User\RiderController@settings
    ตัวแปร: rider, riderData, vehicleTypes, jobTypeOptions, preferences{job_types, radius_km, min_fee}, updateUrl, consentUrl, pageTitle
    ฟอร์ม POST user.rider.settings.update: phone, vehicle_type, vehicle_plate, vehicle_brand, vehicle_color,
          preferred_radius_km, preferred_min_fee, preferred_job_types[]
    ความยินยอม POST user.rider.consent {location_consent=1|0} (ถอนไม่ได้ระหว่างมีงาน)
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ตั้งค่าไรเดอร์')

@push('styles')
    @include('user.rider.partials.styles')
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
@endphp

@section('content')
<div class="rd-scope">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'settings'])

    <section class="tp-card rd-hero">
        <div class="rd-hero-in">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:17px; font-size:22px;"><i class="fas fa-sliders"></i></span>
            <div style="flex:1 1 220px; min-width:0;">
                <h1 class="rd-h1">ตั้งค่าไรเดอร์</h1>
                <div class="rd-muted rd-small">ยานพาหนะ งานที่อยากรับ ความเป็นส่วนตัว และการแจ้งเตือน</div>
            </div>
        </div>
    </section>

    @if($errors->any())
        <div class="rd-alert rd-tone-bad">
            <i class="fas fa-circle-exclamation"></i>
            <div><b>บันทึกไม่สำเร็จ:</b> {{ $errors->first() }}</div>
        </div>
    @endif

    {{-- ── ฟอร์มหลัก ─────────────────────────────────────────── --}}
    <form method="POST" action="{{ $updateUrl }}" class="rd-stack" x-data="rdSettings({{ \Illuminate\Support\Js::from($settingsCfg) }})"
          x-on:submit="guard($event)">
        @csrf

        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas {{ $ui::vehicleIcon($rider->vehicle_type) }}" style="color:var(--accent1);"></i> ยานพาหนะและการติดต่อ</h2>

            <div class="rd-field">
                <label for="phone">เบอร์โทรศัพท์ที่ลูกค้าและร้านติดต่อได้ <span class="req">*</span></label>
                <input id="phone" type="tel" name="phone" inputmode="tel" autocomplete="tel" maxlength="15" required
                       class="tp-input {{ $errors->has('phone') ? 'is-bad' : '' }}" value="{{ old('phone', $rider->phone) }}" placeholder="0812345678">
                @error('phone')<span class="rd-err">{{ $message }}</span>@enderror
            </div>

            <div class="rd-field">
                <span class="rd-label">ประเภทยานพาหนะ <span class="req">*</span></span>
                <div class="rd-grid" style="--rd-min:150px; gap:10px;">
                    @foreach($vehicleTypes as $value => $label)
                        <label class="rd-choice">
                            <input type="radio" name="vehicle_type" value="{{ $value }}" x-model="vehicle" @checked($selectedVehicle === $value) @disabled($hasActiveJob && $value !== $rider->vehicle_type)>
                            <span class="box"><i class="fas {{ $ui::vehicleIcon($value) }}"></i><span style="font-weight:700; font-size:13.5px;">{{ $label }}</span></span>
                        </label>
                    @endforeach
                </div>
                @error('vehicle_type')<span class="rd-err">{{ $message }}</span>@enderror
                @if($hasActiveJob)
                    <span class="rd-hint"><i class="fas fa-lock"></i> เปลี่ยนยานพาหนะได้หลังส่งงานปัจจุบันเสร็จ</span>
                @endif
                @if($isApproved)
                    <div class="rd-alert rd-tone-warn" x-show="vehicle !== initialVehicle" x-cloak>
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>เปลี่ยนยานพาหนะแล้ว<b>ต้องรอทีมงานตรวจเอกสารใหม่</b> ระหว่างนั้นรับงานไม่ได้
                            <template x-if="vehicle === 'motorcycle' || vehicle === 'car'"><span> และต้องอัปโหลดใบขับขี่ + ทะเบียนรถของคันใหม่</span></template>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rd-grid" style="--rd-min:180px;">
                <div class="rd-field">
                    <label for="vehicle_plate">ทะเบียนรถ <span class="req" x-show="vehicle === 'motorcycle' || vehicle === 'car'">*</span></label>
                    <input id="vehicle_plate" type="text" name="vehicle_plate" maxlength="20" class="tp-input {{ $errors->has('vehicle_plate') ? 'is-bad' : '' }}"
                           value="{{ old('vehicle_plate', $rider->vehicle_plate) }}" placeholder="เช่น 1กข 1234 กรุงเทพฯ"
                           :required="vehicle === 'motorcycle' || vehicle === 'car'">
                    @error('vehicle_plate')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="vehicle_brand">ยี่ห้อ/รุ่น</label>
                    <input id="vehicle_brand" type="text" name="vehicle_brand" maxlength="100" class="tp-input" value="{{ old('vehicle_brand', $rider->vehicle_brand) }}" placeholder="เช่น Honda Wave">
                    @error('vehicle_brand')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="vehicle_color">สีรถ</label>
                    <input id="vehicle_color" type="text" name="vehicle_color" maxlength="50" class="tp-input" value="{{ old('vehicle_color', $rider->vehicle_color) }}" placeholder="เช่น แดง">
                    @error('vehicle_color')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-bullseye" style="color:var(--accent1);"></i> งานที่อยากรับ</h2>
            <p class="rd-muted" style="font-size:13px; margin:0;">ระบบจะเสนองานที่ตรงกับความชอบของคุณก่อน — ไม่ตั้งค่า = รับทุกงานตามปกติ</p>

            <div class="rd-field">
                <label class="rd-row" style="gap:10px; cursor:pointer; min-height:44px; flex-wrap:nowrap;">
                    <input type="checkbox" x-model="useRadius" style="width:22px; height:22px; accent-color:var(--accent1); flex:none;">
                    <span class="rd-label">กำหนดรัศมีรับงานเอง</span>
                </label>
                <div x-show="useRadius" x-cloak class="rd-stack" style="gap:8px;">
                    <div class="rd-row" style="justify-content:space-between;">
                        <span class="rd-small rd-muted">1 กม.</span>
                        <span class="rd-pill rd-tone-gold rd-num" x-text="'ไม่เกิน ' + radius + ' กม.'"></span>
                        <span class="rd-small rd-muted">50 กม.</span>
                    </div>
                    <input type="range" name="preferred_radius_km" min="1" max="50" step="1" class="tp-range" x-model.number="radius" :disabled="!useRadius" aria-label="รัศมีรับงาน (กิโลเมตร)">
                </div>
                @error('preferred_radius_km')<span class="rd-err">{{ $message }}</span>@enderror
            </div>

            <div class="rd-field">
                <label for="preferred_min_fee">ค่าส่งขั้นต่ำที่อยากรับ (บาท)</label>
                <input id="preferred_min_fee" type="number" name="preferred_min_fee" min="0" max="1000" step="any" inputmode="decimal" class="tp-input"
                       value="{{ old('preferred_min_fee', $preferences['min_fee'] !== null ? rtrim(rtrim(number_format((float) $preferences['min_fee'], 2, '.', ''), '0'), '.') : '') }}" placeholder="เว้นว่าง = รับทุกราคา" style="max-width:260px;">
                @error('preferred_min_fee')<span class="rd-err">{{ $message }}</span>@enderror
            </div>

            <div class="rd-field">
                <span class="rd-label">ประเภทงาน (เลือกได้หลายอย่าง · ไม่เลือก = รับทุกประเภท)</span>
                <div class="rd-row" style="gap:8px;">
                    @foreach($jobTypeOptions as $value => $label)
                        <label class="rd-choice">
                            <input type="checkbox" name="preferred_job_types[]" value="{{ $value }}" @checked(in_array($value, $selectedJobTypes, true))>
                            <span class="rd-chip"><i class="fas {{ $ui::jobIcon($value) }}"></i> {{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('preferred_job_types')<span class="rd-err">{{ $message }}</span>@enderror
                @error('preferred_job_types.*')<span class="rd-err">{{ $message }}</span>@enderror
            </div>
        </section>

        <div class="rd-sticky-actions">
            <button type="submit" class="rd-btn3d rd-tone-gold lg block" :disabled="saving">
                <i class="fas" :class="saving ? 'fa-circle-notch rd-spin' : 'fa-floppy-disk'"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- ── ความเป็นส่วนตัว: แชร์ตำแหน่งกับลูกค้า ─────────────────── --}}
    <section class="tp-card rd-stack" id="consent">
        <h2 class="rd-h2"><i class="fas fa-location-dot" style="color:var(--accent1);"></i> แชร์ตำแหน่งกับลูกค้าระหว่างส่งงาน</h2>
        <p class="rd-muted" style="font-size:13px; margin:0; line-height:1.6;">
            ลูกค้าเห็นตำแหน่งของคุณเฉพาะออเดอร์ของเขา ตั้งแต่รับงานจนส่งของเสร็จ แล้วหยุดแชร์อัตโนมัติ — ต้องยินยอมก่อนรับงาน
        </p>
        @if($hasConsent)
            <div class="rd-alert rd-tone-ok">
                <i class="fas fa-shield-heart"></i>
                <div><b>ยินยอมแล้ว</b> เมื่อ {{ $ui::date($rd['location_consent_at'] ?? null) }}</div>
            </div>
            <form method="POST" action="{{ $consentUrl }}"
                  x-data x-on:submit="if (!window.confirm('ถอนความยินยอมแล้วจะรับงานใหม่ไม่ได้จนกว่าจะยินยอมอีกครั้ง ยืนยัน?')) { $event.preventDefault(); }">
                @csrf
                <input type="hidden" name="location_consent" value="0">
                <button type="submit" class="tp-btn rd-btn-ghost rd-tone-bad" @disabled($hasActiveJob)>
                    <i class="fas fa-hand"></i> ถอนความยินยอม
                </button>
                @if($hasActiveJob)
                    <span class="rd-hint" style="margin-left:8px;">ถอนได้หลังส่งงานปัจจุบันเสร็จ</span>
                @endif
            </form>
        @else
            <form method="POST" action="{{ $consentUrl }}" class="rd-stack">
                @csrf
                <input type="hidden" name="location_consent" value="1">
                <label class="rd-row" style="gap:10px; cursor:pointer; min-height:44px; flex-wrap:nowrap;">
                    <input type="checkbox" required style="width:22px; height:22px; accent-color:var(--accent1); flex:none;">
                    <span style="font-size:13.5px; font-weight:600;">ฉันเข้าใจและยินยอมแชร์ตำแหน่งกับลูกค้าระหว่างส่งงาน</span>
                </label>
                <div><button type="submit" class="rd-btn3d rd-tone-info"><i class="fas fa-shield-heart"></i> ยินยอม</button></div>
            </form>
        @endif
    </section>

    {{-- ── การแจ้งเตือน ─────────────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <h2 class="rd-h2"><i class="fas fa-bell" style="color:var(--accent1);"></i> การแจ้งเตือน</h2>
        <div class="rd-list">
            <div class="rd-item {{ !empty($rd['permissions']['notification']) ? 'rd-tone-ok' : 'rd-tone-warn' }}">
                <span class="ic"><i class="fas fa-mobile-screen-button"></i></span>
                <span class="main">
                    <span class="ttl" style="display:block;">แจ้งเตือนงานใหม่ในแอปไทยพร้อม</span>
                    <span class="sub" style="display:block;">
                        {{ !empty($rd['permissions']['notification']) ? 'เปิดอยู่ — คุณจะได้รับแจ้งเตือนทันทีเมื่อมีงานใกล้คุณ' : 'ยังไม่ได้อนุญาต — เปิดแอปไทยพร้อม เมนูไรเดอร์ แล้วกดอนุญาตการแจ้งเตือน' }}
                    </span>
                </span>
                <span class="end"><span class="rd-pill">{{ !empty($rd['permissions']['notification']) ? 'เปิด' : 'ปิด' }}</span></span>
            </div>
            <div class="rd-item {{ !empty($rd['permissions']['gps']) ? 'rd-tone-ok' : 'rd-tone-warn' }}">
                <span class="ic"><i class="fas fa-location-crosshairs"></i></span>
                <span class="main">
                    <span class="ttl" style="display:block;">สิทธิ์ตำแหน่ง (GPS) ในแอป</span>
                    <span class="sub" style="display:block;">ใช้ค้นหางานใกล้คุณและให้ลูกค้าติดตามการส่ง — บนเว็บ เบราว์เซอร์จะถามสิทธิ์ตอนเปิดรับงาน</span>
                </span>
                <span class="end"><span class="rd-pill">{{ !empty($rd['permissions']['gps']) ? 'อนุญาต' : 'ยังไม่อนุญาต' }}</span></span>
            </div>
            <a href="{{ route('user.notifications.index') }}" class="rd-item rd-tone-info">
                <span class="ic"><i class="fas fa-inbox"></i></span>
                <span class="main">
                    <span class="ttl" style="display:block;">กล่องแจ้งเตือน</span>
                    <span class="sub" style="display:block;">ดูแจ้งเตือนงาน ผลตรวจเอกสาร และรายได้ย้อนหลัง</span>
                </span>
                <span class="end"><i class="fas fa-chevron-right rd-muted"></i></span>
            </a>
            <a href="{{ route('user.email.preferences') }}" class="rd-item rd-tone-info">
                <span class="ic"><i class="fas fa-envelope"></i></span>
                <span class="main">
                    <span class="ttl" style="display:block;">การแจ้งเตือนทางอีเมล</span>
                    <span class="sub" style="display:block;">เลือกเรื่องที่ต้องการรับทางอีเมล</span>
                </span>
                <span class="end"><i class="fas fa-chevron-right rd-muted"></i></span>
            </a>
        </div>
    </section>

    {{-- ── ข้อมูลผู้สมัคร (อ่านอย่างเดียว) ─────────────────────── --}}
    <section class="tp-card rd-stack">
        <h2 class="rd-h2"><i class="fas fa-id-badge" style="color:var(--accent1);"></i> ข้อมูลไรเดอร์</h2>
        <div class="rd-grid" style="--rd-min:180px; gap:10px;">
            <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;"><span>ชื่อ-นามสกุล</span><b style="color:var(--ink);">{{ $rider->full_name }}</b></div>
            <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;"><span>เลขบัตรประชาชน</span><b style="color:var(--ink);" class="rd-num">{{ $rd['id_card_number_masked'] ?? '—' }}</b></div>
            <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;"><span>สถานะ</span><b style="color:var(--ink);">{{ $rd['status_text'] ?? $rider->status_text }}</b></div>
            <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;"><span>เป็นไรเดอร์ตั้งแต่</span><b style="color:var(--ink);">{{ $ui::date($rd['approved_at'] ?? ($rd['created_at'] ?? null), false) }}</b></div>
        </div>
        <div class="rd-hint">ต้องการแก้ชื่อหรือเลขบัตรประชาชน กรุณา<a href="{{ route('user.tickets.create') }}" class="rd-link"> ติดต่อทีมงาน</a> พร้อมเอกสารยืนยัน</div>
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
