{{-- resources/views/admin/user-guide-v3.blade.php --}}
@extends('layouts.admin-v3')

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
                        เรียนรู้การใช้งานระบบ TP-Affiliate แบบครบวงจร
                    </p>
                </div>
                <div class="hidden md:block">
                    <div class="w-32 h-32 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-full flex items-center justify-center shadow-2xl animate-pulse">
                        <i class="fas fa-graduation-cap text-6xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">50+</div>
                    <div class="text-white/70 text-sm">บทเรียน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">20+</div>
                    <div class="text-white/70 text-sm">วิดีโอสอน</div>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                    <div class="text-3xl font-bold text-white mb-1">100+</div>
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
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg group-hover:scale-110 transition">
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
                    ฟีเจอร์หลักของระบบ
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
                                        class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition font-medium text-sm">
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
                                    <i class="fas fa-comments text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white">Live Chat</h3>
                                    <p class="text-white/60 text-sm">24/7 ทุกวัน</p>
                                </div>
                            </div>
                            <button class="w-full px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition font-medium">
                                <i class="fas fa-comment-dots mr-2"></i>
                                เริ่มแชท
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
                                    class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition font-bold shadow-lg">
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
 * User Guide Manager - Alpine.js Component
 */
function userGuideManager() {
    return {
        currentTab: 'getting-started',
        searchQuery: '',

        tabs: [
            { id: 'getting-started', label: 'เริ่มต้นใช้งาน', icon: 'fas fa-rocket' },
            { id: 'features', label: 'ฟีเจอร์', icon: 'fas fa-star' },
            { id: 'faq', label: 'FAQ', icon: 'fas fa-question-circle' },
            { id: 'videos', label: 'วิดีโอ', icon: 'fas fa-video' },
            { id: 'support', label: 'ติดต่อเรา', icon: 'fas fa-headset' }
        ],

        gettingStartedSteps: [
            {
                title: '1. สร้างบัญชีผู้ใช้',
                description: 'เริ่มต้นด้วยการสมัครสมาชิกและยืนยันอีเมล'
            },
            {
                title: '2. ตั้งค่าโปรไฟล์',
                description: 'กรอกข้อมูลส่วนตัวและข้อมูลธนาคารสำหรับรับเงิน'
            },
            {
                title: '3. เลือกแผนการใช้งาน',
                description: 'เลือกแพ็คเกจที่เหมาะสมกับความต้องการของคุณ'
            },
            {
                title: '4. สร้างลิงก์ Affiliate',
                description: 'สร้างลิงก์พิเศษสำหรับแชร์และรับค่าคอมมิชชั่น'
            },
            {
                title: '5. เริ่มแชร์และสร้างรายได้',
                description: 'แชร์ลิงก์ผ่านช่องทางต่างๆ และเริ่มรับค่าคอมมิชชั่น'
            },
            {
                title: '6. ติดตามผลและถอนเงิน',
                description: 'ดูสถิติการทำงานและถอนเงินเข้าบัญชี'
            }
        ],

        features: [
            {
                id: 1,
                title: 'ระบบ Affiliate หลายระดับ',
                description: 'รับค่าคอมมิชชั่นถึง 10 ชั้นสายงาน',
                icon: 'fas fa-network-wired',
                gradient: 'from-blue-500 to-cyan-600'
            },
            {
                id: 2,
                title: 'Dashboard แบบ Real-time',
                description: 'ติดตามยอดขายและรายได้แบบเรียลไทม์',
                icon: 'fas fa-chart-line',
                gradient: 'from-green-500 to-emerald-600'
            },
            {
                id: 3,
                title: 'ระบบ Rank & Bonus',
                description: 'ระบบยศและโบนัสพิเศษตามผลงาน',
                icon: 'fas fa-trophy',
                gradient: 'from-yellow-500 to-orange-600'
            },
            {
                id: 4,
                title: 'E-Wallet ในตัว',
                description: 'กระเป๋าเงินอิเล็กทรอนิกส์พร้อมถอนอัตโนมัติ',
                icon: 'fas fa-wallet',
                gradient: 'from-purple-500 to-pink-600'
            },
            {
                id: 5,
                title: 'AI Chatbot Integration',
                description: 'เชื่อมต่อ LINE Bot AI ช่วยขายอัตโนมัติ',
                icon: 'fas fa-robot',
                gradient: 'from-indigo-500 to-blue-600'
            },
            {
                id: 6,
                title: 'Marketing Tools',
                description: 'เครื่องมือการตลาดครบครัน Banner, QR Code',
                icon: 'fas fa-bullhorn',
                gradient: 'from-red-500 to-pink-600'
            }
        ],

        faqs: [
            {
                question: 'ระบบ Affiliate คืออะไร?',
                answer: 'ระบบ Affiliate คือการหารายได้จากการแนะนำผู้อื่นมาใช้บริการ โดยคุณจะได้รับค่าคอมมิชชั่นจากยอดขายของคนที่คุณแนะนำ',
                open: false
            },
            {
                question: 'ต้องลงทุนเท่าไหร่ในการเริ่มต้น?',
                answer: 'สามารถเริ่มต้นได้ฟรี ไม่มีค่าใช้จ่ายในการสมัครสมาชิก แต่หากต้องการฟีเจอร์เพิ่มเติม สามารถอัพเกรดแพ็คเกจได้',
                open: false
            },
            {
                question: 'ได้รับค่าคอมมิชชั่นกี่ชั้น?',
                answer: 'ระบบรองรับการรับค่าคอมมิชชั่นได้ถึง 10 ชั้นสายงาน ขึ้นอยู่กับแพ็คเกจและยศของคุณ',
                open: false
            },
            {
                question: 'ถอนเงินขั้นต่ำเท่าไหร่?',
                answer: 'ถอนเงินขั้นต่ำ 100 บาท โดยจะโอนเข้าบัญชีธนาคารภายใน 24 ชั่วโมง',
                open: false
            },
            {
                question: 'มีค่าธรรมเนียมการถอนเงินหรือไม่?',
                answer: 'ไม่มีค่าธรรมเนียมการถอนเงิน ระบบจะโอนเต็มจำนวนที่คุณถอน',
                open: false
            }
        ],

        videos: [
            {
                id: 1,
                title: 'สอนสมัครสมาชิกและเริ่มต้นใช้งาน',
                description: 'วิธีการสมัครและตั้งค่าบัญชีครั้งแรก',
                duration: '05:30'
            },
            {
                id: 2,
                title: 'วิธีสร้างลิงก์ Affiliate และแชร์',
                description: 'สร้างลิงก์พิเศษและวิธีการแชร์ที่มีประสิทธิภาพ',
                duration: '08:45'
            },
            {
                id: 3,
                title: 'เทคนิคการสร้างรายได้ที่ดี',
                description: 'เคล็ดลับการทำ Affiliate Marketing ให้ประสบความสำเร็จ',
                duration: '12:20'
            },
            {
                id: 4,
                title: 'การใช้งาน Dashboard และรายงาน',
                description: 'วิธีอ่านและใช้งานระบบรายงานต่างๆ',
                duration: '06:15'
            },
            {
                id: 5,
                title: 'วิธีถอนเงินและตรวจสอบยอด',
                description: 'ขั้นตอนการถอนเงินและการตรวจสอบรายได้',
                duration: '04:50'
            },
            {
                id: 6,
                title: 'ระบบ Rank และการอัพเกรดยศ',
                description: 'เข้าใจระบบยศและเงื่อนไขการอัพเกรด',
                duration: '07:30'
            }
        ],

        contactForm: {
            subject: '',
            message: ''
        },

        filterContent() {
            // TODO: Implement search filtering
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
                // TODO: Implement API call
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
