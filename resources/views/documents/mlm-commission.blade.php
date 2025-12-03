{{--
/**
 * หน้าเอกสาร MLM & Commission System
 *
 * แสดงรายละเอียดระบบ MLM และคอมมิชชั่น
 * ออกแบบให้มีความน่าเชื่อถือ เป็นมืออาชีพ
 *
 * @version 1.0.0
 * @created 2024-12-03
 */
--}}

@extends('layouts.landing')

@section('title', $pageTitle)

@section('meta_description', 'TP-Affiliate Pro - ระบบ MLM และ Commission ระดับ Enterprise รองรับ Unilevel, Binary และ Hybrid พร้อมคำนวณคอมมิชชั่นอัตโนมัติ')

@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
@endphp

@section('content')

{{-- Hero Section --}}
<section class="relative py-20 lg:py-32 bg-gradient-to-br from-pink-900 via-rose-900 to-red-900 overflow-hidden">
    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-slate-400">
                <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">หน้าแรก</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-white">MLM & Commission System</li>
            </ol>
        </nav>

        <div class="text-center max-w-4xl mx-auto">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 rounded-full mb-6">
                <i class="fas fa-sitemap text-pink-400"></i>
                <span class="text-white font-medium">เอกสารทางการ</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                MLM & Commission System
            </h1>
            <p class="text-xl text-pink-100 leading-relaxed mb-8">
                ระบบ Multi-Level Marketing ระดับ Enterprise
                <br class="hidden md:block">
                รองรับหลายแผน คำนวณคอมมิชชั่นอัตโนมัติ ไม่จำกัดชั้น
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
            <div class="text-center p-6 bg-pink-50 dark:bg-slate-800 rounded-xl">
                <div class="w-12 h-12 mx-auto bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas {{ $stat['icon'] }} text-xl text-white"></i>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ $stat['value'] }}</div>
                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Plans Section --}}
<section class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                แผนการตลาดที่รองรับ
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                เลือกแผนที่เหมาะกับธุรกิจของคุณ
            </p>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            @foreach($data['plans'] as $plan)
            <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="h-2 bg-gradient-to-r {{ $plan['color'] }}"></div>
                <div class="p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br {{ $plan['color'] }} rounded-2xl flex items-center justify-center">
                            <i class="fas {{ $plan['icon'] }} text-2xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $plan['name'] }}</h3>
                        </div>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">{{ $plan['description'] }}</p>

                    <h4 class="text-sm font-semibold text-slate-500 uppercase mb-3">ฟีเจอร์หลัก</h4>
                    <ul class="space-y-2 mb-6">
                        @foreach($plan['features'] as $feature)
                        <li class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <i class="fas fa-check text-green-500"></i>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>

                    @if(isset($plan['commission_structure']))
                    <h4 class="text-sm font-semibold text-slate-500 uppercase mb-3">โครงสร้างคอมมิชชั่น</h4>
                    <div class="space-y-2">
                        @foreach($plan['commission_structure'] as $commission)
                        <div class="flex justify-between items-center p-2 bg-slate-50 dark:bg-slate-800 rounded">
                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                @if(isset($commission['level']))
                                    Level {{ $commission['level'] }}
                                @else
                                    {{ $commission['type'] }}
                                @endif
                            </span>
                            <span class="font-bold text-pink-600 dark:text-pink-400">{{ $commission['percentage'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Commission Types Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ประเภทคอมมิชชั่น
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                หลายช่องทางรายได้สำหรับ Affiliate
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($data['commission_types'] as $type)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-shadow">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                        <i class="fas {{ $type['icon'] }} text-xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">{{ $type['name'] }}</h3>
                        <span class="text-pink-600 dark:text-pink-400 font-bold">{{ $type['percentage'] }}</span>
                    </div>
                </div>
                <p class="text-slate-600 dark:text-slate-400 text-sm">{{ $type['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Rank System Section --}}
<section class="py-16 lg:py-24 bg-gradient-to-br from-pink-50 to-rose-50 dark:from-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ระบบ Rank
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                เลื่อน Rank เพื่อรับสิทธิประโยชน์มากขึ้น
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($data['ranks'] as $rank)
            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 text-center shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-16 h-16 mx-auto bg-gradient-to-br {{ $rank['color'] }} rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-crown text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $rank['name'] }}</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">{{ $rank['requirement'] }}</p>
                <div class="text-sm text-pink-600 dark:text-pink-400 font-medium">{{ $rank['benefits'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Features Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ฟีเจอร์ระบบ
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                เครื่องมือที่ช่วยให้บริหารสายงานได้ง่าย
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($data['features'] as $feature)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                        <i class="fas {{ $feature['icon'] }} text-xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $feature['name'] }}</h3>
                </div>
                <p class="text-slate-600 dark:text-slate-400">{{ $feature['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-16 lg:py-24 bg-gradient-to-br from-pink-600 to-rose-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-20 h-20 mx-auto bg-white/10 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-sitemap text-4xl text-white"></i>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            พร้อมสร้างสายงานแล้วหรือยัง?
        </h2>
        <p class="text-xl text-pink-100 mb-8">
            สมัครสมาชิกวันนี้ รับ Referral Link ทันที
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-pink-600 font-bold rounded-xl hover:bg-pink-50 transition-colors">
                <i class="fas fa-user-plus"></i>
                สมัครสมาชิก
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 text-white font-semibold rounded-xl border border-white/20 hover:bg-white/20 transition-colors">
                <i class="fas fa-headset"></i>
                สอบถามเพิ่มเติม
            </a>
        </div>
    </div>
</section>

{{-- Back to Home --}}
<section class="py-8 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าแรก
        </a>
    </div>
</section>

@endsection
