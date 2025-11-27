{{-- Hero Section --}}
<div class="bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 p-8 md:p-12 rounded-2xl mb-8 shadow-xl">
    <div class="max-w-4xl mx-auto text-center">
        <div class="inline-block bg-white/20 px-6 py-2 rounded-full mb-4 backdrop-blur-sm">
            <span class="text-white font-semibold text-sm">📊 Accounting System</span>
        </div>
        <h1 class="text-white text-2xl md:text-4xl font-extrabold mb-4 drop-shadow-lg">
            ระบบบัญชีครบวงจร
        </h1>
        <p class="text-white/95 text-lg md:text-xl leading-relaxed">
            ระบบบัญชีครบวงจรตามมาตรฐาน รองรับภาษีและการเงิน พร้อม Flowaccount Integration
        </p>
    </div>
</div>

{{-- 1: Invoice & Billing --}}
<section id="tab1" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>📄</span> Invoice & Billing Management
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Professional Invoicing System</h4>
        <p class="text-gray-600 dark:text-gray-400">ระบบออกใบแจ้งหนี้และบิลอัตโนมัติ รองรับภาษีมูลค่าเพิ่ม พร้อมเชื่อมต่อ E-Tax Invoice</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">📝 Document Types</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">📄</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Quotation (ใบเสนอราคา)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Auto-numbering (QT-YYYYMM-####)</li>
                <li>Product/Service Selection</li>
                <li>Discount & Tax Calculation</li>
                <li>Valid Until Date</li>
                <li>Convert to Invoice</li>
                <li>PDF & Email Sending</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">📋</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Invoice (ใบแจ้งหนี้)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Auto-numbering (INV-YYYYMM-####)</li>
                <li>Payment Terms (Net 7/15/30)</li>
                <li>Due Date Tracking</li>
                <li>Partial Payment Support</li>
                <li>Overdue Alerts</li>
                <li>Multi-currency Support</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🧾</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Tax Invoice (ใบกำกับภาษี)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>VAT 7% Calculation</li>
                <li>Tax ID Validation</li>
                <li>Address Verification</li>
                <li>E-Tax Invoice Format</li>
                <li>RD Portal Integration</li>
                <li>Digital Signature</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🧾</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Receipt (ใบเสร็จรับเงิน)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Auto-generate from Invoice</li>
                <li>Payment Method Recording</li>
                <li>Bank Transfer Details</li>
                <li>Cash/Card/Transfer</li>
                <li>Receipt Template</li>
                <li>Email Notification</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🔴</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Credit Note (ใบลดหนี้)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Linked to Original Invoice</li>
                <li>Reason Selection</li>
                <li>Partial/Full Credit</li>
                <li>VAT Adjustment</li>
                <li>Auto AR Update</li>
                <li>Approval Workflow</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="text-4xl mb-4">🔵</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Debit Note (ใบเพิ่มหนี้)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Additional Charges</li>
                <li>Interest Calculation</li>
                <li>Penalty Fees</li>
                <li>Late Payment Charges</li>
                <li>Auto Journal Entry</li>
                <li>Customer Notification</li>
            </ul>
        </div>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">💳 Payment Processing</h3>
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl border-2 border-gray-200 dark:border-gray-700 mb-8">
        <div class="flex items-center gap-4 flex-wrap justify-center">
            <div class="text-center flex-1 min-w-[140px]">
                <div class="w-12 h-12 bg-primary-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">1</div>
                <div class="font-bold mb-1">📄 Create Invoice</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">สร้างใบแจ้งหนี้</div>
            </div>
            <div class="text-2xl text-gray-300 dark:text-gray-600">→</div>
            <div class="text-center flex-1 min-w-[140px]">
                <div class="w-12 h-12 bg-secondary-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">2</div>
                <div class="font-bold mb-1">📧 Send to Customer</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">ส่งเมลพร้อม PDF</div>
            </div>
            <div class="text-2xl text-gray-300 dark:text-gray-600">→</div>
            <div class="text-center flex-1 min-w-[140px]">
                <div class="w-12 h-12 bg-accent-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">3</div>
                <div class="font-bold mb-1">💰 Receive Payment</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">รับชำระเงิน</div>
            </div>
            <div class="text-2xl text-gray-300 dark:text-gray-600">→</div>
            <div class="text-center flex-1 min-w-[140px]">
                <div class="w-12 h-12 bg-primary-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">4</div>
                <div class="font-bold mb-1">🧾 Issue Receipt</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">ออกใบเสร็จ</div>
            </div>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Best Practices for Invoicing</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Sequential Numbering:</strong> ใช้เลขที่เอกสารต่อเนื่อง ไม่ข้าม ไม่ซ้ำ</li>
            <li><strong>Tax Compliance:</strong> ตรวจสอบเลขประจำตัวผู้เสียภาษีทุกครั้ง</li>
            <li><strong>Payment Terms:</strong> ระบุเงื่อนไขการชำระเงินชัดเจน</li>
            <li><strong>Backup Documents:</strong> สำรองเอกสารอิเล็กทรอนิกส์ไว้อย่างน้อย 5 ปี</li>
            <li><strong>Prompt Sending:</strong> ส่งใบแจ้งหนี้ทันทีหลังส่งสินค้า/บริการ</li>
        </ul>
    </div>
</section>

{{-- 2: Expense & AP --}}
<section id="tab2" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>💰</span> Expense & Accounts Payable
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Smart Expense Management</h4>
        <p class="text-gray-600 dark:text-gray-400">ควบคุมค่าใช้จ่ายและจัดการเจ้าหนี้ พร้อม Approval Workflow และ Budget Control</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">📂 Expense Categories</h3>
    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Category</th>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Sub-categories</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Approval Required</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">VAT Deductible</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🏢 Office Expenses</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Rent, Utilities, Supplies, Maintenance</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Yes (>฿10,000)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>💼 Marketing</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Advertising, PR, Events, Social Media</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Yes (>฿20,000)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🚗 Transportation</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Fuel, Taxi, Parking, Toll, Vehicle Maintenance</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">No (Auto)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🍽️ Meals & Entertainment</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Business Meals, Client Entertainment</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Yes (>฿5,000)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">❌ (50% only)</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>💻 IT & Software</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Software Licenses, Cloud Services, Hardware</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Yes (>฿15,000)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>📚 Training & Development</strong></td>
                    <td class="p-4 border border-gray-200 dark:border-gray-600">Courses, Seminars, Books, Certifications</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Yes (>฿10,000)</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">🔄 Expense Workflow</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-2 border-primary-500">
            <div class="text-4xl mb-4">📝</div>
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">1. Submit Expense</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📸 Upload Receipt Photo</li>
                <li>💰 Enter Amount & Details</li>
                <li>🏷️ Select Category</li>
                <li>📅 Date & Vendor</li>
                <li>📋 Description/Remarks</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-2 border-secondary-500">
            <div class="text-4xl mb-4">✅</div>
            <h4 class="text-lg font-bold mb-3 text-secondary-600 dark:text-secondary-400">2. Manager Review</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>👀 Review Details</li>
                <li>🔍 Verify Receipt</li>
                <li>📊 Check Budget</li>
                <li>✅ Approve/Reject</li>
                <li>💬 Add Comments</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-2 border-accent-500">
            <div class="text-4xl mb-4">💼</div>
            <h4 class="text-lg font-bold mb-3 text-accent-600 dark:text-accent-400">3. Finance Processing</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>📚 Create Journal Entry</li>
                <li>🏦 Record AP</li>
                <li>🧾 Link Tax Invoice</li>
                <li>📊 Update Budget</li>
                <li>💾 Archive Documents</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-2 border-primary-500">
            <div class="text-4xl mb-4">💸</div>
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">4. Payment</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li>🏦 Bank Transfer</li>
                <li>💳 Company Card</li>
                <li>💵 Cash Reimbursement</li>
                <li>📧 Payment Notification</li>
                <li>✅ Mark as Paid</li>
            </ul>
        </div>
    </div>

    <h3 class="text-primary-600 dark:text-primary-400 text-xl font-bold mb-4">📊 Vendor Management (Accounts Payable)</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">👥 Vendor Database</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Vendor Profile & Contact</li>
                <li>Tax ID & Branch Code</li>
                <li>Bank Account Details</li>
                <li>Payment Terms</li>
                <li>Purchase History</li>
                <li>Credit Limit</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">📋 Purchase Order (PO)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>PO Creation</li>
                <li>Approval Workflow</li>
                <li>Send to Vendor</li>
                <li>Goods Receipt</li>
                <li>3-Way Matching</li>
                <li>PO vs Invoice Comparison</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">💳 Payment Scheduling</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Aging Report (30/60/90)</li>
                <li>Payment Due Dates</li>
                <li>Batch Payments</li>
                <li>Cash Flow Planning</li>
                <li>Discount Opportunities</li>
                <li>Auto-reminders</li>
            </ul>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Expense Management Tips</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Digital Receipts:</strong> เก็บรูปถ่ายใบเสร็จทุกครั้ง ป้องกันสูญหาย</li>
            <li><strong>Real-time Entry:</strong> บันทึกค่าใช้จ่ายทันที อย่าปล่อยให้ค้าง</li>
            <li><strong>Budget Monitoring:</strong> ติดตาม Budget vs Actual รายเดือน</li>
            <li><strong>Early Payment:</strong> ใช้ประโยชน์จากส่วนลดชำระก่อนกำหนด (2/10 Net 30)</li>
            <li><strong>Vendor Negotiation:</strong> Review ราคาและเงื่อนไขกับเจ้าหนี้ประจำทุกปี</li>
        </ul>
    </div>
</section>

{{-- 3: Financial Reports --}}
<section id="tab3" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>📈</span> Financial Reports & Analytics
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Real-time Financial Insights</h4>
        <p class="text-gray-600 dark:text-gray-400">งบการเงินครบถ้วนตามมาตรฐาน พร้อม Dashboard แสดงผล Real-time และ Export to Excel/PDF</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">📊 Core Financial Statements</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-2 border-primary-500">
            <div class="text-4xl mb-4">💹</div>
            <h4 class="text-lg font-bold mb-2 text-primary-600 dark:text-primary-400">Income Statement (P&L)</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">งบกำไรขาดทุน</p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li><strong class="text-primary-600 dark:text-primary-400">Revenue:</strong> ฿2,500,000</li>
                <li><strong>COGS:</strong> -฿1,200,000</li>
                <li><strong class="text-secondary-600 dark:text-secondary-400">Gross Profit:</strong> ฿1,300,000 (52%)</li>
                <li><strong>Operating Expenses:</strong> -฿800,000</li>
                <li><strong class="text-primary-600 dark:text-primary-400">Net Profit:</strong> ฿500,000 (20%)</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-2 border-secondary-500">
            <div class="text-4xl mb-4">⚖️</div>
            <h4 class="text-lg font-bold mb-2 text-secondary-600 dark:text-secondary-400">Balance Sheet</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">งบดุล</p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li><strong class="text-primary-600 dark:text-primary-400">Total Assets:</strong> ฿5,000,000</li>
                <li>• Current Assets: ฿3,000,000</li>
                <li>• Fixed Assets: ฿2,000,000</li>
                <li><strong class="text-accent-600 dark:text-accent-400">Total Liabilities:</strong> ฿2,000,000</li>
                <li><strong class="text-secondary-600 dark:text-secondary-400">Equity:</strong> ฿3,000,000</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-2 border-accent-500">
            <div class="text-4xl mb-4">💵</div>
            <h4 class="text-lg font-bold mb-2 text-accent-600 dark:text-accent-400">Cash Flow Statement</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">งบกระแสเงินสด</p>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 text-sm">
                <li><strong>Operating:</strong> +฿450,000</li>
                <li><strong>Investing:</strong> -฿200,000</li>
                <li><strong>Financing:</strong> -฿50,000</li>
                <li class="border-t border-gray-300 dark:border-gray-600 pt-2 mt-2">
                    <strong class="text-accent-600 dark:text-accent-400">Net Cash Flow:</strong> +฿200,000
                </li>
            </ul>
        </div>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">📋 Management Reports</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">📊 AR Aging Report</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">อายุลูกหนี้ 0-30, 31-60, 61-90, >90 วัน</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">📉 AP Aging Report</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">อายุเจ้าหนี้ Due Date Tracking</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">💰 Budget vs Actual</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">เปรียบเทียบงบประมาณกับผลจริง</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">📈 Sales by Product</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">ยอดขายแยกตามสินค้า/บริการ</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">👥 Sales by Customer</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">ลูกค้า Top 10, 20, 50</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">💸 Expense by Category</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">ค่าใช้จ่ายแยกตามหมวดหมู่</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">🏦 Bank Reconciliation</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">ยอดตามบัญชีกับยอดธนาคาร</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
            <h5 class="font-bold mb-1 text-sm">📊 Profit by Project</h5>
            <p class="text-xs text-gray-500 dark:text-gray-400">กำไรแยกตาม Project/Department</p>
        </div>
    </div>

    <h3 class="text-primary-600 dark:text-primary-400 text-xl font-bold mb-4">🎯 Key Performance Indicators (KPIs)</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-l-4 border-primary-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Gross Profit Margin</div>
            <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">52%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Target: 50% | ✅ Above Target</div>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-l-4 border-secondary-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Net Profit Margin</div>
            <div class="text-3xl font-extrabold text-secondary-600 dark:text-secondary-400">20%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Target: 15% | ✅ Excellent</div>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-l-4 border-accent-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Average DSO</div>
            <div class="text-3xl font-extrabold text-accent-600 dark:text-accent-400">35 days</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Target: &lt;30 days | ⚠️ Need Improvement</div>
        </div>

        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-l-4 border-primary-500">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Current Ratio</div>
            <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">2.5:1</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">Target: &gt;2:1 | ✅ Healthy</div>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Financial Reporting Best Practices</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Monthly Close:</strong> ปิดบัญชีรายเดือนภายใน 5 วันทำการ</li>
            <li><strong>Regular Review:</strong> ทบทวนงบการเงินกับ Management ทุกเดือน</li>
            <li><strong>Variance Analysis:</strong> อธิบายส่วนต่างระหว่าง Plan vs Actual >10%</li>
            <li><strong>Cash Flow Focus:</strong> ติดตาม Cash Flow เป็นรายสัปดาห์</li>
            <li><strong>External Audit:</strong> จัดทำงบการเงินตรวจสอบปีละ 1 ครั้ง</li>
        </ul>
    </div>
</section>

{{-- 4: Tax & Compliance --}}
<section id="tab4" class="wiki-section mb-12">
    <h2 class="text-primary-600 dark:text-primary-400 text-2xl font-bold mb-6 flex items-center gap-2">
        <span>🧾</span> Tax & Compliance Management
    </h2>

    <div class="bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-primary-500 mb-8">
        <h4 class="font-bold mb-2 text-primary-600 dark:text-primary-400">💡 Tax Compliance Made Easy</h4>
        <p class="text-gray-600 dark:text-gray-400">ระบบภาษีครบถ้วน รองรับการยื่นภาษีทุกประเภท พร้อม E-Filing Integration</p>
    </div>

    <h3 class="text-secondary-600 dark:text-secondary-400 text-xl font-bold mb-4">📋 Tax Types & Filing</h3>
    <div class="overflow-x-auto mb-8">
        <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Tax Type</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Form</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Filing Period</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Due Date</th>
                    <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">E-Filing</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-300">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>📊 VAT (ภาษีมูลค่าเพิ่ม)</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ภ.พ.30</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Monthly</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">15th of next month</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>💼 WHT (ภาษีหัก ณ ที่จ่าย)</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ภ.ง.ด.3, 53</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Monthly</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">7th of next month</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🏢 Corporate Income Tax</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ภ.ง.ด.50</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Semi-annual</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">2 months after period</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>👥 Personal Income Tax</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">ภ.ง.ด.91</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Annual</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">31 March</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 border border-gray-200 dark:border-gray-600"><strong>🏢 SSO (ประกันสังคม)</strong></td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">สปส.1-10</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">Monthly</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">15th of next month</td>
                    <td class="p-4 text-center border border-gray-200 dark:border-gray-600">✅</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="text-accent-600 dark:text-accent-400 text-xl font-bold mb-4">💰 VAT Management</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-6 rounded-xl border-2 border-primary-500">
            <h4 class="text-lg font-bold mb-3 text-primary-600 dark:text-primary-400">📊 Output VAT (ภาษีขาย)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Auto-calculate 7% on Sales</li>
                <li>Tax Invoice Issuance</li>
                <li>E-Tax Invoice Support</li>
                <li>Sales Register (รง 3)</li>
                <li>Monthly Summary</li>
                <li>Customer Tax Report</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 p-6 rounded-xl border-2 border-secondary-500">
            <h4 class="text-lg font-bold mb-3 text-secondary-600 dark:text-secondary-400">📉 Input VAT (ภาษีซื้อ)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Capture 7% on Purchases</li>
                <li>Tax Invoice Verification</li>
                <li>Purchase Register (รง 5)</li>
                <li>Vendor Tax Report</li>
                <li>Non-deductible VAT</li>
                <li>Monthly Summary</li>
            </ul>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 p-6 rounded-xl border-2 border-accent-500">
            <h4 class="text-lg font-bold mb-3 text-accent-600 dark:text-accent-400">🧾 VAT Filing (ภ.พ.30)</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Auto-fill ภ.พ.30 Form</li>
                <li>Output VAT: ฿175,000</li>
                <li>Input VAT: -฿84,000</li>
                <li class="border-t border-gray-300 dark:border-gray-600 pt-2 mt-2 font-bold text-accent-600 dark:text-accent-400">VAT Payable: ฿91,000</li>
                <li>E-Filing to RD Portal</li>
            </ul>
        </div>
    </div>

    <h3 class="text-primary-600 dark:text-primary-400 text-xl font-bold mb-4">🔗 Integration & E-Filing</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <div class="text-4xl mb-4">🏛️</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Revenue Department</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>RD Smart Tax Portal</li>
                <li>E-Tax Invoice</li>
                <li>E-Receipt</li>
                <li>E-Withholding Tax</li>
                <li>Auto XML Generation</li>
                <li>Digital Signature</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <div class="text-4xl mb-4">💼</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Flowaccount Integration</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Sync All Transactions</li>
                <li>Auto Tax Calculation</li>
                <li>Document Management</li>
                <li>Financial Reports</li>
                <li>VAT Report Export</li>
                <li>2-way Sync</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700">
            <div class="text-4xl mb-4">🏦</div>
            <h4 class="text-lg font-bold mb-3 text-gray-900 dark:text-white">Bank Integration</h4>
            <ul class="text-gray-600 dark:text-gray-400 space-y-1 ml-4 list-disc">
                <li>Auto Bank Feed</li>
                <li>Payment Matching</li>
                <li>Reconciliation</li>
                <li>Transfer Slip Upload</li>
                <li>Multi-bank Support</li>
                <li>Real-time Balance</li>
            </ul>
        </div>
    </div>

    <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-xl mb-8">
        <h4 class="font-bold mb-4 text-gray-900 dark:text-white">📊 Tax Compliance Example</h4>
        <div class="font-mono text-sm leading-relaxed text-gray-700 dark:text-gray-300">
            <strong>บริษัท ABC จำกัด - ภาษีประจำเดือน มกราคม 2025</strong><br><br>

            <span class="text-primary-600 dark:text-primary-400"><strong>VAT (ภ.พ.30):</strong></span><br>
            • Output VAT (ภาษีขาย 7%): ฿175,000<br>
            • Input VAT (ภาษีซื้อ 7%): -฿84,000<br>
            • <strong class="text-accent-600 dark:text-accent-400">VAT Payable: ฿91,000</strong><br>
            • Due: 15 Feb 2025<br>
            <hr class="my-4 border-gray-300 dark:border-gray-600">

            <span class="text-secondary-600 dark:text-secondary-400"><strong>WHT (ภ.ง.ด.53):</strong></span><br>
            • Service Fees Paid: ฿500,000<br>
            • WHT 3% Withheld: ฿15,000<br>
            • <strong class="text-accent-600 dark:text-accent-400">WHT Payable: ฿15,000</strong><br>
            • Due: 7 Feb 2025<br>
            <hr class="my-4 border-gray-300 dark:border-gray-600">

            <strong class="text-primary-600 dark:text-primary-400 text-lg">Total Tax Payment: ฿106,000</strong>
        </div>
    </div>

    <div class="bg-secondary-50 dark:bg-secondary-900/20 p-6 rounded-xl border-2 border-secondary-500">
        <h4 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">💡 Tax Compliance Tips</h4>
        <ul class="space-y-2 text-gray-700 dark:text-gray-300">
            <li><strong>Early Preparation:</strong> เตรียมเอกสารล่วงหน้า 3-5 วัน ก่อนวันกำหนดยื่น</li>
            <li><strong>Digital Archiving:</strong> เก็บเอกสารภาษีอิเล็กทรอนิกส์อย่างน้อย 5 ปี</li>
            <li><strong>Tax Calendar:</strong> ตั้งปฏิทินแจ้งเตือนวันกำหนดยื่นภาษีทุกประเภท</li>
            <li><strong>Professional Help:</strong> ใช้บริการบัญชีและภาษีสำหรับธุรกิจขนาดใหญ่</li>
            <li><strong>Regular Updates:</strong> ติดตามกฎหมายภาษีที่เปลี่ยนแปลง</li>
        </ul>
    </div>
</section>
