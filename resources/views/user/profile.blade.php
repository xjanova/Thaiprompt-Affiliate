@extends('layouts.user')

@section('title', 'โปรไฟล์')

@push('scripts')
@if(config('turnstile.enabled') && config('turnstile.points.password_change'))
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endpush

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900">โปรไฟล์ของฉัน</h1>
        <p class="text-sm text-gray-600 mt-1">จัดการข้อมูลส่วนตัวของคุณ</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Profile Information -->
    <div class="bg-white rounded-xl shadow-md p-6" x-data="{
        editMode: false,
        avatarPreview: null,
        avatarFile: null,
        handleAvatarClick() {
            // Click the form's file input (only in edit mode)
            if (this.editMode && this.$refs.profilePictureInput) {
                this.$refs.profilePictureInput.click();
            }
        },
        handleAvatarChange(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
                    event.target.value = '';
                    return;
                }

                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                    event.target.value = '';
                    return;
                }

                this.avatarFile = file;

                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.avatarPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        clearAvatar() {
            this.avatarPreview = null;
            this.avatarFile = null;
            if (this.$refs.profilePictureInput) {
                this.$refs.profilePictureInput.value = '';
            }
        },
        toggleEditMode() {
            this.editMode = !this.editMode;
            // Clear avatar preview when exiting edit mode
            if (!this.editMode) {
                this.clearAvatar();
            }
        }
    }">
        <div class="flex items-center gap-6 mb-6">
            <div class="relative group">
                <div class="w-24 h-24 rounded-full overflow-hidden bg-gradient-primary flex items-center justify-center text-white text-4xl font-bold transition-all"
                     :class="editMode ? 'cursor-pointer hover:ring-4 hover:ring-indigo-300' : ''"
                     @click="editMode && handleAvatarClick()"
                     :title="editMode ? 'คลิกเพื่อเปลี่ยนรูปโปรไฟล์' : ''">
                    <!-- Show preview if new image selected -->
                    <template x-if="avatarPreview">
                        <img :src="avatarPreview"
                             alt="Preview"
                             class="w-full h-full object-cover">
                    </template>
                    <!-- Show existing avatar if no preview -->
                    <template x-if="!avatarPreview">
                        <div class="w-full h-full flex items-center justify-center">
                            @if($user->line_picture_url || $user->profile_picture)
                                <img src="{{ $user->line_picture_url ?? asset('storage/' . $user->profile_picture) }}"
                                     alt="{{ $user->name }}"
                                     class="w-full h-full object-cover">
                            @else
                                <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            @endif
                        </div>
                    </template>
                </div>
                <!-- Camera overlay icon (only in edit mode) -->
                <div x-show="editMode"
                     class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black bg-opacity-50 rounded-full cursor-pointer"
                     @click="handleAvatarClick()">
                    <i class="fas fa-camera text-white text-2xl"></i>
                </div>
                <!-- Clear button when preview is shown (only in edit mode) -->
                <button type="button"
                        x-show="avatarPreview && editMode"
                        @click.stop="clearAvatar()"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 transition shadow-lg"
                        title="ยกเลิก">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
                <p class="text-xs text-gray-500 mt-1" x-show="editMode">
                    <i class="fas fa-info-circle mr-1"></i>คลิกที่รูปโปรไฟล์เพื่อเปลี่ยนรูป
                </p>
                <div class="flex gap-2 mt-2 flex-wrap">
                    <span class="inline-block px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-medium">
                        {{ ucfirst($user->role) }}
                    </span>
                    @if($user->line_verified)
                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            <i class="fab fa-line mr-1"></i>LINE Verified
                        </span>
                    @endif
                    @if($user->phone_verified)
                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            <i class="fas fa-phone mr-1"></i>Phone Verified
                        </span>
                    @endif
                </div>
            </div>
            <button @click="toggleEditMode()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <span x-show="!editMode"><i class="fas fa-edit mr-2"></i>แก้ไขโปรไฟล์</span>
                <span x-show="editMode"><i class="fas fa-times mr-2"></i>ยกเลิก</span>
            </button>
        </div>

        <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data" x-show="editMode" style="display: none;">
            @csrf
            @method('PUT')

            <!-- Hidden input for avatar file -->
            <input type="file"
                   name="profile_picture"
                   x-ref="profilePictureInput"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   class="hidden"
                   @change="handleAvatarChange($event)">

            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทรศัพท์</label>
                            <input type="tel" name="phone" id="profilePhone" value="{{ old('phone', $user->phone) }}"
                                   placeholder="0812345678"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1 text-xs text-gray-500">รูปแบบ: 0812345678 หรือ +66812345678</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">วันเกิด</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('date_of_birth')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">เพศ</label>
                            <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">เลือกเพศ</option>
                                <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>ชาย</option>
                                <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>หญิง</option>
                                <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>อื่นๆ</option>
                                <option value="prefer_not_to_say" {{ old('gender', $user->gender) == 'prefer_not_to_say' ? 'selected' : '' }}>ไม่ระบุ</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">ที่อยู่</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่</label>
                            <textarea name="address" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('address', $user->address) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">เมือง/อำเภอ</label>
                                <input type="text" name="city" value="{{ old('city', $user->city) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จังหวัด</label>
                                <input type="text" name="state" value="{{ old('state', $user->state) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">รหัสไปรษณีย์</label>
                                <input type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ประเทศ</label>
                            <select name="country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="TH" {{ old('country', $user->country) == 'TH' ? 'selected' : '' }}>ไทย</option>
                                <option value="US" {{ old('country', $user->country) == 'US' ? 'selected' : '' }}>United States</option>
                                <option value="GB" {{ old('country', $user->country) == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">ที่อยู่จัดส่ง</h3>
                        <button type="button" onclick="copyBillingAddress()"
                                class="text-sm text-indigo-600 hover:text-indigo-700">
                            <i class="fas fa-copy mr-1"></i>คัดลอกจากที่อยู่ด้านบน
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่จัดส่ง</label>
                            <textarea name="shipping_address" rows="2" id="shipping_address"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('shipping_address', $user->shipping_address) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">เมือง/อำเภอ</label>
                                <input type="text" name="shipping_city" id="shipping_city" value="{{ old('shipping_city', $user->shipping_city) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จังหวัด</label>
                                <input type="text" name="shipping_state" id="shipping_state" value="{{ old('shipping_state', $user->shipping_state) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">รหัสไปรษณีย์</label>
                                <input type="text" name="shipping_postal_code" id="shipping_postal_code" value="{{ old('shipping_postal_code', $user->shipping_postal_code) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ประเทศ</label>
                                <select name="shipping_country" id="shipping_country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="TH" {{ old('shipping_country', $user->shipping_country) == 'TH' ? 'selected' : '' }}>ไทย</option>
                                    <option value="US" {{ old('shipping_country', $user->shipping_country) == 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="GB" {{ old('shipping_country', $user->shipping_country) == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทรศัพท์จัดส่ง</label>
                                <input type="tel" name="shipping_phone" id="shipping_phone" value="{{ old('shipping_phone', $user->shipping_phone) }}"
                                       placeholder="0812345678"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-3 pt-4 border-t">
                    <button type="submit"
                            class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                        <i class="fas fa-save mr-2"></i>บันทึกข้อมูล
                    </button>
                    <button type="button" @click="toggleEditMode()"
                            class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-medium">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </form>

        <!-- Read-only view -->
        <div x-show="!editMode">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ</label>
                    <input type="text" value="{{ $user->name }}" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                    <input type="email" value="{{ $user->email }}" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>

                @if($user->phone)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทรศัพท์</label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $user->phone }}" readonly
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        @if($user->phone_verified)
                            <span class="px-3 py-2 bg-green-100 text-green-800 rounded-lg text-sm">
                                <i class="fas fa-check-circle"></i> ยืนยันแล้ว
                            </span>
                        @else
                            <button type="button" onclick="verifyPhone()"
                                    class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-sm">
                                ยืนยัน OTP
                            </button>
                        @endif
                    </div>
                </div>
                @endif

                @if($user->date_of_birth)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันเกิด</label>
                    <input type="text" value="{{ $user->date_of_birth->format('d/m/Y') }}" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                @endif

                @if($user->gender)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เพศ</label>
                    <input type="text" value="{{ ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'อื่นๆ', 'prefer_not_to_say' => 'ไม่ระบุ'][$user->gender] ?? $user->gender }}" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">บทบาท</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">สมัครสมาชิกเมื่อ</label>
                    <input type="text" value="{{ $user->created_at->format('d/m/Y H:i') }}" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>

                @if($user->affiliate)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสแนะนำ</label>
                    <input type="text" value="{{ $user->affiliate->referral_code }}" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div id="change-password" class="bg-white rounded-xl shadow-md p-6" x-data="{ showPasswordForm: false }">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">เปลี่ยนรหัสผ่าน</h2>
                <p class="text-sm text-gray-600 mt-1">อัปเดตรหัสผ่านของคุณเพื่อความปลอดภัย</p>
            </div>
            <button @click="showPasswordForm = !showPasswordForm"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <span x-show="!showPasswordForm">เปลี่ยนรหัสผ่าน</span>
                <span x-show="showPasswordForm">ยกเลิก</span>
            </button>
        </div>

        <div x-show="showPasswordForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             style="display: none;">
            <form method="POST" action="{{ route('user.profile.update-password') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข</p>
                    @error('new_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="new_password_confirmation" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                @if(config('turnstile.enabled') && config('turnstile.points.password_change'))
                <div class="flex justify-center pt-2">
                    <div class="cf-turnstile"
                         data-sitekey="{{ config('turnstile.site_key') }}"
                         data-theme="{{ config('turnstile.theme') }}"
                         data-size="{{ config('turnstile.size') }}">
                    </div>
                </div>
                @endif

                <div class="flex gap-3 pt-4">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        บันทึกรหัสผ่านใหม่
                    </button>
                    <button type="button" @click="showPasswordForm = false"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Statistics -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">สถิติบัญชี</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-indigo-600">{{ $user->commissions()->count() }}</p>
                <p class="text-sm text-gray-600 mt-1">คอมมิชชั่นทั้งหมด</p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-green-600">
                    ฿{{ number_format($user->commissions()->where('status', 'paid')->sum('amount'), 0) }}
                </p>
                <p class="text-sm text-gray-600 mt-1">จ่ายแล้ว</p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-yellow-600">
                    {{ $user->commissions()->where('status', 'pending')->count() }}
                </p>
                <p class="text-sm text-gray-600 mt-1">รอดำเนินการ</p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-purple-600">
                    {{ $user->affiliate ? $user->affiliate->children()->count() : 0 }}
                </p>
                <p class="text-sm text-gray-600 mt-1">ผู้แนะนำ</p>
            </div>
        </div>
    </div>
</div>

<!-- OTP Verification Modal -->
<div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">ยืนยันเบอร์โทรศัพท์</h3>
            <button onclick="closeOtpModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div id="otpAlert" class="hidden mb-4 p-3 rounded-lg"></div>

        <div id="sendOtpStep">
            <p class="text-sm text-gray-600 mb-4">เราจะส่งรหัส OTP ไปยังเบอร์โทรศัพท์ของคุณ</p>
            <p class="text-lg font-bold text-gray-900 mb-4">{{ $user->phone }}</p>
            <button onclick="sendOtp()" class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                <i class="fas fa-paper-plane mr-2"></i>ส่งรหัส OTP
            </button>
        </div>

        <div id="verifyOtpStep" class="hidden">
            <p class="text-sm text-gray-600 mb-4">กรุณากรอกรหัส OTP ที่ส่งไปยัง <strong>{{ $user->phone }}</strong></p>
            <input type="text" id="otpCode" maxlength="8" placeholder="กรอกรหัส OTP"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl font-bold mb-4">
            <div class="flex gap-3">
                <button onclick="verifyOtp()" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    <i class="fas fa-check mr-2"></i>ยืนยัน
                </button>
                <button onclick="resendOtp()" class="px-4 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    ส่งอีกครั้ง
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Copy billing address to shipping address
function copyBillingAddress() {
    document.querySelector('[name="shipping_address"]').value = document.querySelector('[name="address"]').value;
    document.querySelector('[name="shipping_city"]').value = document.querySelector('[name="city"]').value;
    document.querySelector('[name="shipping_state"]').value = document.querySelector('[name="state"]').value;
    document.querySelector('[name="shipping_postal_code"]').value = document.querySelector('[name="postal_code"]').value;
    document.querySelector('[name="shipping_country"]').value = document.querySelector('[name="country"]').value;
    document.querySelector('[name="shipping_phone"]').value = document.querySelector('[name="phone"]').value;
}

// OTP Modal Functions
function verifyPhone() {
    @if(!$user->phone)
        alert('กรุณากรอกเบอร์โทรศัพท์ก่อนทำการยืนยัน');
        return;
    @endif

    document.getElementById('otpModal').classList.remove('hidden');
    document.getElementById('otpModal').classList.add('flex');
}

function closeOtpModal() {
    document.getElementById('otpModal').classList.add('hidden');
    document.getElementById('otpModal').classList.remove('flex');
    document.getElementById('sendOtpStep').classList.remove('hidden');
    document.getElementById('verifyOtpStep').classList.add('hidden');
    document.getElementById('otpAlert').classList.add('hidden');
    document.getElementById('otpCode').value = '';
}

async function sendOtp() {
    try {
        showAlert('กำลังส่ง OTP...', 'info');

        const response = await fetch('{{ route("otp.send") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                phone: '{{ $user->phone }}',
                purpose: 'phone_verification'
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('sendOtpStep').classList.add('hidden');
            document.getElementById('verifyOtpStep').classList.remove('hidden');
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'error');
    }
}

async function verifyOtp() {
    const code = document.getElementById('otpCode').value;
    if (!code) {
        showAlert('กรุณากรอกรหัส OTP', 'error');
        return;
    }

    try {
        showAlert('กำลังยืนยัน...', 'info');

        const response = await fetch('{{ route("otp.verify") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                phone: '{{ $user->phone }}',
                code: code,
                purpose: 'phone_verification'
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'error');
    }
}

async function resendOtp() {
    await sendOtp();
}

function showAlert(message, type) {
    const alert = document.getElementById('otpAlert');
    alert.classList.remove('hidden', 'bg-blue-100', 'text-blue-800', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');

    if (type === 'success') {
        alert.classList.add('bg-green-100', 'text-green-800');
    } else if (type === 'error') {
        alert.classList.add('bg-red-100', 'text-red-800');
    } else {
        alert.classList.add('bg-blue-100', 'text-blue-800');
    }

    alert.textContent = message;
    alert.classList.remove('hidden');
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOtpModal();
    }
});

// Close modal when clicking outside
document.getElementById('otpModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeOtpModal();
    }
});
</script>
@endsection
