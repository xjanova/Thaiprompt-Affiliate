@extends('layouts.seller')

@section('title', 'พิมพ์ฉลากสินค้า - POS')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="productLabelPrinter()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🏷️ พิมพ์ฉลากสินค้า
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                เลือกสินค้าและพิมพ์ฉลาก Barcode
            </p>
        </div>
        <a href="{{ route('seller.pos.labels.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>
            กลับ
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: รายการสินค้า --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
                {{-- Search & Filter --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <input type="text"
                                   x-model="searchQuery"
                                   @input.debounce.500ms="fetchProducts()"
                                   placeholder="ค้นหาชื่อ, Barcode, SKU..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <select x-model="perPage"
                                @change="fetchProducts()"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="20">20 รายการ</option>
                            <option value="50">50 รายการ</option>
                            <option value="100">100 รายการ</option>
                        </select>
                    </div>
                </div>

                {{-- Products List --}}
                <div class="overflow-y-auto" style="max-height: 600px;">
                    <template x-if="loading">
                        <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                            <p>กำลังโหลดสินค้า...</p>
                        </div>
                    </template>

                    <template x-if="!loading && products.length === 0">
                        <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>ไม่พบสินค้า</p>
                        </div>
                    </template>

                    <template x-if="!loading && products.length > 0">
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="product in products" :key="product.id">
                                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
                                     @click="addProduct(product)">
                                    <div class="flex items-center gap-4">
                                        <img :src="product.image || '/images/no-image.png'"
                                             :alt="product.name"
                                             class="w-16 h-16 object-cover rounded-lg">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white" x-text="product.name"></h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Barcode: <span x-text="product.barcode"></span>
                                                | SKU: <span x-text="product.sku"></span>
                                            </p>
                                            <template x-if="product.store_name">
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    ร้าน: <span x-text="product.store_name"></span>
                                                </p>
                                            </template>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                                ฿<span x-text="product.price"></span>
                                            </p>
                                            <button @click.stop="addProduct(product)"
                                                    class="mt-2 px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition">
                                                <i class="fas fa-plus mr-1"></i> เพิ่ม
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        แสดง <span x-text="products.length"></span> รายการ
                        จากทั้งหมด <span x-text="meta.total"></span> รายการ
                    </div>
                    <div class="flex gap-2">
                        <button @click="prevPage()"
                                :disabled="meta.current_page === 1"
                                :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300 dark:hover:bg-gray-600'"
                                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="px-3 py-1 text-gray-700 dark:text-gray-300">
                            หน้า <span x-text="meta.current_page"></span> / <span x-text="meta.last_page"></span>
                        </span>
                        <button @click="nextPage()"
                                :disabled="meta.current_page === meta.last_page"
                                :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300 dark:hover:bg-gray-600'"
                                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: สินค้าที่เลือก --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 sticky top-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    สินค้าที่เลือก (<span x-text="selectedProducts.length"></span>)
                </h3>

                {{-- Selected Products --}}
                <div class="space-y-3 mb-4" style="max-height: 300px; overflow-y: auto;">
                    <template x-if="selectedProducts.length === 0">
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                            ยังไม่ได้เลือกสินค้า
                        </p>
                    </template>

                    <template x-for="(item, index) in selectedProducts" :key="item.product_id">
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <p class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.name"></p>
                                <button @click="removeProduct(index)"
                                        class="text-red-600 dark:text-red-400 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-gray-600 dark:text-gray-400">จำนวนฉลาก:</label>
                                <input type="number"
                                       x-model.number="item.quantity"
                                       min="1"
                                       @input="updateTotal()"
                                       class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-white text-sm">
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Template Selection --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template</label>
                    <select x-model="selectedTemplate"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">-- เลือก Template --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Summary --}}
                <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 dark:text-gray-300">รายการ:</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="selectedProducts.length"></span>
                    </div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 dark:text-gray-300">ฉลากทั้งหมด:</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="totalLabels"></span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="space-y-2">
                    <button @click="print()"
                            :disabled="selectedProducts.length === 0 || !selectedTemplate"
                            :class="selectedProducts.length === 0 || !selectedTemplate ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'"
                            class="w-full px-4 py-3 bg-green-600 text-white rounded-lg font-semibold transition">
                        <i class="fas fa-print mr-2"></i>
                        พิมพ์ฉลาก
                    </button>
                    <button @click="clearSelection()"
                            class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>
                        ล้างรายการ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productLabelPrinter() {
    return {
        products: [],
        selectedProducts: [],
        searchQuery: '',
        perPage: 20,
        loading: false,
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0
        },
        selectedTemplate: '',
        totalLabels: 0,

        async init() {
            await this.fetchProducts();
        },

        async fetchProducts(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.perPage,
                    search: this.searchQuery
                });

                const response = await fetch(`{{ route('seller.pos.labels.api.products') }}?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.products = data.data;
                    this.meta = data.meta;
                }
            } catch (error) {
                console.error('Error fetching products:', error);
                alert('เกิดข้อผิดพลาดในการโหลดสินค้า');
            } finally {
                this.loading = false;
            }
        },

        addProduct(product) {
            const exists = this.selectedProducts.find(p => p.product_id === product.id);
            if (exists) {
                exists.quantity++;
            } else {
                this.selectedProducts.push({
                    product_id: product.id,
                    name: product.name,
                    barcode: product.barcode,
                    sku: product.sku,
                    price: product.price,
                    quantity: 1
                });
            }
            this.updateTotal();
        },

        removeProduct(index) {
            this.selectedProducts.splice(index, 1);
            this.updateTotal();
        },

        updateTotal() {
            this.totalLabels = this.selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
        },

        clearSelection() {
            this.selectedProducts = [];
            this.totalLabels = 0;
        },

        async print() {
            if (this.selectedProducts.length === 0) {
                alert('กรุณาเลือกสินค้า');
                return;
            }

            if (!this.selectedTemplate) {
                alert('กรุณาเลือก Template');
                return;
            }

            try {
                // 1. บันทึกข้อมูลการพิมพ์ก่อน
                const response = await fetch('{{ route('seller.pos.labels.api.print') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        template_id: this.selectedTemplate,
                        print_type: 'product_label',
                        products: this.selectedProducts
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // 2. เปิดหน้า Preview ในหน้าต่างใหม่
                    const previewUrl = new URL('{{ route('seller.pos.labels.preview') }}', window.location.origin);
                    previewUrl.searchParams.append('template_id', this.selectedTemplate);

                    // ส่งข้อมูล products ผ่าน POST
                    const form = document.createElement('form');
                    form.method = 'GET';
                    form.action = previewUrl.toString();
                    form.target = '_blank';

                    // เพิ่ม template_id
                    const templateInput = document.createElement('input');
                    templateInput.type = 'hidden';
                    templateInput.name = 'template_id';
                    templateInput.value = this.selectedTemplate;
                    form.appendChild(templateInput);

                    // เพิ่ม products
                    this.selectedProducts.forEach((product, index) => {
                        const productIdInput = document.createElement('input');
                        productIdInput.type = 'hidden';
                        productIdInput.name = `products[${index}][product_id]`;
                        productIdInput.value = product.product_id;
                        form.appendChild(productIdInput);

                        const quantityInput = document.createElement('input');
                        quantityInput.type = 'hidden';
                        quantityInput.name = `products[${index}][quantity]`;
                        quantityInput.value = product.quantity;
                        form.appendChild(quantityInput);
                    });

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);

                    // 3. ล้างรายการ
                    this.clearSelection();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                console.error('Print error:', error);
                alert('เกิดข้อผิดพลาดในการพิมพ์');
            }
        },

        prevPage() {
            if (this.meta.current_page > 1) {
                this.fetchProducts(this.meta.current_page - 1);
            }
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.fetchProducts(this.meta.current_page + 1);
            }
        }
    }
}
</script>
@endpush
@endsection
