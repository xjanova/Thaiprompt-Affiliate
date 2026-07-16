@extends('layouts.user-v4')

@section('title', 'สถิติการตลาด')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5aa07e 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#5aa07e;"><i class="fas fa-chart-bar" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">📊 สถิติการตลาด</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">วิเคราะห์ผลการตลาดหน้า Recruit ของคุณ</div>
                    </div>
                </div>
                <a href="{{ route('user.marketing.recruit.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับไปหน้าจัดการ</a>
            </div>
        </div>
    </div>

    {{-- ── เลือกช่วงเวลา ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
            <div style="font-weight:800; color:var(--ink);">ช่วงเวลา</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <a href="{{ route('user.marketing.recruit.analytics', ['days' => 7]) }}" class="tp-btn tp-btn-sm {{ $days == 7 ? 'tp-btn-primary' : '' }}">7 วัน</a>
                <a href="{{ route('user.marketing.recruit.analytics', ['days' => 30]) }}" class="tp-btn tp-btn-sm {{ $days == 30 ? 'tp-btn-primary' : '' }}">30 วัน</a>
                <a href="{{ route('user.marketing.recruit.analytics', ['days' => 90]) }}" class="tp-btn tp-btn-sm {{ $days == 90 ? 'tp-btn-primary' : '' }}">90 วัน</a>
            </div>
        </div>
    </div>

    {{-- ── Conversion Funnel ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:20px;">กระบวนการ Conversion ({{ $days }} วันล่าสุด)</div>
        @php
            $clickRate = $funnel['visits'] > 0 ? ($funnel['clicked_register'] / $funnel['visits'] * 100) : 0;
            $leadRate = $funnel['visits'] > 0 ? ($funnel['leads'] / $funnel['visits'] * 100) : 0;
            $conversionRate = $funnel['visits'] > 0 ? ($funnel['conversions'] / $funnel['visits'] * 100) : 0;
        @endphp
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:18px;">
            @foreach([
                ['fa-eye', '#5689b8', number_format($funnel['visits']), 'การเข้าชม', '100%'],
                ['fa-hand-pointer', '#5aa07e', number_format($funnel['clicked_register']), 'คลิกสมัคร', number_format($clickRate, 1).'%'],
                ['fa-users', '#7c5cbf', number_format($funnel['leads']), 'ผู้มุ่งหวัง', number_format($leadRate, 1).'%'],
                ['fa-check-circle', '#c05a8f', number_format($funnel['conversions']), 'สมัครสำเร็จ', number_format($conversionRate, 1).'%'],
            ] as $step)
                <div style="text-align:center;">
                    <div style="width:80px; height:80px; margin:0 auto 14px; border-radius:50%; background:linear-gradient(135deg, {{ $step[1] }}, color-mix(in srgb, {{ $step[1] }} 70%, #000)); display:flex; align-items:center; justify-content:center; box-shadow:var(--raise);">
                        <i class="fas {{ $step[0] }}" style="font-size:30px; color:#fff;"></i>
                    </div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; color:var(--ink);">{{ $step[2] }}</div>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">{{ $step[3] }}</div>
                    <div style="font-size:12px; font-weight:600; color:{{ $step[1] }}; margin-top:6px;">{{ $step[4] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── กราฟ ─────────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:18px;">
        <div class="tp-card" style="padding:24px;">
            <div class="tp-section-h" style="margin-bottom:18px;">การเข้าชมและผู้มุ่งหวังรายวัน</div>
            <canvas id="visitsLeadsChart" style="width:100%; max-height:300px;"></canvas>
        </div>
        <div class="tp-card" style="padding:24px;">
            <div class="tp-section-h" style="margin-bottom:18px;">การเข้าชมตามอุปกรณ์</div>
            <div style="display:flex; align-items:center; justify-content:center; height:300px;">
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Top Referrers ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:18px;">แหล่งอ้างอิงยอดนิยม (Top 10)</div>
        @if($topReferrers->count() > 0)
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach($topReferrers as $index => $referrer)
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px; border-radius:12px; box-shadow:var(--inset-sm);">
                        <div style="display:flex; align-items:center; gap:14px; flex:1; min-width:0;">
                            <div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg, #5689b8, #7c5cbf); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:13px;">{{ $index + 1 }}</div>
                            <div style="flex:1; min-width:0;">
                                <p style="font-weight:600; color:var(--ink); margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ parse_url($referrer->referrer_url, PHP_URL_HOST) ?? $referrer->referrer_url }}</p>
                                <p style="font-size:11px; color:var(--ink2); margin:2px 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ Str::limit($referrer->referrer_url, 60) }}</p>
                            </div>
                        </div>
                        <span style="flex-shrink:0; display:inline-flex; padding:4px 12px; border-radius:999px; font-size:13px; font-weight:600; color:#fff; background:#5689b8;">{{ number_format($referrer->count) }} ครั้ง</span>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align:center; padding:48px 20px;">
                <div style="width:64px; height:64px; margin:0 auto 16px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-link" style="font-size:26px; color:var(--ink2);"></i>
                </div>
                <p style="color:var(--ink2); margin:0;">ยังไม่มีข้อมูลแหล่งอ้างอิง</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // สีสำหรับ Dark Mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#e5e7eb' : '#374151';
    const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

    // Chart 1: Visits & Leads Line Chart
    const visitsLeadsCtx = document.getElementById('visitsLeadsChart').getContext('2d');

    // เตรียมข้อมูล
    const dates = @json($visitsPerDay->pluck('date'));
    const visitsData = @json($visitsPerDay->pluck('count'));

    // สร้าง Map สำหรับ leads
    const leadsMap = new Map();
    @json($leadsPerDay).forEach(item => {
        leadsMap.set(item.date, item.count);
    });

    // จับคู่ leads กับ dates
    const leadsData = dates.map(date => leadsMap.get(date) || 0);

    new Chart(visitsLeadsCtx, {
        type: 'line',
        data: {
            labels: dates.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
            }),
            datasets: [
                {
                    label: 'การเข้าชม',
                    data: visitsData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'ผู้มุ่งหวัง',
                    data: leadsData,
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(168, 85, 247)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: textColor,
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' ครั้ง';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Chart 2: Device Pie Chart
    const deviceCtx = document.getElementById('deviceChart').getContext('2d');

    const deviceData = @json($deviceBreakdown);
    const deviceLabels = deviceData.map(d => {
        const labels = {
            'mobile': 'มือถือ',
            'desktop': 'คอมพิวเตอร์',
            'tablet': 'แท็บเล็ต'
        };
        return labels[d.device_type] || d.device_type || 'ไม่ระบุ';
    });
    const deviceCounts = deviceData.map(d => d.count);

    new Chart(deviceCtx, {
        type: 'doughnut',
        data: {
            labels: deviceLabels,
            datasets: [{
                data: deviceCounts,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',   // Blue
                    'rgba(168, 85, 247, 0.8)',   // Purple
                    'rgba(236, 72, 153, 0.8)',   // Pink
                ],
                borderColor: [
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                ],
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
