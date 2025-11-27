{{-- Hero Section with RGB Gradient --}}
<div class="bg-gradient-to-br from-primary-500 via-secondary-500 to-accent-500 p-8 md:p-16 rounded-2xl mb-12 shadow-2xl">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-white text-2xl md:text-4xl font-extrabold mb-4 drop-shadow-lg">
            🔌 API & Integration Guide
        </h1>
        <p class="text-white/95 text-lg md:text-xl font-medium mb-6">
            คู่มือ API และการเชื่อมต่อระบบ - RESTful API, Webhooks, Third-party Integrations
        </p>
        <div class="flex gap-4 justify-center flex-wrap">
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm text-white">
                🚀 REST API v1
            </span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm text-white">
                🔗 Webhooks
            </span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm text-white">
                🔐 OAuth 2.0
            </span>
        </div>
    </div>
</div>

{{-- API Overview --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        📋 API Overview
    </h2>

    <div class="info-box success">
        <h4>🎯 RESTful API Architecture</h4>
        <p>ThaiPrompt ใช้ RESTful API ตามมาตรฐาน OpenAPI 3.0 รองรับ JSON format และ OAuth 2.0 authentication</p>
    </div>

    <div class="wiki-grid wiki-grid-4">
        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 border-l-4 border-primary-500 p-6 rounded-lg">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Total Endpoints</div>
            <h4 class="text-3xl font-extrabold text-primary-600 dark:text-primary-400 m-0">{{ $stats['api_endpoints'] ?? 500 }}+</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">REST Endpoints</p>
        </div>

        <div class="bg-gradient-to-br from-secondary-50 to-secondary-100 dark:from-secondary-900/30 dark:to-secondary-800/20 border-l-4 border-secondary-500 p-6 rounded-lg">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Response Time</div>
            <h4 class="text-3xl font-extrabold text-secondary-600 dark:text-secondary-400 m-0">&lt;100</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ms average</p>
        </div>

        <div class="bg-gradient-to-br from-accent-50 to-accent-100 dark:from-accent-900/30 dark:to-accent-800/20 border-l-4 border-accent-500 p-6 rounded-lg">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Rate Limit</div>
            <h4 class="text-3xl font-extrabold text-accent-600 dark:text-accent-400 m-0">1000</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">requests/min</p>
        </div>

        <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 border-l-4 border-primary-500 p-6 rounded-lg">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">API Version</div>
            <h4 class="text-3xl font-extrabold text-primary-600 dark:text-primary-400 m-0">v1</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Current stable</p>
        </div>
    </div>
</section>

{{-- Authentication --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        🔐 Authentication
    </h2>

    <div class="wiki-grid wiki-grid-2">
        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-8">
            <h3 class="font-bold mb-4 text-primary-600 dark:text-primary-400">🎫 API Token (Bearer)</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ใช้สำหรับ Server-to-Server authentication
            </p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-gray-200 m-0 font-mono text-sm"><code>Authorization: Bearer {api_token}

# Example Request
curl -X GET "https://api.thaiprompt.com/v1/users" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1..."</code></pre>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-8">
            <h3 class="font-bold mb-4 text-secondary-600 dark:text-secondary-400">🔑 OAuth 2.0</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ใช้สำหรับ User authentication และ third-party apps
            </p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-gray-200 m-0 font-mono text-sm"><code># OAuth Flow
POST /oauth/token
{
  "grant_type": "authorization_code",
  "client_id": "your_client_id",
  "client_secret": "your_secret",
  "code": "authorization_code"
}</code></pre>
            </div>
        </div>
    </div>

    <div class="info-box tip">
        <h4>💡 Token Management</h4>
        <ul class="leading-loose">
            <li><strong>API Tokens:</strong> ไม่มีวันหมดอายุ (revoke ได้ที่ Dashboard)</li>
            <li><strong>OAuth Tokens:</strong> หมดอายุใน 1 ชั่วโมง (ใช้ refresh_token ต่ออายุ)</li>
            <li><strong>Best Practice:</strong> เก็บ token ใน environment variables ห้าม hardcode</li>
        </ul>
    </div>
</section>

{{-- API Endpoints --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        📡 Core API Endpoints
    </h2>

    <h3 class="text-xl font-bold mt-8 mb-6">👥 User Management</h3>

    <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gradient-to-r from-primary-50 to-secondary-50 dark:from-primary-900/30 dark:to-secondary-900/20">
            <tr>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Method</th>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Endpoint</th>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Description</th>
                <th class="p-4 text-center border border-gray-200 dark:border-gray-600 font-semibold">Auth</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">GET</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/users</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">รายการผู้ใช้ทั้งหมด (paginated)</td>
                <td class="p-4 text-center border border-gray-200 dark:border-gray-600">🔐</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">GET</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/users/{id}</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">รายละเอียดผู้ใช้</td>
                <td class="p-4 text-center border border-gray-200 dark:border-gray-600">🔐</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold">POST</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/users</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">สร้างผู้ใช้ใหม่</td>
                <td class="p-4 text-center border border-gray-200 dark:border-gray-600">🔐</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-yellow-500 text-black px-2 py-1 rounded text-xs font-bold">PUT</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/users/{id}</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">อัปเดตข้อมูลผู้ใช้</td>
                <td class="p-4 text-center border border-gray-200 dark:border-gray-600">🔐</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">DELETE</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/users/{id}</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">ลบผู้ใช้</td>
                <td class="p-4 text-center border border-gray-200 dark:border-gray-600">🔐</td>
            </tr>
        </tbody>
    </table>

    <h3 class="text-xl font-bold mt-8 mb-6">💎 MLM & Affiliate</h3>

    <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gradient-to-r from-secondary-50 to-accent-50 dark:from-secondary-900/30 dark:to-accent-900/20">
            <tr>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Method</th>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Endpoint</th>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">GET</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/mlm/members</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">รายการสมาชิก MLM</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">GET</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/mlm/genealogy/{id}</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">ดู Genealogy Tree</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">GET</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/mlm/commissions</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">รายการค่าคอมมิชชั่น</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold">POST</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/mlm/register</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">สมัครสมาชิก MLM ใหม่</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold">POST</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/mlm/payout</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">ดำเนินการจ่ายค่าคอมมิชชั่น</td>
            </tr>
        </tbody>
    </table>

    <h3 class="text-xl font-bold mt-8 mb-6">🛒 E-Commerce</h3>

    <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gradient-to-r from-accent-50 to-primary-50 dark:from-accent-900/30 dark:to-primary-900/20">
            <tr>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Method</th>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Endpoint</th>
                <th class="p-4 text-left border border-gray-200 dark:border-gray-600 font-semibold">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">GET</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/products</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">รายการสินค้า</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold">POST</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/orders</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">สร้างออเดอร์ใหม่</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">GET</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/orders/{id}</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">รายละเอียดออเดอร์</td>
            </tr>
            <tr>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><span class="bg-yellow-500 text-black px-2 py-1 rounded text-xs font-bold">PUT</span></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600"><code>/api/v1/orders/{id}/status</code></td>
                <td class="p-4 border border-gray-200 dark:border-gray-600">อัปเดตสถานะออเดอร์</td>
            </tr>
        </tbody>
    </table>
</section>

{{-- Webhooks --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        🔗 Webhooks
    </h2>

    <div class="info-box success">
        <h4>🎯 Real-time Event Notifications</h4>
        <p>รับแจ้งเตือนเมื่อเกิด event ต่างๆ ในระบบแบบ real-time ผ่าน HTTP POST</p>
    </div>

    <h3 class="text-xl font-bold mt-8 mb-6">📬 Available Events</h3>

    <div class="wiki-grid wiki-grid-3">
        <div class="wiki-card">
            <div class="text-4xl mb-4">👤</div>
            <h4 class="font-bold mb-4">User Events</h4>
            <ul class="list-none p-0 text-sm leading-loose">
                <li><code>user.created</code></li>
                <li><code>user.updated</code></li>
                <li><code>user.deleted</code></li>
                <li><code>user.kyc.approved</code></li>
                <li><code>user.kyc.rejected</code></li>
            </ul>
        </div>

        <div class="wiki-card">
            <div class="text-4xl mb-4">🛒</div>
            <h4 class="font-bold mb-4">Order Events</h4>
            <ul class="list-none p-0 text-sm leading-loose">
                <li><code>order.created</code></li>
                <li><code>order.paid</code></li>
                <li><code>order.shipped</code></li>
                <li><code>order.completed</code></li>
                <li><code>order.cancelled</code></li>
            </ul>
        </div>

        <div class="wiki-card">
            <div class="text-4xl mb-4">💰</div>
            <h4 class="font-bold mb-4">Commission Events</h4>
            <ul class="list-none p-0 text-sm leading-loose">
                <li><code>commission.earned</code></li>
                <li><code>commission.paid</code></li>
                <li><code>rank.upgraded</code></li>
                <li><code>bonus.achieved</code></li>
            </ul>
        </div>
    </div>

    <h3 class="text-xl font-bold mt-8 mb-6">📝 Webhook Payload Example</h3>

    <div class="bg-gray-900 rounded-xl p-6 overflow-x-auto">
        <pre class="text-gray-200 m-0 font-mono text-sm"><code>{
  "event": "order.paid",
  "timestamp": "2025-01-15T10:30:00Z",
  "data": {
    "order_id": "ORD-2025-00001",
    "customer_id": 12345,
    "total_amount": 1500.00,
    "currency": "THB",
    "payment_method": "promptpay",
    "status": "paid"
  },
  "signature": "sha256=abc123..."
}</code></pre>
    </div>

    <div class="info-box tip">
        <h4>💡 Webhook Best Practices</h4>
        <ul class="leading-loose">
            <li><strong>Verify Signature:</strong> ตรวจสอบ HMAC signature ทุกครั้งเพื่อความปลอดภัย</li>
            <li><strong>Respond Quickly:</strong> ตอบกลับ 200 OK ภายใน 5 วินาที</li>
            <li><strong>Idempotency:</strong> จัดการ duplicate events ด้วย event_id</li>
            <li><strong>Retry Logic:</strong> ระบบจะ retry 3 ครั้งหาก endpoint ไม่ตอบกลับ</li>
        </ul>
    </div>
</section>

{{-- Third-party Integrations --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        🔌 Third-party Integrations
    </h2>

    <div class="wiki-grid wiki-grid-4">
        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">💬</div>
            <h4 class="font-bold mb-2">LINE</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">Messaging API, Login, Pay</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>

        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">💳</div>
            <h4 class="font-bold mb-2">2C2P</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">Payment Gateway</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>

        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">📧</div>
            <h4 class="font-bold mb-2">SendGrid</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">Email Service</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>

        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">☁️</div>
            <h4 class="font-bold mb-2">AWS S3</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">File Storage</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>

        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">🤖</div>
            <h4 class="font-bold mb-2">OpenAI</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">ChatGPT, DALL-E</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>

        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">🌐</div>
            <h4 class="font-bold mb-2">Google Cloud</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">Translate, Vision</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>

        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">📦</div>
            <h4 class="font-bold mb-2">Flash Express</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">Shipping API</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>

        <div class="wiki-card text-center">
            <div class="text-5xl mb-4">📊</div>
            <h4 class="font-bold mb-2">Google Analytics</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">Analytics</p>
            <span class="inline-block bg-green-500 text-white px-2 py-1 rounded text-xs mt-2">Active</span>
        </div>
    </div>
</section>

{{-- SDKs & Libraries --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        📦 SDKs & Libraries
    </h2>

    <div class="wiki-grid wiki-grid-3">
        <div class="bg-white dark:bg-gray-800 border-2 border-blue-500 rounded-xl p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="text-4xl">📘</div>
                <div>
                    <h4 class="font-bold m-0">JavaScript/TypeScript</h4>
                    <span class="text-sm text-gray-500 dark:text-gray-400">npm package</span>
                </div>
            </div>
            <div class="bg-gray-900 rounded-lg p-4 mb-4">
                <code class="text-gray-200 text-sm">npm install @thaiprompt/sdk</code>
            </div>
            <a href="#" class="text-blue-500 text-sm hover:underline">📚 Documentation →</a>
        </div>

        <div class="bg-white dark:bg-gray-800 border-2 border-purple-500 rounded-xl p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="text-4xl">🐘</div>
                <div>
                    <h4 class="font-bold m-0">PHP</h4>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Composer package</span>
                </div>
            </div>
            <div class="bg-gray-900 rounded-lg p-4 mb-4">
                <code class="text-gray-200 text-sm">composer require thaiprompt/sdk</code>
            </div>
            <a href="#" class="text-purple-500 text-sm hover:underline">📚 Documentation →</a>
        </div>

        <div class="bg-white dark:bg-gray-800 border-2 border-yellow-500 rounded-xl p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="text-4xl">🐍</div>
                <div>
                    <h4 class="font-bold m-0">Python</h4>
                    <span class="text-sm text-gray-500 dark:text-gray-400">pip package</span>
                </div>
            </div>
            <div class="bg-gray-900 rounded-lg p-4 mb-4">
                <code class="text-gray-200 text-sm">pip install thaiprompt-sdk</code>
            </div>
            <a href="#" class="text-yellow-600 text-sm hover:underline">📚 Documentation →</a>
        </div>
    </div>
</section>

{{-- Rate Limiting & Errors --}}
<section class="wiki-section">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        ⚠️ Rate Limiting & Error Handling
    </h2>

    <div class="wiki-grid wiki-grid-2">
        <div>
            <h3 class="text-xl font-bold mb-4">📊 Rate Limits</h3>
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="p-3 border border-gray-200 dark:border-gray-600 font-semibold">Plan</th>
                        <th class="p-3 border border-gray-200 dark:border-gray-600 font-semibold">Requests/min</th>
                        <th class="p-3 border border-gray-200 dark:border-gray-600 font-semibold">Burst</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Standard</td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">100</td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">200</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Business</td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">500</td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">1000</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Enterprise</td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Unlimited</td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Unlimited</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h3 class="text-xl font-bold mb-4">❌ Error Codes</h3>
            <table class="w-full border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="p-3 border border-gray-200 dark:border-gray-600 font-semibold">Code</th>
                        <th class="p-3 border border-gray-200 dark:border-gray-600 font-semibold">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600"><code>400</code></td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Bad Request</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600"><code>401</code></td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Unauthorized</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600"><code>403</code></td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Forbidden</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600"><code>429</code></td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Rate Limited</td>
                    </tr>
                    <tr>
                        <td class="p-3 border border-gray-200 dark:border-gray-600"><code>500</code></td>
                        <td class="p-3 border border-gray-200 dark:border-gray-600">Server Error</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
