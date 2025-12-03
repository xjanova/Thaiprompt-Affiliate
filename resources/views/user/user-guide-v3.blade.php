{{-- resources/views/user/user-guide-v3.blade.php --}}
{{--
 * หน้าคู่มือการใช้งานสำหรับผู้ใช้งานทั่วไป (User)
 *
 * แสดงคู่มือการใช้งานระบบ TP-Affiliate สำหรับผู้ใช้งาน
 * รวมถึง FAQ, วิดีโอสอน, และช่องทางติดต่อ
 *
 * @author TP-Affiliate Development Team
--}}
@extends('layouts.user-arrow-x')

@section('title', 'คู่มือการใช้งาน')
@section('page-title', 'คู่มือการใช้งาน')

@section('content')
<div x-data="userGuideManager()" class="space-y-6">
    {{-- Hero Section --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl p-8 relative">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500"></div>
        </div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg">
                        <i class="fas fa-book-open mr-3"></i>
                        ยินดีต้อนรับสู่คู่มือการใช้งาน
                    </h1>
                    <p class="text-white/80 text-lg">
                        เรียนรู้การใช้งานระบบ TP-Affiliate และเริ่มสร้างรายได้
                    </p>
                </div>
                <div class="hidden md:block">
                    <div class="w-32 h-32 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-full flex items-center justify-center shadow-2xl animate-pulse">
                        <i class="fas fa-user-graduate text-6xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">30+</div>
                    <div class="text-white/70 text-sm">บทเรียน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">15+</div>
                    <div class="text-white/70 text-sm">วิดีโอสอน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">50+</div>
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
                                'border-b-2 border-blue-400 bg-white/10': currentTab === tab.id,
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
                    เริ่มต้นใช้งาน
                </h2>

                {{-- Step Cards --}}
                <div class="grid md:grid-cols-2 gap-6">
                    <template x-for="(step, index) in gettingStartedSteps" :key="index">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:border-white/40 transition cursor-pointer group">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:scale-110 transition">
                                    <span x-text="index + 1"></span>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-white mb-2" x-text="step.title"></h3>
                                    <p class="text-white/70 text-sm" x-text="step.description"></p>
                                    <button @click="openStepDetail(step)"
                                            class="mt-3 text-blue-400 hover:text-blue-300 text-sm font-medium inline-flex items-center gap-2">
                                        <span>เรียนรู้เพิ่มเติม</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Features Tab --}}
            <div x-show="currentTab === 'features'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-star mr-2"></i>
                    ฟีเจอร์หลักสำหรับคุณ
                </h2>

                <div class="grid md:grid-cols-3 gap-6">
                    <template x-for="feature in features" :key="feature.id">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:border-white/40 transition hover:shadow-2xl cursor-pointer group">
                            <div class="mb-4">
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br flex items-center justify-center text-3xl shadow-lg group-hover:scale-110 transition"
                                     :class="feature.gradient">
                                    <i :class="feature.icon" class="text-white drop-shadow"></i>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2" x-text="feature.title"></h3>
                            <p class="text-white/70 text-sm mb-4" x-text="feature.description"></p>
                            <button @click="openFeatureDetail(feature)"
                                    class="text-blue-400 hover:text-blue-300 text-sm font-medium inline-flex items-center gap-2">
                                <span>ดูรายละเอียด</span>
                                <i class="fas fa-chevron-right"></i>
                            </button>
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
                                    <i class="fas fa-question-circle text-blue-400"></i>
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

                {{-- Featured Video - Click to Play --}}
                <div class="mb-8" x-data="{ playing: false }">
                    <h3 class="text-lg font-semibold text-white/90 mb-4">
                        <i class="fas fa-star text-yellow-400 mr-2"></i>
                        วิดีโอแนะนำระบบ
                    </h3>
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-white/20 group cursor-pointer"
                         @click="playing = true">
                        {{-- Thumbnail --}}
                        <div class="aspect-video" x-show="!playing">
                            <img src="https://img.youtube.com/vi/-GsrFb2tO1I/maxresdefault.jpg"
                                 alt="วิดีโอแนะนำ TP-Affiliate"
                                 class="w-full h-full object-cover">

                            {{-- Overlay --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent flex flex-col items-center justify-center">
                                {{-- Play Button --}}
                                <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mb-4 group-hover:bg-white/30 group-hover:scale-110 transition-all duration-300 border border-white/30">
                                    <i class="fas fa-play text-4xl text-white ml-1"></i>
                                </div>
                                <h4 class="text-2xl font-bold text-white drop-shadow-lg mb-2">
                                    แนะนำระบบ TP-Affiliate
                                </h4>
                                <p class="text-white/80 text-sm">
                                    เริ่มต้นสร้างรายได้กับระบบ Affiliate
                                </p>
                            </div>
                        </div>

                        {{-- Video iframe (loads when clicked) --}}
                        <div class="aspect-video" x-show="playing" x-cloak>
                            <template x-if="playing">
                                <iframe
                                    src="https://www.youtube.com/embed/-GsrFb2tO1I?autoplay=1&rel=0"
                                    title="TP-Affiliate User Introduction"
                                    class="w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Other Videos Grid --}}
                <h3 class="text-lg font-semibold text-white/90 mb-4">
                    <i class="fas fa-list text-blue-400 mr-2"></i>
                    วิดีโอทั้งหมด
                </h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <template x-for="video in videos" :key="video.id">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl overflow-hidden border border-white/20 hover:border-white/40 transition hover:shadow-2xl cursor-pointer group"
                             x-data="{ videoPlaying: false }">
                            {{-- Thumbnail with Click to Play --}}
                            <div class="relative aspect-video" @click="videoPlaying = true">
                                <template x-if="!videoPlaying">
                                    <div class="w-full h-full">
                                        <img :src="'https://img.youtube.com/vi/' + video.youtubeId + '/mqdefault.jpg'"
                                             :alt="video.title"
                                             class="w-full h-full object-cover bg-gradient-to-br from-gray-800 to-gray-900">
                                        <div class="absolute inset-0 bg-black/30 flex items-center justify-center group-hover:bg-black/20 transition">
                                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center group-hover:bg-white/30 group-hover:scale-110 transition-all border border-white/30">
                                                <i class="fas fa-play text-2xl text-white ml-1"></i>
                                            </div>
                                        </div>
                                        <div class="absolute bottom-2 right-2 px-2 py-1 bg-black/70 rounded text-white text-xs" x-text="video.duration"></div>
                                    </div>
                                </template>
                                <template x-if="videoPlaying">
                                    <iframe
                                        :src="'https://www.youtube.com/embed/' + video.youtubeId + '?autoplay=1&rel=0'"
                                        :title="video.title"
                                        class="w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen>
                                    </iframe>
                                </template>
                            </div>
                            {{-- Info --}}
                            <div class="p-4">
                                <h3 class="font-bold text-white mb-2 line-clamp-2" x-text="video.title"></h3>
                                <p class="text-white/60 text-xs mb-3" x-text="video.description"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Wallet Tab --}}
            <div x-show="currentTab === 'wallet'" x-transition class="space-y-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-wallet mr-2"></i>
                    การใช้งานกระเป๋าเงิน
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    {{-- Wallet Features --}}
                    <template x-for="item in walletGuide" :key="item.id">
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
                                    <h3 class="font-bold text-white">LINE Official</h3>
                                    <p class="text-white/60 text-sm">@thaiprompt</p>
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
                                    <p class="text-white/60 text-sm">support@thaiprompt.com</p>
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
                                    <i class="fas fa-ticket-alt text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white">ระบบ Ticket</h3>
                                    <p class="text-white/60 text-sm">แจ้งปัญหาผ่านระบบ</p>
                                </div>
                            </div>
                            <a href="{{ route('user.tickets.index') }}" class="block w-full px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition font-medium text-center">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                สร้าง Ticket
                            </a>
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
                                    class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 transition font-bold shadow-lg">
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
 * User Guide Manager - Alpine.js Component สำหรับผู้ใช้งาน
 */
function userGuideManager() {
    return {
        currentTab: 'getting-started',
        searchQuery: '',

        tabs: [
            { id: 'getting-started', label: 'เริ่มต้นใช้งาน', icon: 'fas fa-rocket' },
            { id: 'features', label: 'ฟีเจอร์', icon: 'fas fa-star' },
            { id: 'wallet', label: 'กระเป๋าเงิน', icon: 'fas fa-wallet' },
            { id: 'faq', label: 'FAQ', icon: 'fas fa-question-circle' },
            { id: 'videos', label: 'วิดีโอ', icon: 'fas fa-video' },
            { id: 'support', label: 'ติดต่อเรา', icon: 'fas fa-headset' }
        ],

        gettingStartedSteps: [
            {
                title: '1. สมัครสมาชิกและยืนยันตัวตน',
                description: 'สร้างบัญชีใหม่และยืนยันอีเมลเพื่อเริ่มต้นใช้งาน'
            },
            {
                title: '2. ตั้งค่าโปรไฟล์ของคุณ',
                description: 'กรอกข้อมูลส่วนตัวและเพิ่มรูปโปรไฟล์'
            },
            {
                title: '3. เพิ่มช่องทางรับเงิน',
                description: 'เพิ่มบัญชีธนาคารสำหรับรับเงินค่าคอมมิชชั่น'
            },
            {
                title: '4. สร้างลิงก์ Affiliate',
                description: 'สร้างลิงก์แนะนำพิเศษสำหรับแชร์'
            },
            {
                title: '5. แชร์และสร้างรายได้',
                description: 'แชร์ลิงก์ผ่านช่องทางต่างๆ เพื่อรับค่าคอมมิชชั่น'
            },
            {
                title: '6. ติดตามผลและถอนเงิน',
                description: 'ดูสถิติรายได้และถอนเงินเข้าบัญชี'
            }
        ],

        features: [
            {
                id: 1,
                title: 'Dashboard ส่วนตัว',
                description: 'ติดตามยอดรายได้และสถิติการทำงานแบบเรียลไทม์',
                icon: 'fas fa-chart-line',
                gradient: 'from-blue-500 to-cyan-600'
            },
            {
                id: 2,
                title: 'ระบบ Affiliate',
                description: 'สร้างลิงก์แนะนำและรับค่าคอมมิชชั่นหลายระดับ',
                icon: 'fas fa-network-wired',
                gradient: 'from-green-500 to-emerald-600'
            },
            {
                id: 3,
                title: 'กระเป๋าเงินอิเล็กทรอนิกส์',
                description: 'ฝาก ถอน โอนเงินได้ง่ายและปลอดภัย',
                icon: 'fas fa-wallet',
                gradient: 'from-yellow-500 to-orange-600'
            },
            {
                id: 4,
                title: 'ระบบยศและโบนัส',
                description: 'อัพเกรดยศเพื่อรับสิทธิพิเศษและโบนัสเพิ่ม',
                icon: 'fas fa-trophy',
                gradient: 'from-purple-500 to-pink-600'
            },
            {
                id: 5,
                title: 'Crypto Wallet',
                description: 'รองรับการทำธุรกรรมด้วย TPIX Token',
                icon: 'fas fa-coins',
                gradient: 'from-indigo-500 to-blue-600'
            },
            {
                id: 6,
                title: 'ระบบ Support',
                description: 'ติดต่อทีมงานได้ตลอด 24/7 ทุกช่องทาง',
                icon: 'fas fa-headset',
                gradient: 'from-red-500 to-pink-600'
            }
        ],

        walletGuide: [
            {
                id: 1,
                title: 'การเติมเงิน',
                description: 'เติมเงินผ่าน PromptPay, โอนเงินธนาคาร หรือ Crypto',
                icon: 'fas fa-plus-circle',
                gradient: 'from-green-500 to-emerald-600'
            },
            {
                id: 2,
                title: 'การถอนเงิน',
                description: 'ถอนเงินเข้าบัญชีธนาคาร ขั้นต่ำ 100 บาท',
                icon: 'fas fa-minus-circle',
                gradient: 'from-orange-500 to-red-600'
            },
            {
                id: 3,
                title: 'การโอนเงิน',
                description: 'โอนเงินให้สมาชิกอื่นในระบบได้ทันที',
                icon: 'fas fa-exchange-alt',
                gradient: 'from-blue-500 to-indigo-600'
            },
            {
                id: 4,
                title: 'ประวัติธุรกรรม',
                description: 'ดูประวัติการทำธุรกรรมทั้งหมดย้อนหลัง',
                icon: 'fas fa-history',
                gradient: 'from-purple-500 to-pink-600'
            }
        ],

        faqs: [
            {
                question: 'จะเริ่มต้นสร้างรายได้ได้อย่างไร?',
                answer: 'เริ่มจากการสมัครสมาชิก แล้วสร้างลิงก์ Affiliate ของคุณเพื่อแชร์ให้ผู้อื่น เมื่อมีคนซื้อสินค้าผ่านลิงก์ของคุณ คุณจะได้รับค่าคอมมิชชั่น',
                open: false
            },
            {
                question: 'ได้รับค่าคอมมิชชั่นกี่เปอร์เซ็นต์?',
                answer: 'อัตราค่าคอมมิชชั่นขึ้นอยู่กับยศของคุณ เริ่มต้นที่ 5% และสามารถเพิ่มได้ถึง 20% เมื่ออัพยศสูงขึ้น',
                open: false
            },
            {
                question: 'ถอนเงินขั้นต่ำเท่าไหร่?',
                answer: 'ถอนเงินขั้นต่ำ 100 บาท โดยจะโอนเข้าบัญชีธนาคารภายใน 24 ชั่วโมง',
                open: false
            },
            {
                question: 'วิธีอัพเกรดยศทำอย่างไร?',
                answer: 'ยศจะอัพเกรดอัตโนมัติเมื่อคุณทำยอดขายและมีทีมงานตามเงื่อนไขที่กำหนด สามารถดูรายละเอียดได้ที่หน้า Rank',
                open: false
            },
            {
                question: 'ลืมรหัสผ่านต้องทำอย่างไร?',
                answer: 'กดลิงก์ "ลืมรหัสผ่าน" ที่หน้าเข้าสู่ระบบ แล้วกรอกอีเมลที่ลงทะเบียนไว้ ระบบจะส่งลิงก์รีเซ็ตรหัสผ่านให้ทางอีเมล',
                open: false
            }
        ],

        videos: [
            {
                id: 1,
                title: 'วิธีสมัครสมาชิกและเริ่มต้นใช้งาน',
                description: 'สอนการสมัครและตั้งค่าบัญชีครั้งแรก',
                duration: '05:30',
                youtubeId: '-GsrFb2tO1I'
            },
            {
                id: 2,
                title: 'วิธีสร้างลิงก์ Affiliate',
                description: 'สร้างลิงก์แนะนำและวิธีแชร์ที่มีประสิทธิภาพ',
                duration: '06:45',
                youtubeId: '-GsrFb2tO1I'
            },
            {
                id: 3,
                title: 'การใช้งานกระเป๋าเงิน',
                description: 'วิธีเติมเงิน ถอนเงิน และดูประวัติธุรกรรม',
                duration: '08:20',
                youtubeId: '-GsrFb2tO1I'
            },
            {
                id: 4,
                title: 'ทำความเข้าใจระบบยศ',
                description: 'เงื่อนไขการอัพยศและสิทธิพิเศษที่ได้รับ',
                duration: '07:15',
                youtubeId: '-GsrFb2tO1I'
            },
            {
                id: 5,
                title: 'เทคนิคการสร้างรายได้',
                description: 'วิธีเพิ่มรายได้จากระบบ Affiliate',
                duration: '10:50',
                youtubeId: '-GsrFb2tO1I'
            },
            {
                id: 6,
                title: 'การใช้งาน Dashboard',
                description: 'อ่านและวิเคราะห์ข้อมูลใน Dashboard',
                duration: '05:40',
                youtubeId: '-GsrFb2tO1I'
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

        openFeatureDetail(feature) {
            alert('เปิดรายละเอียดฟีเจอร์: ' + feature.title);
        },

        playVideo(video) {
            alert('เล่นวิดีโอ: ' + video.title);
        },

        async submitContactForm() {
            try {
                alert('✅ ส่งคำถามสำเร็จ!\n\nเราจะติดต่อกลับภายใน 24 ชั่วโมง');
                this.contactForm = { subject: '', message: '' };
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            }
        }
    }
}
</script>
@endsection
