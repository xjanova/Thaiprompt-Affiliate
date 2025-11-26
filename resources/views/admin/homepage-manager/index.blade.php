@extends('layouts.admin')

@section('title', $pageTitle ?? 'จัดการหน้าแรก')

@section('styles')
<style>
    /* ซ่อน scrollbar แต่ยังเลื่อนได้ */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Sortable styles */
    .sortable-ghost { opacity: 0.4; background: #e5e7eb; }
    .sortable-chosen { border-color: #6366f1 !important; box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2); }
    .sortable-drag { opacity: 1 !important; }

    /* Element dragging in canvas */
    .element-dragging { cursor: grabbing !important; }
    .element-selected { outline: 2px solid #6366f1; outline-offset: 2px; }

    /* Resize handles */
    .resize-handle { position: absolute; width: 10px; height: 10px; background: #6366f1; border-radius: 2px; }
    .resize-handle.nw { top: -5px; left: -5px; cursor: nwse-resize; }
    .resize-handle.ne { top: -5px; right: -5px; cursor: nesw-resize; }
    .resize-handle.sw { bottom: -5px; left: -5px; cursor: nesw-resize; }
    .resize-handle.se { bottom: -5px; right: -5px; cursor: nwse-resize; }

    /* Canvas grid */
    .canvas-grid { background-image: linear-gradient(rgba(99, 102, 241, 0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(99, 102, 241, 0.1) 1px, transparent 1px); background-size: 20px 20px; }

    /* Animation preview */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes slideInUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
    .animate-fadeIn { animation: fadeIn 0.5s ease-out forwards; }
    .animate-fadeInUp { animation: fadeInUp 0.5s ease-out forwards; }
    .animate-fadeInDown { animation: fadeInDown 0.5s ease-out forwards; }
    .animate-slideInUp { animation: slideInUp 0.5s ease-out forwards; }
    .animate-zoomIn { animation: zoomIn 0.5s ease-out forwards; }
</style>
@endsection

@section('content')
<div x-data="homepageManager()" x-init="init()" class="h-screen flex flex-col bg-gray-100 dark:bg-gray-900 overflow-hidden">
    {{-- Top Toolbar --}}
    <div class="flex-shrink-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
        <div class="flex items-center justify-between">
            {{-- Left: Title & Actions --}}
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-home mr-2 text-indigo-500"></i>
                    จัดการหน้าแรก
                </h1>

                {{-- Device Preview Toggle --}}
                <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button @click="deviceMode = 'desktop'" :class="deviceMode === 'desktop' ? 'bg-white dark:bg-gray-600 shadow' : ''" class="px-3 py-1.5 rounded text-sm transition">
                        <i class="fas fa-desktop"></i>
                    </button>
                    <button @click="deviceMode = 'tablet'" :class="deviceMode === 'tablet' ? 'bg-white dark:bg-gray-600 shadow' : ''" class="px-3 py-1.5 rounded text-sm transition">
                        <i class="fas fa-tablet-alt"></i>
                    </button>
                    <button @click="deviceMode = 'mobile'" :class="deviceMode === 'mobile' ? 'bg-white dark:bg-gray-600 shadow' : ''" class="px-3 py-1.5 rounded text-sm transition">
                        <i class="fas fa-mobile-alt"></i>
                    </button>
                </div>

                {{-- Zoom --}}
                <div class="flex items-center space-x-2">
                    <button @click="zoom = Math.max(25, zoom - 25)" class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-minus text-sm"></i>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-gray-400 w-12 text-center" x-text="zoom + '%'"></span>
                    <button @click="zoom = Math.min(200, zoom + 25)" class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-plus text-sm"></i>
                    </button>
                </div>
            </div>

            {{-- Right: Action Buttons --}}
            <div class="flex items-center space-x-3">
                {{-- Undo/Redo --}}
                <div class="flex items-center space-x-1">
                    <button @click="undo()" :disabled="historyIndex <= 0" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50" title="Undo">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button @click="redo()" :disabled="historyIndex >= history.length - 1" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50" title="Redo">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>

                <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

                {{-- Import/Export --}}
                <button @click="showExportModal = true" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-download mr-1"></i> Export
                </button>
                <button @click="showImportModal = true" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-upload mr-1"></i> Import
                </button>

                <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

                {{-- Preview --}}
                <a href="{{ route('admin.homepage-manager.preview') }}" target="_blank" class="px-3 py-2 text-sm bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800 transition">
                    <i class="fas fa-eye mr-1"></i> Preview
                </a>

                {{-- Save --}}
                <button @click="saveAll()" :disabled="saving" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 flex items-center">
                    <i class="fas fa-save mr-1" :class="saving && 'fa-spin'"></i>
                    <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Main 3-Panel Layout --}}
    <div class="flex-1 flex overflow-hidden">
        {{-- Left Panel: Elements Library --}}
        <div class="w-72 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
            {{-- Panel Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Elements</h2>
                    <button @click="showTemplates = !showTemplates" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                        <span x-text="showTemplates ? 'Elements' : 'Templates'"></span>
                    </button>
                </div>
                <input type="text" x-model="searchElements" placeholder="ค้นหา..." class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
            </div>

            {{-- Elements List --}}
            <div x-show="!showTemplates" class="flex-1 overflow-y-auto hide-scrollbar p-4 space-y-4">
                {{-- Add Section Button --}}
                <button @click="addSection()" class="w-full py-3 border-2 border-dashed border-indigo-300 dark:border-indigo-600 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition flex items-center justify-center">
                    <i class="fas fa-plus-circle mr-2"></i> เพิ่ม Section ใหม่
                </button>

                {{-- Section Types --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Section Types</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($sectionTypes as $type => $label)
                        <button @click="addSection('{{ $type }}')" class="p-3 text-left border border-gray-200 dark:border-gray-600 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition group">
                            <i class="fas fa-{{ $type === 'hero' ? 'star' : ($type === 'features' ? 'th-large' : ($type === 'stats' ? 'chart-bar' : ($type === 'testimonials' ? 'quote-left' : ($type === 'cta' ? 'bullhorn' : ($type === 'gallery' ? 'images' : 'cube'))))) }} text-lg text-gray-400 group-hover:text-indigo-500 mb-1"></i>
                            <div class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $label }}</div>
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Element Types --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Element Types</h3>
                    <div class="space-y-1" id="element-types-list">
                        @foreach($elementTypes as $type => $label)
                        <div draggable="true" @dragstart="dragElementType($event, '{{ $type }}')" class="flex items-center p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-grab transition">
                            <i class="fas fa-{{ $type === 'heading' ? 'heading' : ($type === 'text' ? 'paragraph' : ($type === 'image' ? 'image' : ($type === 'button' ? 'square' : ($type === 'video' ? 'video' : ($type === 'icon' ? 'icons' : ($type === 'html' ? 'code' : 'arrows-alt')))))) }} w-6 text-gray-400"></i>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            <i class="fas fa-grip-vertical ml-auto text-gray-300"></i>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Templates List --}}
            <div x-show="showTemplates" class="flex-1 overflow-y-auto hide-scrollbar p-4 space-y-3">
                @foreach($templates as $template)
                <div @click="importTemplate({{ $template->id }})" class="p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-indigo-500 cursor-pointer transition">
                    @if($template->thumbnail)
                    <img src="{{ $template->thumbnail }}" alt="{{ $template->name }}" class="w-full h-24 object-cover rounded mb-2">
                    @else
                    <div class="w-full h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded mb-2 flex items-center justify-center">
                        <i class="fas fa-layer-group text-3xl text-white/50"></i>
                    </div>
                    @endif
                    <h4 class="font-medium text-sm text-gray-900 dark:text-white">{{ $template->name }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $templateCategories[$template->category] ?? $template->category }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Center: Canvas --}}
        <div class="flex-1 bg-gray-200 dark:bg-gray-900 overflow-auto p-8">
            <div class="mx-auto transition-all duration-300"
                 :style="{
                    width: deviceMode === 'mobile' ? '375px' : (deviceMode === 'tablet' ? '768px' : '100%'),
                    maxWidth: deviceMode === 'desktop' ? '1440px' : 'none',
                    transform: 'scale(' + (zoom / 100) + ')',
                    transformOrigin: 'top center'
                 }">

                {{-- Canvas Area --}}
                <div id="sections-container" class="bg-white dark:bg-gray-800 shadow-2xl rounded-lg overflow-hidden min-h-[600px]" :class="showGrid && 'canvas-grid'">
                    {{-- Sections List --}}
                    <template x-for="(section, sectionIndex) in sections" :key="section.id">
                        <div class="relative group border-b-2 border-transparent hover:border-indigo-500 transition"
                             :class="selectedSection?.id === section.id && 'border-indigo-500 ring-2 ring-indigo-500 ring-opacity-50'"
                             :style="getSectionStyle(section)"
                             @click.self="selectSection(section)"
                             :data-section-id="section.id">

                            {{-- Section Controls --}}
                            <div class="absolute top-2 right-2 flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition z-50">
                                <span class="px-2 py-1 text-xs bg-black/50 text-white rounded mr-2" x-text="section.name"></span>
                                <button @click.stop="moveSection(sectionIndex, -1)" :disabled="sectionIndex === 0" class="p-1.5 bg-white dark:bg-gray-700 rounded shadow hover:bg-gray-100 disabled:opacity-50" title="ขึ้น">
                                    <i class="fas fa-chevron-up text-xs"></i>
                                </button>
                                <button @click.stop="moveSection(sectionIndex, 1)" :disabled="sectionIndex === sections.length - 1" class="p-1.5 bg-white dark:bg-gray-700 rounded shadow hover:bg-gray-100 disabled:opacity-50" title="ลง">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                                <button @click.stop="duplicateSection(section)" class="p-1.5 bg-white dark:bg-gray-700 rounded shadow hover:bg-gray-100" title="ทำสำเนา">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                                <button @click.stop="toggleSection(section)" class="p-1.5 bg-white dark:bg-gray-700 rounded shadow hover:bg-gray-100" title="เปิด/ปิด">
                                    <i class="fas" :class="section.is_active ? 'fa-eye' : 'fa-eye-slash'" :style="!section.is_active && 'color: #ef4444'"></i>
                                </button>
                                <button @click.stop="editSection(section)" class="p-1.5 bg-indigo-500 text-white rounded shadow hover:bg-indigo-600" title="แก้ไข">
                                    <i class="fas fa-cog text-xs"></i>
                                </button>
                                <button @click.stop="deleteSection(section)" class="p-1.5 bg-red-500 text-white rounded shadow hover:bg-red-600" title="ลบ">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>

                            {{-- Section Content Container --}}
                            <div class="relative" :class="section.is_fullwidth ? 'w-full' : 'container mx-auto'" style="min-height: inherit;"
                                 @drop.prevent="dropElement($event, section)"
                                 @dragover.prevent="$event.currentTarget.classList.add('bg-indigo-100', 'dark:bg-indigo-900/30')"
                                 @dragleave="$event.currentTarget.classList.remove('bg-indigo-100', 'dark:bg-indigo-900/30')">

                                {{-- Elements --}}
                                <template x-for="(element, elemIndex) in section.elements" :key="element.id">
                                    <div class="homepage-element cursor-pointer transition-all"
                                         :class="[
                                            selectedElement?.id === element.id && 'element-selected',
                                            element.position.type === 'absolute' ? 'absolute' : 'relative'
                                         ]"
                                         :style="getElementStyle(element)"
                                         @click.stop="selectElement(element, section)"
                                         @mousedown="startDragElement($event, element, section)"
                                         :data-element-id="element.id">

                                        {{-- Element Content --}}
                                        <template x-if="element.type === 'heading'">
                                            <div x-html="'<' + (element.settings?.heading_level || 'h2') + '>' + (element.content?.text || 'Heading') + '</' + (element.settings?.heading_level || 'h2') + '>'"></div>
                                        </template>
                                        <template x-if="element.type === 'text'">
                                            <p x-text="element.content?.text || 'Text content'"></p>
                                        </template>
                                        <template x-if="element.type === 'image'">
                                            <img :src="element.content?.image_url || 'https://via.placeholder.com/400x300'" class="max-w-full h-auto" alt="">
                                        </template>
                                        <template x-if="element.type === 'button'">
                                            <button class="inline-flex items-center justify-center" x-text="element.content?.text || 'Button'"></button>
                                        </template>
                                        <template x-if="element.type === 'icon'">
                                            <i :class="element.content?.icon_class || 'fas fa-star text-4xl'"></i>
                                        </template>
                                        <template x-if="element.type === 'video'">
                                            <div class="aspect-video bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                <i class="fas fa-play-circle text-4xl text-gray-400"></i>
                                            </div>
                                        </template>
                                        <template x-if="element.type === 'html'">
                                            <div x-html="element.content?.text || '<p>Custom HTML</p>'"></div>
                                        </template>
                                        <template x-if="element.type === 'spacer'">
                                            <div class="bg-gray-100 dark:bg-gray-700 border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center text-xs text-gray-400" :style="'height:' + (element.size?.height || '50px')">
                                                Spacer
                                            </div>
                                        </template>

                                        {{-- Resize Handles (when selected) --}}
                                        <template x-if="selectedElement?.id === element.id && element.position.type === 'absolute'">
                                            <div>
                                                <div class="resize-handle nw" @mousedown.stop="startResize($event, element, 'nw')"></div>
                                                <div class="resize-handle ne" @mousedown.stop="startResize($event, element, 'ne')"></div>
                                                <div class="resize-handle sw" @mousedown.stop="startResize($event, element, 'sw')"></div>
                                                <div class="resize-handle se" @mousedown.stop="startResize($event, element, 'se')"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Empty Section Placeholder --}}
                                <div x-show="!section.elements || section.elements.length === 0" class="flex items-center justify-center h-full min-h-[200px] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded m-4">
                                    <div class="text-center text-gray-400">
                                        <i class="fas fa-plus-circle text-3xl mb-2"></i>
                                        <p class="text-sm">ลาก Element มาวางที่นี่</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Inactive Overlay --}}
                            <div x-show="!section.is_active" class="absolute inset-0 bg-gray-500/50 flex items-center justify-center z-40">
                                <span class="px-4 py-2 bg-gray-800 text-white rounded-lg">Section ถูกปิดการแสดงผล</span>
                            </div>
                        </div>
                    </template>

                    {{-- Empty State --}}
                    <div x-show="sections.length === 0" class="flex flex-col items-center justify-center h-[600px] text-gray-400">
                        <i class="fas fa-layer-group text-6xl mb-4"></i>
                        <p class="text-lg mb-4">ยังไม่มี Section</p>
                        <button @click="addSection()" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            <i class="fas fa-plus mr-2"></i> เพิ่ม Section แรก
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel: Properties --}}
        <div class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
            {{-- Panel Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-sliders-h mr-2 text-indigo-500"></i>
                    Properties
                </h2>
            </div>

            {{-- Properties Content --}}
            <div class="flex-1 overflow-y-auto hide-scrollbar">
                {{-- No Selection --}}
                <div x-show="!selectedSection && !selectedElement" class="p-8 text-center text-gray-400">
                    <i class="fas fa-mouse-pointer text-4xl mb-4"></i>
                    <p>เลือก Section หรือ Element เพื่อแก้ไข</p>
                </div>

                {{-- Section Properties --}}
                <div x-show="selectedSection && !selectedElement" class="p-4 space-y-4">
                    <h3 class="font-medium text-gray-900 dark:text-white">Section Settings</h3>

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ Section</label>
                        <input type="text" x-model="selectedSection.name" @input="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                        <select x-model="selectedSection.type" @change="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            @foreach($sectionTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Height --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความสูงขั้นต่ำ</label>
                            <input type="text" x-model="selectedSection.min_height" @input="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" placeholder="400px">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความสูงสูงสุด</label>
                            <input type="text" x-model="selectedSection.max_height" @input="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" placeholder="auto">
                        </div>
                    </div>

                    {{-- Padding --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Padding</label>
                        <input type="text" x-model="selectedSection.padding" @input="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" placeholder="60px 20px">
                    </div>

                    {{-- Background Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทพื้นหลัง</label>
                        <select x-model="selectedSection.background.type" @change="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            @foreach($backgroundTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Background Color --}}
                    <div x-show="selectedSection.background?.type === 'color'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สี Light</label>
                            <div class="flex">
                                <input type="color" x-model="selectedSection.background.color" @input="updateSection()" class="w-10 h-10 rounded-l border border-gray-300 dark:border-gray-600">
                                <input type="text" x-model="selectedSection.background.color" @input="updateSection()" class="flex-1 px-2 py-2 border-t border-r border-b border-gray-300 dark:border-gray-600 rounded-r bg-white dark:bg-gray-700 text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สี Dark</label>
                            <div class="flex">
                                <input type="color" x-model="selectedSection.background.color_dark" @input="updateSection()" class="w-10 h-10 rounded-l border border-gray-300 dark:border-gray-600">
                                <input type="text" x-model="selectedSection.background.color_dark" @input="updateSection()" class="flex-1 px-2 py-2 border-t border-r border-b border-gray-300 dark:border-gray-600 rounded-r bg-white dark:bg-gray-700 text-xs">
                            </div>
                        </div>
                    </div>

                    {{-- Background Gradient --}}
                    <div x-show="selectedSection.background?.type === 'gradient'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gradient CSS</label>
                        <textarea x-model="selectedSection.background.gradient" @input="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" rows="2" placeholder="linear-gradient(135deg, #667eea 0%, #764ba2 100%)"></textarea>
                    </div>

                    {{-- Background Image --}}
                    <div x-show="selectedSection.background?.type === 'image'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL รูปภาพ</label>
                        <div class="flex space-x-2">
                            <input type="text" x-model="selectedSection.background.image" @input="updateSection()" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            <button @click="uploadBackgroundImage()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Animation --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Animation</label>
                        <select x-model="selectedSection.animation.type" @change="updateSection()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            @foreach($animations as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Add Element Button --}}
                    <button @click="addElement(selectedSection)" class="w-full py-2 border-2 border-dashed border-indigo-300 dark:border-indigo-600 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition text-sm">
                        <i class="fas fa-plus mr-1"></i> เพิ่ม Element
                    </button>
                </div>

                {{-- Element Properties --}}
                <div x-show="selectedElement" class="p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-gray-900 dark:text-white">Element Settings</h3>
                        <button @click="selectedElement = null" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Element Type Badge --}}
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded" x-text="selectedElement?.type_label"></span>
                    </div>

                    {{-- Content --}}
                    <div x-show="['heading', 'text', 'button'].includes(selectedElement?.type)">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เนื้อหา</label>
                        <textarea x-model="selectedElement.content.text" @input="updateElement()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" rows="3"></textarea>
                    </div>

                    {{-- Image URL --}}
                    <div x-show="selectedElement?.type === 'image'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL รูปภาพ</label>
                        <div class="flex space-x-2">
                            <input type="text" x-model="selectedElement.content.image_url" @input="updateElement()" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            <button @click="uploadElementImage()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Link --}}
                    <div x-show="['button', 'image'].includes(selectedElement?.type)">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Link URL</label>
                        <input type="text" x-model="selectedElement.content.link_url" @input="updateElement()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" placeholder="https://...">
                    </div>

                    {{-- Position Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                        <select x-model="selectedElement.position.type" @change="updateElement()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            <option value="relative">Relative (ไหลตามปกติ)</option>
                            <option value="absolute">Absolute (ลากวางอิสระ)</option>
                        </select>
                    </div>

                    {{-- Position X/Y (for absolute) --}}
                    <div x-show="selectedElement?.position?.type === 'absolute'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">X</label>
                            <div class="flex">
                                <input type="number" x-model.number="selectedElement.position.x" @input="updateElement()" class="w-full px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-sm">
                                <select x-model="selectedElement.position.x_unit" @change="updateElement()" class="px-2 py-2 border-t border-r border-b border-gray-300 dark:border-gray-600 rounded-r-lg bg-white dark:bg-gray-700 text-sm">
                                    <option value="%">%</option>
                                    <option value="px">px</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Y</label>
                            <div class="flex">
                                <input type="number" x-model.number="selectedElement.position.y" @input="updateElement()" class="w-full px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-sm">
                                <select x-model="selectedElement.position.y_unit" @change="updateElement()" class="px-2 py-2 border-t border-r border-b border-gray-300 dark:border-gray-600 rounded-r-lg bg-white dark:bg-gray-700 text-sm">
                                    <option value="%">%</option>
                                    <option value="px">px</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Size --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Width</label>
                            <input type="text" x-model="selectedElement.size.width" @input="updateElement()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" placeholder="auto">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Height</label>
                            <input type="text" x-model="selectedElement.size.height" @input="updateElement()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm" placeholder="auto">
                        </div>
                    </div>

                    {{-- Typography --}}
                    <div x-show="['heading', 'text', 'button'].includes(selectedElement?.type)">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Typography</label>
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Font Size</label>
                                    <input type="text" x-model="selectedElement.typography.font_size" @input="updateElement()" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm" placeholder="16px">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Font Weight</label>
                                    <select x-model="selectedElement.typography.font_weight" @change="updateElement()" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm">
                                        <option value="300">Light</option>
                                        <option value="400">Normal</option>
                                        <option value="500">Medium</option>
                                        <option value="600">Semi Bold</option>
                                        <option value="700">Bold</option>
                                        <option value="800">Extra Bold</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Text Align</label>
                                <div class="flex border border-gray-300 dark:border-gray-600 rounded overflow-hidden">
                                    <button @click="selectedElement.typography.text_align = 'left'; updateElement()" :class="selectedElement?.typography?.text_align === 'left' && 'bg-indigo-100 dark:bg-indigo-900'" class="flex-1 py-1.5 text-center hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-align-left"></i>
                                    </button>
                                    <button @click="selectedElement.typography.text_align = 'center'; updateElement()" :class="selectedElement?.typography?.text_align === 'center' && 'bg-indigo-100 dark:bg-indigo-900'" class="flex-1 py-1.5 text-center border-l border-r border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-align-center"></i>
                                    </button>
                                    <button @click="selectedElement.typography.text_align = 'right'; updateElement()" :class="selectedElement?.typography?.text_align === 'right' && 'bg-indigo-100 dark:bg-indigo-900'" class="flex-1 py-1.5 text-center hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-align-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Colors --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Colors</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Text Color</label>
                                <div class="flex">
                                    <input type="color" x-model="selectedElement.colors.color" @input="updateElement()" class="w-8 h-8 rounded-l border border-gray-300 dark:border-gray-600">
                                    <input type="text" x-model="selectedElement.colors.color" @input="updateElement()" class="flex-1 px-2 py-1 border-t border-r border-b border-gray-300 dark:border-gray-600 rounded-r bg-white dark:bg-gray-700 text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Background</label>
                                <div class="flex">
                                    <input type="color" x-model="selectedElement.colors.background_color" @input="updateElement()" class="w-8 h-8 rounded-l border border-gray-300 dark:border-gray-600">
                                    <input type="text" x-model="selectedElement.colors.background_color" @input="updateElement()" class="flex-1 px-2 py-1 border-t border-r border-b border-gray-300 dark:border-gray-600 rounded-r bg-white dark:bg-gray-700 text-xs">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Border --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Border</label>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Width</label>
                                <input type="text" x-model="selectedElement.border.width" @input="updateElement()" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Style</label>
                                <select x-model="selectedElement.border.style" @change="updateElement()" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm">
                                    <option value="solid">Solid</option>
                                    <option value="dashed">Dashed</option>
                                    <option value="dotted">Dotted</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Radius</label>
                                <input type="text" x-model="selectedElement.border.radius" @input="updateElement()" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm" placeholder="0">
                            </div>
                        </div>
                    </div>

                    {{-- Spacing --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Spacing</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Padding</label>
                                <input type="text" x-model="selectedElement.spacing.padding" @input="updateElement()" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Margin</label>
                                <input type="text" x-model="selectedElement.spacing.margin" @input="updateElement()" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm" placeholder="0">
                            </div>
                        </div>
                    </div>

                    {{-- Animation --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Animation</label>
                        <select x-model="selectedElement.animation.type" @change="updateElement()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm">
                            @foreach($animations as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Element Actions --}}
                    <div class="flex space-x-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button @click="duplicateElement(selectedElement)" class="flex-1 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            <i class="fas fa-copy mr-1"></i> ทำสำเนา
                        </button>
                        <button @click="deleteElement(selectedElement)" class="flex-1 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                            <i class="fas fa-trash mr-1"></i> ลบ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Element Modal --}}
    <div x-show="showAddElementModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showAddElementModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เลือกประเภท Element</h3>
            <div class="grid grid-cols-3 gap-3">
                @foreach($elementTypes as $type => $label)
                <button @click="createNewElement('{{ $type }}')" class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition text-center">
                    <i class="fas fa-{{ $type === 'heading' ? 'heading' : ($type === 'text' ? 'paragraph' : ($type === 'image' ? 'image' : ($type === 'button' ? 'square' : ($type === 'video' ? 'video' : ($type === 'icon' ? 'icons' : ($type === 'html' ? 'code' : 'arrows-alt')))))) }} text-2xl text-gray-400 mb-2"></i>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</div>
                </button>
                @endforeach
            </div>
            <button @click="showAddElementModal = false" class="mt-4 w-full py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">ยกเลิก</button>
        </div>
    </div>

    {{-- Export Modal --}}
    <div x-show="showExportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showExportModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl p-6" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Export Homepage</h3>
            <textarea x-ref="exportData" class="w-full h-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 font-mono text-sm" readonly></textarea>
            <div class="flex justify-end space-x-3 mt-4">
                <button @click="copyExportData()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    <i class="fas fa-copy mr-1"></i> Copy
                </button>
                <button @click="downloadExportData()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-download mr-1"></i> Download
                </button>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showImportModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl p-6" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Import Homepage</h3>
            <textarea x-model="importData" class="w-full h-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 font-mono text-sm" placeholder="วาง JSON data ที่นี่..."></textarea>
            <div class="flex items-center mt-4">
                <label class="flex items-center">
                    <input type="checkbox" x-model="replaceOnImport" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ลบข้อมูลเดิมทั้งหมดก่อน import</span>
                </label>
            </div>
            <div class="flex justify-end space-x-3 mt-4">
                <button @click="showImportModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    ยกเลิก
                </button>
                <button @click="importHomepage()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-upload mr-1"></i> Import
                </button>
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div x-show="loading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-indigo-600 mb-4"></i>
            <p class="text-gray-700 dark:text-gray-300">กำลังโหลด...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function homepageManager() {
    return {
        // State
        sections: @json($sections->map->toApiArray()),
        templates: @json($templates),
        selectedSection: null,
        selectedElement: null,
        selectedElementSection: null,

        // UI State
        loading: false,
        saving: false,
        deviceMode: 'desktop',
        zoom: 100,
        showGrid: false,
        showTemplates: false,
        searchElements: '',
        showAddElementModal: false,
        showExportModal: false,
        showImportModal: false,
        importData: '',
        replaceOnImport: false,

        // History for undo/redo
        history: [],
        historyIndex: -1,

        // Drag state
        draggingElement: null,
        dragStartX: 0,
        dragStartY: 0,
        elementStartX: 0,
        elementStartY: 0,

        // Sortable instances
        sectionsSortable: null,

        init() {
            this.saveHistory();
            this.initSortable();

            // Global click to deselect
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.homepage-element') && !e.target.closest('[data-section-id]') && !e.target.closest('.properties-panel')) {
                    // Don't deselect if clicking on properties panel
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Delete' && this.selectedElement) {
                    this.deleteElement(this.selectedElement);
                }
                if (e.ctrlKey && e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                }
                if (e.ctrlKey && e.key === 'y') {
                    e.preventDefault();
                    this.redo();
                }
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    this.saveAll();
                }
            });
        },

        initSortable() {
            const container = document.getElementById('sections-container');
            if (container) {
                this.sectionsSortable = new Sortable(container, {
                    animation: 150,
                    handle: '[data-section-id]',
                    ghostClass: 'sortable-ghost',
                    onEnd: (evt) => {
                        const newOrder = Array.from(container.querySelectorAll('[data-section-id]')).map(el => parseInt(el.dataset.sectionId));
                        this.reorderSections(newOrder);
                    }
                });
            }
        },

        // Section Methods
        async addSection(type = 'custom') {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.homepage-manager.sections.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: type === 'hero' ? 'Hero Section' : (type === 'custom' ? 'Section ใหม่' : type.charAt(0).toUpperCase() + type.slice(1) + ' Section'),
                        type: type,
                        is_active: true
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.sections.push(data.data);
                    this.saveHistory();
                    this.showNotification('เพิ่ม Section สำเร็จ', 'success');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
            this.loading = false;
        },

        selectSection(section) {
            this.selectedSection = section;
            this.selectedElement = null;
        },

        editSection(section) {
            this.selectedSection = section;
            this.selectedElement = null;
        },

        async updateSection() {
            if (!this.selectedSection) return;

            try {
                const response = await fetch(`/admin/homepage-manager/sections/${this.selectedSection.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.selectedSection.name,
                        type: this.selectedSection.type,
                        min_height: this.selectedSection.min_height,
                        max_height: this.selectedSection.max_height,
                        padding: this.selectedSection.padding,
                        background_type: this.selectedSection.background?.type,
                        background_color: this.selectedSection.background?.color,
                        background_color_dark: this.selectedSection.background?.color_dark,
                        background_gradient: this.selectedSection.background?.gradient,
                        background_image: this.selectedSection.background?.image,
                        animation: this.selectedSection.animation?.type
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Update local state
                    const index = this.sections.findIndex(s => s.id === this.selectedSection.id);
                    if (index !== -1) {
                        this.sections[index] = { ...this.sections[index], ...data.data };
                    }
                }
            } catch (error) {
                console.error('Update section error:', error);
            }
        },

        async deleteSection(section) {
            if (!confirm('ยืนยันการลบ Section นี้?')) return;

            try {
                const response = await fetch(`/admin/homepage-manager/sections/${section.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.sections = this.sections.filter(s => s.id !== section.id);
                    if (this.selectedSection?.id === section.id) {
                        this.selectedSection = null;
                    }
                    this.saveHistory();
                    this.showNotification('ลบ Section สำเร็จ', 'success');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
        },

        async duplicateSection(section) {
            try {
                const response = await fetch(`/admin/homepage-manager/sections/${section.id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.sections.push(data.data);
                    this.saveHistory();
                    this.showNotification('ทำสำเนา Section สำเร็จ', 'success');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
        },

        async toggleSection(section) {
            try {
                const response = await fetch(`/admin/homepage-manager/sections/${section.id}/toggle`, {
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
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
        },

        async moveSection(index, direction) {
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.sections.length) return;

            const temp = this.sections[index];
            this.sections[index] = this.sections[newIndex];
            this.sections[newIndex] = temp;

            await this.reorderSections(this.sections.map(s => s.id));
        },

        async reorderSections(sectionIds) {
            try {
                await fetch('{{ route("admin.homepage-manager.sections.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ sections: sectionIds })
                });
            } catch (error) {
                console.error('Reorder error:', error);
            }
        },

        // Element Methods
        addElement(section) {
            this.selectedElementSection = section;
            this.showAddElementModal = true;
        },

        async createNewElement(type) {
            if (!this.selectedElementSection) return;

            this.showAddElementModal = false;
            this.loading = true;

            try {
                const response = await fetch(`/admin/homepage-manager/sections/${this.selectedElementSection.id}/elements`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        type: type,
                        content: type === 'heading' ? 'Heading' : (type === 'text' ? 'Text content' : (type === 'button' ? 'Button' : null)),
                        is_active: true
                    })
                });
                const data = await response.json();
                if (data.success) {
                    const sectionIndex = this.sections.findIndex(s => s.id === this.selectedElementSection.id);
                    if (sectionIndex !== -1) {
                        if (!this.sections[sectionIndex].elements) {
                            this.sections[sectionIndex].elements = [];
                        }
                        this.sections[sectionIndex].elements.push(data.data);
                    }
                    this.saveHistory();
                    this.showNotification('เพิ่ม Element สำเร็จ', 'success');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
            this.loading = false;
        },

        selectElement(element, section) {
            this.selectedElement = element;
            this.selectedSection = section;
            this.selectedElementSection = section;
        },

        async updateElement() {
            if (!this.selectedElement) return;

            try {
                const response = await fetch(`/admin/homepage-manager/elements/${this.selectedElement.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        content: this.selectedElement.content?.text,
                        position_type: this.selectedElement.position?.type,
                        position_x: this.selectedElement.position?.x,
                        position_y: this.selectedElement.position?.y,
                        position_x_unit: this.selectedElement.position?.x_unit,
                        position_y_unit: this.selectedElement.position?.y_unit,
                        width: this.selectedElement.size?.width,
                        height: this.selectedElement.size?.height,
                        font_size: this.selectedElement.typography?.font_size,
                        font_weight: this.selectedElement.typography?.font_weight,
                        text_align: this.selectedElement.typography?.text_align,
                        color: this.selectedElement.colors?.color,
                        background_color: this.selectedElement.colors?.background_color,
                        border_width: this.selectedElement.border?.width,
                        border_style: this.selectedElement.border?.style,
                        border_radius: this.selectedElement.border?.radius,
                        padding: this.selectedElement.spacing?.padding,
                        margin: this.selectedElement.spacing?.margin,
                        animation: this.selectedElement.animation?.type,
                        image_url: this.selectedElement.content?.image_url,
                        link_url: this.selectedElement.content?.link_url
                    })
                });
            } catch (error) {
                console.error('Update element error:', error);
            }
        },

        async deleteElement(element) {
            if (!confirm('ยืนยันการลบ Element นี้?')) return;

            try {
                const response = await fetch(`/admin/homepage-manager/elements/${element.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    // Remove from section
                    this.sections.forEach(section => {
                        section.elements = section.elements?.filter(e => e.id !== element.id) || [];
                    });
                    this.selectedElement = null;
                    this.saveHistory();
                    this.showNotification('ลบ Element สำเร็จ', 'success');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
        },

        async duplicateElement(element) {
            try {
                const response = await fetch(`/admin/homepage-manager/elements/${element.id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    const section = this.sections.find(s => s.id === element.section_id);
                    if (section) {
                        section.elements.push(data.data);
                    }
                    this.saveHistory();
                    this.showNotification('ทำสำเนา Element สำเร็จ', 'success');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
        },

        // Drag & Drop Element
        dragElementType(event, type) {
            event.dataTransfer.setData('element-type', type);
        },

        dropElement(event, section) {
            event.currentTarget.classList.remove('bg-indigo-100', 'dark:bg-indigo-900/30');
            const type = event.dataTransfer.getData('element-type');
            if (type) {
                this.selectedElementSection = section;
                this.createNewElement(type);
            }
        },

        startDragElement(event, element, section) {
            if (element.position?.type !== 'absolute') return;

            this.draggingElement = element;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.elementStartX = element.position.x;
            this.elementStartY = element.position.y;

            document.addEventListener('mousemove', this.dragElement.bind(this));
            document.addEventListener('mouseup', this.stopDragElement.bind(this));
        },

        dragElement(event) {
            if (!this.draggingElement) return;

            const dx = event.clientX - this.dragStartX;
            const dy = event.clientY - this.dragStartY;

            const unit = this.draggingElement.position.x_unit;
            const scale = unit === '%' ? 0.1 : 1;

            this.draggingElement.position.x = this.elementStartX + (dx * scale);
            this.draggingElement.position.y = this.elementStartY + (dy * scale);
        },

        stopDragElement() {
            if (this.draggingElement) {
                this.updateElement();
            }
            this.draggingElement = null;
            document.removeEventListener('mousemove', this.dragElement.bind(this));
            document.removeEventListener('mouseup', this.stopDragElement.bind(this));
        },

        // Resize Element
        startResize(event, element, handle) {
            // Implement resize logic
        },

        // Style Helpers
        getSectionStyle(section) {
            let style = `min-height: ${section.min_height || '400px'}; padding: ${section.padding || '60px 20px'};`;

            if (section.background) {
                switch (section.background.type) {
                    case 'color':
                        style += ` background-color: ${section.background.color || '#ffffff'};`;
                        break;
                    case 'gradient':
                        style += ` background: ${section.background.gradient || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'};`;
                        break;
                    case 'image':
                        if (section.background.image) {
                            style += ` background-image: url('${section.background.image}'); background-size: cover; background-position: center;`;
                        }
                        break;
                }
            }

            return style;
        },

        getElementStyle(element) {
            let style = '';

            // Position
            if (element.position?.type === 'absolute') {
                style += `left: ${element.position.x}${element.position.x_unit}; top: ${element.position.y}${element.position.y_unit};`;
            }

            // Size
            if (element.size?.width && element.size.width !== 'auto') {
                style += ` width: ${element.size.width};`;
            }
            if (element.size?.height && element.size.height !== 'auto') {
                style += ` height: ${element.size.height};`;
            }

            // Typography
            if (element.typography) {
                style += ` font-size: ${element.typography.font_size || '16px'};`;
                style += ` font-weight: ${element.typography.font_weight || '400'};`;
                style += ` text-align: ${element.typography.text_align || 'left'};`;
            }

            // Colors
            if (element.colors) {
                style += ` color: ${element.colors.color || '#000000'};`;
                if (element.colors.background_color) {
                    style += ` background-color: ${element.colors.background_color};`;
                }
            }

            // Border
            if (element.border) {
                if (element.border.width && element.border.width !== '0') {
                    style += ` border: ${element.border.width} ${element.border.style || 'solid'} ${element.colors?.border_color || '#000'};`;
                }
                if (element.border.radius && element.border.radius !== '0') {
                    style += ` border-radius: ${element.border.radius};`;
                }
            }

            // Spacing
            if (element.spacing) {
                if (element.spacing.padding && element.spacing.padding !== '0') {
                    style += ` padding: ${element.spacing.padding};`;
                }
                if (element.spacing.margin && element.spacing.margin !== '0') {
                    style += ` margin: ${element.spacing.margin};`;
                }
            }

            // Z-index
            style += ` z-index: ${element.order || 0};`;

            return style;
        },

        // History
        saveHistory() {
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(JSON.parse(JSON.stringify(this.sections)));
            this.historyIndex = this.history.length - 1;
        },

        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.sections = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            }
        },

        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.sections = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            }
        },

        // Export/Import
        async showExport() {
            this.showExportModal = true;
            try {
                const response = await fetch('{{ route("admin.homepage-manager.export") }}');
                const data = await response.json();
                if (data.success) {
                    this.$refs.exportData.value = JSON.stringify(data.data, null, 2);
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
        },

        copyExportData() {
            this.$refs.exportData.select();
            document.execCommand('copy');
            this.showNotification('คัดลอกแล้ว', 'success');
        },

        downloadExportData() {
            const blob = new Blob([this.$refs.exportData.value], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'homepage-export-' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
            URL.revokeObjectURL(url);
        },

        async importHomepage() {
            try {
                const data = JSON.parse(this.importData);
                const response = await fetch('{{ route("admin.homepage-manager.import") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        data: data,
                        replace: this.replaceOnImport
                    })
                });
                const result = await response.json();
                if (result.success) {
                    this.showImportModal = false;
                    location.reload();
                }
            } catch (error) {
                this.showNotification('JSON ไม่ถูกต้อง', 'error');
            }
        },

        async importTemplate(templateId) {
            if (!confirm('ต้องการนำเข้าเทมเพลตนี้?')) return;

            this.loading = true;
            try {
                const response = await fetch(`/admin/homepage-manager/templates/${templateId}/import`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด', 'error');
            }
            this.loading = false;
        },

        // Save
        async saveAll() {
            this.saving = true;
            // All changes are saved automatically via updateSection/updateElement
            await new Promise(resolve => setTimeout(resolve, 500));
            this.saving = false;
            this.showNotification('บันทึกสำเร็จ', 'success');
        },

        // Upload
        async uploadBackgroundImage() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                try {
                    const response = await fetch('{{ route("admin.homepage-manager.upload") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.selectedSection.background.image = data.url;
                        this.updateSection();
                    }
                } catch (error) {
                    this.showNotification('อัพโหลดไม่สำเร็จ', 'error');
                }
            };
            input.click();
        },

        async uploadElementImage() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                try {
                    const response = await fetch('{{ route("admin.homepage-manager.upload") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.selectedElement.content.image_url = data.url;
                        this.updateElement();
                    }
                } catch (error) {
                    this.showNotification('อัพโหลดไม่สำเร็จ', 'error');
                }
            };
            input.click();
        },

        // Notification
        showNotification(message, type = 'info') {
            // Simple alert for now - can be enhanced with toast library
            if (type === 'error') {
                console.error(message);
            }
        }
    }
}
</script>
@endsection
