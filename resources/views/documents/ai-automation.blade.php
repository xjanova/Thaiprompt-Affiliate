{{--
/**
 * หน้าเอกสาร AI-Powered Automation
 *
 * แสดงรายละเอียดระบบ AI และ Automation ทั้งหมด
 * ออกแบบให้มีความน่าเชื่อถือ เป็นมืออาชีพ
 *
 * @version 1.0.0
 * @created 2024-12-03
 */
--}}

@extends('layouts.landing')

@section('title', $pageTitle)

@section('meta_description', 'TP-Affiliate Pro - ระบบ AI อัจฉริยะ ช่วยตอบแชท วิเคราะห์ข้อมูล และทำงานอัตโนมัติ 24/7 ลดต้นทุน เพิ่มประสิทธิภาพ')

@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
@endphp

@section('content')

{{-- Hero Section --}}
<section class="relative py-20 lg:py-32 bg-gradient-to-br from-purple-900 via-violet-900 to-indigo-900 overflow-hidden">
    {{-- Animated Background --}}
    <div class="absolute inset-0">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-slate-400">
                <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">หน้าแรก</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-white">AI-Powered Automation</li>
            </ol>
        </nav>

        <div class="text-center max-w-4xl mx-auto">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 rounded-full mb-6">
                <i class="fas fa-robot text-purple-400"></i>
                <span class="text-white font-medium">เอกสารทางการ</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                AI-Powered Automation
            </h1>
            <p class="text-xl text-slate-300 leading-relaxed mb-8">
                ระบบ AI อัจฉริยะที่ทำงานให้คุณ 24 ชั่วโมง
                <br class="hidden md:block">
                ลดต้นทุนพนักงาน เพิ่มประสิทธิภาพการทำงาน 3 เท่า
            </p>

            {{-- Version Info --}}
            <div class="flex items-center justify-center gap-6 text-sm text-slate-400">
                <span><i class="fas fa-code-branch mr-2"></i>Version {{ $data['version'] }}</span>
                <span><i class="fas fa-calendar mr-2"></i>อัปเดต {{ $data['updated_at'] }}</span>
            </div>
        </div>
    </div>
</section>

{{-- Benefits Stats --}}
<section class="py-12 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($data['benefits'] as $benefit)
            <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-slate-800 dark:to-slate-700 rounded-xl">
                <div class="text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">{{ $benefit['value'] }}</div>
                <div class="text-lg font-semibold text-slate-900 dark:text-white mb-1">{{ $benefit['title'] }}</div>
                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $benefit['description'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- AI Systems Section --}}
<section class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ระบบ AI ที่พร้อมใช้งาน
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                เลือกใช้งาน AI ที่เหมาะกับธุรกิจของคุณ
            </p>
        </div>

        <div class="space-y-8">
            @foreach($data['ai_systems'] as $system)
            <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="md:flex">
                    {{-- Left Side --}}
                    <div class="md:w-1/3 p-8 bg-gradient-to-br {{ $system['color'] }}">
                        <div class="text-center md:text-left">
                            <div class="w-20 h-20 mx-auto md:mx-0 bg-white/20 rounded-2xl flex items-center justify-center mb-6">
                                <i class="{{ $system['icon'] }} text-4xl text-white"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-3">{{ $system['name'] }}</h3>
                            <p class="text-white/80">{{ $system['description'] }}</p>

                            @if(isset($system['integrations']))
                            <div class="mt-6">
                                <p class="text-sm text-white/60 mb-2">เชื่อมต่อกับ:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($system['integrations'] as $integration)
                                    <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white">{{ $integration }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Right Side --}}
                    <div class="md:w-2/3 p-8">
                        <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">ฟีเจอร์หลัก</h4>
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach($system['features'] as $feature)
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i class="fas fa-check text-xs text-green-600 dark:text-green-400"></i>
                                </div>
                                <span class="text-slate-700 dark:text-slate-300">{{ $feature }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Technologies Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                เทคโนโลยี AI ที่เราใช้
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                ใช้เทคโนโลยี AI ล่าสุดจากบริษัทชั้นนำระดับโลก
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            @foreach($data['technologies'] as $tech)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 text-center hover:shadow-lg transition-shadow">
                <div class="w-14 h-14 mx-auto bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas {{ $tech['icon'] }} text-xl text-white"></i>
                </div>
                <h4 class="font-bold text-slate-900 dark:text-white mb-1">{{ $tech['name'] }}</h4>
                <p class="text-xs text-slate-600 dark:text-slate-400">{{ $tech['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-16 lg:py-24 bg-gradient-to-br from-purple-600 to-indigo-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-20 h-20 mx-auto bg-white/10 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-robot text-4xl text-white"></i>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            พร้อมให้ AI ทำงานแทนคุณ?
        </h2>
        <p class="text-xl text-purple-100 mb-8">
            เริ่มใช้งาน AI Chatbot และระบบอัตโนมัติได้ทันที
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-purple-600 font-bold rounded-xl hover:bg-purple-50 transition-colors">
                <i class="fas fa-rocket"></i>
                เริ่มต้นใช้งานฟรี
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
        <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าแรก
        </a>
    </div>
</section>

@endsection
