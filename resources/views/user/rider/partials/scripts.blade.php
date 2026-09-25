{{--
    ตัวช่วย JavaScript ร่วมของหน้าเว็บไรเดอร์ (window.rdApi)
    - post(): ยิง AJAX ไป route session user.rider.* พร้อม CSRF → {ok, status, code, message, data}
      ข้อความผิดพลาดเป็นภาษาไทยเสมอ (ไม่โชว์ข้อความดิบจากเซิร์ฟเวอร์ถ้าไม่ใช่รูปแบบของระบบ)
    - upload(): อัปโหลดไฟล์พร้อมแถบความคืบหน้า (XMLHttpRequest) คืนผลลัพธ์รูปแบบเดียวกับ post()
    - geo(): ขอพิกัดจากเบราว์เซอร์ (ไม่ได้ = null ไม่ throw)
    - rdLocator(): ปุ่ม "อัปเดตตำแหน่ง" สำหรับหน้าที่ไม่มีแดชบอร์ด
    - shrink(): ย่อรูปก่อนอัปโหลด (jpg/png/webp ใหญ่กว่า 1.5MB) ประหยัดเน็ตมือถือ
    - rdJobBoard(): รายการงานรอรับ (รับงาน / ไม่สนใจ) ใช้ทั้งแดชบอร์ดและหน้างาน
--}}
<script>
(function () {
    if (window.rdApi) { return; }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function statusText(status) {
        if (status === 0) { return 'เชื่อมต่อไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่'; }
        if (status === 419) { return 'หน้านี้เปิดค้างนานเกินไป กรุณารีเฟรชหน้าแล้วลองใหม่'; }
        if (status === 429) { return 'ทำรายการถี่เกินไป กรุณารอสักครู่แล้วลองใหม่'; }
        if (status === 413) { return 'ไฟล์มีขนาดใหญ่เกินไป กรุณาเลือกรูปที่เล็กลง'; }
        if (status === 401) { return 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง'; }
        if (status === 403) { return 'ไม่มีสิทธิ์ทำรายการนี้'; }
        if (status === 404) { return 'ไม่พบข้อมูลงานนี้แล้ว'; }
        return 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
    }

    async function post(url, data) {
        var isForm = (typeof FormData !== 'undefined') && (data instanceof FormData);
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' };
        if (!isForm) { headers['Content-Type'] = 'application/json'; }

        var res;
        try {
            res = await fetch(url, { method: 'POST', headers: headers, body: isForm ? data : JSON.stringify(data || {}), credentials: 'same-origin' });
        } catch (e) {
            return { ok: false, status: 0, code: 'NETWORK', message: statusText(0), data: null };
        }

        var body = null;
        try { body = await res.json(); } catch (e) { body = null; }

        return result(res.status, res.ok, body);
    }

    /**
     * แปลงคำตอบเซิร์ฟเวอร์เป็นผลลัพธ์กลาง (ใช้ทั้ง fetch และ XMLHttpRequest)
     * ข้อความจากระบบไรเดอร์ (มี code) เป็นภาษาไทยที่ตั้งใจให้ผู้ใช้เห็น — นอกนั้นใช้ข้อความกลาง
     */
    function result(status, ok, body) {
        if (ok && body && body.success) {
            return { ok: true, status: status, code: null, message: body.message || '', data: body.data || {} };
        }

        var message = statusText(status);
        if (body && body.code && typeof body.message === 'string' && body.message && status < 500) {
            message = body.message;
        } else if (status === 422 && body && body.errors) {
            var first = Object.values(body.errors)[0];
            if (Array.isArray(first) && first.length) { message = String(first[0]); }
        }

        return { ok: false, status: status, code: body && body.code ? body.code : null, message: message, data: body && body.data ? body.data : null };
    }

    /**
     * อัปโหลดแบบมีแถบความคืบหน้า (XMLHttpRequest) → Promise ของผลลัพธ์กลาง
     */
    function upload(url, formData, onProgress) {
        return new Promise(function (resolve) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.withCredentials = true;
            if (xhr.upload && typeof onProgress === 'function') {
                xhr.upload.onprogress = function (e) { if (e.lengthComputable) { onProgress(Math.round(e.loaded / e.total * 100)); } };
            }
            xhr.onload = function () {
                var body = null;
                try { body = JSON.parse(xhr.responseText); } catch (e) { body = null; }
                resolve(result(xhr.status, xhr.status >= 200 && xhr.status < 300, body));
            };
            xhr.onerror = function () { resolve(result(0, false, null)); };
            xhr.ontimeout = function () { resolve(result(0, false, null)); };
            xhr.timeout = 120000;
            xhr.send(formData);
        });
    }

    function geo(timeoutMs) {
        return new Promise(function (resolve) {
            if (!('geolocation' in navigator)) { resolve(null); return; }
            var done = false;
            var timer = setTimeout(function () { if (!done) { done = true; resolve(null); } }, (timeoutMs || 12000) + 500);
            navigator.geolocation.getCurrentPosition(function (pos) {
                if (done) { return; }
                done = true; clearTimeout(timer);
                resolve({ latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: pos.coords.accuracy,
                    speed: pos.coords.speed, heading: pos.coords.heading });
            }, function () {
                if (done) { return; }
                done = true; clearTimeout(timer); resolve(null);
            }, { enableHighAccuracy: true, timeout: timeoutMs || 12000, maximumAge: 30000 });
        });
    }

    function shrink(file, maxSide) {
        return new Promise(function (resolve) {
            if (!file || !/^image\/(jpeg|png|webp)$/i.test(file.type) || file.size < 1.5 * 1024 * 1024 || !window.createImageBitmap) {
                resolve(file); return;
            }
            createImageBitmap(file).then(function (bmp) {
                var side = maxSide || 1600;
                var scale = Math.min(1, side / Math.max(bmp.width, bmp.height));
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(bmp.width * scale);
                canvas.height = Math.round(bmp.height * scale);
                canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function (blob) {
                    if (!blob || blob.size >= file.size) { resolve(file); return; }
                    var name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
                    try { resolve(new File([blob], name, { type: 'image/jpeg' })); } catch (e) { resolve(file); }
                }, 'image/jpeg', 0.85);
            }).catch(function () { resolve(file); });
        });
    }

    function toast(message, type) {
        if (typeof window.showNotification === 'function') { window.showNotification(message, type || 'info'); }
    }

    function money(value) {
        var n = Math.round(Number(value || 0) * 100) / 100;
        return n.toLocaleString('th-TH', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 });
    }

    window.rdApi = { post: post, upload: upload, geo: geo, shrink: shrink, toast: toast, money: money, statusText: statusText };

    /**
     * ปุ่ม "อัปเดตตำแหน่ง" (หน้าที่ไม่มีแดชบอร์ด) — ส่งพิกัดแล้วโหลดหน้าใหม่เพื่อค้นหางาน
     */
    window.rdLocator = function (url) {
        return {
            locating: false,
            async refresh() {
                if (this.locating) { return; }
                this.locating = true;
                var pos = await geo(12000);
                if (!pos) {
                    this.locating = false;
                    toast('อ่านตำแหน่งไม่ได้ กรุณาอนุญาตให้เว็บเข้าถึงตำแหน่งในเบราว์เซอร์', 'error');
                    return;
                }
                var res = await post(url, { latitude: pos.latitude, longitude: pos.longitude, accuracy: pos.accuracy });
                this.locating = false;
                if (res.ok) {
                    toast('อัปเดตตำแหน่งแล้ว กำลังค้นหางานใกล้คุณ...', 'success');
                    setTimeout(function () { window.location.reload(); }, 700);
                } else {
                    toast(res.message, 'error');
                }
            }
        };
    };

    /**
     * รายการงานรอรับ — cfg: { canAccept, blockCode, blockMessage, activeJobUrl (มี __ID__), jobsUrl }
     */
    window.rdJobBoard = function (cfg) {
        return {
            busyId: null,
            hidden: [],
            canAccept: !!cfg.canAccept,
            blockCode: cfg.blockCode || null,
            blockMessage: cfg.blockMessage || '',
            isHidden(id) { return this.hidden.indexOf(id) !== -1; },
            async accept(job) {
                if (this.busyId) { return; }
                if (!this.canAccept) {
                    toast(this.blockMessage || 'ตอนนี้ยังรับงานไม่ได้', 'warning');
                    if (this.blockCode === 'CONSENT_REQUIRED') { this.$dispatch('rd-need-consent'); }
                    return;
                }
                this.busyId = job.id;
                var pos = await geo(8000);
                var res = await post(job.acceptUrl, pos ? { latitude: pos.latitude, longitude: pos.longitude } : {});
                if (res.ok) {
                    toast(res.message || 'รับงานสำเร็จ!', 'success');
                    var id = (res.data && res.data.job && res.data.job.id) ? res.data.job.id : job.id;
                    window.location.href = String(cfg.activeJobUrl || '').replace('__ID__', String(id));
                    return;
                }
                this.busyId = null;
                toast(res.message, res.code === 'JOB_TAKEN' ? 'warning' : 'error');
                if (res.code === 'JOB_TAKEN' || res.code === 'JOB_NOT_FOUND' || res.status === 404) { this.hidden.push(job.id); }
                if (res.data && res.data.block_code === 'CONSENT_REQUIRED') { this.$dispatch('rd-need-consent'); }
            },
            async skip(job) {
                if (this.busyId) { return; }
                this.busyId = job.id;
                var res = await post(job.rejectUrl, {});
                this.busyId = null;
                if (res.ok) { this.hidden.push(job.id); toast('ซ่อนงานนี้แล้ว', 'info'); }
                else { toast(res.message, 'error'); }
            }
        };
    };
})();
</script>
