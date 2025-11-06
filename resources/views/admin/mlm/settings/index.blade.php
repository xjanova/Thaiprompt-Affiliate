@extends('layouts.admin')

@section('title', 'ตั้งค่า MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">ตั้งค่า MLM</h1>
        <p class="text-gray-600 mt-1">กำหนดค่าและตั้งค่าระบบ MLM (Premium Edition)</p>
    </div>

    <!-- Overpay Warning Alert -->
    @if(isset($currentCommissionPercentage))
    <div class="mb-6 rounded-lg p-4 border
        {{ $currentCommissionPercentage > 50 ? 'bg-red-50 border-red-200' : ($currentCommissionPercentage > 40 ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200') }}">
        <div class="flex items-start">
            <svg class="w-6 h-6 mt-0.5 mr-3
                {{ $currentCommissionPercentage > 50 ? 'text-red-500' : ($currentCommissionPercentage > 40 ? 'text-yellow-500' : 'text-green-500') }}"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($currentCommissionPercentage > 50)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                @elseif($currentCommissionPercentage > 40)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @endif
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold
                    {{ $currentCommissionPercentage > 50 ? 'text-red-800' : ($currentCommissionPercentage > 40 ? 'text-yellow-800' : 'text-green-800') }}">
                    ประมาณการเปอร์เซ็นต์คอมมิชชั่นรวม: {{ number_format($currentCommissionPercentage, 2) }}%
                </h3>
                <p class="text-sm mt-1
                    {{ $currentCommissionPercentage > 50 ? 'text-red-700' : ($currentCommissionPercentage > 40 ? 'text-yellow-700' : 'text-green-700') }}">
                    @if($currentCommissionPercentage > 50)
                        <strong>⚠️ อันตราย - Overpay!</strong> เปอร์เซ็นต์คอมมิชชั่นรวมเกิน 50% อาจทำให้ขาดทุน
                        <br>แนะนำ: ลดเปอร์เซ็นต์ Unilevel หรือ Binary เพื่อควบคุมต้นทุน
                    @elseif($currentCommissionPercentage > 40)
                        <strong>⚡ คำเตือน</strong> เปอร์เซ็นต์คอมมิชชั่นใกล้ขีดจำกัด กรุณาตรวจสอบการคำนวณอีกครั้ง
                    @else
                        <strong>✓ ปลอดภัย</strong> เปอร์เซ็นต์คอมมิชชั่นอยู่ในเกณฑ์ที่เหมาะสม
                    @endif
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">การตั้งค่าระบบ MLM</h3>
                <p class="text-sm text-blue-700 mt-1">
                    ค่าเหล่านี้ใช้ควบคุมการทำงานของระบบ MLM โปรดตรวจสอบให้แน่ใจก่อนทำการเปลี่ยนแปลง
                    การเปลี่ยนแปลงบางอย่างอาจส่งผลกระทบต่อการคำนวณคอมมิชชั่น
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.mlm.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        @forelse($settings as $group => $groupSettings)
        <!-- Settings Group -->
        <div class="bg-white rounded-xl shadow-lg mb-6">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-4 rounded-t-xl">
                <h2 class="text-lg font-semibold">{{ ucfirst(str_replace('_', ' ', $group)) }}</h2>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($groupSettings as $setting)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Setting Info -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-800 mb-1">
                                {{ $setting->key }}
                            </label>
                            <p class="text-sm text-gray-600">
                                {{ app()->getLocale() === 'th' && $setting->description_th
                                    ? $setting->description_th
                                    : $setting->description }}
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $setting->type === 'string' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $setting->type === 'number' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $setting->type === 'boolean' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $setting->type === 'json' ? 'bg-pink-100 text-pink-800' : '' }}">
                                    {{ $setting->type }}
                                </span>
                            </div>
                        </div>

                        <!-- Setting Input -->
                        <div class="flex items-center gap-2">
                            @if(!$setting->is_editable)
                                <!-- Read-only display for non-editable settings -->
                                <div class="w-full px-4 py-2 bg-gray-100 text-gray-600 rounded-lg border border-gray-300">
                                    @if($setting->type === 'boolean')
                                        {{ $setting->getTypedValue() ? 'เปิด' : 'ปิด' }}
                                    @elseif($setting->type === 'json')
                                        <pre class="font-mono text-xs">{{ $setting->value }}</pre>
                                    @else
                                        {{ $setting->value }}
                                    @endif
                                </div>
                                <span class="px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded-full whitespace-nowrap">
                                    🔒 Read-only
                                </span>
                            @else
                                <!-- Editable inputs -->
                                @if($setting->type === 'boolean')
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               name="settings[{{ $setting->key }}]"
                                               value="1"
                                               {{ $setting->getTypedValue() ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">
                                            {{ $setting->getTypedValue() ? 'เปิด' : 'ปิด' }}
                                        </span>
                                    </label>
                                @elseif($setting->type === 'integer' || $setting->type === 'decimal' || $setting->type === 'float')
                                    <input type="number"
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ $setting->value }}"
                                           step="{{ $setting->type === 'integer' ? '1' : 'any' }}"
                                           class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                @elseif($setting->type === 'json' || $setting->type === 'array')
                                    <textarea name="settings[{{ $setting->key }}]"
                                              rows="3"
                                              class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ $setting->value }}</textarea>
                                @else
                                    <input type="text"
                                           name="settings[{{ $setting->key }}]"
                                           value="{{ $setting->value }}"
                                           class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Save Button -->
        @if($settings->count() > 0)
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg font-medium shadow-lg transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
        @endif
    </form>

    <!-- Common Settings Reference -->
    <div class="mt-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">การตั้งค่าทั่วไป</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <h4 class="font-medium text-gray-700 mb-2">General Settings</h4>
                <ul class="space-y-1 text-gray-600">
                    <li>• <code class="bg-white px-2 py-0.5 rounded">auto_approve_commissions</code> - อนุมัติคอมมิชชั่นอัตโนมัติ</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">commission_payout_day</code> - วันจ่ายคอมมิชชั่น</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">minimum_payout</code> - ขั้นต่ำในการถอน</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Calculation Settings</h4>
                <ul class="space-y-1 text-gray-600">
                    <li>• <code class="bg-white px-2 py-0.5 rounded">global_commission_per_pv</code> - อัตราค่าคอม/PV โกลบอล</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">enable_compression</code> - เปิดใช้งาน Compression</li>
                    <li>• <code class="bg-white px-2 py-0.5 rounded">enable_carry_forward</code> - เปิดใช้งาน Carry Forward</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
