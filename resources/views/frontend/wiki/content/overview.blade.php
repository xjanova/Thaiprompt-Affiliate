{{--
    📋 Overview & Feature Summary - Arrow X V3
    Tailwind CSS + Alpine.js (No inline styles, No custom CSS)
--}}

<div class="space-y-12">
    {{-- Hero Section --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 p-8 md:p-12 text-center text-white">
        {{-- Animated Background --}}
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-10 left-10 w-72 h-72 bg-white rounded-full filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-blue-300 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="relative z-10">
            <h1 class="text-3xl md:text-5xl font-black mb-4">
                🚀 Thaiprompt Affiliate Marketing Platform
            </h1>
            <p class="text-lg md:text-xl text-white/90 mb-6 max-w-3xl mx-auto">
                แพลตฟอร์มธุรกิจออนไลน์ครบวงจร ที่รวมทุกฟีเจอร์ที่คุณต้องการไว้ในที่เดียว
            </p>

            <div class="flex flex-wrap gap-3 justify-center">
                <span class="px-4 py-1.5 bg-green-500/30 border border-green-400/50 rounded-full text-sm font-semibold backdrop-blur-sm">
                    {{ $stats['version'] ?? '3.0' }}
                </span>
                <span class="px-4 py-1.5 bg-blue-500/30 border border-blue-400/50 rounded-full text-sm font-semibold backdrop-blur-sm">
                    Updated {{ $stats['last_updated'] ?? date('Y-m-d') }}
                </span>
                <span class="px-4 py-1.5 bg-purple-500/30 border border-purple-400/50 rounded-full text-sm font-semibold backdrop-blur-sm">
                    Production Ready
                </span>
            </div>
        </div>
    </div>

    {{-- Statistics Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-6 text-center hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-xl hover:shadow-blue-500/20 transform hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-3">📊</div>
            <div class="text-3xl md:text-4xl font-black bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                {{ $stats['database_models'] ?? '339' }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 font-medium mt-1">Database Models</div>
        </div>

        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-6 text-center hover:border-purple-500 dark:hover:border-purple-400 hover:shadow-xl hover:shadow-purple-500/20 transform hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-3">⚙️</div>
            <div class="text-3xl md:text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                {{ $stats['http_controllers'] ?? '41' }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 font-medium mt-1">Controllers</div>
        </div>

        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-6 text-center hover:border-pink-500 dark:hover:border-pink-400 hover:shadow-xl hover:shadow-pink-500/20 transform hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-3">🔧</div>
            <div class="text-3xl md:text-4xl font-black bg-gradient-to-r from-pink-600 to-red-600 bg-clip-text text-transparent">
                {{ $stats['services_count'] ?? '30' }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 font-medium mt-1">Business Services</div>
        </div>

        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-6 text-center hover:border-green-500 dark:hover:border-green-400 hover:shadow-xl hover:shadow-green-500/20 transform hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-3">💾</div>
            <div class="text-3xl md:text-4xl font-black bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                {{ $stats['database_tables'] ?? '100' }}+
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 font-medium mt-1">Database Tables</div>
        </div>
    </div>

    {{-- Problems & Opportunities Section --}}
    <section class="space-y-6">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center text-xl">⚠️</span>
            ปัญหาและโอกาส: ทำไมต้องมีแพลตฟอร์มนี้
        </h2>

        {{-- Warning Box --}}
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-l-4 border-amber-500 rounded-r-xl p-6">
            <h4 class="text-lg font-bold text-amber-900 dark:text-amber-200 mb-4">ปัญหาหลักที่คนไทยเผชิญ</h4>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="text-gray-700 dark:text-gray-300">
                    <strong class="text-amber-700 dark:text-amber-400">1. รายได้ไม่เพียงพอ:</strong> เงินเดือนค่าครองชีพสูง แต่รายได้ไม่เพิ่มตาม
                </div>
                <div class="text-gray-700 dark:text-gray-300">
                    <strong class="text-amber-700 dark:text-amber-400">2. ขาดโอกาสทางธุรกิจ:</strong> ทุนน้อย ไม่มีทักษะ ไม่กล้าเริ่มต้น
                </div>
                <div class="text-gray-700 dark:text-gray-300">
                    <strong class="text-amber-700 dark:text-amber-400">3. ถูกหลอกในธุรกิจ MLM:</strong> ระบบไม่โปร่งใส มีการโกง
                </div>
                <div class="text-gray-700 dark:text-gray-300">
                    <strong class="text-amber-700 dark:text-amber-400">4. ไม่มีเครื่องมือที่เหมาะสม:</strong> ระบบ MLM เก่าๆ ไม่มี AI ไม่มีอัตโนมัติ
                </div>
            </div>
        </div>

        {{-- Crisis Stats --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 text-center">
                <div class="text-3xl font-black text-red-600 dark:text-red-400">65%</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">ของคนไทยมีรายได้ไม่เพียงพอ</div>
            </div>
            <div class="bg-white dark:bg-gray-800 border-2 border-amber-200 dark:border-amber-800 rounded-xl p-4 text-center">
                <div class="text-3xl font-black text-amber-600 dark:text-amber-400">3M+</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">คนทำงาน Freelance/Part-time</div>
            </div>
            <div class="bg-white dark:bg-gray-800 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 text-center">
                <div class="text-3xl font-black text-red-600 dark:text-red-400">40%</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">ของผู้ทำ MLM ขาดทุน</div>
            </div>
        </div>

        {{-- Success Box --}}
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-r-xl p-6">
            <h4 class="text-lg font-bold text-green-900 dark:text-green-200 mb-4">💡 โซลูชันของเรา - แก้ปัญหาได้จริง</h4>
            <ol class="space-y-2 text-gray-700 dark:text-gray-300">
                <li><strong class="text-green-700 dark:text-green-400">สร้างรายได้เสริมที่แท้จริง:</strong> ระบบ MLM ที่ยุติธรรม ไม่มีการโกง</li>
                <li><strong class="text-green-700 dark:text-green-400">ใช้งานง่าย:</strong> ไม่ต้องเป็นมือโปร ก็ใช้ได้</li>
                <li><strong class="text-green-700 dark:text-green-400">AI ช่วยทำงาน:</strong> ลดภาระงาน เพิ่มประสิทธิภาพ</li>
                <li><strong class="text-green-700 dark:text-green-400">เข้าถึงได้ทุกที่:</strong> มีแค่มือถือก็ทำธุรกิจได้</li>
                <li><strong class="text-green-700 dark:text-green-400">ต้นทุนต่ำ:</strong> ไม่ต้องลงทุนเยอะ เริ่มต้นได้ที่ 0 บาท</li>
            </ol>
        </div>
    </section>

    {{-- Feature Summary Section --}}
    <section class="space-y-8">
        <h2 id="feature-summary" class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-xl">✨</span>
            สรุปฟีเจอร์ทั้งหมด - ทำให้คุณทึ่ง!
        </h2>

        {{-- Info Box --}}
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-l-4 border-blue-500 rounded-r-xl p-6">
            <h4 class="text-lg font-bold text-blue-900 dark:text-blue-200 mb-2">🎯 ภาพรวมของระบบ</h4>
            <p class="text-gray-700 dark:text-gray-300">
                <strong>Thaiprompt Affiliate Marketing Platform</strong> คือระบบธุรกิจออนไลน์ที่ครบครันที่สุด
                ออกแบบมาเพื่อรองรับทุกรูปแบบธุรกิจ ตั้งแต่ระบบขายตรง, อีคอมเมิร์ซ, AI Chatbot,
                การจัดการโรงแรม, ระบบบัญชี, HRM, Trading Bot, และอื่นๆ อีกมากมาย!
            </p>
        </div>

        {{-- Feature Category 1: MLM & Affiliate --}}
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 hover:shadow-lg transition-shadow">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                💎 1. ระบบ MLM & Affiliate Marketing
            </h3>

            <div class="grid md:grid-cols-3 gap-4">
                {{-- Card 1 --}}
                <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-purple-500 dark:hover:border-purple-400 hover:shadow-lg hover:shadow-purple-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🌳
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">MLM Binary & Unilevel</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">รองรับทั้งระบบ Binary และ Unilevel พร้อมระบบจัดการโครงสร้างองค์กรแบบ Real-time</p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Binary Placement อัตโนมัติ</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Unilevel Genealogy Tree</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Commission Calculator แบบ Multi-level</li>
                    </ul>
                </div>

                {{-- Card 2 --}}
                <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-green-500 dark:hover:border-green-400 hover:shadow-lg hover:shadow-green-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        💰
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ระบบคอมมิชชั่นอัจฉริยะ</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">คำนวณค่าคอมมิชชั่นแบบอัตโนมัติ รองรับหลายระดับและหลายประเภท</p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Direct Commission (ค่าแนะนำตรง)</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Matching Bonus (โบนัสจับคู่)</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Auto Payout to Wallet</li>
                    </ul>
                </div>

                {{-- Card 3 --}}
                <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-lg hover:shadow-blue-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        📊
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Genealogy & Analytics</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">ติดตามโครงสร้างองค์กรและประสิทธิภาพของทีม</p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Interactive Tree Visualization</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Team Performance Dashboard</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Sales Volume Reports</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Feature Category 2: AI & Bot --}}
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 hover:shadow-lg transition-shadow">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                🤖 2. AI & Bot Ecosystem
            </h3>

            <div class="grid md:grid-cols-3 gap-4">
                {{-- AI Chatbot --}}
                <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-violet-500 dark:hover:border-violet-400 hover:shadow-lg hover:shadow-violet-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🧠
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">AI Chatbot Multi-Provider</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">รองรับ AI หลายค่าย พร้อมระบบ RAG</p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> OpenAI GPT-4, Claude, Gemini</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Knowledge Base Management</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Vector Embeddings</li>
                    </ul>
                </div>

                {{-- AI Image --}}
                <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-pink-500 dark:hover:border-pink-400 hover:shadow-lg hover:shadow-pink-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        🎨
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">AI Image & Video Generation</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">สร้างภาพและวิดีโอด้วย AI เพื่อการตลาด</p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> DALL-E, Stable Diffusion</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> AI Video Creator</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Marketing Content Generator</li>
                    </ul>
                </div>

                {{-- LINE Bot --}}
                <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-green-500 dark:hover:border-green-400 hover:shadow-lg hover:shadow-green-500/10 transform hover:-translate-y-1 transition-all duration-300 cursor-pointer group">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        💬
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">LINE Official Account</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">ผสานรวมกับ LINE OA ได้อย่างสมบูรณ์</p>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Rich Menu Builder</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> Flex Message Templates</li>
                        <li class="flex items-center gap-2"><span class="text-green-500">✅</span> LINE Login Integration</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- More Features Tip --}}
        <div class="bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 border-l-4 border-cyan-500 rounded-r-xl p-6">
            <h4 class="text-lg font-bold text-cyan-900 dark:text-cyan-200 mb-2">📚 มีฟีเจอร์อีกมากมาย!</h4>
            <p class="text-gray-700 dark:text-gray-300">
                นี่เป็นเพียงส่วนหนึ่งของฟีเจอร์ทั้งหมด ระบบยังมีฟีเจอร์อื่นๆ อีกมากมาย เช่น
                <strong class="text-cyan-700 dark:text-cyan-400">Crypto & Trading</strong>,
                <strong class="text-cyan-700 dark:text-cyan-400">Payment & Wallet</strong>,
                <strong class="text-cyan-700 dark:text-cyan-400">HRM System</strong>,
                <strong class="text-cyan-700 dark:text-cyan-400">Academy & Learning</strong>,
                <strong class="text-cyan-700 dark:text-cyan-400">POS System</strong>,
                <strong class="text-cyan-700 dark:text-cyan-400">Hotel Booking</strong>
                และอื่นๆ อีกเพียบ! <strong>เลือกหมวดจากเมนูด้านซ้าย</strong> เพื่ออ่านรายละเอียดเพิ่มเติม
            </p>
        </div>
    </section>

    {{-- Why Choose Us Section --}}
    <section class="space-y-6">
        <h2 id="why-choose-us" class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="w-10 h-10 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-xl flex items-center justify-center text-xl">🏆</span>
            ทำไมต้องเลือกระบบของเรา?
        </h2>

        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-blue-500 dark:hover:border-blue-400 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center text-xl mb-3">⚡</div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ครบจบในที่เดียว</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">ไม่ต้องใช้ระบบหลายตัว ประหยัดต้นทุนและเวลาในการจัดการ</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-purple-500 dark:hover:border-purple-400 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center text-xl mb-3">🎨</div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ออกแบบสวยงาม</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">UI/UX ทันสมัย รองรับ Dark Mode, Responsive Design</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-red-500 dark:hover:border-red-400 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center text-xl mb-3">🔒</div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ปลอดภัยสูงสุด</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">มีระบบรักษาความปลอดภัยครบครัน 2FA, KYC, IP Blocking</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-green-500 dark:hover:border-green-400 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-xl mb-3">🚀</div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Performance สูง</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">ออกแบบมาเพื่อรองรับผู้ใช้งานจำนวนมาก พร้อม Caching</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-orange-500 dark:hover:border-orange-400 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center text-xl mb-3">📱</div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">รองรับทุกอุปกรณ์</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">ใช้งานได้ทั้ง Desktop, Tablet, และ Mobile อย่างลื่นไหล</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-indigo-500 dark:hover:border-indigo-400 transition-all">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center text-xl mb-3">🔧</div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Customizable</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">ปรับแต่งได้ตามต้องการ มีระบบ Theme Settings ครบครัน</p>
            </div>
        </div>

        {{-- Made in Thailand Box --}}
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-r-xl p-6">
            <h4 class="text-lg font-bold text-green-900 dark:text-green-200 mb-3">🇹🇭 Made in Thailand 100%</h4>
            <div class="grid md:grid-cols-2 gap-3 text-gray-700 dark:text-gray-300">
                <div><strong class="text-green-700 dark:text-green-400">พัฒนาโดยทีมไทย 100%</strong> - ไม่ใช่ Copy มาจากต่างประเทศ</div>
                <div><strong class="text-green-700 dark:text-green-400">เงินทุนหมุนเวียนในไทย</strong> - ไม่รั่วไหลไปต่างประเทศ</div>
                <div><strong class="text-green-700 dark:text-green-400">Support เป็นภาษาไทย 24/7</strong> - เข้าใจบริบทธุรกิจไทย</div>
                <div><strong class="text-green-700 dark:text-green-400">ราคาที่เข้าถึงได้</strong> - ไม่ต้องจ่าย License ต่างประเทศ</div>
            </div>
        </div>
    </section>

    {{-- Technology Stack Section --}}
    <section class="space-y-6">
        <h2 id="tech-stack" class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="w-10 h-10 bg-gradient-to-br from-slate-500 to-gray-600 rounded-xl flex items-center justify-center text-xl">🔧</span>
            เทคโนโลยีที่ใช้
        </h2>

        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-lg transition-all">
                <h4 class="text-base font-bold text-blue-600 dark:text-blue-400 mb-3">Backend</h4>
                <ul class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                    <li>Laravel 11.x (PHP 8.3+)</li>
                    <li>MySQL/MariaDB</li>
                    <li>Redis Cache</li>
                    <li>Queue Jobs</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-purple-500 dark:hover:border-purple-400 hover:shadow-lg transition-all">
                <h4 class="text-base font-bold text-purple-600 dark:text-purple-400 mb-3">Frontend</h4>
                <ul class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                    <li>Blade Templates</li>
                    <li>TailwindCSS</li>
                    <li>Alpine.js</li>
                    <li>JavaScript ES6+</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-pink-500 dark:hover:border-pink-400 hover:shadow-lg transition-all">
                <h4 class="text-base font-bold text-pink-600 dark:text-pink-400 mb-3">AI & Services</h4>
                <ul class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                    <li>OpenAI GPT-4</li>
                    <li>Anthropic Claude</li>
                    <li>Google Gemini</li>
                    <li>Local LLM Support</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:border-green-500 dark:hover:border-green-400 hover:shadow-lg transition-all">
                <h4 class="text-base font-bold text-green-600 dark:text-green-400 mb-3">Integration</h4>
                <ul class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                    <li>LINE Messaging API</li>
                    <li>Marketplace APIs</li>
                    <li>Crypto Exchange APIs</li>
                    <li>Payment Gateways</li>
                </ul>
            </div>
        </div>
    </section>
</div>
