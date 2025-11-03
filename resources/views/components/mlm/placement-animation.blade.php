@props(['type' => 'left_first'])

<div class="placement-animation-container bg-white rounded-xl shadow-lg p-6">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">
            {{ [
                'left_first' => 'ต่อซ้ายสุดก่อน (Left First)',
                'right_first' => 'ต่อขวาสุดก่อน (Right First)',
                'fill_level' => 'เติมเต็มทีละชั้น (Fill Level)',
                'weak_leg' => 'ต่อขาอ่อนก่อน (Weak Leg)',
                'balanced' => 'สมดุลทั้งสองขา (Balanced)'
            ][$type] ?? 'Unknown' }}
        </h3>
        <p class="text-sm text-gray-600">
            {{ [
                'left_first' => 'สมาชิกใหม่จะถูกวางในตำแหน่งซ้ายสุดที่ว่างก่อนเสมอ',
                'right_first' => 'สมาชิกใหม่จะถูกวางในตำแหน่งขวาสุดที่ว่างก่อนเสมอ',
                'fill_level' => 'เติมเต็มสมาชิกทีละชั้น จากซ้ายไปขวา จนเต็มชั้นจึงลงชั้นถัดไป',
                'weak_leg' => 'วางสมาชิกใหม่ในขาที่มี PV น้อยกว่าเพื่อสร้างความสมดุล',
                'balanced' => 'วางสมาชิกใหม่เพื่อให้จำนวนสมาชิกทั้งสองขาใกล้เคียงกัน'
            ][$type] ?? '' }}
        </p>
    </div>

    <div class="relative bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-8 overflow-hidden" style="min-height: 400px;">
        <!-- Animation SVG Container -->
        <svg id="placement-svg-{{ $type }}" class="w-full h-96" viewBox="0 0 800 400">
            <!-- Sponsor (Root) -->
            <g id="sponsor-{{ $type }}" class="member-node sponsor-node">
                <circle cx="400" cy="50" r="25" fill="#8B5CF6" stroke="#6D28D9" stroke-width="2"/>
                <text x="400" y="55" text-anchor="middle" fill="white" font-size="12" font-weight="bold">S</text>
                <text x="400" y="85" text-anchor="middle" fill="#6B7280" font-size="10">Sponsor</text>
            </g>

            <!-- Connection Lines (will be drawn dynamically) -->
            <g id="lines-{{ $type }}" stroke="#D1D5DB" stroke-width="2" fill="none"></g>

            <!-- Member Nodes (will be added dynamically) -->
            <g id="nodes-{{ $type }}"></g>
        </svg>

        <!-- Control Panel -->
        <div class="absolute bottom-4 left-4 right-4 bg-white rounded-lg shadow-md p-4 flex items-center justify-between">
            <div class="flex gap-2">
                <button type="button"
                        onclick="startAnimation('{{ $type }}')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    เริ่มต้น
                </button>
                <button type="button"
                        onclick="resetAnimation('{{ $type }}')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    รีเซ็ต
                </button>
            </div>
            <div class="text-sm text-gray-700">
                <span class="font-semibold">สมาชิกที่เพิ่ม:</span>
                <span id="counter-{{ $type }}" class="text-purple-600 font-bold ml-2">0</span>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="mt-4 flex gap-4 text-sm">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-purple-600 border-2 border-purple-800"></div>
            <span class="text-gray-700">Sponsor</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-blue-500 border-2 border-blue-700"></div>
            <span class="text-gray-700">สมาชิกใหม่</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-green-500 border-2 border-green-700 animate-pulse"></div>
            <span class="text-gray-700">กำลังวาง</span>
        </div>
    </div>
</div>

<style>
.member-node {
    transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.member-node.adding {
    animation: pulse-scale 0.6s ease-in-out;
}

@keyframes pulse-scale {
    0% { transform: scale(1); opacity: 0; }
    50% { transform: scale(1.3); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

.connection-line {
    animation: draw-line 0.4s ease-out forwards;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
}

@keyframes draw-line {
    to {
        stroke-dashoffset: 0;
    }
}
</style>

<script>
// Animation state for each type
window.placementAnimations = window.placementAnimations || {};

function initPlacementAnimation(type) {
    window.placementAnimations[type] = {
        members: [],
        counter: 0,
        isAnimating: false,
        intervalId: null,
        tree: { id: 'S', left: null, right: null, pv: 0 }
    };
}

function getPositionForPlacement(type, tree, memberIndex) {
    const baseX = 400;
    const baseY = 50;
    const levelHeight = 80;
    const horizontalSpacing = 100;

    switch(type) {
        case 'left_first':
            return getLeftFirstPosition(tree, memberIndex, baseX, baseY, levelHeight, horizontalSpacing);

        case 'right_first':
            return getRightFirstPosition(tree, memberIndex, baseX, baseY, levelHeight, horizontalSpacing);

        case 'fill_level':
            return getFillLevelPosition(tree, memberIndex, baseX, baseY, levelHeight, horizontalSpacing);

        case 'weak_leg':
            return getWeakLegPosition(tree, memberIndex, baseX, baseY, levelHeight, horizontalSpacing);

        case 'balanced':
            return getBalancedPosition(tree, memberIndex, baseX, baseY, levelHeight, horizontalSpacing);

        default:
            return { x: baseX, y: baseY + levelHeight, side: 'left' };
    }
}

function getLeftFirstPosition(tree, index, baseX, baseY, levelH, spacing) {
    // Binary tree positions - always go left first
    const positions = [
        { x: baseX - spacing, y: baseY + levelH, side: 'left', parent: baseX },      // 1: Left of sponsor
        { x: baseX + spacing, y: baseY + levelH, side: 'right', parent: baseX },     // 2: Right of sponsor
        { x: baseX - spacing*1.5, y: baseY + levelH*2, side: 'left', parent: baseX - spacing },  // 3: Left of 1
        { x: baseX - spacing*0.5, y: baseY + levelH*2, side: 'right', parent: baseX - spacing }, // 4: Right of 1
        { x: baseX + spacing*0.5, y: baseY + levelH*2, side: 'left', parent: baseX + spacing },  // 5: Left of 2
        { x: baseX + spacing*1.5, y: baseY + levelH*2, side: 'right', parent: baseX + spacing }, // 6: Right of 2
    ];
    return positions[index % positions.length];
}

function getRightFirstPosition(tree, index, baseX, baseY, levelH, spacing) {
    // Right first strategy
    const positions = [
        { x: baseX + spacing, y: baseY + levelH, side: 'right', parent: baseX },     // 1: Right of sponsor
        { x: baseX - spacing, y: baseY + levelH, side: 'left', parent: baseX },      // 2: Left of sponsor
        { x: baseX + spacing*1.5, y: baseY + levelH*2, side: 'right', parent: baseX + spacing }, // 3: Right of 1
        { x: baseX + spacing*0.5, y: baseY + levelH*2, side: 'left', parent: baseX + spacing },  // 4: Left of 1
        { x: baseX - spacing*0.5, y: baseY + levelH*2, side: 'right', parent: baseX - spacing }, // 5: Right of 2
        { x: baseX - spacing*1.5, y: baseY + levelH*2, side: 'left', parent: baseX - spacing },  // 6: Left of 2
    ];
    return positions[index % positions.length];
}

function getFillLevelPosition(tree, index, baseX, baseY, levelH, spacing) {
    // Fill level by level, left to right
    const positions = [
        { x: baseX - spacing, y: baseY + levelH, side: 'left', parent: baseX },
        { x: baseX + spacing, y: baseY + levelH, side: 'right', parent: baseX },
        { x: baseX - spacing*1.5, y: baseY + levelH*2, side: 'left', parent: baseX - spacing },
        { x: baseX - spacing*0.5, y: baseY + levelH*2, side: 'right', parent: baseX - spacing },
        { x: baseX + spacing*0.5, y: baseY + levelH*2, side: 'left', parent: baseX + spacing },
        { x: baseX + spacing*1.5, y: baseY + levelH*2, side: 'right', parent: baseX + spacing },
    ];
    return positions[index % positions.length];
}

function getWeakLegPosition(tree, index, baseX, baseY, levelH, spacing) {
    // Simulate weak leg by alternating with bias
    const leftCount = Math.floor(index / 2);
    const isLeft = (leftCount % 2 === 0) ? (index % 2 === 0) : (index % 2 === 1);

    const positions = [
        { x: baseX - spacing, y: baseY + levelH, side: 'left', parent: baseX },
        { x: baseX + spacing, y: baseY + levelH, side: 'right', parent: baseX },
        { x: baseX - spacing*1.5, y: baseY + levelH*2, side: 'left', parent: baseX - spacing },
        { x: baseX + spacing*1.5, y: baseY + levelH*2, side: 'right', parent: baseX + spacing },
        { x: baseX - spacing*0.5, y: baseY + levelH*2, side: 'right', parent: baseX - spacing },
        { x: baseX + spacing*0.5, y: baseY + levelH*2, side: 'left', parent: baseX + spacing },
    ];
    return positions[index % positions.length];
}

function getBalancedPosition(tree, index, baseX, baseY, levelH, spacing) {
    // Alternate left-right for balance
    const isLeft = index % 2 === 0;
    const level = Math.floor(Math.log2(index + 2));
    const positions = [
        { x: baseX - spacing, y: baseY + levelH, side: 'left', parent: baseX },
        { x: baseX + spacing, y: baseY + levelH, side: 'right', parent: baseX },
        { x: baseX - spacing*1.5, y: baseY + levelH*2, side: 'left', parent: baseX - spacing },
        { x: baseX + spacing*1.5, y: baseY + levelH*2, side: 'right', parent: baseX + spacing },
        { x: baseX - spacing*0.5, y: baseY + levelH*2, side: 'right', parent: baseX - spacing },
        { x: baseX + spacing*0.5, y: baseY + levelH*2, side: 'left', parent: baseX + spacing },
    ];
    return positions[index % positions.length];
}

function startAnimation(type) {
    if (!window.placementAnimations[type]) {
        initPlacementAnimation(type);
    }

    const state = window.placementAnimations[type];

    if (state.isAnimating) return;

    state.isAnimating = true;

    state.intervalId = setInterval(() => {
        if (state.counter >= 6) {
            stopAnimation(type);
            return;
        }

        addMemberWithAnimation(type);
    }, 1500);
}

function addMemberWithAnimation(type) {
    const state = window.placementAnimations[type];
    const svg = document.getElementById(`placement-svg-${type}`);
    const nodesGroup = document.getElementById(`nodes-${type}`);
    const linesGroup = document.getElementById(`lines-${type}`);

    const position = getPositionForPlacement(type, state.tree, state.counter);
    const memberId = state.counter + 1;

    // Draw connection line
    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    line.setAttribute('x1', position.parent);
    line.setAttribute('y1', position.y - 80);
    line.setAttribute('x2', position.x);
    line.setAttribute('y2', position.y);
    line.setAttribute('class', 'connection-line');
    line.setAttribute('stroke', '#D1D5DB');
    line.setAttribute('stroke-width', '2');
    linesGroup.appendChild(line);

    // Create member node
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('class', 'member-node adding');

    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', position.x);
    circle.setAttribute('cy', position.y);
    circle.setAttribute('r', '20');
    circle.setAttribute('fill', '#10B981');
    circle.setAttribute('stroke', '#059669');
    circle.setAttribute('stroke-width', '2');

    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    text.setAttribute('x', position.x);
    text.setAttribute('y', position.y + 5);
    text.setAttribute('text-anchor', 'middle');
    text.setAttribute('fill', 'white');
    text.setAttribute('font-size', '12');
    text.setAttribute('font-weight', 'bold');
    text.textContent = memberId;

    g.appendChild(circle);
    g.appendChild(text);
    nodesGroup.appendChild(g);

    // Change color after animation
    setTimeout(() => {
        circle.setAttribute('fill', '#3B82F6');
        circle.setAttribute('stroke', '#1D4ED8');
        g.classList.remove('adding');
    }, 600);

    state.counter++;
    document.getElementById(`counter-${type}`).textContent = state.counter;
}

function stopAnimation(type) {
    const state = window.placementAnimations[type];
    if (state && state.intervalId) {
        clearInterval(state.intervalId);
        state.isAnimating = false;
    }
}

function resetAnimation(type) {
    stopAnimation(type);

    if (!window.placementAnimations[type]) {
        initPlacementAnimation(type);
    }

    const state = window.placementAnimations[type];
    state.counter = 0;
    state.members = [];

    const nodesGroup = document.getElementById(`nodes-${type}`);
    const linesGroup = document.getElementById(`lines-${type}`);

    if (nodesGroup) nodesGroup.innerHTML = '';
    if (linesGroup) linesGroup.innerHTML = '';

    document.getElementById(`counter-${type}`).textContent = '0';
}

// Initialize all animations on page load
document.addEventListener('DOMContentLoaded', function() {
    const types = ['left_first', 'right_first', 'fill_level', 'weak_leg', 'balanced'];
    types.forEach(type => {
        if (document.getElementById(`placement-svg-${type}`)) {
            initPlacementAnimation(type);
        }
    });
});
</script>
