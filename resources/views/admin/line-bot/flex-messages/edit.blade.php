@extends('layouts.admin-v3')

@section('title', isset($template) ? 'Edit Flex Message' : 'Create Flex Message')

@push('styles')
@vite(['resources/css/app.css'])
<style>
/* LINE Simulator iPhone Mockup */
.iphone-mockup {
    background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
    border-radius: 3rem;
    padding: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.iphone-notch {
    width: 10rem;
    height: 1.75rem;
    background: #1a1a1a;
    border-bottom-left-radius: 1.5rem;
    border-bottom-right-radius: 1.5rem;
}

.iphone-screen {
    background: linear-gradient(to bottom, #f5f5f5, #e5e5e5);
    border-radius: 2.5rem;
    overflow: hidden;
}

/* Dark mode for iPhone screen */
.dark .iphone-screen {
    background: linear-gradient(to bottom, #1a1a1a, #0d0d0d);
}

/* JSON Editor syntax highlighting */
.json-editor {
    tab-size: 2;
    font-family: 'Fira Code', 'Courier New', monospace;
}

/* Component palette item hover */
.component-item {
    transition: all 0.2s ease;
}

.component-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(6, 199, 85, 0.1);
}

/* Alpine x-cloak */
[x-cloak] {
    display: none !important;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="flexMessageEditor()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.line-bot.flex.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-[#06C755] transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    @isset($template) Edit @else Create @endisset Flex Message Template
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Design beautiful interactive messages for LINE</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Step Indicator -->
    <div class="mb-8">
        <div class="flex items-center justify-center gap-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 rounded-full"
                     :class="step === 1 ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-500 dark:text-gray-400'">
                    <span class="font-bold">1</span>
                </div>
                <span class="ml-2 text-sm font-semibold" :class="step === 1 ? 'text-[#06C755]' : 'text-gray-500 dark:text-gray-400'">Template</span>
            </div>
            <div class="w-16 h-0.5 bg-gray-300 dark:bg-slate-700"></div>
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 rounded-full"
                     :class="step === 2 ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-500 dark:text-gray-400'">
                    <span class="font-bold">2</span>
                </div>
                <span class="ml-2 text-sm font-semibold" :class="step === 2 ? 'text-[#06C755]' : 'text-gray-500 dark:text-gray-400'">Edit</span>
            </div>
            <div class="w-16 h-0.5 bg-gray-300 dark:bg-slate-700"></div>
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 rounded-full"
                     :class="step === 3 ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-500 dark:text-gray-400'">
                    <span class="font-bold">3</span>
                </div>
                <span class="ml-2 text-sm font-semibold" :class="step === 3 ? 'text-[#06C755]' : 'text-gray-500 dark:text-gray-400'">Preview</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ isset($template) ? route('admin.line-bot.flex.update', $template->id) : route('admin.line-bot.flex.store') }}" @submit="onSubmit">
        @csrf
        @isset($template) @method('PUT') @endisset

        <!-- Step 1: Template Selection -->
        <div x-show="step === 1" x-cloak class="space-y-6">
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-star text-yellow-500 mr-2"></i>Choose a Template
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Start with a pre-designed template or create from scratch</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Blank Template -->
                    <button type="button" @click="selectTemplate('blank')"
                            :class="selectedTemplate === 'blank' ? 'ring-4 ring-[#06C755]' : ''"
                            class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 p-6 rounded-2xl border border-white/20 dark:border-slate-700/50 cursor-pointer hover:scale-105 transform transition-all">
                        <div class="aspect-[9/16] bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-900/50 dark:to-slate-800/50 rounded-xl mb-4 flex items-center justify-center">
                            <i class="fas fa-file-alt text-6xl text-gray-400 dark:text-gray-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Blank</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Start from scratch</p>
                    </button>

                    <!-- Bubble Template -->
                    <button type="button" @click="selectTemplate('bubble')"
                            :class="selectedTemplate === 'bubble' ? 'ring-4 ring-[#06C755]' : ''"
                            class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 p-6 rounded-2xl border border-white/20 dark:border-slate-700/50 cursor-pointer hover:scale-105 transform transition-all">
                        <div class="aspect-[9/16] bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl mb-4 overflow-hidden flex items-center justify-center p-4">
                            <div class="text-center w-full">
                                <div class="w-full h-24 bg-gray-300 dark:bg-gray-600 rounded-lg mb-3"></div>
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-400 dark:bg-gray-500 rounded w-3/4 mx-auto"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                                </div>
                                <div class="mt-3 h-8 bg-[#06C755] rounded-lg"></div>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Bubble</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Card with image & button</p>
                    </button>

                    <!-- Carousel Template -->
                    <button type="button" @click="selectTemplate('carousel')"
                            :class="selectedTemplate === 'carousel' ? 'ring-4 ring-[#06C755]' : ''"
                            class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 p-6 rounded-2xl border border-white/20 dark:border-slate-700/50 cursor-pointer hover:scale-105 transform transition-all">
                        <div class="aspect-[9/16] bg-gradient-to-br from-purple-100 to-pink-200 dark:from-purple-900/30 dark:to-pink-800/30 rounded-xl mb-4 overflow-hidden flex items-center justify-center">
                            <div class="flex gap-2 overflow-x-auto p-2">
                                <div class="flex-shrink-0 w-24 h-36 bg-gray-300 dark:bg-gray-600 rounded-lg"></div>
                                <div class="flex-shrink-0 w-24 h-36 bg-gray-300 dark:bg-gray-600 rounded-lg"></div>
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Carousel</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Multiple scrollable cards</p>
                    </button>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="step = 2; initializeTemplate()"
                            class="px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition font-bold">
                        Next <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Editor -->
        <div x-show="step === 2" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Editor Area (col-span-2) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Info -->
                    <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Basic Information</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Template Name *</label>
                                <input type="text" name="name" x-model="formData.name" required
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] transition-all">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Category</label>
                                <select name="category" x-model="formData.category"
                                        class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] transition-all">
                                    <option value="welcome">Welcome</option>
                                    <option value="promotion">Promotion</option>
                                    <option value="product">Product</option>
                                    <option value="notification">Notification</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                <textarea name="description" x-model="formData.description" rows="3"
                                          class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] transition-all"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Mode Toggle -->
                    <div class="flex items-center gap-4 mb-6">
                        <button type="button" @click="editorMode = 'json'"
                                :class="editorMode === 'json' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' : 'glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300'"
                                class="px-6 py-3 rounded-xl font-bold transition-all">
                            💻 JSON Editor
                        </button>
                        <div class="flex-1 text-center">
                            <span class="text-sm text-gray-500 dark:text-gray-400">or</span>
                        </div>
                        <a href="https://developers.line.biz/flex-simulator/" target="_blank"
                           class="px-6 py-3 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-all font-bold">
                            <i class="fas fa-external-link-alt mr-2"></i>LINE Simulator
                        </a>
                    </div>

                    <!-- JSON Editor Mode -->
                    <div x-show="editorMode === 'json'" class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Flex Message JSON</h3>
                            <div class="flex gap-2">
                                <button type="button" @click="validateJSON()"
                                        class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition text-sm font-semibold">
                                    <i class="fas fa-check-circle mr-1"></i>Validate
                                </button>
                                <button type="button" @click="formatJSON()"
                                        class="px-4 py-2 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-900/80 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition text-sm font-semibold">
                                    <i class="fas fa-magic mr-1"></i>Format
                                </button>
                            </div>
                        </div>

                        <textarea name="flex_content" id="flex-json" rows="20" x-model="flexJSON"
                                  @input="updatePreview()"
                            class="json-editor w-full px-4 py-3 bg-slate-900 dark:bg-slate-950 border border-slate-700 dark:border-slate-600 text-green-400 rounded-xl focus:ring-2 focus:ring-[#06C755] focus:border-[#06C755] font-mono text-sm transition-all"
                            placeholder='{"type": "bubble", "body": {...}}'
                            required></textarea>

                        <div x-show="validationError" class="mt-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
                            <p class="text-red-800 dark:text-red-200 text-sm" x-text="validationError"></p>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Paste your Flex Message JSON from the
                            <a href="https://developers.line.biz/flex-simulator/" target="_blank" class="text-[#06C755] hover:underline">LINE Flex Message Simulator</a>
                        </p>
                    </div>
                </div>

                <!-- LINE Simulator Preview (Sidebar) -->
                <div class="lg:col-span-1">
                    <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-6 sticky top-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-[#06C755]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                            Preview in LINE
                        </h3>

                        <!-- iPhone Mockup -->
                        <div class="mx-auto max-w-sm">
                            <div class="iphone-mockup">
                                <!-- Notch -->
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 iphone-notch z-10"></div>

                                <!-- Screen -->
                                <div class="iphone-screen">
                                    <!-- LINE Header -->
                                    <div class="bg-[#06C755] px-4 py-3 flex items-center gap-3">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 11l3 3L22 4"/>
                                        </svg>
                                        <span class="text-white font-bold">Bot Preview</span>
                                    </div>

                                    <!-- Chat Area -->
                                    <div class="p-4 min-h-[500px] bg-gradient-to-b from-gray-50 to-gray-100 dark:from-slate-900 dark:to-slate-800">
                                        <!-- Flex Message Preview -->
                                        <div class="max-w-xs">
                                            <div class="bg-white dark:bg-slate-700 rounded-2xl overflow-hidden shadow-lg"
                                                 x-html="renderFlexPreview()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-8 flex justify-between">
                <button type="button" @click="step = 1"
                        class="px-6 py-3 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 transition font-bold">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </button>
                <button type="button" @click="step = 3"
                        class="px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition font-bold">
                    Next <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Final Preview & Submit -->
        <div x-show="step === 3" x-cloak>
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                    <i class="fas fa-check-circle text-[#06C755] mr-2"></i>Review & Save
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Template Info -->
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Template Details</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Name</dt>
                                <dd class="text-base text-gray-900 dark:text-white" x-text="formData.name || 'Untitled'"></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Category</dt>
                                <dd class="text-base text-gray-900 dark:text-white" x-text="formData.category"></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Description</dt>
                                <dd class="text-base text-gray-900 dark:text-white" x-text="formData.description || 'No description'"></dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Final Preview -->
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Final Preview</h4>
                        <div class="bg-gradient-to-b from-gray-50 to-gray-100 dark:from-slate-900 dark:to-slate-800 rounded-xl p-4">
                            <div class="bg-white dark:bg-slate-700 rounded-xl overflow-hidden shadow-lg max-w-xs mx-auto"
                                 x-html="renderFlexPreview()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="mt-8 flex items-center justify-between pt-6 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" @click="step = 2"
                            class="px-6 py-3 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 transition font-bold">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Edit
                    </button>
                    <div class="flex gap-3">
                        <a href="{{ route('admin.line-bot.flex.index') }}"
                           class="px-6 py-3 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-700 transition font-bold">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-8 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition font-bold">
                            <i class="fas fa-save mr-2"></i>
                            @isset($template) Update @else Create @endisset Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
@vite(['resources/js/app.js'])
<script>
/**
 * Alpine.js component สำหรับ Flex Message Editor
 */
function flexMessageEditor() {
    return {
        // State
        step: @isset($template) 2 @else 1 @endisset,
        selectedTemplate: 'blank',
        editorMode: 'json',
        formData: {
            name: @json(old('name', $template->name ?? '')),
            category: @json(old('category', $template->category ?? 'welcome')),
            description: @json(old('description', $template->description ?? ''))
        },
        flexJSON: @json(old('flex_content', isset($template) ? json_encode($template->flex_content, JSON_PRETTY_PRINT) : '')),
        validationError: '',

        /**
         * Initialize
         */
        init() {
            @isset($template)
            this.selectedTemplate = 'custom';
            @endisset
        },

        /**
         * เลือก Template
         */
        selectTemplate(type) {
            this.selectedTemplate = type;
        },

        /**
         * Initialize Template JSON
         */
        initializeTemplate() {
            if (this.flexJSON) return; // Already has content

            const templates = {
                blank: {
                    "type": "bubble",
                    "body": {
                        "type": "box",
                        "layout": "vertical",
                        "contents": [
                            {
                                "type": "text",
                                "text": "Hello, World!",
                                "weight": "bold",
                                "size": "xl"
                            }
                        ]
                    }
                },
                bubble: {
                    "type": "bubble",
                    "hero": {
                        "type": "image",
                        "url": "https://via.placeholder.com/1024x1024",
                        "size": "full",
                        "aspectRatio": "20:13",
                        "aspectMode": "cover"
                    },
                    "body": {
                        "type": "box",
                        "layout": "vertical",
                        "contents": [
                            {
                                "type": "text",
                                "text": "Product Title",
                                "weight": "bold",
                                "size": "xl"
                            },
                            {
                                "type": "text",
                                "text": "Product description goes here",
                                "size": "sm",
                                "color": "#999999",
                                "margin": "md"
                            },
                            {
                                "type": "text",
                                "text": "$99.99",
                                "size": "xxl",
                                "weight": "bold",
                                "color": "#06C755",
                                "margin": "md"
                            }
                        ]
                    },
                    "footer": {
                        "type": "box",
                        "layout": "vertical",
                        "contents": [
                            {
                                "type": "button",
                                "action": {
                                    "type": "uri",
                                    "label": "Buy Now",
                                    "uri": "https://example.com"
                                },
                                "style": "primary",
                                "color": "#06C755"
                            }
                        ]
                    }
                },
                carousel: {
                    "type": "carousel",
                    "contents": [
                        {
                            "type": "bubble",
                            "hero": {
                                "type": "image",
                                "url": "https://via.placeholder.com/1024x1024",
                                "size": "full",
                                "aspectRatio": "20:13",
                                "aspectMode": "cover"
                            },
                            "body": {
                                "type": "box",
                                "layout": "vertical",
                                "contents": [
                                    {
                                        "type": "text",
                                        "text": "Item 1",
                                        "weight": "bold",
                                        "size": "xl"
                                    }
                                ]
                            }
                        },
                        {
                            "type": "bubble",
                            "hero": {
                                "type": "image",
                                "url": "https://via.placeholder.com/1024x1024",
                                "size": "full",
                                "aspectRatio": "20:13",
                                "aspectMode": "cover"
                            },
                            "body": {
                                "type": "box",
                                "layout": "vertical",
                                "contents": [
                                    {
                                        "type": "text",
                                        "text": "Item 2",
                                        "weight": "bold",
                                        "size": "xl"
                                    }
                                ]
                            }
                        }
                    ]
                }
            };

            this.flexJSON = JSON.stringify(templates[this.selectedTemplate] || templates.blank, null, 2);
        },

        /**
         * Validate JSON
         */
        validateJSON() {
            try {
                JSON.parse(this.flexJSON);
                this.validationError = '';
                alert('✅ JSON is valid!');
                return true;
            } catch (e) {
                this.validationError = `❌ Invalid JSON: ${e.message}`;
                return false;
            }
        },

        /**
         * Format JSON
         */
        formatJSON() {
            try {
                const parsed = JSON.parse(this.flexJSON);
                this.flexJSON = JSON.stringify(parsed, null, 2);
                this.validationError = '';
            } catch (e) {
                this.validationError = `Cannot format invalid JSON: ${e.message}`;
            }
        },

        /**
         * Update Preview
         */
        updatePreview() {
            // Debounce preview update
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(() => {
                this.$nextTick(() => {
                    // Preview will auto-update via renderFlexPreview()
                });
            }, 300);
        },

        /**
         * Render Flex Message Preview
         */
        renderFlexPreview() {
            if (!this.flexJSON) {
                return '<p class="p-4 text-gray-500 dark:text-gray-400 text-center">No preview available</p>';
            }

            try {
                const json = JSON.parse(this.flexJSON);
                this.validationError = '';
                return this.renderBubble(json);
            } catch (e) {
                return `<p class="p-4 text-red-500 dark:text-red-400 text-sm text-center">Invalid JSON: ${e.message}</p>`;
            }
        },

        /**
         * Render Bubble (simplified)
         */
        renderBubble(json) {
            if (!json || !json.type) return '<p class="p-4 text-gray-500">Invalid template</p>';

            if (json.type === 'carousel') {
                // Render first bubble of carousel only (for preview)
                if (json.contents && json.contents[0]) {
                    return this.renderSingleBubble(json.contents[0]);
                }
                return '<p class="p-4 text-gray-500">Empty carousel</p>';
            }

            return this.renderSingleBubble(json);
        },

        /**
         * Render Single Bubble
         */
        renderSingleBubble(bubble) {
            let html = '<div class="w-full">';

            // Hero image
            if (bubble.hero && bubble.hero.url) {
                html += `<img src="${bubble.hero.url}" class="w-full h-40 object-cover" onerror="this.src='https://via.placeholder.com/400x200'" />`;
            }

            // Body
            if (bubble.body && bubble.body.contents) {
                html += '<div class="p-4">';
                bubble.body.contents.forEach(item => {
                    html += this.renderComponent(item);
                });
                html += '</div>';
            }

            // Footer buttons
            if (bubble.footer && bubble.footer.contents) {
                html += '<div class="p-4 border-t border-gray-200 dark:border-slate-600">';
                bubble.footer.contents.forEach(item => {
                    html += this.renderComponent(item);
                });
                html += '</div>';
            }

            html += '</div>';
            return html;
        },

        /**
         * Render Component (simplified)
         */
        renderComponent(item) {
            if (!item || !item.type) return '';

            switch (item.type) {
                case 'text':
                    const size = {
                        'xxs': 'text-xs',
                        'xs': 'text-xs',
                        'sm': 'text-sm',
                        'md': 'text-base',
                        'lg': 'text-lg',
                        'xl': 'text-xl',
                        'xxl': 'text-2xl',
                        '3xl': 'text-3xl',
                        '4xl': 'text-4xl',
                        '5xl': 'text-5xl'
                    }[item.size || 'md'] || 'text-base';

                    const weight = item.weight === 'bold' ? 'font-bold' : '';
                    const color = item.color || '#000000';
                    const margin = item.margin ? `mt-${item.margin === 'md' ? '2' : item.margin === 'lg' ? '4' : '1'}` : '';

                    return `<p class="${size} ${weight} ${margin} dark:text-white" style="color: ${color}">${item.text || ''}</p>`;

                case 'button':
                    const btnColor = item.color || '#06C755';
                    const btnStyle = item.style === 'primary' ? `background: ${btnColor}` : `border: 1px solid ${btnColor}; color: ${btnColor}`;
                    const btnLabel = item.action?.label || 'Button';

                    return `<button class="w-full py-3 rounded-lg text-white font-bold mt-2" style="${btnStyle}">${btnLabel}</button>`;

                case 'separator':
                    return '<hr class="my-3 border-gray-200 dark:border-slate-600" />';

                case 'box':
                    if (item.contents && Array.isArray(item.contents)) {
                        let boxHtml = '<div class="flex flex-col gap-1">';
                        item.contents.forEach(child => {
                            boxHtml += this.renderComponent(child);
                        });
                        boxHtml += '</div>';
                        return boxHtml;
                    }
                    return '';

                default:
                    return '';
            }
        },

        /**
         * Form Submit Handler
         */
        onSubmit(e) {
            if (!this.validateJSON()) {
                e.preventDefault();
                alert('❌ Please fix JSON errors before submitting');
                this.step = 2;
                return false;
            }
        }
    };
}
</script>
@endpush
@endsection
