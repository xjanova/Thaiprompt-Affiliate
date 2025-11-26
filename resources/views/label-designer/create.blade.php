{{--
/**
 * หน้าสร้างฉลากใหม่ - Label Designer Canvas
 *
 * เครื่องมือออกแบบฉลากแบบ Drag & Drop:
 * - Canvas สำหรับออกแบบ
 * - Toolbox สำหรับเพิ่มองค์ประกอบ
 * - Panel ตั้งค่าขนาดกระดาษ
 * - Preview ก่อนพิมพ์
 *
 * @author Claude AI
 */
--}}

@extends('layouts.app')

@section('title', $pageTitle ?? 'สร้างฉลากใหม่')

@section('content')
<div x-data="labelDesignerCanvas()" x-init="init()" class="min-h-screen bg-gray-100 dark:bg-gray-900">
    {{-- Top Toolbar --}}
    <div class="sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                {{-- Left: Back & Title --}}
                <div class="flex items-center gap-4">
                    <a href="{{ route('label-designer.index') }}"
                       class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <input type="text" x-model="labelName"
                               class="text-lg font-bold bg-transparent border-none focus:ring-0 text-gray-900 dark:text-white px-0"
                               placeholder="ชื่อฉลาก...">
                    </div>
                </div>

                {{-- Center: Quick Tools --}}
                <div class="hidden md:flex items-center gap-2">
                    <button @click="undo()" :disabled="!canUndo"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition disabled:opacity-50"
                            title="ย้อนกลับ (Ctrl+Z)">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </button>
                    <button @click="redo()" :disabled="!canRedo"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition disabled:opacity-50"
                            title="ทำซ้ำ (Ctrl+Y)">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"></path>
                        </svg>
                    </button>

                    <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-2"></div>

                    <button @click="zoomOut()"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                            title="ย่อ">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                        </svg>
                    </button>
                    <span class="text-sm text-gray-600 dark:text-gray-400 w-14 text-center" x-text="Math.round(zoom * 100) + '%'"></span>
                    <button @click="zoomIn()"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                            title="ขยาย">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                        </svg>
                    </button>
                    <button @click="resetZoom()"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                            title="Reset Zoom">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>

                {{-- Right: Actions --}}
                <div class="flex items-center gap-2">
                    <button @click="showPreview = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span class="hidden sm:inline">Preview</span>
                    </button>
                    <button @click="print()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span class="hidden sm:inline">พิมพ์</span>
                    </button>
                    @auth
                    <button @click="save()"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50">
                        <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="hidden sm:inline" x-text="saving ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                    </button>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Main Layout --}}
    <div class="flex h-[calc(100vh-64px)]">
        {{-- Left Sidebar: Toolbox --}}
        <div class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto hidden md:block">
            <div class="p-4">
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                    เพิ่มองค์ประกอบ
                </h3>

                <div class="space-y-2">
                    {{-- Text Element --}}
                    <button @click="addElement('text')"
                            class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition group">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-900 dark:text-white text-sm">ข้อความ</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">เพิ่มข้อความลงบนฉลาก</p>
                        </div>
                    </button>

                    {{-- Barcode Element --}}
                    <button @click="addElement('barcode')"
                            class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition group">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-900 dark:text-white text-sm">บาร์โค้ด</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">CODE128, EAN13, UPC-A</p>
                        </div>
                    </button>

                    {{-- QR Code Element --}}
                    <button @click="addElement('qrcode')"
                            class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition group">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 group-hover:scale-110 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-900 dark:text-white text-sm">QR Code</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ลิงก์, ข้อความ, WiFi</p>
                        </div>
                    </button>

                    {{-- Image Element --}}
                    <button @click="addElement('image')"
                            class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition group">
                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/50 rounded-lg flex items-center justify-center text-orange-600 dark:text-orange-400 group-hover:scale-110 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-900 dark:text-white text-sm">รูปภาพ</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">โลโก้, ภาพสินค้า</p>
                        </div>
                    </button>

                    {{-- Shape Element --}}
                    <button @click="addElement('shape')"
                            class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition group">
                        <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/50 rounded-lg flex items-center justify-center text-pink-600 dark:text-pink-400 group-hover:scale-110 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-900 dark:text-white text-sm">รูปทรง</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">เส้น, สี่เหลี่ยม, วงกลม</p>
                        </div>
                    </button>
                </div>

                {{-- Paper Size Settings --}}
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-8 mb-4">
                    ขนาดกระดาษ
                </h3>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">กว้าง (mm)</label>
                            <input type="number" x-model.number="paper.width" min="10" max="500"
                                   class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">สูง (mm)</label>
                            <input type="number" x-model.number="paper.height" min="10" max="500"
                                   class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-500 dark:text-gray-400">ขนาดมาตรฐาน</label>
                        <select x-model="selectedPaperSize" @change="applyPaperSize()"
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                            <option value="">กำหนดเอง</option>
                            @foreach($paperSizes as $size)
                                <option value="{{ $size->id }}" data-width="{{ $size->width }}" data-height="{{ $size->height }}">
                                    {{ $size->name_th ?? $size->name }} ({{ $size->width }}x{{ $size->height }}mm)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Grid Settings --}}
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-8 mb-4">
                    ตั้งค่า Canvas
                </h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="settings.showGrid"
                               class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">แสดงเส้น Grid</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="settings.snapToGrid"
                               class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Snap to Grid</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Center: Canvas Area --}}
        <div class="flex-1 overflow-auto p-8 bg-gray-200 dark:bg-gray-900">
            <div class="flex items-center justify-center min-h-full">
                {{-- Canvas Container --}}
                <div class="relative bg-white shadow-2xl"
                     :style="{
                         width: (paper.width * 3.78 * zoom) + 'px',
                         height: (paper.height * 3.78 * zoom) + 'px',
                         backgroundSize: settings.showGrid ? (settings.gridSize * 3.78 * zoom) + 'px ' + (settings.gridSize * 3.78 * zoom) + 'px' : 'none',
                         backgroundImage: settings.showGrid ? 'linear-gradient(to right, #e5e7eb 1px, transparent 1px), linear-gradient(to bottom, #e5e7eb 1px, transparent 1px)' : 'none'
                     }"
                     @click="selectElement(null)"
                     id="label-canvas">

                    {{-- Elements --}}
                    <template x-for="(element, index) in elements" :key="element.id">
                        <div class="absolute cursor-move border-2"
                             :class="selectedElement?.id === element.id ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-transparent hover:border-gray-300'"
                             :style="{
                                 left: (element.x * 3.78 * zoom) + 'px',
                                 top: (element.y * 3.78 * zoom) + 'px',
                                 width: (element.width * 3.78 * zoom) + 'px',
                                 height: (element.height * 3.78 * zoom) + 'px'
                             }"
                             @click.stop="selectElement(element)"
                             @mousedown.prevent="startDrag($event, element)">

                            {{-- Text Element --}}
                            <template x-if="element.type === 'text'">
                                <div class="w-full h-full flex items-center overflow-hidden"
                                     :style="{
                                         fontSize: (element.fontSize * zoom) + 'px',
                                         fontWeight: element.fontWeight || 'normal',
                                         textAlign: element.textAlign || 'left',
                                         color: element.color || '#000000'
                                     }"
                                     x-text="element.content || 'ข้อความ'">
                                </div>
                            </template>

                            {{-- Barcode Element --}}
                            <template x-if="element.type === 'barcode'">
                                <div class="w-full h-full flex flex-col items-center justify-center bg-gray-100 border border-dashed border-gray-300">
                                    <svg class="w-full h-2/3" viewBox="0 0 100 50">
                                        <rect x="5" y="5" width="3" height="40" fill="black"/>
                                        <rect x="10" y="5" width="1" height="40" fill="black"/>
                                        <rect x="14" y="5" width="2" height="40" fill="black"/>
                                        <rect x="20" y="5" width="3" height="40" fill="black"/>
                                        <rect x="26" y="5" width="1" height="40" fill="black"/>
                                        <rect x="30" y="5" width="4" height="40" fill="black"/>
                                        <rect x="38" y="5" width="2" height="40" fill="black"/>
                                        <rect x="44" y="5" width="1" height="40" fill="black"/>
                                        <rect x="48" y="5" width="3" height="40" fill="black"/>
                                        <rect x="54" y="5" width="2" height="40" fill="black"/>
                                        <rect x="60" y="5" width="1" height="40" fill="black"/>
                                        <rect x="65" y="5" width="3" height="40" fill="black"/>
                                        <rect x="72" y="5" width="2" height="40" fill="black"/>
                                        <rect x="78" y="5" width="1" height="40" fill="black"/>
                                        <rect x="83" y="5" width="4" height="40" fill="black"/>
                                        <rect x="92" y="5" width="3" height="40" fill="black"/>
                                    </svg>
                                    <span class="text-xs text-gray-500 mt-1" x-text="element.content || 'BARCODE'"></span>
                                </div>
                            </template>

                            {{-- QR Code Element --}}
                            <template x-if="element.type === 'qrcode'">
                                <div class="w-full h-full flex items-center justify-center bg-gray-100 border border-dashed border-gray-300">
                                    <svg class="w-4/5 h-4/5" viewBox="0 0 100 100">
                                        <rect x="0" y="0" width="30" height="30" fill="black"/>
                                        <rect x="5" y="5" width="20" height="20" fill="white"/>
                                        <rect x="10" y="10" width="10" height="10" fill="black"/>
                                        <rect x="70" y="0" width="30" height="30" fill="black"/>
                                        <rect x="75" y="5" width="20" height="20" fill="white"/>
                                        <rect x="80" y="10" width="10" height="10" fill="black"/>
                                        <rect x="0" y="70" width="30" height="30" fill="black"/>
                                        <rect x="5" y="75" width="20" height="20" fill="white"/>
                                        <rect x="10" y="80" width="10" height="10" fill="black"/>
                                        <rect x="35" y="35" width="30" height="30" fill="black" opacity="0.3"/>
                                        <rect x="35" y="0" width="5" height="5" fill="black"/>
                                        <rect x="45" y="10" width="10" height="5" fill="black"/>
                                        <rect x="35" y="20" width="5" height="10" fill="black"/>
                                    </svg>
                                </div>
                            </template>

                            {{-- Image Element --}}
                            <template x-if="element.type === 'image'">
                                <div class="w-full h-full flex items-center justify-center bg-gray-100 border border-dashed border-gray-300">
                                    <template x-if="element.src">
                                        <img :src="element.src" class="max-w-full max-h-full object-contain">
                                    </template>
                                    <template x-if="!element.src">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </template>
                                </div>
                            </template>

                            {{-- Shape Element --}}
                            <template x-if="element.type === 'shape'">
                                <div class="w-full h-full"
                                     :style="{
                                         backgroundColor: element.shapeType === 'rectangle' ? (element.fillColor || '#e5e7eb') : 'transparent',
                                         borderRadius: element.shapeType === 'circle' ? '50%' : '0',
                                         border: element.shapeType === 'line' ? 'none' : '1px solid ' + (element.strokeColor || '#000')
                                     }">
                                    <template x-if="element.shapeType === 'line'">
                                        <div class="w-full h-0 absolute top-1/2"
                                             :style="{ borderTop: (element.strokeWidth || 1) + 'px solid ' + (element.strokeColor || '#000') }"></div>
                                    </template>
                                </div>
                            </template>

                            {{-- Resize Handle --}}
                            <div x-show="selectedElement?.id === element.id"
                                 class="absolute -right-2 -bottom-2 w-4 h-4 bg-indigo-500 rounded-full cursor-se-resize"
                                 @mousedown.stop.prevent="startResize($event, element)">
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Right Sidebar: Properties --}}
        <div class="w-72 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 overflow-y-auto hidden lg:block">
            <div class="p-4">
                <template x-if="selectedElement">
                    <div>
                        <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                            คุณสมบัติ
                        </h3>

                        {{-- Position --}}
                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">X (mm)</label>
                                <input type="number" x-model.number="selectedElement.x" step="0.5"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">Y (mm)</label>
                                <input type="number" x-model.number="selectedElement.y" step="0.5"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                            </div>
                        </div>

                        {{-- Size --}}
                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">กว้าง (mm)</label>
                                <input type="number" x-model.number="selectedElement.width" step="0.5" min="1"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">สูง (mm)</label>
                                <input type="number" x-model.number="selectedElement.height" step="0.5" min="1"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                            </div>
                        </div>

                        {{-- Text Properties --}}
                        <template x-if="selectedElement.type === 'text'">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">ข้อความ</label>
                                    <textarea x-model="selectedElement.content" rows="2"
                                              class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm"></textarea>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">ขนาดตัวอักษร (pt)</label>
                                    <input type="number" x-model.number="selectedElement.fontSize" min="6" max="72"
                                           class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">น้ำหนัก</label>
                                    <select x-model="selectedElement.fontWeight"
                                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                                        <option value="normal">ปกติ</option>
                                        <option value="bold">ตัวหนา</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">จัดตำแหน่ง</label>
                                    <div class="flex gap-1">
                                        <button @click="selectedElement.textAlign = 'left'"
                                                :class="selectedElement.textAlign === 'left' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600' : 'bg-gray-100 dark:bg-gray-700'"
                                                class="flex-1 p-2 rounded-lg">
                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"></path>
                                            </svg>
                                        </button>
                                        <button @click="selectedElement.textAlign = 'center'"
                                                :class="selectedElement.textAlign === 'center' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600' : 'bg-gray-100 dark:bg-gray-700'"
                                                class="flex-1 p-2 rounded-lg">
                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M5 18h14"></path>
                                            </svg>
                                        </button>
                                        <button @click="selectedElement.textAlign = 'right'"
                                                :class="selectedElement.textAlign === 'right' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600' : 'bg-gray-100 dark:bg-gray-700'"
                                                class="flex-1 p-2 rounded-lg">
                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">สี</label>
                                    <input type="color" x-model="selectedElement.color"
                                           class="w-full h-10 rounded-lg cursor-pointer">
                                </div>
                            </div>
                        </template>

                        {{-- Barcode Properties --}}
                        <template x-if="selectedElement.type === 'barcode'">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">เนื้อหา</label>
                                    <input type="text" x-model="selectedElement.content"
                                           class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm"
                                           placeholder="1234567890">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">รูปแบบ</label>
                                    <select x-model="selectedElement.format"
                                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                                        <option value="CODE128">CODE128</option>
                                        <option value="CODE39">CODE39</option>
                                        <option value="EAN13">EAN-13</option>
                                        <option value="EAN8">EAN-8</option>
                                        <option value="UPC-A">UPC-A</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="selectedElement.showText"
                                           class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">แสดงตัวเลข</span>
                                </label>
                            </div>
                        </template>

                        {{-- QR Code Properties --}}
                        <template x-if="selectedElement.type === 'qrcode'">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">เนื้อหา / URL</label>
                                    <textarea x-model="selectedElement.content" rows="3"
                                              class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm"
                                              placeholder="https://example.com"></textarea>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">ระดับ Error Correction</label>
                                    <select x-model="selectedElement.errorCorrectionLevel"
                                            class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm">
                                        <option value="L">L (7%)</option>
                                        <option value="M">M (15%)</option>
                                        <option value="Q">Q (25%)</option>
                                        <option value="H">H (30%)</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- Delete Button --}}
                        <button @click="deleteElement(selectedElement)"
                                class="w-full mt-6 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg font-medium hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            ลบองค์ประกอบ
                        </button>
                    </div>
                </template>

                <template x-if="!selectedElement">
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                        <p>คลิกที่องค์ประกอบเพื่อแก้ไขคุณสมบัติ</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div x-show="showPreview"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Preview ฉลาก</h3>
                <button @click="showPreview = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-8 bg-gray-100 dark:bg-gray-900 overflow-auto" style="max-height: calc(90vh - 140px);">
                <div class="flex items-center justify-center">
                    <div class="bg-white shadow-lg"
                         :style="{
                             width: (paper.width * 3.78) + 'px',
                             height: (paper.height * 3.78) + 'px'
                         }"
                         id="preview-canvas">
                        {{-- Preview content rendered by JS --}}
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button @click="showPreview = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    ปิด
                </button>
                <button @click="print()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    พิมพ์
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Label Designer Canvas - Alpine.js Component
 *
 * ระบบออกแบบฉลากแบบ Drag & Drop
 */
function labelDesignerCanvas() {
    return {
        // State
        labelName: 'ฉลากใหม่',
        paper: {
            width: {{ $template->paper_width ?? request('width', 50) }},
            height: {{ $template->paper_height ?? request('height', 30) }}
        },
        zoom: 1,
        elements: [],
        selectedElement: null,
        selectedPaperSize: '',
        settings: {
            showGrid: true,
            snapToGrid: true,
            gridSize: 5
        },
        showPreview: false,
        saving: false,
        canUndo: false,
        canRedo: false,
        history: [],
        historyIndex: -1,

        // Drag state
        dragging: false,
        dragStartX: 0,
        dragStartY: 0,
        dragElement: null,

        /**
         * เริ่มต้น component
         */
        init() {
            @if($template)
            // โหลด elements จาก template
            this.elements = @json($template->elements ?? []);
            if (Array.isArray(this.elements)) {
                this.elements = [];
            } else {
                // Flatten elements object
                const flatElements = [];
                Object.keys(this.elements).forEach(type => {
                    if (Array.isArray(this.elements[type])) {
                        this.elements[type].forEach(el => {
                            flatElements.push({...el, type});
                        });
                    }
                });
                this.elements = flatElements;
            }
            this.labelName = '{{ $template->name }} (สำเนา)';
            @endif

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Delete' && this.selectedElement) {
                    this.deleteElement(this.selectedElement);
                }
                if (e.ctrlKey && e.key === 'z') {
                    this.undo();
                }
                if (e.ctrlKey && e.key === 'y') {
                    this.redo();
                }
            });

            // Mouse events for drag
            document.addEventListener('mousemove', this.onDrag.bind(this));
            document.addEventListener('mouseup', this.endDrag.bind(this));
        },

        /**
         * เพิ่มองค์ประกอบใหม่
         */
        addElement(type) {
            const id = 'el_' + Date.now();
            const element = {
                id,
                type,
                x: 5,
                y: 5,
                width: type === 'text' ? 40 : 20,
                height: type === 'text' ? 8 : 20
            };

            // ค่าเริ่มต้นตาม type
            switch(type) {
                case 'text':
                    element.content = 'ข้อความ';
                    element.fontSize = 10;
                    element.fontWeight = 'normal';
                    element.textAlign = 'left';
                    element.color = '#000000';
                    break;
                case 'barcode':
                    element.content = '1234567890';
                    element.format = 'CODE128';
                    element.showText = true;
                    element.width = 40;
                    element.height = 15;
                    break;
                case 'qrcode':
                    element.content = 'https://example.com';
                    element.errorCorrectionLevel = 'M';
                    break;
                case 'image':
                    element.src = '';
                    break;
                case 'shape':
                    element.shapeType = 'rectangle';
                    element.fillColor = '#e5e7eb';
                    element.strokeColor = '#000000';
                    element.strokeWidth = 1;
                    break;
            }

            this.elements.push(element);
            this.selectElement(element);
            this.saveHistory();
        },

        /**
         * เลือกองค์ประกอบ
         */
        selectElement(element) {
            this.selectedElement = element;
        },

        /**
         * ลบองค์ประกอบ
         */
        deleteElement(element) {
            const index = this.elements.findIndex(e => e.id === element.id);
            if (index > -1) {
                this.elements.splice(index, 1);
                this.selectedElement = null;
                this.saveHistory();
            }
        },

        /**
         * เริ่ม drag
         */
        startDrag(event, element) {
            this.dragging = true;
            this.dragElement = element;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.selectElement(element);
        },

        /**
         * กำลัง drag
         */
        onDrag(event) {
            if (!this.dragging || !this.dragElement) return;

            const dx = (event.clientX - this.dragStartX) / (3.78 * this.zoom);
            const dy = (event.clientY - this.dragStartY) / (3.78 * this.zoom);

            let newX = this.dragElement.x + dx;
            let newY = this.dragElement.y + dy;

            // Snap to grid
            if (this.settings.snapToGrid) {
                newX = Math.round(newX / this.settings.gridSize) * this.settings.gridSize;
                newY = Math.round(newY / this.settings.gridSize) * this.settings.gridSize;
            }

            // Bound to canvas
            newX = Math.max(0, Math.min(this.paper.width - this.dragElement.width, newX));
            newY = Math.max(0, Math.min(this.paper.height - this.dragElement.height, newY));

            this.dragElement.x = newX;
            this.dragElement.y = newY;

            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
        },

        /**
         * จบ drag
         */
        endDrag() {
            if (this.dragging) {
                this.saveHistory();
            }
            this.dragging = false;
            this.dragElement = null;
        },

        /**
         * เริ่ม resize
         */
        startResize(event, element) {
            // TODO: Implement resize
        },

        /**
         * Zoom controls
         */
        zoomIn() {
            this.zoom = Math.min(3, this.zoom + 0.25);
        },
        zoomOut() {
            this.zoom = Math.max(0.25, this.zoom - 0.25);
        },
        resetZoom() {
            this.zoom = 1;
        },

        /**
         * ใช้ขนาดกระดาษมาตรฐาน
         */
        applyPaperSize() {
            const select = document.querySelector('select[x-model="selectedPaperSize"]');
            const option = select.options[select.selectedIndex];
            if (option && option.dataset.width) {
                this.paper.width = parseFloat(option.dataset.width);
                this.paper.height = parseFloat(option.dataset.height);
            }
        },

        /**
         * บันทึก history
         */
        saveHistory() {
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(JSON.stringify(this.elements));
            this.historyIndex = this.history.length - 1;
            this.canUndo = this.historyIndex > 0;
            this.canRedo = false;
        },

        /**
         * Undo
         */
        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.elements = JSON.parse(this.history[this.historyIndex]);
                this.selectedElement = null;
                this.canUndo = this.historyIndex > 0;
                this.canRedo = true;
            }
        },

        /**
         * Redo
         */
        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.elements = JSON.parse(this.history[this.historyIndex]);
                this.selectedElement = null;
                this.canUndo = true;
                this.canRedo = this.historyIndex < this.history.length - 1;
            }
        },

        /**
         * พิมพ์
         */
        print() {
            window.print();
        },

        /**
         * บันทึก
         */
        async save() {
            this.saving = true;

            // จัดกลุ่ม elements ตาม type
            const groupedElements = {
                text: [],
                barcode: [],
                qrcode: [],
                image: [],
                shape: []
            };
            this.elements.forEach(el => {
                if (groupedElements[el.type]) {
                    groupedElements[el.type].push(el);
                }
            });

            try {
                const response = await fetch('{{ route("label-designer.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.labelName,
                        width: this.paper.width,
                        height: this.paper.height,
                        unit: 'mm',
                        elements: groupedElements,
                        settings: this.settings
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('บันทึกสำเร็จ!');
                    // Redirect to edit page
                    window.location.href = '/label-designer/edit/' + data.data.id;
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                console.error('Error saving:', error);
                alert('เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endpush

@push('styles')
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #label-canvas, #label-canvas * {
        visibility: visible;
    }
    #label-canvas {
        position: absolute;
        left: 0;
        top: 0;
    }
}
</style>
@endpush
@endsection
