/* =====================================================================
   ธีม "โนวา" — เอฟเฟกต์หน้าแรก Thai Prompt
   - การ์ดกริด 3D แบบ "โซลูชั่นที่ตอบโจทย์": แผงเอียงตามเมาส์/การเลื่อน, การ์ดเอียงเข้าหาเมาส์ + แสงสะท้อน,
     พื้นหลังเรืองตรงการ์ดที่ชี้, ตอนเลือก = แสงวิ่ง + คลื่น + ประกายดาว + กระดิ่ง
   - พื้นหลัง "สายไหมทอง": canvas ชั้นเดียวต่อฉาก 30fps (ไม่ใช้ไลบรารี) หยุดเองเมื่อเลื่อนพ้น/สลับแท็บ/ลดการเคลื่อนไหว
   - เสียงเอฟเฟกต์สังเคราะห์สดด้วย WebAudio (ไม่มีเพลง ไม่มีไฟล์เสียง) ปิดได้ที่แถบหัว จำใน localStorage
   ===================================================================== */
(function () {
    'use strict';

    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var root = document.documentElement;
    var store = {
        get: function (k, d) { try { var v = localStorage.getItem(k); return v === null ? d : v; } catch (e) { return d; } },
        set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { /* โหมดส่วนตัว — ข้าม */ } }
    };
    var mq = function (q) { return window.matchMedia ? window.matchMedia(q).matches : false; };
    var reduce = mq('(prefers-reduced-motion: reduce)');
    var coarse = mq('(pointer: coarse)');
    var lite = (navigator.deviceMemory && navigator.deviceMemory < 4) || (navigator.connection && navigator.connection.saveData);
    var rnd = function (a, b) { return a + Math.random() * (b - a); };
    window.__nvReady = true; // บอกสคริปต์ใน <head> ว่าโหลดขึ้นแล้ว (ไม่ต้องยกเลิกการซ่อนเนื้อหา)
    // สคริปต์ใน <head> ถอด nv-js ทิ้งแล้ว (ไฟล์นี้มาช้าเกิน 3 วิ) = เนื้อหาโชว์อยู่แล้ว → ห้ามซ่อนซ้ำ ข้ามแอนิเมชันเผย
    var revealOn = root.classList.contains('nv-js');

    /* ------------------------------------------------ เสียงเอฟเฟกต์ */
    var Sfx = (function () {
        var ctx = null, out = null, verb = null, noise = null, lastHover = 0;
        var on = store.get('tp_nova_sfx', 'on') !== 'off';
        var SCALE = [523.25, 587.33, 659.25, 783.99, 880.0, 1046.5, 1174.66, 1318.51]; // เพนทาโทนิก ไม่มีโน้ตชนกัน
        function ensure() {
            if (ctx) return ctx;
            var AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return null;
            try { ctx = new AC(); } catch (e) { return null; }
            var comp = ctx.createDynamicsCompressor(); comp.threshold.value = -20; comp.ratio.value = 4;
            out = ctx.createGain(); out.gain.value = 0.6; out.connect(comp); comp.connect(ctx.destination);
            verb = ctx.createConvolver(); // ห้องก้องแบบศาลา สร้างเอง ไม่ต้องโหลดไฟล์
            var len = Math.floor(ctx.sampleRate * 1.6), ir = ctx.createBuffer(2, len, ctx.sampleRate);
            for (var c = 0; c < 2; c++) { var d = ir.getChannelData(c); for (var i = 0; i < len; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / len, 3); }
            verb.buffer = ir; var vg = ctx.createGain(); vg.gain.value = 0.32; verb.connect(vg); vg.connect(out);
            noise = ctx.createBuffer(1, ctx.sampleRate, ctx.sampleRate);
            var nd = noise.getChannelData(0); for (var j = 0; j < nd.length; j++) nd[j] = Math.random() * 2 - 1;
            return ctx;
        }
        // เบราว์เซอร์ยอมให้มีเสียงหลังผู้ใช้แตะ/กดครั้งแรกเท่านั้น
        function unlock() { if (!on) return; var c = ensure(); if (c && c.state === 'suspended') c.resume(); }
        ['pointerdown', 'keydown', 'touchstart'].forEach(function (ev) { window.addEventListener(ev, unlock, { passive: true }); });
        function ready() { return on && ctx && ctx.state === 'running'; }
        function tone(f, t, dur, g, type, wet, attack, pan) {
            var o = ctx.createOscillator(), a = ctx.createGain();
            o.type = type || 'sine'; o.frequency.setValueAtTime(f, t);
            a.gain.setValueAtTime(0.0001, t); a.gain.exponentialRampToValueAtTime(g, t + (attack || 0.004)); a.gain.exponentialRampToValueAtTime(0.0001, t + dur);
            o.connect(a);
            if (pan && ctx.createStereoPanner) { var p = ctx.createStereoPanner(); p.pan.value = pan; a.connect(p); p.connect(out); } else { a.connect(out); }
            if (wet) { var w = ctx.createGain(); w.gain.value = wet; a.connect(w); w.connect(verb); }
            o.start(t); o.stop(t + dur + 0.05);
        }
        function hiss(t, dur, f0, f1, g, q) {
            var s = ctx.createBufferSource(), bp = ctx.createBiquadFilter(), a = ctx.createGain();
            s.buffer = noise; bp.type = 'bandpass'; bp.Q.value = q || 1.2; bp.frequency.setValueAtTime(f0, t); bp.frequency.exponentialRampToValueAtTime(f1, t + dur);
            a.gain.setValueAtTime(0.0001, t); a.gain.exponentialRampToValueAtTime(g, t + dur * 0.3); a.gain.exponentialRampToValueAtTime(0.0001, t + dur);
            s.connect(bp); bp.connect(a); a.connect(out); s.start(t); s.stop(t + dur + 0.05);
        }
        return {
            isOn: function () { return on; },
            set: function (v) { on = !!v; store.set('tp_nova_sfx', on ? 'on' : 'off'); if (on) unlock(); },
            // ชี้การ์ด — "ติ๊ง" แผ่วๆ แพนตามตำแหน่งซ้าย/ขวา
            hover: function (pan, i) {
                if (!ready()) return;
                var now = performance.now(); if (now - lastHover < 70) return; lastHover = now;
                var t = ctx.currentTime, f = SCALE[i % SCALE.length] * 2;
                tone(f, t, 0.16, 0.03, 'sine', 0.2, 0.005, pan * 0.7); tone(f * 2.01, t, 0.05, 0.008, 'sine', 0, 0.003, pan * 0.7);
            },
            // เลือก — กระดิ่งทองในศาลา + ประกายโน้ตสูง
            select: function (i) {
                if (!ready()) return;
                var t = ctx.currentTime, f = SCALE[(i + 2) % SCALE.length] * 0.5;
                [[1, .14, 2.4], [2.0, .05, 1.5], [2.76, .06, 1.2], [4.07, .03, .8], [5.4, .018, .5]].forEach(function (p) {
                    tone(f * p[0], t, p[2], p[1], 'sine', 0.55); tone(f * p[0] * 1.003, t, p[2] * 0.9, p[1] * 0.5, 'sine', 0.55);
                });
                tone(f * 0.5, t, 1.1, 0.05, 'sine', 0.3, 0.02);
                [1318.51, 1567.98, 2093.0].forEach(function (n, k) { tone(n, t + 0.06 + k * 0.06, 0.35, 0.028, 'sine', 0.6); });
                hiss(t, 0.45, 500, 3200, 0.03, 0.8);
            },
            ui: function () { if (ready()) tone(1760, ctx.currentTime, 0.08, 0.02, 'sine', 0.1); }
        };
    })();

    /* ------------------------------------------------ พื้นหลัง canvas "สายไหมทอง" — 1 ชั้นต่อฉาก */
    function makeFX(section, cv, kind) {
        if (!section || !cv || !cv.getContext) return null;
        var g = cv.getContext('2d');
        var W = 0, H = 0, dpr = 1, raf = 0, running = false, visible = false, last = 0, fastUntil = 0;
        var dust = [], stars = [], bits = [], waves = [];
        var bc = { x: 0, y: 0, tx: 0, ty: 0, a: 0, ta: 0, hex: '#f0c96a' }; // แสงเรืองหลังการ์ดที่ชี้
        var sprites = {};
        function rgb(hex) { return [parseInt(hex.slice(1, 3), 16), parseInt(hex.slice(3, 5), 16), parseInt(hex.slice(5, 7), 16)]; }
        function glow(hex) {
            if (sprites[hex]) return sprites[hex];
            var s = 64, c = document.createElement('canvas'); c.width = c.height = s; var x = c.getContext('2d'), k = rgb(hex);
            var gr = x.createRadialGradient(s / 2, s / 2, 0, s / 2, s / 2, s / 2);
            gr.addColorStop(0, 'rgba(255,250,235,1)'); gr.addColorStop(0.22, 'rgba(' + k + ',.9)'); gr.addColorStop(0.55, 'rgba(' + k + ',.22)'); gr.addColorStop(1, 'rgba(' + k + ',0)');
            x.fillStyle = gr; x.fillRect(0, 0, s, s); return (sprites[hex] = c);
        }
        function star(hex) { // ประกายดาวสี่แฉก
            var key = 's' + hex; if (sprites[key]) return sprites[key];
            var c = document.createElement('canvas'); c.width = c.height = 64; var x = c.getContext('2d'), k = rgb(hex);
            x.translate(32, 32);
            var gr = x.createRadialGradient(0, 0, 0, 0, 0, 32); gr.addColorStop(0, 'rgba(255,252,240,1)'); gr.addColorStop(0.3, 'rgba(' + k + ',.95)'); gr.addColorStop(1, 'rgba(' + k + ',0)');
            x.fillStyle = gr; x.beginPath();
            for (var i = 0; i < 8; i++) { var r = i % 2 === 0 ? 32 : 4.5, a = i * Math.PI / 4 - Math.PI / 2; x.lineTo(Math.cos(a) * r, Math.sin(a) * r); }
            x.closePath(); x.fill(); return (sprites[key] = c);
        }
        function newDust(init) { var z = rnd(0.35, 1); return { x: rnd(0, W), y: init ? rnd(0, H) : H + 8, z: z, vy: -rnd(5, 16) * z, sw: rnd(6, 20), ph: rnd(0, 6.3), s: rnd(2, 6) * z, a: rnd(0.25, 0.8) }; }
        function seed() {
            var k = (lite ? 0.4 : W < 700 ? 0.6 : 1) * (kind === 'band' ? 0.7 : 1);
            dust = []; stars = [];
            for (var i = 0; i < Math.round(95 * k); i++) dust.push(newDust(true));
            for (var j = 0; j < Math.round(80 * k); j++) stars.push({ x: rnd(0, W), y: rnd(0, H * (kind === 'band' ? 1 : 0.7)), r: rnd(0.6, 1.8), ph: rnd(0, 6.3), sp: rnd(0.4, 1.6) });
        }
        function resize() {
            dpr = Math.min(window.devicePixelRatio || 1, lite ? 1 : 1.5);
            W = section.clientWidth; H = section.clientHeight;
            cv.width = Math.round(W * dpr); cv.height = Math.round(H * dpr);
            seed(); draw(performance.now() / 1000);
        }
        var RIB = [
            { y: 0.63, amp: 0.07, len: 1.15, sp: 0.05, th: 0.055, a: 0.2, ph: 0 },
            { y: 0.71, amp: 0.05, len: 0.85, sp: -0.04, th: 0.035, a: 0.14, ph: 2.1 },
            { y: 0.55, amp: 0.09, len: 1.5, sp: 0.03, th: 0.028, a: 0.12, ph: 4.2 }
        ];
        function ribbons(t) { // สายไหมทองพลิ้วช้าๆ — 3 เส้น เส้นละ 45 จุด
            for (var n = 0; n < RIB.length; n++) {
                var r = RIB[n], top = [], bot = [];
                for (var i = 0; i <= 44; i++) {
                    var u = i / 44, x = -0.05 * W + u * 1.1 * W;
                    var y = H * (r.y + r.amp * Math.sin(u * 6.283 / r.len + t * r.sp * 6.283 + r.ph) + 0.015 * Math.sin(u * 17 + t * 0.7 + r.ph));
                    var th = H * r.th * (0.55 + 0.45 * Math.sin(u * 4.1 + t * 0.35 + r.ph));
                    top.push(x, y); bot.push(x, y + th);
                }
                var gr = g.createLinearGradient(0, 0, W, 0);
                gr.addColorStop(0, 'rgba(240,201,106,0)'); gr.addColorStop(0.3, 'rgba(240,201,106,' + r.a + ')'); gr.addColorStop(0.62, 'rgba(255,232,176,' + (r.a * 1.25) + ')'); gr.addColorStop(1, 'rgba(240,201,106,0)');
                g.beginPath(); g.moveTo(top[0], top[1]);
                for (var p = 2; p < top.length; p += 2) g.lineTo(top[p], top[p + 1]);
                for (var q = bot.length - 2; q >= 0; q -= 2) g.lineTo(bot[q], bot[q + 1]);
                g.closePath(); g.fillStyle = gr; g.fill();
                g.beginPath(); g.moveTo(top[0], top[1]);
                for (var m = 2; m < top.length; m += 2) g.lineTo(top[m], top[m + 1]);
                g.strokeStyle = gr; g.globalAlpha = 0.9; g.lineWidth = 1; g.stroke(); g.globalAlpha = 1;
            }
        }
        function update(dt, t) {
            for (var i = 0; i < dust.length; i++) { var d = dust[i]; d.y += d.vy * dt; d.x += Math.sin(t * 0.3 + d.ph) * d.sw * dt * 0.3; if (d.y < -10) dust[i] = newDust(false); }
            for (var b = bits.length - 1; b >= 0; b--) { var p = bits[b]; p.life -= dt; if (p.life <= 0) { bits.splice(b, 1); continue; } var k = Math.pow(0.12, dt); p.vx *= k; p.vy = p.vy * k + 40 * dt; p.x += p.vx * dt; p.y += p.vy * dt; }
            for (var w = waves.length - 1; w >= 0; w--) { var v = waves[w]; v.life -= dt; if (v.life <= 0) { waves.splice(w, 1); continue; } v.r += v.vr * dt; v.vr *= Math.pow(0.25, dt); }
            bc.a += (bc.ta - bc.a) * Math.min(1, dt * 7); bc.x += (bc.tx - bc.x) * Math.min(1, dt * 12); bc.y += (bc.ty - bc.y) * Math.min(1, dt * 12);
        }
        function draw(t) {
            g.setTransform(dpr, 0, 0, dpr, 0, 0); g.clearRect(0, 0, W, H);
            g.fillStyle = '#fff4d8';
            for (var i = 0; i < stars.length; i++) { var s = stars[i]; g.globalAlpha = 0.12 + 0.75 * Math.pow(Math.abs(Math.sin(t * s.sp + s.ph)), 5); g.fillRect(s.x, s.y, s.r, s.r); }
            g.globalAlpha = 1; g.globalCompositeOperation = 'lighter';
            if (kind === 'hero') ribbons(t);
            if (bc.a > 0.01) {
                var sz = Math.min(W, 1000) * 0.78, pu = 1 + 0.05 * Math.sin(t * 2.4);
                g.globalAlpha = bc.a; g.drawImage(glow(bc.hex), bc.x - sz * pu / 2, bc.y - sz * pu / 2, sz * pu, sz * pu);
                g.globalAlpha = bc.a * 0.55; g.drawImage(glow('#fff1c7'), bc.x - sz * 0.17, bc.y - sz * 0.17, sz * 0.34, sz * 0.34);
            }
            var gold = glow('#f0c96a');
            for (var j = 0; j < dust.length; j++) { var d = dust[j]; g.globalAlpha = d.a * (0.55 + 0.45 * Math.sin(t * 1.6 + d.ph)); g.drawImage(gold, d.x - d.s, d.y - d.s, d.s * 2, d.s * 2); }
            for (var k = 0; k < bits.length; k++) { var b = bits[k]; g.globalAlpha = Math.min(1, b.life / b.max * 1.6); g.drawImage(b.sp, b.x - b.s, b.y - b.s, b.s * 2, b.s * 2); }
            for (var m = 0; m < waves.length; m++) { var w = waves[m]; g.globalAlpha = (w.life / w.max) * 0.8; g.strokeStyle = w.c; g.lineWidth = 2 * (w.life / w.max) + 0.5; g.beginPath(); g.ellipse(w.x, w.y, w.r, w.r * 0.42, 0, 0, 6.283); g.stroke(); }
            g.globalAlpha = 1; g.globalCompositeOperation = 'source-over';
        }
        function loop(now) {
            raf = requestAnimationFrame(loop);
            var minDt = now < fastUntil ? 0 : 1000 / (lite ? 24 : 30);
            if (now - last < minDt - 1) return;
            var dt = Math.min(0.1, (now - last) / 1000); last = now;
            update(dt, now / 1000); draw(now / 1000);
        }
        function start() { if (running || reduce || !visible || document.hidden) return; running = true; last = performance.now(); raf = requestAnimationFrame(loop); }
        function stop() { if (!running) return; running = false; cancelAnimationFrame(raf); }
        if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { visible = es[0].isIntersecting; if (visible) start(); else stop(); }, { threshold: 0.01 }).observe(section);
        else { visible = true; }
        document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });
        var rt = 0; window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(resize, 150); });
        resize(); start();
        return {
            burst: function (x, y, hex) {
                if (reduce) return;
                var n = lite ? 40 : 96, sp = [glow('#f0c96a'), glow(hex), glow('#fff1c7'), star('#f5d27f'), star(hex)];
                for (var i = 0; i < n; i++) {
                    var a = rnd(0, 6.283), v = rnd(120, 560), big = i % 5 >= 3;
                    bits.push({ x: x, y: y, vx: Math.cos(a) * v, vy: Math.sin(a) * v * 0.7 - 80, life: rnd(0.8, 1.7), max: 1.7, s: big ? rnd(6, 13) : rnd(2, 6), sp: sp[i % 5] });
                }
                waves.push({ x: x, y: y + 30, r: 20, vr: 1100, life: 0.8, max: 0.8, c: '#f5d27f' }, { x: x, y: y + 30, r: 10, vr: 700, life: 1, max: 1, c: hex });
                fastUntil = performance.now() + 1500;
            },
            beacon: function (x, y, hex) { if (reduce) return; if (bc.a < 0.02) { bc.x = x; bc.y = y; } bc.tx = x; bc.ty = y; bc.ta = 1; bc.hex = hex || '#f0c96a'; },
            beaconOff: function () { bc.ta = 0; }
        };
    }
    var heroSec = $('#nv-hero'), bandSec = $('#nv-cats');
    var heroFX = heroSec ? makeFX(heroSec, $('#nv-fx'), 'hero') : null;
    var bandFX = bandSec ? makeFX(bandSec, $('#nv-fx2'), 'band') : null;
    function fxOf(el) {
        if (bandSec && bandSec.contains(el)) return { fx: bandFX, sec: bandSec, flash: $('#nv-flash2') };
        return { fx: heroFX, sec: heroSec, flash: $('#nv-flash') };
    }

    /* ------------------------------------------------ ป้ายแจ้งสั้นๆ */
    var toastEl = $('#nv-toast'), toastT = 0;
    function toast(msg) {
        if (!toastEl) return;
        toastEl.textContent = msg; toastEl.classList.add('is-show');
        clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('is-show'); }, 2400);
    }

    /* ------------------------------------------------ ไกด์มาสคอต "น้องพร้อม" */
    var Guide = (function () {
        var el = $('#nv-guide');
        var nil = { speak: function () {}, pose: function () {} };
        if (!el) return nil;
        var bub = $('#nv-guide-bubble'), say = $('#nv-guide-say'), full = $('#nv-guide-full'), fig = $('#nv-guide-fig');
        var t = 0, docked = false, shut = store.get('tp_nova_guide', '') === 'shut';
        var poses = { welcome: el.getAttribute('data-welcome'), present: el.getAttribute('data-present') };
        // โหลดท่าผายมือรอไว้ เฉพาะจอที่ได้เห็นตัวเต็ม (มือถือเริ่มแบบย่อ = ไม่ต้องเปลืองเน็ต)
        if (!shut && innerWidth >= 720) { var pre = new Image(); pre.src = poses.present; }
        var tips = (el.getAttribute('data-tips') || '').split('|').filter(Boolean), tip = 0;
        function pose(name) {
            if (docked || !poses[name] || full.getAttribute('src') === poses[name]) return;
            full.setAttribute('src', poses[name]);
            full.classList.remove('is-swap'); void full.offsetWidth; full.classList.add('is-swap');
        }
        function hide() { bub.classList.add('is-hide'); }
        function speak(text, ms) {
            if (!text || shut) return;
            say.textContent = text; bub.classList.remove('is-hide'); clearTimeout(t);
            if (ms !== 0) t = setTimeout(function () { hide(); pose('welcome'); }, ms || 4200);
        }
        function dock(v) { docked = v; el.classList.toggle('is-docked', v); if (v) full.setAttribute('src', poses.welcome); }
        if (shut || innerWidth < 720) { dock(true); if (shut) hide(); else setTimeout(hide, 5000); }
        else setTimeout(function () { if (!docked) { dock(true); hide(); } }, 12000);
        window.addEventListener('scroll', function () { if (!docked && window.scrollY > innerHeight * 0.5) { dock(true); hide(); } }, { passive: true });
        fig.addEventListener('click', function () {
            if (!docked) { dock(true); hide(); return; }
            shut = false; store.set('tp_nova_guide', '');
            if (tips.length) { speak(tips[tip % tips.length], 5200); tip++; }
        });
        var x = $('#nv-guide-x');
        if (x) x.addEventListener('click', function (e) { e.stopPropagation(); hide(); dock(true); shut = true; store.set('tp_nova_guide', 'shut'); });
        return { speak: speak, pose: pose };
    })();

    /* ------------------------------------------------ แผงการ์ดเอียงตามเมาส์ (+ ตามการเลื่อนสำหรับแถบหมวดหมู่) */
    function tiltGrid(grid, sec, sweep) {
        if (!grid || !sec) return;
        if (reduce || coarse) { grid.style.setProperty('--tx', '4deg'); grid.style.setProperty('--ty', '0deg'); return; }
        var st = { tx: 6, ty: 0, cx: 6, cy: 0, px: 0, py: 0, on: false }, raf = 0;
        function target() {
            var ty = st.px * (sweep ? 3 : 5), tx = (sweep ? 7 : 6) - st.py * 3;
            if (sweep) { var r = sec.getBoundingClientRect(), p = Math.min(1, Math.max(0, (innerHeight - r.top) / (innerHeight + r.height))); ty += 9 - 18 * (p * p * (3 - 2 * p)); }
            st.tx = tx; st.ty = ty;
        }
        function loop() {
            raf = 0; st.cx += (st.tx - st.cx) * 0.12; st.cy += (st.ty - st.cy) * 0.12;
            grid.style.setProperty('--tx', st.cx.toFixed(2) + 'deg'); grid.style.setProperty('--ty', st.cy.toFixed(2) + 'deg');
            if (Math.abs(st.tx - st.cx) > 0.02 || Math.abs(st.ty - st.cy) > 0.02) raf = requestAnimationFrame(loop);
        }
        function kick() { if (!st.on) return; target(); if (!raf) raf = requestAnimationFrame(loop); }
        if ('IntersectionObserver' in window) new IntersectionObserver(function (es) { st.on = es[0].isIntersecting; kick(); }).observe(sec);
        window.addEventListener('pointermove', function (e) { st.px = e.clientX / innerWidth * 2 - 1; st.py = e.clientY / innerHeight * 2 - 1; kick(); }, { passive: true });
        window.addEventListener('scroll', kick, { passive: true });
    }
    tiltGrid($('#nv-svc-grid'), heroSec, false);
    tiltGrid($('#nv-cat-grid'), bandSec, true);

    /* ------------------------------------------------ การ์ดแต่ละใบ: เอียงเข้าหาเมาส์ + แสงสะท้อน + พื้นหลังเรือง + เสียง */
    var hot = null;
    function accentOf(c) { return (c.style.getPropertyValue('--accent') || '#f0c96a').trim(); }
    document.addEventListener('pointerover', function (e) {
        var c = e.target.closest && e.target.closest('.nv-tcard');
        if (!c || c === hot) return;
        hot = c; c.classList.add('is-hover');
        c.classList.remove('is-shine'); void c.offsetWidth; c.classList.add('is-shine');
        setTimeout(function () { c.classList.remove('is-shine'); }, 1150);
        var o = fxOf(c), r = c.getBoundingClientRect(), sr = o.sec ? o.sec.getBoundingClientRect() : null;
        if (o.fx && sr) o.fx.beacon(r.left + r.width / 2 - sr.left, r.top + r.height / 2 - sr.top, accentOf(c));
        Sfx.hover(((r.left + r.width / 2) / innerWidth) * 2 - 1, +(c.getAttribute('data-i') || 0));
        Guide.speak(c.getAttribute('data-say'), 3800); Guide.pose('present');
    });
    document.addEventListener('pointerout', function (e) {
        var c = e.target.closest && e.target.closest('.nv-tcard');
        if (!c || c.contains(e.relatedTarget)) return;
        c.classList.remove('is-hover'); c.style.rotate = ''; if (hot === c) hot = null;
        var o = fxOf(c); if (o.fx) o.fx.beaconOff();
    });
    document.addEventListener('pointermove', function (e) {
        if (e.pointerType === 'touch') return; // จอสัมผัสไม่มีเมาส์ให้เอียงตาม
        var c = e.target.closest && e.target.closest('.nv-tcard');
        if (!c) return;
        var r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
        c.style.setProperty('--gx', (x * 100).toFixed(1) + '%'); c.style.setProperty('--gy', (y * 100).toFixed(1) + '%');
        var ax = (0.5 - y) * 2, ay = (x - 0.5) * 2, ang = Math.hypot(ax, ay) * 5;
        if (ang > 0.05) c.style.rotate = ax.toFixed(3) + ' ' + ay.toFixed(3) + ' 0 ' + ang.toFixed(2) + 'deg';
    }, { passive: true });
    var navT = 0; // ตัวจับเวลาเปลี่ยนหน้า มีได้ตัวเดียว (ดับเบิลคลิก/คลิกสองการ์ดติดกัน = ไปตามการ์ดล่าสุด)
    document.addEventListener('click', function (e) {
        var c = e.target.closest && e.target.closest('.nv-tcard');
        if (!c) return;
        // คลิกกลาง/กดค้าง Ctrl-Cmd-Shift = เปิดแท็บใหม่ตามปกติของเบราว์เซอร์
        if (e.defaultPrevented || e.button > 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var href = c.getAttribute('href'); if (!href) return;
        e.preventDefault();
        var r = c.getBoundingClientRect(), o = fxOf(c);
        var x = e.clientX || r.left + r.width / 2, y = e.clientY || r.top + r.height / 2;
        var rp = document.createElement('span'); rp.className = 'nv-tcard__ripple'; rp.style.left = (x - r.left) + 'px'; rp.style.top = (y - r.top) + 'px';
        c.appendChild(rp); setTimeout(function () { rp.remove(); }, 850);
        c.classList.add('is-press'); setTimeout(function () { c.classList.remove('is-press'); }, 200);
        c.classList.remove('is-picked'); void c.offsetWidth; c.classList.add('is-picked'); setTimeout(function () { c.classList.remove('is-picked'); }, 820);
        if (o.fx && o.sec) {
            var sr = o.sec.getBoundingClientRect();
            o.fx.burst(x - sr.left, y - sr.top, accentOf(c));
            if (o.flash) {
                o.flash.style.setProperty('--fx-x', ((x - sr.left) / sr.width * 100).toFixed(1) + '%');
                o.flash.style.setProperty('--fx-y', ((y - sr.top) / sr.height * 100).toFixed(1) + '%');
                o.flash.classList.remove('go'); void o.flash.offsetWidth; o.flash.classList.add('go');
            }
        }
        Sfx.select(+(c.getAttribute('data-i') || 0));
        var name = c.getAttribute('data-name') || '';
        Guide.speak(c.getAttribute('data-go-say') || ('ไป' + name + 'กันเลยค่ะ'), 2600);
        clearTimeout(navT);
        navT = setTimeout(function () {
            navT = 0;
            if (href.charAt(0) === '#') {
                var tg = document.querySelector(href);
                if (tg) tg.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' }); else location.hash = href;
            } else {
                toast('กำลังไปที่ ' + name + ' …');
                window.location.href = href;
            }
        }, reduce ? 0 : 420);
    });

    // ออกจากหน้า/กลับมาจาก bfcache (ปุ่ม Back) — ล้างตัวจับเวลาค้าง ไม่ให้เด้งไปหน้าปลายทางซ้ำ และล้างสถานะชี้การ์ด
    window.addEventListener('pagehide', function () { clearTimeout(navT); navT = 0; });
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        clearTimeout(navT); navT = 0; hot = null;
        $$('.nv-tcard.is-hover').forEach(function (c) {
            c.classList.remove('is-hover'); c.style.rotate = '';
            var o = fxOf(c); if (o.fx) o.fx.beaconOff();
        });
    });

    /* ------------------------------------------------ ปุ่มเสียงเอฟเฟกต์บนแถบหัว */
    $$('[data-nv-sfx]').forEach(function (btn) {
        var tipEl = btn.querySelector('.nv-tip'), ft = 0;
        function paint() { btn.setAttribute('aria-pressed', String(Sfx.isOn())); if (tipEl) tipEl.textContent = 'เสียงเอฟเฟกต์ ' + (Sfx.isOn() ? 'เปิด' : 'ปิด'); }
        btn.addEventListener('click', function () {
            Sfx.set(!Sfx.isOn()); paint(); Sfx.ui();
            btn.classList.add('is-flash'); clearTimeout(ft); ft = setTimeout(function () { btn.classList.remove('is-flash'); }, 1400);
        });
        paint();
    });

    /* ------------------------------------------------ แถบหัวทึบเมื่อเลื่อน */
    var nav = $('#nv-nav');
    if (nav) {
        var onScroll = function () { nav.classList.toggle('is-solid', window.scrollY > 40); };
        window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
    }

    /* ------------------------------------------------ พารัลแลกซ์ของวัตถุตกแต่ง: อัปเดต --nv-mx/--nv-my ครั้งเดียวต่อเฟรม */
    if (!reduce && !coarse) {
        var pr = 0, px = 0, py = 0;
        window.addEventListener('pointermove', function (e) {
            px = e.clientX / innerWidth * 2 - 1; py = e.clientY / innerHeight * 2 - 1;
            if (!pr) pr = requestAnimationFrame(function () { pr = 0; root.style.setProperty('--nv-mx', px.toFixed(3)); root.style.setProperty('--nv-my', py.toFixed(3)); });
        }, { passive: true });
    }

    /* ------------------------------------------------ เผยเนื้อหาเมื่อเลื่อนถึง */
    // การ์ดบนฮีโร่เล่นแอนิเมชันเข้าด้วย CSS เอง (ไม่รอสคริปต์) — ที่นี่ดูแลเฉพาะส่วนที่อยู่ใต้ฮีโร่
    if (revealOn) {
        var rvs = $$('.nv-rv').filter(function (el) { return !el.closest('.nv-hero'); });
        // กันเนื้อหาหายค้าง: 4 วิแล้วยังไม่เผย แต่อยู่ในจอแล้ว = เผยเลย
        setTimeout(function () {
            rvs.forEach(function (el) { if (!el.classList.contains('is-in') && el.getBoundingClientRect().top < innerHeight) el.classList.add('is-in'); });
        }, 4000);
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (es) {
                es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
            }, { rootMargin: '0px 0px -6% 0px' });
            rvs.forEach(function (el) { io.observe(el); });
        } else {
            rvs.forEach(function (el) { el.classList.add('is-in'); });
        }
    }
})();
