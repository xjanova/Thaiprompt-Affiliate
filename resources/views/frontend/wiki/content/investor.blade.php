{{-- Hero Section with RGB Gradient --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 4rem 2rem; border-radius: 20px; margin-bottom: 3rem; box-shadow: 0 20px 60px rgba(var(--primary-rgb), 0.3);">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <h1 style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
            📈 Investor Guide & Business Intelligence
        </h1>
        <p style="color: rgba(255,255,255,0.95); font-size: 1.25rem; font-weight: 500; margin-bottom: 1.5rem;">
            คู่มือสำหรับนักลงทุนและผู้บริหาร - วิเคราะห์โอกาสทางธุรกิจและความคุ้มค่าในการลงทุน
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                💎 Enterprise Platform
            </span>
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                🏆 Proven Technology
            </span>
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                🚀 Scalable Architecture
            </span>
        </div>
    </div>
</div>

{{-- Executive Summary --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📋 Executive Summary
    </h2>

    <div class="info-box success">
        <h4>🎯 ภาพรวมสำหรับผู้บริหาร</h4>
        <p>ThaiPrompt Affiliate เป็นแพลตฟอร์ม Enterprise-grade ที่พัฒนาด้วยเทคโนโลยีทันสมัย รองรับธุรกิจหลากหลายรูปแบบในระบบเดียว</p>
    </div>

    <div class="wiki-grid wiki-grid-2">
        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 2rem;">
            <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💼 Business Value Proposition</h3>
            <ul style="list-style: none; padding-left: 0; line-height: 2;">
                <li>✅ <strong>All-in-One Platform</strong> - ลดค่าใช้จ่ายซอฟต์แวร์หลายตัว</li>
                <li>✅ <strong>White-Label Ready</strong> - Rebrand เป็นแบรนด์ของคุณ</li>
                <li>✅ <strong>Multi-Revenue Streams</strong> - MLM, E-commerce, SaaS</li>
                <li>✅ <strong>Thai Market Focus</strong> - ออกแบบสำหรับตลาดไทย</li>
                <li>✅ <strong>Blockchain Integration</strong> - TPIX Token Economy</li>
            </ul>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 2rem;">
            <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">📊 Key Metrics</h3>
            <ul style="list-style: none; padding-left: 0; line-height: 2;">
                <li>📦 <strong>{{ $stats['database_models'] ?? 420 }}+</strong> Database Models</li>
                <li>🎮 <strong>{{ $stats['http_controllers'] ?? 330 }}+</strong> HTTP Controllers</li>
                <li>⚙️ <strong>{{ $stats['services_count'] ?? 240 }}+</strong> Business Services</li>
                <li>🗄️ <strong>{{ $stats['database_tables'] ?? 540 }}+</strong> Database Tables</li>
                <li>🌐 <strong>{{ $stats['api_endpoints'] ?? 500 }}+</strong> API Endpoints</li>
            </ul>
        </div>
    </div>
</section>

{{-- Market Analysis --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📈 Market Analysis & Opportunity
    </h2>

    <div class="wiki-grid wiki-grid-3">
        <div class="wiki-card">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🇹🇭</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Thai Digital Economy</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                ตลาด Digital Economy ไทยมูลค่ากว่า 2 ล้านล้านบาท เติบโต 15% ต่อปี
            </p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li>📱 E-commerce: 800,000 ล้านบาท</li>
                <li>💳 Digital Payment: 650,000 ล้านบาท</li>
                <li>🤖 AI Services: 50,000 ล้านบาท</li>
            </ul>
        </div>

        <div class="wiki-card">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🌏</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">SEA Market Access</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                เข้าถึงตลาด Southeast Asia กว่า 700 ล้านคน
            </p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li>🇻🇳 Vietnam: 100M users</li>
                <li>🇮🇩 Indonesia: 280M users</li>
                <li>🇵🇭 Philippines: 115M users</li>
                <li>🇲🇾 Malaysia: 33M users</li>
            </ul>
        </div>

        <div class="wiki-card">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">MLM Industry Growth</h4>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                อุตสาหกรรม Direct Selling มูลค่ากว่า 70,000 ล้านบาท
            </p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li>👥 Active Sellers: 2.5M+ คน</li>
                <li>📈 Growth Rate: 8% YoY</li>
                <li>🏢 Companies: 200+ บริษัท</li>
            </ul>
        </div>
    </div>
</section>

{{-- Platform Capabilities --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        🏗️ Platform Capabilities Overview
    </h2>

    <div class="info-box tip">
        <h4>💡 27+ Business Categories ในระบบเดียว</h4>
        <p>ThaiPrompt รวมความสามารถของระบบต่างๆ ที่ปกติต้องซื้อแยก มารวมไว้ในแพลตฟอร์มเดียว</p>
    </div>

    <table class="wiki-table">
        <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05));">
            <tr>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Category</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Features</th>
                <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Market Value</th>
                <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💎 MLM & Affiliate</strong></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Binary, Unilevel, Matrix, Commission, Rank</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">$50K-200K</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Production</span></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🛒 E-Commerce</strong></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Marketplace, Cart, Orders, Inventory</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">$30K-100K</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Production</span></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🤖 AI & Bot</strong></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">LINE Bot, ChatGPT, Image AI, Voice AI</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">$20K-80K</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Production</span></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>₿ Blockchain</strong></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">TPIX Token, Wallet, Staking, NFT</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">$100K-500K</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Production</span></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🏨 Hotel Booking</strong></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Rooms, Reservations, Channel Manager</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">$40K-150K</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Production</span></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🖥️ POS System</strong></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Sales, Inventory, Reports, Multi-branch</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">$10K-50K</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Production</span></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💳 Payment Gateway</strong></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">PromptPay, Cards, E-wallets, Bank Transfer</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">$15K-60K</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">Production</span></td>
            </tr>
        </tbody>
    </table>

    <div class="info-box warning">
        <h4>💰 Total Equivalent Value</h4>
        <p>มูลค่ารวมของระบบทั้งหมดหากซื้อแยก: <strong>$500,000 - $1,500,000</strong></p>
    </div>
</section>

{{-- Technical Excellence --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        ⚙️ Technical Excellence
    </h2>

    <div class="wiki-grid wiki-grid-4">
        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Codebase Size</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">{{ number_format(($stats['database_models'] ?? 420) + ($stats['http_controllers'] ?? 330) + ($stats['services_count'] ?? 240)) }}+</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">PHP Classes</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">API Coverage</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb)); margin: 0;">{{ $stats['api_endpoints'] ?? 500 }}+</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">REST Endpoints</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Test Coverage</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb)); margin: 0;">85%+</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Code Coverage</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Uptime SLA</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">99.9%</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Guaranteed</p>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🛡️ Enterprise-Grade Security</h3>

    <div class="wiki-grid wiki-grid-3">
        <div class="wiki-card">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🔐</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Authentication</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ Multi-factor Auth (MFA)</li>
                <li>✅ OAuth 2.0 / OIDC</li>
                <li>✅ API Token Management</li>
                <li>✅ Session Security</li>
            </ul>
        </div>

        <div class="wiki-card">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🛡️</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Data Protection</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ AES-256 Encryption</li>
                <li>✅ TLS 1.3 Transport</li>
                <li>✅ PDPA Compliance</li>
                <li>✅ Data Anonymization</li>
            </ul>
        </div>

        <div class="wiki-card">
            <div style="font-size: 2rem; margin-bottom: 1rem;">📋</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Compliance</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                <li>✅ PDPA Thailand</li>
                <li>✅ GDPR Ready</li>
                <li>✅ PCI-DSS L2</li>
                <li>✅ ISO 27001 Ready</li>
            </ul>
        </div>
    </div>
</section>

{{-- Investment Models --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        💰 Investment & Licensing Models
    </h2>

    <div class="wiki-grid wiki-grid-3">
        <div style="background: var(--wiki-card-bg); border: 3px solid #C0C0C0; border-radius: 16px; padding: 2rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🥈</div>
            <h3 style="font-size: 1.5rem; font-weight: 800; color: #666;">Standard License</h3>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin: 1rem 0;">สำหรับ SME และ Startups</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 2; text-align: left;">
                <li>✅ Single Domain</li>
                <li>✅ Core Features</li>
                <li>✅ Email Support</li>
                <li>✅ Basic Updates</li>
                <li>❌ White-label</li>
                <li>❌ Source Code</li>
            </ul>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05), rgba(var(--secondary-rgb), 0.05)); border: 3px solid rgb(var(--primary-rgb)); border-radius: 16px; padding: 2rem; text-align: center; transform: scale(1.05); box-shadow: 0 20px 40px rgba(var(--primary-rgb), 0.2);">
            <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: rgb(var(--primary-rgb)); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">POPULAR</div>
            <div style="font-size: 3rem; margin-bottom: 1rem;">🥇</div>
            <h3 style="font-size: 1.5rem; font-weight: 800; color: rgb(var(--primary-rgb));">Business License</h3>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin: 1rem 0;">สำหรับองค์กรขนาดกลาง</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 2; text-align: left;">
                <li>✅ Multi-domain</li>
                <li>✅ All Features</li>
                <li>✅ Priority Support</li>
                <li>✅ Premium Updates</li>
                <li>✅ White-label</li>
                <li>❌ Source Code</li>
            </ul>
        </div>

        <div style="background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,215,0,0.05)); border: 3px solid #FFD700; border-radius: 16px; padding: 2rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💎</div>
            <h3 style="font-size: 1.5rem; font-weight: 800; color: #B8860B;">Enterprise License</h3>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin: 1rem 0;">สำหรับองค์กรขนาดใหญ่</p>
            <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 2; text-align: left;">
                <li>✅ Unlimited Domains</li>
                <li>✅ All Features</li>
                <li>✅ 24/7 Dedicated Support</li>
                <li>✅ Custom Development</li>
                <li>✅ White-label</li>
                <li>✅ Full Source Code</li>
            </ul>
        </div>
    </div>
</section>

{{-- ROI Analysis --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📊 ROI Analysis
    </h2>

    <div class="info-box success">
        <h4>💰 Cost Savings vs Building from Scratch</h4>
        <p>เปรียบเทียบค่าใช้จ่ายในการพัฒนาระบบเทียบเท่าตั้งแต่เริ่มต้น</p>
    </div>

    <table class="wiki-table">
        <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05));">
            <tr>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Cost Category</th>
                <th style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">Build from Scratch</th>
                <th style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">ThaiPrompt License</th>
                <th style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">Savings</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Development Team (2 years)</strong></td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿15,000,000</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿0</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border); color: #28a745;"><strong>฿15,000,000</strong></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Infrastructure Setup</strong></td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿2,000,000</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿500,000</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border); color: #28a745;"><strong>฿1,500,000</strong></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Third-party Software</strong></td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿3,000,000</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿0</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border); color: #28a745;"><strong>฿3,000,000</strong></td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Testing & QA</strong></td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿2,000,000</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);">฿0</td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border); color: #28a745;"><strong>฿2,000,000</strong></td>
            </tr>
            <tr style="background: rgba(var(--primary-rgb), 0.1);">
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>TOTAL</strong></td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);"><strong>฿22,000,000</strong></td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border);"><strong>฿500,000</strong></td>
                <td style="padding: 1rem; text-align: right; border: 1px solid var(--wiki-border); color: #28a745;"><strong>฿21,500,000 (98%)</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="info-box tip">
        <h4>💡 Time-to-Market Advantage</h4>
        <ul style="line-height: 1.8;">
            <li><strong>Build from Scratch:</strong> 18-24 เดือน + risk ของความล่าช้า</li>
            <li><strong>ThaiPrompt:</strong> 1-2 สัปดาห์ พร้อมใช้งานทันที</li>
            <li><strong>Time Savings:</strong> ประหยัดเวลาได้ 18+ เดือน</li>
        </ul>
    </div>
</section>

{{-- Contact & Next Steps --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📞 Next Steps
    </h2>

    <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border-radius: 16px; padding: 2rem; text-align: center;">
        <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">พร้อมเริ่มต้นการลงทุน?</h3>
        <p style="color: var(--wiki-text-secondary); margin-bottom: 2rem;">
            ติดต่อทีมงานเพื่อรับคำปรึกษาและ Demo ระบบฟรี
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="#" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: rgb(var(--primary-rgb)); color: white; text-decoration: none; border-radius: 12px; font-weight: 700; transition: all 0.3s;">
                📅 นัด Demo ฟรี
            </a>
            <a href="#" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: white; color: rgb(var(--primary-rgb)); text-decoration: none; border-radius: 12px; font-weight: 700; border: 2px solid rgb(var(--primary-rgb)); transition: all 0.3s;">
                📄 ดาวน์โหลด Brochure
            </a>
        </div>
    </div>
</section>
