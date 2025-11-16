@extends('layouts.user-arrow-x')

@section('title', 'ส่งเอกสารยืนยันตัวตน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('user.kyc.index') }}"
               class="text-gray-600 hover:text-gray-900 transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">ส่งเอกสารยืนยันตัวตน</h1>
                <p class="text-sm text-gray-600 mt-1">กรุณาอัปโหลดเอกสารเพื่อยืนยันตัวตน</p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-2"></i>
                <div>
                    <h3 class="font-semibold text-red-800 mb-2">พบข้อผิดพลาด:</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Example Images Card -->
    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 border-2 border-indigo-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-indigo-900 mb-4 flex items-center">
            <i class="fas fa-images mr-2"></i>ตัวอย่างการถ่ายรูป
        </h3>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- ID Card Example -->
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <h4 class="font-semibold text-indigo-900 mb-3 text-center">
                    <i class="fas fa-id-card text-indigo-600 mr-2"></i>รูปบัตรประชาชน / ใบขับขี่
                </h4>
                <div class="bg-gray-100 rounded-lg p-6 mb-3 border-2 border-dashed border-gray-300">
                    <svg class="w-full h-32" viewBox="0 0 320 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="320" height="180" rx="8" fill="white"/>
                        <rect x="10" y="10" width="300" height="160" rx="4" fill="#E5E7EB"/>
                        <text x="160" y="30" text-anchor="middle" font-size="14" fill="#374151" font-weight="bold">บัตรประจำตัวประชาชน</text>
                        <rect x="20" y="45" width="60" height="80" rx="4" fill="#D1D5DB"/>
                        <text x="50" y="90" text-anchor="middle" font-size="24" fill="#6B7280">👤</text>
                        <rect x="90" y="50" width="120" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="90" y="65" width="100" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="90" y="80" width="80" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="90" y="95" width="110" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="20" y="135" width="280" height="25" rx="4" fill="#3B82F6" fill-opacity="0.2"/>
                        <text x="160" y="152" text-anchor="middle" font-size="12" fill="#1E40AF">เลขบัตรประชาชน 13 หลัก</text>
                    </svg>
                </div>
                <div class="space-y-1 text-xs text-gray-700">
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                        <span>ถ่ายทั้งหน้าบัตรให้เห็นชัดเจน</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                        <span>ข้อความและตัวเลขต้องอ่านได้</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                        <span>แสงสว่างเพียงพอ ไม่มืด ไม่เบลอ</span>
                    </div>
                </div>
            </div>

            <!-- Selfie with ID Example -->
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <h4 class="font-semibold text-indigo-900 mb-3 text-center">
                    <i class="fas fa-user-circle text-indigo-600 mr-2"></i>รูปถ่ายตัวเอง + บัตร
                </h4>
                <div class="bg-gray-100 rounded-lg p-6 mb-3 border-2 border-dashed border-gray-300">
                    <svg class="w-full h-32" viewBox="0 0 320 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="320" height="180" rx="8" fill="white"/>
                        <rect x="10" y="10" width="300" height="160" rx="4" fill="#E5E7EB"/>
                        <!-- Face -->
                        <circle cx="160" cy="70" r="35" fill="#FDE68A"/>
                        <circle cx="150" cy="65" r="3" fill="#374151"/>
                        <circle cx="170" cy="65" r="3" fill="#374151"/>
                        <path d="M 150 80 Q 160 85 170 80" stroke="#374151" stroke-width="2" fill="none"/>
                        <!-- ID Card in hand -->
                        <rect x="190" y="95" width="90" height="55" rx="3" fill="#BFDBFE" stroke="#3B82F6" stroke-width="2"/>
                        <text x="235" y="115" text-anchor="middle" font-size="8" fill="#1E40AF">บัตรประชาชน</text>
                        <rect x="200" y="120" width="20" height="25" rx="2" fill="#93C5FD"/>
                        <rect x="225" y="123" width="35" height="3" rx="1" fill="#93C5FD"/>
                        <rect x="225" y="130" width="30" height="3" rx="1" fill="#93C5FD"/>
                    </svg>
                </div>
                <div class="space-y-1 text-xs text-gray-700">
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                        <span>ถือบัตรไว้ใกล้ใบหน้า</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                        <span>ใบหน้าและบัตรต้องชัดเจนทั้งคู่</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-600 mr-2 mt-0.5"></i>
                        <span>ไม่ปิดบังใบหน้าด้วยหมวกหรือแว่น</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-xs text-yellow-800 flex items-start">
                <i class="fas fa-lightbulb mr-2 mt-0.5 text-yellow-600"></i>
                <span><strong>เคล็ดลับ:</strong> รูปจะถูกแปลงเป็น WebP อัตโนมัติเพื่อประหยัดพื้นที่และเพิ่มความเร็ว</span>
            </p>
        </div>
    </div>

    <!-- Instructions Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-900 mb-4">
            <i class="fas fa-clipboard-check mr-2"></i>เงื่อนไขและข้อกำหนด
        </h3>
        <div class="space-y-3">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                <div>
                    <p class="font-semibold text-blue-900">บัตรประชาชน หรือ ใบขับขี่ ต้องไม่หมดอายุ</p>
                    <p class="text-sm text-blue-700">ตรวจสอบวันหมดอายุบนบัตร</p>
                </div>
            </div>
            <div class="flex items-start">
                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                <div>
                    <p class="font-semibold text-blue-900">รองรับไฟล์: JPEG, JPG, PNG</p>
                    <p class="text-sm text-blue-700">ขนาดไม่เกิน 5 MB ต่อไฟล์</p>
                </div>
            </div>
            <div class="flex items-start">
                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                <div>
                    <p class="font-semibold text-blue-900">รูปถ่ายต้องชัดเจน ไม่มืด ไม่เบลอ</p>
                    <p class="text-sm text-blue-700">ควรถ่ายในที่แสงสว่างเพียงพอ</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-xl shadow-md p-6" x-data="kycUpload()">
        <form action="{{ route('user.kyc.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- ID Card Image -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-id-card mr-1"></i>
                    รูปบัตรประชาชน / ใบขับขี่ <span class="text-red-500">*</span>
                </label>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-500 transition"
                     @click="$refs.idCardInput.click()">
                    <template x-if="!idCardPreview">
                        <div>
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-600 mb-2">คลิกเพื่ออัปโหลดรูปบัตรประชาชน หรือ ใบขับขี่</p>
                            <p class="text-xs text-gray-500">JPEG, JPG, PNG (ไม่เกิน 5 MB)</p>
                        </div>
                    </template>

                    <template x-if="idCardPreview">
                        <div>
                            <img :src="idCardPreview" class="max-h-64 mx-auto rounded-lg mb-3" alt="ID Card Preview">
                            <button type="button"
                                    @click.stop="removeIdCard()"
                                    class="text-sm text-red-600 hover:text-red-800">
                                <i class="fas fa-trash mr-1"></i>ลบรูป
                            </button>
                        </div>
                    </template>
                </div>

                <input type="file"
                       name="id_card_image"
                       id="id_card_image"
                       accept="image/jpeg,image/jpg,image/png"
                       class="hidden"
                       x-ref="idCardInput"
                       @change="previewIdCard($event)"
                       required>

                @error('id_card_image')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Selfie Image -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user-circle mr-1"></i>
                    รูปถ่ายตัวเองพร้อมบัตรประชาชน <span class="text-red-500">*</span>
                </label>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-500 transition"
                     @click="$refs.selfieInput.click()">
                    <template x-if="!selfiePreview">
                        <div>
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-600 mb-2">คลิกเพื่ออัปโหลดรูปถ่ายตัวเอง</p>
                            <p class="text-xs text-gray-500">JPEG, JPG, PNG (ไม่เกิน 5 MB)</p>
                        </div>
                    </template>

                    <template x-if="selfiePreview">
                        <div>
                            <img :src="selfiePreview" class="max-h-64 mx-auto rounded-lg mb-3" alt="Selfie Preview">
                            <button type="button"
                                    @click.stop="removeSelfie()"
                                    class="text-sm text-red-600 hover:text-red-800">
                                <i class="fas fa-trash mr-1"></i>ลบรูป
                            </button>
                        </div>
                    </template>
                </div>

                <input type="file"
                       name="selfie_image"
                       id="selfie_image"
                       accept="image/jpeg,image/jpg,image/png"
                       class="hidden"
                       x-ref="selfieInput"
                       @change="previewSelfie($event)"
                       required>

                @error('selfie_image')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <a href="{{ route('user.kyc.index') }}"
                   class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center">
                    <i class="fas fa-times mr-2"></i>ยกเลิก
                </a>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-primary text-white rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-paper-plane mr-2"></i>ส่งเอกสาร
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function kycUpload() {
    return {
        idCardPreview: null,
        selfiePreview: null,

        previewIdCard(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.idCardPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        previewSelfie(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.selfiePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        removeIdCard() {
            this.idCardPreview = null;
            this.$refs.idCardInput.value = '';
        },

        removeSelfie() {
            this.selfiePreview = null;
            this.$refs.selfieInput.value = '';
        }
    };
}
</script>
@endpush
@endsection
