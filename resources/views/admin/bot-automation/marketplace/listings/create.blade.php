@extends('layouts.admin-v3')

@section('title', 'สร้างรายการใหม่')

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    {{-- Gradient Header --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-amber-600 to-yellow-700 dark:from-orange-900 dark:via-amber-900 dark:to-yellow-950 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-sm flex items-center justify-center border-2 border-white/30" border border-white/20 dark:border-white/10>
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h1 data-translate class="text-4xl font-bold text-white mb-2">สร้างรายการใหม่</h1>
                    <p data-translate class="text-orange-100 text-lg">เพิ่มบอทเข้าสู่ตลาดซื้อขาย</p>
                </div>
            </div>

            
            <div class="flex items-center gap-3">
                {{-- Language Switcher --}}
                <div class="relative inline-block" x-data="{ open: false }">
                    <button @click="open = !open" class="px-4 py-2 glass-fusion backdrop-blur-sm text-white rounded-xl hover:glass-fusion transition-all duration-200 border border-white/30 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span data-translate>ภาษา</span>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 dark:border-slate-700 overflow-hidden z-50" border border-white/20 dark:border-white/10>
                        <a href="/lang/th" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                        </a>
                        <a href="/lang/en" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇬🇧</span> English
                        </a>
                        <a href="/lang/zh" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇨🇳</span> 中文
                        </a>
                        <a href="/lang/ja" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇯🇵</span> 日本語
                        </a>
                    </div>
                </div>
<a href="{{ route('admin.bot-automation.marketplace.index') }}"
               class="inline-flex items-center px-6 py-3 glass-fusion hover:bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-800 dark:hover:bg-gray-700 text-orange-600 dark:text-orange-400 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปตลาด
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700 dark:border-gray-700" border border-white/20 dark:border-white/10>
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ข้อมูลรายการ
                    </h2>
                </div>

                <form action="{{ route('admin.bot-automation.marketplace.listings.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf

                    {{-- ชื่อรายการ --}}
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            ชื่อรายการ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required
                               class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('title') border-red-500 @enderror">
                        @error('title')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- คำอธิบาย --}}
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            คำอธิบาย <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" id="description" rows="4" required
                                  class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- หมวดหมู่และราคา --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                หมวดหมู่ <span class="text-red-500">*</span>
                            </label>
                            <select name="category" id="category" required
                                    class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('category') border-red-500 @enderror">
                                <option data-translate>เลือกหมวดหมู่</option>
                                <option data-translate>ฝ่ายขาย</option>
                                <option data-translate>ฝ่ายสนับสนุน</option>
                                <option data-translate>การตลาด</option>
                                <option data-translate>ระบบอัตโนมัติ</option>
                            </select>
                            @error('category')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="price" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                ราคา (USD) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">$</span>
                                </div>
                                <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0" required
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('price') border-red-500 @enderror">
                            </div>
                            @error('price')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- คุณสมบัติ --}}
                    <div>
                        <label for="features" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            คุณสมบัติ (ทีละบรรทัด)
                        </label>
                        <textarea name="features" id="features" rows="5" placeholder="คุณสมบัติ 1&#10;คุณสมบัติ 2&#10;คุณสมบัติ 3" data-translate-placeholder
                                  class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('features') border-red-500 @enderror">{{ old('features') }}</textarea>
                        @error('features')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- บอทที่เชื่อมโยง --}}
                    <div>
                        <label for="bot_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            เชื่อมโยงกับบอท
                        </label>
                        <select name="bot_id" id="bot_id"
                                class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('bot_id') border-red-500 @enderror">
                            <option data-translate>เลือกบอท (ไม่บังคับ)</option>
                            @foreach($bots ?? [] as $bot)
                            <option value="{{ $bot->id }}" {{ old('bot_id') == $bot->id ? 'selected' : '' }}>
                                {{ $bot->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('bot_id')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- รูปภาพ --}}
                    <div>
                        <label for="image" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            รูปภาพรายการ
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label for="image" class="flex flex-col items-center justify-center w-full h-40 border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 border-dashed rounded-xl cursor-pointer bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-600 transition-colors duration-300">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400"><span data-translate class="font-semibold">คลิกเพื่ออัพโหลด</span> หรือลากไฟล์มาวาง</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">PNG, JPG หรือ GIF (MAX. 2MB)</p>
                                </div>
                                <input type="file" name="image" id="image" accept="image/*" class="hidden" @error('image') border-red-500 @enderror>
                            </label>
                        </div>
                        @error('image')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- รายการเด่น --}}
                    <div class="flex items-center">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                               class="w-5 h-5 text-orange-600 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded focus:ring-orange-500 dark:focus:ring-orange-600 focus:ring-2">
                        <label for="is_featured" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                            รายการเด่น (Featured Listing)
                        </label>
                    </div>

                    {{-- สถานะ --}}
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            สถานะ <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status" required
                                class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('status') border-red-500 @enderror">
                            <option data-translate>รอดำเนินการ</option>
                            <option data-translate>ใช้งาน</option>
                            <option data-translate>ไม่ใช้งาน</option>
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <button type="submit"
                                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 dark:from-orange-600 dark:to-amber-700 dark:hover:from-orange-700 dark:hover:to-amber-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            สร้างรายการ
                        </button>
                        <a href="{{ route('admin.bot-automation.marketplace.index') }}"
                           class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                            ยกเลิก
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tips Sidebar --}}
        <div class="space-y-6">
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700 dark:border-gray-700" border border-white/20 dark:border-white/10>
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        เคล็ดลับ
                    </h3>
                </div>
                <div class="p-6">
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">ใช้ชื่อที่ชัดเจนและเข้าใจง่าย</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">เขียนคำอธิบายละเอียดและครบถ้วน</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">ตั้งราคาที่แข่งขันได้</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">เพิ่มรูปภาพคุณภาพสูง</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">ระบุคุณสมบัติสำคัญอย่างชัดเจน</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
