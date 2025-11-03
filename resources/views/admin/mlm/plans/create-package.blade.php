@extends('layouts.admin')

@section('title', 'สร้างแพคเกจ MLM ใหม่')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">สร้างแพคเกจ MLM ใหม่</h1>
            <p class="text-gray-600 mt-1">กำหนดแพคเกจเริ่มต้นและเลือกสินค้าที่รวมอยู่ในแพคเกจ</p>
        </div>
        <a href="{{ route('admin.mlm.plans.index') }}"
           class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับ
        </a>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">แพคเกจ MLM คืออะไร?</h3>
                <p class="text-sm text-blue-700 mt-1">
                    แพคเกจ MLM คือชุดสินค้าเริ่มต้นสำหรับสมาชิก เช่น Free (ฟรี), Bronze (บรอนซ์), Silver (ซิลเวอร์), Gold (โกลด์)
                    แต่ละแพคเกจจะมีสินค้าและสิทธิ์ต่างกัน โดยการตั้งค่าคอมมิชชั่นและ PV จะใช้ค่าจาก "ตั้งค่าระบบ MLM" แบบโกลบอล
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.mlm.plans.store') }}" method="POST" id="package-form">
        @csrf

        <!-- Basic Package Information -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-4 -mx-6 -mt-6 rounded-t-xl mb-6">
                <h2 class="text-lg font-semibold">ข้อมูลแพคเกจพื้นฐาน</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Package Name (EN) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ชื่อแพคเกจ (EN) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="e.g., Bronze Package" required>
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Package Name (TH) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ชื่อแพคเกจ (TH)
                    </label>
                    <input type="text" name="name_th" value="{{ old('name_th') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="เช่น แพคเกจบรอนซ์">
                    @error('name_th')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Package Description (EN) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        คำอธิบาย (EN)
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                              placeholder="Describe the package benefits...">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Package Description (TH) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        คำอธิบาย (TH)
                    </label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                              placeholder="อธิบายสิทธิประโยชน์ของแพคเกจ...">{{ old('description_th') }}</textarea>
                    @error('description_th')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Joining Fee -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ค่าสมัครสมาชิก (บาท)
                    </label>
                    <input type="number" name="joining_fee" value="{{ old('joining_fee', 0) }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    @error('joining_fee')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        สีของแพคเกจ
                    </label>
                    <div class="flex gap-2">
                        <input type="color" name="color" value="{{ old('color', '#8B5CF6') }}"
                               class="h-10 w-16 border border-gray-300 rounded-lg cursor-pointer">
                        <input type="text" id="color-text" value="{{ old('color', '#8B5CF6') }}"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                               placeholder="#8B5CF6">
                    </div>
                </div>

                <!-- Icon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ไอคอน (Emoji หรือ Class)
                    </label>
                    <input type="text" name="icon" value="{{ old('icon', '📦') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="📦 or fa-box">
                    @error('icon')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ลำดับการแสดง
                    </label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    @error('sort_order')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                    <span class="text-sm font-medium text-gray-700">เปิดใช้งาน</span>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                           class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                    <span class="text-sm font-medium text-gray-700">ตั้งเป็นค่าเริ่มต้น</span>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="requires_joining_fee" value="1" {{ old('requires_joining_fee', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                    <span class="text-sm font-medium text-gray-700">ต้องชำระค่าสมัคร</span>
                </label>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white px-6 py-4 -mx-6 -mt-6 rounded-t-xl mb-6">
                <h2 class="text-lg font-semibold">สินค้าที่รวมอยู่ในแพคเกจ</h2>
            </div>

            <!-- Product Search -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ค้นหาและเลือกสินค้า
                </label>
                <div class="flex gap-2">
                    <input type="text" id="product-search"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                           placeholder="พิมพ์ชื่อสินค้าเพื่อค้นหา...">
                    <button type="button" id="add-product-btn"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                        เพิ่มสินค้า
                    </button>
                </div>
            </div>

            <!-- Selected Products Table -->
            <div id="selected-products-container" class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สินค้า</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">จำนวน</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">ส่วนลด (%)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">ลำดับ</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">ลบ</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody" class="divide-y divide-gray-200 bg-white">
                        <!-- Products will be added here dynamically -->
                        <tr id="no-products-row">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                ยังไม่มีสินค้าในแพคเกจ คลิก "เพิ่มสินค้า" เพื่อเลือกสินค้า
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.mlm.plans.index') }}"
               class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg font-medium shadow-lg transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                สร้างแพคเกจ
            </button>
        </div>
    </form>
</div>

<!-- Product Search Modal -->
<div id="product-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[80vh] flex flex-col">
        <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
            <h3 class="text-lg font-semibold">เลือกสินค้า</h3>
            <button type="button" id="close-modal" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <input type="text" id="modal-search"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 mb-4"
                   placeholder="ค้นหาสินค้า...">
            <div id="products-list" class="space-y-2">
                <!-- Products will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Product selection functionality
let selectedProducts = [];
let allProducts = [];

document.addEventListener('DOMContentLoaded', function() {
    // Sync color input
    const colorInput = document.querySelector('input[name="color"]');
    const colorText = document.getElementById('color-text');

    colorInput.addEventListener('input', function() {
        colorText.value = this.value;
    });

    colorText.addEventListener('input', function() {
        if (/^#[0-9A-F]{6}$/i.test(this.value)) {
            colorInput.value = this.value;
        }
    });

    // Load products
    loadProducts();

    // Open modal
    document.getElementById('add-product-btn').addEventListener('click', function() {
        document.getElementById('product-modal').classList.remove('hidden');
    });

    // Close modal
    document.getElementById('close-modal').addEventListener('click', function() {
        document.getElementById('product-modal').classList.add('hidden');
    });

    // Modal search
    document.getElementById('modal-search').addEventListener('input', function() {
        filterProducts(this.value);
    });

    // Form submission
    document.getElementById('package-form').addEventListener('submit', function(e) {
        // Add hidden inputs for selected products
        selectedProducts.forEach((product, index) => {
            const inputs = [
                `<input type="hidden" name="products[${index}][id]" value="${product.id}">`,
                `<input type="hidden" name="products[${index}][quantity]" value="${product.quantity}">`,
                `<input type="hidden" name="products[${index}][discount_percentage]" value="${product.discount_percentage}">`,
                `<input type="hidden" name="products[${index}][sort_order]" value="${product.sort_order}">`
            ].join('');
            this.insertAdjacentHTML('beforeend', inputs);
        });
    });
});

async function loadProducts() {
    try {
        const response = await fetch('/admin/ecommerce/products');
        const data = await response.json();
        allProducts = data.products || [];
        filterProducts('');
    } catch (error) {
        console.error('Error loading products:', error);
        allProducts = [];
    }
}

function filterProducts(searchTerm) {
    const filtered = allProducts.filter(p =>
        p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (p.sku && p.sku.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const html = filtered.map(product => `
        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
             onclick="addProduct(${product.id})">
            <div class="flex items-center gap-3">
                ${product.image_url ? `<img src="${product.image_url}" class="w-12 h-12 object-cover rounded">` : '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">📦</div>'}
                <div>
                    <div class="font-medium text-gray-900">${product.name}</div>
                    <div class="text-sm text-gray-500">${product.sku || 'N/A'} • ${product.price ? product.price.toLocaleString() + ' บาท' : 'ราคาไม่กำหนด'}</div>
                </div>
            </div>
            <button type="button" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                เลือก
            </button>
        </div>
    `).join('');

    document.getElementById('products-list').innerHTML = html || '<div class="text-center text-gray-500 py-8">ไม่พบสินค้า</div>';
}

function addProduct(productId) {
    const product = allProducts.find(p => p.id === productId);
    if (!product) return;

    // Check if already added
    if (selectedProducts.find(p => p.id === productId)) {
        alert('สินค้านี้เพิ่มไว้แล้ว');
        return;
    }

    selectedProducts.push({
        id: product.id,
        name: product.name,
        sku: product.sku,
        price: product.price,
        image_url: product.image_url,
        quantity: 1,
        discount_percentage: 0,
        sort_order: selectedProducts.length
    });

    renderSelectedProducts();
    document.getElementById('product-modal').classList.add('hidden');
}

function removeProduct(index) {
    selectedProducts.splice(index, 1);
    // Re-index sort_order
    selectedProducts.forEach((p, i) => p.sort_order = i);
    renderSelectedProducts();
}

function updateProduct(index, field, value) {
    selectedProducts[index][field] = value;
}

function renderSelectedProducts() {
    const tbody = document.getElementById('products-tbody');
    const noProductsRow = document.getElementById('no-products-row');

    if (selectedProducts.length === 0) {
        noProductsRow.classList.remove('hidden');
        return;
    }

    noProductsRow.classList.add('hidden');

    const html = selectedProducts.map((product, index) => `
        <tr>
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    ${product.image_url ? `<img src="${product.image_url}" class="w-10 h-10 object-cover rounded">` : '<div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center text-xs">📦</div>'}
                    <div>
                        <div class="font-medium text-gray-900">${product.name}</div>
                        <div class="text-xs text-gray-500">${product.sku || 'N/A'}</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">
                <input type="number" value="${product.quantity}" min="1"
                       onchange="updateProduct(${index}, 'quantity', parseInt(this.value))"
                       class="w-full px-2 py-1 border border-gray-300 rounded">
            </td>
            <td class="px-4 py-3">
                <input type="number" value="${product.discount_percentage}" min="0" max="100" step="0.01"
                       onchange="updateProduct(${index}, 'discount_percentage', parseFloat(this.value))"
                       class="w-full px-2 py-1 border border-gray-300 rounded">
            </td>
            <td class="px-4 py-3">
                <input type="number" value="${product.sort_order}" min="0"
                       onchange="updateProduct(${index}, 'sort_order', parseInt(this.value))"
                       class="w-full px-2 py-1 border border-gray-300 rounded">
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="removeProduct(${index})"
                        class="text-red-600 hover:text-red-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');

    tbody.innerHTML = html + noProductsRow.outerHTML;
}
</script>
@endsection
