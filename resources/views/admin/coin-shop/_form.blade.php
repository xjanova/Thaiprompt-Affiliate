{{--
    Form Partial สำหรับสร้าง/แก้ไขสินค้า
--}}

@php
    $product = $product ?? null;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main Content --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Basic Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>

            <div class="space-y-4">
                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่อสินค้า (EN) <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $product?->name) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white @error('name') border-red-500 @enderror"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Name TH --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่อสินค้า (TH)
                    </label>
                    <input type="text"
                           name="name_th"
                           value="{{ old('name_th', $product?->name_th) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        รายละเอียด (EN)
                    </label>
                    <textarea name="description"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">{{ old('description', $product?->description) }}</textarea>
                </div>

                {{-- Description TH --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        รายละเอียด (TH)
                    </label>
                    <textarea name="description_th"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">{{ old('description_th', $product?->description_th) }}</textarea>
                </div>

                {{-- Type & Category --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ประเภทสินค้า <span class="text-red-500">*</span>
                        </label>
                        <select name="type"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                required>
                            @foreach($types as $key => $typeInfo)
                                <option value="{{ $key }}" {{ old('type', $product?->type) === $key ? 'selected' : '' }}>
                                    {{ $typeInfo['icon'] }} {{ $typeInfo['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            หมวดหมู่
                        </label>
                        <input type="text"
                               name="category"
                               value="{{ old('category', $product?->category) }}"
                               placeholder="เช่น อาหาร, ช้อปปิ้ง, บันเทิง"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>

                {{-- Icon --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ไอคอน (Emoji)
                    </label>
                    <input type="text"
                           name="icon"
                           value="{{ old('icon', $product?->icon) }}"
                           placeholder="🎁"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                           maxlength="10">
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ราคา</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Price Coins --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ราคา (Coins) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="price_coins"
                           value="{{ old('price_coins', $product?->price_coins) }}"
                           min="0"
                           step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white @error('price_coins') border-red-500 @enderror"
                           required>
                    @error('price_coins')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Original Price --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ราคาเดิม (ถ้ามีลด)
                    </label>
                    <input type="number"
                           name="original_price_coins"
                           value="{{ old('original_price_coins', $product?->original_price_coins) }}"
                           min="0"
                           step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                {{-- Price Money --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        มูลค่าเทียบเท่า (บาท)
                    </label>
                    <input type="number"
                           name="price_money"
                           value="{{ old('price_money', $product?->price_money) }}"
                           min="0"
                           step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            {{-- Value --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        มูลค่าสินค้า
                    </label>
                    <input type="number"
                           name="value_amount"
                           value="{{ old('value_amount', $product?->value_amount) }}"
                           min="0"
                           step="0.01"
                           placeholder="เช่น 100, 500"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        หน่วย
                    </label>
                    <input type="text"
                           name="value_unit"
                           value="{{ old('value_unit', $product?->value_unit) }}"
                           placeholder="เช่น บาท, %, วัน, TPIX"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        {{-- Stock & Limits --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สต็อก & จำกัด</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        จำนวนสต็อก
                    </label>
                    <input type="number"
                           name="stock_quantity"
                           value="{{ old('stock_quantity', $product?->stock_quantity) }}"
                           min="0"
                           placeholder="ว่างไว้ = ไม่จำกัด"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">ว่างไว้ = ไม่จำกัด</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        จำกัดต่อคน
                    </label>
                    <input type="number"
                           name="max_per_user"
                           value="{{ old('max_per_user', $product?->max_per_user) }}"
                           min="1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        จำกัดต่อวัน
                    </label>
                    <input type="number"
                           name="max_per_day"
                           value="{{ old('max_per_day', $product?->max_per_day) }}"
                           min="1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Level ขั้นต่ำ
                    </label>
                    <input type="number"
                           name="min_level"
                           value="{{ old('min_level', $product?->min_level ?? 1) }}"
                           min="1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Rank ขั้นต่ำ
                    </label>
                    <select name="min_rank_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">ไม่จำกัด</option>
                        @foreach($ranks as $rank)
                            <option value="{{ $rank->id }}" {{ old('min_rank_id', $product?->min_rank_id) == $rank->id ? 'selected' : '' }}>
                                {{ $rank->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        หมดอายุหลังซื้อ (วัน)
                    </label>
                    <input type="number"
                           name="use_expire_days"
                           value="{{ old('use_expire_days', $product?->use_expire_days) }}"
                           min="1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        {{-- Sale Period --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ช่วงเวลาขาย</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        เริ่มขาย
                    </label>
                    <input type="datetime-local"
                           name="sale_start_at"
                           value="{{ old('sale_start_at', $product?->sale_start_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        สิ้นสุดขาย
                    </label>
                    <input type="datetime-local"
                           name="sale_end_at"
                           value="{{ old('sale_end_at', $product?->sale_end_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Thumbnail --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รูปสินค้า</h3>

            @if($product?->thumbnail)
                <div class="mb-4">
                    <img src="{{ $product->thumbnail_url }}" alt="Current thumbnail" class="w-full rounded-lg">
                </div>
            @endif

            <input type="file"
                   name="thumbnail"
                   accept="image/*"
                   class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-200 dark:hover:file:bg-gray-600">
            <p class="mt-2 text-xs text-gray-500">แนะนำ: 400x400px, สูงสุด 2MB</p>
        </div>

        {{-- Status & Flags --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะ</h3>

            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', $product?->is_active ?? true) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <span class="text-gray-700 dark:text-gray-300">เปิดขาย</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_featured"
                           value="1"
                           {{ old('is_featured', $product?->is_featured) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                    <span class="text-gray-700 dark:text-gray-300">แนะนำ (Featured)</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_new"
                           value="1"
                           {{ old('is_new', $product?->is_new) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-gray-700 dark:text-gray-300">สินค้าใหม่</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_limited"
                           value="1"
                           {{ old('is_limited', $product?->is_limited) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span class="text-gray-700 dark:text-gray-300">Limited Edition</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="require_kyc"
                           value="1"
                           {{ old('require_kyc', $product?->require_kyc) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    <span class="text-gray-700 dark:text-gray-300">ต้อง KYC</span>
                </label>
            </div>
        </div>

        {{-- Display Order --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ลำดับการแสดง</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Priority (ยิ่งสูงยิ่งแสดงก่อน)
                    </label>
                    <input type="number"
                           name="priority"
                           value="{{ old('priority', $product?->priority ?? 0) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Sort Order (ลำดับการแสดง)
                    </label>
                    <input type="number"
                           name="sort_order"
                           value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>
    </div>
</div>
