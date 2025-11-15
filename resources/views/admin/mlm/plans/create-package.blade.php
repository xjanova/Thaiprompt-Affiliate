@extends('layouts.admin')

@section('title', 'สร้างแพคเกจ MLM ใหม่')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-box text-purple-600 dark:text-purple-400"></i>
                สร้างแพคเกจ MLM ใหม่
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">กำหนดแพคเกจเริ่มต้นและเลือกสินค้าที่รวมอยู่ในแพคเกจ</p>
        </div>
        <a href="{{ route('admin.mlm.plans.index') }}"
           class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            กลับ
        </a>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6 mb-6">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-2xl mt-0.5"></i>
            <div>
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">แพคเกจ MLM คืออะไร?</h3>
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    แพคเกจ MLM คือชุดสินค้าเริ่มต้นสำหรับสมาชิก เช่น Free (ฟรี), Bronze (บรอนซ์), Silver (ซิลเวอร์), Gold (โกลด์)
                    แต่ละแพคเกจจะมีสินค้าและสิทธิ์ต่างกัน โดยการตั้งค่าคอมมิชชั่นและ PV จะใช้ค่าจาก "ตั้งค่าระบบ MLM" แบบโกลบอล
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.mlm.plans.store') }}" method="POST" id="package-form">
        @csrf

        <!-- Basic Package Information -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white px-6 py-4">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fas fa-file-alt"></i>
                    ข้อมูลแพคเกจพื้นฐาน
                </h2>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Package Name (EN) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อแพคเกจ (EN) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition"
                               placeholder="e.g., Bronze Package" required>
                        @error('name')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>

                    <!-- Package Name (TH) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อแพคเกจ (TH)
                        </label>
                        <input type="text" name="name_th" value="{{ old('name_th') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition"
                               placeholder="เช่น แพคเกจบรอนซ์">
                        @error('name_th')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>

                    <!-- Package Description (EN) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย (EN)
                        </label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition"
                                  placeholder="Describe the package benefits...">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>

                    <!-- Package Description (TH) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย (TH)
                        </label>
                        <textarea name="description_th" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition"
                                  placeholder="อธิบายสิทธิประโยชน์ของแพคเกจ...">{{ old('description_th') }}</textarea>
                        @error('description_th')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>

                    <!-- Joining Fee -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ค่าสมัครสมาชิก (บาท)
                        </label>
                        <input type="number" name="joining_fee" value="{{ old('joining_fee', 0) }}" step="0.01" min="0"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                        @error('joining_fee')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            สีของแพคเกจ
                        </label>
                        <div class="flex gap-2">
                            <input type="color" name="color" value="{{ old('color', '#8B5CF6') }}"
                                   class="h-12 w-16 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700">
                            <input type="text" id="color-text" value="{{ old('color', '#8B5CF6') }}"
                                   class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition"
                                   placeholder="#8B5CF6">
                        </div>
                    </div>

                    <!-- Icon -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ไอคอน (Emoji หรือ Class)
                        </label>
                        <input type="text" name="icon" value="{{ old('icon', '📦') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition"
                               placeholder="📦 or fa-box">
                        @error('icon')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ลำดับการแสดง
                        </label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition">
                        @error('sort_order')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i>{{ $message }}
                        </p>
                        @enderror
                    </div>
                </div>

                <!-- Checkboxes -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500 dark:focus:ring-purple-400">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500 dark:focus:ring-purple-400">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ตั้งเป็นค่าเริ่มต้น</span>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="requires_joining_fee" value="1" {{ old('requires_joining_fee', true) ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500 dark:focus:ring-purple-400">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ต้องชำระค่าสมัคร</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-700 dark:to-teal-700 text-white px-6 py-4">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fas fa-shopping-cart"></i>
                    สินค้าที่รวมอยู่ในแพคเกจ
                </h2>
            </div>

            <div class="p-6">
                <!-- Product Search -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ค้นหาและเลือกสินค้า
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="product-search"
                               class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition"
                               placeholder="พิมพ์ชื่อสินค้าเพื่อค้นหา...">
                        <button type="button" id="add-product-btn"
                                class="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            เพิ่มสินค้า
                        </button>
                    </div>
                </div>

                <!-- Selected Products Table -->
                <div id="selected-products-container" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สินค้า</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider w-24">จำนวน</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider w-32">ส่วนลด (%)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider w-24">ลำดับ</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider w-20">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody" class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <!-- Products will be added here dynamically -->
                            <tr id="no-products-row">
                                <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p class="text-lg">ยังไม่มีสินค้าในแพคเกจ</p>
                                    <p class="text-sm">คลิก "เพิ่มสินค้า" เพื่อเลือกสินค้า</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row justify-end gap-3">
            <a href="{{ route('admin.mlm.plans.index') }}"
               class="w-full sm:w-auto px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-lg shadow-lg hover:shadow-xl font-medium transition-all duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-times"></i>
                ยกเลิก
            </a>
            <button type="submit"
                    class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 dark:from-purple-500 dark:to-pink-500 dark:hover:from-purple-600 dark:hover:to-pink-600 text-white rounded-lg shadow-lg hover:shadow-xl font-medium transition-all duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-save"></i>
                สร้างแพคเกจ
            </button>
        </div>
    </form>
</div>

<!-- Product Search Modal -->
<div id="product-modal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[80vh] flex flex-col">
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-700 dark:to-teal-700 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
            <h3 class="text-lg font-semibold flex items-center gap-2">
                <i class="fas fa-search"></i>
                เลือกสินค้า
            </h3>
            <button type="button" id="close-modal" class="text-white hover:text-gray-200 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <input type="text" id="modal-search"
                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 mb-4"
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
        <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
             onclick="addProduct(${product.id})">
            <div class="flex items-center gap-3">
                ${product.image_url ? `<img src="${product.image_url}" class="w-12 h-12 object-cover rounded">` : '<div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">📦</div>'}
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">${product.name}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">${product.sku || 'N/A'} • ${product.price ? product.price.toLocaleString() + ' บาท' : 'ราคาไม่กำหนด'}</div>
                </div>
            </div>
            <button type="button" class="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                เลือก
            </button>
        </div>
    `).join('');

    document.getElementById('products-list').innerHTML = html || '<div class="text-center text-gray-500 dark:text-gray-400 py-8">ไม่พบสินค้า</div>';
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
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    ${product.image_url ? `<img src="${product.image_url}" class="w-10 h-10 object-cover rounded">` : '<div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center text-xs">📦</div>'}
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">${product.name}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">${product.sku || 'N/A'}</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">
                <input type="number" value="${product.quantity}" min="1"
                       onchange="updateProduct(${index}, 'quantity', parseInt(this.value))"
                       class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded">
            </td>
            <td class="px-4 py-3">
                <input type="number" value="${product.discount_percentage}" min="0" max="100" step="0.01"
                       onchange="updateProduct(${index}, 'discount_percentage', parseFloat(this.value))"
                       class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded">
            </td>
            <td class="px-4 py-3">
                <input type="number" value="${product.sort_order}" min="0"
                       onchange="updateProduct(${index}, 'sort_order', parseInt(this.value))"
                       class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded">
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="removeProduct(${index})"
                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors">
                    <i class="fas fa-trash text-lg"></i>
                </button>
            </td>
        </tr>
    `).join('');

    tbody.innerHTML = html + noProductsRow.outerHTML;
}
</script>
@endsection
