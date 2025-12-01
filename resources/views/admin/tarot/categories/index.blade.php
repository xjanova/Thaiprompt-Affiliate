@extends('layouts.admin-v3')

@section('title', 'จัดการหมวดหมู่การทำนาย')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="categoryManager()">
    {{-- Header Section --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.tarot.index') }}"
                   class="p-2 rounded-xl bg-white/10 dark:bg-gray-800/50 hover:bg-white/20 dark:hover:bg-gray-700/50 transition-all">
                    <i class="fas fa-arrow-left text-gray-600 dark:text-gray-400"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
                            <i class="fas fa-folder-open text-white text-xl"></i>
                        </div>
                        จัดการหมวดหมู่การทำนาย
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1 ml-16">
                        จัดการหมวดหมู่สำหรับการดูดวงไพ่ทาโร่ต์
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.tarot.categories.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                <i class="fas fa-plus"></i>
                <span>เพิ่มหมวดหมู่ใหม่</span>
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass-fusion rounded-xl p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i class="fas fa-folder text-white"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $categories->count() }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">หมวดหมู่ทั้งหมด</div>
                </div>
            </div>
        </div>
        <div class="glass-fusion rounded-xl p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $categories->where('is_active', true)->count() }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">เปิดใช้งาน</div>
                </div>
            </div>
        </div>
        <div class="glass-fusion rounded-xl p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                    <i class="fas fa-gift text-white"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $categories->where('is_free_first', true)->count() }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">ฟรีครั้งแรก</div>
                </div>
            </div>
        </div>
        <div class="glass-fusion rounded-xl p-4 border border-white/20 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                    <i class="fas fa-baht-sign text-white"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $categories->where('price', '>', 0)->count() }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">แบบเสียเงิน</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Category Cards --}}
    @if($categories->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $category)
            <div class="glass-fusion rounded-2xl shadow-lg overflow-hidden border border-white/20 dark:border-white/10 group hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                {{-- Header with icon --}}
                <div class="h-24 relative overflow-hidden" style="background: linear-gradient(135deg, {{ $category->color }}40, {{ $category->color }}20);">
                    {{-- Decorative circles --}}
                    <div class="absolute -top-4 -right-4 w-24 h-24 rounded-full opacity-30" style="background: {{ $category->color }};"></div>
                    <div class="absolute top-8 right-8 w-12 h-12 rounded-full opacity-20" style="background: {{ $category->color }};"></div>

                    {{-- Icon --}}
                    <div class="absolute bottom-4 left-6">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg"
                             style="background: {{ $category->color }};">
                            <i class="fas {{ $category->icon ?? 'fa-star' }} text-white text-2xl"></i>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="absolute top-4 right-4">
                        @if($category->is_active)
                            <span class="px-3 py-1 bg-green-500/90 text-white text-xs font-medium rounded-full shadow-lg backdrop-blur-sm">
                                <i class="fas fa-check mr-1"></i> เปิดใช้งาน
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-500/90 text-white text-xs font-medium rounded-full shadow-lg backdrop-blur-sm">
                                <i class="fas fa-pause mr-1"></i> ปิดใช้งาน
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $category->name_th }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $category->name_en ?? '' }}</p>

                    @if($category->description_th)
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-3 line-clamp-2">
                            {{ $category->description_th }}
                        </p>
                    @endif

                    {{-- Price --}}
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                @if($category->price > 0)
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        ฿{{ number_format($category->price, 0) }}
                                    </div>
                                    @if($category->is_free_first)
                                        <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400 mt-1">
                                            <i class="fas fa-gift"></i>
                                            <span>ฟรีครั้งแรกต่อวัน</span>
                                        </div>
                                    @endif
                                @else
                                    <div class="text-2xl font-bold text-green-600">
                                        <i class="fas fa-gift mr-1"></i> ฟรี
                                    </div>
                                @endif
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.tarot.categories.edit', $category->id) }}"
                                   class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800/50 flex items-center justify-center transition-colors"
                                   title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button @click="deleteCategory({{ $category->id }}, '{{ $category->name_th }}')"
                                        class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800/50 flex items-center justify-center transition-colors"
                                        title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="glass-fusion rounded-2xl p-12 text-center border border-white/20 dark:border-white/10">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-folder-open text-4xl text-purple-500 dark:text-purple-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีหมวดหมู่</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นสร้างหมวดหมู่แรกของคุณเพื่อจัดระเบียบการทำนายไพ่ทาโร่ต์</p>
            <a href="{{ route('admin.tarot.categories.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                <i class="fas fa-plus"></i>
                <span>สร้างหมวดหมู่แรก</span>
            </a>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>

        {{-- Modal --}}
        <div class="relative glass-fusion rounded-2xl p-6 max-w-md w-full shadow-2xl border border-white/20"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash-alt text-2xl text-red-600 dark:text-red-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบ</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    คุณต้องการลบหมวดหมู่ "<span class="font-semibold text-gray-900 dark:text-white" x-text="deleteCategoryName"></span>" หรือไม่?
                </p>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = false"
                            class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        ยกเลิก
                    </button>
                    <form :action="deleteUrl" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">
                            <i class="fas fa-trash mr-2"></i> ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function categoryManager() {
    return {
        showDeleteModal: false,
        deleteCategoryId: null,
        deleteCategoryName: '',
        deleteUrl: '',

        deleteCategory(id, name) {
            this.deleteCategoryId = id;
            this.deleteCategoryName = name;
            this.deleteUrl = '{{ route("admin.tarot.categories.index") }}/' + id;
            this.showDeleteModal = true;
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
