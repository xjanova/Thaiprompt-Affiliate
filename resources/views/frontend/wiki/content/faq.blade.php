{{-- Hero Section with RGB Gradient --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 4rem 2rem; border-radius: 20px; margin-bottom: 3rem; box-shadow: 0 20px 60px rgba(var(--primary-rgb), 0.3);">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <h1 style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
            ❓ FAQ & Troubleshooting
        </h1>
        <p style="color: rgba(255,255,255,0.95); font-size: 1.25rem; font-weight: 500; margin-bottom: 1.5rem;">
            คำถามที่พบบ่อยและวิธีแก้ไขปัญหา - คำตอบสำหรับทุกข้อสงสัย
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                💡 Quick Answers
            </span>
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                🔧 Troubleshooting
            </span>
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                📞 Support
            </span>
        </div>
    </div>
</div>

{{-- General FAQs --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📋 คำถามทั่วไป (General FAQs)
    </h2>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        {{-- FAQ Item 1 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    🤔 ThaiPrompt Affiliate คืออะไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    ThaiPrompt Affiliate เป็นแพลตฟอร์ม All-in-One สำหรับธุรกิจ MLM, E-commerce, AI Bot และอื่นๆ อีกมากมาย
                    พัฒนาด้วย Laravel 11 และ Modern Web Technologies ออกแบบมาเพื่อรองรับธุรกิจในประเทศไทยและอาเซียน
                    โดยมีฟีเจอร์มากกว่า 27 หมวดหมู่ ครอบคลุมทุกความต้องการทางธุรกิจ
                </p>
            </div>
        </div>

        {{-- FAQ Item 2 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    💰 ต้องใช้เงินลงทุนเท่าไหร่ในการเริ่มต้น?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    มีหลายรูปแบบการใช้งานตามความต้องการ:
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
                    <li><strong>Free Tier:</strong> สมัครสมาชิกและเริ่มต้น MLM ได้ฟรี</li>
                    <li><strong>Standard License:</strong> สำหรับ SME ที่ต้องการใช้งานเต็มรูปแบบ</li>
                    <li><strong>Business License:</strong> สำหรับองค์กรขนาดกลาง</li>
                    <li><strong>Enterprise License:</strong> สำหรับองค์กรขนาดใหญ่พร้อม Source Code</li>
                </ul>
            </div>
        </div>

        {{-- FAQ Item 3 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    🌐 รองรับภาษาอะไรบ้าง?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    ระบบรองรับหลายภาษาหลัก:
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
                    <li>🇹🇭 <strong>ไทย</strong> - ภาษาหลัก (Default)</li>
                    <li>🇺🇸 <strong>English</strong> - รองรับเต็มรูปแบบ</li>
                    <li>🇨🇳 <strong>中文 (简体)</strong> - รองรับด้วย Google Translate</li>
                    <li>🇯🇵 <strong>日本語</strong> - รองรับด้วย Google Translate</li>
                </ul>
            </div>
        </div>

        {{-- FAQ Item 4 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    📱 รองรับมือถือหรือไม่?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    ใช่! ระบบออกแบบด้วย Mobile-First Responsive Design รองรับทุกอุปกรณ์:
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
                    <li>📱 <strong>Smartphone</strong> - iOS และ Android</li>
                    <li>📲 <strong>Tablet</strong> - iPad และ Android Tablet</li>
                    <li>💻 <strong>Desktop</strong> - Windows, Mac, Linux</li>
                    <li>📺 <strong>Large Screen</strong> - รองรับหน้าจอขนาดใหญ่</li>
                </ul>
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    นอกจากนี้ยังมี .NET MAUI Mobile App สำหรับผู้ที่ต้องการ Native App
                </p>
            </div>
        </div>
    </div>
</section>

{{-- MLM FAQs --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        💎 คำถามเกี่ยวกับ MLM & Affiliate
    </h2>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        {{-- MLM FAQ 1 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    🌳 ระบบ MLM รองรับแผนอะไรบ้าง?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    ระบบรองรับหลายแผน MLM ที่นิยม:
                </p>
                <div class="wiki-grid wiki-grid-3" style="margin-top: 1rem;">
                    <div style="background: rgba(var(--primary-rgb), 0.1); padding: 1rem; border-radius: 8px;">
                        <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🌳 Binary Plan</h5>
                        <p style="font-size: 0.85rem;">แผน 2 ขา สร้างทีมซ้ายและขวา</p>
                    </div>
                    <div style="background: rgba(var(--secondary-rgb), 0.1); padding: 1rem; border-radius: 8px;">
                        <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🎯 Unilevel Plan</h5>
                        <p style="font-size: 0.85rem;">แผนหลายขา ไม่จำกัด Downline</p>
                    </div>
                    <div style="background: rgba(var(--accent-rgb), 0.1); padding: 1rem; border-radius: 8px;">
                        <h5 style="font-weight: 700; margin-bottom: 0.5rem;">📊 Matrix Plan</h5>
                        <p style="font-size: 0.85rem;">แผน Fixed Matrix เช่น 3x3, 4x7</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- MLM FAQ 2 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    💰 ค่าคอมมิชชั่นคำนวณอย่างไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    ค่าคอมมิชชั่นมีหลายประเภท:
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
                    <li><strong>Direct Commission:</strong> ค่าแนะนำตรง 5-15%</li>
                    <li><strong>Matching Bonus:</strong> โบนัสจับคู่ทีมซ้าย-ขวา</li>
                    <li><strong>Leadership Bonus:</strong> โบนัสผู้นำตามระดับ Rank</li>
                    <li><strong>Global Bonus:</strong> โบนัสจากยอดขายทั้งระบบ</li>
                    <li><strong>Rank Achievement:</strong> โบนัสเมื่อขึ้นระดับ Rank ใหม่</li>
                </ul>
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    อัตราค่าคอมมิชชั่นสามารถปรับแต่งได้ตามนโยบายบริษัท
                </p>
            </div>
        </div>

        {{-- MLM FAQ 3 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    📊 จะดู Genealogy Tree ได้ที่ไหน?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    ดู Genealogy Tree ได้ที่ <strong>User Dashboard → MLM → Genealogy</strong>
                </p>
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
                    มีหลายมุมมอง:
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
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
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        ⚙️ คำถามทางเทคนิค (Technical FAQs)
    </h2>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        {{-- Tech FAQ 1 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    🖥️ System Requirements คืออะไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    <strong>Server Requirements:</strong>
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
                    <li>PHP 8.1+ (แนะนำ 8.3)</li>
                    <li>MySQL 8.0+ หรือ MariaDB 10.3+</li>
                    <li>Composer 2.x</li>
                    <li>Node.js 18+ และ NPM</li>
                    <li>Redis (สำหรับ Cache และ Queue)</li>
                    <li>Nginx หรือ Apache</li>
                </ul>
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    <strong>Minimum Server Specs:</strong>
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
                    <li>CPU: 2 Cores</li>
                    <li>RAM: 4GB (แนะนำ 8GB+)</li>
                    <li>Storage: 20GB SSD</li>
                </ul>
            </div>
        </div>

        {{-- Tech FAQ 2 --}}
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    🔄 จะอัปเดตระบบอย่างไร?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    การอัปเดตระบบทำได้ง่ายผ่าน Script:
                </p>
                <div style="background: #1e293b; border-radius: 8px; padding: 1rem; margin: 1rem 0; overflow-x: auto;">
                    <pre style="color: #e2e8f0; margin: 0; font-family: 'Fira Code', monospace; font-size: 0.85rem;"><code># 1. Check for updates
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
        <div x-data="{ open: false }" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; overflow: hidden;">
            <button @click="open = !open" style="width: 100%; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none; cursor: pointer; text-align: left;">
                <h4 style="font-weight: 700; margin: 0; color: var(--wiki-text);">
                    🔐 ระบบปลอดภัยแค่ไหน?
                </h4>
                <svg :class="open ? 'rotate-180' : ''" style="width: 24px; height: 24px; transition: transform 0.3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-transition style="padding: 0 1.5rem 1.5rem; border-top: 1px solid var(--wiki-border);">
                <p style="line-height: 1.8; color: var(--wiki-text-secondary); margin-top: 1rem;">
                    ระบบออกแบบด้วย Security Best Practices:
                </p>
                <ul style="line-height: 2; color: var(--wiki-text-secondary); margin-top: 0.5rem;">
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
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        🔧 Troubleshooting (วิธีแก้ไขปัญหา)
    </h2>

    <div class="info-box warning">
        <h4>⚠️ ก่อนติดต่อฝ่าย Support</h4>
        <p>ลองทำตามขั้นตอนด้านล่างก่อน หลายปัญหาสามารถแก้ไขได้ด้วยตัวเอง</p>
    </div>

    <div class="wiki-grid wiki-grid-2">
        {{-- Problem 1 --}}
        <div style="background: var(--wiki-card-bg); border: 2px solid #dc3545; border-radius: 12px; padding: 1.5rem;">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: #dc3545;">
                ❌ ไม่สามารถเข้าสู่ระบบได้
            </h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li>❓ ใส่ Email/Password ผิด</li>
                <li>❓ บัญชีถูกระงับ</li>
                <li>❓ ยังไม่ได้ยืนยันอีเมล</li>
            </ul>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-top: 1rem;">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol style="padding-left: 1.5rem; font-size: 0.85rem; line-height: 1.8;">
                <li>ตรวจสอบ Email และ Password อีกครั้ง</li>
                <li>ใช้ฟีเจอร์ "ลืมรหัสผ่าน"</li>
                <li>ตรวจสอบกล่อง Spam ในอีเมล</li>
                <li>ติดต่อฝ่าย Support</li>
            </ol>
        </div>

        {{-- Problem 2 --}}
        <div style="background: var(--wiki-card-bg); border: 2px solid #ffc107; border-radius: 12px; padding: 1.5rem;">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: #cc9900;">
                ⚠️ หน้าเว็บโหลดช้า
            </h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li>❓ อินเทอร์เน็ตช้า</li>
                <li>❓ Browser Cache เต็ม</li>
                <li>❓ Extension ขัดแย้ง</li>
            </ul>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-top: 1rem;">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol style="padding-left: 1.5rem; font-size: 0.85rem; line-height: 1.8;">
                <li>ลองใช้ Incognito/Private Mode</li>
                <li>ล้าง Browser Cache</li>
                <li>ปิด Extension ที่ไม่จำเป็น</li>
                <li>ลอง Browser อื่น</li>
            </ol>
        </div>

        {{-- Problem 3 --}}
        <div style="background: var(--wiki-card-bg); border: 2px solid #6f42c1; border-radius: 12px; padding: 1.5rem;">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: #6f42c1;">
                💳 ชำระเงินไม่สำเร็จ
            </h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li>❓ ยอดเงินไม่เพียงพอ</li>
                <li>❓ บัตรหมดอายุ</li>
                <li>❓ การเชื่อมต่อขาดหาย</li>
            </ul>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-top: 1rem;">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol style="padding-left: 1.5rem; font-size: 0.85rem; line-height: 1.8;">
                <li>ตรวจสอบยอดเงินในบัญชี</li>
                <li>ลองวิธีชำระเงินอื่น</li>
                <li>รอ 15 นาทีแล้วลองใหม่</li>
                <li>ติดต่อธนาคาร/ฝ่าย Support</li>
            </ol>
        </div>

        {{-- Problem 4 --}}
        <div style="background: var(--wiki-card-bg); border: 2px solid #28a745; border-radius: 12px; padding: 1.5rem;">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: #28a745;">
                📊 ข้อมูลไม่อัปเดต
            </h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                <strong>สาเหตุที่พบบ่อย:</strong>
            </p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li>❓ Cache ข้อมูลเก่า</li>
                <li>❓ ระบบกำลังประมวลผล</li>
                <li>❓ Time Zone ไม่ตรง</li>
            </ul>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-top: 1rem;">
                <strong>วิธีแก้ไข:</strong>
            </p>
            <ol style="padding-left: 1.5rem; font-size: 0.85rem; line-height: 1.8;">
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
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📞 ติดต่อฝ่ายสนับสนุน
    </h2>

    <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border-radius: 16px; padding: 2rem; text-align: center;">
        <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">
            ยังแก้ปัญหาไม่ได้? ติดต่อเราได้เลย!
        </h3>
        <p style="color: var(--wiki-text-secondary); margin-bottom: 2rem;">
            ทีมงานพร้อมให้บริการ 24/7 ตอบกลับภายใน 24 ชั่วโมง
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('user.tickets.create') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: rgb(var(--primary-rgb)); color: white; text-decoration: none; border-radius: 12px; font-weight: 700; transition: all 0.3s;">
                🎫 สร้าง Support Ticket
            </a>
            <a href="#" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: #06c755; color: white; text-decoration: none; border-radius: 12px; font-weight: 700; transition: all 0.3s;">
                💬 LINE Official
            </a>
            <a href="mailto:support@thaiprompt.com" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: white; color: rgb(var(--primary-rgb)); text-decoration: none; border-radius: 12px; font-weight: 700; border: 2px solid rgb(var(--primary-rgb)); transition: all 0.3s;">
                📧 Email Support
            </a>
        </div>
    </div>
</section>
