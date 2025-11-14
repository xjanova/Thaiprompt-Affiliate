@extends('layouts.admin')

@section('title', '🏷️ จัดการหมวดหมู่สินค้า')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🏷️ <span data-translate>จัดการหมวดหมู่สินค้า</span></h1>

        <div class="flex items-center gap-4">
            {{-- Language Switcher --}}
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 transition-all duration-200 shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span data-translate>ภาษา</span>
                </button>

                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden z-50">
                    <a href="#" @click.prevent="language = 'th'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                    </a>
                    <a href="#" @click.prevent="language = 'en'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇬🇧</span> English
                    </a>
                    <a href="#" @click.prevent="language = 'zh'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇨🇳</span> 中文
                    </a>
                    <a href="#" @click.prevent="language = 'ja'" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇯🇵</span> 日本語
                    </a>
                </div>
            </div>

            <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                + <span data-translate>เพิ่มหมวดหมู่</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Categories Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>ชื่อหมวดหมู่</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>จำนวนสินค้า</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>สถานะ</span></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"><span data-translate>การกระทำ</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->name }}</div>
                                @if($category->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($category->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $category->slug }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $category->products_count ?? 0 }} <span data-translate>สินค้า</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($category->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"><span data-translate>ใช้งาน</span></span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200"><span data-translate>ไม่ใช้งาน</span></span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <button onclick="editCategory({{ $category }})" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium">
                                    <span data-translate>แก้ไข</span>
                                </button>
                                <form action="{{ route('admin.ecommerce.categories.delete', $category) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('คุณแน่ใจหรือไม่?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium">
                                        <span data-translate>ลบ</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                <span data-translate>ไม่พบหมวดหมู่</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $categories->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl p-6 w-full max-w-2xl mx-4 my-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white"><span data-translate>เพิ่มหมวดหมู่ใหม่</span></h2>
        <form action="{{ route('admin.ecommerce.categories.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>ชื่อหมวดหมู่</span></label>
                    <input type="text" name="name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug (<span data-translate>เว้นว่างไว้เพื่อสร้างอัตโนมัติ</span>)</label>
                    <input type="text" name="slug" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>คำอธิบาย</span></label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"><span data-translate>รูปภาพหมวดหมู่</span></label>
                    <x-image-upload
                        name="category_image"
                        :multiple="false"
                        :maxFiles="1"
                        :maxSize="5"
                    />
                    <p class="text-xs text-gray-500 mt-2"><span data-translate>ขนาดแนะนำ: 800x800px</span></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>ลำดับการแสดงผล</span></label>
                    <input type="number" name="sort_order" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>หมวดหมู่แม่</span></label>
                    <select name="parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                        <option value=""><span data-translate>ไม่มี (หมวดหมู่หลัก)</span></option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300"><span data-translate>ใช้งาน</span></label>
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600">
                    <span data-translate>ยกเลิก</span>
                </button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    <span data-translate>บันทึก</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl p-6 w-full max-w-2xl mx-4 my-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white"><span data-translate>แก้ไขหมวดหมู่</span></h2>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>ชื่อหมวดหมู่</span></label>
                    <input type="text" id="edit_name" name="name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug (<span data-translate>เว้นว่างไว้เพื่อสร้างอัตโนมัติ</span>)</label>
                    <input type="text" id="edit_slug" name="slug" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>คำอธิบาย</span></label>
                    <textarea id="edit_description" name="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white"></textarea>
                </div>

                <!-- Current Image Preview -->
                <div id="currentImagePreview" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><span data-translate>รูปภาพปัจจุบัน</span></label>
                    <img id="currentImage" src="" alt="Current category image" class="w-32 h-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600 mb-2">
                </div>

                <div id="editImageUpload">
                    <!-- Image upload component will be inserted here by JavaScript -->
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>ลำดับการแสดงผล</span></label>
                    <input type="number" id="edit_sort_order" name="sort_order" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><span data-translate>หมวดหมู่แม่</span></label>
                    <select id="edit_parent_id" name="parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white">
                        <option value=""><span data-translate>ไม่มี (หมวดหมู่หลัก)</span></option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300"><span data-translate>ใช้งาน</span></label>
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-600">
                    <span data-translate>ยกเลิก</span>
                </button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    <span data-translate>บันทึกการแก้ไข</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(category) {
    // Set form action
    const form = document.getElementById('editForm');
    form.action = `/admin/ecommerce/categories/${category.id}`;

    // Populate form fields
    document.getElementById('edit_name').value = category.name || '';
    document.getElementById('edit_slug').value = category.slug || '';
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_sort_order').value = category.sort_order || 0;
    document.getElementById('edit_parent_id').value = category.parent_id || '';
    document.getElementById('edit_is_active').checked = category.is_active;

    // Handle image display
    const imagePreview = document.getElementById('currentImagePreview');
    const currentImage = document.getElementById('currentImage');
    const imageUploadDiv = document.getElementById('editImageUpload');

    let existingImages = [];
    if (category.image_url) {
        // Show current image
        currentImage.src = '/storage/' + category.image_url;
        imagePreview.classList.remove('hidden');

        existingImages = [{
            url: '/storage/' + category.image_url,
            name: 'รูปหมวดหมู่'
        }];
    } else {
        imagePreview.classList.add('hidden');
    }

    // Create image upload component HTML
    imageUploadDiv.innerHTML = `
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3" data-translate>รูปภาพหมวดหมู่${category.image_url ? '<span data-translate> (อัปโหลดใหม่เพื่อเปลี่ยน)</span>' : ''}</label>
        <input type="file" name="category_image" accept="image/*"
            class="block w-full text-sm text-gray-900 dark:text-gray-300
            border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer
            bg-gray-50 dark:bg-slate-700 focus:outline-none
            file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
            file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700
            hover:file:bg-orange-100 dark:file:bg-orange-900 dark:file:text-orange-200">
        <p class="text-xs text-gray-500 mt-2" data-translate>ขนาดแนะนำ: 800x800px (ไฟล์สูงสุด 5MB)</p>
    `;

    // Show modal
    document.getElementById('editModal').classList.remove('hidden');
}

/**
 * ฟังก์ชันแปลภาษาด้วย Google Translate API
 *
 * @param {string} targetLang รหัสภาษาเป้าหมาย (en, zh, ja)
 */
async function translatePage(targetLang) {
    // ถ้าเลือกภาษาไทย ให้โหลดหน้าใหม่เพื่อแสดงข้อความต้นฉบับ
    if (targetLang === 'th') {
        location.reload();
        return;
    }

    // ดึง elements ที่มี data-translate attribute
    const elements = document.querySelectorAll('[data-translate]');

    try {
        // สร้าง array ของข้อความที่ต้องการแปล
        const textsToTranslate = Array.from(elements).map(el => {
            // เก็บข้อความต้นฉบับไว้ใน dataset
            if (!el.dataset.originalText) {
                el.dataset.originalText = el.textContent.trim();
            }
            return el.dataset.originalText;
        });

        // เรียก Google Translate API
        const apiKey = '{{ config("services.google_translate.key", "") }}';

        if (!apiKey) {
            console.warn('Google Translate API key not configured');
            return;
        }

        const response = await fetch(`https://translation.googleapis.com/language/translate/v2?key=${apiKey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                q: textsToTranslate,
                source: 'th',
                target: targetLang,
                format: 'text'
            })
        });

        const data = await response.json();

        // อัพเดทข้อความที่แปลแล้ว
        if (data.data && data.data.translations) {
            data.data.translations.forEach((translation, index) => {
                if (elements[index]) {
                    elements[index].textContent = translation.translatedText;
                }
            });
        }

    } catch (error) {
        console.error('Translation error:', error);
    }
}

// ติดตั้ง watcher สำหรับ Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value);
        }
    });
});
</script>
@endsection
