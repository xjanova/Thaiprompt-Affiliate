@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.fortune.response-templates.index') }}"
           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-4 inline-block">
            ← กลับไปรายการ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ $pageTitle }}
        </h1>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-6 py-4 rounded-lg mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $template ? route('admin.fortune.response-templates.update', $template) : route('admin.fortune.response-templates.store') }}"
          x-data="templateForm()">
        @csrf
        @if($template)
            @method('PUT')
        @endif

        <div class="space-y-6">
            {{-- ข้อมูลพื้นฐาน --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- ชื่อ --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อเทมเพลต *</label>
                        <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                               required placeholder="เช่น คำทำนายพื้นฐาน - มาตรฐาน">
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug (อัตโนมัติ)</label>
                        <input type="text" name="slug" value="{{ old('slug', $template->slug ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                               placeholder="auto-generated-from-name">
                    </div>

                    {{-- ประเภท --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท *</label>
                        <select name="type"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                required>
                            <option value="basic" {{ old('type', $template->type ?? '') === 'basic' ? 'selected' : '' }}>🔮 คำทำนายพื้นฐาน</option>
                            <option value="deep" {{ old('type', $template->type ?? '') === 'deep' ? 'selected' : '' }}>🌟 คำทำนายเชิงลึก</option>
                            <option value="welcome" {{ old('type', $template->type ?? '') === 'welcome' ? 'selected' : '' }}>👋 ข้อความต้อนรับ</option>
                            <option value="payment" {{ old('type', $template->type ?? '') === 'payment' ? 'selected' : '' }}>💰 แจ้งชำระเงิน</option>
                            <option value="limit_exceeded" {{ old('type', $template->type ?? '') === 'limit_exceeded' ? 'selected' : '' }}>⏳ หมดโควต้าฟรี</option>
                            <option value="error" {{ old('type', $template->type ?? '') === 'error' ? 'selected' : '' }}>❌ ข้อผิดพลาด</option>
                        </select>
                    </div>

                    {{-- ลำดับ --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลำดับ</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $template->sort_order ?? 0) }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                               min="0">
                    </div>
                </div>

                {{-- Checkboxes --}}
                <div class="flex gap-6 mt-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}
                               class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <span class="text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1"
                               {{ old('is_default', $template->is_default ?? false) ? 'checked' : '' }}
                               class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <span class="text-gray-700 dark:text-gray-300">เป็นเทมเพลตเริ่มต้น</span>
                    </label>
                </div>
            </div>

            {{-- เนื้อหาเทมเพลต --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">เนื้อหาเทมเพลต</h2>

                {{-- Placeholders ที่ใช้ได้ --}}
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 mb-4">
                    <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200 mb-2">Placeholders ที่ใช้ได้ (คลิกเพื่อเพิ่ม):</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach([
                            '{response}' => 'คำทำนายจาก AI',
                            '{user_name}' => 'ชื่อผู้ใช้',
                            '{date}' => 'วันที่ (dd/mm/yyyy)',
                            '{date_thai}' => 'วันที่ภาษาไทย',
                            '{questions}' => 'คำถาม',
                            '{reading_type}' => 'ประเภท',
                            '{reading_id}' => 'เลขที่',
                            '{rate_url}' => 'URL ให้คะแนน',
                            '{register_url}' => 'URL สมัครสมาชิก',
                            '{payment_url}' => 'URL ชำระเงิน',
                            '{remaining_free}' => 'ครั้งฟรีที่เหลือ',
                            '{max_free}' => 'ครั้งฟรีสูงสุด',
                            '{price}' => 'ราคา',
                        ] as $placeholder => $desc)
                            <button type="button"
                                    @click="insertPlaceholder('{{ $placeholder }}')"
                                    class="text-xs bg-white dark:bg-gray-700 border border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 px-2 py-1 rounded hover:bg-indigo-100 dark:hover:bg-indigo-800 transition"
                                    title="{{ $desc }}">
                                {{ $placeholder }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- ข้อความส่วนหัว --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความส่วนหัว (ก่อนคำทำนาย)</label>
                    <input type="text" name="header_text" value="{{ old('header_text', $template->header_text ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                           placeholder="เว้นว่างถ้าไม่ต้องการ">
                </div>

                {{-- เนื้อหาหลัก --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เนื้อหาหลัก *</label>
                    <textarea name="body" id="templateBody" rows="12"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono text-sm"
                              required placeholder="ใส่เนื้อหาเทมเพลต รองรับ placeholders เช่น {response}, {user_name}">{{ old('body', $template->body ?? '') }}</textarea>
                </div>

                {{-- ข้อความส่วนท้าย --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความส่วนท้าย (หลังคำทำนาย)</label>
                    <input type="text" name="footer_text" value="{{ old('footer_text', $template->footer_text ?? '') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                           placeholder="เว้นว่างถ้าไม่ต้องการ">
                </div>
            </div>

            {{-- รูปภาพ --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">รูปภาพ (ส่งผ่าน Messenger)</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    ระบุ URL รูปภาพ (HTTPS) ที่จะส่งพร้อมข้อความ เช่น QR Code ชำระเงิน, รูปบัญชีธนาคาร
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- รูปส่วนหัว --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            🖼️ รูปส่วนหัว (ส่งก่อนข้อความ)
                        </label>
                        <input type="url" name="header_image_url" value="{{ old('header_image_url', $template->header_image_url ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                               placeholder="https://example.com/header.jpg"
                               x-model="headerImageUrl">
                        <template x-if="headerImageUrl">
                            <img :src="headerImageUrl" alt="ตัวอย่างรูปส่วนหัว"
                                 class="mt-2 rounded-lg max-h-32 object-contain border border-gray-200 dark:border-gray-600"
                                 @error="$el.style.display='none'">
                        </template>
                    </div>

                    {{-- รูปส่วนท้าย (QR Code) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            💰 รูปส่วนท้าย (เช่น QR Code / บัญชีธนาคาร)
                        </label>
                        <input type="url" name="footer_image_url" value="{{ old('footer_image_url', $template->footer_image_url ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                               placeholder="https://example.com/qr-code.jpg"
                               x-model="footerImageUrl">
                        <template x-if="footerImageUrl">
                            <img :src="footerImageUrl" alt="ตัวอย่างรูปส่วนท้าย"
                                 class="mt-2 rounded-lg max-h-32 object-contain border border-gray-200 dark:border-gray-600"
                                 @error="$el.style.display='none'">
                        </template>
                    </div>
                </div>
            </div>

            {{-- ดูตัวอย่าง --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ดูตัวอย่าง</h2>
                    <button type="button" @click="loadPreview()"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition text-sm">
                        โหลดตัวอย่าง
                    </button>
                </div>
                <div x-show="previewText" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-mono" x-text="previewText"></pre>
                </div>
                <div x-show="!previewText" class="text-center text-gray-400 dark:text-gray-500 py-8">
                    กดปุ่ม "โหลดตัวอย่าง" เพื่อดูผลลัพธ์
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex gap-4">
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg shadow-lg transition font-medium">
                    {{ $template ? 'บันทึกการแก้ไข' : 'สร้างเทมเพลต' }}
                </button>
                <a href="{{ route('admin.fortune.response-templates.index') }}"
                   class="px-8 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                    ยกเลิก
                </a>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function templateForm() {
    return {
        headerImageUrl: '{{ old('header_image_url', $template->header_image_url ?? '') }}',
        footerImageUrl: '{{ old('footer_image_url', $template->footer_image_url ?? '') }}',
        previewText: '',

        insertPlaceholder(placeholder) {
            const textarea = document.getElementById('templateBody');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + placeholder + text.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
            textarea.focus();
        },

        async loadPreview() {
            try {
                const body = document.getElementById('templateBody').value;
                const headerText = document.querySelector('[name="header_text"]').value;
                const footerText = document.querySelector('[name="footer_text"]').value;

                const response = await fetch('{{ route("admin.fortune.response-templates.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ body, header_text: headerText, footer_text: footerText }),
                });

                const data = await response.json();
                if (data.success) {
                    this.previewText = data.preview;
                }
            } catch (e) {
                this.previewText = 'เกิดข้อผิดพลาดในการโหลดตัวอย่าง';
            }
        }
    }
}
</script>
@endpush
@endsection
