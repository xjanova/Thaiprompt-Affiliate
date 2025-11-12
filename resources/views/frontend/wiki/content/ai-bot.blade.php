<div class="wiki-content-section">
    <!-- Hero Section -->
    <div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 3rem 2rem; border-radius: 20px; margin-bottom: 3rem; color: white; text-align: center;">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">🤖 AI & Bot Ecosystem</h1>
        <p style="font-size: 1.25rem; opacity: 0.95; max-width: 800px; margin: 0 auto;">ระบบ AI ที่ทรงพลังและครบวงจรที่สุด รองรับ AI Chatbot, Image/Video Generation, และ LINE Bot Integration</p>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-navigation" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; border-bottom: 2px solid var(--wiki-border);">
        <button class="tab-btn active" data-tab="ai-chatbot" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); color: white; border: none; border-radius: 8px 8px 0 0; font-weight: 600; cursor: pointer;">
            🧠 AI Chatbot
        </button>
        <button class="tab-btn" data-tab="ai-creation" style="padding: 0.75rem 1.5rem; background: var(--wiki-card-bg); color: var(--wiki-text-primary); border: 1px solid var(--wiki-border); border-bottom: none; border-radius: 8px 8px 0 0; font-weight: 600; cursor: pointer;">
            🎨 AI Image/Video
        </button>
        <button class="tab-btn" data-tab="line-bot" style="padding: 0.75rem 1.5rem; background: var(--wiki-card-bg); color: var(--wiki-text-primary); border: 1px solid var(--wiki-border); border-bottom: none; border-radius: 8px 8px 0 0; font-weight: 600; cursor: pointer;">
            💬 LINE Bot
        </button>
        <button class="tab-btn" data-tab="knowledge" style="padding: 0.75rem 1.5rem; background: var(--wiki-card-bg); color: var(--wiki-text-primary); border: 1px solid var(--wiki-border); border-bottom: none; border-radius: 8px 8px 0 0; font-weight: 600; cursor: pointer;">
            📚 Knowledge Base
        </button>
        <button class="tab-btn" data-tab="rental" style="padding: 0.75rem 1.5rem; background: var(--wiki-card-bg); color: var(--wiki-text-primary); border: 1px solid var(--wiki-border); border-bottom: none; border-radius: 8px 8px 0 0; font-weight: 600; cursor: pointer;">
            💼 Bot Rental
        </button>
    </div>

    {{-- AI Chatbot Tab --}}
    <div class="tab-content active" data-tab-content="ai-chatbot">
        <section class="wiki-section">
            <h2>🧠 AI Chatbot Multi-Provider</h2>

            <div class="info-box success">
                <h4>✨ รองรับ AI หลายค่าย</h4>
                <p>
                    ระบบรองรับ AI Provider ชั้นนำทั้งหมด รวมถึง OpenAI GPT-4, Anthropic Claude, Google Gemini,
                    และ Local LLM พร้อมระบบ RAG (Retrieval-Augmented Generation) สำหรับการเรียนรู้จากเอกสาร
                </p>
            </div>

            <h3>🎯 AI Providers ที่รองรับ</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div class="feature-card" style="border: 2px solid var(--wiki-border); transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                    <div class="feature-icon">🧠</div>
                    <h4>OpenAI GPT-4</h4>
                    <ul style="font-size: 0.95rem; line-height: 1.8;">
                        <li>✅ GPT-4 Turbo</li>
                        <li>✅ GPT-4 Vision (วิเคราะห์รูปภาพ)</li>
                        <li>✅ GPT-3.5 Turbo (ราคาประหยัด)</li>
                        <li>✅ Function Calling & Tools</li>
                    </ul>
                </div>

                <div class="feature-card" style="border: 2px solid var(--wiki-border); transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                    <div class="feature-icon">🎨</div>
                    <h4>Anthropic Claude</h4>
                    <ul style="font-size: 0.95rem; line-height: 1.8;">
                        <li>✅ Claude 3 Opus (สุดยอด)</li>
                        <li>✅ Claude 3 Sonnet (สมดุล)</li>
                        <li>✅ Claude 3 Haiku (เร็ว)</li>
                        <li>✅ 100K+ Context Window</li>
                    </ul>
                </div>

                <div class="feature-card" style="border: 2px solid var(--wiki-border); transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                    <div class="feature-icon">🌟</div>
                    <h4>Google Gemini</h4>
                    <ul style="font-size: 0.95rem; line-height: 1.8;">
                        <li>✅ Gemini Pro</li>
                        <li>✅ Gemini Pro Vision</li>
                        <li>✅ Multimodal Support</li>
                        <li>✅ ใช้ฟรี (Limited)</li>
                    </ul>
                </div>

                <div class="feature-card" style="border: 2px solid var(--wiki-border); transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'">
                    <div class="feature-icon">💻</div>
                    <h4>Local AI (Self-Hosted)</h4>
                    <ul style="font-size: 0.95rem; line-height: 1.8;">
                        <li>✅ LLaMA 2 / LLaMA 3</li>
                        <li>✅ Mistral AI</li>
                        <li>✅ Custom Fine-tuned Models</li>
                        <li>✅ ไม่ต้องเสียค่า API</li>
                    </ul>
                </div>
            </div>

            <h3>⚙️ ความสามารถหลัก</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h4>Conversation Management</h4>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>• Multi-turn conversations (บทสนทนาต่อเนื่อง)</li>
                        <li>• Context retention (จำบริบท)</li>
                        <li>• Session management</li>
                        <li>• Conversation history & Export</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h4>Smart Responses</h4>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>• Intent recognition (รู้ว่าต้องการอะไร)</li>
                        <li>• Sentiment analysis (วิเคราะห์อารมณ์)</li>
                        <li>• Multi-language support (TH/EN/CN/JP)</li>
                        <li>• Custom system prompts</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h4>Analytics & Monitoring</h4>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>• Usage tracking (ติดตามการใช้งาน)</li>
                        <li>• Token consumption & Cost</li>
                        <li>• Response time metrics</li>
                        <li>• Quality scoring & Feedback</li>
                    </ul>
                </div>
            </div>

            <div class="info-box tip">
                <h4>💡 Use Cases</h4>
                <ul style="line-height: 2;">
                    <li><strong>Customer Support:</strong> ตอบคำถามลูกค้า 24/7 อัตโนมัติ</li>
                    <li><strong>Sales Assistant:</strong> ช่วยแนะนำสินค้า แนะนำ Package</li>
                    <li><strong>Lead Qualification:</strong> กรองลูกค้าที่สนใจจริงๆ</li>
                    <li><strong>Content Creation:</strong> สร้างเนื้อหาการตลาด บทความ</li>
                </ul>
            </div>
        </section>
    </div>

    {{-- AI Image/Video Creation Tab --}}
    <div class="tab-content" data-tab-content="ai-creation" style="display: none;">
        <section class="wiki-section">
            <h2>🎨 AI Image & Video Generation - สร้างภาพและวิดีโอด้วย AI</h2>

            <p style="font-size: 1.1rem; margin-bottom: 2rem;">
                ระบบสร้างภาพและวิดีโอด้วย AI ที่ทรงพลัง รองรับหลาย Provider พร้อมระบบให้เช่าการใช้งาน
                สร้างคอนเทนต์การตลาด, Product Images, Social Media Posts ได้อย่างง่ายดาย
            </p>

            <h3>🖼️ AI Image Generation Providers</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎨</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">DALL-E 3</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">OpenAI's Image Generator</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ คุณภาพสูงสุด 1024x1024</li>
                        <li>✅ เข้าใจ Prompt ซับซ้อน</li>
                        <li>✅ สร้างข้อความในภาพได้</li>
                        <li>✅ Style Consistency</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--secondary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🌌</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Midjourney</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">Artistic & Creative</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ สไตล์ศิลปะสวยงาม</li>
                        <li>✅ Upscale ได้สูงสุด 4K</li>
                        <li>✅ Variations & Remix</li>
                        <li>✅ Multiple Aspect Ratios</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--accent-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚡</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Stable Diffusion</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">Open Source & Customizable</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ ฟรี! (Self-hosted)</li>
                        <li>✅ Custom Models & LoRA</li>
                        <li>✅ ControlNet Support</li>
                        <li>✅ Unlimited Generations</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎭</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Leonardo.ai</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">Game Assets & Characters</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Character Design</li>
                        <li>✅ Product Mockups</li>
                        <li>✅ Background Removal</li>
                        <li>✅ Canvas Editor</li>
                    </ul>
                </div>
            </div>

            <h3>🎬 AI Video Generation</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div class="feature-card">
                    <div class="feature-icon">🎥</div>
                    <h4>Runway Gen-2</h4>
                    <p style="margin-bottom: 1rem;">Text/Image to Video</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Text → Video (4-16 วินาที)</li>
                        <li>✅ Image → Video Animation</li>
                        <li>✅ Video Editing & Effects</li>
                        <li>✅ Green Screen Removal</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🎬</div>
                    <h4>Pika Labs</h4>
                    <p style="margin-bottom: 1rem;">Creative Video AI</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 3D Animation</li>
                        <li>✅ Camera Motion Control</li>
                        <li>✅ Aspect Ratio Options</li>
                        <li>✅ Extend Video Length</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📹</div>
                    <h4>D-ID Studio</h4>
                    <p style="margin-bottom: 1rem;">AI Avatar & Presenter</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Photo → Talking Avatar</li>
                        <li>✅ Multi-language Voice</li>
                        <li>✅ Lip Sync Accuracy</li>
                        <li>✅ Custom Scripts</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">✨</div>
                    <h4>Synthesia</h4>
                    <p style="margin-bottom: 1rem;">Professional AI Videos</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 140+ AI Avatars</li>
                        <li>✅ 120+ Languages</li>
                        <li>✅ Templates Library</li>
                        <li>✅ Brand Customization</li>
                    </ul>
                </div>
            </div>

            <h3>💰 AI Creation Rental System</h3>

            <div class="info-box success">
                <h4>💼 สร้างรายได้จากการให้เช่าระบบ AI Image/Video Generation</h4>
                <p style="margin-bottom: 1rem;">
                    ให้เช่าระบบสร้างภาพและวิดีโอด้วย AI เป็นรายเดือน พร้อมระบบจัดการ Quota, Credit, และ Analytics ครบครัน
                </p>
                <ul style="line-height: 2;">
                    <li><strong>Credit-based System:</strong> ลูกค้าซื้อ Credit แล้วใช้งาน</li>
                    <li><strong>Package Subscription:</strong> แพ็คเกจรายเดือนสำหรับผู้ใช้งานประจำ</li>
                    <li><strong>Usage Analytics:</strong> ติดตามการใช้งาน แยกตาม Model</li>
                    <li><strong>Auto Billing:</strong> เรียกเก็บเงินอัตโนมัติทุกเดือน</li>
                </ul>
            </div>

            <h3>📦 AI Creation Packages</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin: 2rem 0;">
                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 16px; padding: 2rem; text-align: center;">
                    <h4 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">🥉 Basic</h4>
                    <div style="font-size: 2.5rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 1rem 0;">฿1,499<span style="font-size: 1rem; font-weight: 400; color: var(--wiki-text-muted);">/เดือน</span></div>
                    <ul style="list-style: none; padding-left: 0; text-align: left; line-height: 2;">
                        <li>✅ 100 Image Generations</li>
                        <li>✅ DALL-E 2, Stable Diffusion</li>
                        <li>✅ Resolution up to 1024x1024</li>
                        <li>✅ Basic Analytics</li>
                        <li>❌ Video Generation</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 16px; padding: 2rem; text-align: center; position: relative;">
                    <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: rgb(var(--secondary-rgb)); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 800;">⭐ POPULAR</div>
                    <h4 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">🥈 Pro</h4>
                    <div style="font-size: 2.5rem; font-weight: 800; color: rgb(var(--secondary-rgb)); margin: 1rem 0;">฿3,999<span style="font-size: 1rem; font-weight: 400; color: var(--wiki-text-muted);">/เดือน</span></div>
                    <ul style="list-style: none; padding-left: 0; text-align: left; line-height: 2;">
                        <li>✅ 500 Image Generations</li>
                        <li>✅ DALL-E 3, Midjourney, Stable Diffusion</li>
                        <li>✅ 50 Video Generations (4s)</li>
                        <li>✅ Runway, Pika Labs</li>
                        <li>✅ Priority Processing</li>
                        <li>✅ Advanced Analytics</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 16px; padding: 2rem; text-align: center;">
                    <h4 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">🥇 Enterprise</h4>
                    <div style="font-size: 2.5rem; font-weight: 800; color: rgb(var(--accent-rgb)); margin: 1rem 0;">฿12,999<span style="font-size: 1rem; font-weight: 400; color: var(--wiki-text-muted);">/เดือน</span></div>
                    <ul style="list-style: none; padding-left: 0; text-align: left; line-height: 2;">
                        <li>✅ Unlimited Image Generations</li>
                        <li>✅ All Providers (DALL-E 3, Midjourney, etc.)</li>
                        <li>✅ 300 Video Generations (16s)</li>
                        <li>✅ D-ID, Synthesia AI Avatars</li>
                        <li>✅ Custom Models Training</li>
                        <li>✅ API Access</li>
                        <li>✅ Dedicated Support</li>
                    </ul>
                </div>
            </div>

            <h3>🎯 Use Cases</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
                <div class="info-box">
                    <h4>📱 Social Media Content</h4>
                    <p>สร้างภาพโพสต์ Instagram, Facebook, TikTok อัตโนมัติ</p>
                </div>

                <div class="info-box">
                    <h4>🛍️ Product Images</h4>
                    <p>สร้างภาพสินค้าในฉากต่างๆ โดยไม่ต้องถ่ายจริง</p>
                </div>

                <div class="info-box">
                    <h4>🎬 Marketing Videos</h4>
                    <p>สร้างวิดีโอโฆษณา, Tutorial, Presentation</p>
                </div>

                <div class="info-box">
                    <h4>🎨 Design Mockups</h4>
                    <p>สร้าง Mockup สินค้า, Packaging, Branding</p>
                </div>
            </div>
        </section>
    </div>

    {{-- LINE Bot Tab --}}
    <div class="tab-content" data-tab-content="line-bot" style="display: none;">
        <section class="wiki-section">
            <h2>💬 LINE Official Account Integration</h2>

            <div class="info-box">
                <h4>🚀 ผสานรวมกับ LINE OA อย่างสมบูรณ์</h4>
                <p>
                    เชื่อมต่อกับ LINE Official Account ได้อย่างราบรื่น พร้อมฟีเจอร์ครบครัน
                    ทั้ง Rich Menu, Flex Message, Broadcast, และ AI Chatbot
                </p>
            </div>

            <h3>✨ ฟีเจอร์หลัก</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div class="feature-card">
                    <div class="feature-icon">📋</div>
                    <h4>Rich Menu Builder</h4>
                    <p>สร้าง Rich Menu ด้วย Visual Editor</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Drag & Drop Interface</li>
                        <li>✅ Multiple Templates</li>
                        <li>✅ Conditional Display</li>
                        <li>✅ A/B Testing</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h4>Flex Message</h4>
                    <p>ออกแบบข้อความสวยงามแบบ Custom</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Ready-to-use Templates</li>
                        <li>✅ Product Showcase</li>
                        <li>✅ Order Confirmation</li>
                        <li>✅ Event Invitations</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📢</div>
                    <h4>Broadcast Messaging</h4>
                    <p>ส่งข้อความหาผู้ติดตามจำนวนมาก</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Schedule Messages</li>
                        <li>✅ Segment Targeting</li>
                        <li>✅ Rich Content Support</li>
                        <li>✅ Analytics Dashboard</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h4>LINE Login</h4>
                    <p>เข้าสู่ระบบด้วย LINE Account</p>
                    <ul style="font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ One-click Login</li>
                        <li>✅ Profile Sync</li>
                        <li>✅ Friend Verification</li>
                        <li>✅ Secure Authentication</li>
                    </ul>
                </div>
            </div>

            <h3>🤖 LINE AI Features</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
                <div style="padding: 1.5rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); color: white; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎯</div>
                    <h4>Auto Reply</h4>
                    <p style="font-size: 0.9rem; opacity: 0.9;">ตอบกลับอัตโนมัติด้วย AI</p>
                </div>

                <div style="padding: 1.5rem; background: linear-gradient(135deg, rgb(var(--secondary-rgb)), rgb(var(--accent-rgb))); color: white; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏆</div>
                    <h4>Achievement Notifications</h4>
                    <p style="font-size: 0.9rem; opacity: 0.9;">แจ้งเตือนความสำเร็จของทีม</p>
                </div>

                <div style="padding: 1.5rem; background: linear-gradient(135deg, rgb(var(--accent-rgb)), rgb(var(--primary-rgb))); color: white; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                    <h4>Progress Tracking</h4>
                    <p style="font-size: 0.9rem; opacity: 0.9;">ติดตามความคืบหน้า MLM</p>
                </div>

                <div style="padding: 1.5rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--accent-rgb))); color: white; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
                    <h4>Commission Alerts</h4>
                    <p style="font-size: 0.9rem; opacity: 0.9;">แจ้งเตือนค่าคอมมิชชั่น</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Knowledge Base Tab --}}
    <div class="tab-content" data-tab-content="knowledge" style="display: none;">
        <section class="wiki-section">
            <h2>📚 Knowledge Base & RAG System</h2>

            <div class="info-box success">
                <h4>🧠 ระบบ RAG (Retrieval-Augmented Generation)</h4>
                <p>
                    อัพโหลดเอกสารความรู้ของคุณ ระบบจะแปลงเป็น Vector Embeddings และใช้ในการตอบคำถาม
                    ทำให้ AI Bot สามารถตอบคำถามเฉพาะเจาะจงตามเอกสารของคุณได้แม่นยำ
                </p>
            </div>

            <h3>📄 รองรับไฟล์หลายประเภท</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin: 2rem 0;">
                <div style="text-align: center; padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📝</div>
                    <h4>Text Files</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-muted);">.txt, .md</p>
                </div>

                <div style="text-align: center; padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📄</div>
                    <h4>PDF</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-muted);">.pdf</p>
                </div>

                <div style="text-align: center; padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📃</div>
                    <h4>Word</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-muted);">.docx, .doc</p>
                </div>

                <div style="text-align: center; padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">🌐</div>
                    <h4>URL</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-muted);">Web scraping</p>
                </div>
            </div>

            <h3>🔄 กระบวนการทำงาน RAG</h3>
            <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 2rem; margin: 2rem 0;">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: space-between;">
                    <div style="text-align: center; flex: 1; min-width: 120px;">
                        <div style="background: rgb(var(--primary-rgb)); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; margin: 0 auto 0.5rem;">1</div>
                        <h4 style="font-size: 0.9rem;">Upload Document</h4>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 120px;">
                        <div style="background: rgb(var(--secondary-rgb)); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; margin: 0 auto 0.5rem;">2</div>
                        <h4 style="font-size: 0.9rem;">Text Extraction</h4>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 120px;">
                        <div style="background: rgb(var(--accent-rgb)); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; margin: 0 auto 0.5rem;">3</div>
                        <h4 style="font-size: 0.9rem;">Chunking</h4>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 120px;">
                        <div style="background: rgb(var(--primary-rgb)); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; margin: 0 auto 0.5rem;">4</div>
                        <h4 style="font-size: 0.9rem;">Vector Embedding</h4>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 120px;">
                        <div style="background: rgb(var(--secondary-rgb)); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; margin: 0 auto 0.5rem;">5</div>
                        <h4 style="font-size: 0.9rem;">Semantic Search</h4>
                    </div>
                </div>
            </div>

            <div class="info-box tip">
                <h4>💡 ตัวอย่างการใช้งาน</h4>
                <ul style="line-height: 2;">
                    <li><strong>Company Knowledge Base:</strong> อัพโหลดเอกสารบริษัท AI จะตอบคำถามพนักงาน</li>
                    <li><strong>Product Manual:</strong> อัพโหลดคู่มือสินค้า AI ช่วยลูกค้าแก้ปัญหา</li>
                    <li><strong>Legal Documents:</strong> อัพโหลดเอกสารกฎหมาย AI ช่วยค้นหาข้อมูล</li>
                    <li><strong>Training Materials:</strong> อัพโหลดเอกสารอบรม AI ช่วยสอน</li>
                </ul>
            </div>
        </section>
    </div>

    {{-- Bot Rental Tab --}}
    <div class="tab-content" data-tab-content="rental" style="display: none;">
        <section class="wiki-section">
            <h2>💼 AI Bot Rental System</h2>

            <div class="info-box">
                <h4>💰 สร้างรายได้จากการให้เช่า Bot</h4>
                <p>
                    ให้เช่า AI Bot เป็นรายเดือน พร้อมระบบจัดการ Package, Subscription, Quota, และ Analytics
                    ครบครัน สามารถสร้างรายได้เป็นกอบเป็นกำ!
                </p>
            </div>

            <h3>📦 Bot Rental Packages</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin: 2rem 0;">
                <div style="background: var(--wiki-card-bg); border: 3px solid var(--wiki-border); border-radius: 16px; padding: 2rem; text-align: center; transition: all 0.3s;"
                     onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 32px rgba(var(--primary-rgb), 0.2)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; color: var(--wiki-text-primary);">Starter</div>
                    <div style="font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">฿999<span style="font-size: 1rem; color: var(--wiki-text-muted);">/เดือน</span></div>
                    <ul style="text-align: left; list-style: none; padding: 0; margin: 1.5rem 0; line-height: 2;">
                        <li>✅ 1,000 Messages/เดือน</li>
                        <li>✅ GPT-3.5 Turbo</li>
                        <li>✅ Basic Analytics</li>
                        <li>✅ Email Support</li>
                        <li>❌ RAG System</li>
                    </ul>
                    <button style="width: 100%; padding: 1rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">เริ่มต้นใช้งาน</button>
                </div>

                <div style="background: var(--wiki-card-bg); border: 3px solid rgb(var(--primary-rgb)); border-radius: 16px; padding: 2rem; text-align: center; position: relative; transition: all 0.3s;"
                     onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 32px rgba(var(--primary-rgb), 0.3)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: rgb(var(--primary-rgb)); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 800;">⭐ POPULAR</div>
                    <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Pro</div>
                    <div style="font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">฿2,999<span style="font-size: 1rem; color: var(--wiki-text-muted);">/เดือน</span></div>
                    <ul style="text-align: left; list-style: none; padding: 0; margin: 1.5rem 0; line-height: 2;">
                        <li>✅ 10,000 Messages/เดือน</li>
                        <li>✅ GPT-4 + Claude + Gemini</li>
                        <li>✅ Advanced Analytics</li>
                        <li>✅ RAG System (10 Documents)</li>
                        <li>✅ Priority Support</li>
                        <li>✅ Custom Branding</li>
                    </ul>
                    <button style="width: 100%; padding: 1rem; background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb))); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">เลือก Pro</button>
                </div>

                <div style="background: var(--wiki-card-bg); border: 3px solid var(--wiki-border); border-radius: 16px; padding: 2rem; text-align: center; transition: all 0.3s;"
                     onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 32px rgba(var(--accent-rgb), 0.2)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; color: var(--wiki-text-primary);">Enterprise</div>
                    <div style="font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; background: linear-gradient(135deg, rgb(var(--accent-rgb)), rgb(var(--primary-rgb))); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">฿9,999<span style="font-size: 1rem; color: var(--wiki-text-muted);">/เดือน</span></div>
                    <ul style="text-align: left; list-style: none; padding: 0; margin: 1.5rem 0; line-height: 2;">
                        <li>✅ Unlimited Messages</li>
                        <li>✅ All AI Models</li>
                        <li>✅ Full Analytics Suite</li>
                        <li>✅ RAG System (Unlimited Docs)</li>
                        <li>✅ 24/7 Support</li>
                        <li>✅ Dedicated Server</li>
                        <li>✅ Custom Integration & API</li>
                    </ul>
                    <button style="width: 100%; padding: 1rem; background: linear-gradient(135deg, rgb(var(--accent-rgb)), rgb(var(--primary-rgb))); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">ติดต่อเรา</button>
                </div>
            </div>

            <h3>📊 ระบบจัดการ Bot Rental</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0;">
                <div style="padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📈</div>
                    <h4>Usage Tracking</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ติดตามการใช้งาน Real-time</p>
                </div>

                <div style="padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">💳</div>
                    <h4>Auto Billing</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เรียกเก็บเงินอัตโนมัติทุกเดือน</p>
                </div>

                <div style="padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                    <h4>Analytics Dashboard</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">รายงานการใช้งานละเอียด</p>
                </div>

                <div style="padding: 1.5rem; background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔔</div>
                    <h4>Quota Alerts</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">แจ้งเตือนเมื่อใกล้หมด Quota</p>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)), rgb(var(--secondary-rgb)), rgb(var(--accent-rgb))); padding: 2.5rem; border-radius: 16px; color: white; text-align: center; margin: 3rem 0;">
                <div style="font-size: 3.5rem; margin-bottom: 1rem;">💰</div>
                <h3 style="font-size: 2rem; font-weight: 800; margin-bottom: 1rem; color: white;">เปลี่ยน AI เป็นรายได้!</h3>
                <p style="font-size: 1.1rem; opacity: 0.95; max-width: 700px; margin: 0 auto;">
                    สร้างรายได้ Passive Income จากการให้เช่า AI Bot ระบบจัดการทุกอย่างอัตโนมัติ
                    คุณแค่ขายแพ็คเกจ ระบบดูแลที่เหลือให้
                </p>
            </div>
        </section>
    </div>
</div>
