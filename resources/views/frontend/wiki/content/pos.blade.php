<div class="wiki-content-section">
    {{-- Hero Section with RGB Gradient --}}
    <div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 3rem 2rem; border-radius: 20px; margin-bottom: 3rem; color: white; text-align: center;">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">🏪 Point of Sale System</h1>
        <p style="font-size: 1.25rem; opacity: 0.95; max-width: 800px; margin: 0 auto;">ระบบขายหน้าร้านที่ทันสมัย รองรับ Multi-device, Offline Mode และเชื่อมต่อระบบบัญชี</p>
    </div>

    {{-- Tab Navigation --}}
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; border-bottom: 2px solid var(--wiki-border);">
        <button class="wiki-tab active" data-tab="terminal" style="padding: 1rem 2rem; border: none; background: rgb(var(--primary-rgb)); color: white; cursor: pointer; font-weight: 600; border-radius: 8px 8px 0 0; transition: all 0.3s;">
            💻 POS Terminal & Hardware
        </button>
        <button class="wiki-tab" data-tab="sales" style="padding: 1rem 2rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); cursor: pointer; font-weight: 600; border-radius: 8px 8px 0 0; transition: all 0.3s;">
            🛒 Sales & Inventory
        </button>
        <button class="wiki-tab" data-tab="reports" style="padding: 1rem 2rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); cursor: pointer; font-weight: 600; border-radius: 8px 8px 0 0; transition: all 0.3s;">
            📊 Multi-location & Reports
        </button>
        <button class="wiki-tab" data-tab="integration" style="padding: 1rem 2rem; border: none; background: var(--wiki-card-bg); color: var(--wiki-text); cursor: pointer; font-weight: 600; border-radius: 8px 8px 0 0; transition: all 0.3s;">
            🔗 Integration & Features
        </button>
    </div>

    {{-- Tab 1: POS Terminal & Hardware --}}
    <div class="wiki-tab-content active" data-tab-content="terminal" style="display: block;">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">💻 POS Terminal & Hardware</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Modern POS Hardware</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">อุปกรณ์ POS ครบวงจร รองรับ Touch Screen, Barcode Scanner, Receipt Printer และ Cash Drawer พร้อมเชื่อมต่อ Payment Terminal</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🖥️ POS Hardware Components</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🖥️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">POS Terminal</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 15.6" Touch Screen Display</li>
                        <li>✅ Intel i5/i7 Processor</li>
                        <li>✅ 8GB RAM / 256GB SSD</li>
                        <li>✅ Windows 10/11 or Android</li>
                        <li>✅ Multiple USB Ports</li>
                        <li>✅ WiFi & Ethernet</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📷</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Barcode Scanner</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 1D & 2D Barcode Support</li>
                        <li>✅ QR Code Reading</li>
                        <li>✅ USB/Wireless Connection</li>
                        <li>✅ Auto-scan Mode</li>
                        <li>✅ Fast Scanning (200 scans/sec)</li>
                        <li>✅ Durable Design (IP54)</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🖨️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Receipt Printer</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Thermal Printer 80mm</li>
                        <li>✅ High-speed Printing (250mm/sec)</li>
                        <li>✅ Auto-cut Function</li>
                        <li>✅ Logo & Barcode Printing</li>
                        <li>✅ USB/Ethernet/Bluetooth</li>
                        <li>✅ Long-life (100km paper)</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💵</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Cash Drawer</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Heavy-duty Metal Construction</li>
                        <li>✅ 5 Bill + 8 Coin Compartments</li>
                        <li>✅ Key & Electronic Lock</li>
                        <li>✅ Auto-open via RJ11/USB</li>
                        <li>✅ Manual Emergency Open</li>
                        <li>✅ Cable Connection to Printer</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💳</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Payment Terminal</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Chip & PIN Card Reader</li>
                        <li>✅ Contactless NFC Payment</li>
                        <li>✅ QR Code Payment</li>
                        <li>✅ EMV Certified</li>
                        <li>✅ PCI DSS Compliant</li>
                        <li>✅ Multi-bank Support</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📺</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Customer Display</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 7-10" LCD Display</li>
                        <li>✅ Show Item & Price</li>
                        <li>✅ Total Amount Display</li>
                        <li>✅ Advertising Banner Support</li>
                        <li>✅ Adjustable Angle</li>
                        <li>✅ USB Plug & Play</li>
                    </ul>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Best Practices for POS Hardware</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Regular Maintenance:</strong> ทำความสะอาดเครื่องสแกน, เครื่องพิมพ์ ทุกสัปดาห์</li>
                    <li><strong>Backup Power:</strong> ติดตั้ง UPS ป้องกันไฟดับกะทันหัน</li>
                    <li><strong>Software Updates:</strong> อัพเดทซอฟต์แวร์และ Driver เป็นประจำ</li>
                    <li><strong>Security:</strong> ใช้ Password Protection และ Physical Lock</li>
                    <li><strong>Spare Parts:</strong> เตรียมอะไหล่สำรอง (กระดาษใบเสร็จ, หมึก, สาย)</li>
                </ul>
            </div>
        </section>
    </div>

    {{-- Tab 2: Sales & Inventory --}}
    <div class="wiki-tab-content" data-tab-content="sales" style="display: none;">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">🛒 Sales & Inventory Management</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Real-time Sales & Stock Control</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">ระบบขายและสต็อกสินค้าแบบเรียลไทม์ รองรับ Multi-store, Auto-reorder และ Expiry Date Management</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">📦 Product Catalog Management</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Product Information</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>🏷️ SKU & Barcode Management</li>
                        <li>📸 Product Images (Multiple)</li>
                        <li>💰 Pricing & Cost Control</li>
                        <li>📏 Variants (Size, Color, etc.)</li>
                        <li>🏭 Supplier Information</li>
                        <li>📊 Category & Tags</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">Stock Control</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📦 Real-time Stock Level</li>
                        <li>⚠️ Low Stock Alerts</li>
                        <li>🔄 Auto-reorder Points</li>
                        <li>📅 Expiry Date Tracking</li>
                        <li>📍 Multi-location Stock</li>
                        <li>🔢 Batch & Serial Numbers</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💸</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">Pricing & Promotions</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>💰 Regular & Sale Price</li>
                        <li>🎯 Volume Discounts</li>
                        <li>🎁 Buy X Get Y Promotions</li>
                        <li>🏷️ Bundle Pricing</li>
                        <li>⏰ Time-based Promotions</li>
                        <li>👥 Member-only Prices</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">💳 Sales Transaction Flow</h3>
            <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                    <div style="text-align: center; flex: 1; min-width: 150px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--primary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">1</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">📷 Scan Items</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">สแกนบาร์โค้ด/ค้นหา</div>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 150px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--secondary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">2</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">🛒 Add to Cart</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ปรับจำนวน/ส่วนลด</div>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 150px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--accent-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">3</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">💳 Payment</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เงินสด/บัตร/QR</div>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 150px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--primary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">4</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">🧾 Receipt</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">พิมพ์/ส่งอีเมล</div>
                    </div>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">💰 Payment Methods Support</h3>
            <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: var(--wiki-card-bg);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Payment Method</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Processing Time</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Fee</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Features</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💵 Cash</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Instant</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">0%</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Auto-calculate Change, Cash Drawer Integration</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💳 Credit/Debit Card</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">3-5 sec</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1.5-2.5%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Chip & PIN, Contactless, EMV Certified</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📱 QR Payment</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">2-3 sec</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">0.5-1.5%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">PromptPay, TrueMoney, ShopeePay, LINE Pay</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>👛 E-Wallet</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">2-4 sec</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1-2%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Mobile App, Points Redemption, Loyalty</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🎁 Gift Card/Voucher</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Instant</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">0%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Digital & Physical Cards, Balance Check</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💎 Store Credit/Points</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Instant</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">0%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Customer Loyalty Program, Reward Points</td>
                    </tr>
                </tbody>
            </table>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Inventory Best Practices</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Cycle Counting:</strong> ตรวจนับสต็อกประจำวัน/สัปดาห์ ไม่รอปิดงวด</li>
                    <li><strong>FIFO Method:</strong> ใช้วิธีสินค้าเข้าก่อนออกก่อน (First In First Out)</li>
                    <li><strong>ABC Analysis:</strong> จัดกลุ่มสินค้าตามมูลค่าและความสำคัญ</li>
                    <li><strong>Expiry Alerts:</strong> ตั้งเตือนสินค้าใกล้หมดอายุล่วงหน้า 30 วัน</li>
                    <li><strong>Dead Stock:</strong> ทบทวนสินค้าที่ไม่มีการขาย 90 วันขึ้นไป</li>
                </ul>
            </div>
        </section>
    </div>

    {{-- Tab 3: Multi-location & Reports --}}
    <div class="wiki-tab-content" data-tab-content="reports" style="display: none;">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">📊 Multi-location & Sales Reports</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Centralized Management for Multiple Stores</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">บริหารจัดการหลายสาขาพร้อมกัน ข้อมูลแบบเรียลไทม์ รายงานแบบรวมและแยกตามสาขา</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🏪 Multi-location Features</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏢</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Branch Management</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Unlimited Branches</li>
                        <li>✅ Individual Store Settings</li>
                        <li>✅ Separate Stock per Location</li>
                        <li>✅ Branch-specific Pricing</li>
                        <li>✅ Opening Hours Management</li>
                        <li>✅ Store Profile & Address</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔄</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Stock Transfer</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Inter-branch Transfer</li>
                        <li>✅ Transfer Approval Workflow</li>
                        <li>✅ Real-time Stock Updates</li>
                        <li>✅ Transfer History Tracking</li>
                        <li>✅ Shipping Label Printing</li>
                        <li>✅ Received Confirmation</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">☁️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Cloud Sync</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Real-time Data Sync</li>
                        <li>✅ Automatic Backup</li>
                        <li>✅ Access from Anywhere</li>
                        <li>✅ Multi-device Support</li>
                        <li>✅ Offline Mode</li>
                        <li>✅ Auto-sync when Online</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Staff Management</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Role-based Access Control</li>
                        <li>✅ Staff Assignment per Branch</li>
                        <li>✅ Shift Management</li>
                        <li>✅ Performance Tracking</li>
                        <li>✅ Commission Calculation</li>
                        <li>✅ Activity Logs</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📈</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Centralized Dashboard</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ All Branches Overview</li>
                        <li>✅ Comparative Analytics</li>
                        <li>✅ Total Sales Summary</li>
                        <li>✅ Best/Worst Performers</li>
                        <li>✅ Real-time Alerts</li>
                        <li>✅ Executive Reports</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💼</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Franchise Management</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Franchise Partner Portal</li>
                        <li>✅ Royalty Fee Tracking</li>
                        <li>✅ Revenue Sharing</li>
                        <li>✅ Brand Compliance</li>
                        <li>✅ Training Materials</li>
                        <li>✅ Support Ticketing</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">📊 Sales Reports & Analytics</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Today's Sales</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">฿48,350</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">↑ 12% vs Yesterday</p>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Transactions</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb)); margin: 0;">156</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Avg: ฿310 per sale</p>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Items Sold</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb)); margin: 0;">427</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Avg: 2.7 items/sale</p>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Gross Profit</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">35%</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">฿16,923 margin</p>
                </div>
            </div>

            <h4 style="font-weight: 700; margin: 2rem 0 1rem;">📈 Available Reports</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">📊 Daily Sales Report</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">ยอดขายรายวัน แยกตามสาขา, พนักงาน, ช่วงเวลา</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🏆 Best Sellers</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">สินค้าขายดี Top 10, 20, 50 รายการ</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">💰 Payment Methods</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">สัดส่วนการชำระเงิน Cash vs Card vs QR</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">👥 Staff Performance</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">ยอดขายแยกตามพนักงาน คำนวณ Commission</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">📦 Stock Movement</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">สินค้าเข้า-ออก, Stock Transfer, Adjustments</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">📉 Slow Moving</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">สินค้าที่ไม่มีการขาย Dead Stock Analysis</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">💳 Discount Report</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">ส่วนลดที่ให้ไป, โปรโมชั่นที่ใช้ไป</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">📅 Period Comparison</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">เปรียบเทียบยอดขาย เดือนนี้ vs เดือนที่แล้ว</p>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Reporting Best Practices</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Daily Review:</strong> ดูรายงานยอดขายทุกวันเพื่อติดตามผลประกอบการ</li>
                    <li><strong>Weekly Analysis:</strong> วิเคราะห์ Best Sellers และ Slow Moving รายสัปดาห์</li>
                    <li><strong>Monthly Close:</strong> ปิดยอดรายเดือน reconcile กับระบบบัญชี</li>
                    <li><strong>Staff Feedback:</strong> ใช้รายงานเป็นข้อมูลในการประเมินพนักงาน</li>
                    <li><strong>Data Backup:</strong> Backup รายงานและข้อมูลประจำวัน</li>
                </ul>
            </div>
        </section>
    </div>

    {{-- Tab 4: Integration & Features --}}
    <div class="wiki-tab-content" data-tab-content="integration" style="display: none;">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">🔗 Integration & Advanced Features</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Seamless Integration with Your Business Systems</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">เชื่อมต่อกับระบบบัญชี, E-Commerce, CRM และระบบสมาชิก เพื่อการทำงานที่ลื่นไหลไร้รอยต่อ</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🔌 System Integrations</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📚</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Accounting System</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Auto Journal Entries</li>
                        <li>✅ Sales Invoice Generation</li>
                        <li>✅ VAT Calculation</li>
                        <li>✅ Daily Sales Reconciliation</li>
                        <li>✅ Chart of Accounts Mapping</li>
                        <li>✅ Export to Excel/CSV</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🛍️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">E-Commerce Platform</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Inventory Sync (Real-time)</li>
                        <li>✅ Order Import/Export</li>
                        <li>✅ Price Updates</li>
                        <li>✅ Customer Data Sync</li>
                        <li>✅ Fulfillment Status</li>
                        <li>✅ Multi-marketplace Support</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">CRM & Loyalty</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Customer Profile Management</li>
                        <li>✅ Purchase History Tracking</li>
                        <li>✅ Points Accumulation</li>
                        <li>✅ Membership Tiers</li>
                        <li>✅ Birthday Promotions</li>
                        <li>✅ Email/SMS Marketing</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Warehouse Management</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Stock Transfer Orders</li>
                        <li>✅ Purchase Order Creation</li>
                        <li>✅ Goods Receipt</li>
                        <li>✅ Bin Location Management</li>
                        <li>✅ Stock Adjustment</li>
                        <li>✅ Reorder Point Alerts</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📱</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Mobile App</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ iOS & Android Support</li>
                        <li>✅ Bluetooth Printer/Scanner</li>
                        <li>✅ Offline Mode</li>
                        <li>✅ Mobile Payment</li>
                        <li>✅ Cloud Sync</li>
                        <li>✅ Remote Management</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">BI & Analytics</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Power BI Integration</li>
                        <li>✅ Google Analytics</li>
                        <li>✅ Custom Dashboards</li>
                        <li>✅ KPI Tracking</li>
                        <li>✅ Predictive Analytics</li>
                        <li>✅ Data Export API</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🎯 Advanced POS Features</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📴</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Offline Mode</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">ทำงานต่อได้แม้อินเทอร์เน็ตขาด</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Local Data Storage</li>
                        <li>✅ Continue Selling Offline</li>
                        <li>✅ Auto-sync when Back Online</li>
                        <li>✅ Conflict Resolution</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎁</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">Gift Card System</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">บัตรของขวัญและ Store Credit</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Physical & Digital Cards</li>
                        <li>✅ Custom Amount</li>
                        <li>✅ Balance Check</li>
                        <li>✅ Expiry Date Management</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">↩️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">Return & Exchange</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">จัดการคืนสินค้าและเปลี่ยนของ</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Quick Receipt Lookup</li>
                        <li>✅ Partial Returns</li>
                        <li>✅ Store Credit Refund</li>
                        <li>✅ Reason Code Tracking</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Security Features</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">ระบบความปลอดภัยที่แข็งแกร่ง</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ User Access Control</li>
                        <li>✅ Transaction Audit Log</li>
                        <li>✅ Cash Management</li>
                        <li>✅ Void/Cancel Authorization</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎯</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">Customer Display</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">จอแสดงผลสำหรับลูกค้า</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Item Name & Price</li>
                        <li>✅ Total Amount</li>
                        <li>✅ Promotional Messages</li>
                        <li>✅ Logo & Branding</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📧</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">Digital Receipts</h4>
                    <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">ใบเสร็จอิเล็กทรอนิกส์</p>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Email Receipt</li>
                        <li>✅ SMS Receipt</li>
                        <li>✅ QR Code Download</li>
                        <li>✅ Eco-friendly Option</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">💰 Investment & ROI</h3>
            <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1.5rem;">💎 POS System Pricing Packages</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem; text-align: center;">
                        <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Basic</h5>
                        <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 1rem 0;">฿2,990<span style="font-size: 1rem; font-weight: 400;">/mo</span></div>
                        <ul style="list-style: none; padding: 0; text-align: left; font-size: 0.9rem; line-height: 1.8;">
                            <li>✅ 1 Branch</li>
                            <li>✅ 2 POS Terminals</li>
                            <li>✅ 1,000 Products</li>
                            <li>✅ Basic Reports</li>
                        </ul>
                    </div>
                    <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; text-align: center;">
                        <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Professional</h5>
                        <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb)); margin: 1rem 0;">฿5,990<span style="font-size: 1rem; font-weight: 400;">/mo</span></div>
                        <ul style="list-style: none; padding: 0; text-align: left; font-size: 0.9rem; line-height: 1.8;">
                            <li>✅ 5 Branches</li>
                            <li>✅ 10 POS Terminals</li>
                            <li>✅ Unlimited Products</li>
                            <li>✅ Advanced Analytics</li>
                        </ul>
                    </div>
                    <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 12px; padding: 1.5rem; text-align: center;">
                        <h5 style="font-weight: 700; margin-bottom: 0.5rem;">Enterprise</h5>
                        <div style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb)); margin: 1rem 0;">Custom</div>
                        <ul style="list-style: none; padding: 0; text-align: left; font-size: 0.9rem; line-height: 1.8;">
                            <li>✅ Unlimited Branches</li>
                            <li>✅ Unlimited Terminals</li>
                            <li>✅ Custom Features</li>
                            <li>✅ Dedicated Support</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1.5rem;">📊 ROI Calculation Example</h4>
                <div style="font-family: monospace; font-size: 0.95rem; line-height: 2;">
                    <strong>ร้านค้าปลีก ขนาดกลาง (2 สาขา)</strong><br><br>

                    <span style="color: #4CAF50;"><strong>Before POS System:</strong></span><br>
                    • Manual Sales Recording: 30 min/day × 2 staff = ฿15,000/mo<br>
                    • Stock Counting Errors: ~฿8,000/mo loss<br>
                    • Delayed Reporting: 2 days lag in decision making<br>
                    <hr style="margin: 1rem 0; border-color: #ddd;">

                    <span style="color: rgb(var(--primary-rgb));"><strong>After POS System:</strong></span><br>
                    • Time Saved: 4 hours/day = ฿15,000/mo<br>
                    • Inventory Accuracy: 98% = ฿7,000/mo saved<br>
                    • Better Decisions: +10% revenue = ฿25,000/mo<br>
                    • Customer Insights: +5% repeat customers = ฿10,000/mo<br>
                    <hr style="margin: 1rem 0; border-color: #ddd;">

                    <strong>Investment:</strong> ฿5,990/month (Professional Plan)<br>
                    <strong style="color: rgb(var(--secondary-rgb));">Net Benefit:</strong> ฿51,010/month<br>
                    <strong style="color: rgb(var(--primary-rgb)); font-size: 1.2rem;">ROI: 851% 🚀</strong>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Best Practices for POS Success</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Staff Training:</strong> อบรมพนักงานอย่างละเอียดก่อนเปิดใช้งาน</li>
                    <li><strong>Data Migration:</strong> Import ข้อมูลสินค้าและลูกค้าที่มีอยู่อย่างถูกต้อง</li>
                    <li><strong>Regular Updates:</strong> อัพเดทซอฟต์แวร์และราคาสินค้าเป็นประจำ</li>
                    <li><strong>Daily Backup:</strong> Backup ข้อมูลทุกวันเพื่อความปลอดภัย</li>
                    <li><strong>Monitor Performance:</strong> ติดตามรายงานและ KPIs อย่างสม่ำเสมอ</li>
                    <li><strong>Customer Feedback:</strong> รับฟีดแบ็คจากพนักงานและลูกค้าเพื่อปรับปรุง</li>
                </ul>
            </div>
        </section>
    </div>
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
