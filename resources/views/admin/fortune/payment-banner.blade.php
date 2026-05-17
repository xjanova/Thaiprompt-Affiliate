@extends('layouts.admin-v3')

@section('title', '🎨 Payment Banner')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="paymentBannerEditor()">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            🎨 Payment Banner — QR + ลายธนาคาร
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            ฝัง Dynamic QR ในภาพ banner ธนาคาร → ลด FB detection + filename hash ต่างทุกบิล
        </p>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error messages --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <ul class="text-red-800 dark:text-red-200 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- LEFT: Settings Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                ⚙️ ตั้งค่า Banner
            </h2>

            <form method="POST" action="{{ route('admin.fortune.payment-banner.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                {{-- Toggle --}}
                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="payment_banner_enabled" value="1"
                               class="w-5 h-5 rounded text-purple-600"
                               {{ $settings->isPaymentBannerEnabled() ? 'checked' : '' }}>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">เปิดใช้ Banner Composite</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ปิด = ส่ง QR เปลือยเหมือนเดิม</div>
                        </div>
                    </label>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Banner upload --}}
                <div>
                    <label class="block font-semibold text-gray-900 dark:text-white mb-2">
                        📤 Upload Banner Template (PNG/JPG, สูงสุด 4MB)
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        ⭐ แนะนำขนาด <strong>1200x1600px</strong> — ระบบจะวาด QR + ยอด+bill ทับลงไป
                        <br>ปล่อยว่าง = ใช้ default ที่ระบบ generate (ม่วงเข้ม + ดาว + Thai font)
                    </p>

                    {{-- Download template button --}}
                    <div class="mb-3">
                        <a href="{{ route('admin.fortune.payment-banner.download') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-sm font-semibold rounded-md transition">
                            📥 ดาวน์โหลด Template ปัจจุบัน
                        </a>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            💡 โหลด → แก้ใน Photoshop/Figma → upload กลับ → ตำแหน่ง QR ตรงกัน
                        </p>
                    </div>

                    <input type="file" name="payment_banner_image" accept="image/png,image/jpeg"
                           class="block w-full text-sm text-gray-600 dark:text-gray-300
                                  file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                  file:bg-purple-100 dark:file:bg-purple-900 file:text-purple-700 dark:file:text-purple-300
                                  hover:file:bg-purple-200">
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- QR position + size --}}
                <div>
                    <label class="block font-semibold text-gray-900 dark:text-white mb-3">
                        📐 ตำแหน่ง + ขนาด QR ใน Banner
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">X (px)</label>
                            <input type="number" name="payment_banner_qr_x" min="0" max="2000"
                                   value="{{ $settings->payment_banner_qr_x ?? 100 }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Y (px)</label>
                            <input type="number" name="payment_banner_qr_y" min="0" max="2000"
                                   value="{{ $settings->payment_banner_qr_y ?? 150 }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Size (px)</label>
                            <input type="number" name="payment_banner_qr_size" min="100" max="1000"
                                   value="{{ $settings->payment_banner_qr_size ?? 400 }}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        💡 X=0, Y=0 = ระบบคำนวณกลางอัตโนมัติ
                    </p>
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                {{-- Buttons --}}
                <div class="flex flex-wrap gap-3">
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white font-semibold rounded-lg transition">
                        💾 บันทึก
                    </button>
                </div>
            </form>

            {{-- Reset button (separate form to avoid validation conflict) --}}
            <form method="POST" action="{{ route('admin.fortune.payment-banner.reset') }}"
                  onsubmit="return confirm('รีเซ็ตเป็น banner default ของระบบใช่ไหม?');"
                  class="mt-3">
                @csrf
                <button type="submit"
                        class="w-full px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold rounded-lg transition text-sm">
                    🔄 รีเซ็ตเป็น Banner Default ของระบบ
                </button>
            </form>
        </div>

        {{-- RIGHT: Preview --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                👀 Preview
            </h2>

            {{-- Current Template --}}
            <div class="mb-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    🖼️ Template ปัจจุบัน
                </h3>
                @if ($customBannerUrl)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">📌 Admin Upload</div>
                    <img src="{{ $customBannerUrl }}" alt="Custom Banner"
                         class="w-full rounded-lg border-2 border-purple-300 dark:border-purple-700">
                @elseif ($defaultBannerUrl)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">📌 Default (ระบบ generate)</div>
                    <img src="{{ $defaultBannerUrl }}" alt="Default Banner"
                         class="w-full rounded-lg border-2 border-gray-300 dark:border-gray-600">
                @else
                    <div class="text-sm text-red-600 dark:text-red-400 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        ⚠️ ยังไม่มี template — ลองกด "บันทึก" หรือ "ทดสอบ Preview"
                    </div>
                @endif
            </div>

            <hr class="border-gray-200 dark:border-gray-700 my-4">

            {{-- Test Preview --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    🧪 ทดสอบ Composite (banner + QR + ยอด)
                </h3>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">PromptPay ID</label>
                        <input type="text" x-model="testPromptpay"
                               placeholder="0066885782958"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">ยอด (บาท)</label>
                        <input type="number" step="0.01" x-model="testAmount"
                               placeholder="39.07"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">ธนาคาร</label>
                        <input type="text" x-model="testBank"
                               placeholder="กสิกรไทย"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">เลขบัญชี</label>
                        <input type="text" x-model="testAccount"
                               placeholder="066-8-85782958"
                               class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md">
                    </div>
                </div>

                <button @click="generatePreview()" :disabled="loading"
                        class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-semibold rounded-lg transition disabled:opacity-50">
                    <span x-show="!loading">🧪 ทดสอบ Generate</span>
                    <span x-show="loading">⏳ กำลังสร้าง...</span>
                </button>

                {{-- Result --}}
                <div x-show="previewUrl" class="mt-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">✅ ผลลัพธ์ที่ลูกค้าจะได้รับ</div>
                    <img :src="previewUrl" alt="Preview" class="w-full rounded-lg border-2 border-green-400 shadow-lg">
                    <div class="mt-2 text-xs">
                        <a :href="previewUrl" target="_blank"
                           class="text-blue-600 dark:text-blue-400 hover:underline">
                            🔗 เปิดในแท็บใหม่
                        </a>
                    </div>
                </div>

                <div x-show="error" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-sm">
                    ⚠️ <span x-text="error"></span>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700 my-4">

            {{-- Info --}}
            <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-3 rounded-md space-y-1">
                <p>📌 <strong>Anti-FB-detection:</strong> ฝัง QR ใน design ดูเหมือน promotional graphic → ลด suspicion</p>
                <p>📌 <strong>Dynamic QR ทุกครั้ง:</strong> filename hash ของ bill+amount+time → ภาพไม่ซ้ำ → FB perceptual hash ไม่ match</p>
                <p>📌 <strong>SMS Checker:</strong> ยอด+ทศนิยมยังคงเดิม → จับคู่บิลปกติ</p>
            </div>
        </div>
    </div>
</div>

<script>
function paymentBannerEditor() {
    return {
        testPromptpay: '{{ $settings->promptpay_id ?? "0066885782958" }}',
        testAmount: 39.07,
        testBank: 'กสิกรไทย',
        testAccount: '066-8-85782958',
        loading: false,
        previewUrl: null,
        error: null,

        async generatePreview() {
            this.loading = true;
            this.error = null;
            this.previewUrl = null;

            try {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('test_promptpay', this.testPromptpay);
                formData.append('test_amount', this.testAmount);
                formData.append('test_bank', this.testBank);
                formData.append('test_account', this.testAccount);

                const res = await fetch('{{ route("admin.fortune.payment-banner.preview") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    // Cache-bust
                    this.previewUrl = data.preview_url + '?t=' + Date.now();
                } else {
                    this.error = data.error || 'สร้าง preview ไม่สำเร็จ';
                }
            } catch (e) {
                this.error = 'เครือข่ายขัดข้อง: ' + e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
