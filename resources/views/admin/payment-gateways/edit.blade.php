@extends('layouts.admin')

@section('title', 'ตั้งค่า ' . $paymentGateway->name)

@section('content')
<div class="container-fluid px-4 py-6" x-data="{
    testMode: {{ old('test_mode', $paymentGateway->test_mode) ? 'true' : 'false' }},
    isActive: {{ old('is_active', $paymentGateway->is_active) ? 'true' : 'false' }},
    supportsDeposit: {{ old('supports_deposit', $paymentGateway->supports_deposit) ? 'true' : 'false' }},
    supportsWithdrawal: {{ old('supports_withdrawal', $paymentGateway->supports_withdrawal) ? 'true' : 'false' }}
}">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($paymentGateway->logo_url)
                        <img src="{{ $paymentGateway->logo_url }}" alt="{{ $paymentGateway->name }}" class="w-20 h-20 object-contain bg-white rounded-2xl p-3 shadow-lg">
                    @else
                        <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-5xl shadow-lg">
                            {{ $paymentGateway->icon ?? '💳' }}
                        </div>
                    @endif
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2">{{ $paymentGateway->name }}</h1>
                        <p class="text-purple-100 text-lg">{{ $paymentGateway->category_label }}</p>
                    </div>
                </div>

                <a href="{{ route('admin.payment-gateways.index') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 animate-fade-in">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center mr-3">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div>
                    <p class="font-semibold text-green-900">สำเร็จ!</p>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 animate-fade-in">
            <div class="flex items-start">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3 flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div>
                    <p class="font-semibold text-red-900 mb-2">พบข้อผิดพลาด!</p>
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.payment-gateways.update', $paymentGateway) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Settings (Left - 2 columns) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- System Control -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-power-off mr-2"></i>
                            การควบคุมระบบ
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-gray-900 mb-1">เปิดใช้งาน Payment Gateway</h4>
                                <p class="text-sm text-gray-600">อนุญาตให้ผู้ใช้ชำระเงินผ่านช่องทางนี้</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="is_active" name="is_active" value="1" class="sr-only peer"
                                    {{ old('is_active', $paymentGateway->is_active) ? 'checked' : '' }}
                                    x-model="isActive">
                                <div class="w-16 h-8 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-indigo-600 peer-checked:to-purple-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="text-base font-bold text-gray-900 mb-1">โหมดทดสอบ</h4>
                                <p class="text-sm text-gray-600">ใช้ข้อมูลทดสอบสำหรับการพัฒนา</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="test_mode" value="1" class="sr-only peer"
                                    {{ old('test_mode', $paymentGateway->test_mode) ? 'checked' : '' }}
                                    x-model="testMode">
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-7 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-orange-500 peer-checked:to-red-600"></div>
                            </label>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="supports_deposit" name="supports_deposit" value="1"
                                       class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"
                                       {{ old('supports_deposit', $paymentGateway->supports_deposit) ? 'checked' : '' }}
                                       x-model="supportsDeposit">
                                <label for="supports_deposit" class="ml-2 text-sm font-semibold text-gray-700">
                                    <i class="fas fa-arrow-down text-blue-500 mr-1"></i>รองรับการฝากเงิน
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="supports_withdrawal" name="supports_withdrawal" value="1"
                                       class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500"
                                       {{ old('supports_withdrawal', $paymentGateway->supports_withdrawal) ? 'checked' : '' }}
                                       x-model="supportsWithdrawal">
                                <label for="supports_withdrawal" class="ml-2 text-sm font-semibold text-gray-700">
                                    <i class="fas fa-arrow-up text-purple-500 mr-1"></i>รองรับการถอนเงิน
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gateway Configuration -->
                @if(view()->exists('admin.payment-gateways.partials.' . $paymentGateway->code))
                    @include('admin.payment-gateways.partials.' . $paymentGateway->code)
                @else
                    @include('admin.payment-gateways.partials.default')
                @endif

                <!-- Fees Configuration -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-percentage mr-2"></i>
                            ค่าธรรมเนียม
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Deposit Fee -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    ค่าธรรมเนียมการฝาก
                                </label>
                                <div class="flex gap-2">
                                    <select name="fees[deposit_fee_type]" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="percentage" {{ old('fees.deposit_fee_type', $paymentGateway->fees['deposit_fee_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์</option>
                                        <option value="fixed" {{ old('fees.deposit_fee_type', $paymentGateway->fees['deposit_fee_type'] ?? 'percentage') === 'fixed' ? 'selected' : '' }}>คงที่</option>
                                    </select>
                                    <input type="number" name="fees[deposit_fee_amount]" step="0.01"
                                           value="{{ old('fees.deposit_fee_amount', $paymentGateway->fees['deposit_fee_amount'] ?? 0) }}"
                                           class="w-32 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <!-- Withdrawal Fee -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    ค่าธรรมเนียมการถอน
                                </label>
                                <div class="flex gap-2">
                                    <select name="fees[withdrawal_fee_type]" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="percentage" {{ old('fees.withdrawal_fee_type', $paymentGateway->fees['withdrawal_fee_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์</option>
                                        <option value="fixed" {{ old('fees.withdrawal_fee_type', $paymentGateway->fees['withdrawal_fee_type'] ?? 'percentage') === 'fixed' ? 'selected' : '' }}>คงที่</option>
                                    </select>
                                    <input type="number" name="fees[withdrawal_fee_amount]" step="0.01"
                                           value="{{ old('fees.withdrawal_fee_amount', $paymentGateway->fees['withdrawal_fee_amount'] ?? 0) }}"
                                           class="w-32 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                           placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limits Configuration -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-600 to-orange-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-ruler mr-2"></i>
                            ขีดจำกัด
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Min Deposit -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-arrow-down text-blue-500 mr-1"></i>
                                    ฝากขั้นต่ำ (บาท)
                                </label>
                                <input type="number" name="limits[min_deposit]" step="0.01"
                                       value="{{ old('limits.min_deposit', $paymentGateway->limits['min_deposit'] ?? 1) }}"
                                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Max Deposit -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-arrow-down text-blue-500 mr-1"></i>
                                    ฝากสูงสุด (บาท)
                                </label>
                                <input type="number" name="limits[max_deposit]" step="0.01"
                                       value="{{ old('limits.max_deposit', $paymentGateway->limits['max_deposit'] ?? 1000000) }}"
                                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Min Withdrawal -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-arrow-up text-purple-500 mr-1"></i>
                                    ถอนขั้นต่ำ (บาท)
                                </label>
                                <input type="number" name="limits[min_withdrawal]" step="0.01"
                                       value="{{ old('limits.min_withdrawal', $paymentGateway->limits['min_withdrawal'] ?? 100) }}"
                                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Max Withdrawal -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-arrow-up text-purple-500 mr-1"></i>
                                    ถอนสูงสุด (บาท)
                                </label>
                                <input type="number" name="limits[max_withdrawal]" step="0.01"
                                       value="{{ old('limits.max_withdrawal', $paymentGateway->limits['max_withdrawal'] ?? 100000) }}"
                                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar (Right - 1 column) -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Status Card -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl shadow-2xl p-6 text-white sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">สถานะ</h3>
                            <p class="text-sm text-purple-200">ข้อมูลปัจจุบัน</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">Gateway:</span>
                            <span class="px-3 py-1 bg-white/20 rounded-full text-xs font-bold">{{ $paymentGateway->code }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">สถานะ:</span>
                            @if($paymentGateway->is_active)
                                <span class="px-3 py-1 bg-green-400 rounded-full text-xs font-bold text-green-900">Active</span>
                            @else
                                <span class="px-3 py-1 bg-red-500 rounded-full text-xs font-bold">Inactive</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <span class="text-sm">การตั้งค่า:</span>
                            @if($paymentGateway->isConfigured())
                                <span class="px-3 py-1 bg-green-400 rounded-full text-xs font-bold text-green-900">ครบถ้วน</span>
                            @else
                                <span class="px-3 py-1 bg-yellow-400 rounded-full text-xs font-bold text-yellow-900">ไม่ครบ</span>
                            @endif
                        </div>

                        @if($paymentGateway->last_tested_at)
                            <div class="p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                                <span class="text-xs block mb-1">ทดสอบล่าสุด:</span>
                                <span class="text-sm font-bold">{{ $paymentGateway->last_tested_at->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6">
                        <button type="button" onclick="testConnection()"
                                class="w-full px-4 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg hover:shadow-xl font-bold">
                            <i class="fas fa-vial mr-2"></i>ทดสอบการเชื่อมต่อ
                        </button>
                    </div>
                </div>

                <!-- Setup Guide -->
                @if($paymentGateway->instructions)
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <i class="fas fa-book text-indigo-600"></i>
                            </div>
                            <h3 class="font-bold text-gray-900">คู่มือการตั้งค่า</h3>
                        </div>

                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($paymentGateway->instructions)) !!}
                        </div>
                    </div>
                @endif

                <!-- Help Link -->
                @if($paymentGateway->help_url)
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl border border-blue-200 p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                                <i class="fas fa-question-circle text-white"></i>
                            </div>
                            <h3 class="font-bold text-blue-900">ความช่วยเหลือ</h3>
                        </div>

                        <a href="{{ $paymentGateway->help_url }}" target="_blank"
                           class="flex items-center gap-2 p-3 bg-white rounded-lg hover:shadow-md transition-all group">
                            <i class="fas fa-external-link-alt text-blue-500"></i>
                            <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-600">เอกสารประกอบ</span>
                        </a>
                    </div>
                @endif>

            </div>
        </div>

        <!-- Save Button -->
        <div class="mt-8">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-info-circle text-indigo-500 text-xl"></i>
                        <p class="text-sm text-gray-600">
                            ตรวจสอบให้แน่ใจว่าข้อมูลถูกต้องก่อนบันทึก
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('admin.payment-gateways.index') }}"
                           class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-bold">
                            <i class="fas fa-times mr-2"></i>ยกเลิก
                        </a>
                        <button type="submit"
                                class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl font-bold">
                            <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>

<script>
async function testConnection() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...';
    btn.disabled = true;

    try {
        const response = await fetch('{{ route('admin.payment-gateways.test', $paymentGateway) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ ' + (data.message || 'ทดสอบการเชื่อมต่อสำเร็จ'));
        } else {
            alert('❌ ' + (data.message || 'ทดสอบการเชื่อมต่อล้มเหลว'));
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>
@endsection
