@extends('layouts.admin')

@section('title', 'แก้ไขเทมเพลต - ' . $template->name)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #e0e7ff;
    }

    .sortable-chosen {
        background: #eef2ff;
    }

    .component-card:hover {
        transform: translateY(-2px);
    }

    .section-card {
        transition: all 0.2s;
    }

    .section-card:hover {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@section('content')
<div x-data="templateBuilder()" x-init="init()">
    <!-- Top Bar -->
    <div class="mb-6 flex items-center justify-between bg-white p-4 rounded-lg shadow-sm">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.templates.index') }}"
               class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
                <p class="text-sm text-gray-600">แก้ไขเทมเพลต - <span x-text="sections.length"></span> ส่วน</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button @click="previewMode = !previewMode"
                    class="flex items-center gap-2 px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:border-indigo-600 hover:text-indigo-600 transition-all"
                    :class="{ 'border-indigo-600 text-indigo-600 bg-indigo-50': previewMode }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span x-text="previewMode ? 'กลับสู่การแก้ไข' : 'ดูตัวอย่าง'"></span>
            </button>

            <button @click="saveTemplate"
                    :disabled="saving"
                    class="flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 shadow-lg disabled:opacity-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                <span x-show="!saving">บันทึก</span>
                <span x-show="saving">กำลังบันทึก...</span>
            </button>
        </div>
    </div>

    <!-- Main Content: 3 Columns Layout -->
    <div class="grid grid-cols-12 gap-6" x-show="!previewMode">
        <!-- Left Sidebar: Component Library -->
        <div class="col-span-3">
            <div class="bg-white rounded-lg shadow-lg p-4 sticky top-4 max-h-[calc(100vh-120px)] overflow-y-auto">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-2xl">🧩</span>
                    <span>Component Library</span>
                </h2>

                <!-- Search -->
                <input type="text"
                       x-model="componentSearch"
                       placeholder="ค้นหา component..."
                       class="w-full px-3 py-2 mb-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">

                <!-- Component Categories -->
                <div class="space-y-4">
                    <template x-for="(category, type) in componentLibrary" :key="type">
                        <div x-show="matchesSearch(category)">
                            <div class="flex items-center gap-2 mb-2">
                                <span x-text="category.icon" class="text-xl"></span>
                                <h3 class="font-semibold text-gray-900 text-sm" x-text="category.name"></h3>
                                <span class="text-xs text-gray-500" x-text="`(${Object.keys(category.templates).length})`"></span>
                            </div>

                            <div class="space-y-2 ml-2">
                                <template x-for="(template, key) in category.templates" :key="key">
                                    <div @click="addSection(type, key)"
                                         class="component-card p-3 bg-gradient-to-br from-gray-50 to-gray-100 hover:from-indigo-50 hover:to-purple-50 border border-gray-200 rounded-lg cursor-pointer transition-all">
                                        <p class="text-sm font-medium text-gray-900" x-text="template.name"></p>
                                        <p class="text-xs text-gray-500 mt-1 line-clamp-2" x-text="template.description || 'คลิกเพื่อเพิ่ม'"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Center: Canvas (Sections) -->
        <div class="col-span-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">📄 Canvas</h2>
                    <div class="text-sm text-gray-600">
                        <span x-text="sections.length"></span> ส่วน
                    </div>
                </div>

                <!-- Sections List (Sortable) -->
                <div id="sections-container" class="space-y-4">
                    <template x-for="(section, index) in sections" :key="section.id">
                        <div :data-id="section.id"
                             class="section-card bg-gray-50 border-2 rounded-lg overflow-hidden"
                             :class="{ 'border-indigo-600 ring-2 ring-indigo-200': selectedSection && selectedSection.id === section.id, 'border-gray-200': !selectedSection || selectedSection.id !== section.id }">

                            <!-- Section Header -->
                            <div class="flex items-center justify-between p-4 bg-white cursor-move">
                                <div class="flex items-center gap-3 flex-1">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900" x-text="section.name || section.component_type"></h3>
                                        <p class="text-xs text-gray-500" x-text="section.component_type"></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Active Toggle -->
                                    <button @click="toggleSectionActive(section)"
                                            class="p-2 rounded hover:bg-gray-100"
                                            :class="section.is_active ? 'text-green-600' : 'text-gray-400'">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                        </svg>
                                    </button>

                                    <!-- Edit Button -->
                                    <button @click="selectSection(section)"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <button @click="deleteSection(section.id)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Section Preview -->
                            <div class="p-4 bg-gray-50 text-sm text-gray-600">
                                <div class="prose prose-sm max-w-none">
                                    <template x-if="section.content && section.content.title">
                                        <p class="font-medium" x-text="section.content.title"></p>
                                    </template>
                                    <template x-if="section.content && section.content.subtitle">
                                        <p class="text-xs" x-text="section.content.subtitle"></p>
                                    </template>
                                    <template x-if="!section.content || (!section.content.title && !section.content.subtitle)">
                                        <p class="text-gray-400 italic">ไม่มีข้อมูลตัวอย่าง</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="sections.length === 0" class="text-center py-12 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-lg font-medium mb-2">เทมเพลตว่างเปล่า</p>
                    <p class="text-sm">เริ่มต้นสร้างโดยการเลือก Component จากด้านซ้าย</p>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Properties Panel -->
        <div class="col-span-3">
            <div class="bg-white rounded-lg shadow-lg p-4 sticky top-4 max-h-[calc(100vh-120px)] overflow-y-auto">
                <!-- Template Info -->
                <div x-show="!selectedSection" class="text-center py-8">
                    <div class="text-5xl mb-4">⚙️</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">ตั้งค่าเทมเพลต</h3>
                    <p class="text-sm text-gray-600 mb-4">คลิกที่ส่วนใด ๆ เพื่อแก้ไข</p>

                    <!-- Template Settings -->
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อเทมเพลต</label>
                            <input type="text"
                                   x-model="templateName"
                                   @change="updateTemplateName"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย</label>
                            <textarea x-model="templateDescription"
                                     @change="updateTemplateDescription"
                                     rows="3"
                                     class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">สถานะ</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       x-model="templateActive"
                                       @change="updateTemplateActive"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section Properties -->
                <div x-show="selectedSection">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">🎨 แก้ไขส่วน</h3>

                    <template x-if="selectedSection">
                        <div class="space-y-4">
                            <!-- Section Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อส่วน</label>
                                <input type="text"
                                       x-model="selectedSection.name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            <!-- Content Fields (Dynamic based on component type) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">เนื้อหา</label>
                                <div class="space-y-3">
                                    <template x-if="selectedSection.content">
                                        <template x-for="(value, key) in selectedSection.content" :key="key">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1 capitalize" x-text="key.replace('_', ' ')"></label>
                                                <input type="text"
                                                       x-model="selectedSection.content[key]"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </div>

                            <!-- Styles -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">สไตล์</label>
                                <div class="space-y-3">
                                    <template x-if="selectedSection.styles">
                                        <template x-for="(value, key) in selectedSection.styles" :key="key">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1 capitalize" x-text="key.replace('_', ' ')"></label>

                                                <template x-if="key.includes('color')">
                                                    <input type="color"
                                                           x-model="selectedSection.styles[key]"
                                                           class="w-full h-10 border border-gray-300 rounded-lg">
                                                </template>

                                                <template x-if="!key.includes('color')">
                                                    <input type="text"
                                                           x-model="selectedSection.styles[key]"
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                </template>
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="pt-4 border-t space-y-2">
                                <button @click="duplicateSection(selectedSection.id)"
                                        class="w-full px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 text-sm font-medium">
                                    🔄 คัดลอกส่วนนี้
                                </button>
                                <button @click="selectedSection = null"
                                        class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                                    ✕ ปิด
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Mode -->
    <div x-show="previewMode" class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">👁️ ตัวอย่างเทมเพลต</h2>

        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 bg-gray-50">
            <template x-for="section in activeSections" :key="section.id">
                <div class="mb-8 p-6 bg-white rounded-lg shadow-sm"
                     :style="`background-color: ${section.styles?.background_color || '#ffffff'}; padding: ${section.styles?.padding_y || 40}px 20px;`">
                    <div class="max-w-4xl mx-auto">
                        <h2 class="text-3xl font-bold mb-4"
                            :style="`color: ${section.styles?.text_color || '#000000'};`"
                            x-text="section.content?.title || 'Untitled Section'"></h2>
                        <p class="text-lg text-gray-600" x-text="section.content?.subtitle || section.content?.description || ''"></p>
                    </div>
                </div>
            </template>

            <div x-show="activeSections.length === 0" class="text-center py-12 text-gray-400">
                <p>ไม่มีส่วนที่เปิดใช้งาน</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function templateBuilder() {
    return {
        template: @json($template),
        sections: [],
        componentLibrary: @json($componentLibrary),
        selectedSection: null,
        componentSearch: '',
        previewMode: false,
        saving: false,
        sortable: null,
        templateName: '{{ $template->name }}',
        templateDescription: '{{ $template->description }}',
        templateActive: {{ $template->is_active ? 'true' : 'false' }},

        get activeSections() {
            return this.sections.filter(s => s.is_active);
        },

        async init() {
            await this.loadSections();
            this.initSortable();
        },

        async loadSections() {
            try {
                const response = await fetch(`/admin/templates/{{ $template->id }}/sections`);
                const data = await response.json();
                this.sections = data.sections;
            } catch (error) {
                console.error('Error loading sections:', error);
            }
        },

        initSortable() {
            const el = document.getElementById('sections-container');
            this.sortable = Sortable.create(el, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: () => {
                    this.reorderSections();
                }
            });
        },

        matchesSearch(category) {
            if (!this.componentSearch) return true;
            const search = this.componentSearch.toLowerCase();
            return category.name.toLowerCase().includes(search) ||
                   Object.values(category.templates).some(t => t.name.toLowerCase().includes(search));
        },

        async addSection(componentType, templateKey) {
            try {
                const response = await fetch(`/admin/templates/{{ $template->id }}/sections`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        component_type: componentType,
                        template_key: templateKey
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.sections.push(data.section);
                }
            } catch (error) {
                console.error('Error adding section:', error);
            }
        },

        selectSection(section) {
            this.selectedSection = section;
        },

        async toggleSectionActive(section) {
            section.is_active = !section.is_active;
            await this.updateSection(section);
        },

        async updateSection(section) {
            try {
                await fetch(`/admin/templates/{{ $template->id }}/sections/${section.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(section)
                });
            } catch (error) {
                console.error('Error updating section:', error);
            }
        },

        async deleteSection(sectionId) {
            if (!confirm('ต้องการลบส่วนนี้?')) return;

            try {
                const response = await fetch(`/admin/templates/{{ $template->id }}/sections/${sectionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.sections = this.sections.filter(s => s.id !== sectionId);
                    if (this.selectedSection && this.selectedSection.id === sectionId) {
                        this.selectedSection = null;
                    }
                }
            } catch (error) {
                console.error('Error deleting section:', error);
            }
        },

        async duplicateSection(sectionId) {
            try {
                const response = await fetch(`/admin/templates/{{ $template->id }}/sections/${sectionId}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.sections.push(data.section);
                }
            } catch (error) {
                console.error('Error duplicating section:', error);
            }
        },

        async reorderSections() {
            const sectionIds = Array.from(document.querySelectorAll('#sections-container > div')).map(el => el.dataset.id);

            try {
                await fetch(`/admin/templates/{{ $template->id }}/sections/reorder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ section_ids: sectionIds })
                });
            } catch (error) {
                console.error('Error reordering sections:', error);
            }
        },

        async saveTemplate() {
            this.saving = true;

            // Update all sections
            for (const section of this.sections) {
                await this.updateSection(section);
            }

            this.saving = false;
            alert('บันทึกเทมเพลตสำเร็จ!');
        },

        async updateTemplateName() {
            await this.updateTemplateInfo();
        },

        async updateTemplateDescription() {
            await this.updateTemplateInfo();
        },

        async updateTemplateActive() {
            await this.updateTemplateInfo();
        },

        async updateTemplateInfo() {
            try {
                await fetch(`/admin/templates/{{ $template->id }}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.templateName,
                        description: this.templateDescription,
                        is_active: this.templateActive
                    })
                });
            } catch (error) {
                console.error('Error updating template:', error);
            }
        }
    };
}
</script>
@endsection
