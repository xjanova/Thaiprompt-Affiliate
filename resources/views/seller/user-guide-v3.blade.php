{{-- resources/views/seller/user-guide-v3.blade.php --}}
{{--
 * หน้าคู่มือการใช้งานสำหรับผู้ขาย (Seller)
 *
 * แสดงคู่มือการใช้งานระบบ TP-Affiliate สำหรับผู้ขาย
 * รวมถึงการจัดการสินค้า, ออเดอร์, และเครื่องมือขาย
 *
 * @author TP-Affiliate Development Team
--}}
@extends('layouts.seller')

@section('title', 'คู่มือการใช้งาน')
@section('page-title', 'คู่มือการใช้งาน')

@section('content')
<div x-data="sellerGuideManager()" class="space-y-6">
    {{-- Hero Section --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl p-8 relative">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0 bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500"></div>
        </div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg">
                        <i class="fas fa-store mr-3"></i>
                        คู่มือสำหรับผู้ขาย
                    </h1>
                    <p class="text-white/80 text-lg">
                        เรียนรู้การใช้งานระบบร้านค้าและเครื่องมือขายครบวงจร
                    </p>
                </div>
                <div class="hidden md:block">
                    <div class="w-32 h-32 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center shadow-2xl animate-pulse">
                        <i class="fas fa-store text-6xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">40+</div>
                    <div class="text-white/70 text-sm">บทเรียน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">20+</div>
                    <div class="text-white/70 text-sm">วิดีโอสอน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">80+</div>
                    <div class="text-white/70 text-sm">FAQ</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">24/7</div>
                    <div class="text-white/70 text-sm">Support</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="glass-fusion rounded-xl border border-white/30 shadow-xl p-4">
        <div class="relative">
            <input type="text"
                   x-model="searchQuery"
                   @input="filterContent()"
                   placeholder="ค้นหาคู่มือ, FAQ, วิธีใช้งาน..."
                   class="w-full px-6 py-4 pl-14 bg-white/10 text-white placeholder-white/60 rounded-xl border border-white/20 focus:border-white/40 focus:ring-2 focus:ring-white/20 transition-all text-lg">
            <div class="absolute left-5 top-1/2 -translate-y-1/2 pointer-events-none">
                <i class="fas fa-search text-2xl text-white/60 drop-shadow"></i>
            </div>
            <template x-if="searchQuery.length > 0">
                <button @click="searchQuery = ''; filterContent()"
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-white/60 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </template>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="glass-fusion rounded-xl border border-white/30 shadow-xl overflow-hidden">
        <div class="border-b border-white/20">
            <nav class="flex overflow-x-auto -mb-px">
                <template x-for="tab in tabs" :key="tab.id">
                    <button @click="currentTab = tab.id"
                            :class="{
                                'border-b-2 border-green-400 bg-white/10': currentTab === tab.id,
                                'border-transparent': currentTab !== tab.id
                            }"
                            class="px-6 py-4 text-sm font-semibold text-white hover:bg-white/5 transition-all whitespace-nowrap">
                        <i :class="tab.icon" class="mr-2"></i>
                        <span x-text="tab.label"></span>
                    </button>
                </template>
            </nav>
        </div>

        {{-- Tab Contents --}}
        <div class="p-6">
            {{-- Getting Started Tab --}}
            <div x-show="currentTab === 'getting-started'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-rocket mr-2"></i>
                    เริ่มต้นขายสินค้า
                </h2>

                {{-- Step Cards --}}
                <div class="grid md:grid-cols-2 gap-6">
                    <template x-for="(step, index) in gettingStartedSteps" :key="index">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:border-white/40 transition cursor-pointer group">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:scale-110 transition">
                                    <span x-text="index + 1"></span>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-white mb-2" x-text="step.title"></h3>
                                    <p class="text-white/70 text-sm" x-text="step.description"></p>
                                    <button @click="openStepDetail(step)"
                                            class="mt-3 text-green-400 hover:text-green-300 text-sm font-medium inline-flex items-center gap-2">
                                        <span>เรียนรู้เพิ่มเติม</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Products Tab --}}
            <div x-show="currentTab === 'products'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-box mr-2"></i>
                    การจัดการสินค้า
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <template x-for="item in productGuide" :key="item.id">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:border-white/40 transition group">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br flex items-center justify-center text-2xl shadow-lg"
                                     :class="item.gradient">
                                    <i :class="item.icon" class="text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-white mb-2" x-text="item.title"></h3>
                                    <p class="text-white/70 text-sm" x-text="item.description"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Orders Tab --}}
            <div x-show="currentTab === 'orders'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    การจัดการออเดอร์
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <template x-for="item in orderGuide" :key="item.id">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:border-white/40 transition group">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br flex items-center justify-center text-2xl shadow-lg"
                                     :class="item.gradient">
                                    <i :class="item.icon" class="text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-white mb-2" x-text="item.title"></h3>
                                    <p class="text-white/70 text-sm" x-text="item.description"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- POS Tab --}}
            <div x-show="currentTab === 'pos'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-cash-register mr-2"></i>
                    ระบบ POS (ขายหน้าร้าน)
                </h2>

                <div class="grid md:grid-cols-3 gap-6">
                    <template x-for="feature in posFeatures" :key="feature.id">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:border-white/40 transition hover:shadow-2xl cursor-pointer group">
                            <div class="mb-4">
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br flex items-center justify-center text-3xl shadow-lg group-hover:scale-110 transition"
                                     :class="feature.gradient">
                                    <i :class="feature.icon" class="text-white drop-shadow"></i>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2" x-text="feature.title"></h3>
                            <p class="text-white/70 text-sm mb-4" x-text="feature.description"></p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Analytics Tab --}}
            <div x-show="currentTab === 'analytics'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-chart-bar mr-2"></i>
                    รายงานและวิเคราะห์
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <template x-for="item in analyticsGuide" :key="item.id">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:border-white/40 transition group">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br flex items-center justify-center text-2xl shadow-lg"
                                     :class="item.gradient">
                                    <i :class="item.icon" class="text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-white mb-2" x-text="item.title"></h3>
                                    <p class="text-white/70 text-sm" x-text="item.description"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- FAQ Tab --}}
            <div x-show="currentTab === 'faq'" x-transition class="space-y-4">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-question-circle mr-2"></i>
                    คำถามที่พบบ่อย (FAQ)
                </h2>

                <div class="space-y-3">
                    <template x-for="(faq, index) in faqs" :key="index">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl border border-white/20 overflow-hidden">
                            <button @click="toggleFaq(index)"
                                    class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-white/5 transition">
                                <span class="flex items-center gap-3 flex-1">
                                    <i class="fas fa-question-circle text-green-400"></i>
                                    <span class="font-medium text-white" x-text="faq.question"></span>
                                </span>
                                <i class="fas fa-chevron-down text-white/60 transition-transform"
                                   :class="{ 'rotate-180': faq.open }"></i>
                            </button>
                            <div x-show="faq.open"
                                 x-collapse
                                 class="px-6 pb-4">
                                <div class="pl-8 text-white/70" x-text="faq.answer"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Videos Tab --}}
            <div x-show="currentTab === 'videos'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-video mr-2"></i>
                    วิดีโอสอนการใช้งาน
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <template x-for="video in videos" :key="video.id">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl overflow-hidden border border-white/20 hover:border-white/40 transition hover:shadow-2xl cursor-pointer group">
                            {{-- Thumbnail --}}
                            <div class="relative aspect-video bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <i class="fas fa-play-circle text-6xl text-white/80 group-hover:text-white transition"></i>
                                <div class="absolute bottom-2 right-2 px-2 py-1 bg-black/70 rounded text-white text-xs" x-text="video.duration"></div>
                            </div>
                            {{-- Info --}}
                            <div class="p-4">
                                <h3 class="font-bold text-white mb-2 line-clamp-2" x-text="video.title"></h3>
                                <p class="text-white/60 text-xs mb-3" x-text="video.description"></p>
                                <button @click="playVideo(video)"
                                        class="w-full px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition font-medium text-sm">
                                    <i class="fas fa-play mr-2"></i>
                                    เล่นวิดีโอ
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Support Tab --}}
            <div x-show="currentTab === 'support'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-headset mr-2"></i>
                    ติดต่อฝ่ายสนับสนุน
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    {{-- Contact Methods --}}
                    <div class="space-y-4">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                    <i class="fab fa-line text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white">LINE Official (ผู้ขาย)</h3>
                                    <p class="text-white/60 text-sm">@thaiprompt-seller</p>
                                </div>
                            </div>
                            <button class="w-full px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition font-medium">
                                <i class="fab fa-line mr-2"></i>
                                เพิ่มเพื่อน
                            </button>
                        </div>

                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-envelope text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white">Email Support</h3>
                                    <p class="text-white/60 text-sm">seller@thaiprompt.com</p>
                                </div>
                            </div>
                            <button class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-lg hover:from-blue-600 hover:to-cyan-700 transition font-medium">
                                <i class="fas fa-paper-plane mr-2"></i>
                                ส่งอีเมล
                            </button>
                        </div>

                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-phone text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white">Hotline ผู้ขาย</h3>
                                    <p class="text-white/60 text-sm">09:00 - 18:00 น.</p>
                                </div>
                            </div>
                            <button class="w-full px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition font-medium">
                                <i class="fas fa-phone mr-2"></i>
                                โทรหาเรา
                            </button>
                        </div>
                    </div>

                    {{-- Contact Form --}}
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <h3 class="text-xl font-bold text-white mb-4">ส่งคำถามถึงเรา</h3>
                        <form @submit.prevent="submitContactForm" class="space-y-4">
                            <div>
                                <label class="block text-white text-sm font-medium mb-2">หัวข้อ</label>
                                <input type="text"
                                       x-model="contactForm.subject"
                                       class="w-full px-4 py-2 bg-white/10 text-white placeholder-white/50 rounded-lg border border-white/20 focus:border-white/40 focus:ring-2 focus:ring-white/20"
                                       placeholder="เรื่องที่ต้องการสอบถาม">
                            </div>
                            <div>
                                <label class="block text-white text-sm font-medium mb-2">ข้อความ</label>
                                <textarea x-model="contactForm.message"
                                          rows="4"
                                          class="w-full px-4 py-2 bg-white/10 text-white placeholder-white/50 rounded-lg border border-white/20 focus:border-white/40 focus:ring-2 focus:ring-white/20"
                                          placeholder="รายละเอียดคำถาม..."></textarea>
                            </div>
                            <button type="submit"
                                    class="w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition font-bold shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i>
                                ส่งคำถาม
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Seller Guide Manager - Alpine.js Component สำหรับผู้ขาย
 */
function sellerGuideManager() {
    return {
        currentTab: 'getting-started',
        searchQuery: '',

        tabs: [
            { id: 'getting-started', label: 'เริ่มต้นขาย', icon: 'fas fa-rocket' },
            { id: 'products', label: 'จัดการสินค้า', icon: 'fas fa-box' },
            { id: 'orders', label: 'จัดการออเดอร์', icon: 'fas fa-shopping-cart' },
            { id: 'pos', label: 'ระบบ POS', icon: 'fas fa-cash-register' },
            { id: 'analytics', label: 'รายงาน', icon: 'fas fa-chart-bar' },
            { id: 'faq', label: 'FAQ', icon: 'fas fa-question-circle' },
            { id: 'videos', label: 'วิดีโอ', icon: 'fas fa-video' },
            { id: 'support', label: 'ติดต่อเรา', icon: 'fas fa-headset' }
        ],

        gettingStartedSteps: [
            {
                title: '1. สมัครเป็นผู้ขาย',
                description: 'ลงทะเบียนเป็นผู้ขายและรอการอนุมัติจากทีมงาน'
            },
            {
                title: '2. ตั้งค่าร้านค้า',
                description: 'กำหนดชื่อร้าน โลโก้ และข้อมูลร้านค้า'
            },
            {
                title: '3. เพิ่มสินค้าแรก',
                description: 'เพิ่มสินค้าพร้อมรูปภาพและรายละเอียด'
            },
            {
                title: '4. ตั้งค่าการจัดส่ง',
                description: 'กำหนดค่าจัดส่งและระยะเวลาจัดส่ง'
            },
            {
                title: '5. เปิดร้านค้า',
                description: 'เปิดร้านเพื่อเริ่มรับออเดอร์จากลูกค้า'
            },
            {
                title: '6. จัดการและส่งสินค้า',
                description: 'รับออเดอร์ แพ็คสินค้า และจัดส่งให้ลูกค้า'
            }
        ],

        productGuide: [
            {
                id: 1,
                title: 'เพิ่มสินค้าใหม่',
                description: 'วิธีเพิ่มสินค้าใหม่พร้อมรูปภาพและตัวเลือก',
                icon: 'fas fa-plus-circle',
                gradient: 'from-green-500 to-emerald-600'
            },
            {
                id: 2,
                title: 'แก้ไขข้อมูลสินค้า',
                description: 'อัพเดทราคา รูปภาพ และรายละเอียดสินค้า',
                icon: 'fas fa-edit',
                gradient: 'from-blue-500 to-indigo-600'
            },
            {
                id: 3,
                title: 'จัดการสต็อก',
                description: 'ติดตามและอัพเดทจำนวนสินค้าคงคลัง',
                icon: 'fas fa-warehouse',
                gradient: 'from-yellow-500 to-orange-600'
            },
            {
                id: 4,
                title: 'ตัวเลือกสินค้า (Variants)',
                description: 'สร้างตัวเลือกสี ไซส์ และแบบต่างๆ',
                icon: 'fas fa-palette',
                gradient: 'from-purple-500 to-pink-600'
            }
        ],

        orderGuide: [
            {
                id: 1,
                title: 'รับออเดอร์ใหม่',
                description: 'ดูและยืนยันออเดอร์ที่เข้ามาใหม่',
                icon: 'fas fa-bell',
                gradient: 'from-green-500 to-emerald-600'
            },
            {
                id: 2,
                title: 'เตรียมสินค้า',
                description: 'แพ็คสินค้าและเตรียมจัดส่ง',
                icon: 'fas fa-box-open',
                gradient: 'from-blue-500 to-indigo-600'
            },
            {
                id: 3,
                title: 'ใส่เลขพัสดุ',
                description: 'อัพเดทเลขพัสดุเพื่อให้ลูกค้าติดตามได้',
                icon: 'fas fa-shipping-fast',
                gradient: 'from-yellow-500 to-orange-600'
            },
            {
                id: 4,
                title: 'จัดการคืนสินค้า',
                description: 'ดำเนินการกรณีลูกค้าขอคืนสินค้า',
                icon: 'fas fa-undo',
                gradient: 'from-red-500 to-pink-600'
            }
        ],

        posFeatures: [
            {
                id: 1,
                title: 'ขายหน้าร้าน',
                description: 'ระบบ POS สำหรับขายหน้าร้านแบบเรียลไทม์',
                icon: 'fas fa-store',
                gradient: 'from-green-500 to-emerald-600'
            },
            {
                id: 2,
                title: 'สแกนบาร์โค้ด',
                description: 'สแกนสินค้าด้วยบาร์โค้ดหรือ QR Code',
                icon: 'fas fa-barcode',
                gradient: 'from-blue-500 to-indigo-600'
            },
            {
                id: 3,
                title: 'รับชำระเงิน',
                description: 'รองรับหลายช่องทาง: เงินสด, QR, บัตร',
                icon: 'fas fa-credit-card',
                gradient: 'from-yellow-500 to-orange-600'
            },
            {
                id: 4,
                title: 'พิมพ์ใบเสร็จ',
                description: 'พิมพ์ใบเสร็จผ่านเครื่องพิมพ์หรือส่งทาง LINE',
                icon: 'fas fa-print',
                gradient: 'from-purple-500 to-pink-600'
            },
            {
                id: 5,
                title: 'จัดการกะ',
                description: 'เปิด-ปิดกะและสรุปยอดขายประจำวัน',
                icon: 'fas fa-clock',
                gradient: 'from-red-500 to-pink-600'
            },
            {
                id: 6,
                title: 'รายงานขาย',
                description: 'ดูสรุปยอดขายและสถิติแบบเรียลไทม์',
                icon: 'fas fa-chart-line',
                gradient: 'from-indigo-500 to-purple-600'
            }
        ],

        analyticsGuide: [
            {
                id: 1,
                title: 'ภาพรวมยอดขาย',
                description: 'ดูยอดขายรายวัน รายเดือน และรายปี',
                icon: 'fas fa-chart-line',
                gradient: 'from-green-500 to-emerald-600'
            },
            {
                id: 2,
                title: 'สินค้าขายดี',
                description: 'วิเคราะห์สินค้าที่ขายดีและขายไม่ดี',
                icon: 'fas fa-fire',
                gradient: 'from-orange-500 to-red-600'
            },
            {
                id: 3,
                title: 'ข้อมูลลูกค้า',
                description: 'เข้าใจพฤติกรรมและกลุ่มลูกค้าของคุณ',
                icon: 'fas fa-users',
                gradient: 'from-blue-500 to-indigo-600'
            },
            {
                id: 4,
                title: 'รายงาน AI',
                description: 'รับคำแนะนำจาก AI เพื่อเพิ่มยอดขาย',
                icon: 'fas fa-robot',
                gradient: 'from-purple-500 to-pink-600'
            }
        ],

        faqs: [
            {
                question: 'ค่าธรรมเนียมการขายเท่าไหร่?',
                answer: 'ค่าธรรมเนียมการขายอยู่ที่ 3-5% ของยอดขาย ขึ้นอยู่กับแพ็คเกจที่เลือกใช้งาน',
                open: false
            },
            {
                question: 'รับเงินจากการขายเมื่อไหร่?',
                answer: 'เงินจะโอนเข้าบัญชีทุกวันจันทร์ โดยจะเป็นยอดขายของสัปดาห์ก่อนหน้า',
                open: false
            },
            {
                question: 'เพิ่มสินค้าได้กี่ชิ้น?',
                answer: 'ขึ้นอยู่กับแพ็คเกจ: Free = 50 ชิ้น, Basic = 500 ชิ้น, Pro = ไม่จำกัด',
                open: false
            },
            {
                question: 'ใช้ระบบ POS ได้อย่างไร?',
                answer: 'หลังสมัครแพ็คเกจ Pro ขึ้นไป คุณสามารถเปิดใช้งานระบบ POS ได้ทันทีที่เมนู "ระบบ POS"',
                open: false
            },
            {
                question: 'มีระบบจัดการพนักงานหรือไม่?',
                answer: 'มีค่ะ! ในแพ็คเกจ Pro มีระบบ ERP ฟรีสำหรับจัดการพนักงาน, ตำแหน่ง, และกะการทำงาน',
                open: false
            }
        ],

        videos: [
            {
                id: 1,
                title: 'เริ่มต้นเปิดร้านค้า',
                description: 'สอนการตั้งค่าร้านค้าและเพิ่มสินค้าแรก',
                duration: '08:30'
            },
            {
                id: 2,
                title: 'วิธีจัดการออเดอร์',
                description: 'ขั้นตอนรับออเดอร์ แพ็คสินค้า จนถึงจัดส่ง',
                duration: '10:45'
            },
            {
                id: 3,
                title: 'การใช้งานระบบ POS',
                description: 'สอนใช้ระบบขายหน้าร้านแบบละเอียด',
                duration: '15:20'
            },
            {
                id: 4,
                title: 'วิเคราะห์ข้อมูลการขาย',
                description: 'อ่านและใช้งานรายงานต่างๆ ให้เป็นประโยชน์',
                duration: '12:15'
            },
            {
                id: 5,
                title: 'การใช้งาน AI Insights',
                description: 'รับคำแนะนำจาก AI เพื่อเพิ่มยอดขาย',
                duration: '07:50'
            },
            {
                id: 6,
                title: 'จัดการพนักงานด้วย ERP',
                description: 'ใช้งานระบบ ERP ฟรีสำหรับจัดการพนักงาน',
                duration: '09:30'
            }
        ],

        contactForm: {
            subject: '',
            message: ''
        },

        filterContent() {
            console.log('Searching for:', this.searchQuery);
        },

        toggleFaq(index) {
            this.faqs[index].open = !this.faqs[index].open;
        },

        openStepDetail(step) {
            alert('เปิดรายละเอียด: ' + step.title);
        },

        playVideo(video) {
            alert('เล่นวิดีโอ: ' + video.title);
        },

        async submitContactForm() {
            try {
                alert('✅ ส่งคำถามสำเร็จ!\n\nทีมงาน Seller Support จะติดต่อกลับภายใน 24 ชั่วโมง');
                this.contactForm = { subject: '', message: '' };
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            }
        }
    }
}
</script>
@endsection
