{{--
/**
 * หน้าเอกสาร Multi-Currency Wallet
 *
 * แสดงรายละเอียดระบบกระเป๋าเงินหลายสกุล
 * ออกแบบให้มีความน่าเชื่อถือ เป็นมืออาชีพ
 *
 * @version 1.0.0
 * @created 2024-12-03
 */
--}}

@extends('layouts.landing')

@section('title', $pageTitle)

@section('meta_description', 'TP-Affiliate Pro - กระเป๋าเงินหลายสกุล THB, USD, Crypto พร้อมระบบถอนเงินหลายช่องทาง Bank, PromptPay, Crypto')

@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
@endphp

@section('content')

{{-- Hero Section --}}
<section class="relative py-20 lg:py-32 bg-gradient-to-br from-emerald-900 via-green-900 to-teal-900 overflow-hidden">
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
                <li class="text-white">Multi-Currency Wallet</li>
            </ol>
        </nav>

        <div class="text-center max-w-4xl mx-auto">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 rounded-full mb-6">
                <i class="fas fa-wallet text-emerald-400"></i>
                <span class="text-white font-medium">เอกสารทางการ</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                Multi-Currency Wallet
            </h1>
            <p class="text-xl text-emerald-100 leading-relaxed mb-8">
                กระเป๋าเงินหลายสกุล THB, USD, TPIX, Points
                <br class="hidden md:block">
                เติม-ถอนหลายช่องทาง ปลอดภัยระดับธนาคาร
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
            <div class="text-center p-6 bg-emerald-50 dark:bg-slate-800 rounded-xl">
                <div class="w-12 h-12 mx-auto bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas {{ $stat['icon'] }} text-xl text-white"></i>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ $stat['value'] }}</div>
                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Wallet Types Section --}}
<section class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ประเภทกระเป๋าเงิน
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                รองรับหลายสกุลเงินในกระเป๋าเดียว
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            @foreach($data['wallet_types'] as $wallet)
            <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="h-2 bg-gradient-to-r {{ $wallet['color'] }}"></div>
                <div class="p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br {{ $wallet['color'] }} rounded-2xl flex items-center justify-center">
                            <i class="fas {{ $wallet['icon'] }} text-2xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $wallet['name'] }}</h3>
                            <p class="text-slate-600 dark:text-slate-400">{{ $wallet['description'] }}</p>
                        </div>
                    </div>

                    <ul class="space-y-3">
                        @foreach($wallet['features'] as $feature)
                        <li class="flex items-center gap-3">
                            <div class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check text-xs text-green-600 dark:text-green-400"></i>
                            </div>
                            <span class="text-slate-700 dark:text-slate-300">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Deposit Methods Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ช่องทางการเติมเงิน
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                เติมเงินได้หลายช่องทาง สะดวก รวดเร็ว
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($data['deposit_methods'] as $method)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 text-center hover:shadow-lg transition-shadow border border-slate-200 dark:border-slate-700">
                <div class="w-16 h-16 mx-auto bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl flex items-center justify-center mb-4">
                    <i class="fas {{ $method['icon'] }} text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ $method['name'] }}</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ $method['description'] }}</p>
                <div class="flex justify-center gap-4 text-sm">
                    <div>
                        <span class="text-slate-500">ค่าธรรมเนียม</span>
                        <div class="font-bold text-emerald-600 dark:text-emerald-400">{{ $method['fee'] }}</div>
                    </div>
                    <div>
                        <span class="text-slate-500">เวลา</span>
                        <div class="font-bold text-slate-900 dark:text-white">{{ $method['time'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Withdrawal Methods Section --}}
<section class="py-16 lg:py-24 bg-slate-50 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ช่องทางการถอนเงิน
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                ถอนเงินได้ตลอด 24 ชั่วโมง
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach($data['withdrawal_methods'] as $method)
            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas {{ $method['icon'] }} text-xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $method['name'] }}</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ $method['description'] }}</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">ค่าธรรมเนียม</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $method['fee'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">เวลาดำเนินการ</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $method['time'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">ขั้นต่ำ</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $method['min'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">สูงสุด</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $method['max'] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Security Features Section --}}
<section class="py-16 lg:py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                ความปลอดภัยระดับธนาคาร
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                ปกป้องเงินของคุณด้วยเทคโนโลยีล่าสุด
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($data['security_features'] as $feature)
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center">
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
<section class="py-16 lg:py-24 bg-gradient-to-br from-emerald-600 to-green-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-20 h-20 mx-auto bg-white/10 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-wallet text-4xl text-white"></i>
        </div>
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            เปิดกระเป๋าเงินวันนี้
        </h2>
        <p class="text-xl text-emerald-100 mb-8">
            ลงทะเบียนฟรี พร้อมใช้งานทันที
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-emerald-600 font-bold rounded-xl hover:bg-emerald-50 transition-colors">
                <i class="fas fa-wallet"></i>
                สร้างกระเป๋าเงิน
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
        <a href="{{ route('home') }}#features" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้าแรก
        </a>
    </div>
</section>

@endsection
