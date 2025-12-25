@extends('layouts.app')

@section('title', 'ลงทะเบียนนักพัฒนา')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 py-12">

    {{-- Background Decorations --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="inline-block px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-full font-bold text-sm mb-4 shadow-lg">
                    🧑‍💻 นักพัฒนาซอฟต์แวร์
                </div>
                <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white mb-4">
                    ลงทะเบียนนักพัฒนา
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    ขายซอฟต์แวร์ของคุณและรับค่าคอมมิชชั่น 70%
                </p>
            </div>

            {{-- Benefits Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="text-4xl mb-3">💰</div>
                    <h3 class="font-black text-slate-900 dark:text-white mb-2">70% Commission</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">รับส่วนแบ่ง 70% จากทุกการขาย</p>
                </div>
                <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="text-4xl mb-3">🌐</div>
                    <h3 class="font-black text-slate-900 dark:text-white mb-2">Multi-Cloud</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">รองรับ S3, Google Drive, URL</p>
                </div>
                <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <div class="text-4xl mb-3">🔐</div>
                    <h3 class="font-black text-slate-900 dark:text-white mb-2">License System</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">ระบบ License Key อัตโนมัติ</p>
                </div>
            </div>

            {{-- Registration Form --}}
            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-3xl p-8 lg:p-12 border-2 border-white/50 dark:border-slate-700/50 shadow-2xl"
                 x-data="{
                     avatarPreview: null,
                     idCardPreview: null,
                     companyDocPreview: null,
                     handleFilePreview(event, type) {
                         const file = event.target.files[0];
                         if (file) {
                             const reader = new FileReader();
                             reader.onload = (e) => {
                                 this[type + 'Preview'] = e.target.result;
                             };
                             reader.readAsDataURL(file);
                         }
                     }
                 }">

                <form action="{{ route('developer.register.submit') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- ส่วนที่ 1: ข้อมูลพื้นฐาน --}}
                    <div class="mb-10">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-10 h-10 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center text-white text-lg">1</span>
                            <span>ข้อมูลพื้นฐาน</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Display Name --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    ชื่อที่แสดง <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="display_name" value="{{ old('display_name', $user->name) }}" required
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="เช่น: John Doe">
                                @error('display_name')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Company Name --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    ชื่อบริษัท (ถ้ามี)
                                </label>
                                <input type="text" name="company_name" value="{{ old('company_name') }}"
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="เช่น: ABC Software Co., Ltd.">
                                @error('company_name')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Website --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    เว็บไซต์
                                </label>
                                <input type="url" name="website" value="{{ old('website') }}"
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="https://example.com">
                                @error('website')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Avatar --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    รูปโปรไฟล์
                                </label>
                                <div class="relative">
                                    <input type="file" name="avatar" accept="image/*"
                                           @change="handleFilePreview($event, 'avatar')"
                                           class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                                  file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:font-bold hover:file:bg-blue-700
                                                  focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all">
                                    <div x-show="avatarPreview" class="mt-4">
                                        <img :src="avatarPreview" class="w-24 h-24 rounded-xl object-cover border-2 border-blue-500 shadow-lg">
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">ไฟล์ภาพ ≤2MB</p>
                                @error('avatar')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Bio --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    ประวัติ/คำอธิบาย
                                </label>
                                <textarea name="bio" rows="4"
                                          class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                                 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                          placeholder="เกี่ยวกับคุณและผลงานของคุณ...">{{ old('bio') }}</textarea>
                                @error('bio')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ส่วนที่ 2: ข้อมูล KYC --}}
                    <div class="mb-10 pt-10 border-t-2 border-slate-200 dark:border-slate-700">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-10 h-10 bg-gradient-to-r from-purple-600 to-pink-500 rounded-xl flex items-center justify-center text-white text-lg">2</span>
                            <span>ข้อมูล KYC (ยืนยันตัวตน)</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Facebook Profile --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    โปรไฟล์ Facebook <span class="text-red-500">*</span>
                                </label>
                                <input type="url" name="facebook_profile" value="{{ old('facebook_profile') }}" required
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="https://facebook.com/yourprofile">
                                @error('facebook_profile')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- LINE ID --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    LINE ID <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="line_id" value="{{ old('line_id', $user->line_user_id) }}" required
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="@yourlineid">
                                @error('line_id')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="0812345678">
                                @error('phone')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    อีเมล <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="your@email.com">
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ID Card Image --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    รูปบัตรประชาชน <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="file" name="id_card_image" accept="image/*,.pdf" required
                                           @change="handleFilePreview($event, 'idCard')"
                                           class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                                  file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-purple-600 file:text-white file:font-bold hover:file:bg-purple-700
                                                  focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all">
                                    <div x-show="idCardPreview" class="mt-4">
                                        <img :src="idCardPreview" class="max-w-md rounded-xl border-2 border-purple-500 shadow-lg">
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">ไฟล์ภาพหรือ PDF ≤5MB</p>
                                @error('id_card_image')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ส่วนที่ 3: ข้อมูลบริษัท (Optional) --}}
                    <div class="mb-10 pt-10 border-t-2 border-slate-200 dark:border-slate-700">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-10 h-10 bg-gradient-to-r from-green-600 to-emerald-500 rounded-xl flex items-center justify-center text-white text-lg">3</span>
                            <span>ข้อมูลบริษัท (ถ้ามี)</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Company Registration Number --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    เลขทะเบียนบริษัท
                                </label>
                                <input type="text" name="company_registration_number" value="{{ old('company_registration_number') }}"
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="เช่น: 0105560123456">
                            </div>

                            {{-- Tax ID --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    เลขประจำตัวผู้เสียภาษี
                                </label>
                                <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                                       class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                              focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                       placeholder="เช่น: 0123456789012">
                            </div>

                            {{-- Company Address --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    ที่อยู่บริษัท
                                </label>
                                <textarea name="company_address" rows="3"
                                          class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                                 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all"
                                          placeholder="ที่อยู่สำนักงาน...">{{ old('company_address') }}</textarea>
                            </div>

                            {{-- Company Registration Document --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                                    เอกสารทะเบียนบริษัท
                                </label>
                                <div class="relative">
                                    <input type="file" name="company_registration_doc" accept="image/*,.pdf"
                                           @change="handleFilePreview($event, 'companyDoc')"
                                           class="w-full px-4 py-3 bg-white/50 dark:bg-slate-700/50 border-2 border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white font-semibold
                                                  file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-green-600 file:text-white file:font-bold hover:file:bg-green-700
                                                  focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all">
                                    <div x-show="companyDocPreview" class="mt-4">
                                        <img :src="companyDocPreview" class="max-w-md rounded-xl border-2 border-green-500 shadow-lg">
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">ไฟล์ภาพหรือ PDF ≤5MB</p>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex gap-4">
                        <button type="submit"
                                class="flex-1 px-8 py-5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-2xl font-black text-lg shadow-2xl hover:scale-105 transition-all duration-300">
                            🚀 ลงทะเบียนนักพัฒนา
                        </button>
                        <a href="{{ route('home') }}"
                           class="px-8 py-5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-2xl font-bold text-lg shadow-lg hover:scale-105 transition-all duration-300">
                            ยกเลิก
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection
