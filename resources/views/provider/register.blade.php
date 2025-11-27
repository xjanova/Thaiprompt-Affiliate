{{--
    หน้าลงทะเบียนเป็นผู้ให้บริการ - Arrow X V3 Theme
    ฟอร์มสมัครเป็น Service Provider พร้อมยืนยันตัวตน
--}}
@extends('layouts.provider-arrow-x')

@section('title', 'สมัครเป็นผู้ให้บริการ')

@section('page-title', 'สมัครเป็นผู้ให้บริการ')

@section('content')
<div class="max-w-4xl mx-auto" x-data="providerRegistration()">
    {{-- Hero Section --}}
    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-emerald-500 to-cyan-500 rounded-full mb-6 shadow-2xl ring-4 ring-white/20">
            <i class="fas fa-user-tie text-4xl text-white"></i>
        </div>
        <h1 class="text-4xl font-bold text-white drop-shadow-lg mb-4">
            สมัครเป็นผู้ให้บริการ
        </h1>
        <p class="text-lg text-white/80 max-w-2xl mx-auto">
            เข้าร่วมเป็นส่วนหนึ่งของทีมผู้ให้บริการ เริ่มสร้างรายได้จากทักษะของคุณวันนี้!
        </p>
    </div>

    {{-- Benefits --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="glass-fusion border border-white/20 rounded-xl p-4 text-center hover:scale-105 transition-transform">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-green-500/30 rounded-full mb-3">
                <i class="fas fa-money-bill-wave text-green-300 text-xl"></i>
            </div>
            <h3 class="font-bold text-white mb-1">รายได้ดี</h3>
            <p class="text-sm text-white/70">รับ 85-90% ของค่าบริการ</p>
        </div>
        <div class="glass-fusion border border-white/20 rounded-xl p-4 text-center hover:scale-105 transition-transform">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-500/30 rounded-full mb-3">
                <i class="fas fa-clock text-blue-300 text-xl"></i>
            </div>
            <h3 class="font-bold text-white mb-1">เวลายืดหยุ่น</h3>
            <p class="text-sm text-white/70">เลือกรับงานตามเวลาที่สะดวก</p>
        </div>
        <div class="glass-fusion border border-white/20 rounded-xl p-4 text-center hover:scale-105 transition-transform">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-500/30 rounded-full mb-3">
                <i class="fas fa-chart-line text-purple-300 text-xl"></i>
            </div>
            <h3 class="font-bold text-white mb-1">โบนัส PV</h3>
            <p class="text-sm text-white/70">สะสม PV เพื่อรับคอมมิชชั่น</p>
        </div>
    </div>

    {{-- Registration Form --}}
    <form action="{{ route('provider.register.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Step 1: Personal Info --}}
        <div class="glass-fusion border border-white/20 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-purple-600/80 to-pink-600/80 border-b border-white/20">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-user"></i>
                    ข้อมูลส่วนตัว
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Display Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-white/90 mb-2">
                            ชื่อที่ใช้แสดง <span class="text-red-400">*</span>
                        </label>
                        <input type="text"
                               name="display_name"
                               value="{{ old('display_name', $user->name) }}"
                               required
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-white placeholder-white/50 backdrop-blur-sm"
                               placeholder="ชื่อที่ลูกค้าจะเห็น">
                        @error('display_name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="block text-sm font-semibold text-white/90 mb-2">
                            เบอร์โทรศัพท์ <span class="text-red-400">*</span>
                        </label>
                        <input type="tel"
                               name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               required
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-white placeholder-white/50 backdrop-blur-sm"
                               placeholder="08x-xxx-xxxx">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-semibold text-white/90 mb-2">
                        แนะนำตัว / ประสบการณ์
                    </label>
                    <textarea name="description"
                              rows="3"
                              class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-white placeholder-white/50 backdrop-blur-sm"
                              placeholder="เล่าเกี่ยวกับตัวคุณ ประสบการณ์ ความเชี่ยวชาญ...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Profile Photo --}}
                <div>
                    <label class="block text-sm font-semibold text-white/90 mb-2">
                        รูปโปรไฟล์
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="relative w-24 h-24 rounded-full overflow-hidden bg-white/10 ring-2 ring-white/30">
                            <img x-ref="profilePreview"
                                 src="{{ $user->profile_picture_url ?? asset('images/default-avatar.png') }}"
                                 alt="Profile"
                                 class="w-full h-full object-cover"
                                 onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name ?? 'U', 0, 1)) }}&background=10b981&color=fff&size=200';">
                        </div>
                        <div class="flex-1">
                            <input type="file"
                                   name="profile_photo"
                                   accept="image/*"
                                   @change="previewImage($event, $refs.profilePreview)"
                                   class="hidden"
                                   id="profile_photo">
                            <label for="profile_photo"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/30 text-white rounded-lg cursor-pointer hover:bg-white/20 transition-colors">
                                <i class="fas fa-camera"></i>
                                เลือกรูป
                            </label>
                            <p class="text-xs text-white/50 mt-1">JPG, PNG ขนาดไม่เกิน 2MB</p>
                        </div>
                    </div>
                    @error('profile_photo')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Step 2: ID Verification --}}
        <div class="glass-fusion border border-white/20 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-blue-600/80 to-cyan-600/80 border-b border-white/20">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-id-card"></i>
                    ยืนยันตัวตน
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="p-4 bg-blue-500/20 border border-blue-400/30 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-shield-alt text-blue-300 text-xl mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-blue-200">ข้อมูลของคุณปลอดภัย</p>
                            <p class="text-sm text-blue-200/80">
                                ข้อมูลบัตรประชาชนจะถูกเข้ารหัสและเก็บรักษาอย่างปลอดภัย ใช้เพื่อยืนยันตัวตนเท่านั้น
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- ID Card Number --}}
                    <div>
                        <label class="block text-sm font-semibold text-white/90 mb-2">
                            เลขบัตรประชาชน <span class="text-red-400">*</span>
                        </label>
                        <input type="text"
                               name="id_card_number"
                               value="{{ old('id_card_number') }}"
                               required
                               maxlength="13"
                               pattern="[0-9]{13}"
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-transparent text-white placeholder-white/50 backdrop-blur-sm"
                               placeholder="x-xxxx-xxxxx-xx-x">
                        @error('id_card_number')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ID Card Photo --}}
                    <div>
                        <label class="block text-sm font-semibold text-white/90 mb-2">
                            รูปถ่ายบัตรประชาชน <span class="text-red-400">*</span>
                        </label>
                        <div class="relative border-2 border-dashed border-white/30 rounded-xl p-4 text-center hover:border-blue-400 transition-colors cursor-pointer"
                             @click="$refs.idCardInput.click()">
                            <input type="file"
                                   name="id_card_photo"
                                   accept="image/*"
                                   required
                                   x-ref="idCardInput"
                                   @change="previewIdCard($event)"
                                   class="hidden">
                            <div x-show="!idCardPreview">
                                <i class="fas fa-cloud-upload-alt text-3xl text-white/50 mb-2"></i>
                                <p class="text-sm text-white/70">คลิกเพื่อเลือกรูป</p>
                                <p class="text-xs text-white/50 mt-1">JPG, PNG ขนาดไม่เกิน 5MB</p>
                            </div>
                            <div x-show="idCardPreview" class="relative">
                                <img :src="idCardPreview" class="max-h-32 mx-auto rounded-lg">
                                <button type="button"
                                        @click.stop="idCardPreview = null; $refs.idCardInput.value = ''"
                                        class="absolute top-0 right-0 -mt-2 -mr-2 bg-red-500 text-white rounded-full p-1.5 text-xs hover:bg-red-600 transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        @error('id_card_photo')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Service Categories --}}
        <div class="glass-fusion border border-white/20 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-green-600/80 to-emerald-600/80 border-b border-white/20">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-th-large"></i>
                    หมวดหมู่บริการ
                </h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-white/70 mb-4">
                    เลือกหมวดหมู่บริการที่คุณต้องการให้บริการ (เลือกได้มากกว่า 1)
                </p>
                @if($categories->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($categories as $category)
                            <label class="relative cursor-pointer group">
                                <input type="checkbox"
                                       name="categories[]"
                                       value="{{ $category->id }}"
                                       {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}
                                       class="peer hidden">
                                <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-white/20 peer-checked:border-emerald-400 peer-checked:bg-emerald-500/20 transition-all duration-200 hover:border-emerald-300 hover:bg-white/5">
                                    <span class="text-3xl">{{ $category->icon ?? '📦' }}</span>
                                    <span class="text-sm font-semibold text-white text-center">{{ $category->name }}</span>
                                </div>
                                <div class="absolute top-2 right-2 hidden peer-checked:flex items-center justify-center w-6 h-6 bg-emerald-500 rounded-full">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-white/50">
                        <i class="fas fa-folder-open text-3xl mb-2"></i>
                        <p>ยังไม่มีหมวดหมู่บริการในระบบ</p>
                    </div>
                @endif
                @error('categories')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Step 4: Service Areas --}}
        <div class="glass-fusion border border-white/20 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-orange-600/80 to-amber-600/80 border-b border-white/20">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-map-marked-alt"></i>
                    พื้นที่ให้บริการ
                </h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-white/70 mb-4">
                    เลือกพื้นที่ที่คุณสามารถให้บริการได้ (เลือกได้มากกว่า 1)
                </p>
                @if($areas->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-64 overflow-y-auto p-2">
                        @foreach($areas as $area)
                            <label class="relative cursor-pointer group">
                                <input type="checkbox"
                                       name="service_areas[]"
                                       value="{{ $area->id }}"
                                       {{ in_array($area->id, old('service_areas', [])) ? 'checked' : '' }}
                                       class="peer hidden">
                                <div class="flex items-center gap-2 p-3 rounded-lg border border-white/20 peer-checked:border-orange-400 peer-checked:bg-orange-500/20 transition-all duration-200 hover:border-orange-300 hover:bg-white/5">
                                    <i class="fas fa-map-marker-alt text-orange-400"></i>
                                    <span class="text-sm text-white">
                                        {{ $area->district }}{{ $area->subdistrict ? ', '.$area->subdistrict : '' }}
                                    </span>
                                </div>
                                <div class="absolute top-1 right-1 hidden peer-checked:flex items-center justify-center w-5 h-5 bg-orange-500 rounded-full">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-white/50">
                        <i class="fas fa-map-marker-alt text-3xl mb-2"></i>
                        <p>ยังไม่มีพื้นที่ให้บริการในระบบ</p>
                    </div>
                @endif
                @error('service_areas')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Step 5: Bank Account --}}
        <div class="glass-fusion border border-white/20 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-indigo-600/80 to-violet-600/80 border-b border-white/20">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-university"></i>
                    บัญชีธนาคาร (รับเงิน)
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Bank Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-white/90 mb-2">
                            ธนาคาร <span class="text-red-400">*</span>
                        </label>
                        <select name="bank_name"
                                required
                                class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:border-transparent text-white backdrop-blur-sm">
                            <option value="" class="bg-gray-800">เลือกธนาคาร</option>
                            <option value="กสิกรไทย" class="bg-gray-800" {{ old('bank_name') === 'กสิกรไทย' ? 'selected' : '' }}>ธนาคารกสิกรไทย</option>
                            <option value="กรุงเทพ" class="bg-gray-800" {{ old('bank_name') === 'กรุงเทพ' ? 'selected' : '' }}>ธนาคารกรุงเทพ</option>
                            <option value="ไทยพาณิชย์" class="bg-gray-800" {{ old('bank_name') === 'ไทยพาณิชย์' ? 'selected' : '' }}>ธนาคารไทยพาณิชย์</option>
                            <option value="กรุงไทย" class="bg-gray-800" {{ old('bank_name') === 'กรุงไทย' ? 'selected' : '' }}>ธนาคารกรุงไทย</option>
                            <option value="กรุงศรี" class="bg-gray-800" {{ old('bank_name') === 'กรุงศรี' ? 'selected' : '' }}>ธนาคารกรุงศรีอยุธยา</option>
                            <option value="ทหารไทยธนชาต" class="bg-gray-800" {{ old('bank_name') === 'ทหารไทยธนชาต' ? 'selected' : '' }}>ธนาคารทหารไทยธนชาต</option>
                            <option value="ออมสิน" class="bg-gray-800" {{ old('bank_name') === 'ออมสิน' ? 'selected' : '' }}>ธนาคารออมสิน</option>
                            <option value="ธกส" class="bg-gray-800" {{ old('bank_name') === 'ธกส' ? 'selected' : '' }}>ธ.ก.ส.</option>
                        </select>
                        @error('bank_name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Number --}}
                    <div>
                        <label class="block text-sm font-semibold text-white/90 mb-2">
                            เลขบัญชี <span class="text-red-400">*</span>
                        </label>
                        <input type="text"
                               name="bank_account_number"
                               value="{{ old('bank_account_number') }}"
                               required
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:border-transparent text-white placeholder-white/50 backdrop-blur-sm"
                               placeholder="xxx-x-xxxxx-x">
                        @error('bank_account_number')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-white/90 mb-2">
                            ชื่อบัญชี <span class="text-red-400">*</span>
                        </label>
                        <input type="text"
                               name="bank_account_name"
                               value="{{ old('bank_account_name', $user->name) }}"
                               required
                               class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:border-transparent text-white placeholder-white/50 backdrop-blur-sm"
                               placeholder="ชื่อ-นามสกุล">
                        @error('bank_account_name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 6: Settings --}}
        <div class="glass-fusion border border-white/20 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-gray-600/80 to-slate-600/80 border-b border-white/20">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-cog"></i>
                    ตั้งค่า
                </h2>
            </div>
            <div class="p-6 space-y-4">
                {{-- Auto Accept --}}
                <label class="flex items-start gap-4 p-4 bg-white/5 border border-white/20 rounded-xl cursor-pointer hover:bg-white/10 transition-colors group">
                    <input type="checkbox"
                           name="auto_accept"
                           value="1"
                           {{ old('auto_accept') ? 'checked' : '' }}
                           class="w-5 h-5 mt-0.5 text-emerald-500 rounded focus:ring-emerald-500 bg-white/10 border-white/30">
                    <div>
                        <p class="font-semibold text-white">รับงานอัตโนมัติ</p>
                        <p class="text-sm text-white/60">
                            ระบบจะรับงานให้อัตโนมัติเมื่อมีงานใหม่เข้ามาในพื้นที่ของคุณ
                        </p>
                    </div>
                </label>

                {{-- Terms --}}
                <label class="flex items-start gap-4 p-4 bg-emerald-500/10 border border-emerald-400/30 rounded-xl cursor-pointer hover:bg-emerald-500/20 transition-colors group">
                    <input type="checkbox"
                           name="accept_terms"
                           value="1"
                           required
                           {{ old('accept_terms') ? 'checked' : '' }}
                           class="w-5 h-5 mt-0.5 text-emerald-500 rounded focus:ring-emerald-500 bg-white/10 border-white/30">
                    <div>
                        <p class="font-semibold text-white">
                            ยอมรับข้อกำหนดและเงื่อนไข <span class="text-red-400">*</span>
                        </p>
                        <p class="text-sm text-white/70">
                            ข้าพเจ้ายอมรับ
                            <a href="#" class="text-emerald-400 hover:underline">ข้อกำหนดการใช้งาน</a>
                            และ
                            <a href="#" class="text-emerald-400 hover:underline">นโยบายความเป็นส่วนตัว</a>
                            ของผู้ให้บริการ
                        </p>
                    </div>
                </label>
                @error('accept_terms')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="text-center py-6">
            <button type="submit"
                    class="inline-flex items-center gap-3 px-12 py-4 bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 text-white font-bold text-lg rounded-2xl shadow-xl hover:shadow-2xl hover:shadow-emerald-500/30 transition-all duration-300 transform hover:scale-105 group">
                <i class="fas fa-paper-plane group-hover:translate-x-1 transition-transform"></i>
                สมัครเป็นผู้ให้บริการ
            </button>
            <p class="text-sm text-white/60 mt-4">
                หลังจากสมัคร ทีมงานจะตรวจสอบข้อมูลภายใน 1-2 วันทำการ
            </p>
        </div>
    </form>

    {{-- Back to Dashboard --}}
    <div class="text-center pb-8">
        <a href="{{ route('user.dashboard') }}"
           class="inline-flex items-center gap-2 text-white/70 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับไปหน้า Dashboard
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับฟอร์มลงทะเบียน Provider
 */
function providerRegistration() {
    return {
        idCardPreview: null,

        /**
         * Preview รูปโปรไฟล์
         */
        previewImage(event, imgElement) {
            const file = event.target.files[0];
            if (file) {
                imgElement.src = URL.createObjectURL(file);
            }
        },

        /**
         * Preview รูปบัตรประชาชน
         */
        previewIdCard(event) {
            const file = event.target.files[0];
            if (file) {
                this.idCardPreview = URL.createObjectURL(file);
            }
        }
    }
}
</script>
@endpush

@push('styles')
<style>
/**
 * Glass Fusion Effect สำหรับ Cards
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

/**
 * Custom scrollbar สำหรับ areas selection
 */
.max-h-64::-webkit-scrollbar {
    width: 6px;
}
.max-h-64::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}
.max-h-64::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}
.max-h-64::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>
@endpush
