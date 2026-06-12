/**
 * GenealogyNexus — ผังสายงาน MLM แบบ Interactive (V3 Redesign)
 *
 * ความสามารถ:
 * - แสดงผังเป็นการ์ดสมาชิก (avatar + rank ring + PV + สถานะ)
 * - ลากย้ายโหนดได้อิสระ / แพน / ซูม (เมาส์ + ทัชสกรีน + pinch)
 * - คลิกการ์ด → เปิด detail panel / ปุ่มย่อ-ขยายสายงานใต้โหนด
 * - Re-root (โฟกัสสายงานจากคนที่เลือก) พร้อม breadcrumb
 * - เส้นเชื่อม SVG แบบ gradient + animation ไหลตามสาย
 * - Minimap มุมจอ คลิกเพื่อกระโดดตำแหน่ง
 * - Animation ทุกการเคลื่อนไหวผ่าน rAF tween (easeOutCubic)
 *
 * ใช้กับ API เดิม: /admin/mlm/members/{id}/tree-data?type=&depth=
 * ไม่พึ่ง library ภายนอก — vanilla JS ล้วน
 */
class GenealogyNexus {
    /**
     * @param {HTMLElement} container กล่องที่ใช้วาดผัง (ต้องมี position: relative)
     * @param {Object} options ตัวเลือก { treeDataUrlTemplate, treeType, depth, onSelect, onRootChange, isDark }
     */
    constructor(container, options = {}) {
        this.container = container;
        this.options = Object.assign({
            treeDataUrlTemplate: '/admin/mlm/members/:id/tree-data',
            treeType: 'binary',
            depth: 5,
            nodeWidth: 216,
            nodeHeight: 96,
            gapX: 28,
            gapY: 110,
            onSelect: null,      // callback(node) เมื่อเลือกโหนด
            onRootChange: null,  // callback(rootNode, trail) เมื่อ re-root
            onStats: null,       // callback({nodes, depth, totalPv}) หลัง render
        }, options);

        // สถานะ world transform
        this.pan = { x: 0, y: 0 };
        this.zoom = 1;
        this.targetView = null;       // เป้าหมาย tween ของ pan/zoom

        // ข้อมูลผัง
        this.root = null;             // โหนด root ปัจจุบัน (normalized)
        this.nodesById = new Map();   // id → node
        this.dragOffsets = new Map(); // id → {dx, dy} จากการลากย้ายโหนด
        this.collapsed = new Set();   // id ที่ถูกย่อ
        this.selectedId = null;
        this.trail = [];              // breadcrumb ของการ re-root [{id, name}]

        // สถานะ interaction
        this.pointerMode = null;      // 'pan' | 'node' | 'pinch'
        this.activePointers = new Map();
        this.dragNode = null;
        this.pinchStart = null;
        this._raf = null;
        this._tweens = [];

        this.buildDom();
        this.bindEvents();
        this.startLoop();
    }

    // ============================================================
    // DOM Skeleton
    // ============================================================

    buildDom() {
        this.container.classList.add('gnx-container');
        this.container.innerHTML = `
            <div class="gnx-bg"></div>
            <svg class="gnx-edges" xmlns="http://www.w3.org/2000/svg">
                <defs></defs>
                <g class="gnx-edge-group"></g>
            </svg>
            <div class="gnx-world"></div>
            <div class="gnx-loading hidden">
                <div class="gnx-orbit"><span></span><span></span><span></span></div>
                <p>กำลังโหลดผังสายงาน…</p>
            </div>
            <canvas class="gnx-minimap" width="200" height="140" title="คลิกเพื่อกระโดดตำแหน่ง"></canvas>
        `;
        this.bgEl = this.container.querySelector('.gnx-bg');
        this.svgEl = this.container.querySelector('.gnx-edges');
        this.edgeGroup = this.container.querySelector('.gnx-edge-group');
        this.defsEl = this.svgEl.querySelector('defs');
        this.worldEl = this.container.querySelector('.gnx-world');
        this.loadingEl = this.container.querySelector('.gnx-loading');
        this.minimapEl = this.container.querySelector('.gnx-minimap');
    }

    // ============================================================
    // โหลดข้อมูลจาก API
    // ============================================================

    /**
     * โหลดผังสายงานของสมาชิก แล้ว render
     *
     * @param {number} memberId
     * @param {boolean} pushTrail true = เก็บ root เดิมไว้ใน breadcrumb (ตอน drill-down)
     * @param {boolean} resetTrail false = คง trail ปัจจุบันไว้ (ตอนกระโดดกลับตาม breadcrumb)
     */
    async load(memberId, pushTrail = false, resetTrail = true) {
        this.showLoading();
        const url = this.options.treeDataUrlTemplate.replace(':id', memberId)
            + `?type=${this.options.treeType}&depth=${this.options.depth}`;

        // กัน race: กดโหลดรัวๆ ให้ยึดผลของคำขอล่าสุดเท่านั้น
        const seq = (this._loadSeq = (this._loadSeq || 0) + 1);

        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            if (seq !== this._loadSeq) return; // มีคำขอใหม่กว่าแซงไปแล้ว — ทิ้งผลนี้
            if (!json.success || !json.data) throw new Error(json.message || 'no data');

            if (pushTrail && this.root) {
                this.trail.push({ id: this.root.id, name: this.root.name });
            } else if (!pushTrail && resetTrail) {
                this.trail = [];
            }

            this.setData(json.data);

            if (typeof this.options.onRootChange === 'function') {
                this.options.onRootChange(this.root, this.trail);
            }
        } catch (err) {
            if (seq !== this._loadSeq) return;
            console.error('GenealogyNexus: โหลดข้อมูลล้มเหลว', err);
            this.showError('โหลดข้อมูลผังสายงานไม่สำเร็จ');
        } finally {
            if (seq === this._loadSeq) this.hideLoading();
        }
    }

    /** ตั้งข้อมูลผัง (tree จาก API) แล้ว layout + render */
    setData(tree) {
        this.root = this.normalize(tree, null);
        this.dragOffsets.clear();
        this.collapsed.clear();
        this.selectedId = null;
        this.nodesById.clear();
        this.indexNodes(this.root);
        this.renderAll(true);
        this.fitToScreen(true);
    }

    /**
     * แปลง tree จาก API ให้อยู่ในรูปแบบเดียว:
     * binary ใช้ left/right → children[] + ตำแหน่ง, unilevel ใช้ children[] ตรงๆ
     */
    normalize(raw, parent) {
        const node = Object.assign({}, raw);
        node.parent = parent;
        node.children = [];
        node.isGhost = false;

        if (this.options.treeType === 'binary') {
            // binary: เก็บ slot ซ้าย/ขวาเสมอ (null = ว่าง) เพื่อให้ layout ไม่เบี้ยว
            node.slots = {
                left: raw.left ? this.normalize(raw.left, node) : null,
                right: raw.right ? this.normalize(raw.right, node) : null,
            };
            if (node.slots.left) node.children.push(node.slots.left);
            if (node.slots.right) node.children.push(node.slots.right);
        } else {
            (raw.children || []).forEach((c) => node.children.push(this.normalize(c, node)));
        }

        return node;
    }

    indexNodes(node) {
        this.nodesById.set(node.id, node);
        node.children.forEach((c) => this.indexNodes(c));
    }

    // ============================================================
    // Layout — tidy tree (กว้างตาม subtree, y ตาม depth)
    // ============================================================

    /** โหนดที่มองเห็น (ตัดใต้โหนดที่ถูกย่อ) + เติม ghost slot ของ binary */
    visibleChildren(node) {
        if (this.collapsed.has(node.id)) return [];

        if (this.options.treeType === 'binary') {
            const kids = [];
            const hasAny = node.slots && (node.slots.left || node.slots.right);
            // แสดงช่องว่าง (ghost) เมื่อมีลูกอย่างน้อย 1 ฝั่ง หรือเป็น root — ให้เห็นว่าฝั่งไหนยังว่าง
            const showGhost = hasAny || node === this.root;
            if (node.slots) {
                if (node.slots.left) kids.push(node.slots.left);
                else if (showGhost) kids.push(this.makeGhost(node, 'left'));
                if (node.slots.right) kids.push(node.slots.right);
                else if (showGhost) kids.push(this.makeGhost(node, 'right'));
            }
            return kids;
        }

        return node.children;
    }

    makeGhost(parent, position) {
        // cache ghost ไว้ที่ parent — กัน object ใหม่ทุก layout (ทำให้ re-animate กระพริบ)
        parent._ghosts = parent._ghosts || {};
        if (!parent._ghosts[position]) {
            parent._ghosts[position] = {
                id: `ghost-${parent.id}-${position}`,
                isGhost: true,
                position,
                parent,
                children: [],
                name: position === 'left' ? 'ขาซ้ายว่าง' : 'ขาขวาว่าง',
            };
        }
        return parent._ghosts[position];
    }

    /** คำนวณตำแหน่ง x,y ของทุกโหนดที่มองเห็น */
    computeLayout() {
        if (!this.root) return [];

        const { nodeWidth, nodeHeight, gapX, gapY } = this.options;
        const placed = [];

        // คำนวณความกว้าง subtree ก่อน (post-order)
        const widthOf = (node) => {
            const kids = this.visibleChildren(node);
            if (!kids.length) {
                node._w = nodeWidth + gapX;
                return node._w;
            }
            node._w = Math.max(nodeWidth + gapX, kids.reduce((s, k) => s + widthOf(k), 0));
            return node._w;
        };
        widthOf(this.root);

        // วางตำแหน่ง (pre-order) — กึ่งกลางเหนือลูกๆ
        const place = (node, left, depth) => {
            const kids = this.visibleChildren(node);
            node._x = left + node._w / 2 - nodeWidth / 2;
            node._y = depth * (nodeHeight + gapY);

            // offset จากการลากย้าย
            const off = this.dragOffsets.get(node.id);
            node._fx = node._x + (off ? off.dx : 0);
            node._fy = node._y + (off ? off.dy : 0);

            placed.push(node);

            let cursor = left + (node._w - kids.reduce((s, k) => s + k._w, 0)) / 2;
            kids.forEach((k) => {
                place(k, cursor, depth + 1);
                cursor += k._w;
            });
        };
        place(this.root, 0, 0);

        return placed;
    }

    // ============================================================
    // Render — DOM โหนด + SVG เส้นเชื่อม
    // ============================================================

    /**
     * วาดทั้งผัง — โหนดเดิม tween ไปตำแหน่งใหม่, โหนดใหม่ fade-in จากตำแหน่งพ่อ
     *
     * @param {boolean} instant true = วางตำแหน่งทันทีไม่ tween (ตอนโหลดครั้งแรก)
     */
    renderAll(instant = false) {
        const placed = this.computeLayout();
        const liveIds = new Set(placed.map((n) => String(n.id)));

        // ลบโหนดที่ไม่อยู่ในผังแล้ว (fade-out)
        this.worldEl.querySelectorAll('.gnx-node').forEach((el) => {
            if (!liveIds.has(el.dataset.id)) {
                el.classList.add('gnx-leaving');
                setTimeout(() => el.remove(), 260);
            }
        });

        placed.forEach((node) => {
            let el = this.worldEl.querySelector(`.gnx-node[data-id="${CSS.escape(String(node.id))}"]`);
            const isNew = !el;

            if (isNew) {
                el = this.buildNodeEl(node);
                // โหนดใหม่โผล่จากตำแหน่งพ่อ (ดูสมูธเหมือนแตกหน่อ)
                const from = node.parent && node.parent._cur
                    ? node.parent._cur
                    : { x: node._fx, y: node._fy };
                node._cur = { x: from.x, y: from.y };
                el.style.transform = `translate(${from.x}px, ${from.y}px)`;
                el.classList.add('gnx-entering');
                this.worldEl.appendChild(el);
                requestAnimationFrame(() => el.classList.remove('gnx-entering'));
            } else {
                this.updateNodeEl(el, node);
                if (!node._cur) node._cur = { x: node._fx, y: node._fy };
            }
            node._el = el;

            if (instant) {
                node._cur = { x: node._fx, y: node._fy };
                el.style.transform = `translate(${node._fx}px, ${node._fy}px)`;
            } else if (node._cur.x !== node._fx || node._cur.y !== node._fy) {
                this.tweenNode(node);
            }
        });

        this._placed = placed;
        this.applySelectionClass();
        this.drawEdges();
        this.emitStats(placed);
    }

    emitStats(placed) {
        if (typeof this.options.onStats !== 'function') return;
        const real = placed.filter((n) => !n.isGhost);
        const maxDepth = real.reduce((m, n) => Math.max(m, n._y), 0) / (this.options.nodeHeight + this.options.gapY);
        const totalPv = real.reduce((s, n) => s + (parseFloat(n.total_pv) || 0), 0);
        this.options.onStats({ nodes: real.length, depth: Math.round(maxDepth) + 1, totalPv });
    }

    /** สร้าง DOM การ์ดสมาชิก */
    buildNodeEl(node) {
        const el = document.createElement('div');
        el.className = 'gnx-node' + (node.isGhost ? ' gnx-ghost' : '');
        el.dataset.id = String(node.id);
        el.style.width = this.options.nodeWidth + 'px';
        this.updateNodeEl(el, node);
        return el;
    }

    /** อัพเดทเนื้อหาการ์ด (เรียกซ้ำได้ ไม่ rebuild ทั้งก้อน) */
    updateNodeEl(el, node) {
        if (node.isGhost) {
            const side = node.position === 'left' ? 'ซ้าย' : 'ขวา';
            el.innerHTML = `
                <div class="gnx-ghost-inner">
                    <span class="gnx-ghost-plus">+</span>
                    <span>ขา${side}ว่าง</span>
                </div>`;
            return;
        }

        const rankColor = node.rank_color || '#10b981';
        const initial = (node.name || '?').trim().charAt(0).toUpperCase();
        const avatar = node.avatar_url
            ? `<img src="${this.esc(node.avatar_url)}" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'gnx-initial',textContent:'${this.esc(initial)}'}))">`
            : `<span class="gnx-initial">${this.esc(initial)}</span>`;

        const status = node.retention_status || node.status || 'inactive';
        const statusClass = status === 'active' ? 'is-active' : (status === 'grace_period' ? 'is-grace' : 'is-inactive');

        // PV chips: binary = ขาซ้าย/ขวา, unilevel = PV เดือนนี้ + ทีม
        let chips;
        if (this.options.treeType === 'binary') {
            chips = `
                <span class="gnx-chip gnx-chip-l" title="PV ขาซ้าย">L ${this.fmt(node.left_leg_pv)}</span>
                <span class="gnx-chip gnx-chip-r" title="PV ขาขวา">R ${this.fmt(node.right_leg_pv)}</span>`;
        } else {
            chips = `
                <span class="gnx-chip gnx-chip-l" title="PV เดือนนี้">PV ${this.fmt(node.monthly_pv)}</span>
                <span class="gnx-chip gnx-chip-r" title="PV ทีมสะสม">ทีม ${this.fmt(node.total_team_pv)}</span>`;
        }

        const hiddenKids = this.countSubtree(node) - 1;
        const isCollapsed = this.collapsed.has(node.id);
        const hasKids = (this.options.treeType === 'binary')
            ? !!(node.slots && (node.slots.left || node.slots.right))
            : node.children.length > 0;

        const toggleBtn = hasKids
            ? `<button class="gnx-toggle ${isCollapsed ? 'is-collapsed' : ''}" data-action="toggle" title="${isCollapsed ? 'ขยายสายงาน' : 'ย่อสายงาน'}">
                   ${isCollapsed ? `<span class="gnx-toggle-count">${hiddenKids}</span>` : '<span class="gnx-toggle-minus">−</span>'}
               </button>`
            : '';

        el.innerHTML = `
            <div class="gnx-card" style="--rank: ${this.esc(rankColor)}">
                <span class="gnx-status ${statusClass}"></span>
                <div class="gnx-avatar">${avatar}</div>
                <div class="gnx-info">
                    <div class="gnx-name" title="${this.esc(node.name || '')}">${this.esc(node.name || 'ไม่ระบุชื่อ')}</div>
                    <div class="gnx-code">${this.esc(node.member_code || '')}${node.rank_name ? ` · <i style="color:${this.esc(rankColor)}">${this.esc(node.rank_name)}</i>` : ''}</div>
                    <div class="gnx-chips">${chips}</div>
                </div>
                ${toggleBtn}
            </div>`;
    }

    /** นับจำนวนโหนดจริงใน subtree (ไม่รวม ghost) */
    countSubtree(node) {
        let n = node.isGhost ? 0 : 1;
        node.children.forEach((c) => { n += this.countSubtree(c); });
        return n;
    }

    // ============================================================
    // เส้นเชื่อม (SVG bezier + gradient)
    // ============================================================

    drawEdges() {
        if (!this._placed) return;
        const { nodeWidth, nodeHeight } = this.options;
        const frag = [];

        this._placed.forEach((node) => {
            if (!node.parent || !node.parent._cur) return;
            const p = node.parent._cur, c = node._cur || { x: node._fx, y: node._fy };
            const x1 = p.x + nodeWidth / 2, y1 = p.y + nodeHeight;
            const x2 = c.x + nodeWidth / 2, y2 = c.y;
            const my = (y1 + y2) / 2;
            const cls = node.isGhost ? 'gnx-edge gnx-edge-ghost'
                : (this.selectedId && (node.id === this.selectedId || node.parent.id === this.selectedId)
                    ? 'gnx-edge gnx-edge-hot' : 'gnx-edge');
            frag.push(`<path class="${cls}" d="M ${x1} ${y1} C ${x1} ${my}, ${x2} ${my}, ${x2} ${y2}"/>`);
        });

        this.edgeGroup.innerHTML = frag.join('');
    }

    // ============================================================
    // Animation loop + tween
    // ============================================================

    startLoop() {
        const step = () => {
            const now = performance.now();
            let dirty = false;

            // tween ตำแหน่งโหนด
            this._tweens = this._tweens.filter((t) => {
                const k = Math.min(1, (now - t.start) / t.dur);
                const e = 1 - Math.pow(1 - k, 3); // easeOutCubic
                t.node._cur.x = t.from.x + (t.to.x - t.from.x) * e;
                t.node._cur.y = t.from.y + (t.to.y - t.from.y) * e;
                if (t.node._el) {
                    t.node._el.style.transform = `translate(${t.node._cur.x}px, ${t.node._cur.y}px)`;
                }
                dirty = true;
                return k < 1;
            });

            // tween มุมมอง (pan/zoom)
            if (this.targetView) {
                const t = this.targetView;
                const k = Math.min(1, (now - t.start) / t.dur);
                const e = 1 - Math.pow(1 - k, 3);
                this.pan.x = t.from.x + (t.to.x - t.from.x) * e;
                this.pan.y = t.from.y + (t.to.y - t.from.y) * e;
                this.zoom = t.from.z + (t.to.z - t.from.z) * e;
                this.applyTransform();
                if (k >= 1) this.targetView = null;
                dirty = true;
            }

            if (dirty) {
                this.drawEdges();
                this.drawMinimap();
            }
            this._raf = requestAnimationFrame(step);
        };
        this._raf = requestAnimationFrame(step);
    }

    tweenNode(node, dur = 420) {
        this._tweens = this._tweens.filter((t) => t.node !== node);
        this._tweens.push({
            node,
            from: { x: node._cur.x, y: node._cur.y },
            to: { x: node._fx, y: node._fy },
            start: performance.now(),
            dur,
        });
    }

    animateView(x, y, z, dur = 480) {
        this.targetView = {
            from: { x: this.pan.x, y: this.pan.y, z: this.zoom },
            to: { x, y, z },
            start: performance.now(),
            dur,
        };
    }

    applyTransform() {
        const t = `translate(${this.pan.x}px, ${this.pan.y}px) scale(${this.zoom})`;
        this.worldEl.style.transform = t;
        this.edgeGroup.setAttribute('transform',
            `translate(${this.pan.x}, ${this.pan.y}) scale(${this.zoom})`);
        // เลื่อนลาย grid พื้นหลังตามแพนเบาๆ (parallax)
        this.bgEl.style.backgroundPosition = `${this.pan.x * 0.3}px ${this.pan.y * 0.3}px`;
    }

    /** จัดผังให้พอดีจอ */
    fitToScreen(instant = false) {
        if (!this._placed || !this._placed.length) return;
        const rect = this.container.getBoundingClientRect();
        const { nodeWidth, nodeHeight } = this.options;

        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        this._placed.forEach((n) => {
            minX = Math.min(minX, n._fx);
            minY = Math.min(minY, n._fy);
            maxX = Math.max(maxX, n._fx + nodeWidth);
            maxY = Math.max(maxY, n._fy + nodeHeight);
        });

        const pad = 60;
        const z = Math.min(1.1, Math.min(
            (rect.width - pad * 2) / Math.max(1, maxX - minX),
            (rect.height - pad * 2) / Math.max(1, maxY - minY)
        ));
        const x = (rect.width - (maxX - minX) * z) / 2 - minX * z;
        const y = Math.min(pad, (rect.height - (maxY - minY) * z) / 2) - minY * z + 20;

        if (instant) {
            this.pan = { x, y };
            this.zoom = z;
            this.applyTransform();
            this.drawMinimap();
        } else {
            this.animateView(x, y, z);
        }
    }

    /** ซูมไปยังโหนด */
    focusNode(node, zoom = 1) {
        const rect = this.container.getBoundingClientRect();
        const cx = node._fx + this.options.nodeWidth / 2;
        const cy = node._fy + this.options.nodeHeight / 2;
        this.animateView(rect.width / 2 - cx * zoom, rect.height / 2.6 - cy * zoom, zoom);
    }

    // ============================================================
    // Interaction (pointer: ลากโหนด / แพน / pinch zoom / wheel)
    // ============================================================

    bindEvents() {
        const el = this.container;

        el.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        el.addEventListener('pointermove', (e) => this.onPointerMove(e));
        el.addEventListener('pointerup', (e) => this.onPointerUp(e));
        el.addEventListener('pointercancel', (e) => this.onPointerUp(e));

        // ซูมด้วยล้อเมาส์ — ซูมเข้าหาตำแหน่ง cursor
        el.addEventListener('wheel', (e) => {
            e.preventDefault();
            const rect = el.getBoundingClientRect();
            const mx = e.clientX - rect.left, my = e.clientY - rect.top;
            const factor = e.deltaY < 0 ? 1.12 : 0.89;
            const z = Math.max(0.18, Math.min(2.2, this.zoom * factor));
            // คงจุดใต้เมาส์ไว้ที่เดิม
            this.pan.x = mx - ((mx - this.pan.x) / this.zoom) * z;
            this.pan.y = my - ((my - this.pan.y) / this.zoom) * z;
            this.zoom = z;
            this.targetView = null;
            this.applyTransform();
            this.drawEdges();
            this.drawMinimap();
        }, { passive: false });

        // ดับเบิลคลิกพื้นหลัง = fit จอ
        el.addEventListener('dblclick', (e) => {
            if (!e.target.closest('.gnx-node')) this.fitToScreen();
        });

        // คลิก minimap = กระโดด
        this.minimapEl.addEventListener('pointerdown', (e) => {
            e.stopPropagation();
            this.jumpFromMinimap(e);
        });

        // กันรูปภาพ/ข้อความถูกลากแบบ native
        el.addEventListener('dragstart', (e) => e.preventDefault());
    }

    onPointerDown(e) {
        if (e.target.closest('.gnx-minimap')) return;
        this.container.setPointerCapture(e.pointerId);
        this.activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

        if (this.activePointers.size === 2) {
            // เริ่ม pinch
            this.pointerMode = 'pinch';
            const pts = [...this.activePointers.values()];
            this.pinchStart = {
                dist: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y),
                zoom: this.zoom,
                center: { x: (pts[0].x + pts[1].x) / 2, y: (pts[0].y + pts[1].y) / 2 },
                pan: { ...this.pan },
            };
            this.dragNode = null;
            return;
        }

        const nodeEl = e.target.closest('.gnx-node');
        const toggle = e.target.closest('[data-action="toggle"]');

        if (toggle && nodeEl) {
            // ปุ่มย่อ/ขยาย — จัดการตอน pointerup เพื่อไม่ชนกับการลาก
            this.pointerMode = 'toggle';
            this._toggleTarget = nodeEl.dataset.id;
            return;
        }

        if (nodeEl && !nodeEl.classList.contains('gnx-ghost')) {
            const node = this.nodesById.get(this.parseId(nodeEl.dataset.id));
            if (node) {
                this.pointerMode = 'node';
                this.dragNode = node;
                this._dragMoved = false;
                this._dragStart = { x: e.clientX, y: e.clientY, nx: node._cur.x, ny: node._cur.y };
                nodeEl.classList.add('gnx-dragging');
                return;
            }
        }

        // พื้นหลัง = แพน
        this.pointerMode = 'pan';
        this._panStart = { x: e.clientX, y: e.clientY, px: this.pan.x, py: this.pan.y };
        this.container.classList.add('gnx-panning');
    }

    onPointerMove(e) {
        if (!this.activePointers.has(e.pointerId)) return;
        this.activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

        if (this.pointerMode === 'pinch' && this.activePointers.size === 2 && this.pinchStart) {
            const pts = [...this.activePointers.values()];
            const dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            const z = Math.max(0.18, Math.min(2.2, this.pinchStart.zoom * (dist / this.pinchStart.dist)));
            const c = this.pinchStart.center;
            const rect = this.container.getBoundingClientRect();
            const mx = c.x - rect.left, my = c.y - rect.top;
            this.pan.x = mx - ((mx - this.pinchStart.pan.x) / this.pinchStart.zoom) * z;
            this.pan.y = my - ((my - this.pinchStart.pan.y) / this.pinchStart.zoom) * z;
            this.zoom = z;
            this.applyTransform();
            this.drawEdges();
            return;
        }

        if (this.pointerMode === 'node' && this.dragNode) {
            const dx = (e.clientX - this._dragStart.x) / this.zoom;
            const dy = (e.clientY - this._dragStart.y) / this.zoom;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) this._dragMoved = true;

            this.dragNode._cur.x = this._dragStart.nx + dx;
            this.dragNode._cur.y = this._dragStart.ny + dy;
            if (this.dragNode._el) {
                this.dragNode._el.style.transform =
                    `translate(${this.dragNode._cur.x}px, ${this.dragNode._cur.y}px)`;
            }
            this.drawEdges();
            return;
        }

        if (this.pointerMode === 'pan' && this._panStart) {
            this.pan.x = this._panStart.px + (e.clientX - this._panStart.x);
            this.pan.y = this._panStart.py + (e.clientY - this._panStart.y);
            this.targetView = null;
            this.applyTransform();
            this.drawMinimap();
        }
    }

    onPointerUp(e) {
        this.activePointers.delete(e.pointerId);
        this.container.classList.remove('gnx-panning');

        if (this.pointerMode === 'toggle' && this._toggleTarget != null) {
            this.toggleCollapse(this.parseId(this._toggleTarget));
            this._toggleTarget = null;
        } else if (this.pointerMode === 'node' && this.dragNode) {
            const node = this.dragNode;
            if (node._el) node._el.classList.remove('gnx-dragging');

            if (this._dragMoved) {
                // บันทึก offset จากตำแหน่ง layout (ย้ายโหนดถาวรใน session)
                this.dragOffsets.set(node.id, {
                    dx: node._cur.x - node._x,
                    dy: node._cur.y - node._y,
                });
                node._fx = node._cur.x;
                node._fy = node._cur.y;
            } else {
                // คลิกเฉยๆ = เลือกโหนด
                this.selectNode(node);
            }
            this.dragNode = null;
        }

        if (this.activePointers.size < 2) this.pinchStart = null;
        if (this.activePointers.size === 0) this.pointerMode = null;
    }

    parseId(v) {
        return /^\d+$/.test(v) ? parseInt(v, 10) : v;
    }

    // ============================================================
    // คำสั่งจาก UI ภายนอก
    // ============================================================

    selectNode(node) {
        this.selectedId = node.id;
        this.applySelectionClass();
        this.drawEdges();
        if (typeof this.options.onSelect === 'function') this.options.onSelect(node);
    }

    applySelectionClass() {
        this.worldEl.querySelectorAll('.gnx-node').forEach((el) => {
            el.classList.toggle('gnx-selected', el.dataset.id === String(this.selectedId));
        });
    }

    toggleCollapse(id) {
        if (this.collapsed.has(id)) this.collapsed.delete(id);
        else this.collapsed.add(id);
        this.renderAll();
    }

    /** ล้างตำแหน่งที่ลากย้ายทั้งหมด กลับ layout อัตโนมัติ */
    resetPositions() {
        this.dragOffsets.clear();
        this.renderAll();
        this.fitToScreen();
    }

    zoomBy(factor) {
        const rect = this.container.getBoundingClientRect();
        const mx = rect.width / 2, my = rect.height / 2;
        const z = Math.max(0.18, Math.min(2.2, this.zoom * factor));
        const x = mx - ((mx - this.pan.x) / this.zoom) * z;
        const y = my - ((my - this.pan.y) / this.zoom) * z;
        this.animateView(x, y, z, 260);
    }

    setTreeType(type) {
        this.options.treeType = type;
    }

    setDepth(depth) {
        this.options.depth = depth;
    }

    // ============================================================
    // Minimap
    // ============================================================

    drawMinimap() {
        if (!this._placed || !this._placed.length) return;
        const ctx = this.minimapEl.getContext('2d');
        const W = this.minimapEl.width, H = this.minimapEl.height;
        ctx.clearRect(0, 0, W, H);

        const { nodeWidth, nodeHeight } = this.options;
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        this._placed.forEach((n) => {
            const c = n._cur || { x: n._fx, y: n._fy };
            minX = Math.min(minX, c.x); minY = Math.min(minY, c.y);
            maxX = Math.max(maxX, c.x + nodeWidth); maxY = Math.max(maxY, c.y + nodeHeight);
        });
        const pad = 10;
        const s = Math.min((W - pad * 2) / Math.max(1, maxX - minX), (H - pad * 2) / Math.max(1, maxY - minY));
        this._miniScale = { s, minX, minY, pad };

        // จุดโหนด
        this._placed.forEach((n) => {
            const c = n._cur || { x: n._fx, y: n._fy };
            const x = pad + (c.x - minX) * s, y = pad + (c.y - minY) * s;
            ctx.fillStyle = n.isGhost ? 'rgba(148,163,184,.25)'
                : (n.id === this.selectedId ? '#34d399' : (n.rank_color || '#64748b'));
            ctx.beginPath();
            // รัศมีจุดสเกลตามขนาดผัง แต่ไม่เล็กกว่า 2px / ใหญ่กว่า 5px
            const r = Math.max(2, Math.min(5, nodeWidth * s / 4));
            ctx.arc(x + nodeWidth * s / 2, y + nodeHeight * s / 2, r, 0, Math.PI * 2);
            ctx.fill();
        });

        // กรอบ viewport ปัจจุบัน
        const rect = this.container.getBoundingClientRect();
        const vx = pad + ((-this.pan.x / this.zoom) - minX) * s;
        const vy = pad + ((-this.pan.y / this.zoom) - minY) * s;
        const vw = (rect.width / this.zoom) * s;
        const vh = (rect.height / this.zoom) * s;
        ctx.strokeStyle = 'rgba(52,211,153,.9)';
        ctx.lineWidth = 1.5;
        ctx.strokeRect(vx, vy, vw, vh);
    }

    jumpFromMinimap(e) {
        if (!this._miniScale) return;
        const rect = this.minimapEl.getBoundingClientRect();
        const { s, minX, minY, pad } = this._miniScale;
        // จุดที่คลิกใน world coords
        const wx = (e.clientX - rect.left - pad) / s + minX;
        const wy = (e.clientY - rect.top - pad) / s + minY;
        const crect = this.container.getBoundingClientRect();
        this.animateView(crect.width / 2 - wx * this.zoom, crect.height / 2 - wy * this.zoom, this.zoom, 320);
    }

    // ============================================================
    // Utilities
    // ============================================================

    showLoading() { this.loadingEl.classList.remove('hidden'); }

    hideLoading() { this.loadingEl.classList.add('hidden'); }

    showError(msg) {
        this.loadingEl.classList.remove('hidden');
        this.loadingEl.innerHTML = `<p class="gnx-error">⚠️ ${this.esc(msg)}</p>`;
        setTimeout(() => {
            this.loadingEl.classList.add('hidden');
            this.loadingEl.innerHTML = `
                <div class="gnx-orbit"><span></span><span></span><span></span></div>
                <p>กำลังโหลดผังสายงาน…</p>`;
        }, 2600);
    }

    fmt(v) {
        const n = parseFloat(v) || 0;
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n % 1 === 0 ? n.toLocaleString() : n.toFixed(1);
    }

    esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    destroy() {
        if (this._raf) cancelAnimationFrame(this._raf);
        this.container.innerHTML = '';
    }
}

window.GenealogyNexus = GenealogyNexus;
