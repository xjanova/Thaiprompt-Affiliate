{{-- Hero Section with RGB Gradient --}}
<div style="background: linear-gradient(135deg, rgb(var(--primary-rgb)) 0%, rgb(var(--secondary-rgb)) 50%, rgb(var(--accent-rgb)) 100%); padding: 4rem 2rem; border-radius: 20px; margin-bottom: 3rem; box-shadow: 0 20px 60px rgba(var(--primary-rgb), 0.3);">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <h1 style="color: white; font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
            🔌 API & Integration Guide
        </h1>
        <p style="color: rgba(255,255,255,0.95); font-size: 1.25rem; font-weight: 500; margin-bottom: 1.5rem;">
            คู่มือ API และการเชื่อมต่อระบบ - RESTful API, Webhooks, Third-party Integrations
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                🚀 REST API v1
            </span>
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                🔗 Webhooks
            </span>
            <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; color: white;">
                🔐 OAuth 2.0
            </span>
        </div>
    </div>
</div>

{{-- API Overview --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📋 API Overview
    </h2>

    <div class="info-box success">
        <h4>🎯 RESTful API Architecture</h4>
        <p>ThaiPrompt ใช้ RESTful API ตามมาตรฐาน OpenAPI 3.0 รองรับ JSON format และ OAuth 2.0 authentication</p>
    </div>

    <div class="wiki-grid wiki-grid-4">
        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Total Endpoints</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">{{ $stats['api_endpoints'] ?? 500 }}+</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">REST Endpoints</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05)); border-left: 4px solid rgb(var(--secondary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Response Time</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--secondary-rgb)); margin: 0;">&lt;100</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">ms average</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--accent-rgb), 0.05)); border-left: 4px solid rgb(var(--accent-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">Rate Limit</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--accent-rgb)); margin: 0;">1000</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">requests/min</p>
        </div>

        <div style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05)); border-left: 4px solid rgb(var(--primary-rgb)); padding: 1.5rem; border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-bottom: 0.5rem;">API Version</div>
            <h4 style="font-size: 2rem; font-weight: 800; color: rgb(var(--primary-rgb)); margin: 0;">v1</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary); margin-top: 0.5rem;">Current stable</p>
        </div>
    </div>
</section>

{{-- Authentication --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        🔐 Authentication
    </h2>

    <div class="wiki-grid wiki-grid-2">
        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 2rem;">
            <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--primary-rgb));">🎫 API Token (Bearer)</h3>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                ใช้สำหรับ Server-to-Server authentication
            </p>
            <div style="background: #1e293b; border-radius: 8px; padding: 1rem; overflow-x: auto;">
                <pre style="color: #e2e8f0; margin: 0; font-family: 'Fira Code', monospace; font-size: 0.85rem;"><code>Authorization: Bearer {api_token}

# Example Request
curl -X GET "https://api.thaiprompt.com/v1/users" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1..."</code></pre>
            </div>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid var(--wiki-border); border-radius: 12px; padding: 2rem;">
            <h3 style="font-weight: 700; margin-bottom: 1rem; color: rgb(var(--secondary-rgb));">🔑 OAuth 2.0</h3>
            <p style="font-size: 0.9rem; color: var(--wiki-text-secondary); margin-bottom: 1rem;">
                ใช้สำหรับ User authentication และ third-party apps
            </p>
            <div style="background: #1e293b; border-radius: 8px; padding: 1rem; overflow-x: auto;">
                <pre style="color: #e2e8f0; margin: 0; font-family: 'Fira Code', monospace; font-size: 0.85rem;"><code># OAuth Flow
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
        <ul style="line-height: 1.8;">
            <li><strong>API Tokens:</strong> ไม่มีวันหมดอายุ (revoke ได้ที่ Dashboard)</li>
            <li><strong>OAuth Tokens:</strong> หมดอายุใน 1 ชั่วโมง (ใช้ refresh_token ต่ออายุ)</li>
            <li><strong>Best Practice:</strong> เก็บ token ใน environment variables ห้าม hardcode</li>
        </ul>
    </div>
</section>

{{-- API Endpoints --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📡 Core API Endpoints
    </h2>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">👥 User Management</h3>

    <table class="wiki-table">
        <thead style="background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--secondary-rgb), 0.05));">
            <tr>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Method</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Endpoint</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Description</th>
                <th style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">Auth</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">GET</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/users</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">รายการผู้ใช้ทั้งหมด (paginated)</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">🔐</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">GET</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/users/{id}</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">รายละเอียดผู้ใช้</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">🔐</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #007bff; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">POST</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/users</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">สร้างผู้ใช้ใหม่</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">🔐</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #ffc107; color: black; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">PUT</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/users/{id}</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">อัปเดตข้อมูลผู้ใช้</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">🔐</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #dc3545; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">DELETE</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/users/{id}</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ลบผู้ใช้</td>
                <td style="padding: 1rem; text-align: center; border: 1px solid var(--wiki-border);">🔐</td>
            </tr>
        </tbody>
    </table>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">💎 MLM & Affiliate</h3>

    <table class="wiki-table">
        <thead style="background: linear-gradient(135deg, rgba(var(--secondary-rgb), 0.1), rgba(var(--accent-rgb), 0.05));">
            <tr>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Method</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Endpoint</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">GET</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/mlm/members</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">รายการสมาชิก MLM</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">GET</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/mlm/genealogy/{id}</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ดู Genealogy Tree</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">GET</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/mlm/commissions</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">รายการค่าคอมมิชชั่น</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #007bff; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">POST</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/mlm/register</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">สมัครสมาชิก MLM ใหม่</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #007bff; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">POST</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/mlm/payout</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">ดำเนินการจ่ายค่าคอมมิชชั่น</td>
            </tr>
        </tbody>
    </table>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">🛒 E-Commerce</h3>

    <table class="wiki-table">
        <thead style="background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), rgba(var(--primary-rgb), 0.05));">
            <tr>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Method</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Endpoint</th>
                <th style="padding: 1rem; text-align: left; border: 1px solid var(--wiki-border);">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">GET</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/products</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">รายการสินค้า</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #007bff; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">POST</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/orders</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">สร้างออเดอร์ใหม่</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">GET</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/orders/{id}</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">รายละเอียดออเดอร์</td>
            </tr>
            <tr>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><span style="background: #ffc107; color: black; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">PUT</span></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);"><code>/api/v1/orders/{id}/status</code></td>
                <td style="padding: 1rem; border: 1px solid var(--wiki-border);">อัปเดตสถานะออเดอร์</td>
            </tr>
        </tbody>
    </table>
</section>

{{-- Webhooks --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        🔗 Webhooks
    </h2>

    <div class="info-box success">
        <h4>🎯 Real-time Event Notifications</h4>
        <p>รับแจ้งเตือนเมื่อเกิด event ต่างๆ ในระบบแบบ real-time ผ่าน HTTP POST</p>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">📬 Available Events</h3>

    <div class="wiki-grid wiki-grid-3">
        <div class="wiki-card">
            <div style="font-size: 2rem; margin-bottom: 1rem;">👤</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">User Events</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li><code>user.created</code></li>
                <li><code>user.updated</code></li>
                <li><code>user.deleted</code></li>
                <li><code>user.kyc.approved</code></li>
                <li><code>user.kyc.rejected</code></li>
            </ul>
        </div>

        <div class="wiki-card">
            <div style="font-size: 2rem; margin-bottom: 1rem;">🛒</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Order Events</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li><code>order.created</code></li>
                <li><code>order.paid</code></li>
                <li><code>order.shipped</code></li>
                <li><code>order.completed</code></li>
                <li><code>order.cancelled</code></li>
            </ul>
        </div>

        <div class="wiki-card">
            <div style="font-size: 2rem; margin-bottom: 1rem;">💰</div>
            <h4 style="font-weight: 700; margin-bottom: 1rem;">Commission Events</h4>
            <ul style="list-style: none; padding-left: 0; font-size: 0.85rem; line-height: 1.8;">
                <li><code>commission.earned</code></li>
                <li><code>commission.paid</code></li>
                <li><code>rank.upgraded</code></li>
                <li><code>bonus.achieved</code></li>
            </ul>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; font-weight: 700; margin: 2rem 0 1.5rem;">📝 Webhook Payload Example</h3>

    <div style="background: #1e293b; border-radius: 12px; padding: 1.5rem; overflow-x: auto;">
        <pre style="color: #e2e8f0; margin: 0; font-family: 'Fira Code', monospace; font-size: 0.85rem;"><code>{
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
        <ul style="line-height: 1.8;">
            <li><strong>Verify Signature:</strong> ตรวจสอบ HMAC signature ทุกครั้งเพื่อความปลอดภัย</li>
            <li><strong>Respond Quickly:</strong> ตอบกลับ 200 OK ภายใน 5 วินาที</li>
            <li><strong>Idempotency:</strong> จัดการ duplicate events ด้วย event_id</li>
            <li><strong>Retry Logic:</strong> ระบบจะ retry 3 ครั้งหาก endpoint ไม่ตอบกลับ</li>
        </ul>
    </div>
</section>

{{-- Third-party Integrations --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        🔌 Third-party Integrations
    </h2>

    <div class="wiki-grid wiki-grid-4">
        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">LINE</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Messaging API, Login, Pay</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>

        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💳</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">2C2P</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Payment Gateway</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>

        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📧</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">SendGrid</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Email Service</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>

        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">☁️</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">AWS S3</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">File Storage</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>

        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🤖</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">OpenAI</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">ChatGPT, DALL-E</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>

        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🌐</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Google Cloud</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Translate, Vision</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>

        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Flash Express</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Shipping API</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>

        <div class="wiki-card" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
            <h4 style="font-weight: 700; margin-bottom: 0.5rem;">Google Analytics</h4>
            <p style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Analytics</p>
            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Active</span>
        </div>
    </div>
</section>

{{-- SDKs & Libraries --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        📦 SDKs & Libraries
    </h2>

    <div class="wiki-grid wiki-grid-3">
        <div style="background: var(--wiki-card-bg); border: 2px solid #3178c6; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="font-size: 2rem;">📘</div>
                <div>
                    <h4 style="font-weight: 700; margin: 0;">JavaScript/TypeScript</h4>
                    <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">npm package</span>
                </div>
            </div>
            <div style="background: #1e293b; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                <code style="color: #e2e8f0; font-size: 0.85rem;">npm install @thaiprompt/sdk</code>
            </div>
            <a href="#" style="color: #3178c6; font-size: 0.9rem; text-decoration: none;">📚 Documentation →</a>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid #777BB4; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="font-size: 2rem;">🐘</div>
                <div>
                    <h4 style="font-weight: 700; margin: 0;">PHP</h4>
                    <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">Composer package</span>
                </div>
            </div>
            <div style="background: #1e293b; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                <code style="color: #e2e8f0; font-size: 0.85rem;">composer require thaiprompt/sdk</code>
            </div>
            <a href="#" style="color: #777BB4; font-size: 0.9rem; text-decoration: none;">📚 Documentation →</a>
        </div>

        <div style="background: var(--wiki-card-bg); border: 2px solid #3776AB; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="font-size: 2rem;">🐍</div>
                <div>
                    <h4 style="font-weight: 700; margin: 0;">Python</h4>
                    <span style="font-size: 0.85rem; color: var(--wiki-text-secondary);">pip package</span>
                </div>
            </div>
            <div style="background: #1e293b; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                <code style="color: #e2e8f0; font-size: 0.85rem;">pip install thaiprompt-sdk</code>
            </div>
            <a href="#" style="color: #3776AB; font-size: 0.9rem; text-decoration: none;">📚 Documentation →</a>
        </div>
    </div>
</section>

{{-- Rate Limiting & Errors --}}
<section class="wiki-section">
    <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--wiki-text);">
        ⚠️ Rate Limiting & Error Handling
    </h2>

    <div class="wiki-grid wiki-grid-2">
        <div>
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">📊 Rate Limits</h3>
            <table class="wiki-table">
                <thead>
                    <tr>
                        <th style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Plan</th>
                        <th style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Requests/min</th>
                        <th style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Burst</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Standard</td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">100</td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">200</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Business</td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">500</td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">1000</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Enterprise</td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Unlimited</td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Unlimited</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">❌ Error Codes</h3>
            <table class="wiki-table">
                <thead>
                    <tr>
                        <th style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Code</th>
                        <th style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);"><code>400</code></td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Bad Request</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);"><code>401</code></td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Unauthorized</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);"><code>403</code></td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Forbidden</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);"><code>429</code></td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Rate Limited</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);"><code>500</code></td>
                        <td style="padding: 0.75rem; border: 1px solid var(--wiki-border);">Server Error</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
