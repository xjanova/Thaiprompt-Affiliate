@extends('layouts.admin')

@section('title', 'ตั้งค่า MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">ตั้งค่า MLM</h1>
            <p class="text-gray-600 mt-1">กำหนดค่าและตั้งค่าระบบ MLM</p>
        </div>
        <a href="{{ route('admin.mlm.settings.create') }}"
           class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-2.5 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            เพิ่มการตั้งค่า
        </a>
    </div>

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
                            @elseif($setting->type === 'number')
                                <input type="number"
                                       name="settings[{{ $setting->key }}]"
                                       value="{{ $setting->value }}"
                                       step="any"
                                       class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            @elseif($setting->type === 'json')
                                <textarea name="settings[{{ $setting->key }}]"
                                          rows="3"
                                          class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 font-mono text-sm">{{ $setting->value }}</textarea>
                            @else
                                <input type="text"
                                       name="settings[{{ $setting->key }}]"
                                       value="{{ $setting->value }}"
                                       class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            @endif

                            <!-- Delete Button -->
                            <form action="{{ route('admin.mlm.settings.destroy', $setting) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('ยืนยันการลบการตั้งค่านี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">ยังไม่มีการตั้งค่า</h3>
            <p class="text-gray-500 mb-6">เริ่มต้นด้วยการเพิ่มการตั้งค่าใหม่</p>
            <a href="{{ route('admin.mlm.settings.create') }}"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg shadow-lg transition-all duration-200 gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                เพิ่มการตั้งค่า
            </a>
        </div>
        @endforelse

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
