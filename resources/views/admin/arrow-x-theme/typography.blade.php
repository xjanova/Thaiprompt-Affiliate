@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าฟอนต์ - Arrow X')

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('admin.arrow-x-theme.index') }}" class="text-purple-600 dark:text-purple-400 hover:underline mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i> กลับ
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">ตั้งค่าฟอนต์</h1>
        </div>

        @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 text-green-800 dark:text-green-300 px-4 py-3 rounded-xl mb-6">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('admin.arrow-x-theme.typography.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Font Families</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Primary Font</label>
                        <input type="text" name="primary_font" value="{{ $typography->primary_font ?? 'Inter' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Secondary Font (Thai)</label>
                        <input type="text" name="secondary_font" value="{{ $typography->secondary_font ?? 'Noto Sans Thai' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Code Font</label>
                        <input type="text" name="code_font" value="{{ $typography->code_font ?? 'Fira Code' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Font Sizes (rem)</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Base</label>
                        <input type="number" name="base_font_size" value="{{ $typography->base_font_size ?? 1.00 }}" step="0.01" min="0.5" max="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">H1</label>
                        <input type="number" name="heading_h1_size" value="{{ $typography->heading_h1_size ?? 2.50 }}" step="0.01" min="0.5" max="5" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">H2</label>
                        <input type="number" name="heading_h2_size" value="{{ $typography->heading_h2_size ?? 2.00 }}" step="0.01" min="0.5" max="5" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">H3</label>
                        <input type="number" name="heading_h3_size" value="{{ $typography->heading_h3_size ?? 1.75 }}" step="0.01" min="0.5" max="5" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            <input type="hidden" name="heading_h4_size" value="{{ $typography->heading_h4_size ?? 1.50 }}">
            <input type="hidden" name="heading_h5_size" value="{{ $typography->heading_h5_size ?? 1.25 }}">
            <input type="hidden" name="heading_h6_size" value="{{ $typography->heading_h6_size ?? 1.00 }}">
            <input type="hidden" name="heading_line_height" value="{{ $typography->heading_line_height ?? 1.20 }}">
            <input type="hidden" name="body_line_height" value="{{ $typography->body_line_height ?? 1.60 }}">

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.arrow-x-theme.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 rounded-xl">ยกเลิก</a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white rounded-xl">บันทึก</button>
            </div>
        </form>
    </div>
</div>
@endsection
