<div class="wiki-content-section">
    {{-- Hero Section with RGB Gradient --}}
    <div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 3rem 2rem; border-radius: 20px; margin-bottom: 3rem; color: white; text-align: center;">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: white;">🏨 Hotel Booking System</h1>
        <p style="font-size: 1.25rem; opacity: 0.95; max-width: 800px; margin: 0 auto;">ระบบจองโรงแรมครบวงจร Channel Manager & Property Management ระดับมืออาชีพ</p>
    </section>

{{-- 1: Room Management --}}
    <section id="room" class="wiki-section">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">🛏️ Room Management & Inventory</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Smart Room Inventory System</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">จัดการห้องพัก Dynamic Pricing และ Real-time Availability ทุก Channel</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🏨 Room Types</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🛏️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Standard Room</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 25-30 ตร.ม.</li>
                        <li>✅ เตียงคิงไซส์/ทวิน</li>
                        <li>✅ ห้องน้ำส่วนตัว</li>
                        <li>✅ WiFi ฟรี</li>
                        <li>💰 ฿1,200-1,800/คืน</li>
                        <li>📊 15 ห้อง พร้อมให้บริการ</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🌟</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Deluxe Room</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 35-40 ตร.ม.</li>
                        <li>✅ เตียงคิงไซส์</li>
                        <li>✅ ระเบียงส่วนตัว</li>
                        <li>✅ มินิบาร์</li>
                        <li>💰 ฿2,500-3,500/คืน</li>
                        <li>📊 10 ห้อง พร้อมให้บริการ</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👑</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Suite</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ 60-80 ตร.ม.</li>
                        <li>✅ ห้องนอนแยก + ห้องนั่งเล่น</li>
                        <li>✅ อ่างอาบน้ำจากุซซี่</li>
                        <li>✅ Butler Service</li>
                        <li>💰 ฿5,000-8,000/คืน</li>
                        <li>📊 5 ห้อง พร้อมให้บริการ</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">💰 Dynamic Pricing Engine</h3>
            <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: var(--wiki-card-bg);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Season / Event</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Occupancy Target</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Price Multiplier</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Strategy</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔥 Peak Season</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">90-100%</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">×1.8-2.0</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Maximum revenue, Min stay 2-3 nights</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>☀️ High Season</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">80-90%</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">×1.3-1.5</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Optimize revenue per room</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🌤️ Shoulder Season</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">60-70%</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">×1.0-1.2</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Standard rate, Flexible cancellation</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🌧️ Low Season</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">40-50%</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">×0.7-0.9</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Promotional rates, Long-stay discounts</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🎉 Special Events</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">100%</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">×2.5-3.0</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Concert/Festival periods, Min 3 nights</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">📅 Availability Calendar</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">Real-time Sync</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Update All Channels Instantly</li>
                        <li>✅ Prevent Overbooking</li>
                        <li>✅ Room Allocation Control</li>
                        <li>✅ Block Dates for Maintenance</li>
                        <li>✅ Special Rates & Packages</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">Restrictions & Rules</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Minimum Stay (1-7 nights)</li>
                        <li>✅ Maximum Stay Limit</li>
                        <li>✅ Closed to Arrival/Departure</li>
                        <li>✅ Stop-sell Dates</li>
                        <li>✅ Release Periods</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">Occupancy Analytics</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📊 Current: 78% Occupied</li>
                        <li>📈 This Week: 82% Forecast</li>
                        <li>📅 Next Month: 65% Booked</li>
                        <li>💰 ADR: ฿2,350</li>
                        <li>🎯 RevPAR: ฿1,833</li>
                    </ul>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Room Management Best Practices</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Update Instantly:</strong> ปรับราคาและ Availability ทุกช่องทางทันที</li>
                    <li><strong>Strategic Overbooking:</strong> Overbook 5-10% สำหรับ No-shows โดยมี Backup Plan</li>
                    <li><strong>Upsell Opportunities:</strong> เสนอห้องระดับสูงกว่า ณ เวลาเช็คอิน</li>
                    <li><strong>Maintenance Schedule:</strong> วางแผนซ่อมบำรุงใน Low Season</li>
                    <li><strong>Photo Quality:</strong> อัพเดทรูปภาพคุณภาพสูงทุก 6 เดือน</li>
                </ul>
            </div>
        </section>
    </section>

{{-- 2: Booking & Reservation --}}
    <section id="booking" class="wiki-section">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">📅 Booking & Reservation System</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Seamless Booking Experience</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">ระบบจองแบบ Real-time พร้อม Payment Gateway และ Email Confirmation อัตโนมัติ</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🔄 Booking Flow</h3>
            <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                    <div style="text-align: center; flex: 1; min-width: 140px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--primary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">1</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">🔍 Search</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">วันที่ + จำนวนคน</div>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 140px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--secondary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">2</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">🛏️ Select Room</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">เลือกห้องพัก</div>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 140px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--accent-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">3</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">📝 Guest Info</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">กรอกข้อมูล</div>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 140px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--primary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">4</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">💳 Payment</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ชำระเงิน</div>
                    </div>
                    <div style="font-size: 1.5rem; color: var(--wiki-text-muted);">→</div>
                    <div style="text-align: center; flex: 1; min-width: 140px;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgb(var(--secondary-rgb)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 0.5rem;">5</div>
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">✅ Confirm</div>
                        <div style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ส่งอีเมล</div>
                    </div>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">💳 Payment Options</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💳</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Credit/Debit Card</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Visa / MasterCard / AMEX</li>
                        <li>✅ 3D Secure Authentication</li>
                        <li>✅ Instant Confirmation</li>
                        <li>✅ Pre-authorization Hold</li>
                        <li>💰 ค่าธรรมเนียม 2.5%</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏦</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Bank Transfer</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ QR PromptPay</li>
                        <li>✅ Bank Transfer</li>
                        <li>✅ Upload Slip</li>
                        <li>✅ Manual Verification</li>
                        <li>💰 ไม่มีค่าธรรมเนียม</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏨</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Pay at Hotel</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>✅ Reserve Now, Pay Later</li>
                        <li>✅ Credit Card Guarantee</li>
                        <li>✅ Cash/Card at Check-in</li>
                        <li>✅ Flexible for Guests</li>
                        <li>💰 ไม่มีค่าธรรมเนียม</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">📋 Cancellation Policies</h3>
            <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: var(--wiki-card-bg);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Policy Type</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Free Cancellation</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Penalty</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Use Case</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>✅ Flexible</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">ล่วงหน้า 24 ชม.</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">1 คืน</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Low Season, Standard Rates</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>⚖️ Moderate</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">ล่วงหน้า 7 วัน</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">30%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">High Season, Promotional Rates</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🔒 Strict</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">ล่วงหน้า 14 วัน</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">50%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Peak Season, Special Events</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>❌ Non-refundable</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">ไม่มี</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">100%</td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Flash Sales, 30-40% Discount</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">✉️ Automated Communications</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">📧 Booking Confirmation</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">ทันทีหลังจองเสร็จ + QR Code</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">⏰ Pre-arrival Reminder</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">1-3 วันก่อนเช็คอิน</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🏨 Check-in Instructions</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">วันเช็คอิน + แผนที่</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">👋 During Stay</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">ข้อมูลสิ่งอำนวยความสะดวก</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🚪 Check-out Thank You</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">หลังเช็คเอาท์ + ลิงก์รีวิว</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">⭐ Review Request</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">3 วันหลังเช็คเอาท์</p>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Booking Optimization Tips</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Mobile-first:</strong> 70% จองผ่านมือถือ ต้อง Optimize UX</li>
                    <li><strong>Instant Confirmation:</strong> อย่าให้รอนาน ยืนยันทันที</li>
                    <li><strong>Upselling:</strong> เสนอ Room Upgrade, Breakfast, Early Check-in</li>
                    <li><strong>Reduce Friction:</strong> ลดขั้นตอนการจอง ไม่บังคับสมัครสมาชิก</li>
                    <li><strong>Trust Signals:</strong> แสดงรีวิว, Verified Badges, Secure Payment</li>
                </ul>
            </div>
        </section>
    </section>

{{-- 3: Channel Manager --}}
    <section id="channel" class="wiki-section">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">🌐 Channel Manager & Distribution</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Centralized Distribution Management</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">จัดการช่องทางจำหน่ายทั้งหมดจากที่เดียว Real-time Sync ทุก OTA</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🏨 Connected Channels</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🌐</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Booking.com</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📊 35% of Total Bookings</li>
                        <li>💰 Commission: 15-18%</li>
                        <li>⭐ Rating: 8.9/10</li>
                        <li>✅ 2-way Sync</li>
                        <li>🎯 Genius Program</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✈️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Agoda</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📊 25% of Total Bookings</li>
                        <li>💰 Commission: 15-20%</li>
                        <li>⭐ Rating: 8.5/10</li>
                        <li>✅ XML Integration</li>
                        <li>🎯 Strong in Asia</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏖️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Expedia Group</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📊 15% of Total Bookings</li>
                        <li>💰 Commission: 18-25%</li>
                        <li>⭐ Multi-brand (Expedia, Hotels.com)</li>
                        <li>✅ EPS Integration</li>
                        <li>🎯 International Markets</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✈️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Airbnb</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📊 10% of Total Bookings</li>
                        <li>💰 Commission: 3% (Host) + 14% (Guest)</li>
                        <li>⭐ Unique Experiences</li>
                        <li>✅ API Integration</li>
                        <li>🎯 Long-term Stays</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🌐</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Direct Website</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📊 15% of Total Bookings</li>
                        <li>💰 Commission: 0%</li>
                        <li>⭐ Highest Profit Margin</li>
                        <li>✅ Full Control</li>
                        <li>🎯 Loyalty Members</li>
                    </ul>
                </div>

                <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 1.5rem; transition: all 0.3s;"
                     onmouseover="this.style.borderColor='rgb(var(--primary-rgb))'; this.style.boxShadow='0 8px 24px rgba(var(--primary-rgb), 0.2)'; this.style.transform='translateY(-4px)'"
                     onmouseout="this.style.borderColor='var(--wiki-border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✈️</div>
                    <h4 style="font-weight: 700; margin-bottom: 1rem;">Metasearch (Google, Trivago)</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📊 Comparison Shopping</li>
                        <li>💰 CPC/CPA Model</li>
                        <li>⭐ High Visibility</li>
                        <li>✅ Price Comparison</li>
                        <li>🎯 Drive to Direct</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">📊 Channel Performance Analytics</h3>
            <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: var(--wiki-card-bg);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Channel</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Bookings</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Revenue</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Commission</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Net Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Booking.com</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">350</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿875,000</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">15%</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿743,750</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Agoda</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">250</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿625,000</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">18%</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">฿512,500</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Direct Website</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">150</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">฿450,000</td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">0%</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--secondary-rgb));">฿450,000</strong></td>
                    </tr>
                    <tr style="background: rgba(var(--primary-rgb), 0.05);">
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>Total</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>1,000</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>฿2,500,000</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong>Avg 12%</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb)); font-size: 1.1rem;">฿2,200,000</strong></td>
                    </tr>
                </tbody>
            </table>

            <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1.5rem;">💰 Commission Savings with Direct Bookings</h4>
                <div style="font-family: monospace; font-size: 0.95rem; line-height: 2;">
                    <strong>Monthly Performance (1,000 Bookings)</strong><br><br>

                    <span style="color: rgb(var(--accent-rgb));"><strong>OTA Bookings (85%):</strong></span><br>
                    • Revenue: ฿2,125,000<br>
                    • Commission (15% avg): -฿318,750<br>
                    • Net: ฿1,806,250<br>
                    <hr style="margin: 1rem 0; border-color: #ddd;">

                    <span style="color: rgb(var(--primary-rgb));"><strong>Direct Bookings (15%):</strong></span><br>
                    • Revenue: ฿375,000<br>
                    • Commission: ฿0<br>
                    • Net: ฿375,000<br>
                    <hr style="margin: 1rem 0; border-color: #ddd;">

                    <strong style="color: rgb(var(--secondary-rgb)); font-size: 1.1rem;">If Increase Direct to 30% → Save ฿212,500/month! 💰</strong>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Channel Management Strategies</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Rate Parity:</strong> รักษาราคาเท่ากันทุกช่องทาง หรือให้ Direct ถูกกว่า 5-10%</li>
                    <li><strong>Allocate Wisely:</strong> จัดสรรห้องตาม Performance แต่ละ Channel</li>
                    <li><strong>Closed to Arrival:</strong> ปิดรับ Check-in วันที่ต้องการ Drive Extended Stays</li>
                    <li><strong>Last Room Availability:</strong> เปิดห้องสุดท้ายให้ช่องทางที่ Commission ต่ำสุด</li>
                    <li><strong>Drive Direct:</strong> ให้ Benefits เฉพาะ Direct (Free Upgrade, Late Check-out)</li>
                </ul>
            </div>
        </section>
    </section>

{{-- 4: Guest Experience --}}
    <section id="guest" class="wiki-section">
        <section class="wiki-section">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">⭐ Guest Experience & Reviews</h2>

            <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 0.5rem; color: rgb(var(--primary-rgb));">💡 Exceptional Guest Journey</h4>
                <p style="margin: 0; color: var(--wiki-text-secondary);">สร้างประสบการณ์พักที่น่าจดจำ และจัดการรีวิวอย่างมืออาชีพ</p>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🗺️ Guest Journey Touchpoints</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">1️⃣ Pre-arrival</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>📧 Confirmation Email + QR</li>
                        <li>🗺️ Directions & Parking</li>
                        <li>⏰ Check-in Time Options</li>
                        <li>🎁 Special Requests</li>
                        <li>🍽️ Restaurant Reservations</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">2️⃣ Check-in</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>⚡ Express Check-in (<3min)</li>
                        <li>🔑 Digital Room Key</li>
                        <li>🎁 Welcome Drink</li>
                        <li>📱 Hotel App Download</li>
                        <li>🌟 Room Upgrade Offer</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border: 2px solid rgb(var(--accent-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--accent-rgb));">3️⃣ During Stay</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>🛎️ Concierge Services</li>
                        <li>🧹 Housekeeping on Demand</li>
                        <li>🍔 Room Service</li>
                        <li>💬 In-app Chat Support</li>
                        <li>📺 Smart TV + Streaming</li>
                    </ul>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border: 2px solid rgb(var(--primary-rgb)); border-radius: 12px; padding: 1.5rem;">
                    <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">4️⃣ Check-out</h4>
                    <ul style="list-style: none; padding-left: 0; font-size: 0.9rem; line-height: 1.8;">
                        <li>⚡ Express Check-out</li>
                        <li>📧 Email Invoice/Receipt</li>
                        <li>🚖 Airport Transfer</li>
                        <li>💼 Luggage Storage</li>
                        <li>⭐ Review Request</li>
                    </ul>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">⭐ Review Management</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Overall Rating</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">8.9/10</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">⭐⭐⭐⭐⭐ Superb</p>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Total Reviews</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb)); margin: 0;">1,254</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">+45 this month</p>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Response Rate</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb)); margin: 0;">98%</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Within 24 hours</p>
                </div>

                <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
                    <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Recommendation</div>
                    <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">92%</h4>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Would recommend</p>
                </div>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">📊 Review Breakdown</h3>
            <table class="wiki-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: var(--wiki-card-bg);">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Category</th>
                        <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Score</th>
                        <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Common Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🛏️ Room Quality</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">9.2</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Clean, comfortable, spacious</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>👥 Staff Service</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">9.5</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Friendly, helpful, professional</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>📍 Location</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">9.0</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Convenient, near attractions</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🧹 Cleanliness</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">9.3</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Spotless, well-maintained</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>💰 Value for Money</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--accent-rgb));">8.5</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Good price, could be better</td>
                    </tr>
                    <tr>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><strong>🍽️ Facilities</strong></td>
                        <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);"><strong style="color: rgb(var(--primary-rgb));">8.8</strong></td>
                        <td style="padding: 1rem; border: 1px solid var(--wiki-border);">Pool, gym, restaurant quality</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="font-size: 1.35rem; font-weight: 700; margin: 2rem 0 1rem; color: var(--wiki-text);">🛎️ Guest Services & Amenities</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🏊 Pool & Spa</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">Infinity pool, jacuzzi, massage</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">💪 Fitness Center</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">24/7 gym, yoga classes</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🍽️ Restaurant & Bar</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">3 restaurants, rooftop bar</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">💼 Business Center</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">Meeting rooms, co-working space</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">🅿️ Parking</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">Free parking, valet service</p>
                </div>
                <div style="background: var(--wiki-card-bg); border: 1px solid var(--wiki-border); border-radius: 8px; padding: 1rem;">
                    <h5 style="font-weight: 700; margin-bottom: 0.5rem;">📶 WiFi</h5>
                    <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin: 0;">High-speed, unlimited, free</p>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border: 2px solid rgb(var(--secondary-rgb)); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                <h4 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">💡 Guest Experience Tips</h4>
                <ul style="margin: 0; padding-left: 1.5rem; line-height: 1.8;">
                    <li><strong>Personalization:</strong> ทักทายด้วยชื่อ จำ Preferences (pillows, temperature)</li>
                    <li><strong>Surprise & Delight:</strong> Welcome amenity, upgrade, late check-out</li>
                    <li><strong>Quick Response:</strong> ตอบ Request และ Complaint ภายใน 15 นาที</li>
                    <li><strong>Leverage Technology:</strong> Mobile check-in, digital key, in-app services</li>
                    <li><strong>Ask for Reviews:</strong> ขอรีวิวทันทีหลัง Check-out เมื่อประทับใจสูงสุด</li>
                    <li><strong>Respond to All:</strong> ตอบรีวิว 100% ทั้งดีและไม่ดี แสดงความใส่ใจ</li>
                </ul>
            </div>
        </section>
    </div>
</div>

