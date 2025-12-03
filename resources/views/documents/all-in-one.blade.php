{{--
/**
 * หน้าเอกสาร All-in-One Platform
 *
 * แสดงรายละเอียดทุกระบบที่รวมอยู่ในแพลตฟอร์ม
 * ออกแบบให้มีความน่าเชื่อถือ เป็นมืออาชีพ
 *
 * @version 1.0.0
 * @created 2024-12-03
 */
--}}

@extends('layouts.landing')

@section('title', $pageTitle)

@section('meta_description', 'TP-Affiliate Pro - แพลตฟอร์ม All-in-One รวม 20+ ระบบไว้ในที่เดียว Affiliate, MLM, E-Commerce, AI Bot และอื่นๆ')

@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
@endphp

@section('content')

{{-- Hero Section --}}
<section class="relative py-20 lg:py-32 bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 overflow-hidden">
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
                <li class="text-white">All-in-One Platform</li>
            </ol>
        </nav>

        <div class="text-center max-w-4xl mx-auto">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 rounded-full mb-6">
                <i class="fas fa-layer-group text-blue-400"></i>
                <span class="text-white font-medium">เอกสารทางการ</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                All-in-One Platform
            </h1>
            <p class="text-xl text-slate-300 leading-relaxed mb-8">
                ระบบครบวงจรที่รวม 20+ ระบบไว้ในแพลตฟอร์มเดียว
                <br class="hidden md:block">
                ไม่ต้องซื้อแยก ไม่ต้อง Integrate เพิ่ม ทุกอย่างทำงานร่วมกันได้ทันที
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
            <div class="text-center p-6 bg-slate-50 dark:bg-slate-800 rounded-xl">
                <div class="w-12 h-12 mx-auto bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas {{ $stat['icon'] }} text-xl text-white"></i>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ $stat['value'] }}</div>
                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Benefits Section --}}
<section class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ทำไมต้องเลือก All-in-One Platform?
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                ข้อดีที่คุณจะได้รับจากการใช้แพลตฟอร์มเดียว
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($data['benefits'] as $benefit)
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas {{ $benefit['icon'] }} text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $benefit['title'] }}</h3>
                <p class="text-slate-600 dark:text-slate-400">{{ $benefit['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Systems Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ระบบทั้งหมดในแพลตฟอร์ม
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                20+ ระบบที่ครอบคลุมทุกความต้องการของธุรกิจ
            </p>
        </div>

        <div class="space-y-12">
            @foreach($data['systems'] as $category)
            <div>
                {{-- Category Header --}}
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br {{ $category['color'] }} rounded-xl flex items-center justify-center">
                        <i class="fas {{ $category['icon'] }} text-xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $category['category'] }}</h3>
                </div>

                {{-- Systems Grid --}}
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($category['items'] as $system)
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 hover:shadow-lg transition-shadow border border-slate-200 dark:border-slate-700">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $system['name'] }}</h4>
                        <p class="text-slate-600 dark:text-slate-400 mb-4">{{ $system['description'] }}</p>
                        <ul class="space-y-2">
                            @foreach($system['features'] as $feature)
                            <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <i class="fas fa-check text-green-500"></i>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-16 lg:py-24 bg-gradient-to-br from-blue-600 to-indigo-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            พร้อมเริ่มต้นใช้งานหรือยัง?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
            ลงทะเบียนฟรีวันนี้ เริ่มใช้งานได้ทันที
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition-colors">
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
        <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าแรก
        </a>
    </div>
</section>

@endsection
