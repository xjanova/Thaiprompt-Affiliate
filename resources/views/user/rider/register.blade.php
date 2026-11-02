{{--
    สมัครเป็นไรเดอร์ / แก้ไขใบสมัคร / ส่งใบสมัครใหม่ (user.rider.register) — ธีมโนวาเต็ม
    Controller: User\RiderController@register → POST user.rider.register.submit (RiderRegistrationRequest + pdpa_consent)
    ตัวแปร: rider (Rider|null), isReapply, vehicleTypes, requiredDocumentsByVehicle, documentTypes, documentFlags,
            minBirthDate, maxBirthDate, formAction, pageTitle
    ฟิลด์: full_name, phone, id_card_number (13 หลัก + checksum), birth_date (อายุ 18+), address, province, district,
           vehicle_type, vehicle_plate (บังคับเมื่อมอเตอร์ไซค์/รถยนต์), vehicle_brand, vehicle_color, pdpa_consent
    สำเร็จ → หน้าอัปโหลดเอกสาร (user.rider.documents)
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'สมัครเป็นไรเดอร์')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $authUser = auth()->user();
    $isPendingEdit = $rider !== null && $rider->status === 'pending';
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

    $steps = [
        ['label' => '1. กรอกข้อมูล', 'state' => 'current'],
        ['label' => '2. อัปโหลดเอกสาร', 'state' => $rider && ($documentFlags['id_card'] ?? false) ? 'done' : 'todo'],
        ['label' => '3. ทีมงานตรวจสอบ', 'state' => 'todo'],
        ['label' => '4. เริ่มรับงาน', 'state' => 'todo'],
    ];
@endphp

@section('content')
<div class="nvm-stack">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'register'])

    {{-- ── หัวหน้า: ชวนมาเป็นไรเดอร์ ──────────────────────────── --}}
    <section class="nvm-ph" aria-labelledby="nvm-rd-reg-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <div class="nvm-eyebrow nvm-latin">Become a rider</div>
                <h1 id="nvm-rd-reg-h">{{ $isReapply ? 'ส่งใบสมัครไรเดอร์ใหม่' : ($isPendingEdit ? 'แก้ไขใบสมัครไรเดอร์' : 'ขับรถส่งของ รับรายได้ใกล้บ้าน') }}</h1>
                <ul class="nvm-rd-perks">
                    <li><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>รายได้ค่าส่งเข้ากระเป๋าเงินทันทีที่ส่งสำเร็จ</li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>เลือกเวลาทำงานเอง เปิด-ปิดรับงานได้ตลอด</li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>งานใกล้บ้านจากร้านค้าและตลาดสดในชุมชน</li>
                </ul>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/earn/rider.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    {{-- ── ขั้นตอน ────────────────────────────────────────────── --}}
    <div class="nvm-card nvm-box">
        @include('user.rider.partials.steps', ['steps' => $steps, 'stepsLabel' => 'ขั้นตอนการสมัคร'])
    </div>

    @if($isReapply)
        <section class="nvm-card nvm-alert nvm-alert--bad" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></span>
            <div class="nvm-alert__txt">
                <b>ใบสมัครครั้งก่อนไม่ผ่าน</b>
                @if($rider?->rejection_reason)<p>{{ $rider->rejection_reason }}</p>@endif
                <p>แก้ไขข้อมูลตามที่แจ้งแล้วส่งใหม่ได้เลย จากนั้นตรวจเอกสารให้ครบอีกครั้ง</p>
            </div>
        </section>
    @elseif($isPendingEdit)
        <section class="nvm-card nvm-alert nvm-alert--info" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg></span>
            <div class="nvm-alert__txt"><b>ใบสมัครของคุณอยู่ระหว่างตรวจสอบ</b><p>แก้ไขข้อมูลได้ ข้อมูลใหม่จะแทนที่ของเดิม</p></div>
            <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.rider.status') }}">ดูสถานะ</a>
        </section>
    @endif

    @if($errors->any())
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b>กรุณาตรวจสอบข้อมูลอีกครั้ง</b>
                @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        </section>
    @endif

    <form method="POST" action="{{ $formAction }}" class="nvm-form" novalidate
          x-data="rdRegister({{ \Illuminate\Support\Js::from($regCfg) }})" x-on:submit="guard($event)">
        @csrf

        {{-- ── ข้อมูลส่วนตัว ──────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-me-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Personal</small><h2 id="nvm-rd-me-h">ข้อมูลส่วนตัว</h2></div></div>
            <div class="nvm-rd-grid">
                <div class="nvm-field">
                    <label for="full_name" class="nvm-label">ชื่อ-นามสกุล (ตามบัตรประชาชน) <em>*</em></label>
                    <input id="full_name" type="text" name="full_name" required minlength="4" maxlength="255" autocomplete="name"
                           class="nvm-input {{ $errors->has('full_name') ? 'is-bad' : '' }}" value="{{ old('full_name', $rider?->full_name ?? $authUser?->name) }}" placeholder="เช่น สมชาย ใจดี">
                    @error('full_name')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label for="phone" class="nvm-label">เบอร์มือถือ <em>*</em></label>
                    <input id="phone" type="tel" name="phone" required inputmode="tel" autocomplete="tel" maxlength="15"
                           class="nvm-input {{ $errors->has('phone') ? 'is-bad' : '' }}" value="{{ old('phone', $rider?->phone ?? $authUser?->phone) }}" placeholder="0812345678">
                    <span class="nvm-help">ลูกค้าและร้านจะใช้เบอร์นี้ติดต่อคุณระหว่างส่งงาน</span>
                    @error('phone')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label for="id_card_number" class="nvm-label">เลขบัตรประชาชน 13 หลัก <em>*</em></label>
                    <input id="id_card_number" type="text" name="id_card_number" required inputmode="numeric" autocomplete="off" maxlength="17"
                           class="nvm-input nvm-num {{ $errors->has('id_card_number') ? 'is-bad' : '' }}"
                           :class="{ 'nvm-rd-ok': idState === 'ok', 'is-bad': idState === 'bad' || idState === 'long' }"
                           x-model="idCard" placeholder="1-2345-67890-12-3" value="{{ $regCfg['idCard'] }}">
                    <span class="nvm-help" :class="{ 'nvm-rd-bad': idState === 'bad' || idState === 'long', 'nvm-rd-ok': idState === 'ok' }" x-text="idHint">กรอกเลข 13 หลักบนบัตรประชาชน ระบบตรวจหลักสุดท้ายให้อัตโนมัติ</span>
                    @error('id_card_number')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label for="birth_date" class="nvm-label">วันเกิด (ต้องอายุ 18 ปีขึ้นไป) <em>*</em></label>
                    <input id="birth_date" type="date" name="birth_date" required min="{{ $minBirthDate }}" max="{{ $maxBirthDate }}"
                           class="nvm-input {{ $errors->has('birth_date') ? 'is-bad' : '' }}"
                           :class="{ 'nvm-rd-ok': age !== null && age >= 18, 'is-bad': age !== null && age < 18 }"
                           x-model="birth" value="{{ $regCfg['birth'] }}">
                    <span class="nvm-help" :class="{ 'nvm-rd-bad': age !== null && age < 18 }" x-text="ageHint">เลือกวันเกิดตามปฏิทินในเครื่อง</span>
                    @error('birth_date')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        {{-- ── ที่อยู่ ─────────────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-addr-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Address</small><h2 id="nvm-rd-addr-h">ที่อยู่ปัจจุบัน</h2></div></div>
            <div class="nvm-rd-grid">
                <div class="nvm-field nvm-rd-span">
                    <label for="address" class="nvm-label">ที่อยู่ <em>*</em></label>
                    <textarea id="address" name="address" required minlength="10" maxlength="500" rows="3" autocomplete="street-address"
                              class="nvm-input {{ $errors->has('address') ? 'is-bad' : '' }}" placeholder="บ้านเลขที่ ซอย ถนน ตำบล/แขวง">{{ old('address', $rider?->address) }}</textarea>
                    @error('address')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label for="district" class="nvm-label">อำเภอ/เขต</label>
                    <input id="district" type="text" name="district" maxlength="100" class="nvm-input" value="{{ old('district', $rider?->district) }}" placeholder="เช่น บางรัก">
                    @error('district')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label for="province" class="nvm-label">จังหวัด</label>
                    <input id="province" type="text" name="province" maxlength="100" list="rd-provinces" class="nvm-input" value="{{ old('province', $rider?->province) }}" placeholder="เช่น กรุงเทพมหานคร">
                    <datalist id="rd-provinces">
                        @foreach($provinces as $p)
                            <option value="{{ $p }}"></option>
                        @endforeach
                    </datalist>
                    @error('province')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        {{-- ── ยานพาหนะ ────────────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-veh-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Vehicle</small><h2 id="nvm-rd-veh-h">ยานพาหนะที่ใช้ส่งงาน</h2></div></div>
            <div class="nvm-opts nvm-rd-opts" role="radiogroup" aria-label="ประเภทยานพาหนะ">
                @foreach($vehicleTypes as $value => $label)
                    <label class="nvm-opt">
                        <input type="radio" name="vehicle_type" value="{{ $value }}" required x-model="vehicle" @checked($vehicle === $value)>
                        <span class="nvm-opt__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaVehicleIcon($value) }}"/></svg></span>
                        <b>{{ $label }}</b>
                    </label>
                @endforeach
            </div>
            @error('vehicle_type')<span class="nvm-err">{{ $message }}</span>@enderror

            <div class="nvm-rd-grid nvm-rd-grid--3 nvm-gap-t">
                <div class="nvm-field" x-show="needPlate" x-cloak>
                    <label for="vehicle_plate" class="nvm-label">ทะเบียนรถ <em>*</em></label>
                    <input id="vehicle_plate" type="text" name="vehicle_plate" maxlength="20" class="nvm-input {{ $errors->has('vehicle_plate') ? 'is-bad' : '' }}"
                           :required="needPlate" value="{{ old('vehicle_plate', $rider?->vehicle_plate) }}" placeholder="เช่น 1กข 1234 กรุงเทพฯ">
                    @error('vehicle_plate')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label for="vehicle_brand" class="nvm-label">ยี่ห้อ/รุ่น</label>
                    <input id="vehicle_brand" type="text" name="vehicle_brand" maxlength="100" class="nvm-input" value="{{ old('vehicle_brand', $rider?->vehicle_brand) }}" placeholder="เช่น Honda Wave">
                    @error('vehicle_brand')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                <div class="nvm-field">
                    <label for="vehicle_color" class="nvm-label">สีรถ</label>
                    <input id="vehicle_color" type="text" name="vehicle_color" maxlength="50" class="nvm-input" value="{{ old('vehicle_color', $rider?->vehicle_color) }}" placeholder="เช่น แดง">
                    @error('vehicle_color')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        {{-- ── เอกสารที่ต้องเตรียม (อัปโหลดในขั้นถัดไป) ─────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-prep-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Documents</small><h2 id="nvm-rd-prep-h">เอกสารที่ต้องเตรียม</h2></div></div>
            <p class="nvm-desc">หลังส่งใบสมัคร คุณจะอัปโหลดรูปเอกสารเหล่านี้ (ถ่ายจากมือถือได้เลย) — รายการเปลี่ยนตามยานพาหนะที่เลือก</p>
            <ul class="nvm-rd-list nvm-gap-t">
                @foreach($documentTypes as $type => $meta)
                    @php $tj = \Illuminate\Support\Js::from($type); @endphp
                    <li x-show="requiredDocs.indexOf({{ $tj }}) !== -1" x-transition.opacity>
                        <span class="nvm-rd-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaDocumentIcon($type) }}"/></svg></span>
                        <span>
                            <b>{{ $meta['label'] }}</b>
                            <small :class="docFlags[{{ $tj }}] ? 'is-ok' : ''" x-text="docFlags[{{ $tj }}] ? 'อัปโหลดแล้ว' : 'อัปโหลดขั้นถัดไป'">อัปโหลดขั้นถัดไป</small>
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>รูปเอกสารเก็บแบบส่วนตัว ไม่เปิดเผยต่อสาธารณะ เปิดดูได้เฉพาะคุณและทีมงานที่ตรวจสอบ</p>
        </section>

        {{-- ── ความยินยอม PDPA ─────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-pdpa-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Privacy</small><h2 id="nvm-rd-pdpa-h">การเก็บและใช้ข้อมูลส่วนบุคคล (PDPA)</h2></div></div>
            <details class="nvm-rd-details">
                <summary>อ่านรายละเอียดว่าเราใช้ข้อมูลอะไร เพื่ออะไร <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></summary>
                <dl>
                    <dt>ข้อมูลที่เก็บ</dt>
                    <dd>ชื่อ-นามสกุล เบอร์โทร เลขบัตรประชาชน วันเกิด ที่อยู่ ข้อมูลยานพาหนะ รูปเอกสาร (บัตรประชาชน ใบขับขี่ ทะเบียนรถ รูปหน้าตรง) และตำแหน่ง GPS ระหว่างที่คุณเปิดรับงานหรือกำลังส่งงาน</dd>
                    <dt>เพื่อ</dt>
                    <dd>ยืนยันตัวตนและคุณสมบัติไรเดอร์ จับคู่งานใกล้คุณ ให้ลูกค้าติดตามการส่งของ ความปลอดภัยของลูกค้าและร้านค้า และการจ่ายรายได้</dd>
                    <dt>การเปิดเผย</dt>
                    <dd>ลูกค้าและร้านเห็นชื่อ เบอร์ ยานพาหนะ รูปหน้าตรง และตำแหน่งของคุณเฉพาะงานที่คุณรับ ส่วนเลขบัตรและรูปเอกสารเห็นเฉพาะทีมงานที่ตรวจสอบ</dd>
                    <dt>สิทธิ์ของคุณ</dt>
                    <dd>ขอดู แก้ไข หรือขอลบข้อมูล และถอนความยินยอมได้ โดยติดต่อทีมงาน หรือลบบัญชีได้ที่หน้าตั้งค่าความปลอดภัย — อ่านเพิ่มเติมที่<a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="nvm-link">นโยบายความเป็นส่วนตัว</a></dd>
                </dl>
            </details>
            <label class="nvm-rd-consent-box nvm-gap-t">
                <input type="checkbox" name="pdpa_consent" value="1" required @checked(old('pdpa_consent'))>
                <span>ฉันยืนยันว่าข้อมูลเป็นความจริง และยินยอมให้เก็บและใช้ข้อมูลส่วนบุคคลตามรายละเอียดข้างต้น</span>
            </label>
            @error('pdpa_consent')<span class="nvm-err">{{ $message }}</span>@enderror
        </section>

        <div class="nvm-rd-sticky">
            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="submitting">
                <svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="submitting ? 'กำลังส่ง…' : @js($submitLabel)">{{ $submitLabel }}</span>
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
