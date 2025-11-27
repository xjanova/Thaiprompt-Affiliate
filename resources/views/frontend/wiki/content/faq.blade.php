{{-- Hero Section with RGB Gradient --}}
<div class="bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 p-8 md:p-16 rounded-2xl mb-12 shadow-2xl">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-white text-3xl md:text-4xl font-extrabold mb-4 drop-shadow-lg">
            ❓ FAQ & Troubleshooting
        </h1>
        <p class="text-white/95 text-lg md:text-xl font-medium mb-6">
            คำถามที่พบบ่อยและวิธีแก้ไขปัญหา - คำตอบสำหรับทุกข้อสงสัย
        </p>
        <div class="flex gap-4 justify-center flex-wrap">
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm text-white">
                💡 Quick Answers
            </span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm text-white">
                🔧 Troubleshooting
            </span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm text-white">
                📞 Support
            </span>
        </div>
    </div>
</div>

{{-- General FAQs --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        📋 คำถามทั่วไป (General FAQs)
    </h2>

    <div class="flex flex-col gap-4">
        {{-- FAQ Item 1 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    🤔 ThaiPrompt Affiliate คืออะไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    ThaiPrompt Affiliate เป็นแพลตฟอร์ม All-in-One สำหรับธุรกิจ MLM, E-commerce, AI Bot และอื่นๆ อีกมากมาย
                    พัฒนาด้วย Laravel 11 และ Modern Web Technologies ออกแบบมาเพื่อรองรับธุรกิจในประเทศไทยและอาเซียน
                    โดยมีฟีเจอร์มากกว่า 27 หมวดหมู่ ครอบคลุมทุกความต้องการทางธุรกิจ
                </p>
            </div>
        </div>

        {{-- FAQ Item 2 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    💰 ต้องใช้เงินลงทุนเท่าไหร่ในการเริ่มต้น?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    มีหลายรูปแบบการใช้งานตามความต้องการ:
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li><strong>Free Tier:</strong> สมัครสมาชิกและเริ่มต้น MLM ได้ฟรี</li>
                    <li><strong>Standard License:</strong> สำหรับ SME ที่ต้องการใช้งานเต็มรูปแบบ</li>
                    <li><strong>Business License:</strong> สำหรับองค์กรขนาดกลาง</li>
                    <li><strong>Enterprise License:</strong> สำหรับองค์กรขนาดใหญ่พร้อม Source Code</li>
                </ul>
            </div>
        </div>

        {{-- FAQ Item 3 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    🌐 รองรับภาษาอะไรบ้าง?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    ระบบรองรับหลายภาษาหลัก:
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li>🇹🇭 <strong>ไทย</strong> - ภาษาหลัก (Default)</li>
                    <li>🇺🇸 <strong>English</strong> - รองรับเต็มรูปแบบ</li>
                    <li>🇨🇳 <strong>中文 (简体)</strong> - รองรับด้วย Google Translate</li>
                    <li>🇯🇵 <strong>日本語</strong> - รองรับด้วย Google Translate</li>
                </ul>
            </div>
        </div>

        {{-- FAQ Item 4 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    📱 รองรับมือถือหรือไม่?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    ใช่! ระบบออกแบบด้วย Mobile-First Responsive Design รองรับทุกอุปกรณ์:
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li>📱 <strong>Smartphone</strong> - iOS และ Android</li>
                    <li>📲 <strong>Tablet</strong> - iPad และ Android Tablet</li>
                    <li>💻 <strong>Desktop</strong> - Windows, Mac, Linux</li>
                    <li>📺 <strong>Large Screen</strong> - รองรับหน้าจอขนาดใหญ่</li>
                </ul>
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    นอกจากนี้ยังมี .NET MAUI Mobile App สำหรับผู้ที่ต้องการ Native App
                </p>
            </div>
        </div>
    </div>
</section>

{{-- MLM FAQs --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        💎 คำถามเกี่ยวกับ MLM & Affiliate
    </h2>

    <div class="flex flex-col gap-4">
        {{-- MLM FAQ 1 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    🌳 ระบบ MLM รองรับแผนอะไรบ้าง?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    ระบบรองรับหลายแผน MLM ที่นิยม:
                </p>
                <div class="wiki-grid wiki-grid-3 mt-4">
                    <div class="bg-primary-50 dark:bg-primary-900/30 p-4 rounded-lg">
                        <h5 class="font-bold mb-2 text-gray-900 dark:text-white">🌳 Binary Plan</h5>
                        <p class="text-sm text-gray-600 dark:text-gray-300">แผน 2 ขา สร้างทีมซ้ายและขวา</p>
                    </div>
                    <div class="bg-secondary-50 dark:bg-secondary-900/30 p-4 rounded-lg">
                        <h5 class="font-bold mb-2 text-gray-900 dark:text-white">🎯 Unilevel Plan</h5>
                        <p class="text-sm text-gray-600 dark:text-gray-300">แผนหลายขา ไม่จำกัด Downline</p>
                    </div>
                    <div class="bg-accent-50 dark:bg-accent-900/30 p-4 rounded-lg">
                        <h5 class="font-bold mb-2 text-gray-900 dark:text-white">📊 Matrix Plan</h5>
                        <p class="text-sm text-gray-600 dark:text-gray-300">แผน Fixed Matrix เช่น 3x3, 4x7</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- MLM FAQ 2 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    💰 ค่าคอมมิชชั่นคำนวณอย่างไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    ค่าคอมมิชชั่นมีหลายประเภท:
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li><strong>Direct Commission:</strong> ค่าแนะนำตรง 5-15%</li>
                    <li><strong>Matching Bonus:</strong> โบนัสจับคู่ทีมซ้าย-ขวา</li>
                    <li><strong>Leadership Bonus:</strong> โบนัสผู้นำตามระดับ Rank</li>
                    <li><strong>Global Bonus:</strong> โบนัสจากยอดขายทั้งระบบ</li>
                    <li><strong>Rank Achievement:</strong> โบนัสเมื่อขึ้นระดับ Rank ใหม่</li>
                </ul>
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    อัตราค่าคอมมิชชั่นสามารถปรับแต่งได้ตามนโยบายบริษัท
                </p>
            </div>
        </div>

        {{-- MLM FAQ 3 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    📊 จะดู Genealogy Tree ได้ที่ไหน?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    ดู Genealogy Tree ได้ที่ <strong>User Dashboard → MLM → Genealogy</strong>
                </p>
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-2">
                    มีหลายมุมมอง:
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li>🌳 <strong>Tree View:</strong> มุมมองแบบต้นไม้ แสดงโครงสร้างทีม</li>
                    <li>📋 <strong>List View:</strong> มุมมองแบบรายการ ค้นหาง่าย</li>
                    <li>📊 <strong>Graph View:</strong> มุมมอง Interactive Graph</li>
                    <li>📈 <strong>Statistics:</strong> สถิติทีมและยอดขาย</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Technical FAQs --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        ⚙️ คำถามทางเทคนิค (Technical FAQs)
    </h2>

    <div class="flex flex-col gap-4">
        {{-- Tech FAQ 1 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    🖥️ System Requirements คืออะไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    <strong>Server Requirements:</strong>
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li>PHP 8.1+ (แนะนำ 8.3)</li>
                    <li>MySQL 8.0+ หรือ MariaDB 10.3+</li>
                    <li>Composer 2.x</li>
                    <li>Node.js 18+ และ NPM</li>
                    <li>Redis (สำหรับ Cache และ Queue)</li>
                    <li>Nginx หรือ Apache</li>
                </ul>
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    <strong>Minimum Server Specs:</strong>
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li>CPU: 2 Cores</li>
                    <li>RAM: 4GB (แนะนำ 8GB+)</li>
                    <li>Storage: 20GB SSD</li>
                </ul>
            </div>
        </div>

        {{-- Tech FAQ 2 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    🔄 จะอัปเดตระบบอย่างไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    การอัปเดตระบบทำได้ง่ายผ่าน Script:
                </p>
                <div class="bg-slate-800 rounded-lg p-4 my-4 overflow-x-auto">
                    <pre class="text-slate-200 m-0 font-mono text-sm"><code># 1. Check for updates
php artisan app:check-update

# 2. Run update script
./deploy.sh

# 3. Or manual update
git pull origin main
composer install --no-dev
npm run build
php artisan migrate
php artisan optimize</code></pre>
                </div>
            </div>
        </div>

        {{-- Tech FAQ 3 --}}
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <button @click="open = !open" class="w-full p-6 flex justify-between items-center bg-transparent border-none cursor-pointer text-left">
                <h4 class="font-bold m-0 text-gray-900 dark:text-white">
                    🔐 ระบบปลอดภัยแค่ไหน?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
                <p class="leading-relaxed text-gray-600 dark:text-gray-300 mt-4">
                    ระบบออกแบบด้วย Security Best Practices:
                </p>
                <ul class="leading-loose text-gray-600 dark:text-gray-300 mt-2">
                    <li>🔒 <strong>HTTPS/TLS 1.3:</strong> การเข้ารหัสการสื่อสาร</li>
                    <li>🔐 <strong>AES-256 Encryption:</strong> เข้ารหัสข้อมูลสำคัญ</li>
                    <li>🛡️ <strong>CSRF Protection:</strong> ป้องกัน Cross-Site Request Forgery</li>
                    <li>🚫 <strong>SQL Injection Prevention:</strong> ใช้ Prepared Statements</li>
                    <li>🔑 <strong>2FA:</strong> Two-Factor Authentication</li>
                    <li>📋 <strong>PDPA Compliance:</strong> รองรับกฎหมายคุ้มครองข้อมูลส่วนบุคคล</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Troubleshooting --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        🔧 Troubleshooting (วิธีแก้ไขปัญหา)
    </h2>

    <div class="info-box warning">
        <h4>⚠️ ก่อนติดต่อฝ่าย Support</h4>
        <p>ลองทำตามขั้นตอนด้านล่างก่อน หลายปัญหาสามารถแก้ไขได้ด้วยตัวเอง</p>
    </div>

    <div class="wiki-grid wiki-grid-2">
        {{-- Problem 1 --}}
        <div class="bg-white dark:bg-gray-800 border-2 border-red-500 rounded-xl p-6">
            <h4 class="font-bold mb-4 text-red-500">
                ❌ ไม่สามารถเข้าสู่ระบบได้
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul class="list-none pl-0 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>❓ ใส่ Email/Password ผิด</li>
                <li>❓ บัญชีถูกระงับ</li>
                <li>❓ ยังไม่ได้ยืนยันอีเมล</li>
            </ul>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol class="pl-6 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>ตรวจสอบ Email และ Password อีกครั้ง</li>
                <li>ใช้ฟีเจอร์ "ลืมรหัสผ่าน"</li>
                <li>ตรวจสอบกล่อง Spam ในอีเมล</li>
                <li>ติดต่อฝ่าย Support</li>
            </ol>
        </div>

        {{-- Problem 2 --}}
        <div class="bg-white dark:bg-gray-800 border-2 border-yellow-500 rounded-xl p-6">
            <h4 class="font-bold mb-4 text-yellow-600 dark:text-yellow-500">
                ⚠️ หน้าเว็บโหลดช้า
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul class="list-none pl-0 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>❓ อินเทอร์เน็ตช้า</li>
                <li>❓ Browser Cache เต็ม</li>
                <li>❓ Extension ขัดแย้ง</li>
            </ul>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol class="pl-6 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>ลองใช้ Incognito/Private Mode</li>
                <li>ล้าง Browser Cache</li>
                <li>ปิด Extension ที่ไม่จำเป็น</li>
                <li>ลอง Browser อื่น</li>
            </ol>
        </div>

        {{-- Problem 3 --}}
        <div class="bg-white dark:bg-gray-800 border-2 border-purple-500 rounded-xl p-6">
            <h4 class="font-bold mb-4 text-purple-600 dark:text-purple-400">
                💳 ชำระเงินไม่สำเร็จ
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul class="list-none pl-0 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>❓ ยอดเงินไม่เพียงพอ</li>
                <li>❓ บัตรหมดอายุ</li>
                <li>❓ การเชื่อมต่อขาดหาย</li>
            </ul>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol class="pl-6 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>ตรวจสอบยอดเงินในบัญชี</li>
                <li>ลองวิธีชำระเงินอื่น</li>
                <li>รอ 15 นาทีแล้วลองใหม่</li>
                <li>ติดต่อธนาคาร/ฝ่าย Support</li>
            </ol>
        </div>

        {{-- Problem 4 --}}
        <div class="bg-white dark:bg-gray-800 border-2 border-green-500 rounded-xl p-6">
            <h4 class="font-bold mb-4 text-green-600 dark:text-green-400">
                📊 ข้อมูลไม่อัปเดต
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul class="list-none pl-0 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>❓ Cache ข้อมูลเก่า</li>
                <li>❓ ระบบกำลังประมวลผล</li>
                <li>❓ Time Zone ไม่ตรง</li>
            </ul>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol class="pl-6 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                <li>กด Refresh หน้า (Ctrl+Shift+R)</li>
                <li>รอ 5-10 นาทีให้ระบบประมวลผล</li>
                <li>ตรวจสอบ Time Zone ในโปรไฟล์</li>
                <li>Logout แล้ว Login ใหม่</li>
            </ol>
        </div>
    </div>
</section>

{{-- Contact Support --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        📞 ติดต่อฝ่ายสนับสนุน
    </h2>

    <div class="bg-gradient-to-br from-primary-50 via-secondary-50 to-accent-50 dark:from-primary-900/20 dark:via-secondary-900/10 dark:to-accent-900/10 rounded-2xl p-8 text-center">
        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">
            ยังแก้ปัญหาไม่ได้? ติดต่อเราได้เลย!
        </h3>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            ทีมงานพร้อมให้บริการ 24/7 ตอบกลับภายใน 24 ชั่วโมง
        </p>
        <div class="flex gap-4 justify-center flex-wrap">
            <a href="{{ route('user.tickets.create') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white no-underline rounded-xl font-bold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                🎫 สร้าง Support Ticket
            </a>
            <a href="#" class="inline-flex items-center gap-2 px-8 py-4 bg-green-500 hover:bg-green-600 text-white no-underline rounded-xl font-bold transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                💬 LINE Official
            </a>
            <a href="mailto:support@thaiprompt.com" class="inline-flex items-center gap-2 px-8 py-4 bg-white dark:bg-gray-800 text-primary-500 no-underline rounded-xl font-bold border-2 border-primary-500 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:bg-primary-50 dark:hover:bg-primary-900/20">
                📧 Email Support
            </a>
        </div>
    </div>
</section>
