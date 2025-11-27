<div class="wiki-content-section">
    {{-- Hero Section --}}
    <div class="bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 p-8 md:p-12 rounded-2xl mb-8 shadow-xl">
        <h1 class="text-white text-2xl md:text-4xl font-extrabold mb-3 drop-shadow-lg">🎫 Support Ticket System</h1>
        <p class="text-white/95 text-lg md:text-xl font-medium">ระบบ Help Desk และ Support Ticket แบบครบวงจร</p>
    </div>

    {{-- Tab 1: Ticket Management --}}
    <section id="ticket-mgmt" class="wiki-section">
        <div class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 p-6 rounded-xl mb-6 border-l-4 border-primary-500">
            <h3 class="font-bold mb-2 text-primary-600 dark:text-primary-400">🎯 Advanced Ticket Management</h3>
            <p class="leading-relaxed">ระบบจัดการ Ticket ที่ทรงพลัง รองรับ Multi-channel, Priority-based routing, SLA tracking และ Auto-assignment ช่วยให้ทีม Support ทำงานอย่างมีประสิทธิภาพสูงสุด</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📝 Ticket Creation & Channels</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">📧</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Email to Ticket</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Auto-create from support@</li>
                    <li>✅ Extract subject & body</li>
                    <li>✅ Attach email files</li>
                    <li>✅ Reply tracking</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🌐</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Web Portal</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Self-service submission</li>
                    <li>✅ Category selection</li>
                    <li>✅ File upload (max 10MB)</li>
                    <li>✅ Real-time status</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">💬</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Live Chat</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Widget integration</li>
                    <li>✅ Chat to ticket conversion</li>
                    <li>✅ Transcript saving</li>
                    <li>✅ Queue management</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">📱</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">API Integration</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ RESTful API</li>
                    <li>✅ Third-party apps</li>
                    <li>✅ Webhook support</li>
                    <li>✅ OAuth authentication</li>
                </ul>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🎯 Priority & Status Management</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Priority Level</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">SLA Response</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">SLA Resolution</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Auto-Assignment</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Escalation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong class="text-red-500">🔴 Critical</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-red-500">15 min</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-red-500">4 hours</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Senior Agent + Manager notify</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Auto-escalate after 2 hours</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong class="text-orange-500">🟠 High</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-orange-500">1 hour</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-orange-500">8 hours</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Available Senior Agent</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Escalate after 6 hours</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong class="text-yellow-500">🟡 Medium</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-yellow-500">4 hours</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-yellow-500">24 hours</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Round-robin to any agent</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Escalate after 18 hours</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong class="text-green-500">🟢 Low</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-green-500">8 hours</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-green-500">48 hours</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Least loaded agent</td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Escalate after 36 hours</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔄 Ticket Workflow & Status</h2>

        <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-xl mb-8">
            <div class="flex flex-wrap items-center justify-center gap-4">
                <div class="bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border-2 border-gray-400 font-semibold text-gray-700 dark:text-gray-300">1️⃣ New</div>
                <span class="text-2xl text-primary-500">→</span>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border-2 border-cyan-400 font-semibold text-gray-700 dark:text-gray-300">2️⃣ Open</div>
                <span class="text-2xl text-primary-500">→</span>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border-2 border-yellow-400 font-semibold text-gray-700 dark:text-gray-300">3️⃣ In Progress</div>
                <span class="text-2xl text-primary-500">→</span>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border-2 border-blue-400 font-semibold text-gray-700 dark:text-gray-300">4️⃣ Pending</div>
                <span class="text-2xl text-primary-500">→</span>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border-2 border-green-400 font-semibold text-gray-700 dark:text-gray-300">5️⃣ Resolved</div>
                <span class="text-2xl text-primary-500">→</span>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 rounded-lg border-2 border-green-600 font-semibold text-gray-700 dark:text-gray-300">6️⃣ Closed</div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-secondary-50 to-accent-50 dark:from-secondary-900/20 dark:to-accent-900/20 p-6 rounded-xl border-l-4 border-secondary-500">
            <h4 class="font-bold mb-3 text-secondary-600 dark:text-secondary-400">💡 Best Practices - Ticket Management</h4>
            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                <li><strong>Auto-categorization:</strong> ใช้ AI จัด Category และ Priority อัตโนมัติจาก Keywords</li>
                <li><strong>SLA Monitoring:</strong> ตั้ง Alert เตือนก่อน SLA ใกล้หมด 30 นาที</li>
                <li><strong>Merge Duplicates:</strong> ตรวจสอบและรวม Ticket ซ้ำจากลูกค้าคนเดียวกัน</li>
                <li><strong>Smart Assignment:</strong> พิจารณา Agent Skill, Workload และ Availability</li>
                <li><strong>Customer History:</strong> แสดงประวัติ Ticket เก่า และ Order ก่อนหน้า</li>
            </ul>
        </div>
    </section>

    {{-- Tab 2: Communication & Collaboration --}}
    <section id="communication" class="wiki-section mt-12">
        <div class="bg-gradient-to-r from-secondary-50 to-accent-50 dark:from-secondary-900/20 dark:to-accent-900/20 p-6 rounded-xl mb-6 border-l-4 border-secondary-500">
            <h3 class="font-bold mb-2 text-secondary-600 dark:text-secondary-400">💬 Multi-Channel Communication</h3>
            <p class="leading-relaxed">รองรับการสื่อสารหลากหลายช่องทาง พร้อม Internal Notes, Knowledge Base, Canned Responses และ File Attachments ทำให้การแก้ปัญหารวดเร็วและมีประสิทธิภาพ</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📨 Communication Features</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-primary-600 dark:text-primary-400">🔒 Internal Notes</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Private notes between agents</li>
                    <li>✅ @Mention team members</li>
                    <li>✅ Rich text formatting</li>
                    <li>✅ Searchable history</li>
                    <li>✅ Not visible to customers</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-secondary-600 dark:text-secondary-400">📧 Email Integration</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ IMAP/SMTP connection</li>
                    <li>✅ Email templates</li>
                    <li>✅ Auto CC/BCC support</li>
                    <li>✅ Thread conversation</li>
                    <li>✅ HTML email support</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-accent-600 dark:text-accent-400">📎 File Attachments</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Upload up to 10MB per file</li>
                    <li>✅ Images, PDFs, Docs, Videos</li>
                    <li>✅ Drag & drop upload</li>
                    <li>✅ Inline image preview</li>
                    <li>✅ Secure cloud storage</li>
                </ul>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📚 Knowledge Base & Self-Service</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Feature</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Description</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Impact</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📖 Help Center</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Public knowledge base with categories, search, and FAQs</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">-30% tickets</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🔍 Smart Search</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">AI-powered search with instant suggestions and related articles</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">+45% self-solve</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>💬 Community Forum</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">User-to-user support, voting, verified answers</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">-20% tickets</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🎥 Video Tutorials</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Embedded videos, step-by-step guides, screenshots</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">+60% engagement</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📱 Mobile-Friendly</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Responsive design, works on all devices</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">+40% mobile</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">⚡ Canned Responses & Templates</h2>

        <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-xl mb-8 space-y-4">
            <h4 class="font-bold mb-4 text-gray-900 dark:text-white">🎯 Common Response Templates</h4>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-green-500">
                <strong class="text-green-600 dark:text-green-400">✅ Order Confirmation</strong>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">"สวัสดีค่ะ คุณ {customer_name} ออเดอร์ {order_id} ได้รับการยืนยันแล้วค่ะ จะจัดส่งภายใน 2-3 วันทำการ..."</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-cyan-500">
                <strong class="text-cyan-600 dark:text-cyan-400">📦 Shipping Update</strong>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">"พัสดุของคุณได้ถูกส่งแล้วค่ะ Tracking: {tracking_number} คาดว่าจะได้รับภายในวันที่ {delivery_date}..."</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-yellow-500">
                <strong class="text-yellow-600 dark:text-yellow-400">🔄 Refund Process</strong>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">"คำขอคืนเงินของคุณได้รับการอนุมัติแล้วค่ะ จำนวนเงิน ฿{refund_amount} จะโอนคืนภายใน 7-14 วันทำการ..."</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-red-500">
                <strong class="text-red-600 dark:text-red-400">❌ Out of Stock</strong>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">"ขออภัยค่ะ สินค้า {product_name} หมดสต็อกชั่วคราว คาดว่าจะมีสินค้าเข้าใหม่วันที่ {restock_date}..."</p>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">👥 Team Collaboration</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-primary-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">🔔</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Real-time Notifications</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Browser, Email, SMS alerts</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-secondary-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">👤</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Agent Assignment</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Smart routing & load balancing</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-accent-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">🔄</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Ticket Transfer</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Seamless handoff between agents</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-primary-500 hover:scale-105 transition-all duration-300">
                <div class="text-4xl mb-3">📊</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Workload Dashboard</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">See team capacity in real-time</p>
            </div>
        </div>

        <div class="bg-gradient-to-r from-accent-50 to-primary-50 dark:from-accent-900/20 dark:to-primary-900/20 p-6 rounded-xl border-l-4 border-accent-500">
            <h4 class="font-bold mb-3 text-accent-600 dark:text-accent-400">🎯 Pro Tips - Communication Excellence</h4>
            <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                <li><strong>Personalization:</strong> ใช้ {customer_name}, {order_id} แทนที่ค่าอัตโนมัติ</li>
                <li><strong>Tone consistency:</strong> กำหนด Brand Voice และ Tone of voice ที่ชัดเจน</li>
                <li><strong>Multi-language:</strong> รองรับภาษาไทยและอังกฤษในทุก Response</li>
                <li><strong>Fast replies:</strong> ใช้ Keyboard shortcuts (Ctrl+1, Ctrl+2) สำหรับ Canned Responses</li>
                <li><strong>Follow-up:</strong> ตั้ง Reminder สำหรับ Ticket ที่ยังรอการติดตามผล</li>
            </ul>
        </div>
    </section>

    {{-- Tab 3: Analytics & Reports --}}
    <section id="analytics" class="wiki-section mt-12">
        <div class="bg-gradient-to-r from-accent-50 to-primary-50 dark:from-accent-900/20 dark:to-primary-900/20 p-6 rounded-xl mb-6 border-l-4 border-accent-500">
            <h3 class="font-bold mb-2 text-accent-600 dark:text-accent-400">📊 Advanced Analytics & Reporting</h3>
            <p class="leading-relaxed">ติดตามและวิเคราะห์ประสิทธิภาพของทีม Support ด้วย Real-time dashboards, Custom reports, SLA tracking และ Customer satisfaction metrics</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📈 Key Performance Metrics</h2>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-900/10 border-2 border-primary-500 rounded-xl p-6 text-center">
                <div class="text-4xl font-extrabold text-primary-600 dark:text-primary-400 mb-2">4.2 min</div>
                <div class="font-semibold text-gray-700 dark:text-gray-300">Avg First Response</div>
                <div class="text-sm text-green-500 mt-2">↓ 32% vs last month</div>
            </div>

            <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-900/10 border-2 border-secondary-500 rounded-xl p-6 text-center">
                <div class="text-4xl font-extrabold text-secondary-600 dark:text-secondary-400 mb-2">2.8 hrs</div>
                <div class="font-semibold text-gray-700 dark:text-gray-300">Avg Resolution Time</div>
                <div class="text-sm text-green-500 mt-2">↓ 18% vs last month</div>
            </div>

            <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-900/10 border-2 border-accent-500 rounded-xl p-6 text-center">
                <div class="text-4xl font-extrabold text-accent-600 dark:text-accent-400 mb-2">94.6%</div>
                <div class="font-semibold text-gray-700 dark:text-gray-300">Customer Satisfaction</div>
                <div class="text-sm text-green-500 mt-2">↑ 3.2% vs last month</div>
            </div>

            <div class="bg-gradient-to-br from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/10 border-2 border-primary-500 rounded-xl p-6 text-center">
                <div class="text-4xl font-extrabold text-primary-600 dark:text-primary-400 mb-2">1,847</div>
                <div class="font-semibold text-gray-700 dark:text-gray-300">Tickets This Month</div>
                <div class="text-sm text-red-500 mt-2">↑ 12% vs last month</div>
            </div>

            <div class="bg-gradient-to-br from-secondary-50 to-accent-50 dark:from-secondary-900/30 dark:to-accent-900/10 border-2 border-secondary-500 rounded-xl p-6 text-center">
                <div class="text-4xl font-extrabold text-secondary-600 dark:text-secondary-400 mb-2">97.3%</div>
                <div class="font-semibold text-gray-700 dark:text-gray-300">SLA Compliance</div>
                <div class="text-sm text-green-500 mt-2">↑ 1.8% vs last month</div>
            </div>

            <div class="bg-gradient-to-br from-accent-50 to-primary-50 dark:from-accent-900/30 dark:to-primary-900/10 border-2 border-accent-500 rounded-xl p-6 text-center">
                <div class="text-4xl font-extrabold text-accent-600 dark:text-accent-400 mb-2">38%</div>
                <div class="font-semibold text-gray-700 dark:text-gray-300">Self-Service Rate</div>
                <div class="text-sm text-green-500 mt-2">↑ 7% vs last month</div>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">👨‍💼 Agent Performance Dashboard</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Agent Name</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Tickets Solved</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Avg Response</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Avg Resolution</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">CSAT Score</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Performance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>👩 Somchai P.</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong>247</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">3.8 min</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">2.3 hrs</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-green-500">96.2%</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">🌟 Excellent</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>👨 Nattapong K.</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong>198</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">4.1 min</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">2.9 hrs</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-green-500">94.8%</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-cyan-500 text-white px-3 py-1 rounded-full text-sm">👍 Great</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>👩 Kanya W.</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong>176</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">4.5 min</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">3.2 hrs</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-green-500">93.1%</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-cyan-500 text-white px-3 py-1 rounded-full text-sm">👍 Great</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>👨 Wichai S.</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong>154</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">5.2 min</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">3.8 hrs</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-yellow-500">91.3%</strong></td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm">✅ Good</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📊 Ticket Trends & Categories</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-4 text-gray-900 dark:text-white">Top 5 Ticket Categories</h4>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">1. 📦 Shipping & Delivery</span><span class="font-bold">34%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-primary-500 h-full" style="width: 34%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">2. 🔄 Returns & Refunds</span><span class="font-bold">26%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-secondary-500 h-full" style="width: 26%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">3. 🛍️ Product Inquiries</span><span class="font-bold">18%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-accent-500 h-full" style="width: 18%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">4. 💳 Payment Issues</span><span class="font-bold">13%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-yellow-500 h-full" style="width: 13%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">5. 👤 Account Issues</span><span class="font-bold">9%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-gray-500 h-full" style="width: 9%"></div></div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-4 text-gray-900 dark:text-white">Channel Distribution</h4>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">📧 Email</span><span class="font-bold">42%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-cyan-500 h-full" style="width: 42%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">💬 Live Chat</span><span class="font-bold">31%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-green-500 h-full" style="width: 31%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">🌐 Web Portal</span><span class="font-bold">19%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-purple-500 h-full" style="width: 19%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-2"><span class="font-semibold">📱 API</span><span class="font-bold">8%</span></div>
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden"><div class="bg-orange-500 h-full" style="width: 8%"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">📋 Custom Reports Available</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-primary-500 hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
                <div class="text-4xl mb-3">📊</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Daily/Weekly/Monthly</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Summary reports with trends</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-secondary-500 hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
                <div class="text-4xl mb-3">⏱️</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">SLA Compliance</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Track SLA breaches & warnings</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-accent-500 hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
                <div class="text-4xl mb-3">😊</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">CSAT Surveys</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Customer feedback analysis</p>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-primary-500 hover:-translate-y-1 hover:shadow-lg transition-all duration-300">
                <div class="text-4xl mb-3">🎯</div>
                <h4 class="font-bold mb-2 text-gray-900 dark:text-white">Agent Productivity</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Individual performance metrics</p>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-xl mb-8">
            <h4 class="font-bold mb-4 text-gray-900 dark:text-white">💰 ROI Example: Support Ticket System</h4>
            <div class="font-mono text-sm leading-loose text-gray-700 dark:text-gray-300">
                <strong>E-commerce Company - 2,000 tickets/month</strong><br><br>

                <span class="text-red-500 font-bold">Before Ticket System:</span><br>
                • Email chaos: Lost threads, duplicate responses = 40% wasted time<br>
                • No SLA tracking: Slow responses, 72% CSAT only<br>
                • Manual routing: 15 min average assignment delay<br>
                • No knowledge base: Same questions repeated daily<br>
                • Agent cost: 6 agents × ฿25,000 = ฿150,000/month<br>
                <hr class="my-4 border-gray-300 dark:border-gray-600">

                <span class="text-primary-600 dark:text-primary-400 font-bold">After Support Ticket System:</span><br>
                • Auto-routing: &lt;5 sec assignment, +40% agent efficiency<br>
                • Knowledge base: 38% self-service rate = -760 tickets/month<br>
                • Canned responses: 3× faster replies, 4.2 min avg response<br>
                • CSAT improved: 72% → 94.6% (+22.6 points)<br>
                • Agent reduction: 6 → 4 agents needed = ฿50,000/month saved<br>
                <hr class="my-4 border-gray-300 dark:border-gray-600">

                <strong>Investment:</strong> ฿4,990/month (Included in Platform)<br>
                <strong class="text-secondary-600 dark:text-secondary-400">Net Benefit:</strong> ฿45,010/month<br>
                <strong class="text-primary-600 dark:text-primary-400 text-lg">ROI: 902% 🚀</strong><br><br>

                <em class="text-gray-500 dark:text-gray-400">Plus: Happier customers, better reviews, increased retention!</em>
            </div>
        </div>
    </section>

    {{-- Tab 4: Automation & AI --}}
    <section id="automation" class="wiki-section mt-12">
        <div class="bg-gradient-to-r from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20 p-6 rounded-xl mb-6 border-l-4 border-primary-500">
            <h3 class="font-bold mb-2 text-primary-600 dark:text-primary-400">🤖 AI-Powered Support Automation</h3>
            <p class="leading-relaxed">ระบบ AI และ Automation ที่ช่วยลดภาระงาน ตอบคำถามอัตโนมัติ และปรับปรุงประสิทธิภาพของทีม Support อย่างมีนัยสำคัญ</p>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🎯 Smart Automation Features</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🏷️</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Auto-Categorization</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ AI detects ticket category</li>
                    <li>✅ Keyword & phrase analysis</li>
                    <li>✅ 95% accuracy rate</li>
                    <li>✅ Continuous learning</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-secondary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">🎯</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Smart Routing</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Skills-based assignment</li>
                    <li>✅ Workload balancing</li>
                    <li>✅ Language matching</li>
                    <li>✅ VIP customer routing</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-accent-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">⚡</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Auto-Responses</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Instant acknowledgment</li>
                    <li>✅ FAQ auto-answers</li>
                    <li>✅ Order status updates</li>
                    <li>✅ Tracking info lookup</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-primary-500 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <div class="text-4xl mb-4">📊</div>
                <h4 class="font-bold mb-3 text-gray-900 dark:text-white">Priority Detection</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>✅ Sentiment analysis</li>
                    <li>✅ Urgency keywords</li>
                    <li>✅ VIP flagging</li>
                    <li>✅ Escalation triggers</li>
                </ul>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">💬 AI Chatbot Integration</h2>

        <div class="overflow-x-auto mb-8">
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow">
                <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/30">
                    <tr>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Chatbot Capability</th>
                        <th class="p-4 text-left border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Description</th>
                        <th class="p-4 text-center border border-gray-200 dark:border-gray-700 font-bold text-gray-900 dark:text-white">Success Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>📦 Order Tracking</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Instant order status, shipping updates, delivery ETA</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">97%</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🔄 Returns & Exchanges</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Initiate return requests, print labels, check refund status</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">92%</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>💳 Payment Issues</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Payment status, failed transaction help, receipt generation</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">88%</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>👤 Account Management</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Password reset, email changes, profile updates</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">94%</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>❓ Product FAQs</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Product info, availability, specifications, recommendations</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">85%</strong></td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="p-4 border border-gray-200 dark:border-gray-700"><strong>🚀 Human Handoff</strong></td>
                        <td class="p-4 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">Complex issues transferred to agents with full context</td>
                        <td class="p-4 text-center border border-gray-200 dark:border-gray-700"><strong class="text-primary-600 dark:text-primary-400">100%</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🔄 Workflow Automation Rules</h2>

        <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-xl mb-8 space-y-4">
            <h4 class="font-bold mb-4 text-gray-900 dark:text-white">⚙️ Example Automation Rules</h4>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-cyan-500">
                <strong class="text-cyan-600 dark:text-cyan-400 text-lg">🔵 Auto-Close Resolved Tickets</strong>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mt-3 font-mono text-sm">
                    <strong>Trigger:</strong> Status = "Resolved" for 72 hours<br>
                    <strong>Condition:</strong> No customer reply<br>
                    <strong>Action:</strong> Change status to "Closed" + Send CSAT survey
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-red-500">
                <strong class="text-red-600 dark:text-red-400 text-lg">🔴 VIP Customer Alert</strong>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mt-3 font-mono text-sm">
                    <strong>Trigger:</strong> New ticket from VIP customer<br>
                    <strong>Condition:</strong> Customer lifetime value > ฿100,000<br>
                    <strong>Action:</strong> Set priority to "High" + Assign to senior agent + Notify manager
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-yellow-500">
                <strong class="text-yellow-600 dark:text-yellow-400 text-lg">🟡 SLA Breach Warning</strong>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mt-3 font-mono text-sm">
                    <strong>Trigger:</strong> SLA deadline approaching (30 min left)<br>
                    <strong>Condition:</strong> Ticket status = "Open" or "In Progress"<br>
                    <strong>Action:</strong> Send urgent notification to agent + CC manager
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border-l-4 border-green-500">
                <strong class="text-green-600 dark:text-green-400 text-lg">🟢 Auto-Tag & Categorize</strong>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mt-3 font-mono text-sm">
                    <strong>Trigger:</strong> New ticket received<br>
                    <strong>Condition:</strong> Contains keywords: "refund", "return", "exchange"<br>
                    <strong>Action:</strong> Add tag "Returns" + Set category "Refunds" + Add to returns queue
                </div>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">🎓 AI-Suggested Responses</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-primary-50/50 to-secondary-50/50 dark:from-primary-900/10 dark:to-secondary-900/10 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-primary-600 dark:text-primary-400">🧠 Context-Aware Suggestions</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>• AI analyzes ticket content</li>
                    <li>• Suggests relevant responses</li>
                    <li>• Learns from past resolutions</li>
                    <li>• One-click to use/edit</li>
                </ul>
            </div>

            <div class="bg-gradient-to-br from-secondary-50/50 to-accent-50/50 dark:from-secondary-900/10 dark:to-accent-900/10 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-secondary-600 dark:text-secondary-400">📚 Knowledge Base Integration</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>• Links to relevant articles</li>
                    <li>• Auto-insert help links</li>
                    <li>• Suggests new KB articles</li>
                    <li>• Track article effectiveness</li>
                </ul>
            </div>

            <div class="bg-gradient-to-br from-accent-50/50 to-primary-50/50 dark:from-accent-900/10 dark:to-primary-900/10 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <h4 class="font-bold mb-3 text-accent-600 dark:text-accent-400">🎯 Sentiment Analysis</h4>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li>• Detect customer emotions</li>
                    <li>• Flag frustrated customers</li>
                    <li>• Suggest empathetic tones</li>
                    <li>• Escalate if needed</li>
                </ul>
            </div>
        </div>

        <div class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/20 dark:to-secondary-900/20 p-6 rounded-xl border-l-4 border-primary-500">
            <h4 class="font-bold mb-4 text-primary-600 dark:text-primary-400">🚀 Automation Impact Statistics</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div>
                    <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">38%</div>
                    <div class="font-semibold text-gray-700 dark:text-gray-300">Self-Service Rate</div>
                    <div class="text-sm text-green-500 mt-1">760 tickets/mo saved</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-secondary-600 dark:text-secondary-400">45 sec</div>
                    <div class="font-semibold text-gray-700 dark:text-gray-300">Avg Bot Response</div>
                    <div class="text-sm text-green-500 mt-1">92% faster than human</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-accent-600 dark:text-accent-400">24/7</div>
                    <div class="font-semibold text-gray-700 dark:text-gray-300">Support Coverage</div>
                    <div class="text-sm text-green-500 mt-1">No overtime costs</div>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">89%</div>
                    <div class="font-semibold text-gray-700 dark:text-gray-300">Bot Satisfaction</div>
                    <div class="text-sm text-green-500 mt-1">Customers love instant help</div>
                </div>
            </div>
        </div>
    </section>
</div>
