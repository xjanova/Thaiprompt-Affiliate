@extends('layouts.admin')

@section('title', 'เพิ่ม SEO Meta')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">เพิ่ม SEO Meta Tags</h2>
                </div>

                <form action="{{ route('admin.seo.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ประเภทหน้า *</label>
                            <select name="page_type" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- เลือกประเภทหน้า --</option>
                                @foreach($pageTypes as $key => $label)
                                    <option value="{{ $key }}" {{ old('page_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('page_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ภาษา *</label>
                            <select name="language" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                @foreach($languages as $key => $label)
                                    <option value="{{ $key }}" {{ old('language') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('language')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Basic Meta Tags -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Meta Tags พื้นฐาน</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                                <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="หัวข้อหน้า (แนะนำ 50-60 ตัวอักษร)">
                                @error('meta_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                                <textarea name="meta_description" rows="3"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="คำอธิบายหน้า (แนะนำ 150-160 ตัวอักษร)">{{ old('meta_description') }}</textarea>
                                @error('meta_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                                <input type="text" name="meta_keywords" value="{{ old('meta_keywords') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="คำสำคัญ (คั่นด้วยจุลภาค)">
                                @error('meta_keywords')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Canonical URL</label>
                                <input type="url" name="canonical_url" value="{{ old('canonical_url') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="https://example.com/page">
                                @error('canonical_url')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Open Graph Tags -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Open Graph Tags (Facebook)</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">OG Title</label>
                                <input type="text" name="og_title" value="{{ old('og_title') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                @error('og_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">OG Description</label>
                                <textarea name="og_description" rows="3"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('og_description') }}</textarea>
                                @error('og_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">OG Image URL</label>
                                <input type="url" name="og_image" value="{{ old('og_image') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="https://example.com/image.jpg">
                                @error('og_image')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">OG Type</label>
                                <input type="text" name="og_type" value="{{ old('og_type', 'website') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                @error('og_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Twitter Card Tags -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Twitter Card Tags</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Card Type</label>
                                <select name="twitter_card" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="summary_large_image" {{ old('twitter_card') == 'summary_large_image' ? 'selected' : '' }}>Summary Large Image</option>
                                    <option value="summary" {{ old('twitter_card') == 'summary' ? 'selected' : '' }}>Summary</option>
                                </select>
                                @error('twitter_card')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Title</label>
                                <input type="text" name="twitter_title" value="{{ old('twitter_title') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                @error('twitter_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Description</label>
                                <textarea name="twitter_description" rows="3"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('twitter_description') }}</textarea>
                                @error('twitter_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Image URL</label>
                                <input type="url" name="twitter_image" value="{{ old('twitter_image') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                @error('twitter_image')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Robots Settings -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">การตั้งค่า Robots</h3>

                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="index" name="index" value="1"
                                    {{ old('index', true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="index" class="ml-2 text-sm text-gray-700">
                                    Index - อนุญาตให้ Search Engine จัดทำดัชนี
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="follow" name="follow" value="1"
                                    {{ old('follow', true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="follow" class="ml-2 text-sm text-gray-700">
                                    Follow - อนุญาตให้ Search Engine ติดตามลิงก์
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                        <a href="{{ route('admin.seo.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            ยกเลิก
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-save mr-2"></i>บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
