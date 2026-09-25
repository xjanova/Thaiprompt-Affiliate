{{--
 | โหลด Leaflet (แผนที่) ครั้งเดียวต่อหน้า + ตัวช่วยสร้างแผนที่ OpenStreetMap
 | ใส่ในหน้า:  <x-theme-v4.leaflet />
 |
 | ใช้ใน JS:   const map = window.tpMap.create(element, lat, lng, zoom)
 |            window.tpMap.pin(latlng, 'rider'|'home'|'shop')   → ไอคอนหมุดธีม V4
 |
 | ไทล์: OpenStreetMap มาตรฐาน (ต้องแสดง attribution) — ไม่ใช้ CARTO เพราะตอนนี้ต้องมีคีย์ (ไม่มีคีย์ได้ไทล์ลายน้ำ)
 --}}
@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    .tp-map-pin { width:40px; height:40px; border-radius:50% 50% 50% 4px; transform:rotate(-45deg); display:grid; place-items:center; color:var(--on-accent, #fff); box-shadow:0 6px 14px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.45); background:linear-gradient(135deg, var(--accent1), var(--deep1)); border:2px solid var(--on-accent, #fff); }
    .tp-map-pin > i { transform:rotate(45deg); font-size:16px; }
    .tp-map-pin.is-rider { background:linear-gradient(135deg, var(--accent2), var(--deep2)); }
    .tp-map-pin.is-shop { background:linear-gradient(135deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467)); }
    .leaflet-container { font-family:var(--tp-font); }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    (function () {
        const ICONS = { home: 'fa-house', rider: 'fa-motorcycle', shop: 'fa-store' };

        window.tpMap = {
            ready: function () { return typeof window.L !== 'undefined'; },

            create: function (el, lat, lng, zoom) {
                if (!this.ready() || !el) { return null; }
                const map = window.L.map(el, { zoomControl: true, scrollWheelZoom: false, attributionControl: true })
                    .setView([lat, lng], zoom || 15);
                window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
                }).addTo(map);
                return map;
            },

            icon: function (kind) {
                const cls = kind === 'rider' ? 'is-rider' : (kind === 'shop' ? 'is-shop' : '');
                return window.L.divIcon({
                    className: '',
                    html: '<div class="tp-map-pin ' + cls + '"><i class="fas ' + (ICONS[kind] || ICONS.home) + '"></i></div>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40]
                });
            },

            pin: function (map, lat, lng, kind, draggable) {
                if (!map) { return null; }
                return window.L.marker([lat, lng], { icon: this.icon(kind), draggable: !!draggable }).addTo(map);
            }
        };
    })();
</script>
@endpush
@endonce
