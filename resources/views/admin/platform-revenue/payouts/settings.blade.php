@extends('layouts.admin')

@section('title', 'Payout Settings')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-orange-900 to-gray-900 py-8 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.platform-revenue.payouts.index') }}" class="text-orange-400 hover:text-orange-300 mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>กลับรายการ Payout
            </a>
            <h1 class="text-4xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-cog text-orange-400"></i>
                Payout Settings
            </h1>
            <p class="text-white/60">ตั้งค่าการจ่ายเงินสำหรับแต่ละประเภทรายได้</p>
        </div>

        @foreach($settings as $setting)
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-6">
            <form method="POST" action="{{ route('admin.platform-revenue.payouts.settings.update', $setting) }}">
                @csrf
                @method('PUT')

                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-white">{{ $setting->name }}</h2>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}
                               class="w-5 h-5 rounded">
                        <span class="text-white">เปิดใช้งาน</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-white/60 text-sm block mb-2">โหมดการจ่าย</label>
                        <select name="payout_mode" class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white">
                            <option value="manual" {{ $setting->payout_mode === 'manual' ? 'selected' : '' }}>Manual (รออนุมัติ)</option>
                            <option value="auto_schedule" {{ $setting->payout_mode === 'auto_schedule' ? 'selected' : '' }}>Auto Schedule</option>
                            <option value="realtime" {{ $setting->payout_mode === 'realtime' ? 'selected' : '' }}>Realtime</option>
                            <option value="hybrid" {{ $setting->payout_mode === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-white/60 text-sm block mb-2">ต้องอนุมัติ</label>
                        <select name="requires_approval" class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white">
                            <option value="1" {{ $setting->requires_approval ? 'selected' : '' }}>ใช่</option>
                            <option value="0" {{ !$setting->requires_approval ? 'selected' : '' }}>ไม่</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-white/60 text-sm block mb-2">ยอดขั้นต่ำ (บาท)</label>
                        <input type="number" name="min_payout" value="{{ $setting->min_payout }}" step="0.01"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="text-white/60 text-sm block mb-2">ยอดสูงสุด (บาท)</label>
                        <input type="number" name="max_payout" value="{{ $setting->max_payout }}" step="0.01"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white"
                               placeholder="ไม่จำกัด">
                    </div>

                    <div>
                        <label class="text-white/60 text-sm block mb-2">ค่าธรรมเนียมคงที่ (บาท)</label>
                        <input type="number" name="fee_fixed" value="{{ $setting->fee_fixed }}" step="0.01"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="text-white/60 text-sm block mb-2">ค่าธรรมเนียม (%)</label>
                        <input type="number" name="fee_percentage" value="{{ $setting->fee_percentage }}" step="0.01" max="100"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="text-white/60 text-sm block mb-2">ระยะพักเงิน (วัน)</label>
                        <input type="number" name="holding_days" value="{{ $setting->holding_days }}" min="0"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl text-white">
                        <p class="text-white/40 text-sm mt-1">จำนวนวันที่ต้องรอก่อนถอนได้</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-orange-500 rounded-xl text-white hover:bg-orange-600 transition">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endsection
