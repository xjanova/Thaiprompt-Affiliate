@extends('layouts.user-arrow-x')

@section('title', 'จำลองสถานการณ์ MLM')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-4">
                🎯 จำลองสถานการณ์ MLM
            </h1>
            <p class="text-xl text-gray-700 mb-2">ทดสอบสถานการณ์ต่างๆ และดูการไหลของรายได้แบบเรียลไทม์</p>
            <p class="text-gray-600">เครื่องมือสำหรับนำเสนอแผน MLM แบบมืออาชีพ</p>
        </div>

        <!-- Control Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <!-- Left Panel: Controls -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Preset Scenarios -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">📋 สถานการณ์ตัวอย่าง</h3>
                    <div class="space-y-2">
                        <button onclick="loadPreset('perfect')" class="w-full px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:from-green-600 hover:to-emerald-600 transition-all">
                            ✨ ทุกคนแอคทีฟ (100%)
                        </button>
                        <button onclick="loadPreset('realistic')" class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-600 transition-all">
                            📊 สมจริง (70% แอคทีฟ)
                        </button>
                        <button onclick="loadPreset('rollup')" class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-600 transition-all">
                            🔄 โรลอัพ Demo
                        </button>
                        <button onclick="loadPreset('custom')" class="w-full px-4 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl font-semibold hover:from-gray-600 hover:to-gray-700 transition-all">
                            ⚙️ กำหนดเอง
                        </button>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">⚙️ ตั้งค่า</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                💰 ยอดขายต่อคน (บาท)
                            </label>
                            <input type="number" id="perPersonSales" value="5000" step="1000" min="0"
                                   onchange="recalculate()"
                                   class="w-full px-3 py-2 border-2 border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-400">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                📊 จำนวนชั้น
                            </label>
                            <select id="treeDepth" onchange="generateTree()"
                                    class="w-full px-3 py-2 border-2 border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-400">
                                <option value="2">2 ชั้น (7 คน)</option>
                                <option value="3" selected>3 ชั้น (15 คน)</option>
                                <option value="4">4 ชั้น (31 คน)</option>
                                <option value="5">5 ชั้น (63 คน)</option>
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="showRollup" onchange="toggleRollupView()" checked
                                       class="w-5 h-5 text-purple-600 rounded focus:ring-2 focus:ring-purple-400">
                                <span class="text-sm font-semibold text-gray-700">แสดงเส้น Rollup</span>
                            </label>
                        </div>

                        <div>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="animateFlow" onchange="toggleAnimation()" checked
                                       class="w-5 h-5 text-purple-600 rounded focus:ring-2 focus:ring-purple-400">
                                <span class="text-sm font-semibold text-gray-700">แอนิเมชั่น</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <button onclick="playAnimation()"
                                class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-bold hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg">
                            ▶️ เล่นแอนิเมชั่น
                        </button>
                    </div>
                </div>

                <!-- Legend -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">📌 คำอธิบาย</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-6 h-6 bg-green-500 rounded-full border-2 border-green-700"></div>
                            <span>แอคทีฟ (ได้รับรายได้)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-6 h-6 bg-red-500 rounded-full border-2 border-red-700"></div>
                            <span>ไม่แอคทีฟ (รายได้โรลอัพ)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-12 h-1 bg-blue-500"></div>
                            <span>เส้นทางปกติ</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-12 h-1 bg-orange-500 border-dashed border-2 border-orange-600"></div>
                            <span>เส้นทาง Rollup</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center Panel: Tree Visualization -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl p-6 relative">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">
                        🌳 ผังโครงสร้างทีม (คลิกเพื่อเปลี่ยนสถานะ)
                    </h3>

                    <!-- Tree Canvas -->
                    <div id="tree-container" class="relative bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 overflow-auto" style="min-height: 600px;">
                        <!-- Tree will be rendered here by JavaScript -->
                    </div>

                    <!-- Commission Flow Overlay -->
                    <svg id="commission-flow" class="absolute inset-0 pointer-events-none" style="z-index: 10;">
                        <defs>
                            <linearGradient id="flowGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#fbbf24;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#f59e0b;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>

            <!-- Right Panel: Summary -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Total Summary -->
                <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
                    <h3 class="text-lg font-bold mb-4">💰 สรุปรายได้</h3>
                    <div class="space-y-3">
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <div class="text-sm opacity-90 mb-1">รายได้รวมทั้งหมด</div>
                            <div class="text-3xl font-bold">฿<span id="total-commission">0</span></div>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <div class="text-sm opacity-90 mb-1">จำนวนคนแอคทีฟ</div>
                            <div class="text-2xl font-bold"><span id="active-count">0</span>/<span id="total-count">0</span></div>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <div class="text-sm opacity-90 mb-1">อัตราแอคทีฟ</div>
                            <div class="text-2xl font-bold"><span id="active-rate">0</span>%</div>
                        </div>
                    </div>
                </div>

                <!-- Level Breakdown -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">📊 แยกตามชั้น</h3>
                    <div id="level-breakdown" class="space-y-3">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Rollup Details -->
                <div id="rollup-details" class="bg-gradient-to-br from-orange-100 to-yellow-100 rounded-2xl shadow-xl p-6 border-2 border-orange-300">
                    <h3 class="text-lg font-bold text-orange-800 mb-4">🔄 Rollup Details</h3>
                    <div id="rollup-list" class="space-y-2 text-sm">
                        <p class="text-gray-600">คลิก member ที่ไม่แอคทีฟเพื่อดู rollup path</p>
                    </div>
                </div>

                <!-- Export/Share -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">📤 แชร์/บันทึก</h3>
                    <div class="space-y-2">
                        <button onclick="captureScenario()"
                                class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition-all">
                            📸 บันทึกรูปภาพ
                        </button>
                        <button onclick="shareScenario()"
                                class="w-full px-4 py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition-all">
                            🔗 คัดลอกลิงก์
                        </button>
                        <button onclick="printScenario()"
                                class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg font-semibold hover:bg-gray-600 transition-all">
                            🖨️ พิมพ์
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.member-node {
    cursor: pointer;
    transition: all 0.3s ease;
}

.member-node:hover {
    transform: scale(1.1);
    filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.5));
}

.member-node.active {
    fill: #10b981;
    stroke: #059669;
}

.member-node.inactive {
    fill: #ef4444;
    stroke: #dc2626;
}

.connection-line {
    stroke: #6366f1;
    stroke-width: 2;
    fill: none;
}

.rollup-line {
    stroke: #f97316;
    stroke-width: 3;
    stroke-dasharray: 5, 5;
    fill: none;
    animation: dash 1s linear infinite;
}

@keyframes dash {
    to {
        stroke-dashoffset: -10;
    }
}

.commission-particle {
    animation: flow 2s ease-in-out forwards;
}

@keyframes flow {
    0% {
        opacity: 0;
        transform: scale(0);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
    100% {
        opacity: 0;
        transform: scale(0);
    }
}

.pulse-ring {
    animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

@keyframes pulse-ring {
    0% {
        transform: scale(0.95);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.7;
    }
    100% {
        transform: scale(0.95);
        opacity: 1;
    }
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>

<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
let treeData = {
    settings: {},
    members: [],
    connections: [],
    rollupPaths: []
};

let selectedMember = null;
let animationRunning = false;

// Load MLM settings
async function loadSettings() {
    try {
        const response = await fetch('{{ route("admin.mlm.settings.get-settings") }}');
        const data = await response.json();
        treeData.settings = data.settings;
        generateTree();
    } catch (error) {
        console.error('Failed to load settings:', error);
        generateTree(); // Generate with defaults
    }
}

// Generate tree structure
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

    // Generate members for each level
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

            // Create connection
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

// Render tree visualization
function renderTree() {
    const container = document.getElementById('tree-container');
    const width = container.clientWidth;
    const height = 600;

    // Clear existing
    container.innerHTML = '';

    // Create SVG
    const svg = d3.select('#tree-container')
        .append('svg')
        .attr('width', width)
        .attr('height', height);

    // Calculate positions
    const depth = parseInt(document.getElementById('treeDepth').value);
    const levelHeight = height / (depth + 2);

    treeData.members.forEach(member => {
        const membersInLevel = Math.pow(2, member.level);
        const levelWidth = width / (membersInLevel + 1);
        const position = treeData.members.filter(m => m.level === member.level).indexOf(member);

        member.x = levelWidth * (position + 1);
        member.y = levelHeight * (member.level + 1);
    });

    // Draw connections
    svg.selectAll('.connection')
        .data(treeData.connections)
        .enter()
        .append('line')
        .attr('class', d => d.type === 'rollup' ? 'rollup-line' : 'connection-line')
        .attr('x1', d => treeData.members[d.from].x)
        .attr('y1', d => treeData.members[d.from].y)
        .attr('x2', d => treeData.members[d.to].x)
        .attr('y2', d => treeData.members[d.to].y);

    // Draw members
    const nodes = svg.selectAll('.member-group')
        .data(treeData.members)
        .enter()
        .append('g')
        .attr('class', 'member-group')
        .attr('transform', d => `translate(${d.x},${d.y})`);

    // Circle
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

    // Label
    nodes.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '.35em')
        .attr('class', 'text-xs font-bold')
        .style('fill', 'white')
        .style('pointer-events', 'none')
        .text(d => d.name);

    // Commission badge
    nodes.append('circle')
        .attr('cx', 25)
        .attr('cy', -15)
        .attr('r', 15)
        .attr('class', 'fill-yellow-400 stroke-yellow-600')
        .attr('stroke-width', 2)
        .style('display', d => d.commission > 0 ? 'block' : 'none');

    nodes.append('text')
        .attr('x', 25)
        .attr('y', -15)
        .attr('text-anchor', 'middle')
        .attr('dy', '.35em')
        .attr('class', 'text-xs font-bold')
        .style('fill', '#92400e')
        .style('pointer-events', 'none')
        .style('display', d => d.commission > 0 ? 'block' : 'none')
        .text(d => Math.floor(d.commission / 1000) + 'K');
}

// Calculate commissions and rollup
function recalculate() {
    const settings = treeData.settings;
    const unilevelLevels = settings.unilevel_levels || [];
    const unilevelMaxDepth = parseInt(settings.unilevel_max_depth || 10);

    // Reset commissions
    treeData.members.forEach(m => m.commission = 0);
    treeData.rollupPaths = [];

    // Calculate for each member (bottom-up)
    for (let level = treeData.members[treeData.members.length - 1].level; level >= 0; level--) {
        const membersInLevel = treeData.members.filter(m => m.level === level);

        membersInLevel.forEach(member => {
            if (member.level === 0) return; // Skip YOU

            // Find who receives this commission
            let sponsor = treeData.members[member.parentId];
            let currentLevel = 1;
            let originalSponsor = sponsor;

            // Rollup logic: find next active sponsor
            while (sponsor && !sponsor.active && currentLevel < unilevelMaxDepth) {
                // Record rollup path
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

            // Calculate commission
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

// Update summary panel
function updateSummary() {
    const activeMembers = treeData.members.filter(m => m.active).length;
    const totalMembers = treeData.members.length;
    const totalCommission = treeData.members.reduce((sum, m) => sum + m.commission, 0);
    const activeRate = (activeMembers / totalMembers * 100).toFixed(1);

    document.getElementById('total-commission').textContent = Math.floor(totalCommission).toLocaleString();
    document.getElementById('active-count').textContent = activeMembers;
    document.getElementById('total-count').textContent = totalMembers;
    document.getElementById('active-rate').textContent = activeRate;

    // Level breakdown
    const maxLevel = Math.max(...treeData.members.map(m => m.level));
    let levelHtml = '';

    for (let level = 1; level <= maxLevel; level++) {
        const membersInLevel = treeData.members.filter(m => m.level === level);
        const activeInLevel = membersInLevel.filter(m => m.active).length;
        const commissionInLevel = membersInLevel.reduce((sum, m) => sum + m.commission, 0);

        levelHtml += `
            <div class="bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg p-3">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-semibold text-gray-800">ชั้น ${level}</span>
                    <span class="text-xs text-gray-600">${activeInLevel}/${membersInLevel.length} คน</span>
                </div>
                <div class="text-lg font-bold text-purple-600">฿${Math.floor(commissionInLevel).toLocaleString()}</div>
            </div>
        `;
    }

    document.getElementById('level-breakdown').innerHTML = levelHtml;

    // Rollup details
    if (treeData.rollupPaths.length > 0) {
        let rollupHtml = '<div class="space-y-2">';
        treeData.rollupPaths.forEach(path => {
            const fromMember = treeData.members[path.from];
            const skippedMember = treeData.members[path.skipped];
            const toMember = treeData.members[path.to];
            rollupHtml += `
                <div class="bg-white rounded-lg p-2 text-xs">
                    <span class="font-semibold text-green-600">${fromMember.name}</span>
                    →
                    <span class="font-semibold text-red-600 line-through">${skippedMember.name}</span>
                    →
                    <span class="font-semibold text-orange-600">${toMember.name}</span>
                </div>
            `;
        });
        rollupHtml += '</div>';
        document.getElementById('rollup-list').innerHTML = rollupHtml;
    } else {
        document.getElementById('rollup-list').innerHTML = '<p class="text-gray-600 text-sm">ไม่มี rollup (ทุกคนแอคทีฟ)</p>';
    }
}

// Preset scenarios
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
                    m.active = Math.random() > 0.3; // 70% active
                }
            });
            break;
        case 'rollup':
            treeData.members.forEach(m => {
                if (m.level === 0) {
                    m.active = true;
                } else if (m.level === 1) {
                    m.active = m.id % 2 === 1; // Every other one inactive
                } else {
                    m.active = true;
                }
            });
            break;
        case 'custom':
            // Let user customize
            break;
    }
    recalculate();
    renderTree();
}

// Animation
function playAnimation() {
    if (animationRunning) return;
    animationRunning = true;

    const svg = d3.select('#commission-flow');
    svg.selectAll('*').remove();

    let delay = 0;

    // Animate commission flow for each active path
    treeData.members.forEach(member => {
        if (member.level > 0 && member.commission > 0) {
            setTimeout(() => {
                const circle = svg.append('circle')
                    .attr('cx', member.x)
                    .attr('cy', member.y)
                    .attr('r', 5)
                    .attr('fill', 'url(#flowGradient)')
                    .attr('class', 'commission-particle');

                // Animate to parent
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
    // Animation toggle
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
        alert('คัดลอกลิงก์แล้ว!');
    });
}

function printScenario() {
    window.print();
}

// Initialize
loadSettings();
</script>
@endsection
