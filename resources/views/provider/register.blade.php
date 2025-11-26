{{--
    หน้าลงทะเบียนเป็นผู้ให้บริการ
    ฟอร์มสมัครเป็น Service Provider พร้อมยืนยันตัวตน
--}}
@extends('layouts.app')

@section('title', 'สมัครเป็นผู้ให้บริการ')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="providerRegistration()">
    {{-- Hero Section --}}
    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full mb-6 shadow-xl">
            <i class="fas fa-user-tie text-4xl text-white"></i>
        </div>
        <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent mb-4">
            สมัครเป็นผู้ให้บริการ
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            เข้าร่วมเป็นส่วนหนึ่งของทีมผู้ให้บริการ เริ่มสร้างรายได้จากทักษะของคุณวันนี้!
        </p>
    </div>

    {{-- Benefits --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-xl p-4 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full mb-3">
                <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">รายได้ดี</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">รับ 85-90% ของค่าบริการ</p>
        </div>
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-xl p-4 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full mb-3">
                <i class="fas fa-clock text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">เวลายืดหยุ่น</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">เลือกรับงานตามเวลาที่สะดวก</p>
        </div>
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-xl p-4 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full mb-3">
                <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">โบนัส PV</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">สะสม PV เพื่อรับคอมมิชชั่น</p>
        </div>
    </div>

    {{-- Registration Form --}}
    <form action="{{ route('provider.register.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Step 1: Personal Info --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-user mr-2"></i>
                    ข้อมูลส่วนตัว
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Display Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อที่ใช้แสดง <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="display_name"
                               value="{{ old('display_name', $user->name) }}"
                               required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white"
                               placeholder="ชื่อที่ลูกค้าจะเห็น">
                        @error('display_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                        </label>
                        <input type="tel"
                               name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white"
                               placeholder="08x-xxx-xxxx">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        แนะนำตัว / ประสบการณ์
                    </label>
                    <textarea name="description"
                              rows="3"
                              class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white"
                              placeholder="เล่าเกี่ยวกับตัวคุณ ประสบการณ์ ความเชี่ยวชาญ...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Profile Photo --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        รูปโปรไฟล์
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="relative w-24 h-24 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                            {{-- ใช้ profile_picture_url accessor พร้อม fallback --}}
                            <img x-ref="profilePreview"
                                 src="{{ $user->profile_picture_url ?? asset('images/default-avatar.png') }}"
                                 alt="Profile"
                                 class="w-full h-full object-cover"
                                 onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name ?? 'U', 0, 1)) }}&background=6366f1&color=fff&size=200';">
                        </div>
                        <div class="flex-1">
                            <input type="file"
                                   name="profile_photo"
                                   accept="image/*"
                                   @change="previewImage($event, $refs.profilePreview)"
                                   class="hidden"
                                   id="profile_photo">
                            <label for="profile_photo"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                <i class="fas fa-camera"></i>
                                เลือกรูป
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">JPG, PNG ขนาดไม่เกิน 2MB</p>
                        </div>
                    </div>
                    @error('profile_photo')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Step 2: ID Verification --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-blue-600 to-cyan-600 text-white">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-id-card mr-2"></i>
                    ยืนยันตัวตน
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-shield-alt text-blue-600 dark:text-blue-400 text-xl mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-blue-800 dark:text-blue-300">ข้อมูลของคุณปลอดภัย</p>
                            <p class="text-sm text-blue-700 dark:text-blue-400">
                                ข้อมูลบัตรประชาชนจะถูกเข้ารหัสและเก็บรักษาอย่างปลอดภัย ใช้เพื่อยืนยันตัวตนเท่านั้น
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- ID Card Number --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เลขบัตรประชาชน <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="id_card_number"
                               value="{{ old('id_card_number') }}"
                               required
                               maxlength="13"
                               pattern="[0-9]{13}"
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-white"
                               placeholder="x-xxxx-xxxxx-xx-x">
                        @error('id_card_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ID Card Photo --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            รูปถ่ายบัตรประชาชน <span class="text-red-500">*</span>
                        </label>
                        <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-4 text-center hover:border-blue-500 dark:hover:border-blue-400 transition-colors cursor-pointer"
                             @click="$refs.idCardInput.click()">
                            <input type="file"
                                   name="id_card_photo"
                                   accept="image/*"
                                   required
                                   x-ref="idCardInput"
                                   @change="previewIdCard($event)"
                                   class="hidden">
                            <div x-show="!idCardPreview">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600 dark:text-gray-400">คลิกเพื่อเลือกรูป</p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">JPG, PNG ขนาดไม่เกิน 5MB</p>
                            </div>
                            <div x-show="idCardPreview" class="relative">
                                <img :src="idCardPreview" class="max-h-32 mx-auto rounded-lg">
                                <button type="button"
                                        @click.stop="idCardPreview = null; $refs.idCardInput.value = ''"
                                        class="absolute top-0 right-0 -mt-2 -mr-2 bg-red-500 text-white rounded-full p-1 text-xs">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        @error('id_card_photo')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Service Categories --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-green-600 to-emerald-600 text-white">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-th-large mr-2"></i>
                    หมวดหมู่บริการ
                </h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    เลือกหมวดหมู่บริการที่คุณต้องการให้บริการ (เลือกได้มากกว่า 1)
                </p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($categories as $category)
                        <label class="relative cursor-pointer">
                            <input type="checkbox"
                                   name="categories[]"
                                   value="{{ $category->id }}"
                                   {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}
                                   class="peer hidden">
                            <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 transition-all duration-200 hover:border-green-300">
                                <span class="text-3xl">{{ $category->icon ?? '📦' }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">{{ $category->name }}</span>
                            </div>
                            <div class="absolute top-2 right-2 hidden peer-checked:block">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('categories')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Step 4: Service Areas --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-orange-600 to-amber-600 text-white">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-map-marked-alt mr-2"></i>
                    พื้นที่ให้บริการ
                </h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    เลือกพื้นที่ที่คุณสามารถให้บริการได้ (เลือกได้มากกว่า 1)
                </p>

                @if($areas->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-64 overflow-y-auto p-2">
                        @foreach($areas->groupBy('province') as $province => $provinceAreas)
                            @foreach($provinceAreas as $area)
                                <label class="relative cursor-pointer">
                                    <input type="checkbox"
                                           name="service_areas[]"
                                           value="{{ $area->id }}"
                                           {{ in_array($area->id, old('service_areas', [])) ? 'checked' : '' }}
                                           class="peer hidden">
                                    <div class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/20 transition-all duration-200 hover:border-orange-300">
                                        <i class="fas fa-map-marker-alt text-orange-500 peer-checked:text-orange-600"></i>
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $area->district }}{{ $area->subdistrict ? ', '.$area->subdistrict : '' }}
                                        </span>
                                    </div>
                                    <div class="absolute top-2 right-2 hidden peer-checked:block">
                                        <i class="fas fa-check text-orange-500 text-xs"></i>
                                    </div>
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-map-marker-alt text-3xl mb-2"></i>
                        <p>ยังไม่มีพื้นที่ให้บริการในระบบ</p>
                    </div>
                @endif
                @error('service_areas')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Step 5: Bank Account --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-indigo-600 to-violet-600 text-white">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-university mr-2"></i>
                    บัญชีธนาคาร (รับเงิน)
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Bank Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ธนาคาร <span class="text-red-500">*</span>
                        </label>
                        <select name="bank_name"
                                required
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white">
                            <option value="">เลือกธนาคาร</option>
                            <option value="กสิกรไทย" {{ old('bank_name') === 'กสิกรไทย' ? 'selected' : '' }}>ธนาคารกสิกรไทย</option>
                            <option value="กรุงเทพ" {{ old('bank_name') === 'กรุงเทพ' ? 'selected' : '' }}>ธนาคารกรุงเทพ</option>
                            <option value="ไทยพาณิชย์" {{ old('bank_name') === 'ไทยพาณิชย์' ? 'selected' : '' }}>ธนาคารไทยพาณิชย์</option>
                            <option value="กรุงไทย" {{ old('bank_name') === 'กรุงไทย' ? 'selected' : '' }}>ธนาคารกรุงไทย</option>
                            <option value="กรุงศรี" {{ old('bank_name') === 'กรุงศรี' ? 'selected' : '' }}>ธนาคารกรุงศรีอยุธยา</option>
                            <option value="ทหารไทยธนชาต" {{ old('bank_name') === 'ทหารไทยธนชาต' ? 'selected' : '' }}>ธนาคารทหารไทยธนชาต</option>
                            <option value="ออมสิน" {{ old('bank_name') === 'ออมสิน' ? 'selected' : '' }}>ธนาคารออมสิน</option>
                            <option value="ธกส" {{ old('bank_name') === 'ธกส' ? 'selected' : '' }}>ธ.ก.ส.</option>
                        </select>
                        @error('bank_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Number --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เลขบัญชี <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="bank_account_number"
                               value="{{ old('bank_account_number') }}"
                               required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white"
                               placeholder="xxx-x-xxxxx-x">
                        @error('bank_account_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อบัญชี <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="bank_account_name"
                               value="{{ old('bank_account_name', $user->name) }}"
                               required
                               class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white"
                               placeholder="ชื่อ-นามสกุล">
                        @error('bank_account_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 6: Settings --}}
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-gray-600 to-slate-600 text-white">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-cog mr-2"></i>
                    ตั้งค่า
                </h2>
            </div>
            <div class="p-6 space-y-4">
                {{-- Auto Accept --}}
                <label class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <input type="checkbox"
                           name="auto_accept"
                           value="1"
                           {{ old('auto_accept') ? 'checked' : '' }}
                           class="w-5 h-5 mt-0.5 text-purple-600 rounded focus:ring-purple-500">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">รับงานอัตโนมัติ</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ระบบจะรับงานให้อัตโนมัติเมื่อมีงานใหม่เข้ามาในพื้นที่ของคุณ
                        </p>
                    </div>
                </label>

                {{-- Terms --}}
                <label class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl cursor-pointer hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors border border-purple-200 dark:border-purple-800">
                    <input type="checkbox"
                           name="accept_terms"
                           value="1"
                           required
                           {{ old('accept_terms') ? 'checked' : '' }}
                           class="w-5 h-5 mt-0.5 text-purple-600 rounded focus:ring-purple-500">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            ยอมรับข้อกำหนดและเงื่อนไข <span class="text-red-500">*</span>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ข้าพเจ้ายอมรับ
                            <a href="#" class="text-purple-600 dark:text-purple-400 hover:underline">ข้อกำหนดการใช้งาน</a>
                            และ
                            <a href="#" class="text-purple-600 dark:text-purple-400 hover:underline">นโยบายความเป็นส่วนตัว</a>
                            ของผู้ให้บริการ
                        </p>
                    </div>
                </label>
                @error('accept_terms')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="text-center">
            <button type="submit"
                    class="inline-flex items-center gap-3 px-12 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold text-lg rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-paper-plane"></i>
                สมัครเป็นผู้ให้บริการ
            </button>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                หลังจากสมัคร ทีมงานจะตรวจสอบข้อมูลภายใน 1-2 วันทำการ
            </p>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function providerRegistration() {
    return {
        idCardPreview: null,

        previewImage(event, imgElement) {
            const file = event.target.files[0];
            if (file) {
                imgElement.src = URL.createObjectURL(file);
            }
        },

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
