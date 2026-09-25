{{--
    สมัครเป็นไรเดอร์ / แก้ไขใบสมัคร / ส่งใบสมัครใหม่ (user.rider.register)
    Controller: User\RiderController@register → POST user.rider.register.submit (RiderRegistrationRequest + pdpa_consent)
    ตัวแปร: rider (Rider|null), isReapply, vehicleTypes, requiredDocumentsByVehicle, documentTypes, documentFlags,
            minBirthDate, maxBirthDate, formAction, pageTitle
    ฟิลด์: full_name, phone, id_card_number (13 หลัก + checksum), birth_date (อายุ 18+), address, province, district,
           vehicle_type, vehicle_plate (บังคับเมื่อมอเตอร์ไซค์/รถยนต์), vehicle_brand, vehicle_color, pdpa_consent
    สำเร็จ → หน้าอัปโหลดเอกสาร (user.rider.documents)
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'สมัครเป็นไรเดอร์')

@push('styles')
    @include('user.rider.partials.styles')
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $authUser = auth()->user();
    $isPendingEdit = $rider !== null && $rider->status === 'pending';
    $heroImage = file_exists(public_path('images/taladsod/banner-rider.webp')) ? asset('images/taladsod/banner-rider.webp') : null;
    $vehicle = old('vehicle_type', $rider?->vehicle_type ?: 'motorcycle');
    $provinces = array_keys(\App\Support\ThaiProvinces::PROVINCES);
    $docLabels = collect($documentTypes)->map(fn ($d) => $d['label'])->all();

    $regCfg = [
        'idCard' => (string) old('id_card_number', $rider?->id_card_number ?? ''),
        'birth' => (string) old('birth_date', $rider?->birth_date?->toDateString() ?? ''),
        'vehicle' => $vehicle,
        'requiredDocs' => $requiredDocumentsByVehicle,
        'docLabels' => $docLabels,
        'docFlags' => $documentFlags ?? [],
        'minBirth' => $minBirthDate,
        'maxBirth' => $maxBirthDate,
    ];

    $submitLabel = $isReapply ? 'ส่งใบสมัครใหม่' : ($isPendingEdit ? 'บันทึกการแก้ไขใบสมัคร' : 'ส่งใบสมัครและไปอัปโหลดเอกสาร');
@endphp

@section('content')
<div class="rd-scope">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'register'])

    {{-- ── หัวหน้า: ชวนมาเป็นไรเดอร์ ──────────────────────────── --}}
    <section class="tp-card rd-hero">
        @if($heroImage)
            <div class="rd-hero-img" style="background-image:url('{{ $heroImage }}'); opacity:.35;" aria-hidden="true"></div>
        @endif
        <div class="rd-hero-in" style="flex-direction:column; align-items:flex-start; padding-top:26px; padding-bottom:26px;">
            <span class="rd-pill solid rd-tone-gold"><i class="fas fa-motorcycle"></i> ไรเดอร์ไทยพร้อม</span>
            <h1 class="rd-h1" style="font-size:clamp(22px, 5.5vw, 32px); max-width:560px;">
                {{ $isReapply ? 'ส่งใบสมัครไรเดอร์ใหม่' : ($isPendingEdit ? 'แก้ไขใบสมัครไรเดอร์' : 'ขับรถส่งของ รับรายได้ใกล้บ้าน') }}
            </h1>
            <div class="rd-stack" style="gap:8px; max-width:560px;">
                <div class="rd-row" style="gap:8px; flex-wrap:nowrap;"><i class="fas fa-bolt" style="color:var(--accent1); width:18px;"></i><span style="font-size:13.5px;">รายได้ค่าส่งเข้ากระเป๋าเงินทันทีที่ส่งสำเร็จ</span></div>
                <div class="rd-row" style="gap:8px; flex-wrap:nowrap;"><i class="fas fa-clock" style="color:var(--accent1); width:18px;"></i><span style="font-size:13.5px;">เลือกเวลาทำงานเอง เปิด-ปิดรับงานได้ตลอด</span></div>
                <div class="rd-row" style="gap:8px; flex-wrap:nowrap;"><i class="fas fa-store" style="color:var(--accent1); width:18px;"></i><span style="font-size:13.5px;">งานใกล้บ้านจากร้านค้าและตลาดสดในชุมชน</span></div>
            </div>
        </div>
    </section>

    {{-- ── ขั้นตอน ────────────────────────────────────────────── --}}
    <section class="tp-card">
        <div class="rd-steps" aria-label="ขั้นตอนการสมัคร">
            <div class="st current"><div class="bar"></div><div class="lb">1. กรอกข้อมูล</div></div>
            <div class="st {{ $rider && ($documentFlags['id_card'] ?? false) ? 'done' : '' }}"><div class="bar"></div><div class="lb">2. อัปโหลดเอกสาร</div></div>
            <div class="st"><div class="bar"></div><div class="lb">3. ทีมงานตรวจสอบ</div></div>
            <div class="st"><div class="bar"></div><div class="lb">4. เริ่มรับงาน</div></div>
        </div>
    </section>

    @if($isReapply)
        <div class="rd-alert rd-tone-bad">
            <i class="fas fa-circle-xmark"></i>
            <div>
                <b>ใบสมัครครั้งก่อนไม่ผ่าน</b>@if($rider?->rejection_reason) — {{ $rider->rejection_reason }}@endif
                <div class="rd-small" style="margin-top:4px;">แก้ไขข้อมูลตามที่แจ้งแล้วส่งใหม่ได้เลย จากนั้นตรวจเอกสารให้ครบอีกครั้ง</div>
            </div>
        </div>
    @elseif($isPendingEdit)
        <div class="rd-alert rd-tone-info">
            <i class="fas fa-hourglass-half"></i>
            <div>ใบสมัครของคุณอยู่ระหว่างตรวจสอบ — แก้ไขข้อมูลได้ ข้อมูลใหม่จะแทนที่ของเดิม <a href="{{ route('user.rider.status') }}" class="rd-link">ดูสถานะ <i class="fas fa-arrow-right"></i></a></div>
        </div>
    @endif

    @if($errors->any())
        <div class="rd-alert rd-tone-bad" role="alert">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <b>กรุณาตรวจสอบข้อมูลอีกครั้ง</b>
                <ul style="margin:6px 0 0; padding-left:18px;">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="rd-stack" novalidate
          x-data="rdRegister({{ \Illuminate\Support\Js::from($regCfg) }})" x-on:submit="guard($event)">
        @csrf

        {{-- ── ข้อมูลส่วนตัว ──────────────────────────────────── --}}
        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-user" style="color:var(--accent1);"></i> ข้อมูลส่วนตัว</h2>
            <div class="rd-grid" style="--rd-min:240px;">
                <div class="rd-field">
                    <label for="full_name">ชื่อ-นามสกุล (ตามบัตรประชาชน) <span class="req">*</span></label>
                    <input id="full_name" type="text" name="full_name" required minlength="4" maxlength="255" autocomplete="name"
                           class="tp-input {{ $errors->has('full_name') ? 'is-bad' : '' }}" value="{{ old('full_name', $rider?->full_name ?? $authUser?->name) }}" placeholder="เช่น สมชาย ใจดี">
                    @error('full_name')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="phone">เบอร์มือถือ <span class="req">*</span></label>
                    <input id="phone" type="tel" name="phone" required inputmode="tel" autocomplete="tel" maxlength="15"
                           class="tp-input {{ $errors->has('phone') ? 'is-bad' : '' }}" value="{{ old('phone', $rider?->phone ?? $authUser?->phone) }}" placeholder="0812345678">
                    <span class="rd-hint">ลูกค้าและร้านจะใช้เบอร์นี้ติดต่อคุณระหว่างส่งงาน</span>
                    @error('phone')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="id_card_number">เลขบัตรประชาชน 13 หลัก <span class="req">*</span></label>
                    <input id="id_card_number" type="text" name="id_card_number" required inputmode="numeric" autocomplete="off" maxlength="17"
                           class="tp-input {{ $errors->has('id_card_number') ? 'is-bad' : '' }}"
                           :class="{ 'is-ok': idState === 'ok', 'is-bad': idState === 'bad' || idState === 'long' }"
                           x-model="idCard" placeholder="1-2345-67890-12-3" value="{{ $regCfg['idCard'] }}">
                    <span class="rd-hint" :class="{ 'rd-err': idState === 'bad' || idState === 'long' }" x-text="idHint">กรอกเลข 13 หลักบนบัตรประชาชน ระบบตรวจหลักสุดท้ายให้อัตโนมัติ</span>
                    @error('id_card_number')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="birth_date">วันเกิด (ต้องอายุ 18 ปีขึ้นไป) <span class="req">*</span></label>
                    <input id="birth_date" type="date" name="birth_date" required min="{{ $minBirthDate }}" max="{{ $maxBirthDate }}"
                           class="tp-input {{ $errors->has('birth_date') ? 'is-bad' : '' }}"
                           :class="{ 'is-ok': age !== null && age >= 18, 'is-bad': age !== null && age < 18 }"
                           x-model="birth" value="{{ $regCfg['birth'] }}">
                    <span class="rd-hint" :class="{ 'rd-err': age !== null && age < 18 }" x-text="ageHint">เลือกวันเกิดตามปฏิทินในเครื่อง</span>
                    @error('birth_date')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        {{-- ── ที่อยู่ ─────────────────────────────────────────── --}}
        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-house" style="color:var(--accent1);"></i> ที่อยู่ปัจจุบัน</h2>
            <div class="rd-field">
                <label for="address">ที่อยู่ <span class="req">*</span></label>
                <textarea id="address" name="address" required minlength="10" maxlength="500" rows="3" autocomplete="street-address"
                          class="tp-input {{ $errors->has('address') ? 'is-bad' : '' }}" placeholder="บ้านเลขที่ ซอย ถนน ตำบล/แขวง">{{ old('address', $rider?->address) }}</textarea>
                @error('address')<span class="rd-err">{{ $message }}</span>@enderror
            </div>
            <div class="rd-grid" style="--rd-min:200px;">
                <div class="rd-field">
                    <label for="district">อำเภอ/เขต</label>
                    <input id="district" type="text" name="district" maxlength="100" class="tp-input" value="{{ old('district', $rider?->district) }}" placeholder="เช่น บางรัก">
                    @error('district')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="province">จังหวัด</label>
                    <input id="province" type="text" name="province" maxlength="100" list="rd-provinces" class="tp-input" value="{{ old('province', $rider?->province) }}" placeholder="เช่น กรุงเทพมหานคร">
                    <datalist id="rd-provinces">
                        @foreach($provinces as $p)
                            <option value="{{ $p }}"></option>
                        @endforeach
                    </datalist>
                    @error('province')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        {{-- ── ยานพาหนะ ────────────────────────────────────────── --}}
        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-motorcycle" style="color:var(--accent1);"></i> ยานพาหนะที่ใช้ส่งงาน</h2>
            <div class="rd-grid" style="--rd-min:150px; gap:10px;">
                @foreach($vehicleTypes as $value => $label)
                    <label class="rd-choice">
                        <input type="radio" name="vehicle_type" value="{{ $value }}" required x-model="vehicle" @checked($vehicle === $value)>
                        <span class="box"><i class="fas {{ $ui::vehicleIcon($value) }}"></i><span style="font-weight:700; font-size:13.5px;">{{ $label }}</span></span>
                    </label>
                @endforeach
            </div>
            @error('vehicle_type')<span class="rd-err">{{ $message }}</span>@enderror

            <div class="rd-grid" style="--rd-min:180px;">
                <div class="rd-field" x-show="needPlate" x-cloak>
                    <label for="vehicle_plate">ทะเบียนรถ <span class="req">*</span></label>
                    <input id="vehicle_plate" type="text" name="vehicle_plate" maxlength="20" class="tp-input {{ $errors->has('vehicle_plate') ? 'is-bad' : '' }}"
                           :required="needPlate" value="{{ old('vehicle_plate', $rider?->vehicle_plate) }}" placeholder="เช่น 1กข 1234 กรุงเทพฯ">
                    @error('vehicle_plate')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="vehicle_brand">ยี่ห้อ/รุ่น</label>
                    <input id="vehicle_brand" type="text" name="vehicle_brand" maxlength="100" class="tp-input" value="{{ old('vehicle_brand', $rider?->vehicle_brand) }}" placeholder="เช่น Honda Wave">
                    @error('vehicle_brand')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
                <div class="rd-field">
                    <label for="vehicle_color">สีรถ</label>
                    <input id="vehicle_color" type="text" name="vehicle_color" maxlength="50" class="tp-input" value="{{ old('vehicle_color', $rider?->vehicle_color) }}" placeholder="เช่น แดง">
                    @error('vehicle_color')<span class="rd-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        {{-- ── เอกสารที่ต้องเตรียม (อัปโหลดในขั้นถัดไป) ─────────────── --}}
        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-folder-open" style="color:var(--accent1);"></i> เอกสารที่ต้องเตรียม</h2>
            <p class="rd-muted" style="font-size:13px; margin:0;">หลังส่งใบสมัคร คุณจะอัปโหลดรูปเอกสารเหล่านี้ (ถ่ายจากมือถือได้เลย) — รายการเปลี่ยนตามยานพาหนะที่เลือก</p>
            <div class="rd-grid" style="--rd-min:190px; gap:10px;">
                @foreach($documentTypes as $type => $meta)
                    <div class="rd-choice" x-show="requiredDocs.indexOf({{ \Illuminate\Support\Js::from($type) }}) !== -1" x-transition.opacity>
                        <span class="box" style="cursor:default;">
                            <i class="fas {{ $ui::documentIcon($type) }}"></i>
                            <span style="min-width:0;">
                                <span style="display:block; font-weight:700; font-size:13.5px;">{{ $meta['label'] }}</span>
                                <span class="rd-small" style="display:block;" :class="docFlags[{{ \Illuminate\Support\Js::from($type) }}] ? '' : 'rd-muted'"
                                      :style="docFlags[{{ \Illuminate\Support\Js::from($type) }}] ? 'color:var(--rd-ok); font-weight:700;' : ''"
                                      x-text="docFlags[{{ \Illuminate\Support\Js::from($type) }}] ? 'อัปโหลดแล้ว' : 'อัปโหลดขั้นถัดไป'">อัปโหลดขั้นถัดไป</span>
                            </span>
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="rd-hint"><i class="fas fa-lock"></i> รูปเอกสารเก็บแบบส่วนตัว ไม่เปิดเผยต่อสาธารณะ เปิดดูได้เฉพาะคุณและทีมงานที่ตรวจสอบ</div>
        </section>

        {{-- ── ความยินยอม PDPA ─────────────────────────────────── --}}
        <section class="tp-card rd-stack">
            <h2 class="rd-h2"><i class="fas fa-shield-halved" style="color:var(--accent1);"></i> การเก็บและใช้ข้อมูลส่วนบุคคล (PDPA)</h2>
            <details class="rd-details" style="padding:0 4px;">
                <summary>อ่านรายละเอียดว่าเราใช้ข้อมูลอะไร เพื่ออะไร <i class="fas fa-chevron-down chev"></i></summary>
                <div class="rd-muted" style="font-size:13px; line-height:1.7; padding-bottom:6px;">
                    <b style="color:var(--ink);">ข้อมูลที่เก็บ:</b> ชื่อ-นามสกุล เบอร์โทร เลขบัตรประชาชน วันเกิด ที่อยู่ ข้อมูลยานพาหนะ รูปเอกสาร (บัตรประชาชน ใบขับขี่ ทะเบียนรถ รูปหน้าตรง)
                    และตำแหน่ง GPS ระหว่างที่คุณเปิดรับงานหรือกำลังส่งงาน<br>
                    <b style="color:var(--ink);">เพื่อ:</b> ยืนยันตัวตนและคุณสมบัติไรเดอร์ จับคู่งานใกล้คุณ ให้ลูกค้าติดตามการส่งของ ความปลอดภัยของลูกค้าและร้านค้า และการจ่ายรายได้<br>
                    <b style="color:var(--ink);">การเปิดเผย:</b> ลูกค้าและร้านเห็นชื่อ เบอร์ ยานพาหนะ รูปหน้าตรง และตำแหน่งของคุณเฉพาะงานที่คุณรับ ส่วนเลขบัตรและรูปเอกสารเห็นเฉพาะทีมงานที่ตรวจสอบ<br>
                    <b style="color:var(--ink);">สิทธิ์ของคุณ:</b> ขอดู แก้ไข หรือขอลบข้อมูล และถอนความยินยอมได้ โดยติดต่อทีมงาน หรือลบบัญชีได้ที่หน้าตั้งค่าความปลอดภัย
                    — อ่านเพิ่มเติมที่<a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="rd-link"> นโยบายความเป็นส่วนตัว</a>
                </div>
            </details>
            <label class="rd-alert rd-tone-gold" style="cursor:pointer;">
                <input type="checkbox" name="pdpa_consent" value="1" required @checked(old('pdpa_consent'))
                       style="width:22px; height:22px; accent-color:var(--accent1); flex:none; margin-top:1px;">
                <span>ฉันยืนยันว่าข้อมูลเป็นความจริง และยินยอมให้เก็บและใช้ข้อมูลส่วนบุคคลตามรายละเอียดข้างต้น</span>
            </label>
            @error('pdpa_consent')<span class="rd-err">{{ $message }}</span>@enderror
        </section>

        <div class="rd-sticky-actions">
            <button type="submit" class="rd-btn3d rd-tone-gold lg block" :disabled="submitting">
                <i class="fas" :class="submitting ? 'fa-circle-notch rd-spin' : 'fa-paper-plane'"></i> {{ $submitLabel }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script>
    window.rdRegister = function (cfg) {
        // ตรวจเลขบัตรประชาชนไทย (สูตรเดียวกับ App\Rules\ThaiNationalId)
        function validThaiId(d) {
            if (!/^\d{13}$/.test(d) || d.charAt(0) === '0' || /^(\d)\1{12}$/.test(d)) { return false; }
            var sum = 0;
            for (var i = 0; i < 12; i++) { sum += parseInt(d.charAt(i), 10) * (13 - i); }
            return (11 - (sum % 11)) % 10 === parseInt(d.charAt(12), 10);
        }

        return {
            idCard: cfg.idCard || '',
            birth: cfg.birth || '',
            vehicle: cfg.vehicle || 'motorcycle',
            docFlags: cfg.docFlags || {},
            submitting: false,
            init() {
                this.formatId();
                this.$watch('idCard', () => this.formatId());
            },
            get idDigits() { return String(this.idCard || '').replace(/\D/g, ''); },
            get idState() {
                var d = this.idDigits;
                if (!d.length) { return 'empty'; }
                if (d.length < 13) { return 'short'; }
                if (d.length > 13) { return 'long'; }
                return validThaiId(d) ? 'ok' : 'bad';
            },
            get idHint() {
                switch (this.idState) {
                    case 'empty': return 'กรอกเลข 13 หลักบนบัตรประชาชน ระบบตรวจหลักสุดท้ายให้อัตโนมัติ';
                    case 'short': return 'กรอกแล้ว ' + this.idDigits.length + '/13 หลัก';
                    case 'long': return 'เลขบัตรเกิน 13 หลัก กรุณาตรวจสอบ';
                    case 'ok': return '✓ รูปแบบเลขบัตรถูกต้อง';
                    default: return 'เลขบัตรไม่ถูกต้อง (หลักสุดท้ายไม่ตรงกับสูตรตรวจสอบ) กรุณาตรวจอีกครั้ง';
                }
            },
            get age() {
                if (!/^\d{4}-\d{2}-\d{2}$/.test(this.birth)) { return null; }
                var p = this.birth.split('-').map(function (x) { return parseInt(x, 10); });
                var now = new Date();
                var years = now.getFullYear() - p[0];
                if (now.getMonth() + 1 < p[1] || (now.getMonth() + 1 === p[1] && now.getDate() < p[2])) { years--; }
                return years;
            },
            get ageHint() {
                if (this.age === null) { return 'เลือกวันเกิดตามปฏิทินในเครื่อง'; }
                var be = parseInt(this.birth.slice(0, 4), 10) + 543;
                if (this.age < 18) { return 'อายุ ' + this.age + ' ปี — ต้องอายุ 18 ปีบริบูรณ์ขึ้นไปจึงสมัครได้'; }
                return 'เกิดปี พ.ศ. ' + be + ' · อายุ ' + this.age + ' ปี';
            },
            get needPlate() { return this.vehicle === 'motorcycle' || this.vehicle === 'car'; },
            get requiredDocs() { return (cfg.requiredDocs && cfg.requiredDocs[this.vehicle]) ? cfg.requiredDocs[this.vehicle] : []; },
            formatId() {
                // จัดรูปแบบ 1-2345-67890-12-3 ระหว่างพิมพ์ (เซิร์ฟเวอร์ตัดขีดออกเองก่อนตรวจ)
                var d = this.idDigits.slice(0, 13);
                var parts = [d.slice(0, 1), d.slice(1, 5), d.slice(5, 10), d.slice(10, 12), d.slice(12, 13)].filter(function (x) { return x.length; });
                var formatted = parts.join('-');
                if (formatted !== this.idCard) { this.idCard = formatted; }
            },
            guard(event) {
                if (this.submitting) { event.preventDefault(); return; }
                var form = event.target;
                if (this.idState !== 'ok') {
                    event.preventDefault();
                    window.showNotification && window.showNotification('กรุณาตรวจสอบเลขบัตรประชาชนให้ถูกต้อง', 'error');
                    form.querySelector('#id_card_number').focus();
                    return;
                }
                if (this.age !== null && this.age < 18) {
                    event.preventDefault();
                    window.showNotification && window.showNotification('ผู้สมัครไรเดอร์ต้องมีอายุ 18 ปีบริบูรณ์ขึ้นไป', 'error');
                    form.querySelector('#birth_date').focus();
                    return;
                }
                if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                    event.preventDefault();
                    return;
                }
                this.submitting = true;
            }
        };
    };
    </script>
@endpush
