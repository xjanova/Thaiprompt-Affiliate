@extends('layouts.user-v4')

@section('title', 'เปรียบเทียบแผนรายได้')

@php
    // ── ข้อมูลแผนทั้ง 3 (สำหรับ render การ์ดด้านบน) ───────────────
    // คีย์ต้องตรงกับ object scenarios ใน JS เพื่อให้ selector .{key}-income / .{key}-yearly ทำงาน
    $plans = [
        [
            'key'   => 'starter',
            'emoji' => '🌱',
            'name'  => 'แผนเริ่มต้น',
            'desc'  => 'เหมาะกับมือใหม่',
            'color' => '#5aa07e',          // เขียว success
            'sales' => '฿5,000',
            'team'  => '5 คน',
            'tsale' => '฿15,000',
            'badge' => null,
        ],
        [
            'key'   => 'growth',
            'emoji' => '🚀',
            'name'  => 'แผนเติบโต',
            'desc'  => 'สร้างรายได้มั่นคง',
            'color' => 'var(--deep1)',     // ทองคำเข้ม (แผนแนะนำ)
            'sales' => '฿20,000',
            'team'  => '20 คน',
            'tsale' => '฿100,000',
            'badge' => 'แนะนำ!',
        ],
        [
            'key'   => 'elite',
            'emoji' => '👑',
            'name'  => 'แผนชนชั้นสูง',
            'desc'  => 'สร้างอิสรภาพทางการเงิน',
            'color' => '#e0a52e',          // เหลือง/ทอง warning
            'sales' => '฿50,000',
            'team'  => '50 คน',
            'tsale' => '฿250,000',
            'badge' => null,
        ],
    ];

    // ── เรื่องราวความสำเร็จ ─────────────────────────────────────
    $stories = [
        ['emoji' => '👨‍💼', 'name' => 'คุณสมชาย',  'desc' => 'เริ่มต้นด้วยแผนเติบโต ปัจจุบันมีรายได้',    'amount' => '฿85,000/เดือน'],
        ['emoji' => '👩‍💼', 'name' => 'คุณสมหญิง', 'desc' => 'ทำงานพาร์ทไทม์ สร้างรายได้เสริม',          'amount' => '฿42,000/เดือน'],
        ['emoji' => '👨‍🎓', 'name' => 'คุณสมศักดิ์','desc' => 'นักศึกษา สร้างรายได้ระหว่างเรียน',         'amount' => '฿18,000/เดือน'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:26px;">🔥</span>
            <div style="flex:1; min-width:200px;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">เปรียบเทียบแผนรายได้ · INCOME COMPARISON</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:4px 0 0;">เปรียบเทียบแผนรายได้</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">เลือกแผนที่เหมาะกับคุณ — มองเห็นผลตอบแทนชัดเจน</div>
            </div>
            <a href="{{ route('user.mlm.income-simulator') }}" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> จำลองรายได้</a>
        </div>
    </div>

    {{-- ── การ์ดแผนทั้ง 3 ────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
        @foreach($plans as $p)
            <div class="tp-card tp-card-hover" style="padding:0; overflow:hidden; position:relative;
                 {{ $p['badge'] ? 'box-shadow:0 0 0 2px var(--deep1) inset, var(--shadow-soft, none);' : '' }}">

                {{-- ป้ายแนะนำ --}}
                @if($p['badge'])
                    <span class="tp-pill tp-pill-gold" style="position:absolute; top:12px; right:12px; z-index:2; font-size:10px;">{{ $p['badge'] }}</span>
                @endif

                {{-- หัวการ์ด --}}
                <div style="padding:20px; text-align:center;
                            background:linear-gradient(120deg, color-mix(in srgb, {{ $p['color'] }} 18%, transparent), transparent 75%);">
                    <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; font-size:28px; margin:0 auto; background:color-mix(in srgb, {{ $p['color'] }} 22%, transparent);">{{ $p['emoji'] }}</span>
                    <h3 class="tp-num" style="font-size:19px; font-weight:800; margin:12px 0 2px;">{{ $p['name'] }}</h3>
                    <div style="font-size:12px; color:var(--ink2);">{{ $p['desc'] }}</div>
                </div>

                {{-- รายละเอียด --}}
                <div style="padding:18px;">
                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:14px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:13px;">
                            <span style="color:var(--ink2);">ยอดขาย/เดือน:</span>
                            <span class="tp-num" style="font-weight:700;">{{ $p['sales'] }}</span>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:13px;">
                            <span style="color:var(--ink2);">จำนวนทีม:</span>
                            <span class="tp-num" style="font-weight:700;">{{ $p['team'] }}</span>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:13px;">
                            <span style="color:var(--ink2);">ยอดขายทีม:</span>
                            <span class="tp-num" style="font-weight:700;">{{ $p['tsale'] }}</span>
                        </div>
                    </div>

                    <div style="padding-top:14px; margin-bottom:14px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                        <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">รายได้รวม/เดือน:</div>
                        <div class="tp-num" style="font-size:28px; font-weight:800; color:{{ $p['color'] }};">฿<span class="{{ $p['key'] }}-income">0</span></div>
                        <div style="font-size:11px; color:var(--ink2); margin-top:2px;">≈ <span class="{{ $p['key'] }}-yearly">0</span> บาท/ปี</div>
                    </div>

                    <button type="button" onclick="simulateScenario('{{ $p['key'] }}')"
                            class="tp-btn tp-btn-primary" style="width:100%; justify-content:center;">
                        ดูรายละเอียด
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── กราฟเปรียบเทียบรายได้ (Chart.js bar) ──────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:16px; text-align:center;">📊 เปรียบเทียบรายได้</div>
        <canvas id="comparison-chart" height="100"></canvas>
    </div>

    {{-- ── รายละเอียดเชิงลึก (ซ่อนไว้ก่อน) ───────────────────── --}}
    <div id="detailed-breakdown" class="hidden">
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:18px;">
                <div class="tp-section-h">รายละเอียด: <span id="scenario-name" style="color:var(--deep1);"></span></div>
                <button type="button" onclick="closeBreakdown()" class="tp-icon-btn" aria-label="ปิด">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:18px; margin-bottom:18px;">
                {{-- สัดส่วนรายได้ (doughnut) --}}
                <div>
                    <div style="font-size:14px; font-weight:700; margin-bottom:12px;">สัดส่วนรายได้</div>
                    <canvas id="breakdown-pie" height="250"></canvas>
                </div>

                {{-- การเติบโต 12 เดือน (line) --}}
                <div>
                    <div style="font-size:14px; font-weight:700; margin-bottom:12px;">การเติบโต 12 เดือน</div>
                    <canvas id="growth-projection" height="250"></canvas>
                </div>
            </div>

            {{-- ตัวชี้วัดสำคัญ --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px;">
                <div class="tp-card" style="padding:16px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:6px;">รายได้เดือนแรก</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:#5689b8;">฿<span id="month-1">0</span></div>
                </div>
                <div class="tp-card" style="padding:16px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:6px;">เดือนที่ 6</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:#5aa07e;">฿<span id="month-6">0</span></div>
                </div>
                <div class="tp-card" style="padding:16px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:6px;">เดือนที่ 12</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">฿<span id="month-12">0</span></div>
                </div>
                <div class="tp-card" style="padding:16px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:6px;">ROI (%)</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:#e0a52e;"><span id="roi">0</span>%</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── เรื่องราวความสำเร็จ ───────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div class="tp-section-h" style="text-align:center; margin:0;">🌟 เรื่องราวความสำเร็จ</div>
        </div>
        <div style="padding:18px; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
            @foreach($stories as $s)
                <div class="tp-card" style="padding:18px; text-align:center; box-shadow:var(--inset-sm);">
                    <div style="font-size:44px; margin-bottom:8px;">{{ $s['emoji'] }}</div>
                    <div style="font-size:15px; font-weight:700; margin-bottom:4px;">{{ $s['name'] }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:10px;">{{ $s['desc'] }}</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">{{ $s['amount'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── CTA ──────────────────────────────────────────────── --}}
    <div style="text-align:center; padding:8px 0;">
        <a href="{{ route('user.mlm.income-simulator') }}" class="tp-btn tp-btn-primary"
           style="font-size:16px; padding:14px 32px;">
            🚀 ลองจำลองรายได้ของคุณเอง
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let comparisonChart, breakdownPie, growthProjection;
let settings = {};

const scenarios = {
    starter: {
        name: 'แผนเริ่มต้น',
        personalSales: 5000,
        teamSize: 5,
        teamAvgSales: 3000,
        color: '#5aa07e'
    },
    growth: {
        name: 'แผนเติบโต',
        personalSales: 20000,
        teamSize: 20,
        teamAvgSales: 5000,
        color: '#c79a3a'
    },
    elite: {
        name: 'แผนชนชั้นสูง',
        personalSales: 50000,
        teamSize: 50,
        teamAvgSales: 5000,
        color: '#e0a52e'
    }
};

async function loadSettings() {
    try {
        const response = await fetch('{{ route("user.mlm.settings") }}');
        const data = await response.json();
        settings = data.settings;
        calculateAllScenarios();
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
}

function calculateScenario(scenario) {
    const personalSales = scenario.personalSales;
    const teamSales = scenario.teamSize * scenario.teamAvgSales;

    const pvRate = parseFloat(settings.global_pv_rate || 1);
    const teamPv = teamSales / pvRate;

    // Unilevel
    const unilevelLevels = settings.unilevel_levels || [];
    let unilevel = 0;

    if (Array.isArray(unilevelLevels)) {
        unilevelLevels.forEach(level => {
            const percentage = level.percentage || 0;
            unilevel += (teamPv * percentage) / 100;
        });
    }

    // Binary (simplified)
    const binaryPairCommission = parseFloat(settings.binary_pair_commission || 100);
    const weakLegPv = teamPv * 0.4;
    const pairs = Math.floor(weakLegPv);
    const binary = Math.min(pairs * binaryPairCommission, teamPv * 0.1);

    const personal = personalSales * 0.2; // 20% retail profit
    const total = personal + unilevel + binary;

    return {
        personal,
        unilevel,
        binary,
        total,
        yearly: total * 12
    };
}

function calculateAllScenarios() {
    Object.keys(scenarios).forEach(key => {
        const result = calculateScenario(scenarios[key]);

        document.querySelector(`.${key}-income`).textContent = Math.floor(result.total).toLocaleString();
        document.querySelector(`.${key}-yearly`).textContent = Math.floor(result.yearly).toLocaleString();

        scenarios[key].result = result;
    });

    createComparisonChart();
}

function createComparisonChart() {
    const ctx = document.getElementById('comparison-chart');

    if (comparisonChart) comparisonChart.destroy();

    comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['แผนเริ่มต้น', 'แผนเติบโต', 'แผนชนชั้นสูง'],
            datasets: [{
                label: 'รายได้/เดือน (บาท)',
                data: [
                    scenarios.starter.result.total,
                    scenarios.growth.result.total,
                    scenarios.elite.result.total
                ],
                backgroundColor: [
                    'rgba(90, 160, 126, 0.8)',
                    'rgba(199, 154, 58, 0.8)',
                    'rgba(224, 165, 46, 0.8)'
                ],
                borderColor: [
                    'rgb(90, 160, 126)',
                    'rgb(199, 154, 58)',
                    'rgb(224, 165, 46)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => '฿' + value.toLocaleString()
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function simulateScenario(type) {
    const scenario = scenarios[type];
    const result = scenario.result;

    document.getElementById('scenario-name').textContent = scenario.name;
    document.getElementById('detailed-breakdown').classList.remove('hidden');

    // Breakdown Pie
    const ctxPie = document.getElementById('breakdown-pie');
    if (breakdownPie) breakdownPie.destroy();

    breakdownPie = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['ยอดขายตัวเอง', 'Unilevel', 'Binary'],
            datasets: [{
                data: [result.personal, result.unilevel, result.binary],
                backgroundColor: [
                    'rgb(90, 160, 126)',
                    'rgb(86, 137, 184)',
                    'rgb(224, 165, 46)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Growth Projection
    const growthData = [];
    for (let i = 1; i <= 12; i++) {
        const growthFactor = 1 + (i * 0.05);
        growthData.push(result.total * growthFactor);
    }

    const ctxGrowth = document.getElementById('growth-projection');
    if (growthProjection) growthProjection.destroy();

    growthProjection = new Chart(ctxGrowth, {
        type: 'line',
        data: {
            labels: Array.from({length: 12}, (_, i) => `เดือน ${i + 1}`),
            datasets: [{
                label: 'รายได้',
                data: growthData,
                borderColor: scenario.color,
                backgroundColor: scenario.color + '33',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => '฿' + value.toLocaleString()
                    }
                }
            }
        }
    });

    // Key Metrics
    document.getElementById('month-1').textContent = Math.floor(growthData[0]).toLocaleString();
    document.getElementById('month-6').textContent = Math.floor(growthData[5]).toLocaleString();
    document.getElementById('month-12').textContent = Math.floor(growthData[11]).toLocaleString();
    document.getElementById('roi').textContent = Math.floor(((growthData[11] - growthData[0]) / growthData[0]) * 100);

    // Scroll to breakdown
    document.getElementById('detailed-breakdown').scrollIntoView({ behavior: 'smooth' });
}

function closeBreakdown() {
    document.getElementById('detailed-breakdown').classList.add('hidden');
}

loadSettings();
</script>
@endpush
