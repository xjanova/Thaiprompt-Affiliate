# Admin Product Edit View - การปรับปรุง

## การเปลี่ยนแปลงที่ต้องทำใน `resources/views/admin/ecommerce/products/edit.blade.php`

### 1. เพิ่มข้อมูลเจ้าของสินค้า (Seller Info) - หลังบรรทัด 100

```blade
{{-- Seller Information Card --}}
<div class="glass-fusion dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 mb-6 shadow-lg" border border-white/20 dark:border-white/10>
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                {{ substr($product->seller->name ?? 'N', 0, 1) }}
            </div>
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                ข้อมูลร้านค้า/เจ้าของ
            </h3>
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span><strong>ชื่อ:</strong> {{ $product->seller->name ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span><strong>Email:</strong> {{ $product->seller->email ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span><strong>Seller ID:</strong> #{{ $product->seller_id }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Block Status Alert (ถ้าถูกบล็อก) --}}
@if($product->is_blocked)
<div class="glass-fusion dark:bg-red-900/20 border border-red-500 dark:border-red-400 rounded-2xl p-6 mb-6 shadow-lg" border border-white/20 dark:border-white/10>
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0">
            <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-bold text-red-800 dark:text-red-200 mb-2">
                ⚠️ สินค้านี้ถูกบล็อกแล้ว
            </h3>
            <div class="space-y-2 text-sm text-red-700 dark:text-red-300">
                <p><strong>เหตุผล:</strong> {{ $product->block_reason }}</p>
                <p><strong>บล็อกเมื่อ:</strong> {{ $product->blocked_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                <p><strong>บล็อกโดย:</strong> {{ $product->blockedByUser->name ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>
@endif
```

### 2. เพิ่มฟิลด์ PV และ Cashback - หลังบรรทัด 399 (หลัง commission_rate)

```blade
{{-- PV Value --}}
<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        <span class="flex items-center gap-2">
            ⭐ PV (Point Value)
            <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">(สำหรับระบบ MLM)</span>
        </span>
    </label>
    <div class="relative">
        <input type="number"
               name="pv_value"
               value="{{ old('pv_value', $product->pv_value ?? 0) }}"
               step="0.01"
               min="0"
               placeholder="0"
               class="w-full pl-4 pr-16 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-200 dark:focus:ring-purple-900/30 transition-all">
        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold text-sm">PV</span>
    </div>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        💡 ถ้าไม่กำหนด ระบบจะใช้ราคาสินค้าเป็น PV
    </p>
</div>

{{-- Cashback Percentage --}}
<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
        <span class="flex items-center gap-2">
            🎁 Cashback Percentage
            <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">(เงินคืนให้ลูกค้า)</span>
        </span>
    </label>
    <div class="relative">
        <input type="number"
               name="cashback_percentage"
               value="{{ old('cashback_percentage', $product->cashback_percentage ?? 0) }}"
               step="0.01"
               min="0"
               max="100"
               placeholder="0"
               class="w-full pl-4 pr-10 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">%</span>
    </div>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        💡 เปอร์เซ็นต์เงินคืนที่ลูกค้าจะได้รับ (จากร้านค้า)
    </p>
</div>
```

### 3. เพิ่มปุ่ม Block/Unblock - ก่อน Submit Button (หาบรรทัดที่มี "บันทึกการเปลี่ยนแปลง")

```blade
{{-- Block/Unblock Section --}}
<div class="mt-8 pt-6 border-t-2 border-gray-200 dark:border-gray-700">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
        การจัดการสถานะสินค้า (Admin)
    </h3>

    @if($product->is_blocked)
        {{-- Unblock Button --}}
        <form action="{{ route('admin.ecommerce.products.unblock', $product) }}" method="POST"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปลดบล็อกสินค้านี้?')">
            @csrf
            <button type="submit"
                    class="w-full md:w-auto px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
                ปลดบล็อกสินค้า
            </button>
        </form>
    @else
        {{-- Block Button with Modal --}}
        <div x-data="{ showBlockModal: false }">
            <button @click="showBlockModal = true"
                    type="button"
                    class="w-full md:w-auto px-6 py-3 bg-gradient-to-r from-red-500 to-orange-600 hover:from-red-600 hover:to-orange-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                บล็อกสินค้านี้
            </button>

            {{-- Block Modal --}}
            <div x-show="showBlockModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 overflow-y-auto"
                 style="display: none;">

                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showBlockModal = false"></div>

                {{-- Modal Content --}}
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative w-full max-w-lg bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6"
                         @click.away="showBlockModal = false">

                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            🚫 บล็อกสินค้า
                        </h3>

                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            กรุณาระบุเหตุผลในการบล็อกสินค้านี้ ระบบจะส่งการแจ้งเตือนให้ร้านค้าทราบทันที
                        </p>

                        <form action="{{ route('admin.ecommerce.products.block', $product) }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    เหตุผล <span class="text-red-500">*</span>
                                </label>
                                <textarea name="block_reason"
                                          required
                                          rows="4"
                                          class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-red-500 dark:focus:border-red-400 focus:ring-2 focus:ring-red-200 dark:focus:ring-red-900/30 transition-all"
                                          placeholder="เช่น: สินค้าละเมิดลิขสิทธิ์, รูปภาพไม่เหมาะสม, ข้อมูลไม่ถูกต้อง..."></textarea>
                            </div>

                            <div class="flex gap-3">
                                <button type="button"
                                        @click="showBlockModal = false"
                                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                    ยกเลิก
                                </button>
                                <button type="submit"
                                        class="flex-1 px-4 py-2 bg-gradient-to-r from-red-500 to-orange-600 hover:from-red-600 hover:to-orange-700 text-white font-bold rounded-xl transition-all">
                                    ยืนยันการบล็อก
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        ⚠️ <strong>หมายเหตุ:</strong> เมื่อบล็อกสินค้า ระบบจะปิดการแสดงสินค้าอัตโนมัติและแจ้งเตือนร้านค้าทันที
    </p>
</div>
```

### 4. อัปเดต Submit Button Section - เพิ่ม spacing

หาบรรทัดที่มี "บันทึกการเปลี่ยนแปลง" และเพิ่ม `mt-8` class

---

## ไฟล์ที่ต้องแก้ไข

1. `/home/user/Thaiprompt-Affiliate/resources/views/admin/ecommerce/products/edit.blade.php`

## การทดสอบ

1. เปิดหน้า Edit Product ในฐานะ Admin
2. ตรวจสอบว่าเห็น:
   - ข้อมูลเจ้าของสินค้า
   - ฟิลด์ PV (default 0)
   - ฟิลด์ Cashback (default 0)
   - ปุ่ม Block/Unblock
3. ทดสอบบล็อกสินค้า พร้อมกรอกเหตุผล
4. ตรวจสอบว่าแสดง Badge "ถูกบล็อก" หลังบล็อกแล้ว
5. ทดสอบปลดบล็อก
