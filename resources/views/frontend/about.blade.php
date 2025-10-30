@extends('layouts.app')

@section('title', 'เกี่ยวกับเรา')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        @endphp
        <h1 class="text-4xl font-bold text-gray-900 mb-8">เกี่ยวกับ {{ $appName }}</h1>

        <div class="prose max-w-none">
            <p class="text-lg text-gray-600 mb-4">
                {{ $appName }} เป็นระบบ Affiliate Marketing ที่ทันสมัย มืออาชีพ และพร้อมใช้งาน
                ออกแบบมาเพื่อให้ธุรกิจของคุณสามารถสร้างและจัดการโปรแกรม Affiliate ได้อย่างง่ายดาย
            </p>
        </div>
    </div>
</div>
@endsection
