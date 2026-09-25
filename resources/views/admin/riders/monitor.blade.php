{{--
 | มอนิเตอร์การกระจายงานสด (admin.riders.monitor) — หน้าใหม่ ธีม V4 + OpenStreetMap/Leaflet
 | ตัวแปรจาก Admin\RiderController@monitor: $dataUrl (admin.riders.dispatch-monitor JSON), $refreshSeconds (15),
 |   $staleAfterSeconds, $manualAfterMinutes (rider.pending_timeout_minutes), $pageTitle
 | JSON: {success, data{generated_at, pending_jobs[], active_jobs[], riders[], stats{pending,manual_needed,active,online,busy,stale}}}
 | กดงานในรายการ/หมุด → ซูมไปที่งาน + ปุ่ม "เปิดหน้างาน" (มอบหมาย/ยกเลิก/สร้างงานใหม่ทำที่หน้า admin.rider-jobs.show)
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'มอนิเตอร์การกระจายงานสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.leaflet')
<div x-data="w1DispatchMonitor()" x-init="boot()" style="display:flex; flex-direction:column; gap:16px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.rider-jobs.index') }}" class="tp-icon-btn" title="กลับหน้างานไรเดอร์"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · มอนิเตอร์สด</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    มอนิเตอร์การกระจายงาน
                    <span class="tp-pill" :style="paused ? pill('mute') : pill('ok')">
                        <span style="width:7px; height:7px; border-radius:50%;" :style="{ background: paused ? 'var(--ink2)' : 'var(--w-ok)', animation: paused ? 'none' : 'tpPulse 1.4s infinite' }"></span>
                        <span x-text="paused ? 'หยุดชั่วคราว' : 'LIVE'"></span>
                    </span>
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">งานที่รอไรเดอร์ งานที่กำลังวิ่ง และไรเดอร์ออนไลน์ — รีเฟรชอัตโนมัติทุก {{ (int) $refreshSeconds }} วินาที</div>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px; align-items:center;">
            <span style="font-size:12px; color:var(--ink2);" x-show="!paused">รีเฟรชใน <b class="tp-num" x-text="countdown"></b> วิ</span>
            <span style="font-size:12px; color:var(--ink2);">ข้อมูลเมื่อ <b class="tp-num" x-text="generatedAt">-</b></span>
            <button type="button" class="tp-btn tp-btn-sm" @click="paused = !paused; countdown = every">
                <i class="fas" :class="paused ? 'fa-play' : 'fa-pause'"></i> <span x-text="paused ? 'เล่นต่อ' : 'หยุด'"></span>
            </button>
            <button type="button" class="tp-btn tp-btn-sm tp-btn-primary" @click="refresh()" :disabled="loading">
                <i class="fas fa-rotate" :class="loading ? 'fa-spin' : ''"></i> รีเฟรชตอนนี้
            </button>
        </div>
    </div>

    {{-- ===== สรุปตัวเลข ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:12px;">
        <template x-for="card in statCards" :key="card.key">
            <button type="button" class="tp-card" @click="card.filter && card.filter()"
                    style="padding:14px 16px; display:flex; align-items:center; gap:11px; border:0; text-align:left; font-family:inherit; color:var(--ink); position:relative;"
                    :style="card.filter ? { cursor: 'pointer' } : { cursor: 'default' }">
                <span class="tp-tile" style="width:38px; height:38px; font-size:15px;"
                      :style="{ background: 'linear-gradient(135deg, var(' + card.v + '), color-mix(in srgb, var(' + card.v + ') 70%, var(--ink)))' }">
                    <i class="fas" :class="card.icon"></i>
                </span>
                <span>
                    <span class="tp-num" style="display:block; font-size:22px; font-weight:800; line-height:1;" x-text="stats[card.key] ?? 0"></span>
                    <span style="display:block; font-size:11.5px; color:var(--ink2); margin-top:2px;" x-text="card.label"></span>
                </span>
                <span x-show="card.key === 'manual_needed' && (stats.manual_needed || 0) > 0"
                      style="position:absolute; top:10px; right:10px; width:9px; height:9px; border-radius:50%; background:var(--w-bad); animation:tpPulse 1.2s infinite;"></span>
            </button>
        </template>
    </div>

    <div x-show="error" x-cloak class="tp-card" style="padding:12px 16px; border-left:4px solid var(--w-bad); font-size:13px;">
        <i class="fas fa-triangle-exclamation" style="color:var(--w-bad);"></i> <span x-text="error"></span>
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:14px 16px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
        <label class="tp-btn tp-btn-sm" style="cursor:pointer;"><input type="checkbox" x-model="show.pending" @change="draw()" style="accent-color:var(--w-warn);"> <i class="fas fa-box" style="color:var(--w-warn);"></i> งานรอไรเดอร์</label>
        <label class="tp-btn tp-btn-sm" style="cursor:pointer;"><input type="checkbox" x-model="show.active" @change="draw()" style="accent-color:var(--w-info);"> <i class="fas fa-truck-fast" style="color:var(--w-info);"></i> งานที่กำลังวิ่ง</label>
        <label class="tp-btn tp-btn-sm" style="cursor:pointer;"><input type="checkbox" x-model="show.riders" @change="draw()" style="accent-color:var(--w-ok);"> <i class="fas fa-motorcycle" style="color:var(--w-ok);"></i> ไรเดอร์</label>
        <label class="tp-btn tp-btn-sm" style="cursor:pointer;"><input type="checkbox" x-model="manualOnly" @change="draw()" style="accent-color:var(--w-bad);"> <i class="fas fa-hand" style="color:var(--w-bad);"></i> เฉพาะงานที่ต้องจัดเอง</label>
        <select x-model="jobType" @change="draw()" class="tp-input" style="width:auto; padding:7px 12px; font-size:12.5px;">
            <option value="">งานทุกประเภท</option>
            <template x-for="type in jobTypes()" :key="type.value">
                <option :value="type.value" x-text="type.label"></option>
            </template>
        </select>
        <input type="search" x-model="q" @input.debounce.300ms="draw()" class="tp-input" style="flex:1; min-width:180px; padding:8px 12px; font-size:12.5px;" placeholder="ค้นหาเลขงาน ที่อยู่ หรือชื่อไรเดอร์">
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr)); gap:16px; align-items:start;">

        {{-- ===== แผนที่ ===== --}}
        <div class="tp-card" style="padding:0; overflow:hidden; grid-column:1 / -1;">
            <div x-ref="map" style="width:100%; height:min(62vh, 560px); min-height:340px;"></div>
            <div style="display:flex; flex-wrap:wrap; gap:12px; padding:10px 14px; font-size:11.5px; color:var(--ink2);">
                <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-warn);"></span> จุดรับของ (รอไรเดอร์)</span>
                <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-bad);"></span> ต้องจัดเอง / รอนาน</span>
                <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-info);"></span> จุดส่งของ (งานที่วิ่ง)</span>
                <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-ok);"></span> ไรเดอร์ว่าง</span>
                <span><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--w-violet);"></span> ไรเดอร์กำลังส่ง</span>
            </div>
        </div>

        {{-- ===== งานรอไรเดอร์ ===== --}}
        <div class="tp-card" style="padding:16px;">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-hourglass-half" style="color:var(--w-warn);"></i> งานรอไรเดอร์ (<span class="tp-num" x-text="pendingList().length"></span>)</div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:520px; overflow-y:auto; padding:2px;">
                <template x-for="job in pendingList()" :key="'p' + job.id">
                    <div class="tp-card" style="padding:11px 13px; cursor:pointer; border-left:4px solid;" @click="focusPending(job)"
                         :style="{ borderLeftColor: 'var(' + (job.manual_needed || job.age_minutes >= manualAfter ? '--w-bad' : '--w-warn') + ')', boxShadow: selectedKey === 'p' + job.id ? 'var(--inset-sm)' : 'var(--raise)' }">
                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                            <span class="tp-num" style="font-weight:700; font-size:13px;" x-text="'#' + job.job_number"></span>
                            <span style="display:flex; gap:5px;">
                                <span class="tp-pill" x-show="isNew(job)" :style="pill('info')">ใหม่</span>
                                <span class="tp-pill" x-show="job.manual_needed" :style="pill('bad')"><i class="fas fa-hand"></i> ต้องจัดเอง</span>
                                <span class="tp-pill" :style="pill(job.age_minutes >= manualAfter ? 'bad' : 'warn')" x-text="minutesLabel(job.age_minutes)"></span>
                            </span>
                        </div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:4px;" x-text="job.job_type_text + ' · ' + km(job.distance_km) + ' · ค่าส่ง ฿' + money(job.total_fee)"></div>
                        <div style="font-size:12px; margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><i class="fas fa-store" style="color:var(--ink2); width:14px;"></i> <span x-text="job.pickup.name || job.pickup.address || '-'"></span></div>
                        <div style="font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--ink2);"><i class="fas fa-flag-checkered" style="width:14px;"></i> <span x-text="job.dropoff.area || job.dropoff.address || '-'"></span></div>
                        <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:6px; font-size:11px; color:var(--ink2);">
                            <span x-text="'รอบกระจาย ' + job.dispatch_round"></span>
                            <span x-show="job.dispatch_radius_km" x-text="'· รัศมี ' + job.dispatch_radius_km + ' กม.'"></span>
                            <span x-text="'· แจ้งแล้ว ' + job.candidates_count + ' คน'"></span>
                            <span x-show="job.release_count > 0" x-text="'· ถูกคืนงาน ' + job.release_count + ' ครั้ง'" style="color:var(--w-bad);"></span>
                            <span x-show="job.cod_amount > 0" x-text="'· COD ฿' + money(job.cod_amount)"></span>
                        </div>
                        <div style="display:flex; gap:6px; margin-top:8px;">
                            <a :href="job.url" class="tp-btn tp-btn-sm tp-btn-primary" style="flex:1;" @click.stop><i class="fas fa-up-right-from-square"></i> เปิดหน้างาน / มอบหมาย</a>
                        </div>
                    </div>
                </template>
                <div x-show="pendingList().length === 0" style="text-align:center; color:var(--ink2); font-size:13px; padding:26px 8px;">
                    <i class="fas fa-mug-hot" style="font-size:22px; opacity:.5; display:block; margin-bottom:6px;"></i>
                    ไม่มีงานรอไรเดอร์ตอนนี้
                </div>
            </div>
        </div>

        {{-- ===== งานที่กำลังวิ่ง ===== --}}
        <div class="tp-card" style="padding:16px;">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-truck-fast" style="color:var(--w-info);"></i> งานที่กำลังวิ่ง (<span class="tp-num" x-text="activeList().length"></span>)</div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:520px; overflow-y:auto; padding:2px;">
                <template x-for="job in activeList()" :key="'a' + job.id">
                    <div class="tp-card" style="padding:11px 13px; cursor:pointer;" @click="focusActive(job)"
                         :style="{ boxShadow: selectedKey === 'a' + job.id ? 'var(--inset-sm)' : 'var(--raise)' }">
                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                            <span class="tp-num" style="font-weight:700; font-size:13px;" x-text="'#' + job.job_number"></span>
                            <span class="tp-pill" :style="pill(job.status === 'picked_up' || job.status === 'delivering' ? 'violet' : 'info')" x-text="job.status_text"></span>
                        </div>
                        <div style="font-size:12px; margin-top:4px;"><i class="fas fa-motorcycle" style="color:var(--ink2); width:14px;"></i> <span x-text="job.rider ? job.rider.full_name : 'ไม่มีไรเดอร์'"></span>
                            <a x-show="job.rider && job.rider.phone" :href="job.rider ? 'tel:' + job.rider.phone : '#'" class="w1-link" @click.stop x-text="job.rider ? job.rider.phone : ''"></a>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:6px;">
                            <span class="tp-pill" :style="pill(job.gps_active ? 'ok' : 'bad')"><i class="fas" :class="job.gps_active ? 'fa-satellite-dish' : 'fa-triangle-exclamation'"></i> <span x-text="job.gps_active ? 'GPS ปกติ' : 'GPS หาย'"></span></span>
                            <span class="tp-pill" x-show="job.gps_warning_count > 0" :style="pill('warn')" x-text="'เตือน GPS ' + job.gps_warning_count + ' ครั้ง'"></span>
                            <span class="tp-pill" x-show="job.minutes_since_accept !== null" :style="pill(job.minutes_since_accept > 60 ? 'warn' : 'mute')" x-text="'รับงาน ' + minutesLabel(job.minutes_since_accept)"></span>
                            <span class="tp-pill" x-show="job.cod_amount > 0" :style="pill('gold')" x-text="'COD ฿' + money(job.cod_amount)"></span>
                        </div>
                        <a :href="job.url" class="tp-btn tp-btn-sm" style="margin-top:8px; width:100%;" @click.stop><i class="fas fa-up-right-from-square"></i> เปิดหน้างาน</a>
                    </div>
                </template>
                <div x-show="activeList().length === 0" style="text-align:center; color:var(--ink2); font-size:13px; padding:26px 8px;">
                    ไม่มีงานที่กำลังวิ่ง
                </div>
            </div>
        </div>

        {{-- ===== ไรเดอร์ออนไลน์ ===== --}}
        <div class="tp-card" style="padding:16px;">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-users" style="color:var(--w-ok);"></i> ไรเดอร์ออนไลน์ (<span class="tp-num" x-text="riderList().length"></span>)</div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:520px; overflow-y:auto; padding:2px;">
                <template x-for="rider in riderList()" :key="'r' + rider.id">
                    <div class="tp-card" style="padding:10px 12px; cursor:pointer;" @click="focusRider(rider)"
                         :style="{ boxShadow: selectedKey === 'r' + rider.id ? 'var(--inset-sm)' : 'var(--raise)' }">
                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                            <span style="font-weight:700; font-size:13px;" x-text="rider.full_name"></span>
                            <span class="tp-pill" :style="pill(riderTone(rider))" x-text="riderLabel(rider)"></span>
                        </div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">
                            <span x-text="ago(rider.last_location_update)"></span>
                            <span x-show="!rider.has_location_consent" style="color:var(--w-bad);"> · ยังไม่ยินยอมแชร์ตำแหน่ง (รับงานไม่ได้)</span>
                        </div>
                        <a :href="rider.url" class="w1-link" style="font-size:12px;" @click.stop>โปรไฟล์ →</a>
                    </div>
                </template>
                <div x-show="riderList().length === 0" style="text-align:center; color:var(--ink2); font-size:13px; padding:26px 8px;">
                    ไม่มีไรเดอร์ออนไลน์
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /* มอนิเตอร์การกระจายงานสด: ดึง JSON ทุก N วินาที (หยุดเมื่อแท็บถูกซ่อน) แล้ววาดหมุด/เส้นบนแผนที่ OSM */
    function w1DispatchMonitor() {
        return {
            dataUrl: @js($dataUrl),
            every: @js((int) $refreshSeconds),
            manualAfter: @js(max(1, (int) $manualAfterMinutes)),
            staleAfter: @js((int) $staleAfterSeconds),
            baseTitle: document.title,
            pending: [],
            active: [],
            riders: [],
            stats: { pending: 0, manual_needed: 0, active: 0, online: 0, busy: 0, stale: 0 },
            seenPending: null,
            newIds: [],
            show: { pending: true, active: true, riders: true },
            manualOnly: false,
            jobType: '',
            q: '',
            paused: false,
            countdown: 0,
            loading: false,
            error: '',
            generatedAt: '-',
            selectedKey: null,
            map: null,
            layers: null,
            ticker: null,
            fitted: false,
            statCards: [],
            boot() {
                this.countdown = this.every;
                this.statCards = [
                    { key: 'pending', label: 'งานรอไรเดอร์', icon: 'fa-hourglass-half', v: '--w-warn', filter: () => { this.manualOnly = false; this.show.pending = true; this.draw(); } },
                    { key: 'manual_needed', label: 'ต้องจัดเอง', icon: 'fa-hand', v: '--w-bad', filter: () => { this.manualOnly = true; this.show.pending = true; this.draw(); } },
                    { key: 'active', label: 'งานที่กำลังวิ่ง', icon: 'fa-truck-fast', v: '--w-info', filter: null },
                    { key: 'online', label: 'ไรเดอร์ว่าง', icon: 'fa-signal', v: '--w-ok', filter: null },
                    { key: 'busy', label: 'ไรเดอร์กำลังส่ง', icon: 'fa-motorcycle', v: '--w-violet', filter: null },
                    { key: 'stale', label: 'สัญญาณเงียบ', icon: 'fa-satellite-dish', v: '--ink2', filter: null },
                ];
                this.$nextTick(() => {
                    this.map = W1Map.create(this.$refs.map, { zoom: 12 });
                    this.layers = L.layerGroup().addTo(this.map);
                    this.refresh();
                });
                this.ticker = setInterval(() => {
                    if (this.paused || document.hidden) return;
                    this.countdown -= 1;
                    if (this.countdown <= 0) this.refresh();
                }, 1000);
                document.addEventListener('visibilitychange', () => { if (!document.hidden && !this.paused) this.refresh(); });
            },
            async refresh() {
                if (this.loading) return;
                this.loading = true;
                this.countdown = this.every;
                try {
                    const res = await fetch(this.dataUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json.message || 'error');
                    const data = json.data || {};
                    this.pending = data.pending_jobs || [];
                    this.active = data.active_jobs || [];
                    this.riders = data.riders || [];
                    this.stats = data.stats || this.stats;
                    const ids = this.pending.map((j) => j.id);
                    this.newIds = this.seenPending === null ? [] : ids.filter((id) => !this.seenPending.includes(id));
                    this.seenPending = ids;
                    this.generatedAt = data.generated_at ? new Date(data.generated_at).toLocaleTimeString('th-TH') : new Date().toLocaleTimeString('th-TH');
                    this.error = '';
                    const manual = this.stats.manual_needed || 0;
                    document.title = (manual > 0 ? '(' + manual + ') ต้องจัดเอง · ' : '') + this.baseTitle;
                    this.draw();
                } catch (e) {
                    this.error = 'โหลดข้อมูลมอนิเตอร์ไม่สำเร็จ จะลองใหม่อัตโนมัติ';
                } finally {
                    this.loading = false;
                }
            },
            matches(text) {
                const q = this.q.trim().toLowerCase();
                return !q || String(text || '').toLowerCase().includes(q);
            },
            pendingList() {
                return this.pending
                    .filter((j) => !this.manualOnly || j.manual_needed || j.age_minutes >= this.manualAfter)
                    .filter((j) => !this.jobType || j.job_type === this.jobType)
                    .filter((j) => this.matches([j.job_number, j.pickup.name, j.pickup.address, j.dropoff.area, j.dropoff.address].join(' ')))
                    .sort((a, b) => (Number(b.manual_needed) - Number(a.manual_needed)) || (b.age_minutes - a.age_minutes));
            },
            activeList() {
                return this.active.filter((j) => this.matches([j.job_number, j.rider ? j.rider.full_name : '', j.status_text].join(' ')));
            },
            riderList() {
                return this.riders.filter((r) => this.matches([r.full_name, r.phone, r.vehicle_plate].join(' ')));
            },
            jobTypes() {
                const seen = {};
                this.pending.forEach((j) => { seen[j.job_type] = j.job_type_text; });
                return Object.keys(seen).map((value) => ({ value, label: seen[value] }));
            },
            isNew(job) {
                return this.newIds.includes(job.id);
            },
            draw() {
                if (!this.layers) return;
                this.layers.clearLayers();
                const points = [];
                const add = (lat, lng) => { if (W1Map.validPoint(lat, lng)) points.push([lat, lng]); };

                if (this.show.pending) {
                    this.pendingList().forEach((job) => {
                        const lat = Number(job.pickup.latitude), lng = Number(job.pickup.longitude);
                        if (!W1Map.validPoint(lat, lng)) return;
                        const urgent = job.manual_needed || job.age_minutes >= this.manualAfter;
                        const m = L.marker([lat, lng], { icon: W1Map.pin(urgent ? 'bad' : 'warn', job.manual_needed ? 'fa-hand' : 'fa-box', { pulse: urgent, label: '#' + job.job_number }), zIndexOffset: urgent ? 900 : 500 });
                        m.bindPopup(this.pendingPopup(job));
                        m.on('click', () => { this.selectedKey = 'p' + job.id; this.drawRoute(job); });
                        m.addTo(this.layers);
                        add(lat, lng);
                    });
                }
                if (this.show.active) {
                    this.activeList().forEach((job) => {
                        const lat = Number(job.dropoff.latitude), lng = Number(job.dropoff.longitude);
                        if (!W1Map.validPoint(lat, lng)) return;
                        const m = L.marker([lat, lng], { icon: W1Map.pin('info', 'fa-flag-checkered', { size: 28 }) });
                        m.bindPopup('<b>#' + W1Map.esc(job.job_number) + '</b><br>' + W1Map.esc(job.status_text) + '<br>ไรเดอร์: ' + W1Map.esc(job.rider ? job.rider.full_name : '-') + '<br><a href="' + W1Map.esc(job.url) + '">เปิดหน้างาน</a>');
                        m.addTo(this.layers);
                        const rider = job.rider ? this.riders.find((r) => r.id === job.rider.id) : null;
                        if (rider && W1Map.validPoint(Number(rider.latitude), Number(rider.longitude))) {
                            L.polyline([[Number(rider.latitude), Number(rider.longitude)], [lat, lng]], { color: W1Map.color('info'), weight: 3, opacity: .7, dashArray: '6 8' }).addTo(this.layers);
                        }
                        add(lat, lng);
                    });
                }
                if (this.show.riders) {
                    this.riderList().forEach((rider) => {
                        const lat = Number(rider.latitude), lng = Number(rider.longitude);
                        if (!W1Map.validPoint(lat, lng)) return;
                        const m = L.marker([lat, lng], { icon: W1Map.pin(this.riderTone(rider), 'fa-motorcycle', { size: 30, pulse: rider.availability === 'online' && !rider.is_stale }), zIndexOffset: 300 });
                        m.bindPopup('<b>' + W1Map.esc(rider.full_name) + '</b><br>' + W1Map.esc(this.riderLabel(rider)) + '<br>' + W1Map.esc(this.ago(rider.last_location_update)) + '<br><a href="' + W1Map.esc(rider.url) + '">โปรไฟล์</a>');
                        m.addTo(this.layers);
                        add(lat, lng);
                    });
                }
                if (!this.fitted && points.length) {
                    this.map.fitBounds(points, { padding: [40, 40], maxZoom: 15 });
                    this.fitted = true;
                }
            },
            drawRoute(job) {
                const a = [Number(job.pickup.latitude), Number(job.pickup.longitude)];
                const b = [Number(job.dropoff.latitude), Number(job.dropoff.longitude)];
                if (!W1Map.validPoint(a[0], a[1]) || !W1Map.validPoint(b[0], b[1])) return;
                const line = L.polyline([a, b], { color: W1Map.color('warn'), weight: 4, opacity: .85, dashArray: '8 8' }).addTo(this.layers);
                L.marker(b, { icon: W1Map.pin('info', 'fa-flag-checkered', { size: 26, label: job.dropoff.area || 'จุดส่ง' }) }).addTo(this.layers);
                this.map.fitBounds(line.getBounds(), { padding: [50, 50], maxZoom: 16 });
            },
            pendingPopup(job) {
                return '<b>#' + W1Map.esc(job.job_number) + '</b> · ' + W1Map.esc(job.job_type_text)
                    + '<br>รอมาแล้ว ' + W1Map.esc(this.minutesLabel(job.age_minutes))
                    + (job.manual_needed ? '<br><b>ไม่มีไรเดอร์รับ ต้องมอบหมายเอง</b>' : '')
                    + '<br>รับ: ' + W1Map.esc(job.pickup.name || job.pickup.address || '-')
                    + '<br>ส่ง: ' + W1Map.esc(job.dropoff.area || '-')
                    + '<br>ค่าส่ง ฿' + W1Map.esc(this.money(job.total_fee))
                    + '<br><a href="' + W1Map.esc(job.url) + '">เปิดหน้างาน / มอบหมายไรเดอร์</a>';
            },
            focusPending(job) {
                this.selectedKey = 'p' + job.id;
                this.draw();
                this.drawRoute(job);
            },
            focusActive(job) {
                this.selectedKey = 'a' + job.id;
                const lat = Number(job.dropoff.latitude), lng = Number(job.dropoff.longitude);
                if (W1Map.validPoint(lat, lng)) this.map.setView([lat, lng], 15);
            },
            focusRider(rider) {
                this.selectedKey = 'r' + rider.id;
                const lat = Number(rider.latitude), lng = Number(rider.longitude);
                if (W1Map.validPoint(lat, lng)) this.map.setView([lat, lng], 16);
            },
            riderTone(rider) {
                if (rider.is_stale) return 'mute';
                return rider.availability === 'busy' ? 'violet' : 'ok';
            },
            riderLabel(rider) {
                if (rider.is_stale) return 'สัญญาณเงียบ';
                return rider.availability === 'busy' ? 'กำลังส่ง' : 'ว่าง';
            },
            pill(tone) {
                /* คืนเป็น object เพื่อให้ Alpine รวมกับ style เดิม (ไม่ทับ display ของ x-show) */
                const v = W1Map.toneVar(tone);
                return { background: 'color-mix(in srgb, var(' + v + ') 17%, transparent)', color: 'color-mix(in srgb, var(' + v + ') 74%, var(--ink))' };
            },
            minutesLabel(min) {
                if (min === null || min === undefined) return '-';
                if (min < 1) return 'ไม่ถึง 1 นาที';
                if (min < 60) return min + ' นาที';
                return Math.floor(min / 60) + ' ชม. ' + (min % 60) + ' นาที';
            },
            km(value) {
                return (Number(value) || 0).toFixed(1) + ' กม.';
            },
            money(value) {
                return (Number(value) || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            },
            ago(iso) {
                if (!iso) return 'ไม่เคยส่งตำแหน่ง';
                const sec = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
                if (sec < 60) return 'ตำแหน่งเมื่อ ' + sec + ' วินาทีที่แล้ว';
                if (sec < 3600) return 'ตำแหน่งเมื่อ ' + Math.floor(sec / 60) + ' นาทีที่แล้ว';
                return 'ตำแหน่งเมื่อ ' + Math.floor(sec / 3600) + ' ชม.ที่แล้ว';
            },
            destroy() {
                clearInterval(this.ticker);
                document.title = this.baseTitle;
            },
        };
    }
</script>
@endpush
