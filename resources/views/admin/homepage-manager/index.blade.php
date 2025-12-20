@extends('layouts.admin')

@section('title', $pageTitle ?? 'จัดการหน้าแรก')

@push('styles')
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
    .resize-handle { position: absolute; width: 10px; height: 10px; background: #6366f1; border-radius: 2px; z-index: 100; }
    .resize-handle.nw { top: -5px; left: -5px; cursor: nwse-resize; }
    .resize-handle.ne { top: -5px; right: -5px; cursor: nesw-resize; }
    .resize-handle.sw { bottom: -5px; left: -5px; cursor: nesw-resize; }
    .resize-handle.se { bottom: -5px; right: -5px; cursor: nwse-resize; }

    /* Canvas grid */
    .canvas-grid { background-image: linear-gradient(rgba(99, 102, 241, 0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(99, 102, 241, 0.1) 1px, transparent 1px); background-size: 20px 20px; }

    /* Glassmorphism Panel */
    .glass-panel {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .dark .glass-panel {
        background: rgba(0, 0, 0, 0.2);
        border-color: rgba(255, 255, 255, 0.1);
    }

    /* Animations */
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes fadeInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes slideInUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    @keyframes zoomIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
    @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

    .animate-fadeIn { animation: fadeIn 0.5s ease-out forwards; }
    .animate-fadeInUp { animation: fadeInUp 0.5s ease-out forwards; }
    .animate-fadeInDown { animation: fadeInDown 0.5s ease-out forwards; }
    .animate-fadeInLeft { animation: fadeInLeft 0.5s ease-out forwards; }
    .animate-fadeInRight { animation: fadeInRight 0.5s ease-out forwards; }
    .animate-slideInUp { animation: slideInUp 0.5s ease-out forwards; }
    .animate-zoomIn { animation: zoomIn 0.5s ease-out forwards; }
    .animate-bounce { animation: bounce 1s ease infinite; }
    .animate-pulse { animation: pulse 2s ease infinite; }

    /* Element hover effect */
    .homepage-element:hover { outline: 1px dashed #6366f1; outline-offset: 2px; }

    /* Panel tabs */
    .panel-tab { transition: all 0.2s; }
    .panel-tab.active { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; }

    /* Draggable element card */
    .element-card {
        transition: all 0.2s;
        cursor: grab;
    }
    .element-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
    }
    .element-card:active {
        cursor: grabbing;
    }

    /* Color picker */
    .color-picker-wrapper input[type="color"] {
        -webkit-appearance: none;
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    .color-picker-wrapper input[type="color"]::-webkit-color-swatch-wrapper {
        padding: 0;
    }
    .color-picker-wrapper input[type="color"]::-webkit-color-swatch {
        border: 2px solid #e5e7eb;
        border-radius: 6px;
    }

    /* Toast notification */
    .toast-container {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .toast {
        padding: 12px 20px;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        animation: fadeInRight 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .toast.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
    .toast.error { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
    .toast.info { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; }
    .toast.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }

    /* Quick action floating button */
    .quick-action-btn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s;
    }
    .quick-action-btn:hover {
        transform: scale(1.1);
    }

    /* Layer item */
    .layer-item {
        transition: all 0.2s;
    }
    .layer-item:hover {
        background: rgba(99, 102, 241, 0.1);
    }
    .layer-item.selected {
        background: rgba(99, 102, 241, 0.2);
        border-left: 3px solid #6366f1;
    }

    /* Block template card */
    .block-template-card {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .block-template-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    }
    .block-template-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .block-template-card:hover::after {
        opacity: 1;
    }
    .block-template-card .card-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16px;
        transform: translateY(100%);
        transition: transform 0.3s;
        z-index: 1;
    }
    .block-template-card:hover .card-overlay {
        transform: translateY(0);
    }

    /* Gradient presets */
    .gradient-preset {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .gradient-preset:hover {
        transform: scale(1.1);
    }

    /* AI generate button */
    .ai-generate-btn {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        background-size: 200% 200%;
        animation: gradientShift 3s ease infinite;
    }
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Keyboard shortcut badge */
    .kbd {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 6px;
        font-size: 11px;
        font-family: monospace;
        background: rgba(0,0,0,0.1);
        border-radius: 4px;
        border: 1px solid rgba(0,0,0,0.2);
    }
    .dark .kbd {
        background: rgba(255,255,255,0.1);
        border-color: rgba(255,255,255,0.2);
    }
</style>
@endpush

@section('content')
<div x-data="homepageManager()" x-init="init()" class="h-screen flex flex-col bg-gray-100 dark:bg-gray-900 overflow-hidden">

    {{-- Toast Container --}}
    <div class="toast-container" x-show="toasts.length > 0">
        <template x-for="(toast, index) in toasts" :key="index">
            <div class="toast" :class="toast.type" x-show="toast.show" x-transition>
                <i class="fas" :class="{
                    'fa-check-circle': toast.type === 'success',
                    'fa-exclamation-circle': toast.type === 'error',
                    'fa-info-circle': toast.type === 'info',
                    'fa-exclamation-triangle': toast.type === 'warning'
                }"></i>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    {{-- Top Toolbar --}}
    <div class="flex-shrink-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-2 shadow-sm">
        <div class="flex items-center justify-between">
            {{-- Left: Title & Device Preview --}}
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3">
                        <i class="fas fa-magic text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">Homepage Builder</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Visual Editor Pro</p>
                    </div>
                </div>

                <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>

                {{-- Device Preview Toggle --}}
                <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                    <button @click="deviceMode = 'desktop'" :class="deviceMode === 'desktop' ? 'bg-white dark:bg-gray-600 shadow-sm' : 'text-gray-500'" class="px-3 py-1.5 rounded-lg text-sm transition flex items-center" title="Desktop (1440px)">
                        <i class="fas fa-desktop"></i>
                    </button>
                    <button @click="deviceMode = 'tablet'" :class="deviceMode === 'tablet' ? 'bg-white dark:bg-gray-600 shadow-sm' : 'text-gray-500'" class="px-3 py-1.5 rounded-lg text-sm transition flex items-center" title="Tablet (768px)">
                        <i class="fas fa-tablet-alt"></i>
                    </button>
                    <button @click="deviceMode = 'mobile'" :class="deviceMode === 'mobile' ? 'bg-white dark:bg-gray-600 shadow-sm' : 'text-gray-500'" class="px-3 py-1.5 rounded-lg text-sm transition flex items-center" title="Mobile (375px)">
                        <i class="fas fa-mobile-alt"></i>
                    </button>
                </div>

                {{-- Zoom --}}
                <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-xl px-2 py-1">
                    <button @click="zoom = Math.max(25, zoom - 25)" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <i class="fas fa-minus text-xs"></i>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-gray-400 w-12 text-center font-medium" x-text="zoom + '%'"></span>
                    <button @click="zoom = Math.min(200, zoom + 25)" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <i class="fas fa-plus text-xs"></i>
                    </button>
                    <button @click="zoom = 100" class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition text-xs" title="Reset">
                        <i class="fas fa-compress-arrows-alt"></i>
                    </button>
                </div>

                {{-- Grid Toggle --}}
                <button @click="showGrid = !showGrid" :class="showGrid ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'" class="p-2 rounded-lg transition" title="Toggle Grid">
                    <i class="fas fa-th"></i>
                </button>
            </div>

            {{-- Center: Quick Actions --}}
            <div class="flex items-center space-x-2">
                {{-- Undo/Redo --}}
                <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                    <button @click="undo()" :disabled="historyIndex <= 0" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-30 transition" title="Undo (Ctrl+Z)">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button @click="redo()" :disabled="historyIndex >= history.length - 1" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-30 transition" title="Redo (Ctrl+Y)">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>

                {{-- Clear All --}}
                <button @click="clearAll()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-500 hover:text-red-500 transition" title="Clear All">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>

            {{-- Right: Action Buttons --}}
            <div class="flex items-center space-x-2">
                {{-- Keyboard Shortcuts --}}
                <button @click="showShortcutsModal = true" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Keyboard Shortcuts">
                    <i class="fas fa-keyboard"></i>
                </button>

                {{-- Import/Export --}}
                <button @click="showExport()" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center">
                    <i class="fas fa-download mr-1.5"></i> Export
                </button>
                <button @click="showImportModal = true" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center">
                    <i class="fas fa-upload mr-1.5"></i> Import
                </button>

                <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

                {{-- Preview --}}
                <a href="{{ route('admin.homepage-manager.preview') }}" target="_blank" class="px-3 py-2 text-sm bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800 transition flex items-center">
                    <i class="fas fa-eye mr-1.5"></i> Preview
                </a>

                {{-- Save --}}
                <button @click="saveAll()" :disabled="saving" class="px-5 py-2 text-sm bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition disabled:opacity-50 flex items-center shadow-lg shadow-indigo-500/25">
                    <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'" class="mr-1.5"></i>
                    <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Main 3-Panel Layout --}}
    <div class="flex-1 flex overflow-hidden">

        {{-- Left Panel: Elements & Blocks Library --}}
        <div class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
            {{-- Panel Tabs --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button @click="leftPanelTab = 'elements'" :class="leftPanelTab === 'elements' && 'active'" class="panel-tab flex-1 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    <i class="fas fa-shapes mr-1.5"></i> Elements
                </button>
                <button @click="leftPanelTab = 'blocks'" :class="leftPanelTab === 'blocks' && 'active'" class="panel-tab flex-1 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    <i class="fas fa-layer-group mr-1.5"></i> Blocks
                </button>
                <button @click="leftPanelTab = 'templates'" :class="leftPanelTab === 'templates' && 'active'" class="panel-tab flex-1 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    <i class="fas fa-palette mr-1.5"></i> Templates
                </button>
            </div>

            {{-- Search --}}
            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="searchQuery" placeholder="ค้นหา..." class="w-full pl-10 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Elements Tab --}}
            <div x-show="leftPanelTab === 'elements'" class="flex-1 overflow-y-auto hide-scrollbar p-3 space-y-4">
                {{-- Add Section Button --}}
                <button @click="addSection()" class="w-full py-3 border-2 border-dashed border-indigo-300 dark:border-indigo-600 rounded-xl text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition flex items-center justify-center font-medium">
                    <i class="fas fa-plus-circle mr-2"></i> เพิ่ม Section ใหม่
                </button>

                {{-- Section Types --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                        <i class="fas fa-layer-group mr-2"></i> Section Types
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="(label, type) in sectionTypes" :key="type">
                            <button @click="addSection(type)" class="element-card p-3 text-left border border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition group">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center mb-2">
                                    <i class="fas text-white text-sm" :class="getSectionIcon(type)"></i>
                                </div>
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate" x-text="label"></div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Basic Elements --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                        <i class="fas fa-font mr-2"></i> Basic Elements
                    </h3>
                    <div class="space-y-1">
                        <template x-for="(elem, index) in basicElements" :key="index">
                            <div draggable="true" @dragstart="dragElementType($event, elem.type)" class="element-card flex items-center p-2.5 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" :style="'background:' + elem.color">
                                    <i class="fas text-white text-sm" :class="elem.icon"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="elem.label"></div>
                                    <div class="text-xs text-gray-400" x-text="elem.desc"></div>
                                </div>
                                <i class="fas fa-grip-vertical text-gray-300"></i>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Advanced Elements --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                        <i class="fas fa-star mr-2"></i> Advanced Elements
                    </h3>
                    <div class="space-y-1">
                        <template x-for="(elem, index) in advancedElements" :key="index">
                            <div draggable="true" @dragstart="dragElementType($event, elem.type)" class="element-card flex items-center p-2.5 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" :style="'background:' + elem.color">
                                    <i class="fas text-white text-sm" :class="elem.icon"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="elem.label"></div>
                                    <div class="text-xs text-gray-400" x-text="elem.desc"></div>
                                </div>
                                <i class="fas fa-grip-vertical text-gray-300"></i>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Interactive Elements --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                        <i class="fas fa-hand-pointer mr-2"></i> Interactive
                    </h3>
                    <div class="space-y-1">
                        <template x-for="(elem, index) in interactiveElements" :key="index">
                            <div draggable="true" @dragstart="dragElementType($event, elem.type)" class="element-card flex items-center p-2.5 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" :style="'background:' + elem.color">
                                    <i class="fas text-white text-sm" :class="elem.icon"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="elem.label"></div>
                                    <div class="text-xs text-gray-400" x-text="elem.desc"></div>
                                </div>
                                <i class="fas fa-grip-vertical text-gray-300"></i>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Blocks Tab --}}
            <div x-show="leftPanelTab === 'blocks'" class="flex-1 overflow-y-auto hide-scrollbar p-3 space-y-4">
                {{-- Pre-built Blocks --}}
                <template x-for="(category, catName) in prebuiltBlocks" :key="catName">
                    <div class="space-y-2">
                        <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider" x-text="catName"></h3>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="(block, blockIndex) in category" :key="blockIndex">
                                <div @click="addPrebuiltBlock(block)" class="block-template-card border border-gray-200 dark:border-gray-600">
                                    <div class="aspect-video bg-gradient-to-br" :style="'background:' + block.preview"></div>
                                    <div class="card-overlay text-white">
                                        <div class="text-sm font-medium" x-text="block.name"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Templates Tab --}}
            <div x-show="leftPanelTab === 'templates'" class="flex-1 overflow-y-auto hide-scrollbar p-3 space-y-3">
                <template x-for="template in templates" :key="template.id">
                    <div @click="importTemplate(template.id)" class="block-template-card border border-gray-200 dark:border-gray-600 overflow-hidden">
                        <template x-if="template.thumbnail">
                            <img :src="template.thumbnail" :alt="template.name" class="w-full h-28 object-cover">
                        </template>
                        <template x-if="!template.thumbnail">
                            <div class="w-full h-28 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-layer-group text-4xl text-white/50"></i>
                            </div>
                        </template>
                        <div class="p-3 bg-white dark:bg-gray-800">
                            <h4 class="font-medium text-sm text-gray-900 dark:text-white" x-text="template.name"></h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="template.description || template.category"></p>
                        </div>
                    </div>
                </template>

                {{-- Save as Template --}}
                <button @click="showSaveTemplateModal = true" class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-indigo-500 hover:text-indigo-500 transition flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> บันทึกเป็น Template
                </button>
            </div>
        </div>

        {{-- Center: Canvas --}}
        <div class="flex-1 bg-gray-200 dark:bg-gray-900 overflow-auto relative" id="canvas-wrapper">
            {{-- Floating Quick Actions --}}
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center space-x-2 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl px-4 py-2 border border-gray-200 dark:border-gray-700">
                <button @click="addSection('hero')" class="quick-action-btn bg-gradient-to-br from-indigo-500 to-purple-600 text-white" title="Add Hero Section">
                    <i class="fas fa-star"></i>
                </button>
                <button @click="addSection('features')" class="quick-action-btn bg-gradient-to-br from-green-500 to-emerald-600 text-white" title="Add Features Section">
                    <i class="fas fa-th-large"></i>
                </button>
                <button @click="addSection('cta')" class="quick-action-btn bg-gradient-to-br from-orange-500 to-red-600 text-white" title="Add CTA Section">
                    <i class="fas fa-bullhorn"></i>
                </button>
                <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>
                <button @click="showAiGeneratorModal = true" class="quick-action-btn ai-generate-btn text-white" title="AI Content Generator">
                    <i class="fas fa-magic"></i>
                </button>
            </div>

            {{-- Canvas Content --}}
            <div class="p-8 min-h-full">
                <div class="mx-auto transition-all duration-300"
                     :style="{
                        width: deviceMode === 'mobile' ? '375px' : (deviceMode === 'tablet' ? '768px' : '100%'),
                        maxWidth: deviceMode === 'desktop' ? '1440px' : 'none',
                        transform: 'scale(' + (zoom / 100) + ')',
                        transformOrigin: 'top center'
                     }">

                    {{-- Canvas Area --}}
                    <div id="sections-container" class="bg-white dark:bg-gray-800 shadow-2xl rounded-xl overflow-hidden min-h-[600px]" :class="showGrid && 'canvas-grid'">
                        {{-- Sections List --}}
                        <template x-for="(section, sectionIndex) in sections" :key="section.id">
                            <div class="relative group border-b-2 border-transparent hover:border-indigo-500/50 transition"
                                 :class="selectedSection?.id === section.id && 'ring-2 ring-indigo-500 ring-opacity-50'"
                                 :style="getSectionStyle(section)"
                                 @click.self="selectSection(section)"
                                 :data-section-id="section.id">

                                {{-- Section Controls Bar --}}
                                <div class="absolute -top-10 left-0 right-0 flex items-center justify-between px-2 opacity-0 group-hover:opacity-100 transition z-50">
                                    <div class="flex items-center space-x-1">
                                        <span class="px-3 py-1 text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium shadow-lg" x-text="section.name"></span>
                                        <span class="px-2 py-1 text-xs bg-gray-800 text-white rounded" x-text="section.type_label || section.type"></span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <button @click.stop="moveSection(sectionIndex, -1)" :disabled="sectionIndex === 0" class="p-1.5 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 disabled:opacity-30 transition" title="ขึ้น">
                                            <i class="fas fa-chevron-up text-xs"></i>
                                        </button>
                                        <button @click.stop="moveSection(sectionIndex, 1)" :disabled="sectionIndex === sections.length - 1" class="p-1.5 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 disabled:opacity-30 transition" title="ลง">
                                            <i class="fas fa-chevron-down text-xs"></i>
                                        </button>
                                        <button @click.stop="duplicateSection(section)" class="p-1.5 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 transition" title="ทำสำเนา">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                        <button @click.stop="toggleSection(section)" class="p-1.5 bg-white dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 transition" title="เปิด/ปิด">
                                            <i class="fas" :class="section.is_active ? 'fa-eye text-green-500' : 'fa-eye-slash text-red-500'"></i>
                                        </button>
                                        <button @click.stop="selectSection(section)" class="p-1.5 bg-indigo-500 text-white rounded-lg shadow hover:bg-indigo-600 transition" title="แก้ไข">
                                            <i class="fas fa-cog text-xs"></i>
                                        </button>
                                        <button @click.stop="deleteSection(section)" class="p-1.5 bg-red-500 text-white rounded-lg shadow hover:bg-red-600 transition" title="ลบ">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- Section Content Container --}}
                                <div class="relative" :class="section.is_fullwidth ? 'w-full' : 'container mx-auto'" style="min-height: inherit;"
                                     @drop.prevent="dropElement($event, section)"
                                     @dragover.prevent="$event.currentTarget.classList.add('ring-2', 'ring-indigo-500', 'ring-dashed')"
                                     @dragleave="$event.currentTarget.classList.remove('ring-2', 'ring-indigo-500', 'ring-dashed')">

                                    {{-- Elements --}}
                                    <template x-for="(element, elemIndex) in section.elements" :key="element.id">
                                        <div class="homepage-element cursor-pointer transition-all"
                                             :class="[
                                                selectedElement?.id === element.id && 'element-selected',
                                                element.position?.type === 'absolute' ? 'absolute' : 'relative'
                                             ]"
                                             :style="getElementStyle(element)"
                                             @click.stop="selectElement(element, section)"
                                             @dblclick.stop="editElementContent(element)"
                                             :data-element-id="element.id">

                                            {{-- Element Content Renderer --}}
                                            <div x-html="renderElement(element)"></div>

                                            {{-- Element Quick Actions (on hover) --}}
                                            <div x-show="selectedElement?.id === element.id" class="absolute -top-8 left-0 flex items-center space-x-1 bg-gray-900 rounded-lg px-2 py-1 shadow-xl z-50">
                                                <button @click.stop="moveElementLayer(element, 1)" class="p-1 text-white hover:text-indigo-400 transition" title="Bring Forward">
                                                    <i class="fas fa-layer-group text-xs"></i>
                                                </button>
                                                <button @click.stop="duplicateElement(element)" class="p-1 text-white hover:text-green-400 transition" title="Duplicate">
                                                    <i class="fas fa-copy text-xs"></i>
                                                </button>
                                                <button @click.stop="deleteElement(element)" class="p-1 text-white hover:text-red-400 transition" title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </div>

                                            {{-- Resize Handles --}}
                                            <template x-if="selectedElement?.id === element.id">
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
                                    <div x-show="!section.elements || section.elements.length === 0" class="flex flex-col items-center justify-center h-full min-h-[200px] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl m-4">
                                        <div class="text-center text-gray-400 p-8">
                                            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-4">
                                                <i class="fas fa-plus-circle text-3xl text-gray-400"></i>
                                            </div>
                                            <p class="text-sm font-medium mb-2">ลาก Element มาวางที่นี่</p>
                                            <p class="text-xs">หรือ <button @click="addElement(section)" class="text-indigo-500 hover:underline">คลิกเพื่อเพิ่ม</button></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Inactive Overlay --}}
                                <div x-show="!section.is_active" class="absolute inset-0 bg-gray-500/60 backdrop-blur-sm flex items-center justify-center z-40">
                                    <div class="bg-gray-800 text-white px-6 py-3 rounded-xl flex items-center">
                                        <i class="fas fa-eye-slash mr-2"></i>
                                        <span>Section ถูกปิดการแสดงผล</span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State --}}
                        <div x-show="sections.length === 0" class="flex flex-col items-center justify-center h-[600px] text-gray-400">
                            <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center mb-6">
                                <i class="fas fa-layer-group text-5xl text-indigo-500"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มี Section</h3>
                            <p class="text-gray-500 mb-6">เริ่มต้นสร้างหน้าแรกของคุณ</p>
                            <div class="flex items-center space-x-3">
                                <button @click="addSection('hero')" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-500/25">
                                    <i class="fas fa-plus mr-2"></i> เพิ่ม Hero Section
                                </button>
                                <button @click="leftPanelTab = 'templates'" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    <i class="fas fa-palette mr-2"></i> เลือก Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel: Properties & Layers --}}
        <div class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
            {{-- Panel Tabs --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button @click="rightPanelTab = 'properties'" :class="rightPanelTab === 'properties' && 'active'" class="panel-tab flex-1 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    <i class="fas fa-sliders-h mr-1.5"></i> Properties
                </button>
                <button @click="rightPanelTab = 'layers'" :class="rightPanelTab === 'layers' && 'active'" class="panel-tab flex-1 px-4 py-3 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                    <i class="fas fa-layer-group mr-1.5"></i> Layers
                </button>
            </div>

            {{-- Properties Tab --}}
            <div x-show="rightPanelTab === 'properties'" class="flex-1 overflow-y-auto hide-scrollbar">
                {{-- No Selection --}}
                <div x-show="!selectedSection && !selectedElement" class="flex flex-col items-center justify-center h-full p-8 text-center">
                    <div class="w-20 h-20 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                        <i class="fas fa-mouse-pointer text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-2">ไม่มีการเลือก</h3>
                    <p class="text-sm text-gray-500">คลิกที่ Section หรือ Element เพื่อแก้ไข</p>
                </div>

                {{-- Section Properties --}}
                <div x-show="selectedSection && !selectedElement" class="p-4 space-y-4">
                    {{-- Section Header --}}
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-layer-group text-indigo-500 mr-2"></i>
                            Section Settings
                        </h3>
                        <button @click="selectedSection = null" class="p-1 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Section Type Badge --}}
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-lg" x-text="selectedSection?.type_label || selectedSection?.type"></span>
                        <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded" x-text="'ID: ' + selectedSection?.id"></span>
                    </div>

                    {{-- Accordion: General --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.general = !propAccordion.general" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">General</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.general && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.general" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ชื่อ Section</label>
                                <input type="text" x-model="selectedSection.name" @input.debounce.500ms="updateSection()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ประเภท</label>
                                <select x-model="selectedSection.type" @change="updateSection()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
                                    <template x-for="(label, type) in sectionTypes" :key="type">
                                        <option :value="type" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Full Width</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="selectedSection.is_fullwidth" @change="updateSection()" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Accordion: Size --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.size = !propAccordion.size" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Size & Spacing</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.size && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.size" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Min Height</label>
                                    <input type="text" x-model="selectedSection.min_height" @input.debounce.500ms="updateSection()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="400px">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Max Height</label>
                                    <input type="text" x-model="selectedSection.max_height" @input.debounce.500ms="updateSection()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="auto">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Padding</label>
                                <input type="text" x-model="selectedSection.padding" @input.debounce.500ms="updateSection()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="60px 20px">
                            </div>
                        </div>
                    </div>

                    {{-- Accordion: Background --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.background = !propAccordion.background" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Background</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.background && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.background" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            {{-- Background Type --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Type</label>
                                <div class="grid grid-cols-4 gap-1">
                                    <template x-for="bgType in ['color', 'gradient', 'image', 'video']" :key="bgType">
                                        <button @click="selectedSection.background.type = bgType; updateSection()" :class="selectedSection.background?.type === bgType ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" class="p-2 rounded-lg text-xs font-medium transition capitalize" x-text="bgType"></button>
                                    </template>
                                </div>
                            </div>

                            {{-- Color Picker --}}
                            <div x-show="selectedSection.background?.type === 'color'" class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Light Mode</span>
                                    <div class="color-picker-wrapper flex items-center space-x-2">
                                        <input type="color" x-model="selectedSection.background.color" @input="updateSection()">
                                        <input type="text" x-model="selectedSection.background.color" @input="updateSection()" class="w-20 px-2 py-1 text-xs border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Dark Mode</span>
                                    <div class="color-picker-wrapper flex items-center space-x-2">
                                        <input type="color" x-model="selectedSection.background.color_dark" @input="updateSection()">
                                        <input type="text" x-model="selectedSection.background.color_dark" @input="updateSection()" class="w-20 px-2 py-1 text-xs border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                    </div>
                                </div>
                            </div>

                            {{-- Gradient --}}
                            <div x-show="selectedSection.background?.type === 'gradient'" class="space-y-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Gradient CSS</label>
                                    <textarea x-model="selectedSection.background.gradient" @input.debounce.500ms="updateSection()" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 font-mono" rows="2" placeholder="linear-gradient(135deg, #667eea 0%, #764ba2 100%)"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-2">Presets</label>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="gradient in gradientPresets" :key="gradient">
                                            <button @click="selectedSection.background.gradient = gradient; updateSection()" class="gradient-preset" :style="'background:' + gradient" :title="gradient"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Image --}}
                            <div x-show="selectedSection.background?.type === 'image'" class="space-y-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Image URL</label>
                                    <div class="flex space-x-2">
                                        <input type="text" x-model="selectedSection.background.image" @input.debounce.500ms="updateSection()" class="flex-1 px-3 py-2 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                        <button @click="uploadBackgroundImage()" class="px-3 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Position</label>
                                        <select x-model="selectedSection.background.position" @change="updateSection()" class="w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                            <option value="center center">Center</option>
                                            <option value="top center">Top</option>
                                            <option value="bottom center">Bottom</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Size</label>
                                        <select x-model="selectedSection.background.size" @change="updateSection()" class="w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                            <option value="cover">Cover</option>
                                            <option value="contain">Contain</option>
                                            <option value="auto">Auto</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Accordion: Animation --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.animation = !propAccordion.animation" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Animation</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.animation && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.animation" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Animation Type</label>
                                <select x-model="selectedSection.animation.type" @change="updateSection()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    <template x-for="(label, type) in animations" :key="type">
                                        <option :value="type" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Delay (ms)</label>
                                <input type="number" x-model.number="selectedSection.animation.delay" @input.debounce.500ms="updateSection()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="0" min="0" step="100">
                            </div>
                        </div>
                    </div>

                    {{-- Section Actions --}}
                    <div class="flex space-x-2 pt-2">
                        <button @click="addElement(selectedSection)" class="flex-1 py-2.5 text-sm font-medium border-2 border-dashed border-indigo-300 dark:border-indigo-600 rounded-xl text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                            <i class="fas fa-plus mr-1"></i> เพิ่ม Element
                        </button>
                    </div>
                </div>

                {{-- Element Properties --}}
                <div x-show="selectedElement" class="p-4 space-y-4">
                    {{-- Element Header --}}
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-cube text-purple-500 mr-2"></i>
                            Element Settings
                        </h3>
                        <button @click="selectedElement = null" class="p-1 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    {{-- Element Type Badge --}}
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-lg" x-text="selectedElement?.type_label || selectedElement?.type"></span>
                    </div>

                    {{-- Content Section --}}
                    <div x-show="['heading', 'text', 'button', 'html'].includes(selectedElement?.type)" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Content</span>
                        </div>
                        <div class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Text Content</label>
                                    <button @click="showAiGeneratorModal = true" class="text-xs text-indigo-500 hover:text-indigo-600 flex items-center">
                                        <i class="fas fa-magic mr-1"></i> AI Generate
                                    </button>
                                </div>
                                <textarea x-model="selectedElement.content.text" @input.debounce.500ms="updateElement()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" rows="3"></textarea>
                            </div>
                            <div x-show="selectedElement?.type === 'heading'">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Heading Level</label>
                                <select x-model="selectedElement.settings.heading_level" @change="updateElement()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    <option value="h1">H1 - Main Title</option>
                                    <option value="h2">H2 - Section Title</option>
                                    <option value="h3">H3 - Sub Title</option>
                                    <option value="h4">H4 - Heading</option>
                                    <option value="h5">H5 - Small Heading</option>
                                    <option value="h6">H6 - Tiny Heading</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Image Section --}}
                    <div x-show="selectedElement?.type === 'image'" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Image</span>
                        </div>
                        <div class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Image URL</label>
                                <div class="flex space-x-2">
                                    <input type="text" x-model="selectedElement.content.image_url" @input.debounce.500ms="updateElement()" class="flex-1 px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    <button @click="uploadElementImage()" class="px-3 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Alt Text</label>
                                <input type="text" x-model="selectedElement.content.alt" @input.debounce.500ms="updateElement()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="รูปภาพ...">
                            </div>
                        </div>
                    </div>

                    {{-- Link Section --}}
                    <div x-show="['button', 'image'].includes(selectedElement?.type)" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Link</span>
                        </div>
                        <div class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">URL</label>
                                <input type="text" x-model="selectedElement.content.link_url" @input.debounce.500ms="updateElement()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="https://...">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Target</label>
                                <select x-model="selectedElement.content.link_target" @change="updateElement()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    <option value="_self">เปิดหน้าเดิม</option>
                                    <option value="_blank">เปิดหน้าใหม่</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Typography Section --}}
                    <div x-show="['heading', 'text', 'button'].includes(selectedElement?.type)" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.typography = !propAccordion.typography" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Typography</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.typography && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.typography" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Font Size</label>
                                    <input type="text" x-model="selectedElement.typography.font_size" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="16px">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Font Weight</label>
                                    <select x-model="selectedElement.typography.font_weight" @change="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
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
                                <div class="flex border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                    <template x-for="align in ['left', 'center', 'right', 'justify']" :key="align">
                                        <button @click="selectedElement.typography.text_align = align; updateElement()" :class="selectedElement?.typography?.text_align === align ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-600'" class="flex-1 py-2 text-center transition">
                                            <i class="fas" :class="'fa-align-' + align"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Line Height</label>
                                <input type="text" x-model="selectedElement.typography.line_height" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700" placeholder="1.5">
                            </div>
                        </div>
                    </div>

                    {{-- Colors Section --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.colors = !propAccordion.colors" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Colors</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.colors && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.colors" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Text Color</span>
                                <div class="color-picker-wrapper flex items-center space-x-2">
                                    <input type="color" x-model="selectedElement.colors.color" @input="updateElement()">
                                    <input type="text" x-model="selectedElement.colors.color" @input.debounce.500ms="updateElement()" class="w-20 px-2 py-1 text-xs border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Background</span>
                                <div class="color-picker-wrapper flex items-center space-x-2">
                                    <input type="color" x-model="selectedElement.colors.background_color" @input="updateElement()">
                                    <input type="text" x-model="selectedElement.colors.background_color" @input.debounce.500ms="updateElement()" class="w-20 px-2 py-1 text-xs border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Border & Spacing --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.border = !propAccordion.border" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Border & Spacing</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.border && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.border" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Width</label>
                                    <input type="text" x-model="selectedElement.border.width" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Style</label>
                                    <select x-model="selectedElement.border.style" @change="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                        <option value="solid">Solid</option>
                                        <option value="dashed">Dashed</option>
                                        <option value="dotted">Dotted</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Radius</label>
                                    <input type="text" x-model="selectedElement.border.radius" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700" placeholder="0">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Padding</label>
                                    <input type="text" x-model="selectedElement.spacing.padding" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Margin</label>
                                    <input type="text" x-model="selectedElement.spacing.margin" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700" placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Animation --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="propAccordion.elemAnimation = !propAccordion.elemAnimation" class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">Animation</span>
                            <i class="fas fa-chevron-down transition" :class="propAccordion.elemAnimation && 'rotate-180'"></i>
                        </button>
                        <div x-show="propAccordion.elemAnimation" x-collapse class="p-3 space-y-3 bg-white dark:bg-gray-800">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Animation</label>
                                <select x-model="selectedElement.animation.type" @change="updateElement()" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    <template x-for="(label, type) in animations" :key="type">
                                        <option :value="type" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Delay (ms)</label>
                                    <input type="number" x-model.number="selectedElement.animation.delay" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700" min="0" step="100">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Duration (ms)</label>
                                    <input type="number" x-model.number="selectedElement.animation.duration" @input.debounce.500ms="updateElement()" class="w-full px-2 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded bg-white dark:bg-gray-700" min="0" step="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Element Actions --}}
                    <div class="flex space-x-2 pt-2">
                        <button @click="duplicateElement(selectedElement)" class="flex-1 py-2.5 text-sm font-medium bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            <i class="fas fa-copy mr-1"></i> ทำสำเนา
                        </button>
                        <button @click="deleteElement(selectedElement)" class="flex-1 py-2.5 text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                            <i class="fas fa-trash mr-1"></i> ลบ
                        </button>
                    </div>
                </div>
            </div>

            {{-- Layers Tab --}}
            <div x-show="rightPanelTab === 'layers'" class="flex-1 overflow-y-auto hide-scrollbar p-3 space-y-2">
                <template x-for="(section, sIndex) in sections" :key="section.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div @click="selectSection(section)" :class="selectedSection?.id === section.id && !selectedElement ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'bg-gray-50 dark:bg-gray-700/50'" class="flex items-center justify-between p-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <div class="flex items-center">
                                <button @click.stop="section.expanded = !section.expanded" class="p-1 mr-1">
                                    <i class="fas fa-chevron-right text-xs transition" :class="section.expanded && 'rotate-90'"></i>
                                </button>
                                <i class="fas fa-layer-group text-indigo-500 mr-2"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate" x-text="section.name"></span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button @click.stop="toggleSection(section)" class="p-1 text-gray-400 hover:text-gray-600">
                                    <i class="fas text-xs" :class="section.is_active ? 'fa-eye' : 'fa-eye-slash'"></i>
                                </button>
                            </div>
                        </div>
                        <div x-show="section.expanded" x-collapse class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <template x-for="(element, eIndex) in section.elements" :key="element.id">
                                <div @click="selectElement(element, section)" :class="selectedElement?.id === element.id ? 'bg-purple-50 dark:bg-purple-900/30 border-l-purple-500' : ''" class="layer-item flex items-center justify-between p-2 pl-8 cursor-pointer border-l-2 border-transparent">
                                    <div class="flex items-center">
                                        <i class="fas text-purple-500 mr-2 text-xs" :class="getElementIcon(element.type)"></i>
                                        <span class="text-xs text-gray-600 dark:text-gray-400 truncate" x-text="element.name || element.type_label || element.type"></span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-xs text-gray-400" x-text="eIndex + 1"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!section.elements || section.elements.length === 0" class="p-2 pl-8 text-xs text-gray-400 italic">
                                ไม่มี elements
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Include Modals --}}
    @include('admin.homepage-manager.partials.modals')

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@include('admin.homepage-manager.partials.scripts')
@endpush
