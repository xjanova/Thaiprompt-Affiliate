{{--
    เอกสารไรเดอร์ (user.rider.documents) — สถานะรายเอกสาร + อัปโหลด/อัปโหลดใหม่ พร้อมพรีวิว
    Controller: User\RiderController@documents
    ตัวแปร: rider, documents [{type,label,uploaded,required,url}], documentTypes, missingDocuments, missingDocumentLabels,
            documentsComplete, documentsPendingReview, uploadUrl, pageTitle
    อัปโหลด: POST user.rider.documents.upload (multipart document_type + document ≤10MB jpg/png/webp/heic)
            AJAX ได้ data {type, uploaded, url, documents, documents_missing, documents_complete}
    ไฟล์อยู่บน private disk — รูปเปิดผ่าน user.rider.documents.file เท่านั้น
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'เอกสารไรเดอร์')

@push('styles')
    @include('user.rider.partials.styles')
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
<div class="rd-scope" x-data="rdDocuments({{ \Illuminate\Support\Js::from($docCfg) }})">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'documents'])

    {{-- ── หัวหน้า: ความคืบหน้าเอกสารที่ต้องมี ─────────────────── --}}
    <section class="tp-card rd-hero">
        <div class="rd-hero-in">
            <div class="rd-ring rd-tone-ok" :style="'--p:' + percent()" style="--p:{{ $percent }};">
                <span x-text="uploadedCount() + '/' + requiredCount()">{{ $requiredUploaded }}/{{ count($requiredDocs) }}</span>
            </div>
            <div style="flex:1 1 220px; min-width:0;">
                <h1 class="rd-h1">เอกสารไรเดอร์</h1>
                <div class="rd-muted" style="font-size:13px; margin-top:4px;">
                    ต้องมี: {{ collect($requiredDocs)->pluck('label')->implode(' · ') }}
                    @if(!in_array($rider->vehicle_type, ['motorcycle', 'car'], true))
                        <br><span class="rd-small">ใช้{{ $rider->vehicle_type_text }} ไม่ต้องใช้ใบขับขี่และทะเบียนรถ</span>
                    @endif
                </div>
                <div class="rd-small rd-muted" style="margin-top:6px;"><i class="fas fa-lock"></i> เอกสารเก็บแบบส่วนตัว เปิดดูได้เฉพาะคุณและทีมงานที่ตรวจสอบเท่านั้น</div>
            </div>
        </div>
    </section>

    {{-- ── สถานะ ──────────────────────────────────────────────── --}}
    @if($rider->status === 'rejected')
        <div class="rd-alert rd-tone-bad">
            <i class="fas fa-circle-xmark"></i>
            <div>
                <b>ใบสมัครไม่ผ่านการอนุมัติ</b>@if($rider->rejection_reason) — {{ $rider->rejection_reason }}@endif
                <div style="margin-top:6px;">แก้ไขเอกสารตามที่แจ้ง แล้ว<a href="{{ route('user.rider.register') }}" class="rd-link"> ส่งใบสมัครใหม่ <i class="fas fa-arrow-right"></i></a></div>
            </div>
        </div>
    @elseif($documentsPendingReview)
        <div class="rd-alert rd-tone-warn">
            <i class="fas fa-hourglass-half"></i>
            <div><b>เอกสารที่เปลี่ยนใหม่รอทีมงานตรวจสอบ</b> — ระหว่างนี้ยังเปิดรับงานไม่ได้ ทีมงานจะแจ้งผลผ่านการแจ้งเตือน</div>
        </div>
    @endif

    <template x-if="complete && {{ $rider->status === 'pending' ? 'true' : 'false' }}">
        <section class="tp-card rd-celebrate">
            <div class="burst" aria-hidden="true"></div>
            <div style="font-size:36px;">✅</div>
            <div class="rd-h2" style="justify-content:center; margin-top:4px;">เอกสารครบแล้ว!</div>
            <div class="rd-muted" style="font-size:13px; margin-top:6px;">ทีมงานกำลังตรวจสอบใบสมัครของคุณ จะแจ้งผลผ่านการแจ้งเตือนโดยเร็ว</div>
            <a :href="cfg.statusUrl" class="rd-btn3d rd-tone-ok sm" style="margin-top:14px;"><i class="fas fa-list-check"></i> ดูสถานะใบสมัคร</a>
        </section>
    </template>
    <template x-if="!complete">
        <div class="rd-alert rd-tone-warn">
            <i class="fas fa-folder-open"></i>
            <div><b>ยังขาดเอกสาร:</b> <span x-text="missingLabels()">{{ $missingDocumentLabels }}</span> — อัปโหลดให้ครบเพื่อให้ทีมงานตรวจอนุมัติได้</div>
        </div>
    </template>
    @if($isApproved)
        <div class="rd-alert rd-tone-info">
            <i class="fas fa-circle-info"></i>
            <div>อัปโหลดใหม่ได้เมื่อเอกสารหมดอายุหรือเปลี่ยนแปลง — การเปลี่ยนบัตรประชาชน ใบขับขี่ หรือทะเบียนรถหลังอนุมัติ ต้องรอทีมงานตรวจใหม่ และระบบจะปิดรับงานชั่วคราว</div>
        </div>
    @endif

    {{-- ── การ์ดเอกสาร ────────────────────────────────────────── --}}
    <div class="rd-grid" style="--rd-min:260px;">
        @foreach($documents as $doc)
            @php
                $type = $doc['type'];
                // ชื่อประเภทเอกสารในรูปสตริง JS ที่ปลอดภัยสำหรับใส่ในแอตทริบิวต์ Alpine
                $tj = \Illuminate\Support\Js::from($type);
            @endphp
            <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data" class="tp-card rd-stack"
                  x-on:submit.prevent="upload($event, {{ $tj }})">
                @csrf
                <input type="hidden" name="document_type" value="{{ $type }}">

                <div class="rd-row" style="justify-content:space-between; flex-wrap:nowrap;">
                    <div class="rd-row" style="gap:10px; flex-wrap:nowrap; min-width:0;">
                        <span class="tp-tile" style="width:42px; height:42px; border-radius:14px; font-size:17px;"><i class="fas {{ $ui::documentIcon($type) }}"></i></span>
                        <div style="min-width:0;">
                            <div style="font-weight:800; font-size:14.5px;">{{ $doc['label'] }}</div>
                            <div class="rd-small rd-muted">{{ $doc['required'] ? 'จำเป็น' : 'ไม่บังคับสำหรับยานพาหนะนี้' }}</div>
                        </div>
                    </div>
                    <span class="rd-pill {{ $doc['uploaded'] ? 'rd-tone-ok' : ($doc['required'] ? 'rd-tone-warn' : 'rd-tone-muted') }}"
                          :class="docs[{{ $tj }}].uploaded ? 'rd-tone-ok' : (docs[{{ $tj }}].required ? 'rd-tone-warn' : 'rd-tone-muted')">
                        <i class="fas" :class="docs[{{ $tj }}].uploaded ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                        <span x-text="docs[{{ $tj }}].uploaded ? 'อัปโหลดแล้ว' : 'ยังไม่อัปโหลด'">{{ $doc['uploaded'] ? 'อัปโหลดแล้ว' : 'ยังไม่อัปโหลด' }}</span>
                    </span>
                </div>

                {{-- พรีวิว: รูปที่เพิ่งเลือก → รูปที่อัปโหลดแล้ว → ช่องว่าง --}}
                <div>
                    <template x-if="local[{{ $tj }}]">
                        <img :src="local[{{ $tj }}]" alt="ตัวอย่าง{{ $doc['label'] }}ที่เลือก" class="rd-thumb">
                    </template>
                    <template x-if="!local[{{ $tj }}] && docs[{{ $tj }}].url">
                        <a :href="docs[{{ $tj }}].url" target="_blank" rel="noopener">
                            <img :src="docs[{{ $tj }}].url" alt="{{ $doc['label'] }}ที่อัปโหลดแล้ว" class="rd-thumb" loading="lazy">
                        </a>
                    </template>
                    <template x-if="!local[{{ $tj }}] && !docs[{{ $tj }}].url">
                        <div class="rd-thumb-ph"><i class="fas {{ $ui::documentIcon($type) }}"></i></div>
                    </template>
                    <noscript>
                        @if($doc['url'])
                            <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}ที่อัปโหลดแล้ว" class="rd-thumb">
                        @endif
                    </noscript>
                </div>

                <div class="rd-hint">{{ $docHints[$type] ?? '' }}</div>

                <div class="rd-field">
                    <label for="doc-{{ $type }}" class="rd-label">เลือกรูป (jpg, png, webp, heic ไม่เกิน 10MB)</label>
                    <input id="doc-{{ $type }}" type="file" name="document" required class="tp-input"
                           accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif"
                           x-on:change="pick($event, {{ $tj }})">
                </div>

                <template x-if="progress[{{ $tj }}] !== undefined">
                    <div class="rd-progress" role="progressbar" aria-label="กำลังอัปโหลด"><i :style="'width:' + progress[{{ $tj }}] + '%'"></i></div>
                </template>

                <button type="submit" class="rd-btn3d rd-tone-gold block" :disabled="busy !== null">
                    <i class="fas" :class="busy === {{ $tj }} ? 'fa-circle-notch rd-spin' : 'fa-cloud-arrow-up'"></i>
                    <span x-text="busy === {{ $tj }} ? 'กำลังอัปโหลด...' : (docs[{{ $tj }}].uploaded ? 'อัปโหลดใหม่' : 'อัปโหลด')">{{ $doc['uploaded'] ? 'อัปโหลดใหม่' : 'อัปโหลด' }}</span>
                </button>
            </form>
        @endforeach
    </div>

    {{-- ── เคล็ดลับถ่ายรูปเอกสาร ─────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <h2 class="rd-h2"><i class="fas fa-lightbulb" style="color:var(--accent1);"></i> เคล็ดลับให้ผ่านการตรวจเร็ว</h2>
        <div class="rd-grid" style="--rd-min:200px; gap:10px;">
            <div class="rd-meta" style="padding:12px;"><i class="fas fa-sun"></i> ถ่ายในที่แสงสว่าง ไม่มีแสงสะท้อน</div>
            <div class="rd-meta" style="padding:12px;"><i class="fas fa-expand"></i> เห็นเอกสารครบทั้งใบ ไม่ตัดขอบ</div>
            <div class="rd-meta" style="padding:12px;"><i class="fas fa-magnifying-glass"></i> ตัวอักษรอ่านออกชัด ไม่เบลอ</div>
            <div class="rd-meta" style="padding:12px;"><i class="fas fa-user-check"></i> ชื่อในเอกสารตรงกับใบสมัคร</div>
        </div>
    </section>

    <div class="rd-row" style="justify-content:center;">
        @if($isApproved)
            <a href="{{ route('user.rider.dashboard') }}" class="tp-btn"><i class="fas fa-gauge-high"></i> กลับแดชบอร์ด</a>
        @else
            <a href="{{ route('user.rider.status') }}" class="tp-btn"><i class="fas fa-list-check"></i> ดูสถานะใบสมัคร</a>
        @endif
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
            async upload(event, type) {
                if (this.busy) { return; }
                const form = event.target;
                const input = form.querySelector('input[type="file"]');
                const file = input && input.files ? input.files[0] : null;
                if (!file) { window.rdApi.toast('กรุณาเลือกรูปเอกสารก่อน', 'warning'); return; }
                if (file.size > 10 * 1024 * 1024) { window.rdApi.toast('ไฟล์ต้องมีขนาดไม่เกิน 10MB', 'error'); return; }
                if (cfg.isApproved && this.docs[type] && this.docs[type].uploaded && cfg.sensitive.indexOf(type) !== -1) {
                    if (!window.confirm('เปลี่ยน' + this.docs[type].label + 'แล้วต้องรอทีมงานตรวจใหม่ และระบบจะปิดรับงานชั่วคราว ยืนยันอัปโหลด?')) { return; }
                }

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
