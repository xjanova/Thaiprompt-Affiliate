@extends('layouts.seller')

@section('title', 'Label Designer - ' . $template->name)

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<style>
/* ===== DESIGNER CANVAS ===== */
.designer-canvas {
    background-color: #e5e7eb;
    background-image:
        linear-gradient(45deg, #d1d5db 25%, transparent 25%),
        linear-gradient(-45deg, #d1d5db 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #d1d5db 75%),
        linear-gradient(-45deg, transparent 75%, #d1d5db 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
}

.dark .designer-canvas {
    background-color: #1f2937;
    background-image:
        linear-gradient(45deg, #374151 25%, transparent 25%),
        linear-gradient(-45deg, #374151 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #374151 75%),
        linear-gradient(-45deg, transparent 75%, #374151 75%);
}

.label-canvas {
    background: white;
    position: relative;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

/* ===== DRAGGABLE ELEMENT ===== */
.element-draggable {
    position: absolute;
    cursor: move;
    border: 2px dashed transparent;
    transition: border-color 0.2s;
}

.element-draggable:hover {
    border-color: #3b82f6;
}

.element-draggable.selected {
    border-color: #3b82f6;
    border-style: solid;
}

.element-draggable .resize-handle {
    position: absolute;
    width: 8px;
    height: 8px;
    background: #3b82f6;
    border: 1px solid white;
    border-radius: 50%;
}

.element-draggable .resize-handle.nw { top: -4px; left: -4px; cursor: nw-resize; }
.element-draggable .resize-handle.ne { top: -4px; right: -4px; cursor: ne-resize; }
.element-draggable .resize-handle.sw { bottom: -4px; left: -4px; cursor: sw-resize; }
.element-draggable .resize-handle.se { bottom: -4px; right: -4px; cursor: se-resize; }

/* ===== TOOLBAR ===== */
.toolbar-item {
    @apply px-4 py-3 bg-white dark:bg-gray-800 rounded-lg cursor-pointer transition;
    @apply hover:bg-blue-50 dark:hover:bg-blue-900/20;
    @apply border-2 border-gray-200 dark:border-gray-700;
}

.toolbar-item:hover {
    @apply border-blue-500;
}

/* ===== GRID ===== */
.grid-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(59, 130, 246, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
}
</style>
@endpush

@section('content')
<div class="h-screen flex flex-col" x-data="labelDesigner()" @keydown.delete="deleteSelectedElement()">
    {{-- Top Bar --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('seller.pos.labels.templates.index') }}"
               class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>

            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                    🎨 Label Designer
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $template->name }} ({{ $template->paper_width }} x {{ $template->paper_height }} {{ $template->paper_unit }})
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button @click="showPreview = !showPreview"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                <i class="fas fa-eye mr-2"></i>
                <span x-show="!showPreview">แสดง Preview</span>
                <span x-show="showPreview">ซ่อย Preview</span>
            </button>

            <button @click="saveTemplate()"
                    :disabled="saving"
                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition disabled:opacity-50">
                <i class="fas fa-save mr-2"></i>
                <span x-show="!saving">บันทึก</span>
                <span x-show="saving">กำลังบันทึก...</span>
            </button>
        </div>
    </div>

    <div class="flex-1 flex overflow-hidden">
        {{-- Left Sidebar: Toolbar --}}
        <div class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4 overflow-y-auto">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">
                🛠️ เครื่องมือ
            </h3>

            <div class="space-y-2">
                <div @click="addElement('text')" class="toolbar-item">
                    <i class="fas fa-font text-blue-500 mr-2"></i>
                    <span class="text-sm font-medium">ข้อความ (Text)</span>
                </div>

                <div @click="addElement('barcode')" class="toolbar-item">
                    <i class="fas fa-barcode text-green-500 mr-2"></i>
                    <span class="text-sm font-medium">Barcode</span>
                </div>

                <div @click="addElement('qrcode')" class="toolbar-item">
                    <i class="fas fa-qrcode text-purple-500 mr-2"></i>
                    <span class="text-sm font-medium">QR Code</span>
                </div>

                <div @click="addElement('image')" class="toolbar-item">
                    <i class="fas fa-image text-orange-500 mr-2"></i>
                    <span class="text-sm font-medium">รูปภาพ</span>
                </div>

                <div @click="addElement('shape')" class="toolbar-item">
                    <i class="fas fa-square text-gray-500 mr-2"></i>
                    <span class="text-sm font-medium">รูปร่าง</span>
                </div>
            </div>

            <hr class="my-4 border-gray-200 dark:border-gray-700">

            {{-- Elements List --}}
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">
                📋 Elements (<span x-text="elements.length"></span>)
            </h3>

            <div class="space-y-1">
                <template x-for="(element, index) in elements" :key="element.id">
                    <div @click="selectElement(element)"
                         :class="selectedElement?.id === element.id ? 'bg-blue-100 dark:bg-blue-900/30 border-blue-500' : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600'"
                         class="px-3 py-2 rounded border-2 cursor-pointer transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i :class="{
                                    'fa-font': element.type === 'text',
                                    'fa-barcode': element.type === 'barcode',
                                    'fa-qrcode': element.type === 'qrcode',
                                    'fa-image': element.type === 'image',
                                    'fa-square': element.type === 'shape'
                                }" class="fas text-xs"></i>
                                <span class="text-xs font-medium" x-text="element.label || element.type"></span>
                            </div>
                            <button @click.stop="deleteElement(element)"
                                    class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="elements.length === 0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-4">
                        ยังไม่มี elements<br>คลิกเครื่องมือด้านบนเพื่อเพิ่ม
                    </p>
                </template>
            </div>
        </div>

        {{-- Center: Canvas --}}
        <div class="flex-1 overflow-auto designer-canvas p-8">
            <div class="flex items-center justify-center min-h-full">
                <div class="label-canvas relative"
                     :style="{
                         width: canvasScale * {{ $template->paper_width }} + 'px',
                         height: canvasScale * {{ $template->paper_height }} + 'px',
                         padding: `${canvasScale * {{ $template->margin_top }}}px ${canvasScale * {{ $template->margin_right }}}px ${canvasScale * {{ $template->margin_bottom }}}px ${canvasScale * {{ $template->margin_left }}}px`
                     }"
                     @click.self="deselectElement()">

                    {{-- Grid Overlay --}}
                    <div class="grid-overlay"
                         :style="{
                             backgroundSize: `${canvasScale * 5}px ${canvasScale * 5}px`
                         }"></div>

                    {{-- Label Grid --}}
                    <div class="grid h-full relative z-10"
                         :style="{
                             gridTemplateColumns: `repeat({{ $template->columns }}, 1fr)`,
                             gridTemplateRows: `repeat({{ $template->rows }}, 1fr)`,
                             gap: `${canvasScale * {{ $template->gap_vertical }}}px ${canvasScale * {{ $template->gap_horizontal }}}px`
                         }">
                        <template x-for="i in {{ $template->columns * $template->rows }}" :key="i">
                            <div class="border border-dashed border-gray-300 dark:border-gray-600 rounded relative">
                                <span class="absolute top-1 left-1 text-xs text-gray-400" x-text="i"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Elements --}}
                    <template x-for="element in elements" :key="element.id">
                        <div class="element-draggable"
                             :class="{ 'selected': selectedElement?.id === element.id }"
                             :style="{
                                 left: (element.x * canvasScale) + 'px',
                                 top: (element.y * canvasScale) + 'px',
                                 width: (element.width * canvasScale) + 'px',
                                 height: (element.height * canvasScale) + 'px',
                                 zIndex: element.zIndex || 1
                             }"
                             @mousedown="startDrag($event, element)"
                             @click.stop="selectElement(element)">

                            {{-- Text Element --}}
                            <template x-if="element.type === 'text'">
                                <div :style="{
                                         fontSize: (element.fontSize * canvasScale) + 'px',
                                         fontWeight: element.fontWeight || 'normal',
                                         color: element.color || '#000000',
                                         textAlign: element.textAlign || 'left',
                                         fontFamily: element.fontFamily || 'sans-serif'
                                     }"
                                     class="h-full flex items-center px-1"
                                     x-text="element.content || 'Text'"></div>
                            </template>

                            {{-- Barcode Element --}}
                            <template x-if="element.type === 'barcode'">
                                <div class="h-full flex items-center justify-center">
                                    <svg :id="'barcode-' + element.id" class="max-w-full max-h-full"></svg>
                                </div>
                            </template>

                            {{-- QR Code Element --}}
                            <template x-if="element.type === 'qrcode'">
                                <div :id="'qrcode-' + element.id" class="h-full flex items-center justify-center"></div>
                            </template>

                            {{-- Image Element --}}
                            <template x-if="element.type === 'image'">
                                <img :src="element.src || 'https://via.placeholder.com/150'"
                                     class="w-full h-full object-contain">
                            </template>

                            {{-- Shape Element --}}
                            <template x-if="element.type === 'shape'">
                                <div :style="{
                                         backgroundColor: element.fillColor || 'transparent',
                                         border: (element.borderWidth || 1) + 'px solid ' + (element.borderColor || '#000000'),
                                         borderRadius: element.borderRadius ? (element.borderRadius * canvasScale) + 'px' : '0'
                                     }"
                                     class="w-full h-full"></div>
                            </template>

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
                </div>
            </div>
        </div>

        {{-- Right Sidebar: Properties --}}
        <div class="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 p-4 overflow-y-auto">
            <template x-if="selectedElement">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">
                        ⚙️ Properties
                    </h3>

                    <div class="space-y-4">
                        {{-- Label --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ชื่อ Element
                            </label>
                            <input type="text"
                                   x-model="selectedElement.label"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        {{-- Position & Size --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    X
                                </label>
                                <input type="number"
                                       x-model.number="selectedElement.x"
                                       step="0.1"
                                       class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Y
                                </label>
                                <input type="number"
                                       x-model.number="selectedElement.y"
                                       step="0.1"
                                       class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Width
                                </label>
                                <input type="number"
                                       x-model.number="selectedElement.width"
                                       step="0.1"
                                       min="1"
                                       class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Height
                                </label>
                                <input type="number"
                                       x-model.number="selectedElement.height"
                                       step="0.1"
                                       min="1"
                                       class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>
                        </div>

                        {{-- Text Properties --}}
                        <template x-if="selectedElement.type === 'text'">
                            <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        เนื้อหา
                                    </label>
                                    <textarea x-model="selectedElement.content"
                                              rows="3"
                                              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                                    <p class="text-xs text-gray-500 mt-1">
                                        ใช้ {{"{{"}}product_name{{"}}"}}, {{"{{"}}price{{"}}"}}, {{"{{"}}barcode{{"}}"}} สำหรับ placeholder
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Font Size
                                        </label>
                                        <input type="number"
                                               x-model.number="selectedElement.fontSize"
                                               min="6"
                                               max="72"
                                               class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Color
                                        </label>
                                        <input type="color"
                                               x-model="selectedElement.color"
                                               class="w-full h-8 border border-gray-300 dark:border-gray-600 rounded">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Font Weight
                                    </label>
                                    <select x-model="selectedElement.fontWeight"
                                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                        <option value="lighter">Light</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Text Align
                                    </label>
                                    <select x-model="selectedElement.textAlign"
                                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- Barcode Properties --}}
                        <template x-if="selectedElement.type === 'barcode'">
                            <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Barcode Content
                                    </label>
                                    <input type="text"
                                           x-model="selectedElement.content"
                                           @input="renderBarcode(selectedElement)"
                                           placeholder="{{"{{"}}barcode{{"}}"}}"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Barcode Format
                                    </label>
                                    <select x-model="selectedElement.format"
                                            @change="renderBarcode(selectedElement)"
                                            class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="CODE128">CODE128</option>
                                        <option value="EAN13">EAN13</option>
                                        <option value="UPC">UPC</option>
                                        <option value="CODE39">CODE39</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               x-model="selectedElement.showText"
                                               @change="renderBarcode(selectedElement)"
                                               class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                                        <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">
                                            แสดงข้อความใต้ barcode
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        {{-- QR Code Properties --}}
                        <template x-if="selectedElement.type === 'qrcode'">
                            <div class="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        QR Code Content
                                    </label>
                                    <textarea x-model="selectedElement.content"
                                              @input="renderQRCode(selectedElement)"
                                              rows="3"
                                              placeholder="{{"{{"}}product_url{{"}}"}}"
                                              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                                </div>
                            </div>
                        </template>

                        {{-- Delete Button --}}
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <button @click="deleteSelectedElement()"
                                    class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i>
                                ลบ Element
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!selectedElement">
                <div class="text-center py-12">
                    <i class="fas fa-hand-pointer text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        คลิกที่ element เพื่อแก้ไข properties
                    </p>
                </div>
            </template>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div x-show="showPreview"
         x-cloak
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
         @click.self="showPreview = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-auto">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    🔍 Preview
                </h3>
                <button @click="showPreview = false"
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-8">
                <div class="label-canvas mx-auto" style="transform: scale(2); transform-origin: top center;">
                    {{-- Preview content will be rendered here --}}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function labelDesigner() {
    return {
        // Template data
        template: @json($template),

        // Canvas
        canvasScale: 2, // Scale for better visibility

        // Elements
        elements: @json($template->elements ?? []),
        selectedElement: null,
        elementIdCounter: 0,

        // Drag & Drop
        isDragging: false,
        isResizing: false,
        resizeHandle: null,
        dragStart: { x: 0, y: 0 },
        elementStart: { x: 0, y: 0, width: 0, height: 0 },

        // UI
        showPreview: false,
        saving: false,

        init() {
            // Flatten elements array structure
            const flatElements = [];
            if (this.elements.text) flatElements.push(...this.elements.text.map(e => ({...e, type: 'text'})));
            if (this.elements.barcode) flatElements.push(...this.elements.barcode.map(e => ({...e, type: 'barcode'})));
            if (this.elements.qrcode) flatElements.push(...this.elements.qrcode.map(e => ({...e, type: 'qrcode'})));
            if (this.elements.image) flatElements.push(...this.elements.image.map(e => ({...e, type: 'image'})));
            if (this.elements.shape) flatElements.push(...this.elements.shape.map(e => ({...e, type: 'shape'})));

            this.elements = flatElements;
            this.elementIdCounter = this.elements.length;

            // Render all barcodes and QR codes after Alpine is ready
            this.$nextTick(() => {
                this.elements.forEach(element => {
                    if (element.type === 'barcode') this.renderBarcode(element);
                    if (element.type === 'qrcode') this.renderQRCode(element);
                });
            });

            // Add global mouse event listeners
            document.addEventListener('mousemove', (e) => {
                if (this.isDragging) this.drag(e);
                if (this.isResizing) this.resize(e);
            });

            document.addEventListener('mouseup', () => {
                this.isDragging = false;
                this.isResizing = false;
            });
        },

        addElement(type) {
            const element = {
                id: 'element-' + (++this.elementIdCounter),
                type: type,
                label: type.charAt(0).toUpperCase() + type.slice(1),
                x: 10,
                y: 10,
                width: type === 'text' ? 50 : 40,
                height: type === 'text' ? 10 : 40,
                zIndex: this.elements.length + 1
            };

            // Type-specific defaults
            if (type === 'text') {
                element.content = 'Sample Text';
                element.fontSize = 12;
                element.fontWeight = 'normal';
                element.color = '#000000';
                element.textAlign = 'left';
            } else if (type === 'barcode') {
                element.content = '{{barcode}}';
                element.format = 'CODE128';
                element.showText = true;
            } else if (type === 'qrcode') {
                element.content = '{{product_url}}';
            } else if (type === 'image') {
                element.src = '';
            } else if (type === 'shape') {
                element.fillColor = 'transparent';
                element.borderColor = '#000000';
                element.borderWidth = 2;
                element.borderRadius = 0;
            }

            this.elements.push(element);
            this.selectElement(element);

            // Render barcode/QR code after adding
            this.$nextTick(() => {
                if (type === 'barcode') this.renderBarcode(element);
                if (type === 'qrcode') this.renderQRCode(element);
            });
        },

        selectElement(element) {
            this.selectedElement = element;
        },

        deselectElement() {
            this.selectedElement = null;
        },

        deleteElement(element) {
            const index = this.elements.indexOf(element);
            if (index > -1) {
                this.elements.splice(index, 1);
                if (this.selectedElement?.id === element.id) {
                    this.selectedElement = null;
                }
            }
        },

        deleteSelectedElement() {
            if (this.selectedElement) {
                this.deleteElement(this.selectedElement);
            }
        },

        // Drag & Drop
        startDrag(event, element) {
            if (this.isResizing) return;

            this.isDragging = true;
            this.dragStart = { x: event.clientX, y: event.clientY };
            this.elementStart = { x: element.x, y: element.y };
            this.selectElement(element);
        },

        drag(event) {
            if (!this.isDragging || !this.selectedElement) return;

            const dx = (event.clientX - this.dragStart.x) / this.canvasScale;
            const dy = (event.clientY - this.dragStart.y) / this.canvasScale;

            this.selectedElement.x = Math.max(0, this.elementStart.x + dx);
            this.selectedElement.y = Math.max(0, this.elementStart.y + dy);
        },

        startResize(event, element, handle) {
            event.stopPropagation();
            this.isResizing = true;
            this.resizeHandle = handle;
            this.dragStart = { x: event.clientX, y: event.clientY };
            this.elementStart = {
                x: element.x,
                y: element.y,
                width: element.width,
                height: element.height
            };
            this.selectElement(element);
        },

        resize(event) {
            if (!this.isResizing || !this.selectedElement) return;

            const dx = (event.clientX - this.dragStart.x) / this.canvasScale;
            const dy = (event.clientY - this.dragStart.y) / this.canvasScale;

            switch (this.resizeHandle) {
                case 'se':
                    this.selectedElement.width = Math.max(10, this.elementStart.width + dx);
                    this.selectedElement.height = Math.max(10, this.elementStart.height + dy);
                    break;
                case 'sw':
                    this.selectedElement.width = Math.max(10, this.elementStart.width - dx);
                    this.selectedElement.height = Math.max(10, this.elementStart.height + dy);
                    this.selectedElement.x = this.elementStart.x + dx;
                    break;
                case 'ne':
                    this.selectedElement.width = Math.max(10, this.elementStart.width + dx);
                    this.selectedElement.height = Math.max(10, this.elementStart.height - dy);
                    this.selectedElement.y = this.elementStart.y + dy;
                    break;
                case 'nw':
                    this.selectedElement.width = Math.max(10, this.elementStart.width - dx);
                    this.selectedElement.height = Math.max(10, this.elementStart.height - dy);
                    this.selectedElement.x = this.elementStart.x + dx;
                    this.selectedElement.y = this.elementStart.y + dy;
                    break;
            }

            // Re-render barcode/QR code if resized
            if (this.selectedElement.type === 'barcode') {
                this.$nextTick(() => this.renderBarcode(this.selectedElement));
            }
            if (this.selectedElement.type === 'qrcode') {
                this.$nextTick(() => this.renderQRCode(this.selectedElement));
            }
        },

        // Barcode rendering
        renderBarcode(element) {
            this.$nextTick(() => {
                const svg = document.getElementById('barcode-' + element.id);
                if (svg && element.content) {
                    try {
                        JsBarcode(svg, element.content.replace(/[{}]/g, '') || '1234567890', {
                            format: element.format || 'CODE128',
                            width: 2,
                            height: 40,
                            displayValue: element.showText !== false,
                            fontSize: 12,
                            margin: 0
                        });
                    } catch (e) {
                        console.error('Barcode error:', e);
                    }
                }
            });
        },

        // QR Code rendering
        renderQRCode(element) {
            this.$nextTick(() => {
                const container = document.getElementById('qrcode-' + element.id);
                if (container && element.content) {
                    container.innerHTML = '';
                    try {
                        new QRCode(container, {
                            text: element.content.replace(/[{}]/g, '') || 'Sample QR',
                            width: element.width * this.canvasScale,
                            height: element.height * this.canvasScale,
                            colorDark: '#000000',
                            colorLight: '#ffffff'
                        });
                    } catch (e) {
                        console.error('QR Code error:', e);
                    }
                }
            });
        },

        // Save template
        async saveTemplate() {
            this.saving = true;

            // Organize elements by type
            const organizedElements = {
                text: [],
                barcode: [],
                qrcode: [],
                image: [],
                shape: []
            };

            this.elements.forEach(element => {
                const cleanElement = { ...element };
                delete cleanElement.type;
                organizedElements[element.type].push(cleanElement);
            });

            try {
                const response = await fetch('{{ route('seller.pos.labels.templates.update', $template) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        elements: organizedElements
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    alert('✅ บันทึก Template สำเร็จ!');
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Save error:', error);
                alert('❌ เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush
@endsection
