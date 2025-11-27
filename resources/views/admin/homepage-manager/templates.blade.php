@extends('layouts.admin')

@section('title', $pageTitle ?? 'คลัง Templates หน้าแรก')

@section('styles')
<style>
    /* Template card hover effects */
    .template-card {
        transition: all 0.3s ease;
    }
    .template-card:hover {
        transform: translateY(-4px);
    }
    .template-card:hover .template-overlay {
        opacity: 1;
    }
    .template-overlay {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
</style>
@endsection

@section('content')
<div x-data="templatesGallery()" x-init="init()" class="min-h-screen bg-gray-100 dark:bg-gray-900">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                {{-- Title --}}
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-layer-group mr-3 text-indigo-500"></i>
                        คลัง Templates หน้าแรก
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        เลือก template สำเร็จรูปเพื่อใช้งานกับหน้าแรกของคุณ
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.homepage-manager.index') }}"
                       class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับไป Visual Editor
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                {{-- Search --}}
                <div class="flex-1">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.300ms="filterTemplates()"
                               placeholder="ค้นหา template..."
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>

                {{-- Category Filter --}}
                <div class="flex flex-wrap gap-2">
                    <button @click="selectedCategory = 'all'; filterTemplates()"
                            :class="selectedCategory === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg transition hover:opacity-90">
                        ทั้งหมด
                    </button>
                    @foreach($categories as $key => $label)
                    <button @click="selectedCategory = '{{ $key }}'; filterTemplates()"
                            :class="selectedCategory === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg transition hover:opacity-90">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-layer-group text-indigo-600 dark:text-indigo-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $templates->count() }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Templates ทั้งหมด</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-gift text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $templates->where('is_premium', false)->count() }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Templates ฟรี</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-crown text-amber-600 dark:text-amber-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $templates->where('is_premium', true)->count() }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Templates Premium</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $templates->sum('usage_count') }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ครั้งที่ใช้งาน</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Templates Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($templates as $template)
            <div class="template-card bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden"
                 x-show="shouldShowTemplate({{ json_encode($template->toApiArray()) }})"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                {{-- Thumbnail --}}
                <div class="relative aspect-video bg-gradient-to-br from-indigo-500 to-purple-600 group">
                    @if($template->thumbnail)
                    <img src="{{ $template->thumbnail }}"
                         alt="{{ $template->name }}"
                         class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-layer-group text-5xl text-white/30 mb-2"></i>
                            <p class="text-white/50 text-sm">{{ $template->name }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- Overlay --}}
                    <div class="template-overlay absolute inset-0 bg-black/60 flex items-center justify-center gap-3">
                        <button @click="previewTemplate({{ $template->id }})"
                                class="px-4 py-2 bg-white text-gray-900 rounded-lg hover:bg-gray-100 transition flex items-center">
                            <i class="fas fa-eye mr-2"></i>
                            ดูตัวอย่าง
                        </button>
                        <button @click="importTemplate({{ $template->id }}, '{{ addslashes($template->name) }}')"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            ใช้งาน
                        </button>
                    </div>

                    {{-- Premium Badge --}}
                    @if($template->is_premium)
                    <div class="absolute top-3 right-3 px-2 py-1 bg-amber-500 text-white text-xs font-semibold rounded-full flex items-center">
                        <i class="fas fa-crown mr-1"></i>
                        Premium
                    </div>
                    @else
                    <div class="absolute top-3 right-3 px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
                        ฟรี
                    </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $template->name }}</h3>
                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded">
                            {{ $categories[$template->category] ?? $template->category }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
                        {{ $template->description ?? 'ไม่มีคำอธิบาย' }}
                    </p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-500">
                            <i class="fas fa-puzzle-piece mr-1"></i>
                            {{ count($template->sections_data ?? []) }} sections
                        </span>
                        <span class="text-gray-500 dark:text-gray-500">
                            <i class="fas fa-download mr-1"></i>
                            ใช้งาน {{ number_format($template->usage_count) }} ครั้ง
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full py-16 text-center">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-layer-group text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ไม่พบ Templates</h3>
                <p class="text-gray-600 dark:text-gray-400">ยังไม่มี template ในระบบ</p>
            </div>
            @endforelse
        </div>

        {{-- No Results Message --}}
        <div x-show="filteredCount === 0 && {{ $templates->count() }} > 0"
             x-cloak
             class="col-span-full py-16 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ไม่พบผลลัพธ์</h3>
            <p class="text-gray-600 dark:text-gray-400">ลองปรับเงื่อนไขการค้นหา</p>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div x-show="showPreviewModal"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            {{-- Backdrop --}}
            <div @click="showPreviewModal = false" class="fixed inset-0 bg-gray-900/75 transition-opacity"></div>

            {{-- Modal Content --}}
            <div x-show="showPreviewModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl transform w-full max-w-5xl mx-auto overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="previewTemplate.name"></h3>
                    <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                {{-- Preview Area --}}
                <div class="p-6 max-h-[70vh] overflow-y-auto bg-gray-100 dark:bg-gray-900">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        {{-- Placeholder for preview --}}
                        <div class="aspect-video bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center">
                            <div class="text-center p-8">
                                <i class="fas fa-image text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400">Preview จะแสดงที่นี่</p>
                            </div>
                        </div>

                        {{-- Sections Preview --}}
                        <div class="p-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Sections ใน Template นี้:</h4>
                            <template x-if="previewTemplate.sections">
                                <div class="space-y-2">
                                    <template x-for="(section, index) in previewTemplate.sections" :key="index">
                                        <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <span class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold mr-3" x-text="index + 1"></span>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white" x-text="section.name"></p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="'Type: ' + section.type"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <button @click="showPreviewModal = false"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        ปิด
                    </button>
                    <button @click="importTemplate(previewTemplate.id, previewTemplate.name); showPreviewModal = false"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
                        <i class="fas fa-download mr-2"></i>
                        ใช้งาน Template นี้
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Import Modal --}}
    <div x-show="showConfirmModal"
         x-cloak
         x-transition
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="confirm-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div @click="showConfirmModal = false" class="fixed inset-0 bg-gray-900/75 transition-opacity"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl transform w-full max-w-md mx-auto p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยืนยันการนำเข้า Template</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        คุณต้องการนำเข้า template "<span class="font-semibold" x-text="confirmTemplateName"></span>" หรือไม่?
                        <br><br>
                        <span class="text-sm text-amber-600 dark:text-amber-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            Sections จาก template จะถูกเพิ่มเข้าไปในหน้าแรกปัจจุบัน
                        </span>
                    </p>
                    <div class="flex justify-center gap-3">
                        <button @click="showConfirmModal = false"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ยกเลิก
                        </button>
                        <button @click="confirmImport()"
                                :disabled="importing"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center disabled:opacity-50">
                            <i class="fas fa-download mr-2" :class="importing && 'fa-spin'"></i>
                            <span x-text="importing ? 'กำลังนำเข้า...' : 'นำเข้า Template'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function templatesGallery() {
    return {
        // ข้อมูล templates จาก PHP
        templates: @json($templates->map->toApiArray()),

        // ตัวกรอง
        searchQuery: '',
        selectedCategory: 'all',
        filteredCount: {{ $templates->count() }},

        // Modal states
        showPreviewModal: false,
        showConfirmModal: false,
        previewTemplate: {},
        confirmTemplateId: null,
        confirmTemplateName: '',
        importing: false,

        /**
         * เริ่มต้นการทำงาน
         */
        init() {
            // Initialize
        },

        /**
         * กรอง templates
         */
        filterTemplates() {
            let count = 0;
            this.templates.forEach(template => {
                if (this.shouldShowTemplate(template)) {
                    count++;
                }
            });
            this.filteredCount = count;
        },

        /**
         * ตรวจสอบว่าควรแสดง template หรือไม่
         */
        shouldShowTemplate(template) {
            // Filter by category
            if (this.selectedCategory !== 'all' && template.category !== this.selectedCategory) {
                return false;
            }

            // Filter by search
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                const matchName = template.name.toLowerCase().includes(query);
                const matchDesc = (template.description || '').toLowerCase().includes(query);
                const matchCategory = (template.category_label || '').toLowerCase().includes(query);
                if (!matchName && !matchDesc && !matchCategory) {
                    return false;
                }
            }

            return true;
        },

        /**
         * ดูตัวอย่าง template
         */
        previewTemplate(id) {
            const template = this.templates.find(t => t.id === id);
            if (template) {
                // ดึง sections data จาก server
                fetch(`{{ url('admin/homepage-manager/templates') }}/${id}/preview`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.previewTemplate = {
                                ...template,
                                sections: data.sections
                            };
                            this.showPreviewModal = true;
                        }
                    })
                    .catch(error => {
                        // ถ้า endpoint ไม่มี ก็แสดง modal พร้อมข้อมูลพื้นฐาน
                        this.previewTemplate = template;
                        this.showPreviewModal = true;
                    });
            }
        },

        /**
         * เปิด confirm modal สำหรับนำเข้า template
         */
        importTemplate(id, name) {
            this.confirmTemplateId = id;
            this.confirmTemplateName = name;
            this.showConfirmModal = true;
        },

        /**
         * ยืนยันการนำเข้า template
         */
        async confirmImport() {
            if (!this.confirmTemplateId) return;

            this.importing = true;

            try {
                const response = await fetch(`{{ route('admin.homepage-manager.templates.import', '') }}/${this.confirmTemplateId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // แสดง success notification
                    this.showNotification('success', `นำเข้า template สำเร็จ! (${data.sections_count} sections)`);

                    // ปิด modal
                    this.showConfirmModal = false;

                    // ถามว่าจะไป Visual Editor หรือไม่
                    setTimeout(() => {
                        if (confirm('ต้องการไปยัง Visual Editor เพื่อแก้ไข sections ที่นำเข้าหรือไม่?')) {
                            window.location.href = '{{ route("admin.homepage-manager.index") }}';
                        }
                    }, 500);
                } else {
                    this.showNotification('error', data.message || 'เกิดข้อผิดพลาดในการนำเข้า template');
                }
            } catch (error) {
                console.error('Import error:', error);
                this.showNotification('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                this.importing = false;
            }
        },

        /**
         * แสดง notification
         */
        showNotification(type, message) {
            // ถ้ามี notification system ในโปรเจค
            if (typeof showToast !== 'undefined') {
                showToast(type, message);
            } else {
                alert(message);
            }
        }
    }
}
</script>
@endsection
