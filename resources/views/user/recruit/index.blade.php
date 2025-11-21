@extends('layouts.user-arrow-x')

@section('title', 'หน้าแนะนำสมาชิก')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-20 lg:pb-6" x-data="recruitPageManager()">

    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-3xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-user-friends mr-3"></i>หน้าแนะนำสมาชิก
                </h1>
                <p class="text-purple-100">จัดการข้อมูลและดูสถิติหน้า Recruit ของคุณ</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ $recruitUrl }}" target="_blank"
                   class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl font-semibold transition">
                    <i class="fas fa-external-link-alt mr-2"></i>ดูหน้า Recruit
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Views -->
        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
                    <i class="fas fa-eye text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <span class="text-sm text-green-600 dark:text-green-400 font-semibold">
                    +{{ $stats['views_last_7_days'] }} ใน 7 วัน
                </span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['total_views']) }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                การเข้าชมทั้งหมด
            </div>
        </div>

        <!-- Total Leads -->
        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-clock text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <span class="text-sm text-green-600 dark:text-green-400 font-semibold">
                    +{{ $stats['leads_last_7_days'] }} ใน 7 วัน
                </span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['total_leads']) }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                ผู้มุ่งหวัง (Leads)
            </div>
        </div>

        <!-- Total Conversions -->
        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-check text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <span class="text-sm text-blue-600 dark:text-blue-400 font-semibold">
                    สมาชิกใหม่
                </span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['total_conversions']) }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                คนสมัครสำเร็จ
            </div>
        </div>

        <!-- Conversion Rate -->
        <x-arrow-x.card-v3 class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                    อัตราสำเร็จ
                </span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['conversion_rate'], 1) }}%
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Conversion Rate
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            <i class="fas fa-edit text-purple-600 mr-3"></i>แก้ไขข้อมูลส่วนตัว
        </h2>

        <form action="{{ route('user.marketing.recruit.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid md:grid-cols-2 gap-6">

                <!-- Custom Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user mr-2 text-purple-600"></i>ชื่อที่แสดง
                    </label>
                    <input type="text" name="custom_name"
                           value="{{ old('custom_name', $customization->custom_name) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                           placeholder="ชื่อที่ต้องการให้แสดง (ถ้าไม่กรอกจะใช้ชื่อจากโปรไฟล์)">
                    @error('custom_name')
                        <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <!-- Custom Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-phone mr-2 text-blue-600"></i>เบอร์โทรติดต่อ
                    </label>
                    <input type="text" name="custom_phone"
                           value="{{ old('custom_phone', $customization->custom_phone) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                           placeholder="เบอร์โทรสำหรับให้ผู้สนใจติดต่อ">
                    @error('custom_phone')
                        <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <!-- Custom Address -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>ที่อยู่
                    </label>
                    <textarea name="custom_address" rows="3"
                              class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                              placeholder="ที่อยู่ที่ต้องการแสดง">{{ old('custom_address', $customization->custom_address) }}</textarea>
                    @error('custom_address')
                        <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <!-- Custom Pitch -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-comment-dots mr-2 text-green-600"></i>คำชักชวนส่วนตัว
                    </label>
                    <textarea name="custom_pitch" rows="5"
                              class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                              placeholder="เขียนคำชักชวนของคุณเอง (ถ้าไม่กรอกจะใช้ข้อความจากเทมเพลต)">{{ old('custom_pitch', $customization->custom_pitch) }}</textarea>
                    @error('custom_pitch')
                        <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <!-- Custom Image -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-image mr-2 text-pink-600"></i>รูปภาพ
                    </label>

                    @if($customization->custom_image)
                    <div class="mb-3">
                        <img src="{{ $customization->getDisplayImage() }}"
                             alt="Current Image"
                             class="w-32 h-32 rounded-xl object-cover border-2 border-gray-200 dark:border-gray-700">
                    </div>
                    @endif

                    <input type="file" name="custom_image" accept="image/*"
                           class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">รองรับไฟล์: JPG, PNG, GIF (สูงสุด 2MB)</p>
                    @error('custom_image')
                        <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Active -->
                <div class="md:col-span-2">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                               {{ $customization->is_active ? 'checked' : '' }}
                               class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="fas fa-toggle-on text-green-600 mr-2"></i>เปิดใช้งานหน้า Recruit
                        </span>
                    </label>
                </div>

            </div>

            <!-- Submit Button -->
            <div class="mt-8 flex gap-4">
                <button type="submit"
                        class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-bold text-lg hover:from-purple-700 hover:to-pink-700 transition-transform hover:scale-[1.02] transition-all duration-300 shadow-lg">
                    <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                </button>

                <a href="{{ route('user.marketing.recruit.leads') }}"
                   class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-bold text-lg hover:from-blue-700 hover:to-cyan-700 transition-transform hover:scale-[1.02] transition-all duration-300 shadow-lg">
                    <i class="fas fa-users mr-2"></i>ดูผู้มุ่งหวัง
                </a>

                <a href="{{ route('user.marketing.recruit.analytics') }}"
                   class="px-8 py-4 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-xl font-bold text-lg hover:from-green-700 hover:to-teal-700 transition-transform hover:scale-[1.02] transition-all duration-300 shadow-lg">
                    <i class="fas fa-chart-bar mr-2"></i>สถิติการตลาด
                </a>
            </div>
        </form>
    </div>

    <!-- QR Code & Links Section -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            <i class="fas fa-qrcode text-blue-600 mr-3"></i>QR Code และลิงก์แนะนำ
        </h2>

        <div class="grid md:grid-cols-2 gap-8">

            <!-- QR Code -->
            <div class="text-center">
                <div class="inline-block bg-white p-6 rounded-2xl shadow-lg mb-4">
                    <div id="qrcode"></div>
                </div>
                <button @click="downloadQR()"
                        class="px-6 py-3 bg-gray-700 hover:bg-gray-800 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-download mr-2"></i>ดาวน์โหลด QR Code
                </button>
            </div>

            <!-- Links -->
            <div class="space-y-4">
                <!-- Recruit URL -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ลิงก์หน้า Recruit:
                    </label>
                    <div class="flex gap-3">
                        <input type="text" id="recruitUrl" value="{{ $recruitUrl }}" readonly
                               class="flex-1 px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl dark:bg-gray-700 dark:text-white font-mono text-sm">
                        <button @click="copyUrl('recruitUrl')"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <!-- Member Code -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        รหัสสมาชิก:
                    </label>
                    <div class="flex gap-3">
                        <input type="text" id="memberCode" value="{{ $mlmMember->member_code }}" readonly
                               class="flex-1 px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl dark:bg-gray-700 dark:text-white font-mono text-2xl font-bold text-center">
                        <button @click="copyUrl('memberCode')"
                                class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <!-- Share Buttons -->
                <div class="pt-4">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">แชร์ผ่าน:</p>
                    <div class="flex gap-3">
                        <button @click="shareVia('line')"
                                class="flex-1 px-4 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl font-semibold transition">
                            <i class="fab fa-line mr-2"></i>LINE
                        </button>
                        <button @click="shareVia('facebook')"
                                class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition">
                            <i class="fab fa-facebook mr-2"></i>Facebook
                        </button>
                        <button @click="shareVia('twitter')"
                                class="flex-1 px-4 py-3 bg-sky-500 hover:bg-sky-600 text-white rounded-xl font-semibold transition">
                            <i class="fab fa-twitter mr-2"></i>Twitter
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function recruitPageManager() {
    return {
        recruitUrl: '{{ $recruitUrl }}',
        memberCode: '{{ $mlmMember->member_code }}',
        qrcode: null,

        init() {
            // Generate QR Code
            this.qrcode = new QRCode(document.getElementById("qrcode"), {
                text: this.recruitUrl,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
            });
        },

        copyUrl(elementId) {
            const input = document.getElementById(elementId);
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                alert('✅ คัดลอกแล้ว!');
            });
        },

        downloadQR() {
            const canvas = document.querySelector('#qrcode canvas');
            const url = canvas.toDataURL("image/png");
            const link = document.createElement('a');
            link.download = `recruit-qr-${this.memberCode}.png`;
            link.href = url;
            link.click();
        },

        shareVia(platform) {
            const message = `มาร่วมเป็นส่วนหนึ่งกับทีมของฉัน! 🚀`;

            switch(platform) {
                case 'line':
                    window.open(`https://line.me/R/msg/text/?${encodeURIComponent(message + '\n' + this.recruitUrl)}`, '_blank');
                    break;
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(this.recruitUrl)}`, '_blank');
                    break;
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}&url=${encodeURIComponent(this.recruitUrl)}`, '_blank');
                    break;
            }
        }
    }
}
</script>
@endpush

@endsection
