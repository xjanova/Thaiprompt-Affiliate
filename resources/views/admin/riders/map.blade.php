{{--
 | แผนที่ไรเดอร์สด (admin.riders.map) — ธีม V4 + OpenStreetMap/Leaflet (ไม่ใช้ Google Maps / CARTO แล้ว)
 | ตัวแปรจาก Admin\RiderController@map: $onlineRidersData, $busyRidersData (ข้อมูลเริ่มต้น), $staleAfterSeconds,
 |   $gpsDataUrl (admin.riders.gps-data JSON), $dispatchMonitorUrl, $pageTitle
 | JSON: {success, data{riders[{id,name,phone,vehicle_type,vehicle_plate,availability,latitude,longitude,last_location_update,is_stale,url,current_job|null}], stats{total,online,busy,offline,stale}, generated_at}}
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'แผนที่ไรเดอร์')

@section('content')
@include('admin.riders.partials.v4-kit')
@include('admin.riders.partials.leaflet')
<div x-data="w1RiderMap()" x-init="boot()" style="display:flex; flex-direction:column; gap:16px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.riders.index') }}" class="tp-icon-btn" title="กลับหน้ารายชื่อไรเดอร์"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · แผนที่</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0; display:flex; align-items:center; gap:10px;">
                    แผนที่ไรเดอร์
                    <span class="tp-pill" style="background:color-mix(in srgb, var(--w-ok) 18%, transparent); color:color-mix(in srgb, var(--w-ok) 74%, var(--ink));">
                        <span style="width:7px; height:7px; border-radius:50%; background:var(--w-ok); animation:tpPulse 1.4s infinite;"></span> LIVE
                    </span>
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตำแหน่งล่าสุดของไรเดอร์ที่เปิดรับงานและกำลังส่งงาน</div>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px; align-items:center;">
            <span style="font-size:12px; color:var(--ink2);">อัปเดต <b class="tp-num" x-text="lastRefresh">-</b></span>
            <button type="button" class="tp-btn tp-btn-sm" @click="refresh()" :disabled="loading">
                <i class="fas fa-rotate" :class="loading ? 'fa-spin' : ''"></i> รีเฟรช
            </button>
            <a href="{{ route('admin.riders.monitor') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-satellite-dish"></i> มอนิเตอร์งาน</a>
        </div>
    </div>

    {{-- ===== สรุปตัวเลข ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:12px;">
        <template x-for="card in statCards" :key="card.key">
            <div class="tp-card" style="padding:14px 16px; display:flex; align-items:center; gap:11px;">
                <span class="tp-tile" style="width:38px; height:38px; font-size:15px;"
                      :style="{ background: 'linear-gradient(135deg, var(' + card.v + '), color-mix(in srgb, var(' + card.v + ') 70%, var(--ink)))' }">
                    <i class="fas" :class="card.icon"></i>
                </span>
                <div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; line-height:1;" x-text="stats[card.key] ?? 0"></div>
                    <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;" x-text="card.label"></div>
                </div>
            </div>
        </template>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr)); gap:16px; align-items:start;">

        {{-- ===== แผนที่ ===== --}}
        <div class="tp-card" style="padding:0; overflow:hidden; grid-column:1 / -1;"
             :style="fullscreen ? { position:'fixed', inset:'0', zIndex:'70', borderRadius:'0' } : {}">
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content:space-between; padding:12px 14px;">
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    <label class="tp-btn tp-btn-sm" style="cursor:pointer;"><input type="checkbox" x-model="filters.online" @change="refresh()" style="accent-color:var(--w-ok);"> <span style="width:8px; height:8px; border-radius:50%; background:var(--w-ok);"></span> พร้อมรับงาน</label>
                    <label class="tp-btn tp-btn-sm" style="cursor:pointer;"><input type="checkbox" x-model="filters.busy" @change="refresh()" style="accent-color:var(--w-violet);"> <span style="width:8px; height:8px; border-radius:50%; background:var(--w-violet);"></span> กำลังส่งงาน</label>
                    <label class="tp-btn tp-btn-sm" style="cursor:pointer;"><input type="checkbox" x-model="filters.offline" @change="refresh()" style="accent-color:var(--ink2);"> <span style="width:8px; height:8px; border-radius:50%; background:var(--ink2);"></span> ออฟไลน์</label>
                    <select x-model="filters.vehicle" @change="refresh()" class="tp-input" style="width:auto; padding:7px 12px; font-size:12.5px;">
                        <option value="">ยานพาหนะทั้งหมด</option>
                        @foreach (\App\Services\RiderAccountService::VEHICLE_TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select x-model.number="filters.every" @change="schedule()" class="tp-input" style="width:auto; padding:7px 12px; font-size:12.5px;">
                        <option value="0">ไม่รีเฟรชอัตโนมัติ</option>
                        <option value="10">รีเฟรชทุก 10 วินาที</option>
                        <option value="15">รีเฟรชทุก 15 วินาที</option>
                        <option value="30">รีเฟรชทุก 30 วินาที</option>
                        <option value="60">รีเฟรชทุก 1 นาที</option>
                    </select>
                </div>
                <div style="display:flex; gap:6px;">
                    <button type="button" class="tp-icon-btn" style="width:36px; height:36px;" @click="fitAll()" title="ซูมให้เห็นทุกคน"><i class="fas fa-expand"></i></button>
                    <button type="button" class="tp-icon-btn" style="width:36px; height:36px;" @click="toggleFullscreen()" :title="fullscreen ? 'ออกจากเต็มจอ' : 'เต็มจอ'"><i class="fas" :class="fullscreen ? 'fa-compress' : 'fa-maximize'"></i></button>
                </div>
            </div>
            <div x-ref="map" :style="{ height: fullscreen ? 'calc(100vh - 64px)' : 'min(68vh, 620px)' }" style="width:100%; min-height:360px;"></div>
            <div x-show="error" x-cloak style="padding:10px 14px; font-size:12.5px; color:var(--w-bad);"><i class="fas fa-triangle-exclamation"></i> <span x-text="error"></span></div>
        </div>

        {{-- ===== รายชื่อ ===== --}}
        <div class="tp-card" style="padding:16px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px;">
                <div class="tp-section-h"><i class="fas fa-list"></i> ไรเดอร์บนแผนที่ (<span class="tp-num" x-text="riders.length"></span>)</div>
                <input type="search" x-model="q" class="tp-input" style="max-width:190px; padding:8px 12px; font-size:12.5px;" placeholder="ค้นหาชื่อ/ทะเบียน">
            </div>
            <div style="display:flex; flex-direction:column; gap:8px; max-height:420px; overflow-y:auto; padding:2px;">
                <template x-for="rider in filtered()" :key="rider.id">
                    <button type="button" @click="focus(rider)" class="tp-card"
                            style="padding:10px 12px; text-align:left; cursor:pointer; border:0; font-family:inherit; color:var(--ink); box-shadow:var(--raise);"
                            :style="selected && selected.id === rider.id ? { boxShadow: 'var(--inset-sm)' } : {}">
                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                            <span style="font-weight:700; font-size:13px;" x-text="rider.full_name || rider.name"></span>
                            <span class="tp-pill" :style="pillStyle(rider)" x-text="availabilityLabel(rider)"></span>
                        </div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">
                            <span x-text="vehicleLabel(rider.vehicle_type)"></span>
                            <span x-show="rider.vehicle_plate" x-text="' · ' + rider.vehicle_plate"></span>
                            · <span x-text="ago(rider.last_location_update)"></span>
                        </div>
                        <div x-show="rider.current_job" style="font-size:11.5px; margin-top:3px; color:color-mix(in srgb, var(--w-violet) 74%, var(--ink));">
                            <i class="fas fa-truck-fast"></i> <span x-text="rider.current_job ? ('#' + rider.current_job.job_number + ' · ' + rider.current_job.status_text) : ''"></span>
                        </div>
                    </button>
                </template>
                <div x-show="filtered().length === 0" style="text-align:center; color:var(--ink2); font-size:13px; padding:26px 8px;">
                    <i class="fas fa-motorcycle" style="font-size:22px; opacity:.5; display:block; margin-bottom:6px;"></i>
                    ยังไม่มีไรเดอร์ที่ส่งตำแหน่งตามตัวกรองนี้
                </div>
            </div>
        </div>

        {{-- ===== รายละเอียดที่เลือก ===== --}}
        <div class="tp-card" style="padding:16px;">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-circle-info"></i> ไรเดอร์ที่เลือก</div>
            <template x-if="!selected">
                <p style="font-size:13px; color:var(--ink2); margin:0;">กดหมุดบนแผนที่หรือรายชื่อด้านซ้ายเพื่อดูรายละเอียด</p>
            </template>
            <template x-if="selected">
                <div style="display:flex; flex-direction:column; gap:10px; font-size:13px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="tp-tile" style="width:42px; height:42px; border-radius:50%; font-weight:800;" x-text="(selected.full_name || 'R').substring(0, 1)"></span>
                        <div>
                            <div style="font-weight:700;" x-text="selected.full_name"></div>
                            <a class="w1-link" :href="'tel:' + selected.phone" x-text="selected.phone"></a>
                        </div>
                    </div>
                    <div class="tp-well" style="padding:10px 12px; display:grid; grid-template-columns:auto 1fr; gap:5px 10px;">
                        <span style="color:var(--ink2);">สถานะ</span><span x-text="availabilityLabel(selected)"></span>
                        <span style="color:var(--ink2);">ยานพาหนะ</span><span x-text="vehicleLabel(selected.vehicle_type) + (selected.vehicle_plate ? ' · ' + selected.vehicle_plate : '')"></span>
                        <span style="color:var(--ink2);">ตำแหน่งล่าสุด</span><span x-text="ago(selected.last_location_update)"></span>
                    </div>
                    <template x-if="selected.current_job">
                        <a :href="selected.current_job.url" class="tp-card tp-card-hover" style="padding:10px 12px; text-decoration:none; color:var(--ink); border-left:3px solid var(--w-violet);">
                            <div style="font-weight:700;" x-text="'งาน #' + selected.current_job.job_number"></div>
                            <div style="font-size:12px; color:var(--ink2);" x-text="selected.current_job.status_text"></div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:3px;" x-text="'ส่งที่: ' + (selected.current_job.delivery_address || '-')"></div>
                        </a>
                    </template>
                    <div style="display:flex; gap:8px;">
                        <a :href="selected.url" class="tp-btn tp-btn-sm tp-btn-primary" style="flex:1;"><i class="fas fa-eye"></i> โปรไฟล์</a>
                        <a :href="selected.url + '/locations'" class="tp-btn tp-btn-sm" style="flex:1;"><i class="fas fa-route"></i> เส้นทาง</a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /* แผนที่ไรเดอร์สด: ดึง admin.riders.gps-data ตามรอบ แล้ววาดหมุดตามสถานะ */
    function w1RiderMap() {
        return {
            dataUrl: @js($gpsDataUrl),
            staleAfter: @js((int) $staleAfterSeconds),
            vehicles: @js(\App\Services\RiderAccountService::VEHICLE_TYPES),
            riders: @js(array_values(array_merge($onlineRidersData, $busyRidersData))),
            stats: { total: 0, online: 0, busy: 0, offline: 0, stale: 0 },
            statCards: [
                { key: 'online', label: 'พร้อมรับงาน', icon: 'fa-signal', v: '--w-ok' },
                { key: 'busy', label: 'กำลังส่งงาน', icon: 'fa-motorcycle', v: '--w-violet' },
                { key: 'offline', label: 'ออฟไลน์ (ที่แสดง)', icon: 'fa-power-off', v: '--ink2' },
                { key: 'stale', label: 'สัญญาณเงียบ', icon: 'fa-signal', v: '--w-warn' },
                { key: 'total', label: 'บนแผนที่', icon: 'fa-map-pin', v: '--accent1' },
            ],
            filters: { online: true, busy: true, offline: false, vehicle: '', every: 15 },
            q: '',
            selected: null,
            loading: false,
            error: '',
            lastRefresh: '-',
            fullscreen: false,
            map: null,
            layer: null,
            timer: null,
            fitted: false,
            boot() {
                this.$nextTick(() => {
                    this.map = W1Map.create(this.$refs.map, { zoom: 11 });
                    this.layer = L.layerGroup().addTo(this.map);
                    this.draw();
                    this.refresh();
                    this.schedule();
                });
                document.addEventListener('visibilitychange', () => { if (!document.hidden) this.refresh(); });
            },
            schedule() {
                clearInterval(this.timer);
                if (this.filters.every > 0) this.timer = setInterval(() => { if (!document.hidden) this.refresh(); }, this.filters.every * 1000);
            },
            async refresh() {
                if (this.loading) return;
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        show_online: this.filters.online ? '1' : '0',
                        show_busy: this.filters.busy ? '1' : '0',
                        show_offline: this.filters.offline ? '1' : '0',
                        vehicle_type: this.filters.vehicle,
                        limit: 'all',
                    });
                    const res = await fetch(this.dataUrl + '?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json.message || 'โหลดข้อมูลไม่สำเร็จ');
                    this.riders = json.data.riders || [];
                    this.stats = json.data.stats || this.stats;
                    if (this.selected) this.selected = this.riders.find((r) => r.id === this.selected.id) || null;
                    this.error = '';
                    this.lastRefresh = new Date().toLocaleTimeString('th-TH');
                    this.draw();
                } catch (e) {
                    this.error = 'โหลดตำแหน่งไรเดอร์ไม่สำเร็จ จะลองใหม่รอบถัดไป';
                } finally {
                    this.loading = false;
                }
            },
            draw() {
                if (!this.layer) return;
                this.layer.clearLayers();
                const points = [];
                this.riders.forEach((rider) => {
                    const lat = Number(rider.latitude), lng = Number(rider.longitude);
                    if (!W1Map.validPoint(lat, lng)) return;
                    const marker = L.marker([lat, lng], {
                        icon: W1Map.pin(this.tone(rider), this.vehicleIcon(rider.vehicle_type), { pulse: rider.availability === 'online' && !rider.is_stale }),
                        title: rider.full_name || rider.name,
                    });
                    marker.bindPopup('<b>' + W1Map.esc(rider.full_name || rider.name) + '</b><br>' + W1Map.esc(this.availabilityLabel(rider)) + '<br>' + W1Map.esc(this.ago(rider.last_location_update)));
                    marker.on('click', () => { this.selected = rider; });
                    marker.addTo(this.layer);
                    points.push([lat, lng]);
                });
                if (!this.fitted && points.length) {
                    this.map.fitBounds(points, { padding: [40, 40], maxZoom: 15 });
                    this.fitted = true;
                }
            },
            fitAll() {
                const points = this.riders.map((r) => [Number(r.latitude), Number(r.longitude)]).filter((p) => W1Map.validPoint(p[0], p[1]));
                if (points.length) this.map.fitBounds(points, { padding: [40, 40], maxZoom: 15 });
            },
            focus(rider) {
                this.selected = rider;
                const lat = Number(rider.latitude), lng = Number(rider.longitude);
                if (W1Map.validPoint(lat, lng)) this.map.setView([lat, lng], 16);
            },
            toggleFullscreen() {
                this.fullscreen = !this.fullscreen;
                setTimeout(() => this.map && this.map.invalidateSize(), 200);
            },
            filtered() {
                const q = this.q.trim().toLowerCase();
                if (!q) return this.riders;
                return this.riders.filter((r) => ((r.full_name || '') + ' ' + (r.vehicle_plate || '') + ' ' + (r.phone || '')).toLowerCase().includes(q));
            },
            tone(rider) {
                if (rider.is_stale && rider.availability !== 'offline') return 'warn';
                return { online: 'ok', busy: 'violet' }[rider.availability] || 'mute';
            },
            pillStyle(rider) {
                const v = W1Map.toneVar(this.tone(rider));
                return { background: 'color-mix(in srgb, var(' + v + ') 17%, transparent)', color: 'color-mix(in srgb, var(' + v + ') 74%, var(--ink))' };
            },
            availabilityLabel(rider) {
                const label = { online: 'พร้อมรับงาน', busy: 'กำลังส่งงาน', offline: 'ออฟไลน์' }[rider.availability] || 'ไม่ทราบ';
                return rider.is_stale && rider.availability !== 'offline' ? label + ' (สัญญาณเงียบ)' : label;
            },
            vehicleLabel(type) {
                return this.vehicles[type] || 'ไม่ระบุ';
            },
            vehicleIcon(type) {
                return { car: 'fa-car', bicycle: 'fa-bicycle', walk: 'fa-person-walking' }[type] || 'fa-motorcycle';
            },
            ago(iso) {
                if (!iso) return 'ไม่เคยส่งตำแหน่ง';
                const sec = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
                if (sec < 60) return sec + ' วินาทีที่แล้ว';
                if (sec < 3600) return Math.floor(sec / 60) + ' นาทีที่แล้ว';
                if (sec < 86400) return Math.floor(sec / 3600) + ' ชั่วโมงที่แล้ว';
                return Math.floor(sec / 86400) + ' วันที่แล้ว';
            },
            destroy() {
                clearInterval(this.timer);
            },
        };
    }
</script>
@endpush
