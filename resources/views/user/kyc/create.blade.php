{{--
    ส่งเอกสารยืนยันตัวตน (ธีมโนวาเต็ม)
    POST user.kyc.store (KycController::store) — id_card_image + selfie_image · jpeg/jpg/png · ไม่เกิน 5MB ต่อไฟล์
    - ตรวจชนิด/ขนาดไฟล์ในเบราว์เซอร์ก่อน (ฝั่งเซิร์ฟเวอร์ตรวจซ้ำเสมอ) จะได้ไม่ต้องรออัปโหลดรูปใหญ่แล้วค่อยรู้ว่าไม่ผ่าน
    - กดส่งแล้วปุ่มล็อก กันกดซ้ำระหว่างอัปโหลด · กลับมาด้วยปุ่มย้อนกลับของเบราว์เซอร์ = ปลดล็อก (pageshow)
--}}
@extends('layouts.user-nova')

@section('title', 'ส่งเอกสารยืนยันตัวตน')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");

    // ชื่อช่อง => [หัวข้อ, คำอธิบายในกล่องเลือกรูป, ภาพประกอบ]
    $slots = [
        'id_card_image' => ['รูปบัตรประชาชน / ใบขับขี่', 'ถ่ายทั้งใบให้เห็นข้อความและตัวเลขครบ', 'idcard'],
        'selfie_image' => ['รูปตัวเองถือบัตร', 'ถือบัตรไว้ข้างใบหน้า ไม่สวมหมวกหรือแว่นดำ', 'profile'],
    ];
    $hadErrors = $errors->any();
@endphp

@section('content')

<div class="nvm-stack" x-data="nvmKycUpload()" @pageshow.window="busy = false">

<section class="nvm-ph" aria-labelledby="nvm-kyc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.kyc.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>สถานะการยืนยันตัวตน</a>
            <div class="nvm-eyebrow nvm-latin">Submit documents</div>
            <h1 id="nvm-kyc-h">ส่งเอกสารยืนยันตัวตน</h1>
            <p class="nvm-ph__lead">อัปโหลดรูป 2 รูป ระบบอ่านข้อมูลจากบัตรให้อัตโนมัติ แล้วทีมงานตรวจภายใน 1-3 วันทำการ</p>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('shield', 'deco/scroll') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ส่งเอกสารไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

@if($hadErrors)
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ส่งเอกสารไม่สำเร็จ</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            <p>เบราว์เซอร์ไม่เก็บรูปที่เลือกไว้ กรุณาเลือกรูปทั้ง 2 รูปใหม่อีกครั้ง</p>
        </div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-kyc-f-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Documents</small><h2 id="nvm-kyc-f-h">เลือกรูปเอกสาร</h2></div></div>

        <form method="POST" action="{{ route('user.kyc.store') }}" enctype="multipart/form-data" class="nvm-form" novalidate @submit="submit($event)">
            @csrf

            @foreach($slots as $name => [$label, $hint, $art])
                <div class="nvm-field">
                    <span class="nvm-label" id="{{ $name }}-lbl">{{ $label }} <em>*</em></span>
                    <label class="nvm-drop-zone nvm-ky-drop" :class="{ 'has-file': files.{{ $name }}, 'is-bad': errors.{{ $name }} }">
                        <template x-if="! files.{{ $name }}">
                            <span class="nvm-ky-drop__idle">
                                <img src="{{ $nvArt($art, 'deco/scroll') }}" alt="" width="72" height="72" loading="lazy">
                                <b>แตะเพื่อถ่ายรูปหรือเลือกรูป</b>
                                <small>{{ $hint }}</small>
                                <small>JPG หรือ PNG ไม่เกิน 5 MB</small>
                            </span>
                        </template>
                        <template x-if="files.{{ $name }}">
                            <span class="nvm-ky-drop__pick">
                                <img :src="files.{{ $name }}.url" alt="ตัวอย่าง{{ $label }}">
                                <small class="nvm-num" x-text="files.{{ $name }}.name + ' · ' + files.{{ $name }}.size"></small>
                            </span>
                        </template>
                        <input type="file" name="{{ $name }}" id="{{ $name }}" class="nvm-sr" accept="image/jpeg,image/png" aria-labelledby="{{ $name }}-lbl" x-ref="{{ $name }}" @change="pick('{{ $name }}', $event)">
                    </label>
                    <div class="nvm-ky-drop__acts" x-show="files.{{ $name }}" x-cloak>
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="$refs.{{ $name }}.click()">เปลี่ยนรูป</button>
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="clear('{{ $name }}')">ลบรูป</button>
                    </div>
                    <span class="nvm-err" x-show="errors.{{ $name }}" x-text="errors.{{ $name }}" x-cloak></span>
                    @error($name)<span class="nvm-err" x-show="! errors.{{ $name }}">{{ $message }}</span>@enderror
                </div>
            @endforeach

            <div class="nvm-filter__btns">
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.kyc.index') }}">ยกเลิก</a>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังส่งเอกสาร…' : 'ส่งเอกสาร'">ส่งเอกสาร</span></button>
            </div>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>รูปเอกสารเก็บในพื้นที่ส่วนตัว เปิดดูได้เฉพาะคุณและทีมงานที่ตรวจ</p>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-kyc-tip-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Photo tips</small><h2 id="nvm-kyc-tip-h">ถ่ายรูปให้ผ่านในครั้งเดียว</h2></div></div>
        <ol class="nvm-steps">
            <li>ใช้บัตรประชาชนหรือใบขับขี่ที่ยังไม่หมดอายุ</li>
            <li>วางบัตรบนพื้นเรียบ ถ่ายตรงๆ ให้เห็นครบทั้งใบ ไม่ตัดขอบ</li>
            <li>ถ่ายในที่สว่าง ไม่มีแสงสะท้อนทับตัวหนังสือ ภาพไม่มืดและไม่เบลอ</li>
            <li>รูปคู่บัตร ถือบัตรไว้ข้างใบหน้า ให้เห็นทั้งหน้าและบัตรชัดเจน</li>
        </ol>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ระบบแปลงรูปเป็น WebP ให้เองหลังส่ง ไม่ต้องย่อรูปก่อน</p>
    </aside>
</div>

</div>
@endsection

@push('scripts')
<script>
/**
 * ฟอร์มส่งเอกสาร KYC — แสดงตัวอย่างรูป ตรวจชนิด/ขนาดก่อนส่ง และกันกดส่งซ้ำ
 *
 * ใช้ object URL แทน FileReader (ไม่ต้องอ่านรูป 5MB เป็น base64 ทั้งก้อน) และคืนหน่วยความจำทุกครั้งที่เปลี่ยน/ลบรูป
 */
function nvmKycUpload() {
    const MAX = 5 * 1024 * 1024;
    const TYPES = ['image/jpeg', 'image/png'];
    const LABELS = { id_card_image: 'รูปบัตรประชาชน / ใบขับขี่', selfie_image: 'รูปตัวเองถือบัตร' };

    return {
        busy: false,
        files: { id_card_image: null, selfie_image: null },
        errors: { id_card_image: '', selfie_image: '' },

        init() {
            window.addEventListener('pagehide', () => Object.keys(this.files).forEach((name) => this.release(name)));
        },

        pick(name, event) {
            const file = event.target.files[0];
            this.errors[name] = '';
            if (! file) { return; }

            // บางเครื่องไม่บอกชนิดไฟล์ (type ว่าง) — ปล่อยให้เซิร์ฟเวอร์ตัดสิน
            if (file.type && ! TYPES.includes(file.type)) {
                this.clear(name);
                this.errors[name] = LABELS[name] + 'ต้องเป็นไฟล์ JPG หรือ PNG เท่านั้น';
                return;
            }
            if (file.size > MAX) {
                this.clear(name);
                this.errors[name] = LABELS[name] + 'ใหญ่เกิน 5 MB กรุณาถ่ายใหม่หรือเลือกรูปที่เล็กลง';
                return;
            }

            this.release(name);
            const mb = file.size / 1024 / 1024;
            this.files[name] = {
                url: URL.createObjectURL(file),
                name: file.name,
                size: mb >= 1 ? mb.toFixed(1) + ' MB' : Math.max(1, Math.round(file.size / 1024)) + ' KB',
            };
        },

        clear(name) {
            this.release(name);
            this.files[name] = null;
            if (this.$refs[name]) { this.$refs[name].value = ''; }
        },

        release(name) {
            if (this.files[name]) { URL.revokeObjectURL(this.files[name].url); }
        },

        submit(event) {
            if (this.busy) { event.preventDefault(); return; }

            let ok = true;
            Object.keys(this.files).forEach((name) => {
                if (! this.files[name]) {
                    this.errors[name] = this.errors[name] || 'กรุณาเลือก' + LABELS[name];
                    ok = false;
                }
            });
            if (! ok) {
                event.preventDefault();
                return;
            }

            this.busy = true;
        },
    };
}
</script>
@endpush
