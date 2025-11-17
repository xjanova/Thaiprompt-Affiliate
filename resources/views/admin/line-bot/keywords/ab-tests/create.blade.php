@extends('layouts.admin-v3')

@section('title', 'สร้าง A/B Test')

@section('content')
<div class="container-fluid px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.ab-tests.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline mb-4 inline-block">
            ← กลับ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            🧪 สร้าง A/B Test ใหม่
        </h1>
    </div>

    <form action="{{ route('admin.line-bot.keywords.ab-tests.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Section 1: Test Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                📋 ข้อมูลการทดสอบ
            </h2>

            <!-- Keyword Selection -->
            <div>
                <label for="keyword_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    คีย์เวิร์ดที่ต้องการทดสอบ *
                </label>
                <select id="keyword_id" name="keyword_id" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- เลือกคีย์เวิร์ด --</option>
                    @foreach($keywords as $keyword)
                        <option value="{{ $keyword->id }}" {{ $selected_keyword?->id === $keyword->id ? 'selected' : '' }}>
                            {{ $keyword->keyword }}
                        </option>
                    @endforeach
                </select>
                @error('keyword_id')
                    <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Test Name -->
            <div>
                <label for="test_name" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    ชื่อการทดสอบ *
                </label>
                <input type="text" id="test_name" name="test_name" required
                    placeholder="เช่น Test response greeting variations"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="{{ old('test_name') }}">
                @error('test_name')
                    <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    คำอธิบาย (ไม่บังคับ)
                </label>
                <textarea id="description" name="description" rows="3"
                    placeholder="คำอธิบายโดยละเอียดเกี่ยวกับการทดสอบนี้"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>
        </div>

        <!-- Section 2: Test Configuration -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                ⚙️ การตั้งค่า
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Variant A Percentage -->
                <div>
                    <label for="variant_a_percentage" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Variant A % *
                    </label>
                    <input type="number" id="variant_a_percentage" name="variant_a_percentage" required min="1" max="99"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="{{ old('variant_a_percentage', 50) }}">
                    <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                        (A + B ต้องรวมกันเท่า 100%)
                    </p>
                </div>

                <!-- Variant B Percentage -->
                <div>
                    <label for="variant_b_percentage" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Variant B % *
                    </label>
                    <input type="number" id="variant_b_percentage" name="variant_b_percentage" required min="1" max="99"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="{{ old('variant_b_percentage', 50) }}">
                </div>

                <!-- Winning Criterion -->
                <div>
                    <label for="winning_criterion" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        เกณฑ์ชนะ *
                    </label>
                    <select id="winning_criterion" name="winning_criterion" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="conversion_rate" {{ old('winning_criterion', 'conversion_rate') === 'conversion_rate' ? 'selected' : '' }}>
                            Conversion Rate
                        </option>
                        <option value="response_time" {{ old('winning_criterion') === 'response_time' ? 'selected' : '' }}>
                            Response Time
                        </option>
                        <option value="satisfaction" {{ old('winning_criterion') === 'satisfaction' ? 'selected' : '' }}>
                            Satisfaction
                        </option>
                        <option value="interaction_rate" {{ old('winning_criterion') === 'interaction_rate' ? 'selected' : '' }}>
                            Interaction Rate
                        </option>
                    </select>
                </div>
            </div>

            <!-- Minimum Samples -->
            <div class="md:w-1/3">
                <label for="minimum_samples" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    จำนวนตัวอย่างต่ำสุด *
                </label>
                <input type="number" id="minimum_samples" name="minimum_samples" required min="10" max="10000"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="{{ old('minimum_samples', 100) }}">
                <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                    การทดสอบจะจบหลังจากไม่ต่ำกว่า samples นี้
                </p>
            </div>
        </div>

        <!-- Section 3: Variant A -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                📌 Variant A (Original)
            </h2>

            <!-- Response Text -->
            <div>
                <label for="variant_a_response_text" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    ข้อความตอบกลับ *
                </label>
                <textarea id="variant_a_response_text" name="variant_a[response_text]" required rows="4"
                    placeholder="ข้อความที่บอทตอบกลับเมื่อเข้าใจคำหลัก"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('variant_a.response_text') }}</textarea>
                @error('variant_a.response_text')
                    <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Response Type -->
            <div>
                <label for="variant_a_response_type" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    ประเภทการตอบกลับ *
                </label>
                <select id="variant_a_response_type" name="variant_a[response_type]" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="text" {{ old('variant_a.response_type', 'text') === 'text' ? 'selected' : '' }}>
                        Text Message
                    </option>
                    <option value="quick_reply" {{ old('variant_a.response_type') === 'quick_reply' ? 'selected' : '' }}>
                        Quick Reply
                    </option>
                    <option value="flex_message" {{ old('variant_a.response_type') === 'flex_message' ? 'selected' : '' }}>
                        Flex Message
                    </option>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label for="variant_a_description" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    คำอธิบาย (ไม่บังคับ)
                </label>
                <input type="text" id="variant_a_description" name="variant_a[description]"
                    placeholder="เช่น Original greeting message"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="{{ old('variant_a.description') }}">
            </div>
        </div>

        <!-- Section 4: Variant B -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                📌 Variant B (Test)
            </h2>

            <!-- Response Text -->
            <div>
                <label for="variant_b_response_text" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    ข้อความตอบกลับ *
                </label>
                <textarea id="variant_b_response_text" name="variant_b[response_text]" required rows="4"
                    placeholder="ข้อความทดสอบที่ต่างจาก variant A"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('variant_b.response_text') }}</textarea>
                @error('variant_b.response_text')
                    <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Response Type -->
            <div>
                <label for="variant_b_response_type" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    ประเภทการตอบกลับ *
                </label>
                <select id="variant_b_response_type" name="variant_b[response_type]" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="text" {{ old('variant_b.response_type', 'text') === 'text' ? 'selected' : '' }}>
                        Text Message
                    </option>
                    <option value="quick_reply" {{ old('variant_b.response_type') === 'quick_reply' ? 'selected' : '' }}>
                        Quick Reply
                    </option>
                    <option value="flex_message" {{ old('variant_b.response_type') === 'flex_message' ? 'selected' : '' }}>
                        Flex Message
                    </option>
                </select>
            </div>

            <!-- Description -->
            <div>
                <label for="variant_b_description" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                    คำอธิบาย (ไม่บังคับ)
                </label>
                <input type="text" id="variant_b_description" name="variant_b[description]"
                    placeholder="เช่น Friendlier greeting with emoji"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="{{ old('variant_b.description') }}">
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex gap-4">
            <button type="submit"
                class="px-8 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-semibold transition">
                ✅ สร้าง A/B Test
            </button>
            <a href="{{ route('admin.line-bot.keywords.ab-tests.index') }}"
                class="px-8 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg font-semibold transition">
                ❌ ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
