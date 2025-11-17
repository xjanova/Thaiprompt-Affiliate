@extends('layouts.admin-v3')

@section('title', 'Visual Page Builder')

@push('styles')
<!-- SortableJS CSS -->
<style>
    .builder-layout {
        display: grid;
        grid-template-columns: 280px 1fr 320px;
        gap: 1rem;
        height: calc(100vh - 140px);
        overflow: hidden;
    }
    @media (max-width: 1280px) {
        .builder-layout {
            grid-template-columns: 1fr;
            height: auto;
        }
    }
    .component-item {
        transition: all 0.2s;
        cursor: grab;
    }
    .component-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .component-item:active {
        cursor: grabbing;
    }
    .section-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 2px solid #e5e7eb;
        transition: all 0.2s;
        cursor: move;
    }
    .dark .section-card {
        background: #1e293b;
        border-color: #334155;
    }
    .section-card:hover {
        border-color: #6366f1;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    }
    .dark .section-card:hover {
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .section-card.sortable-ghost {
        opacity: 0.4;
        background: #f3f4f6;
    }
    .dark .section-card.sortable-ghost {
        background: #0f172a;
    }
    .section-card.sortable-chosen {
        border-color: #6366f1;
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2);
    }
    .dark .section-card.sortable-chosen {
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
    }
    .canvas-empty {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 4rem;
        text-align: center;
        color: #9ca3af;
    }
    .dark .canvas-empty {
        border-color: #475569;
        color: #64748b;
    }
    .properties-panel {
        position: sticky;
        top: 1rem;
        height: fit-content;
        max-height: calc(100vh - 160px);
        overflow-y: auto;
    }
    .toolbar {
        position: sticky;
        top: 0;
        z-index: 100;
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem;
        margin: -1.5rem -1.5rem 1.5rem -1.5rem;
    }
    .dark .toolbar {
        background: #1e293b;
        border-bottom-color: #334155;
    }
</style>
@endpush

@section('content')
<div x-data="visualBuilder()" x-init="init()" class="space-y-4">

    <!-- Toolbar -->
    <div class="toolbar flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                🎨 Visual Page Builder
            </h1>
            <select x-model="currentPage" @change="loadSections()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl">
                <option value="home">หน้าแรก</option>
                <option value="about">เกี่ยวกับเรา</option>
                <option value="contact">ติดต่อเรา</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <!-- Preview Modes -->
            <div class="flex items-center gap-1 bg-gray-100/50 dark:bg-gray-800/50 rounded-xl p-1">
                <button @click="previewMode = 'desktop'" :class="previewMode === 'desktop' ? 'glass-fusion shadow' : ''" class="px-3 py-2 rounded text-sm transition">
                    🖥️ Desktop
                </button>
                <button @click="previewMode = 'tablet'" :class="previewMode === 'tablet' ? 'glass-fusion shadow' : ''" class="px-3 py-2 rounded text-sm transition">
                    📱 Tablet
                </button>
                <button @click="previewMode = 'mobile'" :class="previewMode === 'mobile' ? 'glass-fusion shadow' : ''" class="px-3 py-2 rounded text-sm transition">
                    📱 Mobile
                </button>
            </div>

            <!-- Actions -->
            <button @click="exportTemplate()" class="px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:bg-gray-700 transition">
                📥 Export
            </button>
            <button @click="showImportModal = true" class="px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:bg-gray-700 transition">
                📤 Import
            </button>
            <button @click="saveAllSections()" :disabled="saving" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg disabled:opacity-50">
                <span x-show="!saving">💾 บันทึก</span>
                <span x-show="saving">⏳ กำลังบันทึก...</span>
            </button>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="builder-layout">

        <!-- LEFT: Component Library -->
        <div class="glass-fusion rounded-xl shadow-md p-4 overflow-y-auto" border border-white/20 dark:border-white/10>
            <h3 class="font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Component Library
            </h3>

            <div class="space-y-3">
                <template x-for="(component, key) in componentLibrary" :key="key">
                    <div class="component-item bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-3 border border-blue-200">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span x-text="component.icon" class="text-2xl"></span>
                                <div>
                                    <div class="font-medium text-sm" x-text="component.name"></div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400" x-text="component.category"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-1 flex-wrap">
                            <template x-for="(template, templateKey) in component.templates" :key="templateKey">
                                <button @click="addSection(key, templateKey)" class="px-3 py-1 glass-fusion text-xs rounded border border-indigo-300 hover:bg-indigo-50 transition">
                                    + <span x-text="template.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- CENTER: Canvas -->
        <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 rounded-xl shadow-md p-6 overflow-y-auto">
            <h3 class="font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
                Canvas
                <span class="text-xs text-gray-500 dark:text-gray-400">(<span x-text="sections.length"></span> sections)</span>
            </h3>

            <!-- Empty State -->
            <div x-show="sections.length === 0" class="canvas-empty">
                <div class="text-4xl mb-4">📄</div>
                <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Canvas ว่างเปล่า</h4>
                <p class="text-sm">เลือก Component จากซ้ายเพื่อเริ่มสร้างหน้าเว็บ</p>
            </div>

            <!-- Sections List -->
            <div id="sections-sortable" class="space-y-4">
                <template x-for="(section, index) in sections" :key="section.id">
                    <div class="section-card" :data-id="section.id">
                        <!-- Section Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="cursor-move text-gray-400 hover:text-gray-600 dark:text-gray-400">
                                    ⋮⋮
                                </div>
                                <span class="text-2xl" x-text="getComponentIcon(section.component_type)"></span>
                                <div>
                                    <div class="font-semibold" x-text="section.name || getComponentName(section.component_type)"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="section.component_type"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="toggleSection(section)" :class="section.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400'" class="px-3 py-1 rounded text-xs transition">
                                    <span x-show="section.is_active">👁️ Visible</span>
                                    <span x-show="!section.is_active">🙈 Hidden</span>
                                </button>
                                <button @click="editSection(section)" class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 transition">
                                    ✏️ Edit
                                </button>
                                <button @click="duplicateSection(section)" class="px-3 py-1 bg-purple-100 text-purple-700 rounded text-xs hover:bg-purple-200 transition">
                                    📋 Duplicate
                                </button>
                                <button @click="deleteSection(section)" class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200 transition">
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>

                        <!-- Section Preview -->
                        <div class="glass-fusion rounded p-4 border border-gray-200 dark:border-gray-700" border border-white/20 dark:border-white/10>
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                <div x-show="section.content?.title" class="font-bold text-lg mb-2" x-text="section.content?.title"></div>
                                <div x-show="section.content?.subtitle" class="text-gray-600 dark:text-gray-400 mb-2" x-text="section.content?.subtitle"></div>
                                <div x-show="section.content?.description" class="text-sm" x-text="truncate(section.content?.description, 100)"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- RIGHT: Properties Panel -->
        <div class="properties-panel glass-fusion rounded-xl shadow-md p-4" border border-white/20 dark:border-white/10>
            <h3 class="font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Properties
            </h3>

            <!-- No Selection -->
            <div x-show="!selectedSection" class="text-center py-12 text-gray-400">
                <div class="text-4xl mb-2">🎯</div>
                <p class="text-sm">เลือก Section เพื่อแก้ไข</p>
            </div>

            <!-- Selected Section Properties -->
            <div x-show="selectedSection" class="space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Section Name</label>
                    <input type="text" x-model="selectedSection.name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl">
                </div>

                <!-- Content Fields -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label>
                    <input type="text" x-model="selectedSection.content.title" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subtitle</label>
                    <input type="text" x-model="selectedSection.content.subtitle" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea x-model="selectedSection.content.description" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl"></textarea>
                </div>

                <!-- Styles -->
                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-3">Styles</h4>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Background Color</label>
                            <div class="flex gap-2">
                                <input type="color" x-model="selectedSection.styles.bg_color" class="w-12 h-10 rounded cursor-pointer">
                                <input type="text" x-model="selectedSection.styles.bg_color" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Padding Y: <span x-text="selectedSection.styles.padding_y || 80"></span>px</label>
                            <input type="range" x-model="selectedSection.styles.padding_y" min="0" max="200" class="w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Text Align</label>
                            <select x-model="selectedSection.styles.text_align" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="border-t pt-4 flex gap-2">
                    <button @click="updateSection(selectedSection)" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                        💾 Save
                    </button>
                    <button @click="selectedSection = null" class="px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:bg-gray-700 transition">
                        ✕
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Import Modal -->
    <div x-show="showImportModal" @click.away="showImportModal = false" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div class="glass-fusion rounded-xl p-6 max-w-lg w-full" border border-white/20 dark:border-white/10 @click.stop>
            <h3 class="text-lg font-bold mb-4">Import Template</h3>
            <textarea x-model="importData" rows="10" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl mb-4" placeholder="Paste JSON template here..."></textarea>
            <div class="flex items-center gap-2 mb-4">
                <input type="checkbox" x-model="replaceExisting" id="replace-existing" class="w-4 h-4">
                <label for="replace-existing" class="text-sm">Replace existing sections</label>
            </div>
            <div class="flex gap-2">
                <button @click="importTemplate()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700">
                    Import
                </button>
                <button @click="showImportModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
function visualBuilder() {
    return {
        currentPage: '{{ $page }}',
        sections: @json($sections),
        componentLibrary: @json($componentLibrary),
        selectedSection: null,
        saving: false,
        previewMode: 'desktop',
        showImportModal: false,
        importData: '',
        replaceExisting: false,
        sortable: null,

        init() {
            this.initSortable();
        },

        initSortable() {
            const el = document.getElementById('sections-sortable');
            if (!el) return;

            this.sortable = Sortable.create(el, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: (evt) => {
                    this.reorderSections();
                }
            });
        },

        async loadSections() {
            try {
                const response = await fetch(`/admin/visual-builder?page=${this.currentPage}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                this.sections = data.sections || [];
            } catch (error) {
                console.error('Error loading sections:', error);
            }
        },

        async addSection(componentType, templateKey) {
            const component = this.componentLibrary[componentType];
            const template = component.templates[templateKey];

            try {
                const response = await fetch('/admin/visual-builder/sections', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        page: this.currentPage,
                        component_type: componentType,
                        name: template.name,
                        content: template.content || {},
                        styles: template.styles || {},
                        settings: {}
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.sections.push(data.section);
                    alert('✅ เพิ่ม Section สำเร็จ!');
                }
            } catch (error) {
                console.error('Error adding section:', error);
                alert('❌ เกิดข้อผิดพลาด');
            }
        },

        editSection(section) {
            this.selectedSection = { ...section };
        },

        async updateSection(section) {
            try {
                const response = await fetch(`/admin/visual-builder/sections/${section.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(section)
                });

                const data = await response.json();
                if (data.success) {
                    const index = this.sections.findIndex(s => s.id === section.id);
                    if (index !== -1) {
                        this.sections[index] = data.section;
                    }
                    this.selectedSection = null;
                    alert('✅ บันทึกสำเร็จ!');
                }
            } catch (error) {
                console.error('Error updating section:', error);
                alert('❌ เกิดข้อผิดพลาด');
            }
        },

        async deleteSection(section) {
            if (!confirm('คุณต้องการลบ Section นี้หรือไม่?')) return;

            try {
                const response = await fetch(`/admin/visual-builder/sections/${section.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.sections = this.sections.filter(s => s.id !== section.id);
                    alert('✅ ลบสำเร็จ!');
                }
            } catch (error) {
                console.error('Error deleting section:', error);
                alert('❌ เกิดข้อผิดพลาด');
            }
        },

        async duplicateSection(section) {
            try {
                const response = await fetch(`/admin/visual-builder/sections/${section.id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.sections.push(data.section);
                    alert('✅ ทำสำเนาสำเร็จ!');
                }
            } catch (error) {
                console.error('Error duplicating section:', error);
                alert('❌ เกิดข้อผิดพลาด');
            }
        },

        async toggleSection(section) {
            try {
                const response = await fetch(`/admin/visual-builder/sections/${section.id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    section.is_active = data.is_active;
                }
            } catch (error) {
                console.error('Error toggling section:', error);
            }
        },

        async reorderSections() {
            const sectionIds = Array.from(document.querySelectorAll('.section-card')).map(el => parseInt(el.dataset.id));

            try {
                await fetch('/admin/visual-builder/sections/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        page: this.currentPage,
                        sections: sectionIds
                    })
                });
            } catch (error) {
                console.error('Error reordering sections:', error);
            }
        },

        async exportTemplate() {
            try {
                const response = await fetch(`/admin/visual-builder/export?page=${this.currentPage}`);
                const data = await response.json();

                if (data.success) {
                    const json = JSON.stringify(data.template, null, 2);
                    const blob = new Blob([json], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `template-${this.currentPage}-${Date.now()}.json`;
                    a.click();
                    alert('✅ Export สำเร็จ!');
                }
            } catch (error) {
                console.error('Error exporting:', error);
                alert('❌ เกิดข้อผิดพลาด');
            }
        },

        async importTemplate() {
            try {
                const template = JSON.parse(this.importData);

                const response = await fetch('/admin/visual-builder/import', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        page: this.currentPage,
                        template: template,
                        replace_existing: this.replaceExisting
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('✅ Import สำเร็จ!');
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Error importing:', error);
                alert('❌ JSON ไม่ถูกต้อง');
            }

            this.showImportModal = false;
        },

        getComponentIcon(type) {
            return this.componentLibrary[type]?.icon || '📦';
        },

        getComponentName(type) {
            return this.componentLibrary[type]?.name || type;
        },

        truncate(str, length) {
            if (!str) return '';
            return str.length > length ? str.substring(0, length) + '...' : str;
        },

        async saveAllSections() {
            this.saving = true;
            // All changes are already saved via individual API calls
            setTimeout(() => {
                this.saving = false;
                alert('✅ บันทึกทั้งหมดสำเร็จ!');
            }, 500);
        }
    }
}
</script>
@endpush

@endsection
