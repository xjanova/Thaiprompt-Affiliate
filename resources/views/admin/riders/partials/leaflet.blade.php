{{--
 | แผนที่ OpenStreetMap + Leaflet 1.9.4 (ไม่ต้องใช้ API key — CARTO ต้องใช้ key แล้ว จึงเลิกใช้)
 | ต้องแสดงเครดิต "© OpenStreetMap contributors" เสมอ (เงื่อนไขการใช้ tile ของ OSM)
 |
 | ใช้งาน: @include('admin.riders.partials.leaflet') แล้วใน script ของหน้าเรียก
 |   const map = W1Map.create(element, { center: [lat, lng], zoom: 13 });
 |   L.marker([lat, lng], { icon: W1Map.pin('ok', 'fa-motorcycle') }).addTo(map);
 |   W1Map.color('--w-ok') → สีจริงของโทน (ใช้กับเส้น polyline ที่ต้องการค่าสีตรง)
--}}
@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    /* isolation: เก็บ z-index ภายในของ Leaflet (400-1000) ไว้ในกล่องแผนที่ ไม่ให้ทับ topbar/โมดัลของ layout */
    .leaflet-container { font-family: var(--tp-font); background: var(--surf); border-radius: inherit; isolation: isolate; z-index: 0; }
    .dark .leaflet-tile-pane { filter: invert(.92) hue-rotate(180deg) brightness(.92) contrast(.92) saturate(.7); }
    .leaflet-popup-content-wrapper, .leaflet-popup-tip { background: var(--surf); color: var(--ink); box-shadow: var(--card-shadow-sm); }
    .leaflet-popup-content { margin: 12px 14px; font-size: 12.5px; line-height: 1.55; }
    .leaflet-control-attribution { font-size: 10.5px; }
    .w1-pin { background: transparent; border: 0; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    /* ตัวช่วยแผนที่ของหน้าแอดมิน V4 (OSM tiles + หมุดสีตามโทนธีม) */
    window.W1Map = {
        TONES: { ok: '--w-ok', warn: '--w-warn', bad: '--w-bad', info: '--w-info', violet: '--w-violet', gold: '--accent1', mute: '--ink2' },
        BANGKOK: [13.7563, 100.5018],
        create(el, opts) {
            const o = Object.assign({ center: this.BANGKOK, zoom: 12 }, opts || {});
            /* ปิดซูมด้วยลูกกลิ้งไว้ก่อน (ไม่งั้นเลื่อนหน้าผ่านแผนที่แล้วแผนที่ซูมแทน) — คลิกแผนที่ก่อนจึงซูมด้วยลูกกลิ้งได้ */
            const map = L.map(el, { zoomControl: true, attributionControl: true, scrollWheelZoom: false })
                .setView(o.center, o.zoom);
            if (o.scrollWheelZoom !== false) {
                map.on('click focus', () => map.scrollWheelZoom.enable());
                map.on('mouseout blur', () => map.scrollWheelZoom.disable());
            }
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
            }).addTo(map);
            setTimeout(() => map.invalidateSize(), 250);
            /* การ์ดรอบแผนที่เปลี่ยนขนาดได้ (รายการด้านข้างโหลดทีหลัง / เต็มจอ / หมุนจอ) → ให้แผนที่วัดขนาดใหม่เสมอ */
            if (window.ResizeObserver) {
                new ResizeObserver(() => map.invalidateSize()).observe(el);
            }
            return map;
        },
        toneVar(tone) {
            return this.TONES[tone] || tone || '--accent1';
        },
        color(tone) {
            const root = getComputedStyle(document.documentElement);
            const value = root.getPropertyValue(this.toneVar(tone)).trim();
            return value || root.getPropertyValue('--accent1').trim();
        },
        pin(tone, icon, opts) {
            const o = Object.assign({ size: 34, pulse: false, label: '' }, opts || {});
            const v = this.toneVar(tone);
            const ring = o.pulse ? 'box-shadow:0 0 0 6px color-mix(in srgb, var(' + v + ') 30%, transparent), 0 6px 14px rgba(0,0,0,.28);' : 'box-shadow:0 6px 14px rgba(0,0,0,.28);';
            const label = o.label ? '<span style="position:absolute; top:100%; left:50%; transform:translate(-50%,3px); white-space:nowrap; font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:10px; background:var(--surf); color:var(--ink); box-shadow:var(--card-shadow-sm);">' + this.esc(o.label) + '</span>' : '';
            const html = '<span style="position:relative; display:grid; place-items:center; width:' + o.size + 'px; height:' + o.size + 'px; border-radius:50%;'
                + ' background:linear-gradient(135deg, var(' + v + '), color-mix(in srgb, var(' + v + ') 70%, var(--ink)));'
                + ' color:var(--w-on); border:3px solid var(--surf); ' + ring + ' font-size:' + Math.round(o.size * 0.4) + 'px;">'
                + '<i class="fas ' + (icon || 'fa-location-dot') + '"></i>' + label + '</span>';
            return L.divIcon({ className: 'w1-pin', html: html, iconSize: [o.size, o.size], iconAnchor: [o.size / 2, o.size / 2], popupAnchor: [0, -o.size / 2] });
        },
        esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        },
        validPoint(lat, lng) {
            return Number.isFinite(lat) && Number.isFinite(lng) && !(Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001);
        },
    };
</script>
@endpush
@endonce
