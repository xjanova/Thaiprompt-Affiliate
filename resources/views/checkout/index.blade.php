{{--
    Checkout Page - Multi-Step Wizard (V3)

    หน้า Checkout แบบ Multi-step พร้อมแสดง PV, Commission, Cashback
    รองรับ MLM features และ Multi-vendor

    Steps:
    1. ข้อมูลจัดส่ง (Shipping Information)
    2. ชำระเงิน (Payment Method)
    3. ตรวจสอบคำสั่งซื้อ (Review Order)

    @extends layouts.user-arrow-x
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ชำระเงิน - Checkout')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="checkoutComponent()">

    {{-- Progress Indicator --}}
    <div class="max-w-4xl mx-auto mb-8">
        <div class="flex items-center justify-between">
            {{-- Step 1: Shipping --}}
            <div class="flex flex-col items-center flex-1">
                <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
                     :class="currentStep >= 1 ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <span class="text-sm mt-2 font-medium"
                      :class="currentStep >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">
                    ข้อมูลจัดส่ง
                </span>
            </div>

            {{-- Connector Line --}}
            <div class="flex-1 h-1 mx-4 transition-all duration-300"
                 :class="currentStep >= 2 ? 'bg-gradient-to-r from-purple-600 to-pink-600' : 'bg-gray-200 dark:bg-gray-700'">
            </div>

            {{-- Step 2: Payment --}}
            <div class="flex flex-col items-center flex-1">
                <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
                     :class="currentStep >= 2 ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                    <i class="fas fa-credit-card"></i>
                </div>
                <span class="text-sm mt-2 font-medium"
                      :class="currentStep >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">
                    ชำระเงิน
                </span>
            </div>

            {{-- Connector Line --}}
            <div class="flex-1 h-1 mx-4 transition-all duration-300"
                 :class="currentStep >= 3 ? 'bg-gradient-to-r from-purple-600 to-pink-600' : 'bg-gray-200 dark:bg-gray-700'">
            </div>

            {{-- Step 3: Review --}}
            <div class="flex flex-col items-center flex-1">
                <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
                     :class="currentStep >= 3 ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="text-sm mt-2 font-medium"
                      :class="currentStep >= 3 ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">
                    ตรวจสอบ
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2">
            {{-- Step 1: Shipping Information --}}
            <div x-show="currentStep === 1" x-transition class="glass-fusion-card rounded-2xl p-6 mb-6
                        backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                        border border-white/20 dark:border-gray-700/30 shadow-2xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-shipping-fast mr-2 text-purple-600"></i>
                    ข้อมูลการจัดส่ง
                </h2>

                <form @submit.prevent="nextStep()">
                    <div class="space-y-4">
                        {{-- Full Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อ-นามสกุล <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="shipping.fullName"
                                   required
                                   class="w-full px-4 py-3 rounded-xl
                                          bg-white dark:bg-gray-700
                                          border-2 border-gray-200 dark:border-gray-600
                                          focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                          text-gray-900 dark:text-gray-100
                                          transition-all"
                                   placeholder="ระบุชื่อ-นามสกุลผู้รับ">
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                            </label>
                            <input type="tel"
                                   x-model="shipping.phone"
                                   required
                                   pattern="[0-9]{10}"
                                   class="w-full px-4 py-3 rounded-xl
                                          bg-white dark:bg-gray-700
                                          border-2 border-gray-200 dark:border-gray-600
                                          focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                          text-gray-900 dark:text-gray-100
                                          transition-all"
                                   placeholder="0812345678">
                        </div>

                        {{-- Address --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ที่อยู่ <span class="text-red-500">*</span>
                            </label>
                            <textarea x-model="shipping.address"
                                      required
                                      rows="3"
                                      class="w-full px-4 py-3 rounded-xl
                                             bg-white dark:bg-gray-700
                                             border-2 border-gray-200 dark:border-gray-600
                                             focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                             text-gray-900 dark:text-gray-100
                                             transition-all"
                                      placeholder="บ้านเลขที่ หมู่บ้าน ซอย ถนน"></textarea>
                        </div>

                        {{-- Province & District --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    จังหวัด <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       x-model="shipping.province"
                                       required
                                       class="w-full px-4 py-3 rounded-xl
                                              bg-white dark:bg-gray-700
                                              border-2 border-gray-200 dark:border-gray-600
                                              focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                              text-gray-900 dark:text-gray-100
                                              transition-all"
                                       placeholder="เช่น กรุงเทพมหานคร">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    อำเภอ/เขต <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       x-model="shipping.district"
                                       required
                                       class="w-full px-4 py-3 rounded-xl
                                              bg-white dark:bg-gray-700
                                              border-2 border-gray-200 dark:border-gray-600
                                              focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                              text-gray-900 dark:text-gray-100
                                              transition-all"
                                       placeholder="เช่น บางรัก">
                            </div>
                        </div>

                        {{-- Sub-district & Postal Code --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ตำบล/แขวง <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       x-model="shipping.subDistrict"
                                       required
                                       class="w-full px-4 py-3 rounded-xl
                                              bg-white dark:bg-gray-700
                                              border-2 border-gray-200 dark:border-gray-600
                                              focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                              text-gray-900 dark:text-gray-100
                                              transition-all"
                                       placeholder="เช่น สีลม">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    รหัสไปรษณีย์ <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       x-model="shipping.postalCode"
                                       required
                                       pattern="[0-9]{5}"
                                       class="w-full px-4 py-3 rounded-xl
                                              bg-white dark:bg-gray-700
                                              border-2 border-gray-200 dark:border-gray-600
                                              focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                              text-gray-900 dark:text-gray-100
                                              transition-all"
                                       placeholder="10500">
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                หมายเหตุ (ถ้ามี)
                            </label>
                            <textarea x-model="shipping.notes"
                                      rows="2"
                                      class="w-full px-4 py-3 rounded-xl
                                             bg-white dark:bg-gray-700
                                             border-2 border-gray-200 dark:border-gray-600
                                             focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                             text-gray-900 dark:text-gray-100
                                             transition-all"
                                      placeholder="เช่น ห้ามส่งช่วงเย็น, ฝากยามบ้าน, ฯลฯ"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit"
                                class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600
                                       hover:from-purple-700 hover:to-pink-700
                                       text-white font-bold rounded-xl
                                       shadow-lg hover:shadow-xl
                                       transform hover:scale-105 active:scale-95
                                       transition-all duration-300
                                       flex items-center gap-2">
                            <span>ไปต่อ: เลือกวิธีชำระเงิน</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Step 2: Payment Method --}}
            <div x-show="currentStep === 2" x-transition class="glass-fusion-card rounded-2xl p-6 mb-6
                        backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                        border border-white/20 dark:border-gray-700/30 shadow-2xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-credit-card mr-2 text-purple-600"></i>
                    เลือกวิธีชำระเงิน
                </h2>

                <div class="space-y-4">
                    {{-- Cash on Delivery --}}
                    <label class="block cursor-pointer">
                        <div class="p-4 rounded-xl border-2 transition-all"
                             :class="payment.method === 'cod' ?
                                     'border-purple-600 bg-purple-50 dark:bg-purple-900/20' :
                                     'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                            <div class="flex items-center">
                                <input type="radio"
                                       x-model="payment.method"
                                       value="cod"
                                       class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-money-bill-wave text-green-600"></i>
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            ชำระเงินปลายทาง (COD)
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        ชำระเมื่อได้รับสินค้า (มีค่าธรรมเนียม ฿30)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>

                    {{-- Bank Transfer --}}
                    <label class="block cursor-pointer">
                        <div class="p-4 rounded-xl border-2 transition-all"
                             :class="payment.method === 'bank_transfer' ?
                                     'border-purple-600 bg-purple-50 dark:bg-purple-900/20' :
                                     'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                            <div class="flex items-center">
                                <input type="radio"
                                       x-model="payment.method"
                                       value="bank_transfer"
                                       class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-university text-blue-600"></i>
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            โอนเงินผ่านธนาคาร
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        โอนเงินเข้าบัญชีธนาคาร (ไม่มีค่าธรรมเนียม)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>

                    {{-- Credit Card --}}
                    <label class="block cursor-pointer">
                        <div class="p-4 rounded-xl border-2 transition-all"
                             :class="payment.method === 'credit_card' ?
                                     'border-purple-600 bg-purple-50 dark:bg-purple-900/20' :
                                     'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                            <div class="flex items-center">
                                <input type="radio"
                                       x-model="payment.method"
                                       value="credit_card"
                                       class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-credit-card text-purple-600"></i>
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            บัตรเครดิต/เดบิต
                                        </span>
                                        <span class="text-xs px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30
                                                     text-orange-700 dark:text-orange-300 rounded-full">
                                            เร็วๆ นี้
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        ชำระด้วยบัตรเครดิตหรือเดบิต (Visa, Mastercard)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>

                    {{-- PromptPay --}}
                    <label class="block cursor-pointer">
                        <div class="p-4 rounded-xl border-2 transition-all"
                             :class="payment.method === 'promptpay' ?
                                     'border-purple-600 bg-purple-50 dark:bg-purple-900/20' :
                                     'border-gray-200 dark:border-gray-700 hover:border-purple-300'">
                            <div class="flex items-center">
                                <input type="radio"
                                       x-model="payment.method"
                                       value="promptpay"
                                       class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-qrcode text-pink-600"></i>
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            พร้อมเพย์ (PromptPay)
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        สแกน QR Code ชำระผ่านแอปธนาคาร
                                    </p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="flex justify-between mt-6">
                    <button @click="prevStep()"
                            class="px-8 py-3 bg-gray-200 dark:bg-gray-700
                                   hover:bg-gray-300 dark:hover:bg-gray-600
                                   text-gray-700 dark:text-gray-300 font-bold rounded-xl
                                   transition-all
                                   flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>ย้อนกลับ</span>
                    </button>

                    <button @click="nextStep()"
                            :disabled="!payment.method"
                            class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600
                                   hover:from-purple-700 hover:to-pink-700
                                   text-white font-bold rounded-xl
                                   shadow-lg hover:shadow-xl
                                   transform hover:scale-105 active:scale-95
                                   transition-all duration-300
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   flex items-center gap-2">
                        <span>ไปต่อ: ตรวจสอบคำสั่งซื้อ</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            {{-- Step 3: Review Order --}}
            <div x-show="currentStep === 3" x-transition class="glass-fusion-card rounded-2xl p-6 mb-6
                        backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                        border border-white/20 dark:border-gray-700/30 shadow-2xl">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-check-circle mr-2 text-purple-600"></i>
                    ตรวจสอบคำสั่งซื้อ
                </h2>

                {{-- Shipping Address Review --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                            ที่อยู่จัดส่ง
                        </h3>
                        <button @click="currentStep = 1"
                                class="text-sm text-purple-600 dark:text-purple-400 hover:underline">
                            <i class="fas fa-edit mr-1"></i>
                            แก้ไข
                        </button>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="font-bold text-gray-900 dark:text-white" x-text="shipping.fullName"></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="shipping.phone"></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <span x-text="shipping.address"></span><br>
                            <span x-text="shipping.subDistrict + ', ' + shipping.district + ', ' + shipping.province + ' ' + shipping.postalCode"></span>
                        </p>
                        <template x-if="shipping.notes">
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-2 italic">
                                หมายเหตุ: <span x-text="shipping.notes"></span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- Payment Method Review --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            <i class="fas fa-credit-card mr-2 text-blue-500"></i>
                            วิธีชำระเงิน
                        </h3>
                        <button @click="currentStep = 2"
                                class="text-sm text-purple-600 dark:text-purple-400 hover:underline">
                            <i class="fas fa-edit mr-1"></i>
                            แก้ไข
                        </button>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="font-medium text-gray-900 dark:text-white" x-text="getPaymentMethodName()"></p>
                    </div>
                </div>

                {{-- Order Items --}}
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-shopping-bag mr-2 text-purple-500"></i>
                        รายการสินค้า
                    </h3>
                    <div class="space-y-3">
                        <template x-for="item in cartItems" :key="item.id">
                            <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <img :src="item.product.image || '/images/placeholder.png'"
                                     :alt="item.product.name"
                                     class="w-16 h-16 rounded-lg object-cover">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white" x-text="item.product.name"></h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        จำนวน: <span x-text="item.quantity"></span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        ฿<span x-text="(item.product.price * item.quantity).toFixed(2)"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Terms and Conditions --}}
                <div class="mb-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox"
                               x-model="agreedToTerms"
                               class="mt-1 w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            ฉันยอมรับ
                            <a href="/terms" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">
                                เงื่อนไขและข้อตกลง
                            </a>
                            และ
                            <a href="/privacy" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">
                                นโยบายความเป็นส่วนตัว
                            </a>
                        </span>
                    </label>
                </div>

                <div class="flex justify-between">
                    <button @click="prevStep()"
                            class="px-8 py-3 bg-gray-200 dark:bg-gray-700
                                   hover:bg-gray-300 dark:hover:bg-gray-600
                                   text-gray-700 dark:text-gray-300 font-bold rounded-xl
                                   transition-all
                                   flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>ย้อนกลับ</span>
                    </button>

                    <button @click="placeOrder()"
                            :disabled="!agreedToTerms || isPlacingOrder"
                            class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600
                                   hover:from-green-700 hover:to-emerald-700
                                   text-white font-bold rounded-xl text-lg
                                   shadow-lg hover:shadow-xl
                                   transform hover:scale-105 active:scale-95
                                   transition-all duration-300
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   flex items-center gap-2">
                        <i class="fas" :class="isPlacingOrder ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
                        <span x-text="isPlacingOrder ? 'กำลังสั่งซื้อ...' : 'ยืนยันคำสั่งซื้อ'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Order Summary Sidebar --}}
        <div class="lg:col-span-1">
            <div class="glass-fusion-card rounded-2xl p-6 sticky top-4
                        backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                        border border-white/20 dark:border-gray-700/30 shadow-2xl">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    สรุปคำสั่งซื้อ
                </h3>

                {{-- Subtotal --}}
                <div class="flex justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">ยอดรวมสินค้า</span>
                    <span class="font-bold text-gray-900 dark:text-white">
                        ฿<span x-text="getSubtotal().toFixed(2)"></span>
                    </span>
                </div>

                {{-- Shipping Cost --}}
                <div class="flex justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">ค่าจัดส่ง</span>
                    <span class="font-bold"
                          :class="getShippingCost() === 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'">
                        <template x-if="getShippingCost() === 0">
                            <span>ฟรี</span>
                        </template>
                        <template x-if="getShippingCost() > 0">
                            <span>฿<span x-text="getShippingCost().toFixed(2)"></span></span>
                        </template>
                    </span>
                </div>

                {{-- COD Fee (if applicable) --}}
                <div x-show="payment.method === 'cod'"
                     x-transition
                     class="flex justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียม COD</span>
                    <span class="font-bold text-gray-900 dark:text-white">
                        ฿30.00
                    </span>
                </div>

                {{-- Discount (if any) --}}
                <div x-show="discount > 0"
                     x-transition
                     class="flex justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">ส่วนลด</span>
                    <span class="font-bold text-red-600 dark:text-red-400">
                        -฿<span x-text="discount.toFixed(2)"></span>
                    </span>
                </div>

                {{-- Total --}}
                <div class="flex justify-between py-4 border-b-2 border-gray-300 dark:border-gray-600">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">ยอดรวมทั้งหมด</span>
                    <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        ฿<span x-text="getTotal().toFixed(2)"></span>
                    </span>
                </div>

                {{-- MLM Benefits (for authenticated affiliate members) --}}
                @if(auth()->check() && optional(auth()->user()->mlmMember)->exists())
                <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20
                            rounded-xl border border-blue-200 dark:border-blue-800">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-gift text-purple-600"></i>
                        สิทธิประโยชน์ MLM
                    </h4>

                    <div class="space-y-2">
                        {{-- Total PV --}}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                <i class="fas fa-coins text-blue-500 text-xs"></i>
                                PV รวม
                            </span>
                            <span class="font-bold text-blue-600 dark:text-blue-400" x-text="getTotalPV()"></span>
                        </div>

                        {{-- Estimated Commission --}}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                <i class="fas fa-percent text-green-500 text-xs"></i>
                                ค่าคอมมิชชั่น (โดยประมาณ)
                            </span>
                            <span class="font-bold text-green-600 dark:text-green-400">
                                ฿<span x-text="getEstimatedCommission().toFixed(2)"></span>
                            </span>
                        </div>

                        {{-- Cashback --}}
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                <i class="fas fa-gift text-purple-500 text-xs"></i>
                                Cashback
                            </span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">
                                ฿<span x-text="getTotalCashback().toFixed(2)"></span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-800">
                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                            * คอมมิชชั่นจะถูกคำนวณหลังจากยืนยันการชำระเงินและจัดส่ง
                        </p>
                    </div>
                </div>
                @endif

                {{-- Secure Checkout Badge --}}
                <div class="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-lock"></i>
                    <span>การชำระเงินปลอดภัย SSL</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Checkout Component - จัดการ Multi-step checkout process
 */
function checkoutComponent() {
    return {
        // Current step (1-3)
        currentStep: 1,

        // Shipping information
        shipping: {
            fullName: '',
            phone: '',
            address: '',
            province: '',
            district: '',
            subDistrict: '',
            postalCode: '',
            notes: '',
        },

        // Payment information
        payment: {
            method: '', // cod, bank_transfer, credit_card, promptpay
        },

        // Cart items (mock data - should be fetched from API)
        cartItems: [],

        // Order summary
        discount: 0,
        freeShippingThreshold: 500,

        // Terms agreement
        agreedToTerms: false,

        // Loading state
        isPlacingOrder: false,

        /**
         * เริ่มต้น component
         */
        init() {
            this.loadCartItems();
            this.loadSavedShippingInfo();
        },

        /**
         * โหลดข้อมูลตะกร้า
         */
        async loadCartItems() {
            try {
                const response = await fetch('/api/cart');
                const data = await response.json();

                if (data.success) {
                    this.cartItems = data.data.items || [];
                }
            } catch (error) {
                console.error('Load cart error:', error);
                // Mock data for demo
                this.cartItems = [
                    {
                        id: 1,
                        product: {
                            id: 1,
                            name: 'สินค้าตัวอย่าง 1',
                            price: 299,
                            image: '/images/placeholder.png',
                            pv_value: 10,
                            commission_rate: 5,
                            customer_cashback: 15,
                        },
                        quantity: 2,
                    },
                    {
                        id: 2,
                        product: {
                            id: 2,
                            name: 'สินค้าตัวอย่าง 2',
                            price: 499,
                            image: '/images/placeholder.png',
                            pv_value: 20,
                            commission_rate: 10,
                            customer_cashback: 25,
                        },
                        quantity: 1,
                    },
                ];
            }
        },

        /**
         * โหลดข้อมูลที่อยู่ที่บันทึกไว้
         */
        loadSavedShippingInfo() {
            // TODO: Load from user profile or localStorage
            @if(auth()->check() && auth()->user()->address)
            this.shipping = {
                fullName: '{{ auth()->user()->name }}',
                phone: '{{ auth()->user()->phone ?? '' }}',
                address: '{{ auth()->user()->address ?? '' }}',
                province: '{{ auth()->user()->province ?? '' }}',
                district: '{{ auth()->user()->district ?? '' }}',
                subDistrict: '{{ auth()->user()->sub_district ?? '' }}',
                postalCode: '{{ auth()->user()->postal_code ?? '' }}',
                notes: '',
            };
            @endif
        },

        /**
         * ไปขั้นตอนถัดไป
         */
        nextStep() {
            if (this.currentStep < 3) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        /**
         * ย้อนกลับขั้นตอนก่อนหน้า
         */
        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        /**
         * คำนวณยอดรวมสินค้า
         */
        getSubtotal() {
            return this.cartItems.reduce((sum, item) => {
                return sum + (item.product.price * item.quantity);
            }, 0);
        },

        /**
         * คำนวณค่าจัดส่ง
         */
        getShippingCost() {
            const subtotal = this.getSubtotal();
            return subtotal >= this.freeShippingThreshold ? 0 : 50;
        },

        /**
         * คำนวณค่าธรรมเนียม COD
         */
        getCODFee() {
            return this.payment.method === 'cod' ? 30 : 0;
        },

        /**
         * คำนวณยอดรวมทั้งหมด
         */
        getTotal() {
            return this.getSubtotal() +
                   this.getShippingCost() +
                   this.getCODFee() -
                   this.discount;
        },

        /**
         * คำนวณ PV รวม
         */
        getTotalPV() {
            return this.cartItems.reduce((sum, item) => {
                const pv = item.product.pv_value || 0;
                return sum + (pv * item.quantity);
            }, 0);
        },

        /**
         * คำนวณค่าคอมมิชชั่นโดยประมาณ
         */
        getEstimatedCommission() {
            return this.cartItems.reduce((sum, item) => {
                const rate = (item.product.commission_rate || 0) / 100;
                const amount = item.product.price * item.quantity * rate;
                return sum + amount;
            }, 0);
        },

        /**
         * คำนวณ Cashback รวม
         */
        getTotalCashback() {
            return this.cartItems.reduce((sum, item) => {
                const cashback = item.product.customer_cashback || 0;
                return sum + (cashback * item.quantity);
            }, 0);
        },

        /**
         * แปลงชื่อวิธีชำระเงิน
         */
        getPaymentMethodName() {
            const methods = {
                cod: 'ชำระเงินปลายทาง (COD)',
                bank_transfer: 'โอนเงินผ่านธนาคาร',
                credit_card: 'บัตรเครดิต/เดบิต',
                promptpay: 'พร้อมเพย์ (PromptPay)',
            };
            return methods[this.payment.method] || 'ยังไม่ได้เลือก';
        },

        /**
         * สั่งซื้อสินค้า
         */
        async placeOrder() {
            if (!this.agreedToTerms) {
                alert('กรุณายอมรับเงื่อนไขและข้อตกลง');
                return;
            }

            this.isPlacingOrder = true;

            try {
                const response = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        shipping: this.shipping,
                        payment_method: this.payment.method,
                        items: this.cartItems.map(item => ({
                            product_id: item.product.id,
                            quantity: item.quantity,
                            price: item.product.price,
                        })),
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    // แสดงข้อความสำเร็จ
                    this.$dispatch('notify', {
                        message: 'สั่งซื้อสำเร็จ! เลขที่คำสั่งซื้อ: ' + data.data.order_number,
                        type: 'success',
                    });

                    // Redirect ไปหน้า order success
                    setTimeout(() => {
                        window.location.href = '/orders/' + data.data.id + '/success';
                    }, 1500);
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Place order error:', error);
                this.$dispatch('notify', {
                    message: 'ไม่สามารถสั่งซื้อได้: ' + error.message,
                    type: 'error',
                });
            } finally {
                this.isPlacingOrder = false;
            }
        },
    };
}
</script>
@endpush
@endsection
