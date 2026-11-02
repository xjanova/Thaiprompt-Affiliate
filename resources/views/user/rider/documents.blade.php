{{--
    เอกสารไรเดอร์ (user.rider.documents) — สถานะรายเอกสาร + อัปโหลด/อัปโหลดใหม่ พร้อมพรีวิว · ธีมโนวาเต็ม
    Controller: User\RiderController@documents
    ตัวแปร: rider, documents [{type,label,uploaded,required,url}], documentTypes, missingDocuments, missingDocumentLabels,
            documentsComplete, documentsPendingReview, uploadUrl, pageTitle
    อัปโหลด: POST user.rider.documents.upload (multipart document_type + document ≤10MB jpg/png/webp/heic)
            AJAX ได้ data {type, uploaded, url, documents, documents_missing, documents_complete}
            ไม่มี JS → ฟอร์มส่งแบบปกติแล้ว redirect กลับพร้อมข้อความ (ระบบเดิมรองรับทั้งสองแบบ)
    ไฟล์อยู่บน private disk — รูปเปิดผ่าน user.rider.documents.file เท่านั้น
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'เอกสารไรเดอร์')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $requiredDocs = array_values(array_filter($documents, fn ($d) => $d['required']));
    $requiredUploaded = count(array_filter($requiredDocs, fn ($d) => $d['uploaded']));
    $requiredTotal = max(1, count($requiredDocs));
    $percent = (int) round($requiredUploaded / $requiredTotal * 100);
    $isApproved = in_array($rider->status, ['approved', 'suspended'], true);
    $docHints = [
        'id_card' => 'ถ่ายด้านหน้าบัตรให้เห็นชื่อ เลขบัตร และรูปชัดเจน',
        'driver_license' => 'ใบขับขี่ที่ยังไม่หมดอายุ ตรงกับประเภทรถที่ใช้',
        'vehicle_registration' => 'หน้าสำเนาทะเบียนรถที่เห็นเลขทะเบียน',
        'profile' => 'รูปหน้าตรง ไม่ใส่หมวก/แว่นดำ แสงสว่างพอ — ลูกค้าจะเห็นรูปนี้',
    ];
    $docCfg = [
        'uploadUrl' => $uploadUrl,
        'isApproved' => $isApproved,
        'sensitive' => ['id_card', 'driver_license', 'vehicle_registration'],
        'docs' => collect($documents)->mapWithKeys(fn ($d) => [$d['type'] => [
            'uploaded' => (bool) $d['uploaded'],
            'required' => (bool) $d['required'],
            'url' => $d['url'],
            'label' => $d['label'],
        ]])->all(),
        'missing' => array_values($missingDocuments),
        'complete' => (bool) $documentsComplete,
        'statusUrl' => route('user.rider.status'),
    ];
@endphp

@section('content')
<div class="nvm-stack" x-data="rdDocuments({{ \Illuminate\Support\Js::from($docCfg) }})" @keydown.escape.window="confirmType = null">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'documents'])

    {{-- ── หัวหน้า: ความคืบหน้าเอกสารที่ต้องมี ─────────────────── --}}
    <section class="nvm-ph" aria-labelledby="nvm-rd-doc-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <div class="nvm-eyebrow nvm-latin">Rider documents</div>
                <h1 id="nvm-rd-doc-h">เอกสารไรเดอร์</h1>
                <p class="nvm-ph__lead">
                    ต้องมี: {{ collect($requiredDocs)->pluck('label')->implode(' · ') }}
                    @if(!in_array($rider->vehicle_type, ['motorcycle', 'car'], true))
                        — ใช้{{ $rider->vehicle_type_text }} ไม่ต้องใช้ใบขับขี่และทะเบียนรถ
                    @endif
                </p>
                <div class="nvm-pills">
                    <span class="nvm-pill" :class="complete ? 'nvm-pill--ok' : 'nvm-pill--warn'">
                        <svg class="nvm-ico"><use href="#nvm-i-id"/></svg>
                        <span>อัปโหลดแล้ว <span class="nvm-num" x-text="uploadedCount() + '/' + requiredCount()">{{ $requiredUploaded }}/{{ count($requiredDocs) }}</span> รายการ</span>
                    </span>
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>เปิดดูได้เฉพาะคุณและทีมงานที่ตรวจ</span>
                </div>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/account/idcard.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    <div class="nvm-card nvm-box">
        <div class="nvm-meter">
            <div class="nvm-meter__row"><span>ความครบของเอกสารที่ต้องมี</span><b class="nvm-num" x-text="percent() + '%'">{{ $percent }}%</b></div>
            <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="percent()" aria-valuenow="{{ $percent }}" aria-label="ความครบของเอกสาร">
                <i class="nvm-rd-meter" :style="'--w:' + percent() + '%'" style="--w: {{ $percent }}%"></i>
            </div>
        </div>
    </div>

    {{-- ── สถานะ ──────────────────────────────────────────────── --}}
    @if($rider->status === 'rejected')
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></span>
            <div class="nvm-alert__txt">
                <b>ใบสมัครไม่ผ่านการอนุมัติ</b>
                @if($rider->rejection_reason)<p>{{ $rider->rejection_reason }}</p>@endif
                <p>แก้ไขเอกสารตามที่แจ้ง แล้วส่งใบสมัครใหม่</p>
            </div>
            <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.rider.register') }}">ส่งใบสมัครใหม่</a>
        </section>
    @elseif($documentsPendingReview)
        <section class="nvm-card nvm-alert" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg></span>
            <div class="nvm-alert__txt"><b>เอกสารที่เปลี่ยนใหม่รอทีมงานตรวจสอบ</b><p>ระหว่างนี้ยังเปิดรับงานไม่ได้ ทีมงานจะแจ้งผลผ่านการแจ้งเตือน</p></div>
        </section>
    @endif

    <template x-if="complete && {{ $rider->status === 'pending' ? 'true' : 'false' }}">
        <section class="nvm-card nvm-alert nvm-alert--ok" role="status">
            <img class="nvm-alert__img" src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="58" height="58">
            <div class="nvm-alert__txt"><b>เอกสารครบแล้ว!</b><p>ทีมงานกำลังตรวจสอบใบสมัครของคุณ จะแจ้งผลผ่านการแจ้งเตือนโดยเร็ว</p></div>
            <a :href="cfg.statusUrl" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ดูสถานะใบสมัคร</a>
        </section>
    </template>
    <template x-if="!complete">
        <section class="nvm-card nvm-alert" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg></span>
            <div class="nvm-alert__txt"><b>ยังขาดเอกสาร: <span x-text="missingLabels()">{{ $missingDocumentLabels }}</span></b><p>อัปโหลดให้ครบเพื่อให้ทีมงานตรวจอนุมัติได้</p></div>
        </section>
    </template>
    @if($isApproved)
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>อัปโหลดใหม่ได้เมื่อเอกสารหมดอายุหรือเปลี่ยนแปลง — การเปลี่ยนบัตรประชาชน ใบขับขี่ หรือทะเบียนรถหลังอนุมัติ ต้องรอทีมงานตรวจใหม่ และระบบจะปิดรับงานชั่วคราว</p>
    @endif

    {{-- ── การ์ดเอกสาร ────────────────────────────────────────── --}}
    <div class="nvm-rd-docs">
        @foreach($documents as $doc)
            @php
                $type = $doc['type'];
                // ชื่อประเภทเอกสารในรูปสตริง JS ที่ปลอดภัยสำหรับใส่ในแอตทริบิวต์ Alpine
                $tj = \Illuminate\Support\Js::from($type);
            @endphp
            <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data" class="nvm-card nvm-box nvm-form"
                  x-on:submit.prevent="upload($event, {{ $tj }})" aria-labelledby="nvm-rd-doc-{{ $type }}">
                @csrf
                <input type="hidden" name="document_type" value="{{ $type }}">

                <div class="nvm-rd-job__head">
                    <div class="nvm-rd-job__who">
                        <span class="nvm-rd-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaDocumentIcon($type) }}"/></svg></span>
                        <div>
                            <b id="nvm-rd-doc-{{ $type }}">{{ $doc['label'] }}</b>
                            <small>{{ $doc['required'] ? 'จำเป็น' : 'ไม่บังคับสำหรับยานพาหนะนี้' }}</small>
                        </div>
                    </div>
                    <span class="nvm-chip {{ $doc['uploaded'] ? 'nvm-chip--ok' : ($doc['required'] ? 'nvm-chip--warn' : '') }}"
                          :class="docs[{{ $tj }}].uploaded ? 'nvm-chip--ok' : (docs[{{ $tj }}].required ? 'nvm-chip--warn' : '')">
                        <span x-text="docs[{{ $tj }}].uploaded ? 'อัปโหลดแล้ว' : 'ยังไม่อัปโหลด'">{{ $doc['uploaded'] ? 'อัปโหลดแล้ว' : 'ยังไม่อัปโหลด' }}</span>
                    </span>
                </div>

                {{-- พรีวิว: รูปที่เพิ่งเลือก → รูปที่อัปโหลดแล้ว → ช่องว่าง --}}
                <div>
                    <template x-if="local[{{ $tj }}]">
                        <img :src="local[{{ $tj }}]" alt="ตัวอย่าง{{ $doc['label'] }}ที่เลือก" class="nvm-rd-thumb">
                    </template>
                    <template x-if="!local[{{ $tj }}] && docs[{{ $tj }}].url">
                        <a :href="docs[{{ $tj }}].url" target="_blank" rel="noopener">
                            <img :src="docs[{{ $tj }}].url" alt="{{ $doc['label'] }}ที่อัปโหลดแล้ว" class="nvm-rd-thumb" loading="lazy">
                        </a>
                    </template>
                    <template x-if="!local[{{ $tj }}] && !docs[{{ $tj }}].url">
                        <div class="nvm-rd-thumb nvm-rd-thumb--ph"><svg class="nvm-ico"><use href="#nvm-i-camera"/></svg></div>
                    </template>
                    <noscript>
                        @if($doc['url'])
                            <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}ที่อัปโหลดแล้ว" class="nvm-rd-thumb">
                        @endif
                    </noscript>
                </div>

                <div class="nvm-field">
                    <label for="doc-{{ $type }}" class="nvm-label">เลือกรูป (jpg, png, webp, heic ไม่เกิน 10MB)</label>
                    <input id="doc-{{ $type }}" type="file" name="document" required class="nvm-input"
                           accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif"
                           x-on:change="pick($event, {{ $tj }})">
                    <span class="nvm-help">{{ $docHints[$type] ?? '' }}</span>
                </div>

                <template x-if="progress[{{ $tj }}] !== undefined">
                    <div class="nvm-rd-progress" role="progressbar" aria-label="กำลังอัปโหลด" :aria-valuenow="progress[{{ $tj }}]" aria-valuemin="0" aria-valuemax="100"><i :style="'--w:' + progress[{{ $tj }}] + '%'"></i></div>
                </template>

                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="busy !== null">
                    <svg class="nvm-ico"><use href="#nvm-i-up"/></svg>
                    <span x-text="busy === {{ $tj }} ? 'กำลังอัปโหลด...' : (docs[{{ $tj }}].uploaded ? 'อัปโหลดใหม่' : 'อัปโหลด')">{{ $doc['uploaded'] ? 'อัปโหลดใหม่' : 'อัปโหลด' }}</span>
                </button>
            </form>
        @endforeach
    </div>

    <div class="nvm-split">
        {{-- ── เคล็ดลับถ่ายรูปเอกสาร ─────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-tip-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Photo tips</small><h2 id="nvm-rd-tip-h">เคล็ดลับให้ผ่านการตรวจเร็ว</h2></div></div>
            <ol class="nvm-steps">
                <li>ถ่ายในที่แสงสว่าง ไม่มีแสงสะท้อน</li>
                <li>เห็นเอกสารครบทั้งใบ ไม่ตัดขอบ</li>
                <li>ตัวอักษรอ่านออกชัด ไม่เบลอ</li>
                <li>ชื่อในเอกสารตรงกับใบสมัคร</li>
            </ol>
        </section>
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-rd-next-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Next</small><h2 id="nvm-rd-next-h">ไปต่อ</h2></div></div>
            <p class="nvm-desc">เอกสารเก็บแบบส่วนตัว เปิดดูได้เฉพาะคุณและทีมงานที่ตรวจสอบเท่านั้น</p>
            @if($isApproved)
                <a href="{{ route('user.rider.dashboard') }}" class="nvm-btn nvm-btn--line nvm-btn--block nvm-gap-t"><svg class="nvm-ico"><use href="#nvm-i-home"/></svg>กลับแดชบอร์ด</a>
            @else
                <a href="{{ route('user.rider.status') }}" class="nvm-btn nvm-btn--line nvm-btn--block nvm-gap-t"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ดูสถานะใบสมัคร</a>
            @endif
        </aside>
    </div>

    {{-- ── ยืนยันก่อนเปลี่ยนเอกสารสำคัญหลังอนุมัติ (รอตรวจใหม่ + ปิดรับงานชั่วคราว) ── --}}
    <div class="nvm-modal" x-show="confirmType" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-rd-cf-h" @click.self="confirmType = null">
        <div class="nvm-card nvm-modal__box">
            <img src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="110" height="110">
            <h2 id="nvm-rd-cf-h">เปลี่ยน<span x-text="confirmType && docs[confirmType] ? docs[confirmType].label : 'เอกสาร'"></span>?</h2>
            <p>เปลี่ยนเอกสารนี้แล้วต้องรอทีมงานตรวจใหม่ และระบบจะปิดรับงานชั่วคราวจนกว่าจะตรวจเสร็จ</p>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="confirmType = null">ยกเลิก</button>
                <button type="button" class="nvm-btn nvm-btn--gold" @click="confirmUpload()">ยืนยันอัปโหลด</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('user.rider.partials.scripts')
    <script>
    window.rdDocuments = function (cfg) {
        return {
            cfg: cfg,
            docs: cfg.docs,
            missing: cfg.missing,
            complete: !!cfg.complete,
            local: {},
            progress: {},
            busy: null,
            // ฟอร์มที่รอยืนยัน (เปลี่ยนเอกสารสำคัญหลังอนุมัติ)
            confirmType: null,
            confirmForm: null,
            init() {
                // คืนหน่วยความจำรูปตัวอย่างเมื่อออกจากหน้า
                window.addEventListener('pagehide', () => Object.values(this.local).forEach((u) => { if (u) { URL.revokeObjectURL(u); } }));
            },
            requiredCount() {
                return Object.values(this.docs).filter((d) => d.required).length;
            },
            uploadedCount() {
                return Object.values(this.docs).filter((d) => d.required && d.uploaded).length;
            },
            percent() {
                const total = this.requiredCount();
                return total > 0 ? Math.round(this.uploadedCount() / total * 100) : 100;
            },
            missingLabels() {
                return this.missing.map((t) => (this.docs[t] ? this.docs[t].label : t)).join(', ');
            },
            pick(event, type) {
                const file = event.target.files && event.target.files[0];
                if (this.local[type]) { URL.revokeObjectURL(this.local[type]); }
                this.local = Object.assign({}, this.local, { [type]: null });
                if (!file) { return; }
                if (file.size > 10 * 1024 * 1024) {
                    window.rdApi.toast('ไฟล์ต้องมีขนาดไม่เกิน 10MB กรุณาเลือกรูปที่เล็กลง', 'error');
                    event.target.value = '';
                    return;
                }
                // HEIC แสดงตัวอย่างในเบราว์เซอร์ส่วนใหญ่ไม่ได้ → อัปโหลดได้ แต่ไม่มีภาพตัวอย่าง
                if (/^image\/(jpeg|png|webp|gif)$/i.test(file.type)) {
                    this.local = Object.assign({}, this.local, { [type]: URL.createObjectURL(file) });
                }
            },
            upload(event, type) {
                if (this.busy) { return; }
                const form = event.target;
                const input = form.querySelector('input[type="file"]');
                const file = input && input.files ? input.files[0] : null;
                if (!file) { window.rdApi.toast('กรุณาเลือกรูปเอกสารก่อน', 'error'); return; }
                if (file.size > 10 * 1024 * 1024) { window.rdApi.toast('ไฟล์ต้องมีขนาดไม่เกิน 10MB', 'error'); return; }
                if (cfg.isApproved && this.docs[type] && this.docs[type].uploaded && cfg.sensitive.indexOf(type) !== -1) {
                    this.confirmType = type;
                    this.confirmForm = form;
                    return;
                }
                this.send(form, type);
            },
            confirmUpload() {
                const type = this.confirmType;
                const form = this.confirmForm;
                this.confirmType = null;
                this.confirmForm = null;
                if (type && form) { this.send(form, type); }
            },
            async send(form, type) {
                const input = form.querySelector('input[type="file"]');
                const file = input && input.files ? input.files[0] : null;
                if (!file || this.busy) { return; }

                this.busy = type;
                this.progress = Object.assign({}, this.progress, { [type]: 0 });
                const data = new FormData();
                data.append('document_type', type);
                data.append('document', await window.rdApi.shrink(file, 2000));

                const res = await window.rdApi.upload(cfg.uploadUrl, data, (p) => {
                    this.progress = Object.assign({}, this.progress, { [type]: p });
                });

                this.busy = null;
                const progress = Object.assign({}, this.progress);
                delete progress[type];
                this.progress = progress;

                if (!res.ok) {
                    window.rdApi.toast(res.message, 'error');
                    return;
                }

                const wasComplete = this.complete;
                const url = res.data.url ? (res.data.url + (res.data.url.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now()) : null;
                this.docs[type] = Object.assign({}, this.docs[type], { uploaded: true, url: url });
                this.missing = Array.isArray(res.data.documents_missing) ? res.data.documents_missing : this.missing;
                this.complete = !!res.data.documents_complete;
                if (this.local[type]) { URL.revokeObjectURL(this.local[type]); }
                this.local = Object.assign({}, this.local, { [type]: null });
                input.value = '';

                window.rdApi.toast(!wasComplete && this.complete ? 'เอกสารครบแล้ว! รอทีมงานตรวจสอบ' : (res.message || 'อัปโหลดเอกสารสำเร็จ'), 'success');
            }
        };
    };
    </script>
@endpush
