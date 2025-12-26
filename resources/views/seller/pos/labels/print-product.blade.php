@extends('layouts.seller')

@section('title', 'พิมพ์ฉลากสินค้า')

@section('content')
<div class="container-fluid px-4 py-6" x-data="productLabelPrinter()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🏷️ พิมพ์ฉลากสินค้า
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                เลือกสินค้าและพิมพ์ฉลากแบบ Batch
            </p>
        </div>
        <a href="{{ route('seller.pos.labels.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Product Selection --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Search Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ค้นหาสินค้า</h3>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="searchProducts()"
                       placeholder="ค้นหาด้วยชื่อ, Barcode, SKU..."
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 text-gray-900 dark:text-white">

                {{-- Search Results --}}
                <div x-show="searchResults.length > 0" class="mt-4 max-h-96 overflow-y-auto">
                    <template x-for="product in searchResults" :key="product.id">
                        <div @click="addProduct(product)"
                             class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white" x-text="product.name"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span x-text="product.barcode"></span> • ฿<span x-text="product.price"></span>
                                </p>
                            </div>
                            <button class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                + เพิ่ม
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Selected Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สินค้าที่เลือก</h3>

                <div x-show="selectedProducts.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่ได้เลือกสินค้า</p>
                </div>

                <div x-show="selectedProducts.length > 0" class="space-y-3">
                    <template x-for="(product, index) in selectedProducts" :key="index">
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white" x-text="product.name"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="product.barcode"></p>
                            </div>
                            <input type="number"
                                   x-model.number="product.quantity"
                                   @change="updateTotal()"
                                   min="1"
                                   class="w-20 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-center">
                            <button @click="removeProduct(index)" class="text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Right: Settings & Preview --}}
        <div class="space-y-6">
            {{-- Template Selection --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เลือก Template</h3>
                <select x-model="selectedTemplate" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                    <option value="">-- เลือก Template --</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Paper Size --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ขนาดกระดาษ</h3>
                <select x-model="selectedPaperSize" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                    <option value="">-- เลือกขนาด --</option>
                    @foreach($paperSizes as $size)
                        <option value="{{ $size->name }}">{{ $size->name }} ({{ $size->width }}x{{ $size->height }}mm)</option>
                    @endforeach
                </select>
            </div>

            {{-- Summary --}}
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-4">สรุป</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>จำนวนสินค้า:</span>
                        <span class="font-bold" x-text="selectedProducts.length + ' รายการ'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>จำนวนฉลาก:</span>
                        <span class="font-bold" x-text="totalLabels + ' ดวง'"></span>
                    </div>
                    <div class="flex justify-between" x-show="sheetsCount > 0">
                        <span>จำนวนแผ่น:</span>
                        <span class="font-bold" x-text="sheetsCount + ' แผ่น'"></span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="space-y-3">
                <button @click="preview()"
                        :disabled="selectedProducts.length === 0 || !selectedTemplate"
                        class="w-full px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    👁️ ดูตัวอย่าง
                </button>
                <button @click="print()"
                        :disabled="selectedProducts.length === 0 || !selectedTemplate"
                        class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-bold">
                    🖨️ พิมพ์ทันที
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productLabelPrinter() {
    return {
        searchQuery: '',
        searchResults: [],
        selectedProducts: [],
        selectedTemplate: '',
        selectedPaperSize: '',
        totalLabels: 0,
        sheetsCount: 0,

        async searchProducts() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }

            try {
                const response = await fetch(`{{ route('seller.pos.labels.api.search-products') }}?q=${this.searchQuery}`);
                const data = await response.json();
                this.searchResults = data.data;
            } catch (error) {
                console.error('Search error:', error);
            }
        },

        addProduct(product) {
            if (!this.selectedProducts.find(p => p.id === product.id)) {
                this.selectedProducts.push({...product, quantity: 1});
                this.updateTotal();
            }
            this.searchQuery = '';
            this.searchResults = [];
        },

        removeProduct(index) {
            this.selectedProducts.splice(index, 1);
            this.updateTotal();
        },

        updateTotal() {
            this.totalLabels = this.selectedProducts.reduce((sum, p) => sum + p.quantity, 0);
            // Calculate sheets (example: 30 labels per sheet)
            this.sheetsCount = Math.ceil(this.totalLabels / 30);
        },

        async preview() {
            const products = this.selectedProducts.map(p => ({
                product_id: p.id,
                quantity: p.quantity
            }));

            try {
                const response = await fetch('{{ route('seller.pos.labels.api.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        template_id: this.selectedTemplate,
                        products: products
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Preview: ' + data.data.total_labels + ' ฉลาก, ' + data.data.sheets_count + ' แผ่น');
                }
            } catch (error) {
                console.error('Preview error:', error);
            }
        },

        async print() {
            const products = this.selectedProducts.map(p => ({
                product_id: p.id,
                quantity: p.quantity
            }));

            try {
                const response = await fetch('{{ route('seller.pos.labels.api.print') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        template_id: this.selectedTemplate,
                        print_type: 'product_label',
                        products: products,
                        paper_size: this.selectedPaperSize
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('บันทึกการพิมพ์สำเร็จ!\nฉลาก: ' + data.data.total_labels + ' ดวง');
                    // Reset form
                    this.selectedProducts = [];
                    this.updateTotal();
                    window.print(); // Open browser print dialog
                }
            } catch (error) {
                console.error('Print error:', error);
                alert('เกิดข้อผิดพลาด');
            }
        }
    }
}
</script>
@endpush
@endsection
