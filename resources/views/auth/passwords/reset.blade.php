@extends('layouts.auth-arrow-x')

@section('title', 'รีเซ็ตรหัสผ่าน')
@section('subtitle', 'สร้างรหัสผ่านใหม่')

@section('content')
    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-teal-500 rounded-full mb-4 shadow-lg">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            สร้างรหัสผ่านใหม่
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            กรุณากรอกรหัสผ่านใหม่ของคุณ
        </p>
    </div>

    {{-- Error Messages --}}
    @if ($errors->any())
        <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-l-4 border-red-500 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <ul class="list-disc list-inside space-y-1 text-sm font-medium text-red-800 dark:text-red-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('password.update') }}"
          x-data="{
              loading: false,
              password: '',
              passwordConfirm: '',
              showPassword: false,
              showPasswordConfirm: false,
              passwordStrength: 0,
              get strengthColor() {
                  if (this.passwordStrength < 2) return 'bg-red-500';
                  if (this.passwordStrength < 3) return 'bg-yellow-500';
                  if (this.passwordStrength < 4) return 'bg-blue-500';
                  return 'bg-green-500';
              },
              get strengthText() {
                  if (this.passwordStrength === 0) return '';
                  if (this.passwordStrength < 2) return 'อ่อนแอ';
                  if (this.passwordStrength < 3) return 'ปานกลาง';
                  if (this.passwordStrength < 4) return 'ดี';
                  return 'แข็งแรง';
              },
              checkPasswordStrength() {
                  let strength = 0;
                  if (this.password.length >= 8) strength++;
                  if (/[a-z]/.test(this.password)) strength++;
                  if (/[A-Z]/.test(this.password)) strength++;
                  if (/[0-9]/.test(this.password)) strength++;
                  if (/[^a-zA-Z0-9]/.test(this.password)) strength++;
                  this.passwordStrength = strength;
              }
          }"
          @submit="loading = true">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="space-y-6">
            {{-- Email Input --}}
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        อีเมล
                    </span>
                </label>
                <input type="email"
                       name="email"
                       id="email"
                       value="{{ $email ?? old('email') }}"
                       class="input-3d w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                              focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                              dark:bg-gray-800 dark:text-white
                              transition-all duration-300
                              @error('email') border-red-500 @enderror"
                       placeholder="example@email.com"
                       required
                       autofocus>
                @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Password Input --}}
            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        รหัสผ่านใหม่
                    </span>
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'"
                           name="password"
                           id="password"
                           x-model="password"
                           @input="checkPasswordStrength"
                           class="input-3d w-full px-4 py-3 pr-12 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                  focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                  dark:bg-gray-800 dark:text-white
                                  transition-all duration-300
                                  @error('password') border-red-500 @enderror"
                           placeholder="สร้างรหัสผ่านที่แข็งแรง"
                           required>
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>

                {{-- Password Strength Indicator --}}
                <div class="mt-2" x-show="password.length > 0" x-transition>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                            ความแข็งแรง:
                            <span :class="{'text-red-600': passwordStrength < 2, 'text-yellow-600': passwordStrength < 3, 'text-blue-600': passwordStrength < 4, 'text-green-600': passwordStrength >= 4}" x-text="strengthText"></span>
                        </span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div :class="strengthColor"
                             class="h-full transition-all duration-300"
                             :style="'width: ' + (passwordStrength * 20) + '%'"></div>
                    </div>
                </div>

                @error('password')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror

                {{-- Password Requirements --}}
                <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">รหัสผ่านควรมี:</p>
                    <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                        <li class="flex items-center" :class="password.length >= 8 ? 'text-green-600 dark:text-green-400' : ''">
                            <svg class="w-4 h-4 mr-1" :class="password.length >= 8 ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            อย่างน้อย 8 ตัวอักษร
                        </li>
                        <li class="flex items-center" :class="/[A-Z]/.test(password) ? 'text-green-600 dark:text-green-400' : ''">
                            <svg class="w-4 h-4 mr-1" :class="/[A-Z]/.test(password) ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            ตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว (A-Z)
                        </li>
                        <li class="flex items-center" :class="/[a-z]/.test(password) ? 'text-green-600 dark:text-green-400' : ''">
                            <svg class="w-4 h-4 mr-1" :class="/[a-z]/.test(password) ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            ตัวพิมพ์เล็กอย่างน้อย 1 ตัว (a-z)
                        </li>
                        <li class="flex items-center" :class="/[0-9]/.test(password) ? 'text-green-600 dark:text-green-400' : ''">
                            <svg class="w-4 h-4 mr-1" :class="/[0-9]/.test(password) ? 'text-green-500' : 'text-gray-400'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            ตัวเลขอย่างน้อย 1 ตัว (0-9)
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Password Confirmation Input --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        ยืนยันรหัสผ่านใหม่
                    </span>
                </label>
                <div class="relative">
                    <input :type="showPasswordConfirm ? 'text' : 'password'"
                           name="password_confirmation"
                           id="password_confirmation"
                           x-model="passwordConfirm"
                           class="input-3d w-full px-4 py-3 pr-12 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                  focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                  dark:bg-gray-800 dark:text-white
                                  transition-all duration-300"
                           placeholder="กรอกรหัสผ่านอีกครั้ง"
                           required>
                    <button type="button"
                            @click="showPasswordConfirm = !showPasswordConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                        <svg x-show="!showPasswordConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPasswordConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>

                {{-- Password Match Indicator --}}
                <div class="mt-2" x-show="passwordConfirm.length > 0" x-transition>
                    <p class="text-xs font-medium flex items-center"
                       :class="password === passwordConfirm ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path x-show="password === passwordConfirm" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            <path x-show="password !== passwordConfirm" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span x-text="password === passwordConfirm ? 'รหัสผ่านตรงกัน' : 'รหัสผ่านไม่ตรงกัน'"></span>
                    </p>
                </div>
            </div>

            {{-- Submit Button --}}
            <button type="submit"
                    :disabled="loading || password.length < 8 || password !== passwordConfirm"
                    class="btn-shimmer w-full py-4 px-6 bg-gradient-to-r from-green-600 via-teal-600 to-cyan-600
                           hover:from-green-700 hover:via-teal-700 hover:to-cyan-700
                           dark:from-green-500 dark:via-teal-500 dark:to-cyan-500
                           dark:hover:from-green-600 dark:hover:via-teal-600 dark:hover:to-cyan-600
                           text-white font-bold text-lg rounded-xl
                           shadow-lg hover:shadow-2xl
                           transform hover:-translate-y-1
                           transition-all duration-300
                           disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none
                           focus:ring-4 focus:ring-green-500/50 focus:outline-none">
                <span x-show="!loading" class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    รีเซ็ตรหัสผ่าน
                </span>
                <span x-show="loading" class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    กำลังบันทึก...
                </span>
            </button>
        </div>
    </form>
@endsection

@section('footer-links')
    <a href="{{ route('login') }}"
       class="inline-flex items-center text-white/90 dark:text-gray-300 hover:text-white dark:hover:text-white font-semibold transition duration-300 group">
        <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
        </svg>
        กลับไปหน้าเข้าสู่ระบบ
    </a>
@endsection
