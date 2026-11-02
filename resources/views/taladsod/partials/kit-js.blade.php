{{--
 | สคริปต์กลางของหน้าตลาดสด (ไม่มีสไตล์ — ใช้ได้ทั้งหน้า V4 และหน้าผู้ขายธีมโนวา layouts.user-nova)
 | ใส่ในหน้า:  @include('taladsod.partials.kit-js')   (partials.kit เรียกไฟล์นี้ให้เองแล้ว)
 |
 | window.ts: req()/get()/post()/put()/del() (JSON + CSRF, ข้อความไทยเสมอ), geo(), money(), distance(), timeAgo(), hhmm(), notify()
 | คอมโพเนนต์ Alpine กลาง: tsPinMap(cfg) — แผนที่ปักหมุด (ลากหมุด/แตะแผนที่/ใช้ตำแหน่งปัจจุบัน) ต้องคู่กับ <x-theme-v4.leaflet />
 --}}
@once
@push('scripts')
<script>
(function () {
    if (window.ts) { return; }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function notify(message, type) {
        window.dispatchEvent(new CustomEvent('notify', { detail: { message: message, type: type || 'info' } }));
    }

    function hasThai(text) {
        return typeof text === 'string' && /[฀-๿]/.test(text);
    }

    function statusText(status) {
        if (status === 0) { return 'เชื่อมต่อไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่'; }
        if (status === 401) { return 'กรุณาเข้าสู่ระบบก่อนทำรายการ'; }
        if (status === 403) { return 'ไม่มีสิทธิ์ทำรายการนี้'; }
        if (status === 404) { return 'ไม่พบข้อมูลนี้แล้ว'; }
        if (status === 413) { return 'ไฟล์มีขนาดใหญ่เกินไป'; }
        if (status === 419) { return 'หน้านี้เปิดค้างไว้นานเกินไป กรุณารีเฟรชหน้าแล้วลองใหม่'; }
        if (status === 422) { return 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบแล้วลองใหม่'; }
        if (status === 429) { return 'ทำรายการถี่เกินไป กรุณารอสักครู่แล้วลองใหม่'; }
        return 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
    }

    /**
     * เรียก endpoint แบบ JSON (session + CSRF) → {ok, status, code, message, data, body}
     * ข้อความที่แสดงผู้ใช้เป็นภาษาไทยเสมอ (ข้อความจากเซิร์ฟเวอร์ใช้เฉพาะที่เป็นภาษาไทย)
     */
    async function req(method, url, data) {
        method = (method || 'GET').toUpperCase();
        var isForm = (typeof FormData !== 'undefined') && (data instanceof FormData);
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' };
        var opts = { method: method, headers: headers, credentials: 'same-origin' };

        if (method === 'GET') {
            if (data && typeof data === 'object') {
                var qs = new URLSearchParams();
                Object.keys(data).forEach(function (k) {
                    if (data[k] !== null && data[k] !== undefined && data[k] !== '') { qs.append(k, data[k]); }
                });
                var q = qs.toString();
                if (q) { url += (url.indexOf('?') === -1 ? '?' : '&') + q; }
            }
        } else if (isForm) {
            opts.body = data;
        } else {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(data || {});
        }

        var res;
        try {
            res = await fetch(url, opts);
        } catch (e) {
            return { ok: false, status: 0, code: 'NETWORK', message: statusText(0), data: null, body: null };
        }

        var body = null;
        try { body = await res.json(); } catch (e) { body = null; }

        var ok = res.ok && !!body && body.success !== false;
        var message = '';

        if (body && hasThai(body.message)) {
            message = body.message;
        }
        if (!ok && body && body.errors && typeof body.errors === 'object') {
            var keys = Object.keys(body.errors);
            for (var i = 0; i < keys.length; i++) {
                var first = Array.isArray(body.errors[keys[i]]) ? body.errors[keys[i]][0] : body.errors[keys[i]];
                if (hasThai(first)) { message = first; break; }
            }
        }
        if (!ok && (!message || res.status >= 500)) {
            message = statusText(res.status);
        }

        return { ok: ok, status: res.status, code: (body && body.code) || null, message: message, data: body ? (body.data !== undefined ? body.data : null) : null, body: body };
    }

    /**
     * ขอพิกัดจากเบราว์เซอร์ → Promise<{lat, lng, accuracy}> (ไม่ได้ = reject {code, message} ภาษาไทย)
     */
    function geo(options) {
        return new Promise(function (resolve, reject) {
            if (!('geolocation' in navigator)) {
                reject({ code: 'UNSUPPORTED', message: 'เบราว์เซอร์นี้ไม่รองรับการระบุตำแหน่ง' });
                return;
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy });
            }, function (err) {
                var map = {
                    1: { code: 'DENIED', message: 'ยังไม่ได้อนุญาตให้เข้าถึงตำแหน่ง กรุณากดอนุญาตในเบราว์เซอร์แล้วลองใหม่' },
                    2: { code: 'UNAVAILABLE', message: 'หาตำแหน่งไม่ได้ กรุณาเปิด GPS แล้วลองใหม่' },
                    3: { code: 'TIMEOUT', message: 'หาตำแหน่งนานเกินไป กรุณาลองใหม่อีกครั้ง' }
                };
                reject(map[err && err.code] || map[2]);
            }, Object.assign({ enableHighAccuracy: true, timeout: 15000, maximumAge: 20000 }, options || {}));
        });
    }

    /** เคยอนุญาตตำแหน่งแล้วหรือยัง (true/false/null = ไม่รู้) */
    async function geoGranted() {
        try {
            if (!navigator.permissions || !navigator.permissions.query) { return null; }
            var st = await navigator.permissions.query({ name: 'geolocation' });
            return st.state === 'granted';
        } catch (e) {
            return null;
        }
    }

    function money(n) {
        var v = Math.round((Number(n) || 0) * 100) / 100;
        var dec = Math.abs(v - Math.round(v)) >= 0.005 ? 2 : 0;
        return v.toLocaleString('th-TH', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }

    function distance(km) {
        if (km === null || km === undefined || km === '') { return '—'; }
        var v = Number(km) || 0;
        return v < 1 ? Math.max(0, Math.round(v * 1000)).toLocaleString('th-TH') + ' ม.' : v.toFixed(1) + ' กม.';
    }

    function timeAgo(iso) {
        if (!iso) { return '—'; }
        var t = new Date(iso).getTime();
        if (isNaN(t)) { return '—'; }
        var s = Math.max(0, Math.round((Date.now() - t) / 1000));
        if (s < 45) { return 'เมื่อสักครู่'; }
        if (s < 3600) { return Math.round(s / 60) + ' นาทีที่แล้ว'; }
        if (s < 86400) { return Math.round(s / 3600) + ' ชั่วโมงที่แล้ว'; }
        return Math.round(s / 86400) + ' วันที่แล้ว';
    }

    function hhmm(iso) {
        if (!iso) { return ''; }
        var d = new Date(iso);
        if (isNaN(d.getTime())) { return ''; }
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    window.ts = {
        csrf: csrf, notify: notify, req: req, geo: geo, geoGranted: geoGranted,
        get: function (u, d) { return req('GET', u, d); },
        post: function (u, d) { return req('POST', u, d); },
        put: function (u, d) { return req('PUT', u, d); },
        del: function (u, d) { return req('DELETE', u, d); },
        money: money, distance: distance, timeAgo: timeAgo, hhmm: hhmm
    };

    /**
     * แผนที่ปักหมุด (ลงทะเบียนร้าน / ตั้งค่าร้าน / ที่อยู่จัดส่ง)
     * cfg: { id, lat, lng, defaultLat, defaultLng, kind: 'shop'|'home' }
     * ส่งอีเวนต์ ts-pin {id, lat, lng} ทุกครั้งที่หมุดเปลี่ยน · ฟังอีเวนต์ ts-map-refresh เพื่อคำนวณขนาดแผนที่ใหม่ (เช่นเพิ่งแสดงผล)
     */
    window.tsPinMap = function (cfg) {
        cfg = cfg || {};
        var toNum = function (v) { var n = parseFloat(v); return isFinite(n) ? n : null; };
        // วัตถุ Leaflet เก็บนอก state ของ Alpine (ห้ามให้ถูกห่อเป็น reactive proxy)
        var map = null;
        var marker = null;

        return {
            lat: toNum(cfg.lat),
            lng: toNum(cfg.lng),
            locating: false,
            error: '',

            init: function () {
                var self = this;
                this.$nextTick(function () { self.boot(); });
                window.addEventListener('ts-map-refresh', function () { self.refresh(); });
                // ตั้งหมุดจากภายนอก (เช่นเลือกที่อยู่ที่บันทึกไว้): ts-pin-set {id?, lat, lng}
                window.addEventListener('ts-pin-set', function (e) {
                    var d = e.detail || {};
                    if (cfg.id && d.id && d.id !== cfg.id) { return; }
                    if (d.lat === null || d.lat === undefined || d.lng === null || d.lng === undefined) { return; }
                    self.setPoint(d.lat, d.lng, 17);
                });
            },

            boot: function () {
                if (!window.tpMap || !window.tpMap.ready()) {
                    this.error = 'โหลดแผนที่ไม่สำเร็จ — กดปุ่ม "ใช้ตำแหน่งปัจจุบัน" แทนได้';
                    return;
                }
                var has = this.lat !== null && this.lng !== null;
                var c = has ? [this.lat, this.lng] : [toNum(cfg.defaultLat) || 13.7563, toNum(cfg.defaultLng) || 100.5018];
                map = window.tpMap.create(this.$refs.map, c[0], c[1], has ? 16 : 11);
                if (!map) { return; }
                if (has) { this.placeMarker(this.lat, this.lng); }
                var self = this;
                map.on('click', function (e) { self.setPoint(e.latlng.lat, e.latlng.lng); });
                setTimeout(function () { self.refresh(); }, 300);
            },

            refresh: function () {
                if (map) { map.invalidateSize(); }
            },

            placeMarker: function (lat, lng) {
                if (!map) { return; }
                var self = this;
                if (!marker) {
                    marker = window.tpMap.pin(map, lat, lng, cfg.kind || 'home', true);
                    marker.on('dragend', function () {
                        var p = marker.getLatLng();
                        self.setPoint(p.lat, p.lng);
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                }
            },

            setPoint: function (lat, lng, zoom) {
                this.lat = Math.round(Number(lat) * 1e7) / 1e7;
                this.lng = Math.round(Number(lng) * 1e7) / 1e7;
                this.error = '';
                this.placeMarker(this.lat, this.lng);
                if (map && zoom) { map.setView([this.lat, this.lng], zoom); }
                this.$dispatch('ts-pin', { id: cfg.id || null, lat: this.lat, lng: this.lng });
            },

            locate: function () {
                var self = this;
                this.locating = true;
                this.error = '';
                window.ts.geo().then(function (p) {
                    self.setPoint(p.lat, p.lng, 17);
                    if (p.accuracy && p.accuracy > 150) {
                        self.error = 'ตำแหน่งคลาดเคลื่อนประมาณ ' + Math.round(p.accuracy) + ' ม. — ลากหมุดให้ตรงจุดได้';
                    }
                }).catch(function (e) {
                    self.error = e.message;
                }).finally(function () {
                    self.locating = false;
                });
            }
        };
    };
})();
</script>
@endpush
@endonce
