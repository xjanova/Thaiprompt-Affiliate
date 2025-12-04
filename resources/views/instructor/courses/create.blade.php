@extends('layouts.admin')

@section('title', 'สร้างคอร์สใหม่ - Instructor')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.instructor.courses.index') }}"
           class="p-2 text-gray-400 hover:text-white transition">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">
                <i class="fas fa-plus-circle mr-3"></i>
                สร้างคอร์สใหม่
            </h1>
            <p class="text-gray-300 mt-1">กรอกข้อมูลคอร์สของคุณ</p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.instructor.courses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
                    <h2 class="text-lg font-semibold text-white mb-6">
                        <i class="fas fa-info-circle mr-2 text-blue-400"></i>
                        ข้อมูลพื้นฐาน
                    </h2>

                    <div class="space-y-4">
                        {{-- Title --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">ชื่อคอร์ส <span class="text-red-400">*</span></label>
                            <input type="text"
                                   name="title"
                                   value="{{ old('title') }}"
                                   required
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 @error('title') border-red-500 @enderror"
                                   placeholder="ตั้งชื่อคอร์สที่น่าสนใจ">
                            @error('title')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Category --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">หมวดหมู่ <span class="text-red-400">*</span></label>
                            <select name="category_id"
                                    required
                                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                                <option value="" class="bg-gray-800">เลือกหมวดหมู่</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }} class="bg-gray-800">
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Excerpt --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">คำอธิบายสั้นๆ</label>
                            <textarea name="excerpt"
                                      rows="3"
                                      class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                                      placeholder="อธิบายสั้นๆ ว่าคอร์สนี้เกี่ยวกับอะไร">{{ old('excerpt') }}</textarea>
                        </div>

                        {{-- Content --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">เนื้อหาคอร์ส <span class="text-red-400">*</span></label>
                            <textarea name="content"
                                      rows="12"
                                      required
                                      class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 @error('content') border-red-500 @enderror"
                                      placeholder="เนื้อหาคอร์สทั้งหมด (รองรับ HTML)">{{ old('content') }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-400">รองรับ HTML สำหรับจัดรูปแบบเนื้อหา</p>
                        </div>
                    </div>
                </div>

                {{-- Media --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
                    <h2 class="text-lg font-semibold text-white mb-6">
                        <i class="fas fa-image mr-2 text-green-400"></i>
                        สื่อประกอบ
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Thumbnail --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">รูปปก</label>
                            <input type="file"
                                   name="thumbnail"
                                   accept="image/*"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-purple-500 file:text-white hover:file:bg-purple-600">
                            <p class="mt-1 text-sm text-gray-400">แนะนำขนาด 1280x720px</p>
                        </div>

                        {{-- Video URL --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">URL วิดีโอ</label>
                            <input type="url"
                                   name="video_url"
                                   value="{{ old('video_url') }}"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                                   placeholder="https://youtube.com/watch?v=...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Settings --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
                    <h2 class="text-lg font-semibold text-white mb-6">
                        <i class="fas fa-cog mr-2 text-purple-400"></i>
                        การตั้งค่า
                    </h2>

                    <div class="space-y-4">
                        {{-- Difficulty --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">ระดับความยาก <span class="text-red-400">*</span></label>
                            <select name="difficulty"
                                    required
                                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                                <option value="beginner" {{ old('difficulty') === 'beginner' ? 'selected' : '' }} class="bg-gray-800">เริ่มต้น (Beginner)</option>
                                <option value="intermediate" {{ old('difficulty') === 'intermediate' ? 'selected' : '' }} class="bg-gray-800">ปานกลาง (Intermediate)</option>
                                <option value="advanced" {{ old('difficulty') === 'advanced' ? 'selected' : '' }} class="bg-gray-800">ขั้นสูง (Advanced)</option>
                            </select>
                        </div>

                        {{-- Course Level --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">ระดับคอร์ส (1-10)</label>
                            <input type="number"
                                   name="course_level"
                                   value="{{ old('course_level', 1) }}"
                                   min="1"
                                   max="10"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                            <p class="mt-1 text-sm text-gray-400">ใช้สำหรับจัดลำดับการเรียน</p>
                        </div>

                        {{-- Duration --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">ระยะเวลา (นาที)</label>
                            <input type="number"
                                   name="estimated_duration"
                                   value="{{ old('estimated_duration') }}"
                                   min="0"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                                   placeholder="30">
                        </div>

                        {{-- Tags --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">แท็ก</label>
                            <input type="text"
                                   name="tags"
                                   value="{{ old('tags') }}"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                                   placeholder="คั่นด้วยเครื่องหมายจุลภาค">
                        </div>
                    </div>
                </div>

                {{-- Quiz Settings --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
                    <h2 class="text-lg font-semibold text-white mb-6">
                        <i class="fas fa-clipboard-question mr-2 text-yellow-400"></i>
                        การตั้งค่า Quiz
                    </h2>

                    <div class="space-y-4">
                        {{-- Require Quiz Pass --}}
                        <div class="flex items-center justify-between">
                            <label class="text-sm text-gray-300">ต้องผ่าน Quiz</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="require_quiz_pass" value="1" {{ old('require_quiz_pass') ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                            </label>
                        </div>

                        {{-- Min Quiz Score --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">คะแนนขั้นต่ำ (%)</label>
                            <input type="number"
                                   name="min_quiz_score"
                                   value="{{ old('min_quiz_score', 70) }}"
                                   min="0"
                                   max="100"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                        </div>
                    </div>
                </div>

                {{-- Rewards --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
                    <h2 class="text-lg font-semibold text-white mb-6">
                        <i class="fas fa-gift mr-2 text-pink-400"></i>
                        รางวัลเมื่อจบคอร์ส
                    </h2>

                    <div class="space-y-4">
                        {{-- Points --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">แต้ม (Points)</label>
                            <input type="number"
                                   name="points_reward"
                                   value="{{ old('points_reward', 0) }}"
                                   min="0"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                        </div>

                        {{-- Coins --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-coins text-yellow-400 mr-1"></i>
                                Coins
                            </label>
                            <input type="number"
                                   name="coin_reward"
                                   value="{{ old('coin_reward', 0) }}"
                                   min="0"
                                   step="0.01"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                        </div>

                        {{-- Money --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-baht-sign text-green-400 mr-1"></i>
                                เงิน (บาท)
                            </label>
                            <input type="number"
                                   name="money_reward"
                                   value="{{ old('money_reward', 0) }}"
                                   min="0"
                                   step="0.01"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                        </div>

                        {{-- EXP --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-arrow-up text-blue-400 mr-1"></i>
                                EXP
                            </label>
                            <input type="number"
                                   name="exp_reward"
                                   value="{{ old('exp_reward', 0) }}"
                                   min="0"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                        </div>

                        {{-- PV --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-chart-line text-purple-400 mr-1"></i>
                                PV (Point Value)
                            </label>
                            <input type="number"
                                   name="pv_value"
                                   value="{{ old('pv_value', 0) }}"
                                   min="0"
                                   step="0.01"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                            <p class="mt-1 text-sm text-gray-400">สำหรับระบบ MLM</p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-3">
                    <button type="submit"
                            class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกคอร์ส
                    </button>
                    <a href="{{ route('admin.instructor.courses.index') }}"
                       class="w-full py-3 bg-gray-600 hover:bg-gray-500 text-white font-semibold rounded-xl text-center transition">
                        ยกเลิก
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
