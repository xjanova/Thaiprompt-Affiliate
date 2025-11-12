<div class="wiki-content-section">
    {{-- Hero Section --}}
    <div class="hero-section" style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 100%); padding: 3rem 2rem; border-radius: 20px; margin-bottom: 3rem; color: white; text-align: center;">
        <h1 class="hero-title" style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">🚀 Thaiprompt Affiliate Marketing Platform</h1>
        <p class="hero-subtitle" style="font-size: 1.25rem; opacity: 0.95; margin-bottom: 1.5rem; color: white;">
            แพลตฟอร์มธุรกิจออนไลน์ครบวงจร ที่รวมทุกฟีเจอร์ที่คุณต้องการไว้ในที่เดียว
        </p>

        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
            <span class="wiki-badge badge-green">{{ $stats['version'] }}</span>
            <span class="wiki-badge badge-blue">Updated {{ $stats['last_updated'] }}</span>
            <span class="wiki-badge badge-purple">Production Ready</span>
        </div>
    </div>

    {{-- Statistics Grid --}}
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div class="stat-card">
            <div class="stat-icon" style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
            <div class="stat-number">{{ $stats['database_models'] }}</div>
            <div class="stat-label">Database Models</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="font-size: 3rem; margin-bottom: 1rem;">⚙️</div>
            <div class="stat-number">{{ $stats['http_controllers'] }}</div>
            <div class="stat-label">Controllers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="font-size: 3rem; margin-bottom: 1rem;">🔧</div>
            <div class="stat-number">{{ $stats['services_count'] }}</div>
            <div class="stat-label">Business Services</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="font-size: 3rem; margin-bottom: 1rem;">💾</div>
            <div class="stat-number">{{ $stats['database_tables'] }}</div>
            <div class="stat-label">Database Tables</div>
        </div>
    </div>

    {{-- Problems & Opportunities --}}
    <section class="wiki-section">
        <h2>⚠️ ปัญหาและโอกาส: ทำไมต้องมีแพลตฟอร์มนี้</h2>

        <div class="info-box warning">
            <h4>ปัญหาหลักที่คนไทยเผชิญ</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div>
                    <strong>1. รายได้ไม่เพียงพอ:</strong> เงินเดือนค่าครองชีพสูง แต่รายได้ไม่เพิ่มตาม
                </div>
                <div>
                    <strong>2. ขาดโอกาสทางธุรกิจ:</strong> ทุนน้อย ไม่มีทักษะ ไม่กล้าเริ่มต้น
                </div>
                <div>
                    <strong>3. ถูกหลอกในธุรกิจ MLM:</strong> ระบบไม่โปร่งใส มีการโกง
                </div>
                <div>
                    <strong>4. ไม่มีเครื่องมือที่เหมาะสม:</strong> ระบบ MLM เก่าๆ ไม่มี AI ไม่มีอัตโนมัติ
                </div>
            </div>
        </div>

        <div class="stats-grid" style="margin: 2rem 0;">
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;">65%</div>
                <div class="stat-label">ของคนไทยมีรายได้ไม่เพียงพอ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f59e0b;">3M+</div>
                <div class="stat-label">คนทำงาน Freelance/Part-time</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;">40%</div>
                <div class="stat-label">ของผู้ทำ MLM ขาดทุน</div>
            </div>
        </div>

        <div class="info-box success">
            <h4>💡 โซลูชันของเรา - แก้ปัญหาได้จริง</h4>
            <ol style="margin-left: 1.5rem; margin-top: 0.5rem;">
                <li><strong>สร้างรายได้เสริมที่แท้จริง:</strong> ระบบ MLM ที่ยุติธรรม ไม่มีการโกง</li>
                <li><strong>ใช้งานง่าย:</strong> ไม่ต้องเป็นมือโปร ก็ใช้ได้</li>
                <li><strong>AI ช่วยทำงาน:</strong> ลดภาระงาน เพิ่มประสิทธิภาพ</li>
                <li><strong>เข้าถึงได้ทุกที่:</strong> มีแค่มือถือก็ทำธุรกิจได้</li>
                <li><strong>ต้นทุนต่ำ:</strong> ไม่ต้องลงทุนเยอะ เริ่มต้นได้ที่ 0 บาท</li>
            </ol>
        </div>
    </section>

    {{-- Feature Summary Section - CLICKABLE CARDS --}}
    <section class="wiki-section">
        <h2 id="feature-summary">✨ สรุปฟีเจอร์ทั้งหมด - ทำให้คุณทึ่ง!</h2>

        <div class="info-box">
            <h4>🎯 ภาพรวมของระบบ</h4>
            <p>
                <strong>Thaiprompt Affiliate Marketing Platform</strong> คือระบบธุรกิจออนไลน์ที่ครบครันที่สุด
                ออกแบบมาเพื่อรองรับทุกรูปแบบธุรกิจ ตั้งแต่ระบบขายตรง, อีคอมเมิร์ซ, AI Chatbot,
                การจัดการโรงแรม, ระบบบัญชี, HRM, Trading Bot, และอื่นๆ อีกมากมาย!
            </p>
        </div>

        {{-- Feature Category 1: MLM & Affiliate - CLICKABLE --}}
        <div class="feature-category">
            <h3>💎 1. ระบบ MLM & Affiliate Marketing</h3>
            <div class="feature-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                <a href="/wiki#mlm-affiliate" style="text-decoration: none; color: inherit;">
                    <div class="feature-card clickable-card" style="cursor: pointer;">
                        <div class="feature-icon">🌳</div>
                        <h4>MLM Binary & Unilevel</h4>
                        <p>รองรับทั้งระบบ Binary และ Unilevel พร้อมระบบจัดการโครงสร้างองค์กรแบบ Real-time</p>
                        <ul style="list-style: none; padding: 0;">
                            <li>✅ Binary Placement อัตโนมัติ</li>
                            <li>✅ Unilevel Genealogy Tree</li>
                            <li>✅ Commission Calculator แบบ Multi-level</li>
                            <li>✅ PV (Point Value) System</li>
                            <li>✅ Rank Achievement & Bonuses</li>
                        </ul>
                        <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                            👆 คลิกเพื่ออ่านเพิ่มเติม
                        </div>
                    </div>
                </a>

                <a href="/wiki#mlm-affiliate" style="text-decoration: none; color: inherit;">
                    <div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">💰</div>
                    <h4>ระบบคอมมิชชั่นอัจฉริยะ</h4>
                    <p>คำนวณค่าคอมมิชชั่นแบบอัตโนมัติ รองรับหลายระดับและหลายประเภท</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Direct Commission (ค่าแนะนำตรง)</li>
                        <li>✅ Matching Bonus (โบนัสจับคู่)</li>
                        <li>✅ Rank Bonus (โบนัสตามยศ)</li>
                        <li>✅ Leadership Bonus</li>
                        <li>✅ Auto Payout to Wallet</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>

                <a href="/wiki#mlm-affiliate" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">📊</div>
                    <h4>Genealogy & Analytics</h4>
                    <p>ติดตามโครงสร้างองค์กรและประสิทธิภาพของทีม</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Interactive Tree Visualization</li>
                        <li>✅ Team Performance Dashboard</li>
                        <li>✅ Downline Activity Tracking</li>
                        <li>✅ Sales Volume Reports</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>
            </div>
        </div>

        {{-- Feature Category 2: AI & Bot - CLICKABLE --}}
        <div class="feature-category">
            <h3>🤖 2. AI & Bot Ecosystem</h3>
            <div class="feature-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                <a href="/wiki#ai-bot" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">🧠</div>
                    <h4>AI Chatbot Multi-Provider</h4>
                    <p>รองรับ AI หลายค่าย พร้อมระบบ RAG (Retrieval-Augmented Generation)</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ OpenAI GPT-4, Claude, Gemini</li>
                        <li>✅ Local AI Support</li>
                        <li>✅ Knowledge Base Management</li>
                        <li>✅ Vector Embeddings & Semantic Search</li>
                        <li>✅ Conversation Context Management</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>

                <a href="/wiki#ai-bot" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">🎨</div>
                    <h4>AI Image & Video Generation</h4>
                    <p>สร้างภาพและวิดีโอด้วย AI เพื่อการตลาด</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ DALL-E, Midjourney, Stable Diffusion</li>
                        <li>✅ Text-to-Image Generation</li>
                        <li>✅ AI Video Creator</li>
                        <li>✅ Auto Product Images</li>
                        <li>✅ Marketing Content Generator</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>

                <a href="/wiki#ai-bot" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">💬</div>
                    <h4>LINE Official Account Integration</h4>
                    <p>ผสานรวมกับ LINE OA ได้อย่างสมบูรณ์</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Rich Menu Builder</li>
                        <li>✅ Flex Message Templates</li>
                        <li>✅ Broadcast Messaging</li>
                        <li>✅ LINE Login Integration</li>
                        <li>✅ Smart Follow-up System</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>
            </div>
        </div>

        {{-- Feature Category 3: Investment & ROI - CLICKABLE --}}
        <div class="feature-category">
            <h3>📈 3. Investment & ROI Distribution</h3>
            <div class="feature-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                <a href="/wiki#crypto" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">💎</div>
                    <h4>Staking & Investment Plans</h4>
                    <p>ระบบ Staking และลงทุนแบบหลากหลาย</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Flexible Staking Pools</li>
                        <li>✅ Fixed Term Investments</li>
                        <li>✅ Auto ROI Distribution</li>
                        <li>✅ Compound Interest Options</li>
                        <li>✅ Risk Management Tools</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>

                <a href="/wiki#crypto" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">💰</div>
                    <h4>ROI & Dividend System</h4>
                    <p>ระบบจ่ายผลตอบแทนและปันผลอัตโนมัติ</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Daily/Weekly/Monthly ROI</li>
                        <li>✅ Profit Sharing System</li>
                        <li>✅ Auto Reinvestment Options</li>
                        <li>✅ Transparent Calculation</li>
                        <li>✅ Real-time Tracking</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>

                <a href="/wiki#crypto" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">📊</div>
                    <h4>Portfolio Management</h4>
                    <p>จัดการพอร์ตการลงทุนแบบมืออาชีพ</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Multi-asset Tracking</li>
                        <li>✅ Performance Analytics</li>
                        <li>✅ Risk Assessment</li>
                        <li>✅ Rebalancing Tools</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>
            </div>
        </div>

        {{-- Feature Category 4: E-Commerce - CLICKABLE --}}
        <div class="feature-category">
            <h3>🛍️ 4. E-Commerce & Marketplace Integration</h3>
            <div class="feature-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                <a href="/wiki#ecommerce" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">🏪</div>
                    <h4>ระบบอีคอมเมิร์ซครบครัน</h4>
                    <p>ขายสินค้าออนไลน์ได้ทันทีพร้อมระบบจัดการที่ทันสมัย</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Product Management System</li>
                        <li>✅ Multi-variant Products</li>
                        <li>✅ Shopping Cart & Checkout</li>
                        <li>✅ Order Management</li>
                        <li>✅ Inventory Tracking</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>

                <a href="/wiki#ecommerce" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">🔗</div>
                    <h4>Marketplace Integration</h4>
                    <p>เชื่อมต่อกับ Marketplace ชั้นนำ</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Lazada API Integration</li>
                        <li>✅ Shopee API Integration</li>
                        <li>✅ TikTok Shop API</li>
                        <li>✅ Auto Product Sync</li>
                        <li>✅ Order Auto-import</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>

                <a href="/wiki#vendor" style="text-decoration: none; color: inherit;"><div class="feature-card clickable-card" style="cursor: pointer;">
                    <div class="feature-icon">📦</div>
                    <h4>Multi-Vendor System</h4>
                    <p>รองรับผู้ขายหลายราย แบบ Marketplace</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✅ Vendor Store Management</li>
                        <li>✅ Commission Split</li>
                        <li>✅ Vendor Analytics</li>
                        <li>✅ Package & Subscription</li>
                    </ul>
                    <div class="click-hint" style="margin-top: 1rem; text-align: center; color: rgb(var(--primary-rgb)); font-weight: 600;">
                        👆 คลิกเพื่ออ่านเพิ่มเติม
                    </div>
                </div></a>
            </div>
        </div>

        {{-- More categories with same clickable pattern... --}}
        <div class="info-box tip">
            <h4>📚 มีฟีเจอร์อีกมากมาย!</h4>
            <p>
                นี่เป็นเพียงส่วนหนึ่งของฟีเจอร์ทั้งหมด ระบบยังมีฟีเจอร์อื่นๆ อีกมากมาย เช่น
                <strong>Crypto & Trading</strong>, <strong>Payment & Wallet</strong>, <strong>HRM System</strong>,
                <strong>Academy & Learning</strong>, <strong>POS System</strong>, <strong>ระบบบัญชี</strong>,
                <strong>Hotel Booking</strong>, <strong>Software Sales</strong>, <strong>Security System</strong>,
                และอื่นๆ อีกเพียบ! <strong>คลิกที่การ์ดด้านบน</strong> หรือ <strong>เลือกหมวดจากเมนูด้านซ้าย</strong>
                เพื่ออ่านรายละเอียดเพิ่มเติม
            </p>
        </div>
    </section>

    {{-- Why Choose Us --}}
    <section class="wiki-section">
        <h2 id="why-choose-us">🏆 ทำไมต้องเลือกระบบของเรา?</h2>

        <div class="feature-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h4>ครบจบในที่เดียว</h4>
                <p>ไม่ต้องใช้ระบบหลายตัว ประหยัดต้นทุนและเวลาในการจัดการ</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h4>ออกแบบสวยงาม</h4>
                <p>UI/UX ทันสมัย รองรับ Dark Mode, Responsive Design, และ Mobile-friendly</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h4>ปลอดภัยสูงสุด</h4>
                <p>มีระบบรักษาความปลอดภัยครบครัน 2FA, KYC, IP Blocking, Threat Detection</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🚀</div>
                <h4>Performance สูง</h4>
                <p>ออกแบบมาเพื่อรองรับผู้ใช้งานจำนวนมาก พร้อม Caching และ Optimization</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h4>รองรับทุกอุปกรณ์</h4>
                <p>ใช้งานได้ทั้ง Desktop, Tablet, และ Mobile อย่างลื่นไหล</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔧</div>
                <h4>Customizable</h4>
                <p>ปรับแต่งได้ตามต้องการ มีระบบ Page Builder, Theme Settings, และ Custom Components</p>
            </div>
        </div>

        <div class="info-box success" style="margin-top: 2rem;">
            <h4>🇹🇭 Made in Thailand 100%</h4>
            <p>
                <strong>พัฒนาโดยทีมไทย 100%</strong> - ไม่ใช่ Copy มาจากต่างประเทศ<br>
                <strong>เงินทุนหมุนเวียนในไทย</strong> - ไม่รั่วไหลไปต่างประเทศ<br>
                <strong>Support เป็นภาษาไทย 24/7</strong> - เข้าใจบริบทธุรกิจไทย<br>
                <strong>ราคาที่เข้าถึงได้</strong> - ไม่ต้องจ่าย License ต่างประเทศ
            </p>
        </div>
    </section>

    {{-- Technology Stack --}}
    <section class="wiki-section">
        <h2 id="tech-stack">🔧 เทคโนโลยีที่ใช้</h2>

        <div class="tech-stack-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
            <div class="tech-item" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s ease;" onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'" onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Backend</h4>
                <ul style="list-style: none; padding: 0;">
                    <li>Laravel 11.x (PHP 8.3+)</li>
                    <li>MySQL/MariaDB</li>
                    <li>Redis Cache</li>
                    <li>Queue Jobs</li>
                </ul>
            </div>

            <div class="tech-item" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s ease;" onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'" onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">Frontend</h4>
                <ul style="list-style: none; padding: 0;">
                    <li>Blade Templates</li>
                    <li>TailwindCSS</li>
                    <li>Alpine.js</li>
                    <li>JavaScript ES6+</li>
                </ul>
            </div>

            <div class="tech-item" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s ease;" onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'" onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">AI & Services</h4>
                <ul style="list-style: none; padding: 0;">
                    <li>OpenAI GPT-4</li>
                    <li>Anthropic Claude</li>
                    <li>Google Gemini</li>
                    <li>Local LLM Support</li>
                </ul>
            </div>

            <div class="tech-item" style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s ease;" onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'" onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Integration</h4>
                <ul style="list-style: none; padding: 0;">
                    <li>LINE Messaging API</li>
                    <li>Marketplace APIs</li>
                    <li>Crypto Exchange APIs</li>
                    <li>Payment Gateways</li>
                </ul>
            </div>
        </div>
    </section>
</div>

<style>
.wiki-content-section {
    padding: 2rem 0;
}

.stat-card {
    background: var(--wiki-card-bg);
    border: 2px solid var(--wiki-border);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: rgb(var(--primary-rgb));
    box-shadow: 0 10px 30px rgba(var(--primary-rgb), 0.3);
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb)));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--wiki-text-muted);
    font-size: 0.9rem;
    font-weight: 600;
}

.feature-category {
    margin: 3rem 0;
    padding: 2rem;
    background: var(--wiki-hover-bg);
    border-radius: 16px;
    transition: all 0.3s ease;
}

.feature-category:hover {
    box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.1);
}

.feature-category h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 2rem;
    color: var(--wiki-text-primary);
}

.feature-card {
    background: var(--wiki-card-bg);
    border: 2px solid var(--wiki-border);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-2px);
    border-color: rgb(var(--primary-rgb));
    box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.2);
}

.clickable-card {
    position: relative;
}

.clickable-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 32px rgba(var(--primary-rgb), 0.3);
}

.clickable-card .click-hint {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.clickable-card:hover .click-hint {
    opacity: 1;
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb)));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.feature-card h4 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--wiki-text-primary);
}

.feature-card p {
    color: var(--wiki-text-secondary);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.feature-card ul li {
    padding: 0.4rem 0;
    color: var(--wiki-text-secondary);
    font-size: 0.9rem;
}
</style>
