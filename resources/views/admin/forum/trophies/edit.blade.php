{{--
    หน้าแก้ไขถ้วยรางวัลฟอรั่ม
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.admin-v3')

@section('title', 'แก้ไขถ้วยรางวัล - ' . $trophy->name)

@section('content')
<div class="space-y-6" x-data="trophyForm()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-white/60 mb-2">
                <a href="{{ route('admin.platform-revenue.forum.trophies.index') }}" class="hover:text-white transition">
                    จัดการถ้วยรางวัล
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-white">แก้ไข: {{ $trophy->name }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-white">✏️ แก้ไขถ้วยรางวัล</h1>
            <p class="text-white/60 mt-1">แก้ไขรายละเอียดถ้วยรางวัล "{{ $trophy->name }}"</p>
        </div>
        <a href="{{ route('admin.platform-revenue.forum.trophies.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition">
            <i class="fas fa-arrow-left"></i>
            <span>กลับ</span>
        </a>
    </div>

    {{-- Form --}}
    <div class="glass-card rounded-xl p-6">
        <form action="{{ route('admin.platform-revenue.forum.trophies.update', $trophy) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- ชื่อถ้วยรางวัล --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        ชื่อถ้วยรางวัล <span class="text-red-400">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           x-model="name"
                           value="{{ old('name', $trophy->name) }}"
                           required
                           placeholder="เช่น นักโพสต์ยอดเยี่ยม"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    @error('name')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- คะแนน --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        คะแนน <span class="text-red-400">*</span>
                    </label>
                    <input type="number"
                           name="points"
                           x-model="points"
                           value="{{ old('points', $trophy->points) }}"
                           required
                           min="0"
                           placeholder="10"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    <p class="text-white/40 text-xs mt-1">คะแนนที่ผู้ใช้จะได้รับเมื่อได้รับถ้วยรางวัลนี้</p>
                    @error('points')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ไอคอน --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        ไอคอน (Emoji)
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="text"
                               name="icon"
                               x-model="icon"
                               value="{{ old('icon', $trophy->icon ?? '🏆') }}"
                               maxlength="10"
                               placeholder="🏆"
                               class="w-24 px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white text-center text-2xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        <div class="flex gap-2 flex-wrap">
                            @foreach(['🏆', '🥇', '🥈', '🥉', '⭐', '💎', '🎖️', '🏅', '👑', '🌟', '💪', '🔥'] as $emoji)
                            <button type="button"
                                    @click="icon = '{{ $emoji }}'"
                                    class="w-10 h-10 text-xl bg-white/10 hover:bg-white/20 rounded-lg transition"
                                    :class="{ 'ring-2 ring-yellow-500': icon === '{{ $emoji }}' }">
                                {{ $emoji }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @error('icon')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- โหมดอัตโนมัติ --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        โหมดมอบรางวัล
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer p-3 bg-white/5 rounded-lg">
                        <input type="checkbox"
                               name="is_automatic"
                               x-model="isAutomatic"
                               value="1"
                               {{ old('is_automatic', $trophy->is_automatic) ? 'checked' : '' }}
                               class="w-5 h-5 rounded bg-white/10 border-white/20 text-yellow-500 focus:ring-yellow-500">
                        <div>
                            <span class="text-white font-medium">มอบอัตโนมัติ</span>
                            <p class="text-white/40 text-xs">ระบบจะมอบรางวัลอัตโนมัติเมื่อผู้ใช้ทำตามเงื่อนไข</p>
                        </div>
                    </label>
                </div>

                {{-- คำอธิบาย --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description"
                              x-model="description"
                              rows="3"
                              placeholder="อธิบายว่าถ้วยรางวัลนี้มอบให้สำหรับอะไร..."
                              class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:ring-2 focus:ring-yellow-500 focus:border-transparent resize-none">{{ old('description', $trophy->description) }}</textarea>
                    @error('description')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- เงื่อนไขอัตโนมัติ (แสดงเมื่อเลือกโหมดอัตโนมัติ) --}}
                <div class="lg:col-span-2" x-show="isAutomatic" x-transition>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        เงื่อนไขอัตโนมัติ
                    </label>
                    @php
                        $requirements = $trophy->requirements ?? [];
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-white/5 rounded-lg">
                        <div>
                            <label class="block text-xs text-white/60 mb-1">จำนวนกระทู้ขั้นต่ำ</label>
                            <input type="number"
                                   name="requirements[min_threads]"
                                   value="{{ old('requirements.min_threads', $requirements['min_threads'] ?? '') }}"
                                   min="0"
                                   placeholder="0"
                                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 text-sm focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div>
                            <label class="block text-xs text-white/60 mb-1">จำนวนโพสต์ขั้นต่ำ</label>
                            <input type="number"
                                   name="requirements[min_posts]"
                                   value="{{ old('requirements.min_posts', $requirements['min_posts'] ?? '') }}"
                                   min="0"
                                   placeholder="0"
                                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 text-sm focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div>
                            <label class="block text-xs text-white/60 mb-1">จำนวนไลค์ที่ได้รับขั้นต่ำ</label>
                            <input type="number"
                                   name="requirements[min_likes_received]"
                                   value="{{ old('requirements.min_likes_received', $requirements['min_likes_received'] ?? '') }}"
                                   min="0"
                                   placeholder="0"
                                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 text-sm focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div>
                            <label class="block text-xs text-white/60 mb-1">จำนวนคำตอบที่ดีที่สุดขั้นต่ำ</label>
                            <input type="number"
                                   name="requirements[min_best_answers]"
                                   value="{{ old('requirements.min_best_answers', $requirements['min_best_answers'] ?? '') }}"
                                   min="0"
                                   placeholder="0"
                                   class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 text-sm focus:ring-2 focus:ring-yellow-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-white">{{ $trophy->users_count ?? 0 }}</div>
                    <div class="text-white/60 text-sm">ผู้ได้รับ</div>
                </div>
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-yellow-400">{{ $trophy->points }}</div>
                    <div class="text-white/60 text-sm">คะแนน</div>
                </div>
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-white">{{ $trophy->created_at->format('d/m/Y') }}</div>
                    <div class="text-white/60 text-sm">สร้างเมื่อ</div>
                </div>
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-white">{{ $trophy->updated_at->diffForHumans() }}</div>
                    <div class="text-white/60 text-sm">แก้ไขล่าสุด</div>
                </div>
            </div>

            {{-- Preview --}}
            <div class="mt-6 p-4 bg-white/5 rounded-lg">
                <h3 class="text-sm font-medium text-white/60 mb-3">ตัวอย่างถ้วยรางวัล</h3>
                <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 rounded-xl border border-yellow-500/30">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center text-3xl shadow-lg">
                        <span x-text="icon || '🏆'">{{ $trophy->icon ?? '🏆' }}</span>
                    </div>
                    <div>
                        <div class="font-bold text-white text-lg" x-text="name || 'ชื่อถ้วยรางวัล'">{{ $trophy->name }}</div>
                        <div class="text-white/60 text-sm" x-text="description || 'คำอธิบายถ้วยรางวัล'">{{ $trophy->description }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-500/30 text-yellow-300 rounded text-xs">
                                <i class="fas fa-star"></i>
                                <span x-text="points + ' คะแนน'">{{ $trophy->points }} คะแนน</span>
                            </span>
                            <span x-show="isAutomatic" class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-500/30 text-blue-300 rounded text-xs">
                                <i class="fas fa-magic"></i> อัตโนมัติ
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-4 mt-8">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white font-medium rounded-lg hover:from-yellow-600 hover:to-orange-600 transition shadow-lg">
                    <i class="fas fa-save"></i>
                    <span>บันทึกการแก้ไข</span>
                </button>
                <a href="{{ route('admin.platform-revenue.forum.trophies.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white/10 text-white font-medium rounded-lg hover:bg-white/20 transition">
                    <i class="fas fa-times"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="glass-card rounded-xl p-6 border border-red-500/30">
        <h3 class="text-lg font-bold text-red-400 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            พื้นที่อันตราย
        </h3>
        <p class="text-white/60 text-sm mb-4">
            การลบถ้วยรางวัลจะลบข้อมูลการมอบรางวัลทั้งหมดด้วย
        </p>
        <form action="{{ route('admin.platform-revenue.forum.trophies.delete', $trophy) }}" method="POST"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบถ้วยรางวัลนี้? การกระทำนี้ไม่สามารถยกเลิกได้')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-trash"></i>
                ลบถ้วยรางวัลนี้
            </button>
        </form>
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

@push('scripts')
<script>
function trophyForm() {
    return {
        name: @json(old('name', $trophy->name)),
        description: @json(old('description', $trophy->description ?? '')),
        icon: @json(old('icon', $trophy->icon ?? '🏆')),
        points: {{ old('points', $trophy->points) }},
        isAutomatic: {{ old('is_automatic', $trophy->is_automatic) ? 'true' : 'false' }}
    };
}
</script>
@endpush
@endsection
