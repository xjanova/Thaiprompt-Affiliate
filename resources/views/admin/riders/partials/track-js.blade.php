{{--
 | คอมโพเนนต์ Alpine เล่นเส้นทาง GPS ของไรเดอร์บนแผนที่ OSM (ใช้ร่วมกันระหว่างหน้า locations และ playback)
 | ต้อง @include('admin.riders.partials.leaflet') ก่อน
 | ใช้งาน: x-data="w1Track(points, { latestUrl })" x-init="boot()" และมี x-ref="map"
 |   points = [{ lat, lng, t (ISO), speed, heading, accuracy, battery, job_id }] เรียงเก่า → ใหม่
--}}
@once
@push('scripts')
<script>
    /* เล่นเส้นทาง GPS 24 ชม. + ตำแหน่งล่าสุด (หน้าแอดมินไรเดอร์) */
    function w1Track(points, opts) {
        const o = opts || {};
        return {
            points: (points || []).filter((p) => W1Map.validPoint(Number(p.lat), Number(p.lng))),
            latestUrl: o.latestUrl || null,
            index: 0,
            playing: false,
            speed: 1,
            timer: null,
            map: null,
            cursor: null,
            live: null,
            loading: false,
            liveInfo: null,
            liveError: '',
            boot() {
                this.$nextTick(() => {
                    if (!this.$refs.map || !window.L) return;
                    const first = this.points[0];
                    this.map = W1Map.create(this.$refs.map, { center: first ? [first.lat, first.lng] : W1Map.BANGKOK, zoom: 14 });
                    if (this.points.length) {
                        const path = this.points.map((p) => [p.lat, p.lng]);
                        const line = L.polyline(path, { color: W1Map.color('gold'), weight: 5, opacity: .85 }).addTo(this.map);
                        L.marker(path[0], { icon: W1Map.pin('ok', 'fa-play', { size: 26, label: 'เริ่ม' }) }).addTo(this.map);
                        if (path.length > 1) {
                            L.marker(path[path.length - 1], { icon: W1Map.pin('bad', 'fa-flag-checkered', { size: 26, label: 'ล่าสุด' }) }).addTo(this.map);
                            this.map.fitBounds(line.getBounds(), { padding: [40, 40], maxZoom: 17 });
                        }
                        this.cursor = L.marker(path[0], { icon: this.arrowIcon(0), zIndexOffset: 1000 }).addTo(this.map);
                        this.index = 0;
                    }
                });
            },
            arrowIcon(heading) {
                const html = '<span style="display:grid; place-items:center; width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:var(--w-on); border:3px solid var(--surf); box-shadow:0 6px 14px rgba(0,0,0,.3);">'
                    + '<i class="fas fa-location-arrow" style="transform:rotate(' + ((Number(heading) || 0) - 45) + 'deg);"></i></span>';
                return L.divIcon({ className: 'w1-pin', html: html, iconSize: [30, 30], iconAnchor: [15, 15] });
            },
            current() {
                return this.points[this.index] || null;
            },
            seek(i) {
                if (!this.points.length) return;
                this.index = Math.min(Math.max(0, parseInt(i, 10) || 0), this.points.length - 1);
                const p = this.current();
                if (this.cursor && p) {
                    this.cursor.setLatLng([p.lat, p.lng]);
                    this.cursor.setIcon(this.arrowIcon(this.headingAt(this.index)));
                    if (this.playing) this.map.panTo([p.lat, p.lng], { animate: true });
                }
            },
            headingAt(i) {
                const p = this.points[i];
                if (p && p.heading !== null && p.heading !== undefined) return p.heading;
                const n = this.points[i + 1] || p;
                const prev = this.points[i - 1] || p;
                const a = n === p ? prev : p;
                const b = n === p ? p : n;
                const y = Math.sin((b.lng - a.lng) * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180);
                const x = Math.cos(a.lat * Math.PI / 180) * Math.sin(b.lat * Math.PI / 180) - Math.sin(a.lat * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180) * Math.cos((b.lng - a.lng) * Math.PI / 180);
                return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
            },
            toggle() {
                this.playing ? this.pause() : this.play();
            },
            play() {
                if (this.points.length < 2) return;
                if (this.index >= this.points.length - 1) this.seek(0);
                this.playing = true;
                clearInterval(this.timer);
                this.timer = setInterval(() => {
                    if (this.index >= this.points.length - 1) { this.pause(); return; }
                    this.seek(this.index + 1);
                }, Math.max(60, 1000 / (Number(this.speed) || 1)));
            },
            pause() {
                this.playing = false;
                clearInterval(this.timer);
            },
            setSpeed(value) {
                this.speed = Number(value) || 1;
                if (this.playing) this.play();
            },
            distanceUntil(i) {
                let km = 0;
                for (let k = 1; k <= i && k < this.points.length; k++) {
                    km += this.haversine(this.points[k - 1], this.points[k]);
                }
                return km;
            },
            haversine(a, b) {
                const R = 6371, rad = Math.PI / 180;
                const dLat = (b.lat - a.lat) * rad, dLng = (b.lng - a.lng) * rad;
                const h = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLng / 2) ** 2;
                return R * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
            },
            time(iso) {
                return iso ? new Date(iso).toLocaleTimeString('th-TH') : '-';
            },
            dateTime(iso) {
                return iso ? new Date(iso).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' }) : '-';
            },
            num(value, digits) {
                return value === null || value === undefined ? '-' : Number(value).toFixed(digits);
            },
            async refreshLatest() {
                if (!this.latestUrl || this.loading) return;
                this.loading = true;
                try {
                    const res = await fetch(this.latestUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error('error');
                    this.liveInfo = json.data;
                    this.liveError = json.data ? '' : 'ไรเดอร์ยังไม่เคยส่งตำแหน่ง';
                    if (json.data && W1Map.validPoint(Number(json.data.latitude), Number(json.data.longitude))) {
                        const at = [Number(json.data.latitude), Number(json.data.longitude)];
                        if (!this.live) {
                            this.live = L.marker(at, { icon: W1Map.pin(json.data.is_stale ? 'warn' : 'ok', 'fa-motorcycle', { pulse: !json.data.is_stale, label: 'ตอนนี้' }), zIndexOffset: 1200 }).addTo(this.map);
                        } else {
                            this.live.setLatLng(at);
                            this.live.setIcon(W1Map.pin(json.data.is_stale ? 'warn' : 'ok', 'fa-motorcycle', { pulse: !json.data.is_stale, label: 'ตอนนี้' }));
                        }
                        this.map.setView(at, Math.max(this.map.getZoom(), 15));
                    }
                } catch (e) {
                    this.liveError = 'โหลดตำแหน่งล่าสุดไม่สำเร็จ';
                } finally {
                    this.loading = false;
                }
            },
            destroy() {
                this.pause();
            },
        };
    }
</script>
@endpush
@endonce
