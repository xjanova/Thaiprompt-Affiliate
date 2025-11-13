{{-- Hero Section with RGB Gradient --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 4rem 2rem; border-radius: 20px; margin-bottom: 3rem; box-shadow: 0 20px 60px rgba(var(--primary-rgb), 0.3);">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <div style="display: inline-block; background: rgba(255,255,255,0.2); padding: 0.5rem 1.5rem; border-radius: 50px; margin-bottom: 1.5rem; backdrop-filter: blur(10px);">
            <span style="color: white; font-weight: 600; font-size: 0.9rem;">💻 Software Sales & Licensing</span>
        </div>
        <h1 style="color: white; font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; text-shadow: 0 2px 20px rgba(0,0,0,0.2);">
            ระบบขายซอฟต์แวร์ & License Management
        </h1>
        <p style="color: rgba(255,255,255,0.95); font-size: 1.3rem; line-height: 1.8; margin-bottom: 2rem;">
            ระบบบริหารจัดการขายซอฟต์แวร์ ใบเสนอราคาอัตโนมัติ การจัดการ License Key<br>
            ระบบ Subscription และการต่ออายุ พร้อม Customer Portal ที่ครบครัน
        </p>
        <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">∞</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Unlimited Products</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">Auto</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Auto Quotation</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">99.9%</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">License Uptime</div>
            </div>
        </div>
    </div>
</section>

{{-- 1: Product & Licensing --}}
<section id="tab1" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📦</span> Software Product Catalog
        </h2>

        <div class="wiki-grid wiki-grid-3">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">☁️</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">SaaS Products</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    Cloud-based subscription software with monthly/annual billing, user-based pricing, and auto-provisioning
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Monthly/Annual Plans</li>
                    <li>Per-User/Per-Feature Pricing</li>
                    <li>Auto License Provisioning</li>
                    <li>Usage-Based Billing</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--secondary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">💻</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Desktop Software</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    Perpetual or subscription licenses for Windows/Mac applications with version control
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Perpetual Licenses</li>
                    <li>Version Upgrade Paths</li>
                    <li>Hardware Lock Options</li>
                    <li>Offline Activation</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--accent-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📱</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Mobile Apps</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    iOS/Android applications with in-app purchases and subscription management
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>App Store Integration</li>
                    <li>In-App Purchases</li>
                    <li>Trial Periods</li>
                    <li>Family Sharing</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏢</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Enterprise Solutions</h4>
                <p style="color: var(--wiki-text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                    Custom pricing for large organizations with volume discounts and dedicated support
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Volume Licensing</li>
                    <li>Custom Deployment</li>
                    <li>Dedicated Support</li>
                    <li>SLA Guarantees</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            💳 License Types & Pricing Models
        </h3>

        <table class="wiki-table">
            <thead>
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">License Type</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Pricing Model</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Duration</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Best For</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Transfer</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔒 Perpetual</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">One-time payment + Optional maintenance</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Lifetime</td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Desktop software, Professional tools</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">✅</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📅 Subscription</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Monthly/Annual recurring</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1-12 months</td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">SaaS, Cloud services, Updates included</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">❌</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📦 Volume</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Tiered pricing (10, 50, 100+ users)</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Annual</td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Enterprise, SME, Organizations</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Limited</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🎓 Educational</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">50-70% discount</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1 year</td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Students, Teachers, Schools</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">❌</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🆓 Freemium</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Free + Paid upgrades</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Unlimited</td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">User acquisition, Basic features</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">N/A</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⏱️ Trial</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Full features, Time-limited</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">7-30 days</td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Product evaluation, Demos</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Convert only</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🔑 License Activation System
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb)); margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">1️⃣</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Generate Key</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        System generates unique license key with product code, expiry, and features encoded
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">2️⃣</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Customer Activation</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Customer enters key in software, system validates online or offline with hardware fingerprint
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">3️⃣</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Activation Lock</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        License bound to device with transfer limits, prevents piracy and unauthorized use
                    </p>
                </div>
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">4️⃣</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Usage Monitoring</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        Track usage, check expiry, send renewal reminders automatically via email/SMS
                    </p>
                </div>
            </div>
        </div>

        <div style="background: var(--wiki-hover-bg); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--accent-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">💡 Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Hardware Fingerprinting:</strong> Bind license to MAC address, CPU ID, or hardware signature to prevent key sharing</li>
                <li><strong>Grace Period:</strong> Allow 7-14 days grace period after expiry before disabling software completely</li>
                <li><strong>Transfer Policy:</strong> Allow 2-3 transfers per year for hardware upgrades, require admin approval</li>
                <li><strong>Offline Activation:</strong> Provide manual activation for air-gapped systems with challenge-response</li>
                <li><strong>Feature Flags:</strong> Use license to enable/disable specific features dynamically (Pro, Enterprise, etc.)</li>
            </ul>
        </div>
    </section>
</section>

{{-- 2: Quotation & Sales --}}
<section id="tab2" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📄</span> Auto Quotation Generation
        </h2>

        <div class="wiki-grid wiki-grid-3">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">
                    🎨 Custom Templates
                </h4>
                <p style="color: var(--wiki-text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                    Create professional quotations with your brand logo, colors, terms, and custom layouts
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Logo & Company Branding</li>
                    <li>Custom Header/Footer</li>
                    <li>Multi-language Support (TH/EN)</li>
                    <li>Terms & Conditions</li>
                    <li>Payment Instructions</li>
                    <li>Digital Signatures</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">
                    🧮 Pricing Calculator
                </h4>
                <p style="color: var(--wiki-text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                    Dynamic pricing with quantity discounts, bundle deals, and promo codes
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Quantity Discounts (10%, 20%, 30%)</li>
                    <li>Bundle Pricing</li>
                    <li>Promo Code Integration</li>
                    <li>VAT Calculation (7%)</li>
                    <li>Multi-Currency Support</li>
                    <li>Maintenance/Support Add-ons</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">
                    📧 Auto Email Sending
                </h4>
                <p style="color: var(--wiki-text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                    Automatically send quotations via email with PDF attachment and tracking
                </p>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Email Templates</li>
                    <li>PDF Attachment (Auto-generated)</li>
                    <li>Open/Click Tracking</li>
                    <li>Follow-up Reminders</li>
                    <li>Expiry Notifications</li>
                    <li>CC/BCC Support</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            📊 Sales Pipeline Management
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div class="wiki-grid wiki-grid-3">
                <div style="background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">🎯</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Lead</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.85rem;">
                        Initial inquiry<br>Qualify prospect
                    </p>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">📄</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">Quotation Sent</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.85rem;">
                        Proposal delivered<br>Awaiting response
                    </p>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">💬</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">Negotiation</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.85rem;">
                        Price discussion<br>Feature requests
                    </p>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">📝</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Closing</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.85rem;">
                        Final approval<br>Contract signing
                    </p>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">✅</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">Won</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.85rem;">
                        Payment received<br>License issued
                    </p>
                </div>
            </div>
        </div>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            💰 Pricing Tiers Example
        </h3>

        <table class="wiki-table">
            <thead>
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Product</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Starter</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Professional</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Enterprise</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💼 CRM System</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--primary-rgb));">฿2,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Up to 5 users</span>
                    </td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--secondary-rgb));">฿7,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Up to 20 users</span>
                    </td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--accent-rgb));">Custom</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Unlimited</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📊 Analytics Pro</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--primary-rgb));">฿4,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Basic reports</span>
                    </td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--secondary-rgb));">฿9,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Advanced AI</span>
                    </td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--accent-rgb));">฿29,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Custom dashboards</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🛒 E-Commerce Suite</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--primary-rgb));">฿3,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">100 products</span>
                    </td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--secondary-rgb));">฿8,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">1,000 products</span>
                    </td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">
                        <strong style="color: rgb(var(--accent-rgb));">฿19,990/mo</strong><br>
                        <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Unlimited</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="background: var(--wiki-hover-bg); padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
            <h4 style="font-weight: 700; margin-bottom: 1.5rem;">📈 Quotation Conversion Tips</h4>
            <div class="wiki-grid wiki-grid-3">
                <div>
                    <h5 style="font-weight: 700; color: rgb(var(--primary-rgb)); margin-bottom: 0.5rem;">⏱️ Time Pressure</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.95rem;">
                        Add expiry date (7-14 days) to create urgency. "Valid until [Date]"
                    </p>
                </div>
                <div>
                    <h5 style="font-weight: 700; color: rgb(var(--secondary-rgb)); margin-bottom: 0.5rem;">🎁 Special Offers</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.95rem;">
                        Include limited-time discounts or free add-ons (e.g., "Free 3 months support")
                    </p>
                </div>
                <div>
                    <h5 style="font-weight: 700; color: rgb(var(--accent-rgb)); margin-bottom: 0.5rem;">✅ Social Proof</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.95rem;">
                        Mention existing customers/success stories in quotation footer
                    </p>
                </div>
                <div>
                    <h5 style="font-weight: 700; color: rgb(var(--primary-rgb)); margin-bottom: 0.5rem;">📞 Clear CTA</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.95rem;">
                        Include easy next steps: "Click to Accept" button, phone number, chat link
                    </p>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.1) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--accent-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">💡 Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Personalization:</strong> Address customer by name, reference previous conversations, customize recommendations</li>
                <li><strong>Itemization:</strong> Break down pricing clearly - license cost, maintenance, support, training separately</li>
                <li><strong>Options:</strong> Provide 2-3 package options (Good, Better, Best) to give choice and upsell</li>
                <li><strong>Visual Appeal:</strong> Use professional design, company colors, clean layout - first impression matters</li>
                <li><strong>Follow-up:</strong> Auto-send reminder emails at 3, 7, and 14 days if no response received</li>
            </ul>
        </div>
    </section>
</section>

{{-- 3: Customer Portal --}}
<section id="tab3" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>👤</span> Customer Self-Service Portal
        </h2>

        <div class="wiki-grid wiki-grid-3">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔑</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">License Management</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>View all active licenses</li>
                    <li>Check expiry dates</li>
                    <li>Manage activations (deactivate/reactivate)</li>
                    <li>Transfer to new device</li>
                    <li>Upgrade/Downgrade plans</li>
                    <li>View usage statistics</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--secondary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📥</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Download Center</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Download installers (Windows/Mac/Linux)</li>
                    <li>Access all purchased versions</li>
                    <li>Mobile app links (iOS/Android)</li>
                    <li>Documentation & Manuals (PDF)</li>
                    <li>Video tutorials</li>
                    <li>Release notes & changelogs</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--accent-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🎫</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Support Tickets</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Create new support ticket</li>
                    <li>Track ticket status</li>
                    <li>View conversation history</li>
                    <li>Attach files/screenshots</li>
                    <li>Priority support (Pro/Enterprise)</li>
                    <li>Knowledge base articles</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔄</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Renewal Management</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Auto-renewal settings (On/Off)</li>
                    <li>Update payment method</li>
                    <li>View renewal history</li>
                    <li>Invoice downloads</li>
                    <li>Early renewal discounts</li>
                    <li>Cancellation requests</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--secondary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📊</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Usage Analytics</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Track active users/devices</li>
                    <li>Feature usage statistics</li>
                    <li>API call limits (if applicable)</li>
                    <li>Storage usage (cloud apps)</li>
                    <li>Performance metrics</li>
                    <li>Export usage reports</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--accent-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">👥</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Team Management</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Invite team members</li>
                    <li>Assign roles & permissions</li>
                    <li>Monitor team usage</li>
                    <li>Seat management (add/remove)</li>
                    <li>Activity logs</li>
                    <li>Billing admin controls</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🔔 Automated Notifications
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;">⏰</div>
                        <h5 style="font-weight: 700; color: rgb(var(--primary-rgb));">Expiry Reminders</h5>
                    </div>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        30, 14, 7, and 1 day before license expires via email and in-app notification
                    </p>
                </div>

                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;">🆕</div>
                        <h5 style="font-weight: 700; color: rgb(var(--secondary-rgb));">Update Alerts</h5>
                    </div>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        New version released, new features available, security patches ready to install
                    </p>
                </div>

                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;">💳</div>
                        <h5 style="font-weight: 700; color: rgb(var(--accent-rgb));">Payment Status</h5>
                    </div>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        Payment successful, failed payment, payment method expiring, invoice ready
                    </p>
                </div>

                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;">✅</div>
                        <h5 style="font-weight: 700; color: rgb(var(--primary-rgb));">Ticket Updates</h5>
                    </div>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        Support ticket reply, ticket resolved, new knowledge base article matches your issue
                    </p>
                </div>
            </div>
        </div>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            📱 Mobile App Access
        </h3>

        <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">📱 iOS App</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.95rem; margin-bottom: 0.5rem;">
                        Full customer portal access on iPhone/iPad
                    </p>
                    <ul style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.85rem; margin-left: 1.2rem;">
                        <li>Face ID/Touch ID login</li>
                        <li>Push notifications</li>
                        <li>Offline license check</li>
                    </ul>
                </div>

                <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">🤖 Android App</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.95rem; margin-bottom: 0.5rem;">
                        Complete portal features on Android devices
                    </p>
                    <ul style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.85rem; margin-left: 1.2rem;">
                        <li>Fingerprint authentication</li>
                        <li>Firebase notifications</li>
                        <li>QR code activation</li>
                    </ul>
                </div>

                <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">🌐 Progressive Web App</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.95rem; margin-bottom: 0.5rem;">
                        No installation required, works everywhere
                    </p>
                    <ul style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.85rem; margin-left: 1.2rem;">
                        <li>Add to home screen</li>
                        <li>Offline mode</li>
                        <li>Cross-platform</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="background: var(--wiki-hover-bg); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--primary-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💡 Customer Portal Benefits</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>24/7 Self-Service:</strong> Customers can manage licenses, download software, and check status anytime without contacting support</li>
                <li><strong>Reduced Support Load:</strong> 60-70% of common queries (downloads, license keys, renewals) handled automatically</li>
                <li><strong>Faster Resolution:</strong> Instant access to documentation, FAQs, and troubleshooting reduces ticket resolution time</li>
                <li><strong>Improved Retention:</strong> Easy renewal process with saved payment methods increases subscription continuity</li>
                <li><strong>Upsell Opportunities:</strong> In-portal upgrade prompts and feature comparisons drive plan upgrades</li>
            </ul>
        </div>
    </section>
</section>

{{-- 4: Analytics & Renewals --}}
<section id="tab4" class="wiki-section">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📊</span> Sales Analytics & Revenue Metrics
        </h2>

        <div class="wiki-grid wiki-grid-3">
            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">💰</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Monthly Recurring Revenue</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb));">฿847,500</div>
                <div style="font-size: 0.85rem; color: rgb(var(--secondary-rgb)); margin-top: 0.5rem;">▲ 18% from last month</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--secondary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📅</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Annual Recurring Revenue</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb));">฿10.17M</div>
                <div style="font-size: 0.85rem; color: rgb(var(--primary-rgb)); margin-top: 0.5rem;">▲ 24% YoY growth</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--accent-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👥</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Active Licenses</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb));">1,847</div>
                <div style="font-size: 0.85rem; color: rgb(var(--secondary-rgb)); margin-top: 0.5rem;">▲ 127 new this month</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔄</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Renewal Rate</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb));">87.4%</div>
                <div style="font-size: 0.85rem; color: rgb(var(--secondary-rgb)); margin-top: 0.5rem;">▲ 3.2% improvement</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--secondary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📉</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Churn Rate</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb));">4.2%</div>
                <div style="font-size: 0.85rem; color: rgb(var(--primary-rgb)); margin-top: 0.5rem;">▼ 1.1% lower (good)</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--accent-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⏱️</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Average LTV</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb));">฿127,500</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Customer Lifetime Value</div>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            📈 Sales Reports & Insights
        </h3>

        <table class="wiki-table">
            <thead>
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Product Line</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Active Licenses</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">MRR</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Renewal %</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Avg License Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💼 CRM Pro</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">524</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿287,800</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">91.2%</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿5,490/mo</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📊 Analytics Suite</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">387</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿248,700</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">89.4%</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿6,430/mo</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🛒 E-Commerce</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">612</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿198,900</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">84.7%</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿3,250/mo</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🎓 LMS Platform</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">324</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿112,100</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">86.1%</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿3,460/mo</td>
                </tr>
                <tr style="background: rgba(var(--primary-rgb), 0.05); font-weight: 700;">
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">TOTAL</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1,847</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿847,500</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">87.4%</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿4,588/mo</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🔄 Renewal Forecasting & Automation
        </h3>

        <div class="wiki-grid wiki-grid-3">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">
                    📅 Upcoming Renewals
                </h4>
                <div style="line-height: 2; color: var(--wiki-text);">
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--wiki-border);">
                        <span>Next 7 days:</span>
                        <strong style="color: rgb(var(--accent-rgb));">147 licenses (฿89,200)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--wiki-border);">
                        <span>Next 30 days:</span>
                        <strong style="color: rgb(var(--secondary-rgb));">524 licenses (฿318,500)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                        <span>Next 90 days:</span>
                        <strong style="color: rgb(var(--primary-rgb));">1,287 licenses (฿782,100)</strong>
                    </div>
                </div>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">
                    🤖 Auto-Renewal Setup
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Auto-charge saved payment method 7 days before expiry</li>
                    <li>Email confirmation sent immediately</li>
                    <li>New license key generated automatically</li>
                    <li>Fallback to manual renewal if payment fails</li>
                    <li>Retry failed payments 3 times over 7 days</li>
                    <li>Grace period: 14 days after expiry</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">
                    💌 Renewal Campaigns
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li><strong>60 days:</strong> Early bird discount (10% off)</li>
                    <li><strong>30 days:</strong> Renewal reminder with benefits</li>
                    <li><strong>14 days:</strong> Urgent renewal notice</li>
                    <li><strong>7 days:</strong> Last chance + phone call (>฿50k)</li>
                    <li><strong>Expiry day:</strong> Final reminder</li>
                    <li><strong>7 days after:</strong> Win-back offer (15% off)</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--primary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🎯 Churn Prevention Strategies
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem;">
                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚨</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">At-Risk Detection</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        Track login frequency, feature usage, support tickets. Flag customers with declining engagement 60 days before renewal.
                    </p>
                </div>

                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📞</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">Proactive Outreach</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        Account manager calls at-risk customers, offers training, addresses pain points, suggests better plan fit.
                    </p>
                </div>

                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎁</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">Retention Offers</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        Provide discounts (15-25%), free upgrades, extended payment terms, or additional users at no cost for 3-6 months.
                    </p>
                </div>

                <div>
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📚</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">Success Program</h5>
                    <p style="color: var(--wiki-text-secondary); line-height: 1.6; font-size: 0.9rem;">
                        Onboarding checklists, quarterly business reviews, dedicated support for high-value customers (>฿100k/year).
                    </p>
                </div>
            </div>
        </div>

        <div style="background: var(--wiki-hover-bg); padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
            <h4 style="font-weight: 700; margin-bottom: 1.5rem;">📊 ROI Example: Software Sales Business</h4>
            <div style="font-family: monospace; font-size: 0.95rem; line-height: 2;">
                <strong>Software Vendor - Medium Size</strong><br><br>

                <span style="color: var(--wiki-danger);"><strong>Before Automated System:</strong></span><br>
                • Manual quotation creation: 30 min each × 200/mo = ฿75,000/mo cost<br>
                • License key generation: Manual process, 15 min each = ฿35,000/mo<br>
                • Renewal tracking: Spreadsheets, missed renewals 20% = ฿240,000/mo lost<br>
                • Customer support: 40% tickets for keys/downloads = ฿120,000/mo<br>
                <hr style="margin: 1rem 0; border-color: var(--wiki-border);">

                <span style="color: rgb(var(--primary-rgb));"><strong>After Software Sales System:</strong></span><br>
                • Auto quotation: 2 min each = ฿67,500/mo saved<br>
                • Auto license generation: Instant = ฿35,000/mo saved<br>
                • Auto renewals: 87% renewal rate = ฿240,000/mo recovered<br>
                • Self-service portal: 65% fewer support tickets = ฿78,000/mo saved<br>
                • Upsells via portal: +12% upgrade rate = ฿150,000/mo new revenue<br>
                <hr style="margin: 1rem 0; border-color: var(--wiki-border);">

                <strong>Investment:</strong> ฿9,990/month (Included in Platform)<br>
                <strong style="color: rgb(var(--secondary-rgb));">Net Benefit:</strong> ฿560,510/month<br>
                <strong style="color: rgb(var(--primary-rgb)); font-size: 1.2rem;">ROI: 5,611% 🚀</strong><br><br>

                <em style="color: var(--wiki-text-secondary);">Payback period: Less than 1 week!</em>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--primary-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💡 Revenue Optimization Tips</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Annual Prepay Discount:</strong> Offer 15-20% discount for annual payment upfront - improves cash flow and retention</li>
                <li><strong>Multi-Year Deals:</strong> Provide 25-30% discount for 2-3 year commitments - locks in customers and predictable revenue</li>
                <li><strong>Usage-Based Pricing:</strong> Consider hybrid models: base fee + usage charges for high-volume customers (API calls, storage, etc.)</li>
                <li><strong>Grandfather Pricing:</strong> Allow existing customers to keep old pricing when features increase - builds loyalty</li>
                <li><strong>Partner/Reseller Program:</strong> Offer 20-30% commission to partners who sell your software - expands reach without marketing cost</li>
                <li><strong>Educational/Non-Profit Discount:</strong> 50-70% discount builds goodwill and creates future enterprise customers</li>
            </ul>
        </div>
    </section>
</div>

