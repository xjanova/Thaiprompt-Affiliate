@extends('layouts.user-v4')

@section('title', 'จำลองสถานการณ์ MLM')

@php
    // ── หา route สำหรับโหลดตั้งค่า MLM (ป้องกัน route หาย → หน้าไม่พัง) ──
    // ของเดิมเรียก admin.mlm.settings.get-settings ที่ไม่มีอยู่จริง + เป็น route ฝั่ง admin
    // หน้านี้เป็นของลูกค้า → ใช้ user.mlm.settings (read-only, คืน JSON เดียวกัน { settings })
    // fallback: admin.mlm.settings.get ถ้ามีสิทธิ์เข้าถึง
    $mlmSettingsUrl = \Illuminate\Support\Facades\Route::has('user.mlm.settings')
        ? route('user.mlm.settings')
        : (\Illuminate\Support\Facades\Route::has('admin.mlm.settings.get')
            ? route('admin.mlm.settings.get')
            : '');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:17px; font-size:24px;"><i class="fas fa-dice" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">🎯 จำลองสถานการณ์ MLM</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ทดสอบสถานการณ์ต่างๆ และดูการไหลของรายได้แบบเรียลไทม์</div>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('user.mlm.genealogy'))
                <a href="{{ route('user.mlm.genealogy') }}" class="tp-icon-btn no-print" title="กลับไปผังทีม"><i class="fas fa-arrow-left"></i></a>
            @endif
        </div>
    </div>

    {{-- ── เลย์เอาต์ 3 คอลัมน์ (ควบคุม / ผังทีม / สรุป) ────────── --}}
    <div style="display:grid; grid-template-columns:1fr; gap:18px;" class="tp-sim-grid">

        {{-- ════ ซ้าย: แผงควบคุม ════ --}}
        <div style="display:flex; flex-direction:column; gap:18px;" class="no-print">

            {{-- สถานการณ์ตัวอย่าง --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">📋 สถานการณ์ตัวอย่าง</div>
                <div style="display:flex; flex-direction:column; gap:9px;">
                    <button type="button" onclick="loadPreset('perfect')" class="tp-btn" style="width:100%; justify-content:flex-start; gap:8px; color:#5aa07e; background:color-mix(in srgb, #5aa07e 14%, transparent);">✨ ทุกคนแอคทีฟ (100%)</button>
                    <button type="button" onclick="loadPreset('realistic')" class="tp-btn" style="width:100%; justify-content:flex-start; gap:8px; color:#5689b8; background:color-mix(in srgb, #5689b8 14%, transparent);">📊 สมจริง (70% แอคทีฟ)</button>
                    <button type="button" onclick="loadPreset('rollup')" class="tp-btn" style="width:100%; justify-content:flex-start; gap:8px; color:var(--deep1); background:color-mix(in srgb, var(--accent1) 16%, transparent);">🔄 โรลอัพ Demo</button>
                    <button type="button" onclick="loadPreset('custom')" class="tp-btn" style="width:100%; justify-content:flex-start; gap:8px; color:var(--ink2); background:color-mix(in srgb, var(--ink2) 13%, transparent);">⚙️ กำหนดเอง</button>
                </div>
            </div>

            {{-- ตั้งค่า --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">⚙️ ตั้งค่า</div>

                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div>
                        <label for="perPersonSales" style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">💰 ยอดขายต่อคน (บาท)</label>
                        <input type="number" id="perPersonSales" value="5000" step="1000" min="0"
                               onchange="recalculate()" class="tp-input" style="width:100%;">
                    </div>

                    <div>
                        <label for="treeDepth" style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">📊 จำนวนชั้น</label>
                        <select id="treeDepth" onchange="generateTree()" class="tp-input" style="width:100%;">
                            <option value="2">2 ชั้น (7 คน)</option>
                            <option value="3" selected>3 ชั้น (15 คน)</option>
                            <option value="4">4 ชั้น (31 คน)</option>
                            <option value="5">5 ชั้น (63 คน)</option>
                        </select>
                    </div>

                    <label style="display:flex; align-items:center; gap:9px; cursor:pointer;">
                        <input type="checkbox" id="showRollup" onchange="toggleRollupView()" checked style="width:18px; height:18px; accent-color:var(--accent1);">
                        <span style="font-size:12.5px; font-weight:700; color:var(--ink);">แสดงเส้น Rollup</span>
                    </label>

                    <label style="display:flex; align-items:center; gap:9px; cursor:pointer;">
                        <input type="checkbox" id="animateFlow" onchange="toggleAnimation()" checked style="width:18px; height:18px; accent-color:var(--accent1);">
                        <span style="font-size:12.5px; font-weight:700; color:var(--ink);">แอนิเมชั่น</span>
                    </label>
                </div>

                <div style="margin-top:16px; padding-top:16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <button type="button" onclick="playAnimation()" class="tp-btn tp-btn-primary" style="width:100%;">▶️ เล่นแอนิเมชั่น</button>
                </div>
            </div>

            {{-- คำอธิบาย (Legend) --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">📌 คำอธิบาย</div>
                <div style="display:flex; flex-direction:column; gap:11px; font-size:13px;">
                    <div style="display:flex; align-items:center; gap:9px;">
                        <span style="width:22px; height:22px; border-radius:50%; background:#5aa07e; box-shadow:0 0 0 2px color-mix(in srgb, #5aa07e 55%, #000); flex:none;"></span>
                        <span>แอคทีฟ (ได้รับรายได้)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:9px;">
                        <span style="width:22px; height:22px; border-radius:50%; background:#d9534f; box-shadow:0 0 0 2px color-mix(in srgb, #d9534f 55%, #000); flex:none;"></span>
                        <span>ไม่แอคทีฟ (รายได้โรลอัพ)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:9px;">
                        <span style="width:44px; height:3px; border-radius:2px; background:var(--accent1); flex:none;"></span>
                        <span>เส้นทางปกติ</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:9px;">
                        <span style="width:44px; height:0; border-top:3px dashed #e0a52e; flex:none;"></span>
                        <span>เส้นทาง Rollup</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════ กลาง: ผังโครงสร้างทีม (D3 viz — เก็บไลบรารีไว้) ════ --}}
        <div>
            <div class="tp-card" style="padding:18px; position:relative;">
                <div class="tp-section-h" style="margin-bottom:14px; text-align:center;">🌳 ผังโครงสร้างทีม (คลิกเพื่อเปลี่ยนสถานะ)</div>

                {{-- Tree Canvas (เรนเดอร์โดย D3.js) --}}
                <div id="tree-container" style="position:relative; border-radius:16px; box-shadow:var(--inset-sm); padding:16px; overflow:auto; min-height:600px; background:color-mix(in srgb, var(--accent1) 6%, transparent);">
                    {{-- Tree จะถูกเรนเดอร์ที่นี่ด้วย JavaScript --}}
                </div>

                {{-- Commission Flow Overlay --}}
                <svg id="commission-flow" style="position:absolute; inset:0; pointer-events:none; z-index:10;">
                    <defs>
                        <linearGradient id="flowGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#e8c468;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#c79a3a;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
        </div>

        {{-- ════ ขวา: สรุปผล ════ --}}
        <div style="display:flex; flex-direction:column; gap:18px;">

            {{-- สรุปรายได้รวม --}}
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div style="padding:20px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
                    <div class="tp-section-h" style="margin-bottom:14px;">💰 สรุปรายได้</div>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="padding:13px 15px; border-radius:14px; box-shadow:var(--inset-sm);">
                            <div style="font-size:12px; color:var(--ink2); margin-bottom:3px;">รายได้รวมทั้งหมด</div>
                            <div class="tp-num" style="font-size:30px; font-weight:800; color:var(--deep1); line-height:1.1;">฿<span id="total-commission">0</span></div>
                        </div>
                        <div style="padding:13px 15px; border-radius:14px; box-shadow:var(--inset-sm);">
                            <div style="font-size:12px; color:var(--ink2); margin-bottom:3px;">จำนวนคนแอคทีฟ</div>
                            <div class="tp-num" style="font-size:22px; font-weight:800;"><span id="active-count">0</span>/<span id="total-count">0</span></div>
                        </div>
                        <div style="padding:13px 15px; border-radius:14px; box-shadow:var(--inset-sm);">
                            <div style="font-size:12px; color:var(--ink2); margin-bottom:3px;">อัตราแอคทีฟ</div>
                            <div class="tp-num" style="font-size:22px; font-weight:800;"><span id="active-rate">0</span>%</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- แยกตามชั้น --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">📊 แยกตามชั้น</div>
                <div id="level-breakdown" style="display:flex; flex-direction:column; gap:11px;">
                    {{-- เติมโดย JavaScript --}}
                </div>
            </div>

            {{-- รายละเอียด Rollup --}}
            <div id="rollup-details" class="tp-card" style="padding:18px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
                <div class="tp-section-h" style="margin-bottom:14px; color:#e0a52e;">🔄 รายละเอียด Rollup</div>
                <div id="rollup-list" style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                    <p style="color:var(--ink2); margin:0;">คลิก member ที่ไม่แอคทีฟเพื่อดู rollup path</p>
                </div>
            </div>

            {{-- แชร์/บันทึก --}}
            <div class="tp-card no-print" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:14px;">📤 แชร์/บันทึก</div>
                <div style="display:flex; flex-direction:column; gap:9px;">
                    <button type="button" onclick="captureScenario()" class="tp-btn" style="width:100%; color:#5689b8; background:color-mix(in srgb, #5689b8 14%, transparent);">📸 บันทึกรูปภาพ</button>
                    <button type="button" onclick="shareScenario()" class="tp-btn" style="width:100%; color:#5aa07e; background:color-mix(in srgb, #5aa07e 14%, transparent);">🔗 คัดลอกลิงก์</button>
                    <button type="button" onclick="printScenario()" class="tp-btn" style="width:100%; color:var(--ink2); background:color-mix(in srgb, var(--ink2) 13%, transparent);">🖨️ พิมพ์</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- สไตล์เฉพาะ D3 tree viz (ไลบรารีกราฟจริง) + เลย์เอาต์ 3 คอลัมน์ + print --}}
<style>
    @media (min-width: 1024px) {
        .tp-sim-grid { grid-template-columns: 1fr 2fr 1fr; }
    }
    @media print {
        .no-print { display: none !important; }
    }

    /* โหนดสมาชิกใน D3 tree */
    .member-node {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .member-node:hover {
        transform: scale(1.1);
        filter: drop-shadow(0 0 10px color-mix(in srgb, var(--accent1) 55%, transparent));
    }
    .member-node.active {
        fill: #5aa07e;
        stroke: #3f7a5d;
    }
    .member-node.inactive {
        fill: #d9534f;
        stroke: #b13c36;
    }

    /* เส้นเชื่อมปกติ + เส้น Rollup */
    .connection-line {
        stroke: var(--accent1);
        stroke-width: 2;
        fill: none;
    }
    .rollup-line {
        stroke: #e0a52e;
        stroke-width: 3;
        stroke-dasharray: 5, 5;
        fill: none;
        animation: dash 1s linear infinite;
    }
    @keyframes dash {
        to { stroke-dashoffset: -10; }
    }

    /* อนุภาคแสดงการไหลของคอมมิชชั่น */
    .commission-particle {
        animation: flow 2s ease-in-out forwards;
    }
    @keyframes flow {
        0%   { opacity: 0; transform: scale(0); }
        50%  { opacity: 1; transform: scale(1); }
        100% { opacity: 0; transform: scale(0); }
    }
</style>

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
// ── URL โหลดตั้งค่า MLM (ส่งจาก PHP — ว่างถ้า route ไม่มี → ใช้ค่า default) ──
const MLM_SETTINGS_URL = @json($mlmSettingsUrl);

let treeData = {
    settings: {},
    members: [],
    connections: [],
    rollupPaths: []
};

let selectedMember = null;
let animationRunning = false;

// โหลดตั้งค่า MLM
async function loadSettings() {
    if (!MLM_SETTINGS_URL) {
        generateTree(); // ไม่มี route → ใช้ค่า default
        return;
    }
    try {
        const response = await fetch(MLM_SETTINGS_URL);
        const data = await response.json();
        treeData.settings = data.settings;
        generateTree();
    } catch (error) {
        console.error('Failed to load settings:', error);
        generateTree(); // Generate with defaults
    }
}

// สร้างโครงสร้าง tree
function generateTree() {
    const depth = parseInt(document.getElementById('treeDepth').value);
    treeData.members = [];
    treeData.connections = [];

    // YOU (root)
    treeData.members.push({
        id: 0,
        level: 0,
        name: 'คุณ',
        active: true,
        sales: 0,
        commission: 0,
        x: 0,
        y: 0
    });

    let memberId = 1;

    // สร้าง member สำหรับแต่ละชั้น
    for (let level = 1; level <= depth; level++) {
        const membersInLevel = Math.pow(2, level);

        for (let i = 0; i < membersInLevel; i++) {
            const parentId = Math.floor((memberId - 1) / 2);

            treeData.members.push({
                id: memberId,
                level: level,
                name: `M${memberId}`,
                active: true,
                sales: parseFloat(document.getElementById('perPersonSales').value),
                commission: 0,
                parentId: parentId,
                x: 0,
                y: 0
            });

            // สร้างเส้นเชื่อม
            treeData.connections.push({
                from: parentId,
                to: memberId,
                type: 'normal'
            });

            memberId++;
        }
    }

    renderTree();
    recalculate();
}

// เรนเดอร์ tree visualization
function renderTree() {
    const container = document.getElementById('tree-container');
    const width = container.clientWidth;
    const height = 600;

    // ล้างของเดิม
    container.innerHTML = '';

    // สร้าง SVG
    const svg = d3.select('#tree-container')
        .append('svg')
        .attr('width', width)
        .attr('height', height);

    // คำนวณตำแหน่ง
    const depth = parseInt(document.getElementById('treeDepth').value);
    const levelHeight = height / (depth + 2);

    treeData.members.forEach(member => {
        const membersInLevel = Math.pow(2, member.level);
        const levelWidth = width / (membersInLevel + 1);
        const position = treeData.members.filter(m => m.level === member.level).indexOf(member);

        member.x = levelWidth * (position + 1);
        member.y = levelHeight * (member.level + 1);
    });

    // วาดเส้นเชื่อม
    svg.selectAll('.connection')
        .data(treeData.connections)
        .enter()
        .append('line')
        .attr('class', d => d.type === 'rollup' ? 'rollup-line' : 'connection-line')
        .attr('x1', d => treeData.members[d.from].x)
        .attr('y1', d => treeData.members[d.from].y)
        .attr('x2', d => treeData.members[d.to].x)
        .attr('y2', d => treeData.members[d.to].y);

    // วาด member
    const nodes = svg.selectAll('.member-group')
        .data(treeData.members)
        .enter()
        .append('g')
        .attr('class', 'member-group')
        .attr('transform', d => `translate(${d.x},${d.y})`);

    // วงกลม
    nodes.append('circle')
        .attr('class', d => `member-node ${d.active ? 'active' : 'inactive'}`)
        .attr('r', d => d.level === 0 ? 30 : 20)
        .on('click', function(event, d) {
            if (d.level > 0) {
                d.active = !d.active;
                recalculate();
                renderTree();
            }
        });

    // ป้ายชื่อ
    nodes.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '.35em')
        .style('font-size', '11px')
        .style('font-weight', '700')
        .style('fill', 'white')
        .style('pointer-events', 'none')
        .text(d => d.name);

    // ป้ายคอมมิชชั่น
    nodes.append('circle')
        .attr('cx', 25)
        .attr('cy', -15)
        .attr('r', 15)
        .style('fill', '#e0a52e')
        .style('stroke', '#a87a1d')
        .attr('stroke-width', 2)
        .style('display', d => d.commission > 0 ? 'block' : 'none');

    nodes.append('text')
        .attr('x', 25)
        .attr('y', -15)
        .attr('text-anchor', 'middle')
        .attr('dy', '.35em')
        .style('font-size', '11px')
        .style('font-weight', '700')
        .style('fill', '#5a3e0c')
        .style('pointer-events', 'none')
        .style('display', d => d.commission > 0 ? 'block' : 'none')
        .text(d => Math.floor(d.commission / 1000) + 'K');
}

// คำนวณคอมมิชชั่นและ rollup
function recalculate() {
    const settings = treeData.settings;
    const unilevelLevels = settings.unilevel_levels || [];
    const unilevelMaxDepth = parseInt(settings.unilevel_max_depth || 10);

    // รีเซ็ตคอมมิชชั่น
    treeData.members.forEach(m => m.commission = 0);
    treeData.rollupPaths = [];

    // คำนวณแต่ละ member (จากล่างขึ้นบน)
    for (let level = treeData.members[treeData.members.length - 1].level; level >= 0; level--) {
        const membersInLevel = treeData.members.filter(m => m.level === level);

        membersInLevel.forEach(member => {
            if (member.level === 0) return; // ข้าม YOU

            // หาว่าใครได้รับคอมมิชชั่นนี้
            let sponsor = treeData.members[member.parentId];
            let currentLevel = 1;
            let originalSponsor = sponsor;

            // Rollup logic: หา sponsor ที่แอคทีฟถัดไป
            while (sponsor && !sponsor.active && currentLevel < unilevelMaxDepth) {
                // บันทึก rollup path
                const nextSponsor = sponsor.parentId >= 0 ? treeData.members[sponsor.parentId] : null;
                if (nextSponsor) {
                    treeData.rollupPaths.push({
                        from: member.id,
                        skipped: sponsor.id,
                        to: nextSponsor.id
                    });
                    sponsor = nextSponsor;
                    currentLevel++;
                } else {
                    break;
                }
            }

            // คำนวณคอมมิชชั่น
            if (sponsor && sponsor.active && currentLevel <= unilevelMaxDepth) {
                const levelConfig = unilevelLevels[currentLevel - 1];
                if (levelConfig) {
                    const percentage = levelConfig.percentage || 0;
                    const pv = member.sales * (settings.global_pv_rate || 1);
                    const commission = (pv * percentage) / 100;
                    sponsor.commission += commission;
                }
            }
        });
    }

    updateSummary();
    renderTree();
}

// อัพเดทแผงสรุป
function updateSummary() {
    const activeMembers = treeData.members.filter(m => m.active).length;
    const totalMembers = treeData.members.length;
    const totalCommission = treeData.members.reduce((sum, m) => sum + m.commission, 0);
    const activeRate = (activeMembers / totalMembers * 100).toFixed(1);

    document.getElementById('total-commission').textContent = Math.floor(totalCommission).toLocaleString();
    document.getElementById('active-count').textContent = activeMembers;
    document.getElementById('total-count').textContent = totalMembers;
    document.getElementById('active-rate').textContent = activeRate;

    // แยกตามชั้น
    const maxLevel = Math.max(...treeData.members.map(m => m.level));
    let levelHtml = '';

    for (let level = 1; level <= maxLevel; level++) {
        const membersInLevel = treeData.members.filter(m => m.level === level);
        const activeInLevel = membersInLevel.filter(m => m.active).length;
        const commissionInLevel = membersInLevel.reduce((sum, m) => sum + m.commission, 0);

        levelHtml += `
            <div style="padding:12px 14px; border-radius:13px; box-shadow:var(--inset-sm);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-weight:700; color:var(--ink);">ชั้น ${level}</span>
                    <span style="font-size:11px; color:var(--ink2);">${activeInLevel}/${membersInLevel.length} คน</span>
                </div>
                <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--deep1);">฿${Math.floor(commissionInLevel).toLocaleString()}</div>
            </div>
        `;
    }

    document.getElementById('level-breakdown').innerHTML = levelHtml;

    // รายละเอียด Rollup
    if (treeData.rollupPaths.length > 0) {
        let rollupHtml = '<div style="display:flex; flex-direction:column; gap:8px;">';
        treeData.rollupPaths.forEach(path => {
            const fromMember = treeData.members[path.from];
            const skippedMember = treeData.members[path.skipped];
            const toMember = treeData.members[path.to];
            rollupHtml += `
                <div style="padding:9px 11px; border-radius:11px; box-shadow:var(--inset-sm); font-size:12px;">
                    <span style="font-weight:700; color:#5aa07e;">${fromMember.name}</span>
                    →
                    <span style="font-weight:700; color:#d9534f; text-decoration:line-through;">${skippedMember.name}</span>
                    →
                    <span style="font-weight:700; color:#e0a52e;">${toMember.name}</span>
                </div>
            `;
        });
        rollupHtml += '</div>';
        document.getElementById('rollup-list').innerHTML = rollupHtml;
    } else {
        document.getElementById('rollup-list').innerHTML = '<p style="color:var(--ink2); font-size:13px; margin:0;">ไม่มี rollup (ทุกคนแอคทีฟ)</p>';
    }
}

// สถานการณ์ตัวอย่าง
function loadPreset(type) {
    switch(type) {
        case 'perfect':
            treeData.members.forEach(m => m.active = true);
            break;
        case 'realistic':
            treeData.members.forEach(m => {
                if (m.level === 0) {
                    m.active = true;
                } else {
                    m.active = Math.random() > 0.3; // แอคทีฟ 70%
                }
            });
            break;
        case 'rollup':
            treeData.members.forEach(m => {
                if (m.level === 0) {
                    m.active = true;
                } else if (m.level === 1) {
                    m.active = m.id % 2 === 1; // สลับไม่แอคทีฟ
                } else {
                    m.active = true;
                }
            });
            break;
        case 'custom':
            // ให้ผู้ใช้กำหนดเอง
            break;
    }
    recalculate();
    renderTree();
}

// แอนิเมชั่น
function playAnimation() {
    if (animationRunning) return;
    animationRunning = true;

    const svg = d3.select('#commission-flow');
    svg.selectAll('*').remove();

    let delay = 0;

    // แอนิเมชั่นการไหลของคอมมิชชั่นแต่ละเส้นที่แอคทีฟ
    treeData.members.forEach(member => {
        if (member.level > 0 && member.commission > 0) {
            setTimeout(() => {
                const circle = svg.append('circle')
                    .attr('cx', member.x)
                    .attr('cy', member.y)
                    .attr('r', 5)
                    .attr('fill', 'url(#flowGradient)')
                    .attr('class', 'commission-particle');

                // เคลื่อนไปหา parent
                const parent = treeData.members[member.parentId];
                circle.transition()
                    .duration(1000)
                    .attr('cx', parent.x)
                    .attr('cy', parent.y)
                    .on('end', function() {
                        d3.select(this).remove();
                    });
            }, delay);
            delay += 200;
        }
    });

    setTimeout(() => {
        animationRunning = false;
    }, delay + 1000);
}

function toggleRollupView() {
    const show = document.getElementById('showRollup').checked;
    d3.selectAll('.rollup-line').style('display', show ? 'block' : 'none');
}

function toggleAnimation() {
    // สลับแอนิเมชั่น
}

function captureScenario() {
    html2canvas(document.getElementById('tree-container')).then(canvas => {
        const link = document.createElement('a');
        link.download = 'mlm-scenario.png';
        link.href = canvas.toDataURL();
        link.click();
    });
}

function shareScenario() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        if (window.showNotification) {
            window.showNotification('คัดลอกลิงก์แล้ว!', 'success');
        } else {
            alert('คัดลอกลิงก์แล้ว!');
        }
    });
}

function printScenario() {
    window.print();
}

// เริ่มต้น
loadSettings();
</script>
@endpush
@endsection
