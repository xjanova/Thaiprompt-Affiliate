@extends('layouts.admin-v3')

@section('title', 'TPIX Settings')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-slate-600 via-gray-700 to-zinc-800 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                        <span class="text-5xl">⚙️</span>
                        <span>TPIX Settings</span>
                    </h1>
                    <p class="text-gray-300">การตั้งค่าสกุลเงิน TPIX และระบบ Blockchain</p>
                </div>
                <a href="{{ route('admin.tpix.dashboard') }}" class="px-4 py-2 glass-fusion bg-opacity-20 hover:bg-opacity-30 rounded-xl text-sm font-semibold transition">
                    ← Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Settings Form --}}
    <form action="{{ route('admin.tpix.update-settings') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Price Settings --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <span>💰</span>
                <span>ราคาและอัตราแลกเปลี่ยน</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Manual Price THB --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        ราคา TPIX (บาท)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            name="manual_price_thb"
                            value="{{ old('manual_price_thb', $tpixCurrency->manual_price_thb ?? 1) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">฿</span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">ราคา 1 TPIX เป็นเงินบาท</p>
                    @error('manual_price_thb')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Exchange Fee --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        ค่าธรรมเนียมแลกเปลี่ยน (%)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            name="exchange_fee_percentage"
                            value="{{ old('exchange_fee_percentage', $tpixCurrency->exchange_fee_percentage ?? 1) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">%</span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">เปอร์เซ็นต์ค่าธรรมเนียมการแลกเปลี่ยน</p>
                </div>
            </div>
        </div>

        {{-- Deposit Settings --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <span>📥</span>
                <span>การฝาก (Deposit)</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Min Deposit --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        จำนวนฝากขั้นต่ำ
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="min_deposit"
                            value="{{ old('min_deposit', $tpixCurrency->min_deposit ?? 10) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">TPIX</span>
                        </div>
                    </div>
                </div>

                {{-- Deposit Enabled --}}
                <div class="flex items-center pt-8">
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="deposit_enabled"
                            value="1"
                            {{ old('deposit_enabled', $tpixCurrency->deposit_enabled ?? true) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-700 border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 focus:ring-2"
                        >
                        <span class="ml-3 text-sm font-semibold text-gray-900 dark:text-white">
                            เปิดใช้งานการฝาก
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Withdrawal Settings --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <span>📤</span>
                <span>การถอน (Withdrawal)</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Min Withdrawal --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        จำนวนถอนขั้นต่ำ
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="min_withdrawal"
                            value="{{ old('min_withdrawal', $tpixCurrency->min_withdrawal ?? 10) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">TPIX</span>
                        </div>
                    </div>
                </div>

                {{-- Max Withdrawal --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        จำนวนถอนสูงสุด
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="max_withdrawal"
                            value="{{ old('max_withdrawal', $tpixCurrency->max_withdrawal ?? '') }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            placeholder="ไม่จำกัด"
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">TPIX</span>
                        </div>
                    </div>
                </div>

                {{-- Withdrawal Fee --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        ค่าธรรมเนียมการถอน
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="withdrawal_fee"
                            value="{{ old('withdrawal_fee', $tpixCurrency->withdrawal_fee ?? 0) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">TPIX</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="withdrawal_enabled"
                        value="1"
                        {{ old('withdrawal_enabled', $tpixCurrency->withdrawal_enabled ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-700 border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 focus:ring-2"
                    >
                    <span class="ml-3 text-sm font-semibold text-gray-900 dark:text-white">
                        เปิดใช้งานการถอน
                    </span>
                </label>
            </div>
        </div>

        {{-- Blockchain Settings --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <span>⛓️</span>
                <span>การตั้งค่า Blockchain</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Confirmations Required --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        จำนวน Block Confirmations
                    </label>
                    <input
                        type="number"
                        min="1"
                        max="100"
                        name="confirmations_required"
                        value="{{ old('confirmations_required', $tpixCurrency->confirmations_required ?? 3) }}"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        required
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">จำนวนยืนยันก่อนถือว่าธุรกรรมสำเร็จ</p>
                </div>

                {{-- Exchange Enabled --}}
                <div class="flex items-center pt-8">
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="exchange_enabled"
                            value="1"
                            {{ old('exchange_enabled', $tpixCurrency->exchange_enabled ?? true) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-700 border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 focus:ring-2"
                        >
                        <span class="ml-3 text-sm font-semibold text-gray-900 dark:text-white">
                            เปิดใช้งานการแลกเปลี่ยน
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Payment Gateway --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6 mb-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <span>💳</span>
                <span>Payment Gateway</span>
            </h2>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="payment_gateway_enabled"
                        value="1"
                        {{ old('payment_gateway_enabled', $tpixCurrency->payment_gateway_enabled ?? false) ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-700 border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 focus:ring-2"
                    >
                    <span class="ml-3 text-sm font-semibold text-gray-900 dark:text-white">
                        เปิดใช้งาน TPIX Payment Gateway
                    </span>
                </label>
            </div>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                อนุญาตให้ใช้ TPIX เป็นช่องทางชำระเงินในระบบ
            </p>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center gap-4">
            <button
                type="submit"
                class="px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition duration-300"
            >
                <span class="flex items-center gap-2">
                    <span>💾</span>
                    <span>บันทึกการตั้งค่า</span>
                </span>
            </button>
            <a
                href="{{ route('admin.tpix.dashboard') }}"
                class="px-6 py-4 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 font-semibold rounded-xl transition"
            >
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
