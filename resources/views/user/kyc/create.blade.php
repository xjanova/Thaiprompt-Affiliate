@extends('layouts.user')

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

    <!-- Instructions Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-900 mb-4">
            <i class="fas fa-info-circle mr-2"></i>คำแนะนำในการอัปโหลดเอกสาร
        </h3>
        <div class="space-y-4">
            <div>
                <h4 class="font-semibold text-blue-900 mb-2">รูปบัตรประชาชน:</h4>
                <ul class="space-y-1 text-sm text-blue-800 ml-4">
                    <li>• ถ่ายรูปบัตรประชาชนให้ชัดเจน ไม่มืด ไม่เบลอ</li>
                    <li>• แสดงรายละเอียดในบัตรให้เห็นทุกส่วน</li>
                    <li>• บัตรต้องไม่หมดอายุ</li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold text-blue-900 mb-2">รูปถ่ายตัวเองพร้อมบัตรประชาชน:</h4>
                <ul class="space-y-1 text-sm text-blue-800 ml-4">
                    <li>• ถ่ายรูปตัวเองพร้อมถือบัตรประชาชนไว้ใกล้ใบหน้า</li>
                    <li>• ใบหน้าและบัตรประชาชนต้องเห็นชัดเจนในภาพเดียวกัน</li>
                    <li>• ไม่สวมหมวก แว่นตาดำ หรือสิ่งปิดบังใบหน้า</li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold text-blue-900 mb-2">ข้อกำหนดไฟล์:</h4>
                <ul class="space-y-1 text-sm text-blue-800 ml-4">
                    <li>• รองรับไฟล์: JPEG, JPG, PNG</li>
                    <li>• ขนาดไฟล์ไม่เกิน 5 MB</li>
                </ul>
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
                    รูปบัตรประชาชน <span class="text-red-500">*</span>
                </label>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-500 transition"
                     @click="$refs.idCardInput.click()">
                    <template x-if="!idCardPreview">
                        <div>
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-600 mb-2">คลิกเพื่ออัปโหลดรูปบัตรประชาชน</p>
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
