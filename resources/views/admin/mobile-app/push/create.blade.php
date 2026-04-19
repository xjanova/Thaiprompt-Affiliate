@extends('layouts.admin-v3')

@section('title', 'ส่ง Push Notification')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="text-4xl">📢</span>
            ส่ง Push Notification
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-2">
            ส่งข้อความถึงผู้ใช้แอพมือถือ
        </p>
    </div>

    {{-- Device Count Info --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <div class="text-sm text-blue-600 dark:text-blue-400">เครื่องทั้งหมด</div>
            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($totalDevices) }}</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
            <div class="text-sm text-gray-600 dark:text-gray-400"> iOS</div>
            <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ number_format($iosDevices) }}</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
            <div class="text-sm text-green-600 dark:text-green-400">🤖 Android</div>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ number_format($androidDevices) }}</div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.mobile-app.push.store') }}" method="POST"
          class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Title --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    หัวข้อ <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="เช่น โปรโมชั่นพิเศษ! ลด 50%">
                @error('title')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Body --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ข้อความ <span class="text-red-500">*</span>
                </label>
                <textarea name="body" rows="4" required
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                                 bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          placeholder="ข้อความที่ต้องการส่ง...">{{ old('body') }}</textarea>
                @error('body')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Image URL --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    รูปภาพ (URL)
                </label>
                <input type="url" name="image" value="{{ old('image') }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="https://example.com/image.jpg">
                @error('image')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Action URL --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Action URL (เมื่อแตะ notification)
                </label>
                <input type="text" name="action_url" value="{{ old('action_url') }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                       placeholder="เช่น /shopping หรือ /product/123">
                @error('action_url')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Target Platform --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ส่งถึง <span class="text-red-500">*</span>
                </label>
                <select name="target" required
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                               focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="all" {{ old('target') === 'all' ? 'selected' : '' }}>
                        ทุกเครื่อง ({{ number_format($totalDevices) }})
                    </option>
                    <option value="ios" {{ old('target') === 'ios' ? 'selected' : '' }}>
                        iOS เท่านั้น ({{ number_format($iosDevices) }})
                    </option>
                    <option value="android" {{ old('target') === 'android' ? 'selected' : '' }}>
                        Android เท่านั้น ({{ number_format($androidDevices) }})
                    </option>
                </select>
                @error('target')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Schedule --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ตั้งเวลาส่ง (ถ้าต้องการ)
                </label>
                <input type="datetime-local" name="schedule_at" value="{{ old('schedule_at') }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                              bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                              focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    ถ้าไม่กำหนด จะส่งทันที
                </p>
                @error('schedule_at')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Info Box --}}
        <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
            <div class="flex items-start gap-3">
                <span class="text-xl">💡</span>
                <div class="text-sm text-yellow-700 dark:text-yellow-300">
                    <strong>หมายเหตุ:</strong> Push Notification จะถูกส่งไปยังเครื่องที่มี Push Token เท่านั้น
                    หากเครื่องไม่มีเน็ต จะถูกเก็บไว้และส่งใหม่เมื่อเครื่องเชื่อมต่อ
                </div>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.mobile-app.push.index') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600
                      text-gray-700 dark:text-gray-300 rounded-lg transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition">
                📢 ส่ง Push Notification
            </button>
        </div>
    </form>
</div>
@endsection
