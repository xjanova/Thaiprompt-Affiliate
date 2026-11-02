{{--
    แผนที่ Leaflet (OpenStreetMap) สำหรับหน้าคำสั่งซื้อ/การจองธีมโนวา — โหลดครั้งเดียวต่อหน้า
    ใช้ใน JS:  const map = window.nvmOsMap.create(element, lat, lng, zoom)
               window.nvmOsMap.pin(map, lat, lng, 'home'|'rider'|'shop'|'me')  → หมุดโนวา (ไอคอนจากสไปรต์ #nvm-i-*)
    แทน <x-theme-v4.leaflet /> เดิม (หมุดใช้ Font Awesome + สีธีม V4)
--}}
@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    (function () {
        // ชนิดหมุด → ไอคอนในสไปรต์ของเลย์เอาต์โนวา
        const ICONS = { home: 'home', rider: 'scooter', shop: 'store', me: 'user' };

        window.nvmOsMap = {
            ready: function () { return typeof window.L !== 'undefined'; },

            create: function (el, lat, lng, zoom) {
                if (! this.ready() || ! el) { return null; }
                const map = window.L.map(el, { zoomControl: true, scrollWheelZoom: false, attributionControl: true })
                    .setView([lat, lng], zoom || 15);
                window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
                }).addTo(map);
                // กล่องที่เพิ่งแสดง/เปลี่ยนขนาด → ให้ Leaflet คำนวณขนาดใหม่ (กันไทล์โหลดแค่แถบเดียว)
                if (window.ResizeObserver) {
                    new ResizeObserver(function () {
                        if (el.clientWidth > 0 && el.clientHeight > 0) {
                            try { map.invalidateSize(); } catch (e) { /* แผนที่ถูกลบไปแล้ว */ }
                        }
                    }).observe(el);
                }
                return map;
            },

            icon: function (kind) {
                const name = ICONS[kind] || ICONS.home;
                return window.L.divIcon({
                    className: '',
                    html: '<span class="nvm-os-pin nvm-os-pin--' + kind + '"><svg class="nvm-ico"><use href="#nvm-i-' + name + '"/></svg></span>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40]
                });
            },

            pin: function (map, lat, lng, kind) {
                if (! map) { return null; }
                return window.L.marker([lat, lng], { icon: this.icon(kind) }).addTo(map);
            }
        };

        // แจ้งเตือนลอยของเลย์เอาต์โนวา (รับอีเวนต์ notify)
        window.nvmOsNotify = function (message, type) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message: message, type: type || 'info' } }));
        };
    })();
</script>
@endpush
@endonce
