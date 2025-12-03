{{--
/**
 * หน้าเอกสาร Enterprise Security
 *
 * แสดงรายละเอียดระบบความปลอดภัยระดับ Enterprise
 * ออกแบบให้มีความน่าเชื่อถือ เป็นมืออาชีพ
 *
 * @version 1.0.0
 * @created 2024-12-03
 */
--}}

@extends('layouts.landing')

@section('title', $pageTitle)

@section('meta_description', 'TP-Affiliate Pro - ความปลอดภัยระดับ Enterprise พร้อม 2FA, SSL, IP Whitelist, DDoS Protection และ License System')

@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
@endphp

@section('content')

{{-- Hero Section --}}
<section class="relative py-20 lg:py-32 bg-gradient-to-br from-cyan-900 via-blue-900 to-indigo-900 overflow-hidden">
    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
    </div>

    {{-- Shield Icon Animation --}}
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-5">
        <i class="fas fa-shield-alt text-[400px]"></i>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-slate-400">
                <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">หน้าแรก</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-white">Enterprise Security</li>
            </ol>
        </nav>

        <div class="text-center max-w-4xl mx-auto">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 rounded-full mb-6">
                <i class="fas fa-shield-alt text-cyan-400"></i>
                <span class="text-white font-medium">เอกสารทางการ</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                Enterprise Security
            </h1>
            <p class="text-xl text-cyan-100 leading-relaxed mb-8">
                ความปลอดภัยระดับสถาบันการเงิน
                <br class="hidden md:block">
                ปกป้องข้อมูลและเงินของคุณด้วยเทคโนโลยีล่าสุด
            </p>

            {{-- Version Info --}}
            <div class="flex items-center justify-center gap-6 text-sm text-slate-400">
                <span><i class="fas fa-code-branch mr-2"></i>Version {{ $data['version'] }}</span>
                <span><i class="fas fa-calendar mr-2"></i>อัปเดต {{ $data['updated_at'] }}</span>
            </div>
        </div>
    </div>
</section>

{{-- Stats Section --}}
<section class="py-12 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($data['stats'] as $stat)
            <div class="text-center p-6 bg-cyan-50 dark:bg-slate-800 rounded-xl">
                <div class="w-12 h-12 mx-auto bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas {{ $stat['icon'] }} text-xl text-white"></i>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ $stat['value'] }}</div>
                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Security Systems Section --}}
<section class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ระบบความปลอดภัย
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                เทคโนโลยีป้องกันหลายชั้นที่ทำงานร่วมกัน
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($data['security_systems'] as $system)
            <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="h-2 bg-gradient-to-r {{ $system['color'] }}"></div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br {{ $system['color'] }} rounded-xl flex items-center justify-center">
                            <i class="fas {{ $system['icon'] }} text-2xl text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $system['name'] }}</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 mb-4 text-sm">{{ $system['description'] }}</p>
                    <ul class="space-y-2">
                        @foreach($system['features'] as $feature)
                        <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <i class="fas fa-check text-green-500 flex-shrink-0"></i>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Standards Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                มาตรฐานความปลอดภัย
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                เป็นไปตามมาตรฐานสากลและกฎหมายท้องถิ่น
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($data['standards'] as $standard)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 text-center border border-slate-200 dark:border-slate-700">
                <div class="w-16 h-16 mx-auto bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fas {{ $standard['icon'] }} text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $standard['name'] }}</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $standard['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Backup & Recovery Section --}}
<section class="py-16 lg:py-24 bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                Backup & Disaster Recovery
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                ข้อมูลของคุณปลอดภัยแม้เกิดเหตุไม่คาดฝัน
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($data['backup_recovery'] as $backup)
            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $backup['name'] }}</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ $backup['description'] }}</p>
                <div class="space-y-2 text-sm">
                    @if(isset($backup['frequency']))
                    <div class="flex justify-between">
                        <span class="text-slate-500">ความถี่</span>
                        <span class="font-medium text-cyan-600 dark:text-cyan-400">{{ $backup['frequency'] }}</span>
                    </div>
                    @endif
                    @if(isset($backup['retention']))
                    <div class="flex justify-between">
                        <span class="text-slate-500">เก็บข้อมูล</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $backup['retention'] }}</span>
                    </div>
                    @endif
                    @if(isset($backup['locations']))
                    <div class="flex justify-between">
                        <span class="text-slate-500">ที่ตั้ง</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $backup['locations'] }}</span>
                    </div>
                    @endif
                    @if(isset($backup['rto']))
                    <div class="flex justify-between">
                        <span class="text-slate-500">RTO</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ $backup['rto'] }}</span>
                    </div>
                    @endif
                    @if(isset($backup['rpo']))
                    <div class="flex justify-between">
                        <span class="text-slate-500">RPO</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ $backup['rpo'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Monitoring Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                การตรวจสอบและแจ้งเตือน
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                ระบบตรวจจับภัยคุกคามแบบ Real-time
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($data['monitoring_alerts'] as $monitor)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $monitor['name'] }}</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ $monitor['description'] }}</p>
                @if(isset($monitor['tools']))
                <div class="flex flex-wrap gap-2">
                    @foreach($monitor['tools'] as $tool)
                    <span class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 rounded text-xs">{{ $tool }}</span>
                    @endforeach
                </div>
                @endif
                @if(isset($monitor['channels']))
                <div class="flex flex-wrap gap-2">
                    @foreach($monitor['channels'] as $channel)
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded text-xs">{{ $channel }}</span>
                    @endforeach
                </div>
                @endif
                @if(isset($monitor['frequency']))
                <div class="text-sm mt-4">
                    <span class="text-slate-500">ความถี่:</span>
                    <span class="font-medium text-cyan-600 dark:text-cyan-400 ml-2">{{ $monitor['frequency'] }}</span>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Trust Section --}}
<section class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-24 h-24 mx-auto bg-gradient-to-br from-cyan-500 to-blue-600 rounded-3xl flex items-center justify-center mb-8">
            <i class="fas fa-shield-alt text-5xl text-white"></i>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-6">
            ความปลอดภัยคือสิ่งที่เราให้ความสำคัญที่สุด
        </h2>
        <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed mb-8">
            เราลงทุนในโครงสร้างพื้นฐานและเทคโนโลยีความปลอดภัยอย่างต่อเนื่อง
            เพื่อให้มั่นใจว่าข้อมูลและทรัพย์สินของคุณจะได้รับการปกป้องอย่างดีที่สุด
            ทีมผู้เชี่ยวชาญด้านความปลอดภัยของเราพร้อมดูแลระบบ 24/7
        </p>
        <div class="flex flex-wrap justify-center gap-6 text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                <span>ตรวจสอบความปลอดภัยทุก 3 เดือน</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                <span>ทีม Security ดูแล 24/7</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                <span>อัปเดต Patch ทันที</span>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-16 lg:py-24 bg-gradient-to-br from-cyan-600 to-blue-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-20 h-20 mx-auto bg-white/10 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-lock text-4xl text-white"></i>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            มั่นใจในความปลอดภัย
        </h2>
        <p class="text-xl text-cyan-100 mb-8">
            เริ่มใช้งานวันนี้ ข้อมูลของคุณปลอดภัย 100%
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-cyan-600 font-bold rounded-xl hover:bg-cyan-50 transition-colors">
                <i class="fas fa-rocket"></i>
                เริ่มต้นใช้งานฟรี
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 text-white font-semibold rounded-xl border border-white/20 hover:bg-white/20 transition-colors">
                <i class="fas fa-headset"></i>
                ติดต่อทีมขาย
            </a>
        </div>
    </div>
</section>

{{-- Back to Home --}}
<section class="py-8 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าแรก
        </a>
    </div>
</section>

@endsection
