@extends('layouts.admin')

@section('title', 'แก้ไข: ' . $sectionData['name'])

@push('styles')
<!-- TinyMCE CSS -->
<style>
    .split-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        height: calc(100vh - 200px);
    }
    @media (max-width: 1024px) {
        .split-container {
            grid-template-columns: 1fr;
            height: auto;
        }
    }
    .settings-panel {
        overflow-y: auto;
        padding-right: 1rem;
    }
    .preview-panel {
        position: sticky;
        top: 1rem;
        height: fit-content;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
        background: #f9fafb;
        border-radius: 12px;
        padding: 2rem;
        border: 2px solid #e5e7eb;
    }
    .dark .preview-panel {
        background: #0f172a;
        border-color: #334155;
    }
    .form-control {
        transition: all 0.2s;
    }
    .form-control:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
    }
    .section-preview {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .dark .section-preview {
        background: #1e293b;
        box-shadow: 0 1px 3px rgba(0,0,0,0.5);
    }
    .tox-tinymce {
        border-radius: 8px !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="sectionEditor()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.premium-page.index') }}"
               class="text-gray-600 hover:text-gray-900 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="text-4xl">{{ $sectionData['icon'] }}</span>
                    {{ $sectionData['name'] }}
                </h1>
                <p class="text-sm text-gray-600 mt-1">แก้ไขเนื้อหาและดูตัวอย่างแบบ Real-time</p>
            </div>
        </div>

        <!-- Save Button (Floating) -->
        <div class="flex items-center gap-3">
            <button @click="resetForm"
                    type="button"
                    class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                🔄 รีเซ็ต
            </button>
            <button @click="saveSection"
                    type="button"
                    class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 transition flex items-center gap-2 shadow-lg">
                <svg x-show="!saving" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg x-show="saving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="saving ? 'กำลังบันทึก...' : '💾 บันทึกการเปลี่ยนแปลง'"></span>
            </button>
        </div>
    </div>

    <!-- Split-screen Layout -->
    <div class="split-container">

        <!-- LEFT: Settings Panel -->
        <div class="settings-panel">
            <div class="space-y-6">

                <!-- Enable/Disable Section -->
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        การแสดงผล
                    </h3>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <span class="text-sm font-medium text-gray-900">เปิดการแสดงผลในหน้าแรก</span>
                            <p class="text-xs text-gray-600 mt-1">ปิดเพื่อซ่อนส่วนนี้โดยไม่ลบข้อมูล</p>
                        </div>
                        <button @click="formData.enabled = !formData.enabled"
                                :class="formData.enabled ? 'bg-green-600' : 'bg-gray-400'"
                                class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors">
                            <span :class="formData.enabled ? 'translate-x-7' : 'translate-x-1'"
                                  class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform shadow-lg"></span>
                        </button>
                    </div>
                </div>

                <!-- Content Fields -->
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        เนื้อหา
                    </h3>

                    <div class="space-y-5">
                        @if(in_array('title', $sectionData['fields']))
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                หัวข้อหลัก
                            </label>
                            <input type="text"
                                   x-model="formData.title"
                                   @input="updatePreview"
                                   class="form-control w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="ใส่หัวข้อหลักของส่วนนี้">
                        </div>
                        @endif

                        @if(in_array('subtitle', $sectionData['fields']))
                        <!-- Subtitle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                คำบรรยายรอง
                            </label>
                            <input type="text"
                                   x-model="formData.subtitle"
                                   @input="updatePreview"
                                   class="form-control w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="ใส่คำบรรยายรอง">
                        </div>
                        @endif

                        @if(in_array('description', $sectionData['fields']))
                        <!-- Description with TinyMCE -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                รายละเอียด (รองรับ Rich Text)
                            </label>
                            <textarea id="description-editor"
                                      x-ref="descriptionEditor"
                                      class="form-control w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('description', \App\Models\Setting::get("premium_page_{$section}_description")) }}</textarea>
                            <p class="mt-2 text-xs text-gray-500">💡 รองรับการจัดรูปแบบข้อความ, ลิงก์, และรูปภาพ</p>
                        </div>
                        @endif

                        @if(in_array('cta_text', $sectionData['fields']))
                        <!-- CTA Text -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ข้อความปุ่ม Call-to-Action
                            </label>
                            <input type="text"
                                   x-model="formData.cta_text"
                                   @input="updatePreview"
                                   class="form-control w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="เช่น: เริ่มต้นฟรีวันนี้, สมัครเลย">
                        </div>
                        @endif

                        @if(in_array('limit', $sectionData['fields']))
                        <!-- Limit -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                จำนวนที่แสดง: <span x-text="formData.limit"></span>
                            </label>
                            <input type="range"
                                   x-model="formData.limit"
                                   @input="updatePreview"
                                   min="1" max="20"
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <p class="mt-1 text-xs text-gray-500">จำนวนรายการที่จะแสดงในส่วนนี้</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Tips -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="flex gap-3">
                        <div class="text-2xl">💡</div>
                        <div>
                            <h4 class="text-blue-900 font-semibold mb-2">เคล็ดลับ</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• การเปลี่ยนแปลงจะแสดงใน Preview แบบ Real-time</li>
                                <li>• คลิก "บันทึก" เพื่อบันทึกการเปลี่ยนแปลงไปยังเว็บไซต์</li>
                                <li>• ใช้ Rich Text Editor สำหรับจัดรูปแบบข้อความ</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- RIGHT: Live Preview Panel -->
        <div class="preview-panel">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    ตัวอย่างแบบ Real-time
                </h3>
                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full flex items-center gap-1">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Live
                </span>
            </div>

            <!-- Preview Content -->
            <div class="section-preview" x-show="formData.enabled">
                <div class="text-center space-y-4">
                    <!-- Title -->
                    @if(in_array('title', $sectionData['fields']))
                    <h2 class="text-3xl font-bold text-gray-900" x-text="formData.title || 'หัวข้อหลัก'"></h2>
                    @endif

                    <!-- Subtitle -->
                    @if(in_array('subtitle', $sectionData['fields']))
                    <p class="text-lg text-gray-600" x-text="formData.subtitle || 'คำบรรยายรอง'"></p>
                    @endif

                    <!-- Description -->
                    @if(in_array('description', $sectionData['fields']))
                    <div class="prose prose-lg max-w-none text-gray-700" x-html="formData.description || '<p>รายละเอียดจะแสดงที่นี่</p>'"></div>
                    @endif

                    <!-- CTA Button -->
                    @if(in_array('cta_text', $sectionData['fields']))
                    <div class="mt-6">
                        <button class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-lg">
                            <span x-text="formData.cta_text || 'ปุ่ม CTA'"></span>
                        </button>
                    </div>
                    @endif

                    <!-- Limit Info -->
                    @if(in_array('limit', $sectionData['fields']))
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">จะแสดง <span class="font-semibold text-indigo-600" x-text="formData.limit"></span> รายการ</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Disabled State -->
            <div class="section-preview flex items-center justify-center py-12" x-show="!formData.enabled">
                <div class="text-center space-y-3">
                    <div class="text-4xl">🔒</div>
                    <p class="text-gray-600 font-medium">ส่วนนี้ถูกปิดการแสดงผล</p>
                    <p class="text-sm text-gray-500">เปิดการแสดงผลเพื่อดูตัวอย่าง</p>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
function sectionEditor() {
    return {
        formData: {
            enabled: {{ old('enabled', \App\Models\Setting::get("premium_page_{$section}_enabled", true)) ? 'true' : 'false' }},
            @if(in_array('title', $sectionData['fields']))
            title: `{{ old('title', \App\Models\Setting::get("premium_page_{$section}_title")) }}`,
            @endif
            @if(in_array('subtitle', $sectionData['fields']))
            subtitle: `{{ old('subtitle', \App\Models\Setting::get("premium_page_{$section}_subtitle")) }}`,
            @endif
            @if(in_array('description', $sectionData['fields']))
            description: `{!! old('description', \App\Models\Setting::get("premium_page_{$section}_description")) !!}`,
            @endif
            @if(in_array('cta_text', $sectionData['fields']))
            cta_text: `{{ old('cta_text', \App\Models\Setting::get("premium_page_{$section}_cta_text")) }}`,
            @endif
            @if(in_array('limit', $sectionData['fields']))
            limit: {{ old('limit', \App\Models\Setting::get("premium_page_{$section}_limit", 5)) }},
            @endif
        },
        originalData: {},
        saving: false,
        editor: null,

        init() {
            // Store original data for reset
            this.originalData = { ...this.formData };

            // Initialize TinyMCE if description field exists
            @if(in_array('description', $sectionData['fields']))
            this.initTinyMCE();
            @endif
        },

        initTinyMCE() {
            tinymce.init({
                selector: '#description-editor',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                setup: (editor) => {
                    this.editor = editor;
                    editor.on('change keyup', () => {
                        this.formData.description = editor.getContent();
                        this.updatePreview();
                    });
                }
            });
        },

        updatePreview() {
            // Preview updates automatically via Alpine.js reactivity
        },

        async saveSection() {
            this.saving = true;

            // Get content from TinyMCE if exists
            if (this.editor) {
                this.formData.description = this.editor.getContent();
            }

            try {
                const response = await fetch('{{ route('admin.premium-page.update', $section) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    body: JSON.stringify(this.formData)
                });

                if (response.ok) {
                    alert('✅ บันทึกสำเร็จ! การเปลี่ยนแปลงจะแสดงในหน้าแรกทันที');
                    this.originalData = { ...this.formData };
                } else {
                    const data = await response.json();
                    alert('❌ เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถบันทึกได้'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                this.saving = false;
            }
        },

        resetForm() {
            if (confirm('คุณต้องการรีเซ็ตการเปลี่ยนแปลงทั้งหมดหรือไม่?')) {
                this.formData = { ...this.originalData };

                // Reset TinyMCE content
                if (this.editor) {
                    this.editor.setContent(this.formData.description || '');
                }
            }
        }
    }
}
</script>
@endpush

@endsection
