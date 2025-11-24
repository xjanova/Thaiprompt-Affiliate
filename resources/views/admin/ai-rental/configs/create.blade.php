@extends('layouts.admin-v3')

@section('title', 'เพิ่ม Configuration ใหม่')

@section('content')
<div class="space-y-6">
    {{-- Back Button --}}
    <div>
        <a href="{{ route('admin.ai-rental.configs.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 glass-neu text-white/80 hover:text-white rounded-lg transition">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้ารายการ
        </a>
    </div>

    {{-- Page Header --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h1 class="text-3xl font-bold text-white flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-teal-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-plus text-2xl text-white"></i>
            </div>
            เพิ่ม Configuration ใหม่
        </h1>
        <p class="text-white/70 mt-2">กรอกข้อมูล API Key และการตั้งค่า Cloud Provider</p>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.ai-rental.configs.store') }}" method="POST">
        @csrf

        @include('admin.ai-rental.configs._form', ['config' => null, 'providers' => $providers])

        {{-- Submit Buttons --}}
        <div class="glass-fusion rounded-2xl p-6 border border-white/30">
            <div class="flex gap-4">
                <button type="submit"
                        class="px-8 py-4 bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold rounded-xl hover:scale-105 transition shadow-lg text-lg">
                    <i class="fas fa-save"></i> บันทึก Configuration
                </button>
                <a href="{{ route('admin.ai-rental.configs.index') }}"
                   class="px-8 py-4 glass-neu text-white font-bold rounded-xl hover:bg-white/20 transition text-lg">
                    <i class="fas fa-times"></i> ยกเลิก
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
