{{-- Hero Section with RGB Gradient --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 4rem 2rem; border-radius: 20px; margin-bottom: 3rem; box-shadow: 0 20px 60px rgba(var(--primary-rgb), 0.3);">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <div style="display: inline-block; background: rgba(255,255,255,0.2); padding: 0.5rem 1.5rem; border-radius: 50px; margin-bottom: 1.5rem; backdrop-filter: blur(10px);">
            <span style="color: white; font-weight: 600; font-size: 0.9rem;">🏬 Multi-Vendor Marketplace</span>
        </div>
        <h1 style="color: white; font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem; text-shadow: 0 2px 20px rgba(0,0,0,0.2);">
            ระบบ Multi-Vendor Marketplace
        </h1>
        <p style="color: rgba(255,255,255,0.95); font-size: 1.3rem; line-height: 1.8; margin-bottom: 2rem;">
            แพลตฟอร์มขายสินค้าแบบ Multi-Vendor ให้ผู้ขายหลายรายจัดการร้านค้าของตัวเอง<br>
            พร้อมระบบค่าคอมมิชชันอัตโนมัติและการจ่ายเงินที่โปร่งใส
        </p>
        <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">∞</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Unlimited Vendors</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">Auto</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Commission Split</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; backdrop-filter: blur(10px);">
                <div style="color: white; font-size: 2rem; font-weight: 700;">Real-time</div>
                <div style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Analytics Dashboard</div>
            </div>
        </div>
    </div>
</div>

{{-- Tab Navigation --}}
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; border-bottom: 2px solid var(--wiki-border); padding-bottom: 1rem;">
    <button class="wiki-tab active" data-tab="tab1"
            style="flex: 1; min-width: 200px; padding: 1rem 1.5rem; border: none; background: rgb(var(--primary-rgb)); color: white; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 1rem;"
            onmouseover="if(!this.classList.contains('active')) this.style.background='rgba(var(--primary-rgb), 0.1)'; if(!this.classList.contains('active')) this.style.color='rgb(var(--primary-rgb))'"
            onmouseout="if(!this.classList.contains('active')) this.style.background='var(--wiki-card-bg)'; if(!this.classList.contains('active')) this.style.color='var(--wiki-text)'">
        🏪 Vendor Store Management
    </button>
    <button class="wiki-tab" data-tab="tab2"
            style="flex: 1; min-width: 200px; padding: 1rem 1.5rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 1rem;"
            onmouseover="if(!this.classList.contains('active')) this.style.background='rgba(var(--secondary-rgb), 0.1)'; if(!this.classList.contains('active')) this.style.color='rgb(var(--secondary-rgb))'"
            onmouseout="if(!this.classList.contains('active')) this.style.background='var(--wiki-card-bg)'; if(!this.classList.contains('active')) this.style.color='var(--wiki-text)'">
        💰 Commission & Payouts
    </button>
    <button class="wiki-tab" data-tab="tab3"
            style="flex: 1; min-width: 200px; padding: 1rem 1.5rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 1rem;"
            onmouseover="if(!this.classList.contains('active')) this.style.background='rgba(var(--accent-rgb), 0.1)'; if(!this.classList.contains('active')) this.style.color='rgb(var(--accent-rgb))'"
            onmouseout="if(!this.classList.contains('active')) this.style.background='var(--wiki-card-bg)'; if(!this.classList.contains('active')) this.style.color='var(--wiki-text)'">
        📊 Vendor Analytics
    </button>
    <button class="wiki-tab" data-tab="tab4"
            style="flex: 1; min-width: 200px; padding: 1rem 1.5rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 1rem;"
            onmouseover="if(!this.classList.contains('active')) this.style.background='rgba(var(--primary-rgb), 0.1)'; if(!this.classList.contains('active')) this.style.color='rgb(var(--primary-rgb))'"
            onmouseout="if(!this.classList.contains('active')) this.style.background='var(--wiki-card-bg)'; if(!this.classList.contains('active')) this.style.color='var(--wiki-text)'">
        ⚙️ Marketplace Admin
    </button>
</div>

{{-- Tab 1: Vendor Store Management --}}
<div class="wiki-tab-content active" data-tab-content="tab1" style="display: block;">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>🏪</span> Vendor Store Setup & Management
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🎨</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Store Branding & Customization</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Custom store URL (yourdomain.com/vendor-name)</li>
                    <li>Logo & banner upload (max 5MB)</li>
                    <li>Color scheme customization (6 themes)</li>
                    <li>About page & vendor bio</li>
                    <li>Social media links integration</li>
                    <li>Contact information & business hours</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--secondary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📦</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Product Catalog Management</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Unlimited products per store</li>
                    <li>Bulk product upload (CSV/Excel)</li>
                    <li>Multiple product images (up to 10)</li>
                    <li>Variants (size, color, etc.)</li>
                    <li>Inventory tracking per SKU</li>
                    <li>Product approval workflow (optional)</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--accent-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📋</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Order Management</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Real-time order notifications</li>
                    <li>Order status workflow (6 stages)</li>
                    <li>Print shipping labels & invoices</li>
                    <li>Bulk status updates</li>
                    <li>Customer communication center</li>
                    <li>Return & refund processing</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🚚</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Shipping Configuration</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Multiple shipping methods</li>
                    <li>Per-product shipping rates</li>
                    <li>Free shipping thresholds</li>
                    <li>Shipping zone management (domestic/intl)</li>
                    <li>Courier integration (Kerry, Flash, J&T)</li>
                    <li>Tracking number management</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🏬 Vendor Dashboard Overview
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Sales Summary</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem;">
                        Today, This Week, This Month sales with growth percentage charts
                    </p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📦</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Pending Orders</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem;">
                        Orders waiting for processing, packing, shipping with action buttons
                    </p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚠️</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Low Stock Alerts</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem;">
                        Products with inventory below threshold (default: 10 units)
                    </p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⭐</div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Reviews & Ratings</h5>
                    <p style="color: var(--wiki-text-secondary); font-size: 0.9rem;">
                        Average rating, new reviews to respond to, feedback management
                    </p>
                </div>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--accent-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">💡 Vendor Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>High-Quality Photos:</strong> Use 1200x1200px images, white background, multiple angles. Good photos increase sales by 40%</li>
                <li><strong>Detailed Descriptions:</strong> Include specifications, materials, dimensions, care instructions. Answer common questions upfront</li>
                <li><strong>Competitive Pricing:</strong> Research competitor pricing, offer bundle deals, use psychological pricing (฿99 vs ฿100)</li>
                <li><strong>Fast Fulfillment:</strong> Ship within 24 hours, update tracking info promptly. Fast shipping = higher ratings = more visibility</li>
                <li><strong>Excellent Service:</strong> Respond to messages within 2 hours, handle returns gracefully, go above and beyond</li>
            </ul>
        </div>
    </section>
</div>

{{-- Tab 2: Commission & Payouts --}}
<div class="wiki-tab-content" data-tab-content="tab2" style="display: none;">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>💰</span> Commission Structure & Auto-Split
        </h2>

        <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead style="background: var(--wiki-card-bg);">
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Vendor Tier</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Monthly Sales Volume</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Platform Fee</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Vendor Keeps</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Benefits</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🥉 Bronze</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">< ฿50,000</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">15%</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">85%</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Basic support, Monthly payout</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🥈 Silver</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿50K - ฿200K</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">12%</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">88%</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Priority support, Weekly payout</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🥇 Gold</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿200K - ฿500K</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">10%</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">90%</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Dedicated manager, 3-day payout, Featured placement</td>
                </tr>
                <tr style="background: rgba(var(--primary-rgb), 0.05);">
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💎 Platinum</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">> ฿500K</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">8%</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">92%</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">VIP support, Next-day payout, Marketing support, Custom terms</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            💸 Automated Payout System
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">
                    🏦 Payout Methods
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li><strong>Bank Transfer:</strong> Direct deposit to Thai bank (free, 1-2 business days)</li>
                    <li><strong>PromptPay:</strong> Instant transfer via phone number (฿10 fee)</li>
                    <li><strong>PayPal:</strong> International vendors (3.4% + ฿11 fee)</li>
                    <li><strong>Wise (TransferWise):</strong> Cross-border (2.5% fee)</li>
                    <li><strong>Check:</strong> For >฿100K (฿50 fee, 5-7 days)</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">
                    📅 Payout Schedule
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li><strong>Bronze (Monthly):</strong> 1st of month for previous month sales</li>
                    <li><strong>Silver (Weekly):</strong> Every Friday for previous week</li>
                    <li><strong>Gold (3-day):</strong> Rolling 3-day after order completion</li>
                    <li><strong>Platinum (Next-day):</strong> Next business day after order delivered</li>
                    <li><strong>Hold Period:</strong> 14-day reserve for refunds/disputes</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border);">
                <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">
                    📊 Transaction Reports
                </h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li><strong>Sales Report:</strong> Daily/weekly/monthly breakdown by product</li>
                    <li><strong>Commission Report:</strong> Platform fees deducted, net earnings</li>
                    <li><strong>Payout History:</strong> All past payouts with transaction IDs</li>
                    <li><strong>Tax Documents:</strong> Annual 1099/tax forms for reporting</li>
                    <li><strong>CSV Export:</strong> Download for accounting software import</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            💵 Commission Calculation Example
        </h3>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
            <h4 style="font-weight: 700; margin-bottom: 1.5rem;">📊 Sample Transaction Breakdown</h4>
            <div style="font-family: monospace; font-size: 0.95rem; line-height: 2;">
                <strong>Vendor: ABC Fashion (Silver Tier - 12% commission)</strong><br><br>

                <span style="color: rgb(var(--primary-rgb));"><strong>Order Details:</strong></span><br>
                • Product Price: ฿1,500<br>
                • Shipping Fee: ฿50 (customer paid)<br>
                • Total Order Value: ฿1,550<br>
                <hr style="margin: 1rem 0; border-color: #ddd;">

                <span style="color: rgb(var(--secondary-rgb));"><strong>Deductions:</strong></span><br>
                • Platform Commission (12%): -฿180<br>
                • Payment Gateway Fee (2.9% + ฿11): -฿56<br>
                • Shipping Cost (actual): -฿42<br>
                <hr style="margin: 1rem 0; border-color: #ddd;">

                <strong style="color: rgb(var(--accent-rgb)); font-size: 1.1rem;">Vendor Receives: ฿1,272</strong><br>
                <em style="color: var(--wiki-text-secondary); font-size: 0.9rem;">Payout Date: Next Friday (Weekly cycle)</em>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.1) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--accent-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">💡 Maximizing Your Earnings</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Volume Discounts:</strong> Higher sales = Lower commission rate. Aim for Gold tier (10%) by doing ฿200K+/month</li>
                <li><strong>Bundle Products:</strong> Increase average order value with product bundles, upsells, and cross-sells</li>
                <li><strong>Reduce Returns:</strong> Accurate descriptions and sizing = fewer returns = more money in your pocket</li>
                <li><strong>Optimize Shipping:</strong> Negotiate better rates with couriers, use lightweight packaging to reduce costs</li>
                <li><strong>Seasonal Promotions:</strong> Participate in platform-wide sales events for increased visibility and volume</li>
            </ul>
        </div>
    </section>
</div>

{{-- Tab 3: Vendor Analytics --}}
<div class="wiki-tab-content" data-tab-content="tab3" style="display: none;">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📊</span> Performance Analytics & Insights
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">💰</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Total Revenue (This Month)</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb));">฿187,450</div>
                <div style="font-size: 0.85rem; color: rgb(var(--secondary-rgb)); margin-top: 0.5rem;">▲ 23% vs last month</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--secondary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📦</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Orders Processed</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb));">342</div>
                <div style="font-size: 0.85rem; color: rgb(var(--primary-rgb)); margin-top: 0.5rem;">▲ 18 new today</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1) 0%, rgba(var(--accent-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--accent-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⭐</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Average Rating</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb));">4.7/5</div>
                <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Based on 1,247 reviews</div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%); padding: 2rem; border-radius: 16px; border-left: 4px solid rgb(var(--primary-rgb));">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔄</div>
                <h4 style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Conversion Rate</h4>
                <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb));">3.2%</div>
                <div style="font-size: 0.85rem; color: rgb(var(--secondary-rgb)); margin-top: 0.5rem;">▲ 0.4% improvement</div>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            📈 Top Performing Products
        </h3>

        <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead style="background: var(--wiki-card-bg);">
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Product Name</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Units Sold</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Revenue</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Avg Rating</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Stock Level</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Premium Cotton T-Shirt (White)</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">147</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿44,100</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">4.8 ⭐</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="color: #28a745;">287</span></td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Denim Jeans (Slim Fit)</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">89</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿53,400</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">4.6 ⭐</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="color: #ffc107;">24</span></td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Casual Sneakers (Canvas)</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">106</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿63,600</strong></td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">4.9 ⭐</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><span style="color: #dc3545;">8</span></td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            👥 Customer Insights
        </h3>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem;">
                <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">👤 Customer Demographics</h5>
                    <ul style="color: var(--wiki-text-secondary); line-height: 1.8;">
                        <li><strong>Gender:</strong> 68% Female, 32% Male</li>
                        <li><strong>Age:</strong> 25-34 (45%), 18-24 (28%)</li>
                        <li><strong>Location:</strong> Bangkok (52%), Chiang Mai (12%)</li>
                    </ul>
                </div>
                <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">💰 Spending Patterns</h5>
                    <ul style="color: var(--wiki-text-secondary); line-height: 1.8;">
                        <li><strong>Avg Order Value:</strong> ฿548</li>
                        <li><strong>Repeat Purchase Rate:</strong> 34%</li>
                        <li><strong>Peak Hours:</strong> 7-9 PM weekdays</li>
                    </ul>
                </div>
                <div>
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">📱 Traffic Sources</h5>
                    <ul style="color: var(--wiki-text-secondary); line-height: 1.8;">
                        <li><strong>Mobile:</strong> 72%</li>
                        <li><strong>Desktop:</strong> 28%</li>
                        <li><strong>Referral:</strong> Facebook (45%), Google (30%)</li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--primary-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💡 Data-Driven Optimization Tips</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Stock Management:</strong> Products in red (<10 units) need restocking. Use sales velocity to predict reorder points</li>
                <li><strong>Pricing Strategy:</strong> Monitor competitors' pricing. Products with <2% conversion may be overpriced</li>
                <li><strong>Review Management:</strong> Respond to all reviews within 24 hours. 4.5+ star rating increases sales by 20%</li>
                <li><strong>Product Photography:</strong> A/B test different images. Products with lifestyle photos convert 35% better</li>
                <li><strong>Seasonal Planning:</strong> Identify sales patterns (holidays, pay days). Stock up 2 weeks before peak periods</li>
            </ul>
        </div>
    </section>
</div>

{{-- Tab 4: Marketplace Admin --}}
<div class="wiki-tab-content" data-tab-content="tab4" style="display: none;">
    <section class="wiki-section" style="margin-bottom: 3rem;">
        <h2 style="color: rgb(var(--primary-rgb)); font-size: 2rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>⚙️</span> Marketplace Administration & Control
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">✅</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Vendor Approval Workflow</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Application review (business docs, tax ID)</li>
                    <li>Background check & verification</li>
                    <li>Manual approval or auto-approve (configurable)</li>
                    <li>Onboarding email with setup guide</li>
                    <li>Probation period (first 90 days)</li>
                    <li>Performance monitoring & quality checks</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--secondary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--secondary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🛡️</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Quality Control & Moderation</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Product listing review before publish</li>
                    <li>Prohibited items detection (auto-flag)</li>
                    <li>Image quality checks (min 800x800px)</li>
                    <li>Description completeness score</li>
                    <li>Trademark/Copyright violation detection</li>
                    <li>Manual moderation queue for flagged items</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--accent-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--accent-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">⚖️</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Dispute Resolution System</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Customer complaints management</li>
                    <li>Order disputes (not received, wrong item)</li>
                    <li>Refund requests with evidence upload</li>
                    <li>Mediation between vendor & customer</li>
                    <li>Escalation to admin for final decision</li>
                    <li>Automated resolution for clear-cut cases</li>
                </ul>
            </div>

            <div style="background: var(--wiki-card-bg); padding: 2rem; border-radius: 16px; border: 2px solid var(--wiki-border); transition: all 0.3s ease;"
                 onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(var(--primary-rgb), 0.15)'"
                 onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">⚠️</div>
                <h4 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; color: var(--wiki-text);">Vendor Performance Monitoring</h4>
                <ul style="color: var(--wiki-text-secondary); line-height: 1.8; margin-left: 1.2rem;">
                    <li>Fulfillment time tracking (target <24h)</li>
                    <li>Order defect rate (target <1%)</li>
                    <li>Customer service response time</li>
                    <li>Return/refund rate by vendor</li>
                    <li>Warning system (3 strikes)</li>
                    <li>Suspension/termination procedures</li>
                </ul>
            </div>
        </div>

        <h3 style="color: rgb(var(--secondary-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            🎯 Platform Settings & Configuration
        </h3>

        <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead style="background: var(--wiki-card-bg);">
                <tr>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Setting Category</th>
                    <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Configurable Options</th>
                    <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Default Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💰 Commission Rates</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Per-tier rates, Category-specific rates, Individual vendor custom rates</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">15% Bronze, 12% Silver, 10% Gold, 8% Platinum</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>✅ Approval Mode</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Auto-approve, Manual review, Hybrid (auto for verified)</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Manual Review</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📦 Product Limits</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Max products per vendor, Max images per product, Max variants</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Unlimited, 10 images, 50 variants</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💸 Payout Thresholds</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Minimum payout amount, Hold period days, Processing fee</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿500 minimum, 14-day hold, Free bank transfer</td>
                </tr>
                <tr>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⭐ Rating System</strong></td>
                    <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Enable/disable reviews, Verified purchase only, Moderation</td>
                    <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Enabled, Verified only, Auto-publish (no profanity)</td>
                </tr>
            </tbody>
        </table>

        <h3 style="color: rgb(var(--accent-rgb)); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">
            📊 Marketplace-Wide Analytics
        </h3>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">847</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">Active Vendors</div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--secondary-rgb));">฿12.4M</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">GMV This Month</div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--accent-rgb));">18,542</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">Total Orders</div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">4.6/5</div>
                    <div style="font-size: 0.9rem; color: var(--wiki-text-secondary);">Platform Avg Rating</div>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--secondary-rgb), 0.1) 100%); padding: 2rem; border-radius: 12px; border-left: 4px solid rgb(var(--primary-rgb));">
            <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">💡 Marketplace Management Best Practices</h4>
            <ul style="line-height: 2; color: var(--wiki-text);">
                <li><strong>Vendor Vetting:</strong> Verify business licenses, tax registrations. Bad vendors hurt platform reputation</li>
                <li><strong>Clear Policies:</strong> Document commission rates, prohibited items, dispute procedures. Transparency builds trust</li>
                <li><strong>Fair Mediation:</strong> Listen to both sides in disputes. Data-driven decisions (tracking, photos, chat logs)</li>
                <li><strong>Performance Incentives:</strong> Reward top vendors with lower commissions, featured placement, marketing support</li>
                <li><strong>Regular Communication:</strong> Monthly vendor newsletter with tips, policy updates, platform improvements</li>
                <li><strong>Competitive Commission:</strong> Research competitor rates (Lazada 2-4%, Shopee 3-5%). Balance profitability with attracting quality vendors</li>
            </ul>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.wiki-tab');
    const tabContents = document.querySelectorAll('.wiki-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'var(--wiki-card-bg)';
                btn.style.color = 'var(--wiki-text)';
            });
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });

            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            button.style.background = 'rgb(var(--primary-rgb))';
            button.style.color = 'white';

            const targetContent = document.querySelector(`[data-tab-content="${targetTab}"]`);
            if (targetContent) {
                targetContent.classList.add('active');
                targetContent.style.display = 'block';
            }
        });
    });
});
</script>
