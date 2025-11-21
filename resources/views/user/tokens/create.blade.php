@extends('layouts.user-arrow-x')

@section('title', 'สร้าง Token ใหม่ - TPIX')

@section('content')
{{-- Alpine.js Component สำหรับ Create Token Form --}}
<div x-data="createTokenForm()" x-init="init()" class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Hero Header --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 p-1 mb-8">
            <div class="bg-white dark:bg-gray-900 rounded-[22px] p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 shadow-xl mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-400 dark:to-emerald-400 mb-3">
                    สร้าง Token ของคุณ
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    เปิดตัว Token ของคุณบน TPIX Blockchain ภายในไม่กี่นาที
                </p>
            </div>
        </div>

        {{-- Step Indicator --}}
        <div class="mb-8">
            <div class="flex items-center justify-between relative">
                {{-- Progress Bar --}}
                <div class="absolute top-8 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 -z-10">
                    <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 transition-all duration-300"
                         :style="`width: ${((currentStep - 1) / 3) * 100}%`"></div>
                </div>

                {{-- Steps --}}
                <template x-for="step in steps" :key="step.number">
                    <div class="flex flex-col items-center flex-1">
                        <button @click="goToStep(step.number)"
                                :class="[
                                    'w-16 h-16 rounded-full flex items-center justify-center font-bold text-lg transition-all duration-200 mb-2',
                                    currentStep === step.number ? 'bg-gradient-to-br from-green-500 to-emerald-500 text-white ring-4 ring-green-200 dark:ring-green-900 scale-110' :
                                    currentStep > step.number ? 'bg-gradient-to-br from-blue-500 to-purple-500 text-white' :
                                    'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
                                ]">
                            <span x-show="currentStep > step.number">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span x-show="currentStep <= step.number" x-text="step.number"></span>
                        </button>
                        <p class="text-sm font-semibold text-center"
                           :class="currentStep >= step.number ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                           x-text="step.label"></p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Form --}}
        <form @submit.prevent="submitForm" id="createTokenForm">
            @csrf

            {{-- Step 1: Basic Information --}}
            <div x-show="currentStep === 1" class="bg-white dark:bg-gray-800 rounded-2xl p-8 border border-gray-200 dark:border-gray-700 shadow-xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ข้อมูลพื้นฐาน
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อ Token <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="formData.name" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="เช่น My Awesome Token">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สัญลักษณ์ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="symbol" x-model="formData.symbol" required
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white uppercase focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="เช่น MAT" maxlength="20">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" x-model="formData.description" required rows="4"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                                  placeholder="อธิบายวัตถุประสงค์และคุณสมบัติของ Token (อย่างน้อย 50 ตัวอักษร)"></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">อย่างน้อย 50 ตัวอักษร</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Total Supply <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="total_supply" x-model="formData.total_supply" required step="0.01"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="เช่น 1000000">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Decimals <span class="text-red-500">*</span>
                        </label>
                        <select name="decimals" x-model="formData.decimals" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="18">18 (มาตรฐาน)</option>
                            <option value="8">8</option>
                            <option value="6">6</option>
                            <option value="2">2</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            หมวดหมู่ <span class="text-red-500">*</span>
                        </label>
                        <select name="category" x-model="formData.category" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                            <option value="">เลือกหมวดหมู่</option>
                            <option value="defi">🏦 DeFi</option>
                            <option value="gamefi">🎮 GameFi</option>
                            <option value="meme">😂 Meme</option>
                            <option value="utility">🔧 Utility</option>
                            <option value="stablecoin">💵 Stablecoin</option>
                            <option value="nft">🎨 NFT</option>
                            <option value="dao">🏛️ DAO</option>
                            <option value="other">🔹 อื่นๆ</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Logo
                        </label>
                        <input type="file" name="logo" accept="image/*" @change="previewLogo($event)"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">PNG, JPG, SVG (สูงสุด 2MB)</p>
                        <div x-show="logoPreview" class="mt-3">
                            <img :src="logoPreview" class="w-20 h-20 rounded-full object-cover ring-4 ring-gray-200 dark:ring-gray-700">
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="button" @click="nextStep"
                            class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition duration-200 flex items-center gap-2">
                        ถัดไป
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Step 2: Features --}}
            <div x-show="currentStep === 2" class="bg-white dark:bg-gray-800 rounded-2xl p-8 border border-gray-200 dark:border-gray-700 shadow-xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    คุณสมบัติ Token
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-6 rounded-xl border-2 transition-all cursor-pointer hover:shadow-lg"
                         :class="formData.is_mintable ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-green-300'"
                         @click="formData.is_mintable = !formData.is_mintable">
                        <div class="flex items-start gap-4">
                            <input type="checkbox" name="is_mintable" x-model="formData.is_mintable"
                                   class="mt-1 w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-1">Mintable</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">อนุญาตให้สร้าง Token ใหม่ได้หลังจาก Deploy</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 rounded-xl border-2 transition-all cursor-pointer hover:shadow-lg"
                         :class="formData.is_burnable ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-red-300'"
                         @click="formData.is_burnable = !formData.is_burnable">
                        <div class="flex items-start gap-4">
                            <input type="checkbox" name="is_burnable" x-model="formData.is_burnable"
                                   class="mt-1 w-5 h-5 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-1">Burnable</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">อนุญาตให้ทำลาย Token เพื่อลด Supply</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 rounded-xl border-2 transition-all cursor-pointer hover:shadow-lg"
                         :class="formData.is_pausable ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-yellow-300'"
                         @click="formData.is_pausable = !formData.is_pausable">
                        <div class="flex items-start gap-4">
                            <input type="checkbox" name="is_pausable" x-model="formData.is_pausable"
                                   class="mt-1 w-5 h-5 text-yellow-600 bg-gray-100 border-gray-300 rounded focus:ring-yellow-500">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-1">Pausable</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">สามารถหยุดการโอน Token ชั่วคราวได้</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 rounded-xl border-2 transition-all cursor-pointer hover:shadow-lg"
                         :class="formData.is_freezable ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-blue-300'"
                         @click="formData.is_freezable = !formData.is_freezable">
                        <div class="flex items-start gap-4">
                            <input type="checkbox" name="is_freezable" x-model="formData.is_freezable"
                                   class="mt-1 w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-1">Freezable</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">สามารถ Freeze Address เฉพาะได้</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" @click="prevStep"
                            class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                        </svg>
                        ก่อนหน้า
                    </button>
                    <button type="button" @click="nextStep"
                            class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition duration-200 flex items-center gap-2">
                        ถัดไป
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Step 3: Tokenomics --}}
            <div x-show="currentStep === 3" class="bg-white dark:bg-gray-800 rounded-2xl p-8 border border-gray-200 dark:border-gray-700 shadow-xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tokenomics & ข้อมูลเพิ่มเติม
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ราคาเริ่มต้น (TPIX) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="initial_price_tpix" x-model="formData.initial_price_tpix" required step="0.00000001"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="เช่น 0.01">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เว็บไซต์
                        </label>
                        <input type="url" name="website" x-model="formData.website"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="https://yourtoken.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Whitepaper URL
                        </label>
                        <input type="url" name="whitepaper_url" x-model="formData.whitepaper_url"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="https://yourtoken.com/whitepaper.pdf">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            GitHub Repository
                        </label>
                        <input type="url" name="github_url" x-model="formData.github_url"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="https://github.com/yourproject">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Twitter Handle
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-gray-500 dark:text-gray-400">@</span>
                            <input type="text" name="twitter_handle" x-model="formData.twitter_handle"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                                   placeholder="yourtoken">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Telegram Group
                        </label>
                        <input type="url" name="telegram_group" x-model="formData.telegram_group"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="https://t.me/yourgroup">
                    </div>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" @click="prevStep"
                            class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                        </svg>
                        ก่อนหน้า
                    </button>
                    <button type="button" @click="nextStep"
                            class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition duration-200 flex items-center gap-2">
                        ตรวจสอบ
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Step 4: Review & Submit --}}
            <div x-show="currentStep === 4" class="bg-white dark:bg-gray-800 rounded-2xl p-8 border border-gray-200 dark:border-gray-700 shadow-xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ตรวจสอบและยืนยัน
                </h2>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        โปรดตรวจสอบข้อมูลของคุณอย่างละเอียด หลังจากส่งแล้ว Token ของคุณจะได้รับการตรวจสอบโดยทีมงานก่อนการ Deploy
                    </p>
                </div>

                <div class="space-y-6">
                    {{-- Basic Info Review --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">ชื่อ Token:</span>
                                <span class="ml-2 font-semibold text-gray-900 dark:text-white" x-text="formData.name || '-'"></span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">สัญลักษณ์:</span>
                                <span class="ml-2 font-semibold text-gray-900 dark:text-white" x-text="formData.symbol || '-'"></span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Total Supply:</span>
                                <span class="ml-2 font-semibold text-gray-900 dark:text-white" x-text="formData.total_supply || '-'"></span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Decimals:</span>
                                <span class="ml-2 font-semibold text-gray-900 dark:text-white" x-text="formData.decimals || '-'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Features Review --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">คุณสมบัติ</h3>
                        <div class="flex flex-wrap gap-2">
                            <span x-show="formData.is_mintable" class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-semibold">Mintable</span>
                            <span x-show="formData.is_burnable" class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-sm font-semibold">Burnable</span>
                            <span x-show="formData.is_pausable" class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm font-semibold">Pausable</span>
                            <span x-show="formData.is_freezable" class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-semibold">Freezable</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" x-model="agreedToTerms" required
                               class="mt-1 w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            ฉันยอมรับ <a href="#" class="text-green-600 dark:text-green-400 hover:underline">ข้อกำหนดการใช้งาน</a> และเข้าใจว่าการสร้าง Token ต้องผ่านการอนุมัติก่อน
                        </span>
                    </label>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" @click="prevStep"
                            class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                        </svg>
                        ก่อนหน้า
                    </button>
                    <button type="submit" :disabled="!agreedToTerms"
                            class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        สร้าง Token
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับ Create Token Form
 */
function createTokenForm() {
    return {
        // State
        currentStep: 1,
        logoPreview: null,
        agreedToTerms: false,

        // Steps configuration
        steps: [
            { number: 1, label: 'ข้อมูลพื้นฐาน' },
            { number: 2, label: 'คุณสมบัติ' },
            { number: 3, label: 'Tokenomics' },
            { number: 4, label: 'ตรวจสอบ' }
        ],

        // Form data
        formData: {
            name: '',
            symbol: '',
            description: '',
            total_supply: '',
            decimals: '18',
            category: '',
            is_mintable: false,
            is_burnable: true,
            is_pausable: false,
            is_freezable: false,
            initial_price_tpix: '',
            website: '',
            whitepaper_url: '',
            github_url: '',
            twitter_handle: '',
            telegram_group: ''
        },

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Create Token Form initialized');
        },

        /**
         * ไปขั้นตอนถัดไป
         */
        nextStep() {
            if (this.currentStep < 4) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        /**
         * กลับขั้นตอนก่อนหน้า
         */
        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        /**
         * ไปยังขั้นตอนที่ระบุ
         */
        goToStep(step) {
            if (step <= this.currentStep || step === this.currentStep + 1) {
                this.currentStep = step;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        /**
         * แสดงตัวอย่าง logo
         */
        previewLogo(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.logoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        /**
         * ส่งฟอร์ม
         */
        submitForm() {
            // Form จะถูก submit ตามปกติผ่าน Laravel
            document.getElementById('createTokenForm').submit();
        }
    };
}
</script>
@endpush
@endsection
