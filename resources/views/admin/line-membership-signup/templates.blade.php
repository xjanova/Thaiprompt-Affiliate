@extends('layouts.admin-v3')

@section('title', 'LINE Flex Message Templates')

@section('content')
{{--
    LINE Flex Message Templates - V3 Theme
    ใช้ Tailwind CSS + Alpine.js
    Dark Mode Support + Responsive
--}}
<div class="min-h-screen" x-data="templatesManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-[#00B900] via-[#06C755] to-[#00E600] p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <i class="fas fa-file-code text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                            Flex Message Templates
                        </h1>
                        <p class="text-white/90 text-sm md:text-base mt-1">
                            จัดการ LINE Flex Message templates สำหรับ signup flow
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.line-membership-signup.templates.create') }}"
                   class="px-6 py-3 bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 rounded-xl text-white font-bold transition-all duration-300 hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    <span>สร้าง Template ใหม่</span>
                </a>

                <a href="{{ route('admin.line-membership-signup.index') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 rounded-xl text-white font-medium hover:bg-white/30 transition-all duration-300 flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับ Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Templates Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            {{-- Template Header --}}
            <div class="p-6 bg-gradient-to-br from-purple-400 to-pink-500 text-white">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold mb-1">{{ $template->template_name }}</h3>
                        <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs font-mono">
                            <i class="fas fa-key text-xs"></i>
                            {{ $template->template_key }}
                        </div>
                    </div>
                </div>
                @if($template->description)
                <p class="text-sm text-white/90 line-clamp-2">{{ $template->description }}</p>
                @endif
            </div>

            {{-- Template Body --}}
            <div class="p-6">
                <div class="space-y-3">
                    {{-- Variables --}}
                    @if($template->variables && count($template->variables) > 0)
                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">
                            <i class="fas fa-code mr-1"></i>
                            Variables
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($template->variables as $variable)
                            <span class="inline-flex px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-xs font-mono">
                                {{ $variable }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Metadata --}}
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>
                                <i class="fas fa-calendar mr-1"></i>
                                {{ $template->created_at->format('d M Y') }}
                            </span>
                            <span>
                                <i class="fas fa-clock mr-1"></i>
                                {{ $template->updated_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Template Actions --}}
            <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-2 mb-2">
                    {{-- View Button --}}
                    <button
                        @click="viewTemplate({{ json_encode($template) }})"
                        class="px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium transition-all duration-300 text-xs flex items-center justify-center gap-1.5"
                    >
                        <i class="fas fa-eye"></i>
                        <span>ดู</span>
                    </button>

                    {{-- Edit Button --}}
                    <a
                        href="{{ route('admin.line-membership-signup.templates.edit', $template) }}"
                        class="px-3 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-lg font-medium transition-all duration-300 text-xs flex items-center justify-center gap-1.5"
                    >
                        <i class="fas fa-edit"></i>
                        <span>แก้ไข</span>
                    </a>

                    {{-- Duplicate Button --}}
                    <button
                        @click="duplicateTemplate({{ $template->id }})"
                        class="px-3 py-2 bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-600 hover:to-teal-600 text-white rounded-lg font-medium transition-all duration-300 text-xs flex items-center justify-center gap-1.5"
                    >
                        <i class="fas fa-copy"></i>
                        <span>คัดลอก</span>
                    </button>

                    {{-- Reset Button (สำหรับ default templates เท่านั้น) --}}
                    @if($template->is_default)
                    <button
                        @click="resetTemplate({{ $template->id }})"
                        class="px-3 py-2 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-lg font-medium transition-all duration-300 text-xs flex items-center justify-center gap-1.5"
                    >
                        <i class="fas fa-undo"></i>
                        <span>Reset</span>
                    </button>
                    @else
                    {{-- Delete Button (สำหรับ non-default templates) --}}
                    <button
                        @click="deleteTemplate({{ $template->id }})"
                        class="px-3 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg font-medium transition-all duration-300 text-xs flex items-center justify-center gap-1.5"
                    >
                        <i class="fas fa-trash"></i>
                        <span>ลบ</span>
                    </button>
                    @endif
                </div>

                {{-- Template Status Badges --}}
                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    @if($template->is_default)
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">
                        <i class="fas fa-star"></i>
                        Default
                    </span>
                    @endif

                    @if($template->is_active)
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">
                        <i class="fas fa-check-circle"></i>
                        ใช้งาน
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs font-medium">
                        <i class="fas fa-times-circle"></i>
                        ปิด
                    </span>
                    @endif

                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs font-medium ml-auto">
                        <i class="fas fa-chart-line"></i>
                        {{ number_format($template->usage_count) }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-code text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Templates</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">เริ่มต้นสร้าง Flex Message template แรกของคุณ</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- View Template Modal --}}
    <div
        x-show="showModal"
        x-cloak
        @click.self="closeModal()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            @click.stop
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
        >
            {{-- Modal Header --}}
            <div class="p-6 bg-gradient-to-r from-purple-500 to-pink-500 text-white">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold" x-text="currentTemplate?.template_name"></h3>
                    <button
                        @click="closeModal()"
                        class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/30 transition-colors duration-200"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-white/90 text-sm mt-1" x-text="currentTemplate?.description"></p>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
                <div class="space-y-4">
                    {{-- Template Key --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">
                            Template Key
                        </label>
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 rounded-lg font-mono text-sm text-gray-900 dark:text-white">
                            <span x-text="currentTemplate?.template_key"></span>
                        </div>
                    </div>

                    {{-- Flex Message JSON --}}
                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">
                            Flex Message JSON
                        </label>
                        <pre class="p-4 bg-gray-900 dark:bg-black rounded-lg overflow-x-auto text-xs text-green-400 font-mono max-h-96"><code x-text="JSON.stringify(currentTemplate?.flex_message_json, null, 2)"></code></pre>
                    </div>

                    {{-- Variables --}}
                    <div x-show="currentTemplate?.variables && currentTemplate.variables.length > 0">
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">
                            Variables
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="variable in currentTemplate?.variables" :key="variable">
                                <span class="inline-flex px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-sm font-mono">
                                    <span x-text="variable"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button
                    @click="closeModal()"
                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all duration-300"
                >
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Templates Manager - Alpine.js Component
 *
 * จัดการ state และ actions สำหรับ templates management
 */
function templatesManager() {
    return {
        showModal: false,
        currentTemplate: null,

        /**
         * เริ่มต้น component
         */
        init() {
            // เพิ่ม functionality เพิ่มเติมได้ที่นี่
        },

        /**
         * แสดง template details
         */
        viewTemplate(template) {
            this.currentTemplate = template;
            this.showModal = true;
        },

        /**
         * ปิด modal
         */
        closeModal() {
            this.showModal = false;
            setTimeout(() => {
                this.currentTemplate = null;
            }, 300);
        },

        /**
         * Reset template กลับไปค่าเริ่มต้น
         */
        async resetTemplate(templateId) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะ Reset template นี้กลับไปค่าเริ่มต้น?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/line-membership-signup/templates/${templateId}/reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Reset error:', error);
                alert('❌ เกิดข้อผิดพลาดในการ reset template');
            }
        },

        /**
         * Duplicate template
         */
        async duplicateTemplate(templateId) {
            if (!confirm('คุณต้องการคัดลอก template นี้หรือไม่?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/line-membership-signup/templates/${templateId}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Duplicate error:', error);
                alert('❌ เกิดข้อผิดพลาดในการคัดลอก template');
            }
        },

        /**
         * Delete template
         */
        async deleteTemplate(templateId) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบ template นี้? การลบไม่สามารถยกเลิกได้!')) {
                return;
            }

            try {
                const response = await fetch(`/admin/line-membership-signup/templates/${templateId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('❌ เกิดข้อผิดพลาดในการลบ template');
            }
        }
    };
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
@endpush
@endsection
