@extends('layouts.admin')

@section('title', 'สร้าง Label Template ใหม่ - POS')

@section('content')
<div class="container-fluid px-4 py-6" x-data="templateCreator()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                ✨ สร้าง Label Template ใหม่
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                กำหนดขนาดและรูปแบบฉลากของคุณ
            </p>
        </div>
        <a href="{{ route('admin.pos.labels.templates.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>
            กลับ
        </a>
    </div>

    <form action="{{ route('admin.pos.labels.templates.store') }}" method="POST" class="max-w-5xl mx-auto">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Template Info --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                        ข้อมูล Template
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อ Template <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   x-model="form.name"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                   placeholder="เช่น ฉลากสินค้า 50x30mm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea name="description"
                                      x-model="form.description"
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                      placeholder="อธิบายรายละเอียด template"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ประเภท Template <span class="text-red-500">*</span>
                            </label>
                            <select name="pos_category"
                                    x-model="form.pos_category"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">-- เลือกประเภท --</option>
                                <option value="product_label">ฉลากสินค้า (Product Label)</option>
                                <option value="shipping_label">ใบปะสินค้า (Shipping Label)</option>
                                <option value="price_tag">ฉลากราคา (Price Tag)</option>
                                <option value="barcode_only">Barcode เท่านั้น</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ประเภทเครื่องพิมพ์ <span class="text-red-500">*</span>
                            </label>
                            <select name="printer_type"
                                    x-model="form.printer_type"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="any">ทุกประเภท</option>
                                <option value="thermal_roll">Thermal Roll (ม้วน)</option>
                                <option value="thermal_label">Thermal Label (ฉลาก)</option>
                                <option value="inkjet">Inkjet</option>
                                <option value="laser">Laser</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Paper Size --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-ruler-combined mr-2 text-green-500"></i>
                        ขนาดกระดาษ
                    </h2>

                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ความกว้าง <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   name="paper_width"
                                   x-model.number="form.paper_width"
                                   @input="updatePreview()"
                                   required
                                   step="0.1"
                                   min="10"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ความสูง <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   name="paper_height"
                                   x-model.number="form.paper_height"
                                   @input="updatePreview()"
                                   required
                                   step="0.1"
                                   min="10"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                หน่วย <span class="text-red-500">*</span>
                            </label>
                            <select name="paper_unit"
                                    x-model="form.paper_unit"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="mm">mm (มิลลิเมตร)</option>
                                <option value="cm">cm (เซนติเมตร)</option>
                                <option value="in">in (นิ้ว)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="is_continuous_roll"
                                   x-model="form.is_continuous_roll"
                                   value="1"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                เป็นกระดาษม้วน (Continuous Roll)
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Label Layout --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-th mr-2 text-purple-500"></i>
                        รูปแบบฉลาก (Layout)
                    </h2>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                จำนวนคอลัมน์ <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   name="columns"
                                   x-model.number="form.columns"
                                   @input="updatePreview()"
                                   required
                                   min="1"
                                   max="20"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                จำนวนแถว <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   name="rows"
                                   x-model.number="form.rows"
                                   @input="updatePreview()"
                                   required
                                   min="1"
                                   max="20"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <i class="fas fa-info-circle mr-2"></i>
                            จำนวนฉลากต่อแผ่น: <strong x-text="form.columns * form.rows"></strong> ฉลาก
                        </p>
                    </div>
                </div>

                {{-- Spacing --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-arrows-alt mr-2 text-orange-500"></i>
                        ระยะห่าง & ขอบ
                    </h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ระยะห่างแนวนอน
                            </label>
                            <input type="number"
                                   name="gap_horizontal"
                                   x-model.number="form.gap_horizontal"
                                   @input="updatePreview()"
                                   step="0.1"
                                   min="0"
                                   value="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ระยะห่างแนวตั้ง
                            </label>
                            <input type="number"
                                   name="gap_vertical"
                                   x-model.number="form.gap_vertical"
                                   @input="updatePreview()"
                                   step="0.1"
                                   min="0"
                                   value="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ขอบบน
                            </label>
                            <input type="number"
                                   name="margin_top"
                                   x-model.number="form.margin_top"
                                   @input="updatePreview()"
                                   step="0.1"
                                   min="0"
                                   value="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ขอบล่าง
                            </label>
                            <input type="number"
                                   name="margin_bottom"
                                   x-model.number="form.margin_bottom"
                                   @input="updatePreview()"
                                   step="0.1"
                                   min="0"
                                   value="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ขอบซ้าย
                            </label>
                            <input type="number"
                                   name="margin_left"
                                   x-model.number="form.margin_left"
                                   @input="updatePreview()"
                                   step="0.1"
                                   min="0"
                                   value="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ขอบขวา
                            </label>
                            <input type="number"
                                   name="margin_right"
                                   x-model.number="form.margin_right"
                                   @input="updatePreview()"
                                   step="0.1"
                                   min="0"
                                   value="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold rounded-lg hover:from-blue-700 hover:to-blue-800 transition shadow-lg">
                        <i class="fas fa-check mr-2"></i>
                        สร้าง Template และไปที่ Designer
                    </button>
                    <a href="{{ route('admin.pos.labels.templates.index') }}"
                       class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        ยกเลิก
                    </a>
                </div>
            </div>

            {{-- Right: Live Preview --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        🔍 ตัวอย่าง
                    </h3>

                    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4 mb-4 overflow-auto" style="max-height: 600px;">
                        <div class="flex items-center justify-center min-h-[200px]">
                            <div class="bg-white shadow-lg"
                                 :style="{
                                     width: (form.paper_width * 2) + 'px',
                                     height: (form.paper_height * 2) + 'px',
                                     padding: `${form.margin_top * 2}px ${form.margin_right * 2}px ${form.margin_bottom * 2}px ${form.margin_left * 2}px`
                                 }">
                                <div class="grid h-full"
                                     :style="{
                                         gridTemplateColumns: `repeat(${form.columns}, 1fr)`,
                                         gridTemplateRows: `repeat(${form.rows}, 1fr)`,
                                         gap: `${form.gap_vertical * 2}px ${form.gap_horizontal * 2}px`
                                     }">
                                    <template x-for="i in (form.columns * form.rows)" :key="i">
                                        <div class="border-2 border-dashed border-blue-400 rounded flex items-center justify-center text-xs text-gray-500">
                                            <span x-text="i"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ขนาดกระดาษ:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                <span x-text="form.paper_width"></span> x <span x-text="form.paper_height"></span>
                                <span x-text="form.paper_unit"></span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ฉลากต่อแผ่น:</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="form.columns * form.rows + ' ฉลาก'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ขนาดฉลากโดยประมาณ:</span>
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="labelSize"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function templateCreator() {
    return {
        form: {
            name: '',
            description: '',
            pos_category: '',
            paper_width: 210,
            paper_height: 297,
            paper_unit: 'mm',
            printer_type: 'any',
            is_continuous_roll: false,
            columns: 3,
            rows: 8,
            gap_horizontal: 2,
            gap_vertical: 0,
            margin_top: 10,
            margin_bottom: 10,
            margin_left: 7,
            margin_right: 7,
        },

        get labelSize() {
            const usableWidth = this.form.paper_width - this.form.margin_left - this.form.margin_right;
            const usableHeight = this.form.paper_height - this.form.margin_top - this.form.margin_bottom;

            const totalGapH = (this.form.columns - 1) * this.form.gap_horizontal;
            const totalGapV = (this.form.rows - 1) * this.form.gap_vertical;

            const labelWidth = ((usableWidth - totalGapH) / this.form.columns).toFixed(1);
            const labelHeight = ((usableHeight - totalGapV) / this.form.rows).toFixed(1);

            return `${labelWidth} x ${labelHeight} ${this.form.paper_unit}`;
        },

        updatePreview() {
            // Force Alpine to re-render
            this.$nextTick();
        }
    }
}
</script>
@endpush
@endsection
