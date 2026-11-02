{{--
    จำลองสถานการณ์ MLM (ธีมโนวาเต็ม) — route closure user.mlm.scenario-simulator (เครื่องมือการตลาด ไม่แตะเงินจริง)
    ข้อมูล: GET user.mlm.settings (global_pv_rate, unilevel_levels, unilevel_max_depth)
    🐛 แก้จากหน้าเดิม:
       - เปลี่ยน "ยอดขายต่อคน" แล้วตัวเลขไม่เปลี่ยน (ยอดขายถูกอ่านแค่ตอนสร้างผัง) → อ่านใหม่ทุกครั้งที่คำนวณ
       - คิดค่าคอมให้อัพไลน์แค่ชั้นเดียว ทั้งที่ตั้งค่า Unilevel มีหลายชั้น → ไล่ขึ้นทุกชั้นตาม unilevel_levels พร้อมข้ามคนไม่แอคทีฟ (rollup)
       - "เส้น Rollup" ไม่เคยถูกวาด (ไม่มี connection ชนิด rollup) และติ๊ก "แอนิเมชั่น" ไม่มีผล → วาดเส้นประจริง + ติ๊กแล้วเล่นอัตโนมัติ
       - ป้ายค่าคอมแสดง "0K" เมื่อยอดต่ำกว่าพัน, renderTree ถูกเรียกซ้ำ 2 รอบทุกคลิก, อนุภาคแอนิเมชันลอยผิดตำแหน่งเมื่อเลื่อนผัง
       - ปุ่ม "คัดลอกลิงก์" คัดลอกแค่ URL หน้าเปล่า (ไม่มีสถานการณ์ติดไป) → เอาออก
       - d3/html2canvas ไม่ปักเวอร์ชัน → d3@7.9.0 และบันทึกรูปจาก SVG โดยตรง (ไม่ต้องใช้ html2canvas)
--}}
@extends('layouts.user-nova')

@section('title', 'จำลองสถานการณ์ MLM')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page" x-data="nvmMlScenario(@js(route('user.mlm.settings')))">

<section class="nvm-ph" aria-labelledby="nvm-mlsc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.genealogy'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.genealogy') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ผังสายงาน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Scenario simulator</div>
            <h1 id="nvm-mlsc-h">จำลองสถานการณ์ทีม</h1>
            <p class="nvm-ph__lead">แตะสมาชิกในผังเพื่อสลับแอคทีฟ/ไม่แอคทีฟ แล้วดูว่าค่าคอม Unilevel ไหลขึ้นไปหาใคร</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/lantern.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-alert nvm-alert--info">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
    <div class="nvm-alert__txt"><b>ผังนี้เป็นทีมสมมติ</b><small>ใช้ดูหลักการ Rollup และค่าคอม Unilevel ตามค่าตั้งของระบบ ไม่ใช่ทีมจริงและไม่ใช่การรับประกันรายได้</small></div>
</div>

<div class="nvm-ml-sim">
    <div class="nvm-stack nvm-ml-noprint">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlsc-p-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Presets</small><h2 id="nvm-mlsc-p-h">สถานการณ์ตัวอย่าง</h2></div></div>
            <div class="nvm-ml-presets">
                <button type="button" class="nvm-btn nvm-btn--line" @click="preset('perfect')"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ทุกคนแอคทีฟ</button>
                <button type="button" class="nvm-btn nvm-btn--line" @click="preset('realistic')"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>สมจริง (แอคทีฟ ~70%)</button>
                <button type="button" class="nvm-btn nvm-btn--line" @click="preset('rollup')"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>ตัวอย่าง Rollup</button>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlsc-s-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Settings</small><h2 id="nvm-mlsc-s-h">ตั้งค่าผัง</h2></div></div>
            <div class="nvm-form">
                <div class="nvm-field">
                    <label class="nvm-label" for="mlsc-sales">ยอดขายต่อคน (บาท/เดือน)</label>
                    <input id="mlsc-sales" type="number" inputmode="numeric" min="0" step="1000" class="nvm-input nvm-num" x-model.number="sales" @input.debounce.300ms="recalc()">
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="mlsc-depth">จำนวนชั้น</label>
                    <select id="mlsc-depth" class="nvm-input" x-model.number="depth" @change="build()">
                        <option value="2">2 ชั้น (7 คน)</option>
                        <option value="3">3 ชั้น (15 คน)</option>
                        <option value="4">4 ชั้น (31 คน)</option>
                        <option value="5">5 ชั้น (63 คน)</option>
                    </select>
                </div>
                <label class="nvm-check"><input type="checkbox" x-model="showRollup" @change="draw()"><span>แสดงเส้น Rollup</span></label>
                <label class="nvm-check"><input type="checkbox" x-model="autoAnimate"><span>เล่นแอนิเมชันทุกครั้งที่เปลี่ยน</span></label>
                <button type="button" class="nvm-btn nvm-btn--gold" @click="play()" :disabled="animating"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>เล่นการไหลของค่าคอม</button>
            </div>
            <p class="nvm-err nvm-gap-t" x-show="settingsFallback" x-cloak>โหลดค่าตั้งของระบบไม่สำเร็จ กำลังใช้ค่าตัวอย่างแทน</p>
            <p class="nvm-note" x-show="! settingsFallback && ! levels.length" x-cloak><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>ระบบยังไม่ได้ตั้งเปอร์เซ็นต์ Unilevel ค่าคอมจึงเป็น 0</span></p>
        </section>
    </div>

    <section class="nvm-card nvm-box nvm-ml-tree" aria-labelledby="nvm-mlsc-t-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Team tree</small><h2 id="nvm-mlsc-t-h">ผังทีมสมมติ</h2></div></div>
        <div class="nvm-ml-tree__canvas nvm-ml-simtree" x-ref="canvas" role="img" aria-label="ผังทีมสมมติ แตะสมาชิกเพื่อสลับสถานะ"></div>
        <ul class="nvm-ml-legend">
            <li><i class="is-ok"></i>แอคทีฟ (ได้รับค่าคอม)</li>
            <li><i class="is-bad"></i>ไม่แอคทีฟ (ค่าคอมถูกส่งขึ้นไป)</li>
            <li><i class="is-line"></i>สายงานปกติ</li>
            <li><i class="is-dash"></i>เส้นทาง Rollup</li>
        </ul>
    </section>

    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlsc-r-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Summary</small><h2 id="nvm-mlsc-r-h">สรุปรายได้</h2></div></div>
            <div class="nvm-minis">
                <div class="nvm-mini nvm-mini--wide"><small>ค่าคอมที่คุณได้</small><b class="nvm-num is-in">฿<span x-text="fmt(rootCommission)"></span></b></div>
                <div class="nvm-mini"><small>ค่าคอมทั้งผัง</small><b class="nvm-num">฿<span x-text="fmt(totalCommission)"></span></b></div>
                <div class="nvm-mini"><small>แอคทีฟ</small><b class="nvm-num"><span x-text="activeCount"></span>/<span x-text="members.length"></span> (<span x-text="activeRate"></span>%)</b></div>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlsc-l-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">By level</small><h2 id="nvm-mlsc-l-h">ค่าคอมของคุณแยกตามชั้น</h2></div></div>
            <div class="nvm-txs">
                <template x-for="row in byLevel" :key="row.level">
                    <div class="nvm-tx">
                        <span class="nvm-tx__ic"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg></span>
                        <span class="nvm-tx__t"><b>ชั้น <span x-text="row.level"></span></b><small>แอคทีฟ <span x-text="row.active"></span>/<span x-text="row.total"></span> คน</small></span>
                        <span class="nvm-tx__amt is-in nvm-num">฿<span x-text="fmt(row.commission)"></span></span>
                    </div>
                </template>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlsc-ru-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Rollup</small><h2 id="nvm-mlsc-ru-h">รายละเอียด Rollup</h2></div><span class="nvm-chip" x-show="rollups.length" x-text="rollups.length + ' เส้นทาง'"></span></div>
            <p class="nvm-desc" x-show="! rollups.length">ไม่มี Rollup ทุกคนแอคทีฟ แตะสมาชิกในผังเพื่อปิดการแอคทีฟ</p>
            <ul class="nvm-ml-rollups" x-show="rollups.length">
                <template x-for="(r, i) in rollups.slice(0, 30)" :key="i">
                    <li><b class="is-ok" x-text="r.from"></b><svg class="nvm-ico"><use href="#nvm-i-chev"/></svg><s x-text="r.skipped.join(', ')"></s><svg class="nvm-ico"><use href="#nvm-i-chev"/></svg><b class="is-gold" x-text="r.to"></b></li>
                </template>
            </ul>
            <p class="nvm-help" x-show="rollups.length > 30" x-text="'และอีก ' + (rollups.length - 30) + ' เส้นทาง'"></p>
        </section>

        <section class="nvm-card nvm-box nvm-ml-noprint" aria-labelledby="nvm-mlsc-x-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Save</small><h2 id="nvm-mlsc-x-h">บันทึก</h2></div></div>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="saveImage()"><svg class="nvm-ico"><use href="#nvm-i-camera"/></svg>บันทึกรูปผัง</button>
                <button type="button" class="nvm-btn nvm-btn--ghost" @click="window.print()"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>พิมพ์</button>
            </div>
        </section>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js"></script>
<script>
/**
 * จำลองสถานการณ์ทีม (ผังไบนารีสมมติ) — Alpine + d3
 * ค่าคอม Unilevel: สมาชิกแต่ละคนส่งค่าคอมขึ้นไปตามชั้น ข้ามคนที่ไม่แอคทีฟ (dynamic compression / rollup)
 */
function nvmMlScenario(settingsUrl) {
    const FALLBACK_LEVELS = [10, 5, 3, 2, 1].map((p, i) => ({ level: i + 1, percentage: p }));
    const css = (name, fallback) => (getComputedStyle(document.documentElement).getPropertyValue(name) || '').trim() || fallback;
    // เก็บ selection ของ d3 นอก state ของ Alpine (ไม่ให้ถูกห่อเป็น Proxy)
    let particles = null;

    return {
        sales: 5000,
        depth: 3,
        showRollup: true,
        autoAnimate: true,
        animating: false,
        settingsFallback: false,
        pvRate: 1,
        maxDepth: 10,
        levels: [],
        members: [],
        rollups: [],
        byLevel: [],
        rootCommission: 0,
        totalCommission: 0,

        get activeCount() { return this.members.filter(m => m.active).length; },
        get activeRate() { return this.members.length ? (this.activeCount / this.members.length * 100).toFixed(1) : '0.0'; },

        async init() {
            try {
                const res = await fetch(settingsUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (! res.ok || ! data.success) throw new Error('settings');
                const s = data.settings || {};
                let levels = s.unilevel_levels;
                if (typeof levels === 'string') { try { levels = JSON.parse(levels); } catch (e) { levels = []; } }
                this.levels = Array.isArray(levels) ? levels : [];
                this.pvRate = Number(s.global_pv_rate) || 1;
                this.maxDepth = parseInt(s.unilevel_max_depth, 10) || 10;
            } catch (e) {
                this.levels = FALLBACK_LEVELS;
                this.settingsFallback = true;
            }
            this.build();
            let t;
            window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(() => this.draw(), 200); });
        },

        /** สร้างผังไบนารีเต็มตามจำนวนชั้น (id 0 = คุณ, ลูกของ i คือ 2i+1, 2i+2) */
        build() {
            const depth = Math.min(5, Math.max(2, parseInt(this.depth, 10) || 3));
            const list = [];
            for (let level = 0, id = 0; level <= depth; level++) {
                for (let i = 0; i < 2 ** level; i++, id++) {
                    list.push({ id, level, index: i, name: id === 0 ? 'คุณ' : 'M' + id, parentId: id === 0 ? null : Math.floor((id - 1) / 2), active: true, commission: 0 });
                }
            }
            this.members = list;
            this.recalc();
        },

        pct(level) {
            const cfg = this.levels.find(l => parseInt(l.level, 10) === level) || null;
            return cfg ? (Number(cfg.percentage) || 0) : 0;
        },

        recalc() {
            const sales = Math.max(0, Number(this.sales) || 0);
            const m = this.members;
            m.forEach(x => { x.commission = 0; });
            const rollups = [];

            m.forEach(member => {
                if (member.level === 0) return;
                const pv = sales * this.pvRate;
                let anc = m[member.parentId];
                let paidLevel = 1;
                const skipped = [];
                let firstReceiver = null;
                while (anc && paidLevel <= this.maxDepth) {
                    if (anc.active) {
                        const c = pv * this.pct(paidLevel) / 100;
                        anc.commission += c;
                        if (! firstReceiver) firstReceiver = anc;
                        paidLevel++;
                    } else if (! firstReceiver) {
                        skipped.push(anc.name);
                    }
                    anc = anc.parentId === null ? null : m[anc.parentId];
                }
                if (skipped.length && firstReceiver) {
                    rollups.push({ fromId: member.id, toId: firstReceiver.id, from: member.name, skipped, to: firstReceiver.name });
                }
            });

            this.rollups = rollups;
            const root = m[0];
            this.rootCommission = root ? root.commission : 0;
            this.totalCommission = m.reduce((s, x) => s + x.commission, 0);
            // ค่าคอมของ "คุณ" แยกตามชั้นของผู้ซื้อ (ชั้นในผัง)
            const rows = [];
            const maxLevel = m.reduce((mx, x) => Math.max(mx, x.level), 0);
            for (let level = 1; level <= maxLevel; level++) {
                const inLevel = m.filter(x => x.level === level);
                rows.push({ level, total: inLevel.length, active: inLevel.filter(x => x.active).length, commission: 0 });
            }
            this.byLevel = rows;
            this.rootByTreeLevel();
            this.draw();
            if (this.autoAnimate) this.play();
        },

        /** คิดใหม่เฉพาะของ "คุณ" ว่ามาจากสมาชิกชั้นไหนในผัง */
        rootByTreeLevel() {
            const sales = Math.max(0, Number(this.sales) || 0);
            const m = this.members;
            const root = m[0];
            if (! root || ! root.active) return;
            m.forEach(member => {
                if (member.level === 0) return;
                let anc = m[member.parentId];
                let paidLevel = 1;
                while (anc && paidLevel <= this.maxDepth) {
                    if (anc.active) {
                        if (anc.id === 0) {
                            const row = this.byLevel.find(r => r.level === member.level);
                            if (row) row.commission += sales * this.pvRate * this.pct(paidLevel) / 100;
                            break;
                        }
                        paidLevel++;
                    }
                    anc = anc.parentId === null ? null : m[anc.parentId];
                }
            });
        },

        toggle(id) {
            const member = this.members[id];
            if (! member || member.level === 0) return;
            member.active = ! member.active;
            this.recalc();
        },

        preset(type) {
            this.members.forEach(x => {
                if (x.level === 0) { x.active = true; return; }
                if (type === 'realistic') x.active = Math.random() > 0.3;
                else if (type === 'rollup') x.active = x.level !== 1 || x.id % 2 === 0;
                else x.active = true;
            });
            this.recalc();
        },

        layout() {
            const box = this.$refs.canvas;
            const depth = this.members.reduce((mx, x) => Math.max(mx, x.level), 0);
            const leaves = 2 ** depth;
            const r = depth >= 4 ? 15 : 20;
            const width = Math.max(box.clientWidth || 320, leaves * (r * 2 + 10));
            const height = Math.max(320, (depth + 1) * 92 + 40);
            const levelH = (height - 40) / (depth + 1);
            this.members.forEach(x => {
                const slot = width / (2 ** x.level);
                x.x = slot * (x.index + 0.5);
                x.y = 30 + levelH * x.level + levelH / 2 - 20;
            });
            return { width, height, r };
        },

        badge(n) {
            if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
            if (n >= 1000) return (n / 1000).toFixed(n >= 10000 ? 0 : 1) + 'K';
            return Math.round(n).toString();
        },

        draw() {
            if (typeof d3 === 'undefined' || ! this.$refs.canvas) return;
            const { width, height, r } = this.layout();
            const c = { ok: css('--nvm-good', '#2f9e6a'), bad: css('--nvm-bad', '#d24b4b'), gold: css('--nv-gold-500', '#c9a24a'), line: css('--nvm-line-2', '#c8ccd6'), ink: css('--nvm-ink', '#1b2233'), paper: css('--nvm-paper', '#fffdf8'), badgeInk: css('--nv-navy-800', '#0d1b3d') };
            const m = this.members;
            const box = d3.select(this.$refs.canvas);
            box.selectAll('*').remove();
            const svg = box.append('svg').attr('xmlns', 'http://www.w3.org/2000/svg').attr('width', width).attr('height', height).attr('viewBox', `0 0 ${width} ${height}`);

            svg.append('g').selectAll('line').data(m.filter(x => x.parentId !== null)).enter().append('line')
                .attr('x1', d => m[d.parentId].x).attr('y1', d => m[d.parentId].y).attr('x2', d => d.x).attr('y2', d => d.y)
                .attr('stroke', c.line).attr('stroke-width', 2);

            if (this.showRollup) {
                svg.append('g').selectAll('path').data(this.rollups).enter().append('path')
                    .attr('class', 'nvm-ml-rollline')
                    .attr('d', d => { const a = m[d.fromId], b = m[d.toId]; const mx = (a.x + b.x) / 2 + (a.x < b.x ? -30 : 30); return `M${a.x},${a.y} Q${mx},${(a.y + b.y) / 2} ${b.x},${b.y}`; })
                    .attr('fill', 'none').attr('stroke', c.gold).attr('stroke-width', 2.5).attr('stroke-dasharray', '6 5');
            }

            const nodes = svg.append('g').selectAll('g').data(m).enter().append('g')
                .attr('transform', d => `translate(${d.x},${d.y})`)
                .attr('class', d => d.level ? 'nvm-ml-simnode' : 'nvm-ml-simnode is-root')
                .attr('tabindex', d => d.level ? 0 : null)
                .attr('role', d => d.level ? 'button' : null)
                .attr('aria-label', d => d.level ? `${d.name} ${d.active ? 'แอคทีฟ' : 'ไม่แอคทีฟ'} แตะเพื่อสลับ` : null)
                .on('click', (e, d) => this.toggle(d.id))
                .on('keydown', (e, d) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.toggle(d.id); } });

            nodes.append('circle').attr('r', d => d.level ? r : r + 8).attr('fill', d => d.active ? c.ok : c.bad).attr('stroke', c.paper).attr('stroke-width', 2);
            nodes.append('text').attr('text-anchor', 'middle').attr('dy', '.35em').attr('fill', c.paper).attr('font-size', d => d.level ? 10 : 12).attr('font-weight', 700).attr('pointer-events', 'none').text(d => d.name);

            const withPay = nodes.filter(d => d.commission > 0);
            withPay.append('rect').attr('x', r - 6).attr('y', -r - 14).attr('rx', 8).attr('width', 40).attr('height', 16).attr('fill', c.gold);
            withPay.append('text').attr('x', r + 14).attr('y', -r - 6).attr('dy', '.35em').attr('text-anchor', 'middle').attr('fill', c.badgeInk).attr('font-size', 10).attr('font-weight', 700).attr('pointer-events', 'none').text(d => this.badge(d.commission));

            particles = svg.append('g').attr('pointer-events', 'none');
        },

        /** อนุภาคเหรียญไหลจากผู้ซื้อไปยังผู้รับค่าคอมคนแรก */
        play() {
            if (this.animating || ! particles || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            this.animating = true;
            const m = this.members;
            const gold = css('--nv-gold-500', '#c9a24a');
            let delay = 0;
            m.forEach(member => {
                if (member.level === 0) return;
                let to = m[member.parentId];
                while (to && ! to.active) to = to.parentId === null ? null : m[to.parentId];
                if (! to) return;
                particles.append('circle').attr('cx', member.x).attr('cy', member.y).attr('r', 5).attr('fill', gold).attr('opacity', 0)
                    .transition().delay(delay).duration(250).attr('opacity', 1)
                    .transition().duration(800).attr('cx', to.x).attr('cy', to.y)
                    .transition().duration(200).attr('opacity', 0).remove();
                delay += Math.max(30, 600 / m.length);
            });
            setTimeout(() => { this.animating = false; }, delay + 1300);
        },

        /** บันทึกผังเป็น PNG จาก SVG โดยตรง */
        saveImage() {
            const svgEl = this.$refs.canvas.querySelector('svg');
            if (! svgEl) return;
            const w = svgEl.width.baseVal.value, h = svgEl.height.baseVal.value;
            const blob = new Blob([new XMLSerializer().serializeToString(svgEl)], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = w * 2; canvas.height = h * 2;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = css('--nvm-paper', '#fffdf8'); ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.scale(2, 2); ctx.drawImage(img, 0, 0);
                URL.revokeObjectURL(url);
                const a = document.createElement('a');
                a.download = 'mlm-scenario.png';
                a.href = canvas.toDataURL('image/png');
                a.click();
                window.showNotification?.('บันทึกรูปผังแล้ว', 'success');
            };
            img.onerror = () => { URL.revokeObjectURL(url); window.showNotification?.('บันทึกรูปไม่สำเร็จ', 'error'); };
            img.src = url;
        },

        fmt(n) { return Math.floor(Number(n) || 0).toLocaleString('th-TH'); },
    };
}
</script>
@endpush
