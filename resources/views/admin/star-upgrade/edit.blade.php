{{--
    หน้าแก้ไขราคาดาว (Admin)

    @author Video Reward System
--}}

@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'แก้ไขราคาดาว')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.star-upgrade.index') }}"
           class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขราคาดาว</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $starPrice->display_name }}</p>
        </div>
    </div>

    {{-- Preview Card --}}
    <div class="max-w-md mx-auto mb-8">
        @php $colorInfo = $starColors[$starPrice->star_color] ?? $starColors['bronze']; @endphp
        <div class="bg-gradient-to-br {{ $colorInfo['gradient'] }} rounded-2xl p-6 text-center text-white shadow-xl">
            <div class="flex justify-center items-center gap-1 mb-3">
                @for($i = 0; $i < $starPrice->stars; $i++)
                    <svg class="w-10 h-10 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <h2 class="text-2xl font-bold">{{ $starPrice->stars }} ดาว</h2>
            <p class="text-white/80">{{ $colorInfo['name'] }}</p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.star-upgrade.update', $starPrice) }}" method="POST" class="max-w-2xl mx-auto">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-6">
            {{-- Basic Info --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (EN)</label>
                        <input type="text" name="name" value="{{ old('name', $starPrice->name) }}" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (TH)</label>
                        <input type="text" name="name_th" value="{{ old('name_th', $starPrice->name_th) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย (EN)</label>
                        <textarea name="description" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">{{ old('description', $starPrice->description) }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย (TH)</label>
                        <textarea name="description_th" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">{{ old('description_th', $starPrice->description_th) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Star Color --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สีดาว</h3>

                <div class="grid grid-cols-5 gap-3">
                    @foreach($starColors as $colorKey => $colorInfo)
                        <label class="cursor-pointer">
                            <input type="radio" name="star_color" value="{{ $colorKey }}"
                                   {{ old('star_color', $starPrice->star_color) === $colorKey ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="bg-gradient-to-br {{ $colorInfo['gradient'] }} rounded-xl p-3 text-center text-white opacity-50 peer-checked:opacity-100 peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-blue-500 transition">
                                <svg class="w-8 h-8 mx-auto text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <p class="text-xs mt-1">{{ $colorInfo['name'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Pricing --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ราคา</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาเต็ม (Coins)</label>
                        <input type="number" name="price_coins" value="{{ old('price_coins', $starPrice->price_coins) }}" required min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ราคาสำหรับซื้อตรงจาก 0 ดาว</p>
                        @error('price_coins')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคาอัพจากก่อนหน้า (Coins)</label>
                        <input type="number" name="price_coins_from_previous" value="{{ old('price_coins_from_previous', $starPrice->price_coins_from_previous) }}" min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ราคาสำหรับอัพจากดาว {{ $starPrice->stars - 1 }} (ปล่อยว่างถ้าไม่มีส่วนลด)</p>
                    </div>
                </div>
            </div>

            {{-- Requirements --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เงื่อนไข</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Level ขั้นต่ำ</label>
                        <input type="number" name="min_level" value="{{ old('min_level', $starPrice->min_level) }}" required min="1"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        @error('min_level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="hidden" name="require_previous_star" value="0">
                            <input type="checkbox" name="require_previous_star" value="1"
                                   {{ old('require_previous_star', $starPrice->require_previous_star) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">ต้องมีดาวก่อนหน้า</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Multipliers --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สิทธิประโยชน์</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตัวคูณ EXP</label>
                        <input type="number" name="exp_multiplier" value="{{ old('exp_multiplier', $starPrice->exp_multiplier) }}" required min="1" max="10" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เช่น 1.50 = เพิ่ม 50%</p>
                        @error('exp_multiplier')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตัวคูณ Coins</label>
                        <input type="number" name="coin_multiplier" value="{{ old('coin_multiplier', $starPrice->coin_multiplier) }}" required min="1" max="10" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เช่น 1.25 = เพิ่ม 25%</p>
                        @error('coin_multiplier')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สถานะ</h3>

                <div class="flex items-center gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $starPrice->is_active) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">เปิดการขาย</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('admin.star-upgrade.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection
