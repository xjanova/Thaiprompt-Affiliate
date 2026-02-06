{{--
    หน้าแก้ไขหมวดหมู่ฟอรั่ม
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.admin-v3')

@section('title', 'แก้ไขหมวดหมู่ - ' . $category->name)

@section('content')
<div class="space-y-6" x-data="categoryForm()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-white/60 mb-2">
                <a href="{{ route('admin.platform-revenue.forum.categories.index') }}" class="hover:text-white transition">
                    จัดการหมวดหมู่
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-white">แก้ไข: {{ $category->name }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-white">✏️ แก้ไขหมวดหมู่ฟอรั่ม</h1>
            <p class="text-white/60 mt-1">แก้ไขรายละเอียดหมวดหมู่ "{{ $category->name }}"</p>
        </div>
        <a href="{{ route('admin.platform-revenue.forum.categories.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition">
            <i class="fas fa-arrow-left"></i>
            <span>กลับ</span>
        </a>
    </div>

    {{-- Form --}}
    <div class="glass-card rounded-xl p-6">
        <form action="{{ route('admin.platform-revenue.forum.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- ชื่อหมวดหมู่ --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        ชื่อหมวดหมู่ <span class="text-red-400">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $category->name) }}"
                           required
                           placeholder="เช่น ถามตอบทั่วไป"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('name')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- หมวดหมู่หลัก --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        หมวดหมู่หลัก (ถ้าเป็นหมวดหมู่ย่อย)
                    </label>
                    <select name="parent_id"
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- ไม่มี (เป็นหมวดหมู่หลัก) --</option>
                        @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->icon ?? '📁' }} {{ $parent->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('parent_id')
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
                               value="{{ old('icon', $category->icon ?? '📁') }}"
                               maxlength="10"
                               placeholder="📁"
                               class="w-24 px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white text-center text-2xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <div class="flex gap-2 flex-wrap">
                            @foreach(['📁', '💬', '❓', '📢', '🎮', '💻', '📚', '🛒', '💡', '🔧'] as $emoji)
                            <button type="button"
                                    @click="icon = '{{ $emoji }}'"
                                    class="w-10 h-10 text-xl bg-white/10 hover:bg-white/20 rounded-lg transition">
                                {{ $emoji }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @error('icon')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- สี --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        สี
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="text"
                               name="color"
                               x-model="color"
                               value="{{ old('color', $category->color ?? 'blue') }}"
                               placeholder="blue"
                               class="flex-1 px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <div class="flex gap-2">
                            @foreach(['blue', 'green', 'purple', 'red', 'orange', 'pink'] as $c)
                            <button type="button"
                                    @click="color = '{{ $c }}'"
                                    class="w-8 h-8 rounded-lg bg-{{ $c }}-500 hover:ring-2 hover:ring-white transition"
                                    :class="{ 'ring-2 ring-white': color === '{{ $c }}' }">
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @error('color')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- คำอธิบาย --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description"
                              rows="3"
                              placeholder="อธิบายว่าหมวดหมู่นี้ใช้สำหรับอะไร..."
                              class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ลำดับ --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        ลำดับ
                    </label>
                    <input type="number"
                           name="order"
                           value="{{ old('order', $category->order) }}"
                           min="0"
                           placeholder="0"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-white/40 text-xs mt-1">ตัวเลขน้อยจะแสดงก่อน</p>
                    @error('order')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- สถานะ --}}
                <div>
                    <label class="block text-sm font-medium text-white/80 mb-2">
                        สถานะ
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_locked"
                               value="1"
                               {{ old('is_locked', $category->is_locked) ? 'checked' : '' }}
                               class="w-5 h-5 rounded bg-white/10 border-white/20 text-red-500 focus:ring-red-500">
                        <span class="text-white/80">
                            <i class="fas fa-lock text-red-400 mr-1"></i>
                            ล็อคหมวดหมู่ (ไม่อนุญาตให้สร้างกระทู้ใหม่)
                        </span>
                    </label>
                </div>
            </div>

            {{-- Stats --}}
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-white">{{ $category->threads_count ?? 0 }}</div>
                    <div class="text-white/60 text-sm">กระทู้</div>
                </div>
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-white">{{ $category->children_count ?? 0 }}</div>
                    <div class="text-white/60 text-sm">หมวดหมู่ย่อย</div>
                </div>
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-white">{{ $category->created_at->format('d/m/Y') }}</div>
                    <div class="text-white/60 text-sm">สร้างเมื่อ</div>
                </div>
                <div class="p-4 bg-white/5 rounded-lg text-center">
                    <div class="text-2xl font-bold text-white">{{ $category->updated_at->diffForHumans() }}</div>
                    <div class="text-white/60 text-sm">แก้ไขล่าสุด</div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-4 mt-8">
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white font-medium rounded-lg hover:from-blue-600 hover:to-purple-600 transition shadow-lg">
                    <i class="fas fa-save"></i>
                    <span>บันทึกการแก้ไข</span>
                </button>
                <a href="{{ route('admin.platform-revenue.forum.categories.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white/10 text-white font-medium rounded-lg hover:bg-white/20 transition">
                    <i class="fas fa-times"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    @if(($category->threads_count ?? 0) == 0 && ($category->children_count ?? 0) == 0)
    <div class="glass-card rounded-xl p-6 border border-red-500/30">
        <h3 class="text-lg font-bold text-red-400 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            พื้นที่อันตราย
        </h3>
        <p class="text-white/60 text-sm mb-4">
            การลบหมวดหมู่จะเป็นการถาวร ไม่สามารถกู้คืนได้
        </p>
        <form action="{{ route('admin.platform-revenue.forum.categories.delete', $category) }}" method="POST"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่นี้?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-trash"></i>
                ลบหมวดหมู่นี้
            </button>
        </form>
    </div>
    @endif
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
function categoryForm() {
    return {
        icon: @json(old('icon', $category->icon ?? '📁')),
        color: @json(old('color', $category->color ?? 'blue'))
    };
}
</script>
@endpush
@endsection
