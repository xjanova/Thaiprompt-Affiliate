{{-- Hero Section --}}
<div class="bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 p-8 md:p-12 rounded-2xl mb-8 shadow-xl">
    <div class="max-w-4xl mx-auto text-center">
        <div class="inline-block bg-white/20 px-6 py-2 rounded-full mb-4 backdrop-blur-sm">
            <span class="text-white font-semibold text-sm">🏨 Hotel Booking System</span>
        </div>
        <h1 class="text-white text-2xl md:text-4xl font-extrabold mb-4 drop-shadow-lg">
            ระบบจองโรงแรมครบวงจร
        </h1>
        <p class="text-white/95 text-lg md:text-xl leading-relaxed">
            ระบบจองโรงแรมครบวงจร Channel Manager & Property Management ระดับมืออาชีพ
        </p>
    </div>
</div>

{{-- 1: Room Management --}}
<section id="tab1" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>🛏️</span> Room Management & Inventory
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Smart Room Inventory System</h4>
        <p class="text-gray-600 dark:text-gray-400">จัดการห้องพัก Dynamic Pricing และ Real-time Availability ทุก Channel</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">🏨 Room Types</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🛏️</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Standard Room</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>25-30 ตร.ม.</li>
                <li>เตียงคิงไซส์/ทวิน</li>
                <li>ห้องน้ำส่วนตัว</li>
                <li>WiFi ฟรี</li>
                <li class="font-bold text-primary-600 dark:text-primary-400">฿1,200-1,800/คืน</li>
                <li class="text-sm">📊 15 ห้อง พร้อมให้บริการ</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🌟</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Deluxe Room</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>35-40 ตร.ม.</li>
                <li>เตียงคิงไซส์</li>
                <li>ระเบียงส่วนตัว</li>
                <li>มินิบาร์</li>
                <li class="font-bold text-secondary-600 dark:text-secondary-400">฿2,500-3,500/คืน</li>
                <li class="text-sm">📊 10 ห้อง พร้อมให้บริการ</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">👑</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Suite</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>60-80 ตร.ม.</li>
                <li>ห้องนอนแยก + ห้องนั่งเล่น</li>
                <li>อ่างอาบน้ำจากุซซี่</li>
                <li>Butler Service</li>
                <li class="font-bold text-accent-600 dark:text-accent-400">฿5,000-8,000/คืน</li>
                <li class="text-sm">📊 5 ห้อง พร้อมให้บริการ</li>
            </ul>
        </div>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">💰 Dynamic Pricing Engine</h3>
    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Season / Event</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Occupancy Target</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Price Multiplier</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Strategy</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🔥 Peak Season</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">90-100%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-accent-600 dark:text-accent-400">×1.8-2.0</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Maximum revenue, Min stay 2-3 nights</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>☀️ High Season</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">80-90%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">×1.3-1.5</td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Optimize revenue per room</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🌤️ Shoulder Season</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">60-70%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">×1.0-1.2</td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Standard rate, Flexible cancellation</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🌧️ Low Season</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">40-50%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-secondary-600 dark:text-secondary-400">×0.7-0.9</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Promotional rates, Long-stay discounts</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🎉 Special Events</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">100%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">×2.5-3.0</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Concert/Festival periods, Min 3 nights</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-primary-600 dark:text-primary-400 text-xl font-bold mb-4">📅 Availability Calendar</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-2 border-primary-500">
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">Real-time Sync</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Update All Channels Instantly</li>
                <li>Prevent Overbooking</li>
                <li>Room Allocation Control</li>
                <li>Block Dates for Maintenance</li>
                <li>Special Rates & Packages</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-2 border-secondary-500">
            <h4 class="text-lg font-bold mb-3 text-secondary-600 dark:text-secondary-400">Restrictions & Rules</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Minimum Stay (1-7 nights)</li>
                <li>Maximum Stay Limit</li>
                <li>Closed to Arrival/Departure</li>
                <li>Stop-sell Dates</li>
                <li>Release Periods</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-2 border-accent-500">
            <h4 class="text-lg font-bold mb-3 text-accent-600 dark:text-accent-400">Occupancy Analytics</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📊 Current: <strong>78%</strong> Occupied</li>
                <li>📈 This Week: <strong>82%</strong> Forecast</li>
                <li>📅 Next Month: <strong>65%</strong> Booked</li>
                <li>💰 ADR: <strong>฿2,350</strong></li>
                <li>🎯 RevPAR: <strong>฿1,833</strong></li>
            </ul>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Room Management Best Practices</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Update Instantly:</strong> ปรับราคาและ Availability ทุกช่องทางทันที</li>
            <li><strong>Strategic Overbooking:</strong> Overbook 5-10% สำหรับ No-shows โดยมี Backup Plan</li>
            <li><strong>Upsell Opportunities:</strong> เสนอห้องระดับสูงกว่า ณ เวลาเช็คอิน</li>
            <li><strong>Maintenance Schedule:</strong> วางแผนซ่อมบำรุงใน Low Season</li>
            <li><strong>Photo Quality:</strong> อัพเดทรูปภาพคุณภาพสูงทุก 6 เดือน</li>
        </ul>
    </div>
</section>

{{-- 2: Booking & Reservation --}}
<section id="tab2" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>📅</span> Booking & Reservation System
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Seamless Booking Experience</h4>
        <p class="text-gray-600 dark:text-gray-400">ระบบจองแบบ Real-time พร้อม Payment Gateway และ Email Confirmation อัตโนมัติ</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">🔄 Booking Flow</h3>
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl border-2 border-gray-200 dark:border-gray-700 mb-8">
        <div class="flex items-center gap-4 flex-wrap justify-center">
            <div class="text-center flex-1 min-w-[120px]">
                <div class="w-12 h-12 bg-primary-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">1</div>
                <div class="font-bold mb-1">🔍 Search</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">วันที่ + จำนวนคน</div>
            </div>
            <div class="text-2xl text-gray-300 dark:text-gray-600">→</div>
            <div class="text-center flex-1 min-w-[120px]">
                <div class="w-12 h-12 bg-secondary-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">2</div>
                <div class="font-bold mb-1">🛏️ Select Room</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">เลือกห้องพัก</div>
            </div>
            <div class="text-2xl text-gray-300 dark:text-gray-600">→</div>
            <div class="text-center flex-1 min-w-[120px]">
                <div class="w-12 h-12 bg-accent-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">3</div>
                <div class="font-bold mb-1">📝 Guest Info</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">กรอกข้อมูล</div>
            </div>
            <div class="text-2xl text-gray-300 dark:text-gray-600">→</div>
            <div class="text-center flex-1 min-w-[120px]">
                <div class="w-12 h-12 bg-primary-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">4</div>
                <div class="font-bold mb-1">💳 Payment</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">ชำระเงิน</div>
            </div>
            <div class="text-2xl text-gray-300 dark:text-gray-600">→</div>
            <div class="text-center flex-1 min-w-[120px]">
                <div class="w-12 h-12 bg-secondary-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">5</div>
                <div class="font-bold mb-1">✅ Confirm</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">ส่งอีเมล</div>
            </div>
        </div>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">💳 Payment Options</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <div class="text-4xl mb-4">💳</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Credit/Debit Card</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Visa / MasterCard / AMEX</li>
                <li>3D Secure Authentication</li>
                <li>Instant Confirmation</li>
                <li>Pre-authorization Hold</li>
                <li class="font-bold">💰 ค่าธรรมเนียม 2.5%</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <div class="text-4xl mb-4">🏦</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Bank Transfer</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>QR PromptPay</li>
                <li>Bank Transfer</li>
                <li>Upload Slip</li>
                <li>Manual Verification</li>
                <li class="font-bold text-secondary-600 dark:text-secondary-400">💰 ไม่มีค่าธรรมเนียม</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <div class="text-4xl mb-4">🏨</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Pay at Hotel</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Reserve Now, Pay Later</li>
                <li>Credit Card Guarantee</li>
                <li>Cash/Card at Check-in</li>
                <li>Flexible for Guests</li>
                <li class="font-bold text-secondary-600 dark:text-secondary-400">💰 ไม่มีค่าธรรมเนียม</li>
            </ul>
        </div>
    </div>

    <h3 class="text-primary-600 dark:text-primary-400 text-xl font-bold mb-4">📋 Cancellation Policies</h3>
    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Policy Type</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Free Cancellation</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Penalty</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Use Case</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>✅ Flexible</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ล่วงหน้า 24 ชม.</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">1 คืน</td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Low Season, Standard Rates</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>⚖️ Moderate</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ล่วงหน้า 7 วัน</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">30%</td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">High Season, Promotional Rates</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🔒 Strict</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ล่วงหน้า 14 วัน</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">50%</td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Peak Season, Special Events</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>❌ Non-refundable</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ไม่มี</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">100%</td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Flash Sales, 30-40% Discount</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">✉️ Automated Communications</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">📧 Booking Confirmation</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">ทันทีหลังจองเสร็จ + QR Code</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">⏰ Pre-arrival Reminder</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">1-3 วันก่อนเช็คอิน</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">🏨 Check-in Instructions</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">วันเช็คอิน + แผนที่</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">👋 During Stay</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">ข้อมูลสิ่งอำนวยความสะดวก</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">🚪 Check-out Thank You</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">หลังเช็คเอาท์ + ลิงก์รีวิว</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">⭐ Review Request</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">3 วันหลังเช็คเอาท์</p>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Booking Optimization Tips</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Mobile-first:</strong> 70% จองผ่านมือถือ ต้อง Optimize UX</li>
            <li><strong>Instant Confirmation:</strong> อย่าให้รอนาน ยืนยันทันที</li>
            <li><strong>Upselling:</strong> เสนอ Room Upgrade, Breakfast, Early Check-in</li>
            <li><strong>Reduce Friction:</strong> ลดขั้นตอนการจอง ไม่บังคับสมัครสมาชิก</li>
            <li><strong>Trust Signals:</strong> แสดงรีวิว, Verified Badges, Secure Payment</li>
        </ul>
    </div>
</section>

{{-- 3: Channel Manager --}}
<section id="tab3" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>🌐</span> Channel Manager & Distribution
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Centralized Distribution Management</h4>
        <p class="text-gray-600 dark:text-gray-400">จัดการช่องทางจำหน่ายทั้งหมดจากที่เดียว Real-time Sync ทุก OTA</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">🏨 Connected Channels</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🌐</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Booking.com</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📊 35% of Total Bookings</li>
                <li>💰 Commission: 15-18%</li>
                <li>⭐ Rating: 8.9/10</li>
                <li>✅ 2-way Sync</li>
                <li>🎯 Genius Program</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">✈️</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Agoda</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📊 25% of Total Bookings</li>
                <li>💰 Commission: 15-20%</li>
                <li>⭐ Rating: 8.5/10</li>
                <li>✅ XML Integration</li>
                <li>🎯 Strong in Asia</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🏖️</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Expedia Group</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📊 15% of Total Bookings</li>
                <li>💰 Commission: 18-25%</li>
                <li>⭐ Multi-brand (Expedia, Hotels.com)</li>
                <li>✅ EPS Integration</li>
                <li>🎯 International Markets</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">✈️</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Airbnb</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📊 10% of Total Bookings</li>
                <li>💰 Commission: 3% (Host) + 14% (Guest)</li>
                <li>⭐ Unique Experiences</li>
                <li>✅ API Integration</li>
                <li>🎯 Long-term Stays</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🌐</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Direct Website</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📊 15% of Total Bookings</li>
                <li>💰 <strong class="text-secondary-600 dark:text-secondary-400">Commission: 0%</strong></li>
                <li>⭐ Highest Profit Margin</li>
                <li>✅ Full Control</li>
                <li>🎯 Loyalty Members</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">✈️</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Metasearch</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📊 Google, Trivago</li>
                <li>💰 CPC/CPA Model</li>
                <li>⭐ High Visibility</li>
                <li>✅ Price Comparison</li>
                <li>🎯 Drive to Direct</li>
            </ul>
        </div>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">📊 Channel Performance Analytics</h3>
    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Channel</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Bookings</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Revenue</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Commission</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Net Revenue</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>Booking.com</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">350</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">฿875,000</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">15%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">฿743,750</strong></td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>Agoda</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">250</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">฿625,000</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">18%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">฿512,500</strong></td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>Direct Website</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">150</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">฿450,000</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-secondary-600 dark:text-secondary-400">0%</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-secondary-600 dark:text-secondary-400">฿450,000</strong></td>
                </tr>
                <tr class="bg-primary-50 dark:bg-primary-900/20 font-bold">
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Total</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">1,000</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">฿2,500,000</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Avg 12%</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400 text-lg">฿2,200,000</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-xl mb-8">
        <h4 class="font-bold mb-4 text-gray-900 dark:text-white">💰 Commission Savings with Direct Bookings</h4>
        <div class="font-mono text-sm leading-relaxed text-gray-700 dark:text-gray-300">
            <strong>Monthly Performance (1,000 Bookings)</strong><br><br>

            <span class="text-accent-600 dark:text-accent-400"><strong>OTA Bookings (85%):</strong></span><br>
            • Revenue: ฿2,125,000<br>
            • Commission (15% avg): -฿318,750<br>
            • Net: ฿1,806,250<br>
            <hr class="my-4 border-gray-300 dark:border-gray-600">

            <span class="text-primary-600 dark:text-primary-400"><strong>Direct Bookings (15%):</strong></span><br>
            • Revenue: ฿375,000<br>
            • Commission: ฿0<br>
            • Net: ฿375,000<br>
            <hr class="my-4 border-gray-300 dark:border-gray-600">

            <strong class="text-secondary-600 dark:text-secondary-400 text-lg">If Increase Direct to 30% → Save ฿212,500/month! 💰</strong>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Channel Management Strategies</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Rate Parity:</strong> รักษาราคาเท่ากันทุกช่องทาง หรือให้ Direct ถูกกว่า 5-10%</li>
            <li><strong>Allocate Wisely:</strong> จัดสรรห้องตาม Performance แต่ละ Channel</li>
            <li><strong>Closed to Arrival:</strong> ปิดรับ Check-in วันที่ต้องการ Drive Extended Stays</li>
            <li><strong>Last Room Availability:</strong> เปิดห้องสุดท้ายให้ช่องทางที่ Commission ต่ำสุด</li>
            <li><strong>Drive Direct:</strong> ให้ Benefits เฉพาะ Direct (Free Upgrade, Late Check-out)</li>
        </ul>
    </div>
</section>

{{-- 4: Guest Experience --}}
<section id="tab4" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>⭐</span> Guest Experience & Reviews
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Exceptional Guest Journey</h4>
        <p class="text-gray-600 dark:text-gray-400">สร้างประสบการณ์พักที่น่าจดจำ และจัดการรีวิวอย่างมืออาชีพ</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">🗺️ Guest Journey Touchpoints</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-2 border-primary-500">
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">1️⃣ Pre-arrival</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>📧 Confirmation Email + QR</li>
                <li>🗺️ Directions & Parking</li>
                <li>⏰ Check-in Time Options</li>
                <li>🎁 Special Requests</li>
                <li>🍽️ Restaurant Reservations</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-2 border-secondary-500">
            <h4 class="text-lg font-bold mb-3 text-secondary-600 dark:text-secondary-400">2️⃣ Check-in</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>⚡ Express Check-in (&lt;3min)</li>
                <li>🔑 Digital Room Key</li>
                <li>🎁 Welcome Drink</li>
                <li>📱 Hotel App Download</li>
                <li>🌟 Room Upgrade Offer</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-2 border-accent-500">
            <h4 class="text-lg font-bold mb-3 text-accent-600 dark:text-accent-400">3️⃣ During Stay</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>🛎️ Concierge Services</li>
                <li>🧹 Housekeeping on Demand</li>
                <li>🍔 Room Service</li>
                <li>💬 In-app Chat Support</li>
                <li>📺 Smart TV + Streaming</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-2 border-primary-500">
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">4️⃣ Check-out</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc text-sm">
                <li>⚡ Express Check-out</li>
                <li>📧 Email Invoice/Receipt</li>
                <li>🚖 Airport Transfer</li>
                <li>💼 Luggage Storage</li>
                <li>⭐ Review Request</li>
            </ul>
        </div>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">⭐ Review Management</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-l-4 border-primary-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Overall Rating</div>
            <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">8.9/10</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">⭐⭐⭐⭐⭐ Superb</div>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-l-4 border-secondary-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Total Reviews</div>
            <div class="text-3xl font-extrabold text-secondary-600 dark:text-secondary-400">1,254</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">+45 this month</div>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-l-4 border-accent-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Response Rate</div>
            <div class="text-3xl font-extrabold text-accent-600 dark:text-accent-400">98%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Within 24 hours</div>
        </div>

        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-l-4 border-primary-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Recommendation</div>
            <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">92%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Would recommend</div>
        </div>
    </div>

    <h3 class="text-primary-600 dark:text-primary-400 text-xl font-bold mb-4">📊 Review Breakdown</h3>
    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Category</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Score</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Common Feedback</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🛏️ Room Quality</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">9.2</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Clean, comfortable, spacious</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>👥 Staff Service</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">9.5</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Friendly, helpful, professional</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>📍 Location</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">9.0</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Convenient, near attractions</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🧹 Cleanliness</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">9.3</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Spotless, well-maintained</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>💰 Value for Money</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-accent-600 dark:text-accent-400">8.5</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Good price, could be better</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🍽️ Facilities</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600"><strong class="text-primary-600 dark:text-primary-400">8.8</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Pool, gym, restaurant quality</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">🛎️ Guest Services & Amenities</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl mb-2">🏊</div>
            <h5 class="font-bold text-sm">Pool & Spa</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">Infinity pool, jacuzzi</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl mb-2">💪</div>
            <h5 class="font-bold text-sm">Fitness Center</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">24/7 gym, yoga</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl mb-2">🍽️</div>
            <h5 class="font-bold text-sm">Restaurant</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">3 restaurants, bar</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl mb-2">💼</div>
            <h5 class="font-bold text-sm">Business Center</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">Meeting rooms</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl mb-2">🅿️</div>
            <h5 class="font-bold text-sm">Parking</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">Free, valet service</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
            <div class="text-2xl mb-2">📶</div>
            <h5 class="font-bold text-sm">WiFi</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">High-speed, free</p>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Guest Experience Tips</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Personalization:</strong> ทักทายด้วยชื่อ จำ Preferences (pillows, temperature)</li>
            <li><strong>Surprise & Delight:</strong> Welcome amenity, upgrade, late check-out</li>
            <li><strong>Quick Response:</strong> ตอบ Request และ Complaint ภายใน 15 นาที</li>
            <li><strong>Leverage Technology:</strong> Mobile check-in, digital key, in-app services</li>
            <li><strong>Ask for Reviews:</strong> ขอรีวิวทันทีหลัง Check-out เมื่อประทับใจสูงสุด</li>
            <li><strong>Respond to All:</strong> ตอบรีวิว 100% ทั้งดีและไม่ดี แสดงความใส่ใจ</li>
        </ul>
    </div>
</section>
