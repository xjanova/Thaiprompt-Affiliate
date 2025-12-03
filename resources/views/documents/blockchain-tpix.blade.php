{{--
/**
 * หน้าเอกสาร Blockchain & TPIX Token
 *
 * Whitepaper ฉบับสมบูรณ์สำหรับ TPIX
 * รวมเอกสารทั้งหมดที่เกี่ยวข้อง
 *
 * @version 2.0.0
 * @created 2024-12-03
 */
--}}

@extends('layouts.landing')

@section('title', $pageTitle)

@section('meta_description', 'TPIX Whitepaper - Native Cryptocurrency บน Blockchain ของตัวเอง รองรับ Staking, DEX, และระบบ Rewards สำหรับ Affiliate')

@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
@endphp

@push('styles')
<style>
    .gradient-tpix { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%); }
    .text-gradient-tpix { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
</style>
@endpush

@section('content')

{{-- Hero Section --}}
<section class="relative py-20 lg:py-32 bg-gradient-to-br from-amber-900 via-orange-900 to-yellow-900 overflow-hidden">
    {{-- Animated Background --}}
    <div class="absolute inset-0">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 50px 50px;"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-amber-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-slate-400">
                <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">หน้าแรก</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-white">TPIX Whitepaper</li>
            </ol>
        </nav>

        <div class="text-center max-w-4xl mx-auto">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 rounded-full mb-6">
                <i class="fas fa-coins text-amber-400"></i>
                <span class="text-white font-medium">Whitepaper ฉบับสมบูรณ์</span>
                <span class="px-2 py-0.5 bg-amber-500 text-white text-xs font-bold rounded">v{{ $data['version'] }}</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                TPIX Token
                <span class="block text-2xl md:text-3xl font-normal text-amber-200 mt-2">{{ $data['full_name'] }}</span>
            </h1>
            <p class="text-xl text-amber-100 leading-relaxed mb-8">
                Native Cryptocurrency บน Blockchain ของตัวเอง
                <br class="hidden md:block">
                รองรับ Staking, DEX, และระบบ Rewards สำหรับ Affiliate
            </p>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
                @foreach($data['executive_summary']['highlights'] as $highlight)
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-sm text-amber-200">{{ $highlight['label'] }}</div>
                    <div class="text-lg font-bold text-white">{{ $highlight['value'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Table of Contents --}}
<section class="py-8 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center gap-4 overflow-x-auto pb-2">
            <a href="#executive-summary" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 whitespace-nowrap">บทสรุป</a>
            <a href="#blockchain" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 whitespace-nowrap">Blockchain</a>
            <a href="#tokenomics" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 whitespace-nowrap">Tokenomics</a>
            <a href="#use-cases" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 whitespace-nowrap">Use Cases</a>
            <a href="#roadmap" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 whitespace-nowrap">Roadmap</a>
            <a href="#tech-stack" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 whitespace-nowrap">เทคโนโลยี</a>
            <a href="#team" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 whitespace-nowrap">ทีมงาน</a>
        </div>
    </div>
</section>

{{-- Executive Summary --}}
<section id="executive-summary" class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <span class="inline-block px-4 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium mb-4">Section 1</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">บทสรุปผู้บริหาร</h2>
                <p class="text-lg text-slate-600 dark:text-slate-400">{{ $data['executive_summary']['title'] }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 shadow-lg border border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                    <i class="fas fa-bullseye text-amber-500 mr-2"></i>
                    วิสัยทัศน์
                </h3>
                <p class="text-slate-600 dark:text-slate-400 mb-8 leading-relaxed">{{ $data['executive_summary']['vision'] }}</p>

                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                    <i class="fas fa-rocket text-amber-500 mr-2"></i>
                    พันธกิจ
                </h3>
                <ul class="space-y-3 mb-8">
                    @foreach($data['executive_summary']['mission'] as $mission)
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fas fa-check text-xs text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <span class="text-slate-700 dark:text-slate-300">{{ $mission }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Blockchain Section --}}
<section id="blockchain" class="py-16 lg:py-24 bg-white dark:bg-slate-900 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium mb-4">Section 2</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">TPIX Blockchain</h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">ข้อมูลทางเทคนิคของ Blockchain</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($data['blockchain'] as $key => $value)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                <div class="text-lg font-bold text-slate-900 dark:text-white">{{ $value }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Tokenomics Section --}}
<section id="tokenomics" class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium mb-4">Section 3</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">Tokenomics</h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">การกระจาย Token ทั้งหมด {{ number_format($data['tokenomics']['total_supply']) }} TPIX</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            {{-- Chart --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 shadow-lg border border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 text-center">การกระจาย Token</h3>
                <div class="relative pt-[100%]">
                    <canvas id="tokenomicsChart" class="absolute inset-0"></canvas>
                </div>
            </div>

            {{-- Distribution List --}}
            <div class="space-y-4">
                @foreach($data['tokenomics']['distribution'] as $item)
                <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-bold text-slate-900 dark:text-white">{{ $item['name'] }}</h4>
                        <span class="text-lg font-bold" style="color: {{ $item['color'] }}">{{ $item['percentage'] }}%</span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">{{ $item['description'] }}</p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">จำนวน Token</span>
                        <span class="font-mono text-slate-900 dark:text-white">{{ number_format($item['amount']) }} TPIX</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mt-2">
                        <div class="h-2 rounded-full" style="width: {{ $item['percentage'] }}%; background-color: {{ $item['color'] }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Use Cases Section --}}
<section id="use-cases" class="py-16 lg:py-24 bg-white dark:bg-slate-900 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium mb-4">Section 4</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">Use Cases</h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">การใช้งาน TPIX ในระบบต่างๆ</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($data['use_cases'] as $useCase)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow">
                <div class="h-2 bg-gradient-to-r {{ $useCase['color'] }}"></div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br {{ $useCase['color'] }} rounded-xl flex items-center justify-center">
                            <i class="fas {{ $useCase['icon'] }} text-xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $useCase['title'] }}</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 mb-4 text-sm">{{ $useCase['description'] }}</p>
                    <ul class="space-y-2">
                        @foreach($useCase['features'] as $feature)
                        <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <i class="fas fa-check text-green-500 mt-1 flex-shrink-0"></i>
                            <span>{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Roadmap Section --}}
<section id="roadmap" class="py-16 lg:py-24 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-slate-800 dark:to-slate-900 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium mb-4">Section 5</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">Roadmap 2023-2026</h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">แผนการพัฒนา TPIX</p>
        </div>

        <div class="space-y-6">
            @foreach($data['roadmap'] as $phase)
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">
                    <div class="flex items-center gap-3">
                        @if($phase['status'] === 'completed')
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-600 dark:text-green-400"></i>
                        </div>
                        @elseif($phase['status'] === 'in_progress')
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-amber-600 dark:text-amber-400"></i>
                        </div>
                        @else
                        <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center">
                            <i class="fas fa-clock text-slate-400"></i>
                        </div>
                        @endif
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $phase['phase'] }}</h3>
                            <p class="text-sm text-slate-500">{{ $phase['quarter'] }}</p>
                        </div>
                    </div>
                    <div class="md:ml-auto">
                        @if($phase['status'] === 'completed')
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-sm font-medium">เสร็จสิ้น</span>
                        @elseif($phase['status'] === 'in_progress')
                        <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium">กำลังดำเนินการ</span>
                        @else
                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-full text-sm font-medium">วางแผน</span>
                        @endif
                    </div>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-2 ml-13">
                    @foreach($phase['milestones'] as $milestone)
                    <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <span>{{ $milestone }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Technology Stack Section --}}
<section id="tech-stack" class="py-16 lg:py-24 bg-white dark:bg-slate-900 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium mb-4">Section 6</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">Technology Stack</h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">เทคโนโลยีที่ใช้ในการพัฒนา</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($data['tech_stack'] as $category => $items)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 capitalize">{{ str_replace('_', ' ', $category) }}</h3>
                <ul class="space-y-3">
                    @foreach($items as $name => $description)
                    <li class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-amber-500 rounded-full mt-2"></div>
                        <div>
                            <span class="font-medium text-slate-900 dark:text-white">{{ $name }}</span>
                            <span class="text-slate-500 dark:text-slate-400"> - {{ $description }}</span>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Team Section --}}
<section id="team" class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-sm font-medium mb-4">Section 7</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">ทีมผู้พัฒนา</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
            @foreach($data['team'] as $member)
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 text-center shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-amber-500 to-orange-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-users text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ $member['name'] }}</h3>
                <p class="text-amber-600 dark:text-amber-400 text-sm font-medium mb-3">{{ $member['role'] }}</p>
                <p class="text-slate-600 dark:text-slate-400 text-sm">{{ $member['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Legal Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-8 border border-amber-200 dark:border-amber-800">
            <h3 class="text-xl font-bold text-amber-800 dark:text-amber-200 mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                ข้อจำกัดความรับผิดชอบ
            </h3>
            <p class="text-amber-700 dark:text-amber-300 mb-4">{{ $data['legal']['disclaimer'] }}</p>
            <p class="text-amber-700 dark:text-amber-300">{{ $data['legal']['risk_warning'] }}</p>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-16 lg:py-24 bg-gradient-to-br from-amber-600 to-orange-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-20 h-20 mx-auto bg-white/10 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-coins text-4xl text-white"></i>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            เริ่มต้นใช้งาน TPIX
        </h2>
        <p class="text-xl text-amber-100 mb-8">
            สมัครสมาชิกวันนี้ รับ TPIX ฟรี!
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-amber-600 font-bold rounded-xl hover:bg-amber-50 transition-colors">
                <i class="fas fa-wallet"></i>
                สร้างกระเป๋าเงิน
            </a>
            <a href="{{ route('tpix.whitepaper.index') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 text-white font-semibold rounded-xl border border-white/20 hover:bg-white/20 transition-colors">
                <i class="fas fa-file-pdf"></i>
                ดาวน์โหลด PDF
            </a>
        </div>
    </div>
</section>

{{-- Back to Home --}}
<section class="py-8 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าแรก
        </a>
    </div>
</section>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tokenomics Chart
    const ctx = document.getElementById('tokenomicsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: @json(array_column($data['tokenomics']['distribution'], 'name')),
                datasets: [{
                    data: @json(array_column($data['tokenomics']['distribution'], 'percentage')),
                    backgroundColor: @json(array_column($data['tokenomics']['distribution'], 'color')),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
