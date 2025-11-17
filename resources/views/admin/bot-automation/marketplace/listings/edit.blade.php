@extends('layouts.admin-v3')

@section('title', 'แก้ไขรายการ')

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    {{-- Gradient Header --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-amber-600 to-yellow-700 dark:from-orange-900 dark:via-amber-900 dark:to-yellow-950 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-sm flex items-center justify-center border-2 border-white/30" border border-white/20 dark:border-white/10>
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h1 data-translate class="text-4xl font-bold text-white mb-2">แก้ไขรายการ</h1>
                    <p data-translate class="text-orange-100 text-lg">อัพเดทข้อมูลรายการในตลาด</p>
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

                <form action="{{ route('admin.bot-automation.marketplace.listings.update', $listing->id ?? 0) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- ชื่อรายการ --}}
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            ชื่อรายการ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" value="{{ old('title', $listing->title ?? '') }}" required
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
                                  class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('description') border-red-500 @enderror">{{ old('description', $listing->description ?? '') }}</textarea>
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
                                <option value="sales" {{ old('category', $listing->category ?? '') == 'sales' ? 'selected' : '' }}>ฝ่ายขาย</option>
                                <option value="support" {{ old('category', $listing->category ?? '') == 'support' ? 'selected' : '' }}>ฝ่ายสนับสนุน</option>
                                <option value="marketing" {{ old('category', $listing->category ?? '') == 'marketing' ? 'selected' : '' }}>การตลาด</option>
                                <option value="automation" {{ old('category', $listing->category ?? '') == 'automation' ? 'selected' : '' }}>ระบบอัตโนมัติ</option>
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
                                <input type="number" name="price" id="price" value="{{ old('price', $listing->price ?? '') }}" step="0.01" min="0" required
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
                                  class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300 @error('features') border-red-500 @enderror">{{ old('features', $listing->features ?? '') }}</textarea>
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
                            <option value="{{ $bot->id }}" {{ old('bot_id', $listing->bot_id ?? '') == $bot->id ? 'selected' : '' }}>
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
                        @if(isset($listing->image) && $listing->image)
                        <div class="mb-4">
                            <img src="{{ asset('storage/' . $listing->image) }}" alt="รูปปัจจุบัน" class="w-48 h-48 object-cover rounded-xl border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <p data-translate class="mt-2 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">รูปภาพปัจจุบัน (อัพโหลดใหม่เพื่อเปลี่ยน)</p>
                        </div>
                        @endif
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
                        <p data-translate class="mt-2 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">หากไม่อัพโหลดรูปใหม่ จะใช้รูปเดิม</p>
                        @error('image')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- รายการเด่น --}}
                    <div class="flex items-center">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $listing->is_featured ?? false) ? 'checked' : '' }}
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
                            <option value="pending" {{ old('status', $listing->status ?? '') == 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                            <option value="active" {{ old('status', $listing->status ?? '') == 'active' ? 'selected' : '' }}>ใช้งาน</option>
                            <option value="inactive" {{ old('status', $listing->status ?? '') == 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <div class="flex gap-4">
                            <button type="submit"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 dark:from-orange-600 dark:to-amber-700 dark:hover:from-orange-700 dark:hover:to-amber-800 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                บันทึกการแก้ไข
                            </button>
                            <a href="{{ route('admin.bot-automation.marketplace.index') }}"
                               class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                                ยกเลิก
                            </a>
                        </div>
                        <button type="button" onclick="confirmDelete()"
                                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-medium rounded-xl transition-all duration-300 shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            ลบรายการ
                        </button>
                    </div>
                </form>

                {{-- Delete Form (Hidden) --}}
                <form id="deleteForm" action="{{ route('admin.bot-automation.marketplace.listings.destroy', $listing->id ?? 0) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Statistics --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700 dark:border-gray-700" border border-white/20 dark:border-white/10>
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
                    <h3 data-translate class="text-lg font-bold text-gray-900 dark:text-white">สถิติรายการ</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <span data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">ยอดดู:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $listing->views ?? '0' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <span data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">สมาชิก:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $listing->subscribers ?? '0' }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <span data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">รายได้:</span>
                            <span class="text-lg font-bold text-green-600 dark:text-green-400">${{ number_format($listing->revenue ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                            <span data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">คะแนน:</span>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-gray-900 dark:text-white mr-2">{{ number_format($listing->rating ?? 0, 1) }}</span>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">สร้างเมื่อ:</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ isset($listing->created_at) ? $listing->created_at->format('d M Y') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script>
function confirmDelete() {
    if (confirm('คุณต้องการลบรายการนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endsection
