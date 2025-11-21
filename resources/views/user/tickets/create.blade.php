@extends('layouts.user-arrow-x')

@section('title', 'สร้าง Ticket ใหม่')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">สร้าง Ticket ใหม่</h2>
                <p class="text-blue-100 text-sm">แจ้งปัญหาหรือขอความช่วยเหลือจากทีมงาน</p>
            </div>
            <a href="{{ route('user.tickets.index') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-8">
        <form method="POST" action="{{ route('user.tickets.store') }}">
            @csrf

            <!-- Category -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fa-solid fa-folder mr-2"></i>
                    หมวดหมู่ *
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($categories as $category)
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="category_id" value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'checked' : '' }} class="peer sr-only" required>
                            <div class="relative p-5 border-2 border-gray-200 dark:border-slate-600 rounded-2xl peer-checked:border-indigo-600 peer-checked:shadow-xl peer-checked:shadow-indigo-500/20 dark:peer-checked:shadow-indigo-500/30 hover:border-indigo-400 hover:shadow-lg transition-all duration-300 bg-white dark:bg-slate-800 group-hover:scale-[1.02]">
                                <!-- Selected Badge -->
                                <div class="absolute -top-2 -right-2 w-8 h-8 bg-indigo-600 rounded-full items-center justify-center text-white hidden peer-checked:flex">
                                    <i class="fa-solid fa-check text-sm"></i>
                                </div>

                                <!-- Category Content -->
                                <div class="flex flex-col items-center text-center space-y-3">
                                    <!-- Icon Circle -->
                                    <div class="relative">
                                        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-4xl shadow-lg transform group-hover:scale-110 transition-transform duration-300"
                                             style="background: linear-gradient(135deg, {{ $category->color }}15 0%, {{ $category->color }}30 100%); color: {{ $category->color }}; border: 2px solid {{ $category->color }}40;">
                                            @if($category->icon)
                                                <i class="{{ $category->icon }}"></i>
                                            @else
                                                <i class="fa-solid fa-folder"></i>
                                            @endif
                                        </div>
                                        <!-- Glow Effect on Hover -->
                                        <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 blur-xl"
                                             style="background: {{ $category->color }}40;"></div>
                                    </div>

                                    <!-- Category Info -->
                                    <div>
                                        <h4 class="font-bold text-base text-gray-900 dark:text-white mb-1 line-clamp-1">
                                            {{ $category->name }}
                                        </h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 leading-relaxed">
                                            {{ $category->description }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Selected Overlay -->
                                <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 opacity-0 peer-checked:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('category_id')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        <i class="fa-solid fa-exclamation-circle mr-1"></i>
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-lightbulb mr-1 text-yellow-500"></i>
                    เลือกหมวดหมู่ที่ตรงกับปัญหาของคุณมากที่สุดเพื่อให้ทีมงานสามารถช่วยเหลือได้อย่างรวดเร็ว
                </p>
            </div>

            <!-- Subject -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fa-solid fa-heading mr-2"></i>
                    หัวข้อ *
                </label>
                <input
                    type="text"
                    name="subject"
                    value="{{ old('subject') }}"
                    required
                    maxlength="255"
                    placeholder="สรุปปัญหาหรือคำถามของคุณในหัวข้อสั้นๆ"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                @error('subject')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fa-solid fa-align-left mr-2"></i>
                    รายละเอียด *
                </label>
                <textarea
                    name="description"
                    required
                    rows="8"
                    placeholder="โปรดอธิบายปัญหาหรือคำถามของคุณให้ละเอียดที่สุด เพื่อให้เราสามารถช่วยเหลือคุณได้อย่างรวดเร็ว"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    ควรระบุ: ขั้นตอนที่ทำให้เกิดปัญหา, ข้อความ error (ถ้ามี), และสิ่งที่คุณคาดหวัง
                </p>
            </div>

            <!-- Priority -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fa-solid fa-exclamation-circle mr-2"></i>
                    ความสำคัญ
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="priority" value="low" {{ old('priority', 'medium') == 'low' ? 'checked' : '' }} class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 dark:border-slate-600 rounded-xl peer-checked:border-gray-600 peer-checked:bg-gray-50 dark:peer-checked:bg-gray-900/30 hover:border-gray-300 transition-all text-center">
                            <div class="text-3xl mb-2">😌</div>
                            <h4 class="font-bold text-gray-900 dark:text-white">ต่ำ</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">ไม่เร่งด่วน</p>
                        </div>
                    </label>

                    <label class="relative cursor-pointer">
                        <input type="radio" name="priority" value="medium" {{ old('priority', 'medium') == 'medium' ? 'checked' : '' }} class="peer sr-only">
                        <div class="p-4 border-2 border-blue-200 dark:border-blue-600 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30 hover:border-blue-300 transition-all text-center">
                            <div class="text-3xl mb-2">🤔</div>
                            <h4 class="font-bold text-gray-900 dark:text-white">ปานกลาง</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">ปกติ (แนะนำ)</p>
                        </div>
                    </label>

                    <label class="relative cursor-pointer">
                        <input type="radio" name="priority" value="high" {{ old('priority', 'medium') == 'high' ? 'checked' : '' }} class="peer sr-only">
                        <div class="p-4 border-2 border-orange-200 dark:border-orange-600 rounded-xl peer-checked:border-orange-600 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/30 hover:border-orange-300 transition-all text-center">
                            <div class="text-3xl mb-2">😰</div>
                            <h4 class="font-bold text-gray-900 dark:text-white">สูง</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">เร่งด่วน</p>
                        </div>
                    </label>
                </div>
                @error('priority')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-6">
                <div class="flex items-start space-x-4">
                    <i class="fa-solid fa-lightbulb text-2xl text-blue-600 dark:text-blue-400 mt-1"></i>
                    <div>
                        <h4 class="font-bold text-blue-900 dark:text-blue-300 mb-2">เคล็ดลับสำหรับการสร้าง Ticket ที่ดี</h4>
                        <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                            <li>• เลือกหมวดหมู่ที่ตรงกับปัญหาของคุณมากที่สุด</li>
                            <li>• เขียนหัวข้อที่กระชับและชัดเจน</li>
                            <li>• อธิบายรายละเอียดให้ละเอียดเพื่อให้ทีมงานช่วยเหลือได้เร็วขึ้น</li>
                            <li>• หากเป็นปัญหาเทคนิค ควรระบุ browser, อุปกรณ์, และขั้นตอนการเกิดปัญหา</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <a href="{{ route('user.tickets.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition-all">
                    <i class="fa-solid fa-times mr-2"></i>
                    ยกเลิก
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-lg shadow-lg transition-all transition-transform hover:scale-[1.02]">
                    <i class="fa-solid fa-paper-plane mr-2"></i>
                    สร้าง Ticket
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
