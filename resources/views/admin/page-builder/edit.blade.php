@extends('layouts.admin')

@section('title', 'Edit Page - ' . $page->name)

@push('styles')
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #E5E7EB;
    }
    .sortable-drag {
        opacity: 1 !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .section-card {
        transition: all 0.2s ease;
    }
    .section-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@section('content')
<div class="h-screen flex flex-col bg-gray-50 dark:bg-gray-900" x-data="pageBuilder()" x-init="init()">
    <!-- Top Bar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.page-builder.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ $page->name }}</h1>
                <p class="text-sm text-gray-500">{{ ucfirst($page->page_type) }} - /{{ $page->slug }}</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <!-- Device Preview Toggle -->
            <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                <button @click="previewDevice = 'desktop'" :class="previewDevice === 'desktop' ? 'bg-white dark:bg-gray-600 shadow' : ''"
                        class="px-3 py-1.5 rounded text-sm font-medium transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </button>
                <button @click="previewDevice = 'tablet'" :class="previewDevice === 'tablet' ? 'bg-white dark:bg-gray-600 shadow' : ''"
                        class="px-3 py-1.5 rounded text-sm font-medium transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </button>
                <button @click="previewDevice = 'mobile'" :class="previewDevice === 'mobile' ? 'bg-white dark:bg-gray-600 shadow' : ''"
                        class="px-3 py-1.5 rounded text-sm font-medium transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>

            <a href="{{ route('admin.page-builder.preview', $page) }}" target="_blank"
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Preview
            </a>

            <button @click="savePage()"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                Save Changes
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Sidebar - Sections Manager -->
        <div class="w-96 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex -mb-px">
                    <button @click="activeTab = 'sections'" :class="activeTab === 'sections' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                            class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm">
                        Sections
                    </button>
                    <button @click="activeTab = 'templates'" :class="activeTab === 'templates' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                            class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm">
                        Templates
                    </button>
                    <button @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                            class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm">
                        Settings
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto p-4">
                <!-- Sections Tab -->
                <div x-show="activeTab === 'sections'" class="space-y-4">
                    <button @click="showTemplateGallery = true"
                            class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Section
                    </button>

                    <div id="sections-list" class="space-y-3">
                        @foreach($page->sections->sortBy('order') as $section)
                        <div class="section-card bg-gray-50 dark:bg-gray-700 rounded-lg p-4 cursor-move" data-id="{{ $section->id }}">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        </svg>
                                        <h3 class="font-semibold text-gray-800 dark:text-white text-sm">
                                            {{ $section->name ?? ucfirst(str_replace('_', ' ', $section->section_type)) }}
                                        </h3>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $sectionTypes[$section->section_type] ?? $section->section_type }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-1">
                                    @if($section->is_visible)
                                    <button onclick="toggleVisibility({{ $section->id }})" class="text-green-600 hover:text-green-800" title="Visible">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    @else
                                    <button onclick="toggleVisibility({{ $section->id }})" class="text-gray-400 hover:text-gray-600" title="Hidden">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                <button onclick="editSection({{ $section->id }})" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    Edit
                                </button>
                                <div class="flex items-center space-x-2">
                                    <button onclick="duplicateSection({{ $section->id }})" class="text-gray-600 hover:text-gray-800" title="Duplicate">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteSection({{ $section->id }})" class="text-red-600 hover:text-red-800" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Templates Tab -->
                <div x-show="activeTab === 'templates'" class="space-y-3">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Click on a template to add it to your page
                    </div>

                    @foreach($sectionTypes as $type => $label)
                    <button onclick="addSectionFromType('{{ $type }}')"
                            class="w-full text-left p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="font-medium text-gray-800 dark:text-white">{{ $label }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $type }}</div>
                    </button>
                    @endforeach
                </div>

                <!-- Settings Tab -->
                <div x-show="activeTab === 'settings'" class="space-y-4">
                    <form id="page-settings-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page Name</label>
                            <input type="text" name="name" value="{{ $page->name }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URL Slug</label>
                            <input type="text" name="slug" value="{{ $page->slug }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page Type</label>
                            <select name="page_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                @foreach($pageTypes as $value => $label)
                                <option value="{{ $value }}" {{ $page->page_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ $page->is_active ? 'checked' : '' }}
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded">
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                            </label>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Panel - Preview -->
        <div class="flex-1 bg-gray-100 dark:bg-gray-900 overflow-auto">
            <div class="h-full flex items-center justify-center">
                <div :class="{
                    'w-full': previewDevice === 'desktop',
                    'max-w-2xl': previewDevice === 'tablet',
                    'max-w-sm': previewDevice === 'mobile'
                }" class="bg-white dark:bg-gray-800 shadow-2xl mx-auto transition-all duration-300" style="min-height: 100%;">
                    <iframe id="preview-iframe" src="{{ route('admin.page-builder.preview', $page) }}"
                            class="w-full h-full border-0" style="min-height: 100vh;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Section Modal -->
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeEditModal()"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

            <!-- Modal panel -->
            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 rounded-2xl shadow-xl">

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        Edit Section
                    </h3>
                    <button @click="closeEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                    <form id="edit-section-form" class="space-y-6">
                        <!-- Section Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Section Name
                            </label>
                            <input type="text"
                                   x-model="editingSectionData.name"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Section Type (Read-only) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Section Type
                            </label>
                            <input type="text"
                                   x-model="editingSectionData.section_type"
                                   readonly
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Content JSON Editor -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Content (JSON)
                            </label>
                            <textarea x-model="editingSectionContent"
                                      rows="12"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500"
                                      placeholder='{"title": "Your title", "subtitle": "Your subtitle"}'></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Enter valid JSON format. Example: {"title": "Hello", "subtitle": "World"}
                            </p>
                        </div>

                        <!-- Settings JSON Editor -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Settings (JSON)
                            </label>
                            <textarea x-model="editingSectionSettings"
                                      rows="8"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500"
                                      placeholder='{"padding_y": "py-20", "bg_color": "#ffffff"}'></textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Enter valid JSON format for section settings.
                            </p>
                        </div>

                        <!-- Active & Visible Toggles -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           x-model="editingSectionData.is_active"
                                           class="w-5 h-5 text-blue-600 border-gray-300 rounded">
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           x-model="editingSectionData.is_visible"
                                           class="w-5 h-5 text-blue-600 border-gray-300 rounded">
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Visible</span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button @click="closeEditModal()"
                            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button @click="saveEditSection()"
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function pageBuilder() {
    return {
        activeTab: 'sections',
        previewDevice: 'desktop',
        showTemplateGallery: false,
        showEditModal: false,
        editingSectionId: null,
        editingSectionData: {},
        editingSectionContent: '{}',
        editingSectionSettings: '{}',

        init() {
            this.initSortable();
            this.initAutoSave();
        },

        openEditModal(sectionId, sectionData) {
            this.editingSectionId = sectionId;
            this.editingSectionData = { ...sectionData };
            this.editingSectionContent = JSON.stringify(sectionData.content || {}, null, 2);
            this.editingSectionSettings = JSON.stringify(sectionData.settings || {}, null, 2);
            this.showEditModal = true;
        },

        closeEditModal() {
            this.showEditModal = false;
            this.editingSectionId = null;
            this.editingSectionData = {};
            this.editingSectionContent = '{}';
            this.editingSectionSettings = '{}';
        },

        saveEditSection() {
            try {
                // Parse JSON content
                const content = JSON.parse(this.editingSectionContent);
                const settings = JSON.parse(this.editingSectionSettings);

                const updateData = {
                    name: this.editingSectionData.name,
                    section_type: this.editingSectionData.section_type,
                    content: content,
                    settings: settings,
                    is_active: this.editingSectionData.is_active ? 1 : 0,
                    is_visible: this.editingSectionData.is_visible ? 1 : 0,
                };

                fetch(`/admin/page-builder/sections/${this.editingSectionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    body: JSON.stringify(updateData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Section updated successfully!');
                        this.closeEditModal();
                        this.refreshPreview();
                        setTimeout(() => location.reload(), 500);
                    } else {
                        alert('❌ Error: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error updating section. Check console for details.');
                });
            } catch (error) {
                alert('❌ Invalid JSON format: ' + error.message);
            }
        },

        initSortable() {
            const el = document.getElementById('sections-list');
            if (el) {
                Sortable.create(el, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    onEnd: (evt) => {
                        this.updateSectionOrder();
                    }
                });
            }
        },

        updateSectionOrder() {
            const sectionIds = Array.from(document.querySelectorAll('.section-card')).map(el => el.dataset.id);

            fetch('{{ route("admin.page-builder.reorder-sections", $page) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ section_ids: sectionIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.refreshPreview();
                }
            });
        },

        refreshPreview() {
            document.getElementById('preview-iframe').src = document.getElementById('preview-iframe').src;
        },

        savePage() {
            const formData = new FormData(document.getElementById('page-settings-form'));
            formData.append('_method', 'PUT');

            fetch('{{ route("admin.page-builder.update", $page) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Page saved successfully!');
                    this.refreshPreview();
                } else {
                    alert('❌ Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error saving page. Check console for details.');
            });
        },

        initAutoSave() {
            // Auto-save page settings every 30 seconds
            setInterval(() => {
                this.autoSavePage();
            }, 30000);

            // Also save on window blur (user switches tab/window)
            window.addEventListener('blur', () => {
                this.autoSavePage();
            });
        },

        autoSavePage() {
            const formData = new FormData(document.getElementById('page-settings-form'));

            // Only auto-save if form exists and has data
            if (!formData || formData.entries().next().done) {
                return;
            }

            formData.append('_method', 'PUT');

            fetch('{{ route("admin.page-builder.update", $page) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show subtle notification
                    this.showAutoSaveNotification('✓ Auto-saved');
                }
            })
            .catch(error => {
                console.log('Auto-save skipped:', error.message);
            });
        },

        showAutoSaveNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.textContent = message;
            notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg text-sm z-50 transition-opacity duration-300';
            document.body.appendChild(notification);

            // Remove after 2 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }
    }
}

function toggleVisibility(sectionId) {
    fetch(`/admin/page-builder/sections/${sectionId}/toggle-visibility`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Visibility toggled successfully!');
            location.reload();
        } else {
            alert('❌ Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error toggling visibility. Check console for details.');
    });
}

function editSection(sectionId) {
    // Fetch section data from API
    fetch(`/admin/page-builder/sections/${sectionId}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.section) {
            // Get Alpine.js component instance
            const builderComponent = document.querySelector('[x-data="pageBuilder()"]').__x.$data;
            builderComponent.openEditModal(sectionId, data.section);
        } else {
            alert('❌ Error loading section data');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error loading section. Check console for details.');
    });
}

function duplicateSection(sectionId) {
    if (confirm('📋 Duplicate this section?')) {
        fetch(`/admin/page-builder/sections/${sectionId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Section duplicated successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error duplicating section. Check console for details.');
        });
    }
}

function deleteSection(sectionId) {
    if (confirm('⚠️ Are you sure you want to delete this section?')) {
        const formData = new FormData();
        formData.append('_method', 'DELETE');

        fetch(`/admin/page-builder/sections/${sectionId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Section deleted successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error deleting section. Check console for details.');
        });
    }
}

function addSectionFromType(type) {
    fetch(`{{ route("admin.page-builder.sections.store", $page) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            section_type: type,
            name: type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
            settings: {},
            content: {},
            is_active: true,
            is_visible: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Section added successfully!');
            location.reload();
        } else {
            alert('❌ Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error adding section. Check console for details.');
    });
}
</script>
@endpush
@endsection
