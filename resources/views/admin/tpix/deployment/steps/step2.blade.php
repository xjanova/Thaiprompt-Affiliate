@extends('admin.tpix.deployment._wizard-layout')

@section('step-content')
<form method="POST" action="{{ route('admin.tpix.deployment.step2.save', $config->slug) }}" class="space-y-8">
    @csrf

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">
            Step 2: ตั้งค่าเหรียญ
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
            กรอกข้อมูลพื้นฐานของเหรียญ Token ที่คุณต้องการสร้าง
        </p>
    </div>

    {{-- Basic Information --}}
    <div class="p-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            ข้อมูลพื้นฐาน
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Token Name --}}
            <div x-data="{ focused: false }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ชื่อเหรียญ <span class="text-red-500">*</span>
                </label>
                <input type="text" name="token_name" value="{{ old('token_name', $config->token_name) }}"
                       @focus="focused = true" @blur="focused = false"
                       class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                              dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                       :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                       placeholder="e.g., My Awesome Token" required />
                @error('token_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Token Symbol --}}
            <div x-data="{ focused: false }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สัญลักษณ์ <span class="text-red-500">*</span>
                </label>
                <input type="text" name="token_symbol" value="{{ old('token_symbol', $config->token_symbol) }}"
                       @focus="focused = true" @blur="focused = false"
                       class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 uppercase
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                              dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                       :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                       placeholder="e.g., MAT" maxlength="20" required />
                @error('token_symbol')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">2-20 ตัวอักษร, ใช้ตัวพิมพ์ใหญ่</p>
            </div>

            {{-- Total Supply --}}
            <div x-data="{ focused: false }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำนวนเหรียญทั้งหมด <span class="text-red-500">*</span>
                </label>
                <input type="number" name="total_supply" value="{{ old('total_supply', $config->total_supply) }}"
                       @focus="focused = true" @blur="focused = false"
                       class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                              dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                       :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                       placeholder="1000000" min="1" max="1000000000000" required />
                @error('total_supply')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Decimals --}}
            <div x-data="{ focused: false }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำนวนทศนิยม <span class="text-red-500">*</span>
                </label>
                <input type="number" name="decimals" value="{{ old('decimals', $config->decimals ?? 18) }}"
                       @focus="focused = true" @blur="focused = false"
                       class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                              dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                       :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                       min="0" max="18" required />
                @error('decimals')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">แนะนำ: 18 (มาตรฐาน ERC20)</p>
            </div>
        </div>

        {{-- Description --}}
        <div class="mt-6" x-data="{ focused: false }">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                คำอธิบายโครงการ
            </label>
            <textarea name="description" rows="4"
                      @focus="focused = true" @blur="focused = false"
                      class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
                             focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                             dark:bg-gray-700 dark:border-gray-600 dark:text-white resize-none"
                      :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                      placeholder="อธิบายเกี่ยวกับโครงการของคุณ...">{{ old('description', $config->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Branding --}}
    <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
            </svg>
            Branding
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Logo URL --}}
            <div x-data="{ focused: false }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Logo URL
                </label>
                <input type="url" name="logo_url" value="{{ old('logo_url', $config->logo_url) }}"
                       @focus="focused = true" @blur="focused = false"
                       class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                              dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                       :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                       placeholder="https://example.com/logo.png" />
                @error('logo_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Website URL --}}
            <div x-data="{ focused: false }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Website URL
                </label>
                <input type="url" name="website_url" value="{{ old('website_url', $config->website_url) }}"
                       @focus="focused = true" @blur="focused = false"
                       class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
                              focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                              dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                       :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                       placeholder="https://example.com" />
                @error('website_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Category --}}
    <div class="p-6 bg-gradient-to-r from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
            </svg>
            หมวดหมู่ <span class="text-red-500">*</span>
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($categories as $key => $label)
                <label class="relative cursor-pointer">
                    <input type="radio" name="category" value="{{ $key }}"
                           {{ old('category', $config->category) === $key ? 'checked' : '' }}
                           class="peer sr-only" required />
                    <div class="p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl
                                peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/30
                                hover:border-green-300 transition-all duration-300 text-center">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300
                                     peer-checked:text-green-900 dark:peer-checked:text-green-100">
                            {{ $label }}
                        </span>
                    </div>
                </label>
            @endforeach
        </div>
        @error('category')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Social Links --}}
    <div class="p-6 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path>
            </svg>
            Social Media
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach(['twitter' => 'Twitter/X', 'telegram' => 'Telegram', 'discord' => 'Discord', 'github' => 'GitHub'] as $key => $label)
                <div x-data="{ focused: false }">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $label }}
                    </label>
                    <input type="url" name="social_links[{{ $key }}]"
                           value="{{ old('social_links.' . $key, $config->social_links[$key] ?? '') }}"
                           @focus="focused = true" @blur="focused = false"
                           class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-300
                                  focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                                  dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           :class="focused ? 'border-blue-500 shadow-lg' : 'border-gray-300 dark:border-gray-600'"
                           placeholder="https://{{ $key }}.com/..." />
                </div>
            @endforeach
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-4 pt-6">
        <a href="{{ route('admin.tpix.deployment.step1', $config->slug) }}"
           class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
            ← ย้อนกลับ
        </a>
        <button type="submit"
                class="flex-1 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
            ถัดไป: Tokenomics →
        </button>
    </div>
</form>
@endsection
