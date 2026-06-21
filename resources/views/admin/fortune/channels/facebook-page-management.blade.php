@extends('layouts.admin-v4')

@section('title', 'จัดการ Facebook Page Integration')

@section('content')
{{-- 📘 หน้าจัดการ Facebook Page Integration — ธีม V4 "นวลทองคำ"
     เชื่อมต่อ Page, ตั้งค่า Messenger Profile (Get Started/เมนู/welcome),
     ระบบ Messenger อัตโนมัติ, ตอบกลับ Comment, ข้อมูลธุรกิจ
     🔴 คง AJAX endpoint/payload/CSRF เดิม 100% — Alpine facebookPageMgmt() อยู่ใน scripts stack ตามเดิม
     ตัวแปรจาก controller: $settings, $fbStats, $commentStats --}}
<div style="display:flex;flex-direction:column;gap:18px;" x-data="facebookPageMgmt()">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · จัดการ Facebook Page
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fab fa-facebook" style="color:#5689b8;"></i> จัดการ Facebook Page Integration
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                เชื่อมต่อ Facebook Page เพื่อให้บริการดูดวงผ่าน Messenger และตอบกลับ Comment อัตโนมัติ
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @if(Route::has('admin.fortune.channels.index'))
                <a href="{{ route('admin.fortune.channels.index') }}" class="tp-btn">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            @endif
            @if(Route::has('admin.fortune.settings.index'))
                <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-gear"></i> ตั้งค่าการเชื่อมต่อ
                </a>
            @endif
        </div>
    </div>

    {{-- ===== Section 1: Pages List (pages_show_list) ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="fas fa-list" style="color:var(--accent1);"></i>
            <span>เลือก Facebook Page ที่ต้องการเชื่อมต่อ</span>
        </div>
        <p class="tp-muted" style="font-size:12px;margin:0 0 16px;">
            เลือก Page ที่คุณเป็นผู้จัดการเพื่อเชื่อมต่อกับระบบดูดวง
        </p>

        {{-- สถานะการเชื่อมต่อปัจจุบัน --}}
        @if($settings->facebook_page_id)
            <div class="tp-card tp-inset" style="border-left:3px solid #5aa07e;background:rgba(90,160,126,.06);margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#5aa07e;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <p style="margin:0;font-weight:600;color:#5aa07e;">เชื่อมต่อสำเร็จแล้ว</p>
                        <p class="tp-muted" style="margin:2px 0 0;font-size:13px;">Page ID: {{ $settings->facebook_page_id }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- รายการ Pages --}}
        <div class="tp-inset" style="border-radius:14px;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--sd);">
                <span style="font-size:13px;font-weight:600;color:var(--ink);">
                    <i class="fas fa-building" style="color:var(--accent1);margin-right:4px;"></i> Facebook Pages ของคุณ
                </span>
                <button type="button" @click="refreshPages()" class="tp-btn tp-btn-sm">
                    <i class="fas fa-sync-alt" :class="{'fa-spin': loadingPages}"></i> รีเฟรช
                </button>
            </div>

            {{-- Page List Items --}}
            <div>
                @if($settings->facebook_page_id)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:48px;height:48px;border-radius:50%;background:#5689b8;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0;">
                                <i class="fab fa-facebook-f"></i>
                            </div>
                            <div>
                                <p style="margin:0;font-weight:600;color:var(--ink);">
                                    {{ $settings->facebook_page_name ?? 'Facebook Page' }}
                                </p>
                                <p class="tp-muted" style="margin:2px 0 0;font-size:13px;">
                                    ID: {{ $settings->facebook_page_id }}
                                </p>
                            </div>
                        </div>
                        <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;">
                            <i class="fas fa-circle-check"></i> เชื่อมต่อแล้ว
                        </span>
                    </div>
                @else
                    <div style="padding:36px 16px;text-align:center;">
                        <i class="fab fa-facebook" style="font-size:40px;color:var(--ink2);opacity:.5;display:block;margin-bottom:12px;"></i>
                        <p style="margin:0;color:var(--ink);font-weight:600;">ยังไม่ได้เชื่อมต่อ Facebook Page</p>
                        <p class="tp-muted" style="margin:4px 0 0;font-size:13px;">กรุณาตั้งค่า Facebook App ID และ Page Token ในหน้าตั้งค่า</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ปุ่ม action --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
            @if(Route::has('admin.fortune.settings.index'))
                <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-primary tp-btn-sm">
                    <i class="fas fa-gear"></i> ตั้งค่าการเชื่อมต่อ
                </a>
            @endif
            <button type="button" @click="testConnection()" :disabled="testingConnection" class="tp-btn tp-btn-sm">
                <i class="fas" :class="testingConnection ? 'fa-spinner fa-spin' : 'fa-plug'"></i>
                <span x-text="testingConnection ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
            </button>
        </div>
    </div>

    {{-- ===== Section 2: Page Metadata (pages_manage_metadata) ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="fas fa-cogs" style="color:var(--accent1);"></i>
            <span>ตั้งค่า Page Metadata</span>
        </div>
        <p class="tp-muted" style="font-size:12px;margin:0 0 16px;">
            จัดการ Messenger Profile: ปุ่มเริ่มต้น, เมนู, ข้อความต้อนรับ
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
            {{-- Get Started Button --}}
            <div class="tp-card tp-inset">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:var(--a1soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-play-circle" style="color:var(--accent1);"></i>
                    </div>
                    <div>
                        <p style="margin:0;font-weight:600;color:var(--ink);">ปุ่มเริ่มต้น</p>
                        <p class="tp-muted" style="margin:0;font-size:11px;">Get Started Button</p>
                    </div>
                </div>
                <p class="tp-muted" style="font-size:13px;margin:0 0 12px;">
                    ปุ่มเริ่มต้นใช้งานที่แสดงเมื่อผู้ใช้เปิดแชทครั้งแรก
                </p>
                <button type="button" @click="setupMessengerProfile()" :disabled="settingUpProfile"
                        class="tp-btn tp-btn-primary tp-btn-sm" style="width:100%;justify-content:center;">
                    <i class="fas" :class="settingUpProfile ? 'fa-spinner fa-spin' : 'fa-magic'"></i>
                    <span x-text="settingUpProfile ? 'กำลังตั้งค่า...' : 'ตั้งค่า Messenger Profile'"></span>
                </button>
            </div>

            {{-- Persistent Menu --}}
            <div class="tp-card tp-inset">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:var(--a2soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-bars" style="color:#b79ae8;"></i>
                    </div>
                    <div>
                        <p style="margin:0;font-weight:600;color:var(--ink);">เมนูถาวร</p>
                        <p class="tp-muted" style="margin:0;font-size:11px;">Persistent Menu</p>
                    </div>
                </div>
                <p class="tp-muted" style="font-size:13px;margin:0 0 12px;">
                    เมนูลัดแสดงเสมอในแชท เช่น ดูดวง, ถามคำถาม
                </p>
                <div style="font-size:13px;color:#5aa07e;">
                    <i class="fas fa-circle-check"></i> ตั้งค่าพร้อมกับ Messenger Profile
                </div>
            </div>

            {{-- Greeting Text --}}
            <div class="tp-card tp-inset">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(214,130,74,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-comment-dots" style="color:#d6824a;"></i>
                    </div>
                    <div>
                        <p style="margin:0;font-weight:600;color:var(--ink);">ข้อความต้อนรับ</p>
                        <p class="tp-muted" style="margin:0;font-size:11px;">Greeting Text</p>
                    </div>
                </div>
                <p class="tp-muted" style="font-size:13px;margin:0 0 12px;">
                    ข้อความแรกที่แสดงเมื่อผู้ใช้เปิดแชท
                </p>
                <div class="tp-inset-sm" style="border-radius:10px;padding:10px 12px;font-size:13px;color:var(--ink);">
                    {{ Str::limit($settings->welcome_message ?? 'สวัสดี! ยินดีต้อนรับสู่ระบบดูดวง', 60) }}
                </div>
            </div>
        </div>

        {{-- Webhook Configuration --}}
        <div class="tp-divider" style="margin:18px 0;"></div>
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <i class="fas fa-link" style="color:var(--accent1);"></i>
            <span>Webhook Configuration</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;">
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">Callback URL</label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <code class="tp-inset-sm" style="flex:1;border-radius:10px;padding:10px 12px;font-size:13px;color:var(--ink);overflow-x:auto;white-space:nowrap;">
                        {{ url('/webhook/facebook') }}
                    </code>
                    <button type="button" @click="copyToClipboard('{{ url('/webhook/facebook') }}')" class="tp-icon-btn">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">Verify Token</label>
                <code class="tp-inset-sm" style="display:block;border-radius:10px;padding:10px 12px;font-size:13px;color:var(--ink);overflow-x:auto;white-space:nowrap;">
                    {{ $settings->facebook_verify_token ?? 'ยังไม่ได้ตั้งค่า' }}
                </code>
            </div>
        </div>
        <div style="margin-top:14px;">
            <label class="tp-muted" style="display:block;font-size:12px;margin-bottom:6px;">Subscribed Events</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach(['messages', 'messaging_postbacks', 'feed', 'message_echoes'] as $event)
                    <span class="tp-pill tp-pill-soft" style="font-size:12px;color:#5689b8;">
                        <i class="fas fa-check" style="font-size:10px;"></i> {{ $event }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== Section 3: Messaging (pages_messaging) ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="fas fa-comment-alt" style="color:var(--accent1);"></i>
            <span>ระบบส่งข้อความอัตโนมัติ (Messenger)</span>
        </div>
        <p class="tp-muted" style="font-size:12px;margin:0 0 16px;">
            ตอบกลับข้อความผ่าน Messenger โดยอัตโนมัติด้วย AI ดูดวง
        </p>

        {{-- สถิติ Messaging (KPI) --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:18px;">
            <div class="tp-card tp-tile">
                <div class="tp-muted" style="font-size:12px;">คำทำนายทั้งหมด</div>
                <div class="tp-num" style="font-size:28px;line-height:1.1;margin-top:4px;color:#5689b8;">{{ number_format($fbStats['total_readings']) }}</div>
            </div>
            <div class="tp-card tp-tile">
                <div class="tp-muted" style="font-size:12px;">คำทำนายเชิงลึก</div>
                <div class="tp-num" style="font-size:28px;line-height:1.1;margin-top:4px;color:#b79ae8;">{{ number_format($fbStats['total_deep']) }}</div>
            </div>
            <div class="tp-card tp-tile">
                <div class="tp-muted" style="font-size:12px;">ผู้ใช้ที่ไม่ซ้ำ</div>
                <div class="tp-num" style="font-size:28px;line-height:1.1;margin-top:4px;color:#5aa07e;">{{ number_format($fbStats['unique_users']) }}</div>
            </div>
            <div class="tp-card tp-tile">
                <div class="tp-muted" style="font-size:12px;">รายได้</div>
                <div class="tp-num" style="font-size:28px;line-height:1.1;margin-top:4px;color:#e0a52e;">฿{{ number_format($fbStats['total_revenue'], 0) }}</div>
            </div>
        </div>

        {{-- Message Flow Demo --}}
        <div class="tp-inset" style="border-radius:14px;padding:16px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-project-diagram" style="color:var(--accent1);"></i>
                <span>Flow การทำงานของ Messenger Bot</span>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
                <div class="tp-pill tp-pill-soft" style="font-size:13px;padding:10px 14px;">
                    <i class="fas fa-user" style="color:#5689b8;"></i> ผู้ใช้ส่งข้อความ
                </div>
                <i class="fas fa-arrow-right tp-muted"></i>
                <div class="tp-pill tp-pill-soft" style="font-size:13px;padding:10px 14px;">
                    <i class="fas fa-robot" style="color:#b79ae8;"></i> AI วิเคราะห์คำถาม
                </div>
                <i class="fas fa-arrow-right tp-muted"></i>
                <div class="tp-pill tp-pill-soft" style="font-size:13px;padding:10px 14px;">
                    <i class="fas fa-star" style="color:#5aa07e;"></i> ส่งคำทำนายกลับ
                </div>
                <i class="fas fa-arrow-right tp-muted"></i>
                <div class="tp-pill tp-pill-soft" style="font-size:13px;padding:10px 14px;">
                    <i class="fas fa-coins" style="color:#e0a52e;"></i> ชำระเงิน (เชิงลึก)
                </div>
            </div>
        </div>

        {{-- Recent Readings --}}
        @if($fbStats['recent_readings']->count() > 0)
            <div class="tp-divider" style="margin:18px 0;"></div>
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-history" style="color:var(--accent1);"></i>
                <span>คำทำนายล่าสุดผ่าน Messenger</span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0 8px;font-size:13px;min-width:560px;">
                    <thead>
                        <tr style="color:var(--ink2);text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">
                            <th style="padding:6px 12px;">ผู้ใช้</th>
                            <th style="padding:6px 12px;">ประเภท</th>
                            <th style="padding:6px 12px;">สถานะ</th>
                            <th style="padding:6px 12px;">วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fbStats['recent_readings'] as $reading)
                            <tr style="box-shadow:var(--inset-sm);border-radius:14px;">
                                <td style="padding:12px;color:var(--ink);border-radius:14px 0 0 14px;">
                                    {{ Str::limit($reading->platform_user_id, 15) }}
                                </td>
                                <td style="padding:12px;">
                                    @if($reading->is_deep_reading)
                                        <span class="tp-pill tp-pill-soft" style="color:#b79ae8;font-size:12px;"><i class="fas fa-gem"></i> เชิงลึก</span>
                                    @else
                                        <span class="tp-pill tp-pill-soft" style="color:#5689b8;font-size:12px;"><i class="fas fa-star"></i> พื้นฐาน</span>
                                    @endif
                                </td>
                                <td style="padding:12px;">
                                    <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;font-size:12px;">
                                        <i class="fas fa-circle-check"></i> ส่งแล้ว
                                    </span>
                                </td>
                                <td style="padding:12px;color:var(--ink2);border-radius:0 14px 14px 0;">
                                    {{ $reading->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ===== Section 4: Comment Engagement (pages_read_engagement) ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="fas fa-comments" style="color:var(--accent1);"></i>
            <span>ระบบตอบกลับ Comment อัตโนมัติ</span>
        </div>
        <p class="tp-muted" style="font-size:12px;margin:0 0 16px;">
            อ่าน Comment บน Page และทักแชทผู้ใช้ที่คอมเม้นต์โดยอัตโนมัติ
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-bottom:18px;">
            {{-- การตั้งค่า --}}
            <div>
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <i class="fas fa-sliders-h" style="color:var(--accent1);"></i>
                    <span>การตั้งค่า</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span style="font-size:13px;color:var(--ink);">ตอบกลับ Comment อัตโนมัติ</span>
                        @if($settings->comment_engagement_enabled)
                            <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;font-size:12px;">
                                <i class="fas fa-toggle-on"></i> เปิด
                            </span>
                        @else
                            <span class="tp-pill tp-pill-soft" style="color:var(--ink2);font-size:12px;">
                                <i class="fas fa-toggle-off"></i> ปิด
                            </span>
                        @endif
                    </div>
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span style="font-size:13px;color:var(--ink);">โหมดการตอบ</span>
                        <span style="font-size:13px;color:#5689b8;font-weight:600;">
                            {{ $settings->comment_engagement_mode ?? 'reply_and_dm' }}
                        </span>
                    </div>
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span style="font-size:13px;color:var(--ink);">ทักแชทส่วนตัวอัตโนมัติ</span>
                        <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;font-size:12px;">
                            <i class="fas fa-circle-check"></i> เปิดใช้งาน
                        </span>
                    </div>
                </div>
            </div>

            {{-- สถิติ Comment Engagement --}}
            <div>
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <i class="fas fa-chart-bar" style="color:var(--accent1);"></i>
                    <span>สถิติ Comment Engagement</span>
                </div>
                @if(!empty($commentStats))
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                            <span style="font-size:13px;color:var(--ink);">Comment ที่รับมา</span>
                            <span class="tp-num" style="font-weight:700;color:#5689b8;">{{ number_format($commentStats['total'] ?? 0) }}</span>
                        </div>
                        <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                            <span style="font-size:13px;color:var(--ink);">ตอบกลับแล้ว</span>
                            <span class="tp-num" style="font-weight:700;color:#5aa07e;">{{ number_format($commentStats['replied'] ?? 0) }}</span>
                        </div>
                        <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                            <span style="font-size:13px;color:var(--ink);">ทักแชทส่วนตัวแล้ว</span>
                            <span class="tp-num" style="font-weight:700;color:#b79ae8;">{{ number_format($commentStats['dm_sent'] ?? 0) }}</span>
                        </div>
                    </div>
                @else
                    <div class="tp-inset" style="border-radius:14px;text-align:center;padding:28px 16px;">
                        <i class="fas fa-chart-line" style="font-size:30px;color:var(--ink2);opacity:.5;display:block;margin-bottom:8px;"></i>
                        <p class="tp-muted" style="margin:0;font-size:13px;">ยังไม่มีข้อมูลสถิติ</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Comment Reply Templates --}}
        <div class="tp-inset" style="border-radius:14px;padding:16px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-envelope-open-text" style="color:var(--accent1);"></i>
                <span>ข้อความที่ใช้ตอบกลับ</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
                <div class="tp-inset-sm" style="border-radius:12px;padding:12px 14px;">
                    <label class="tp-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;letter-spacing:.04em;">ตอบกลับใต้ Comment</label>
                    <p style="margin:6px 0 0;font-size:13px;color:var(--ink);">
                        {{ $settings->comment_reply_template ?? 'ขอบคุณที่สนใจค่ะ ทักแชทมาเพื่อดูดวงได้เลยนะคะ' }}
                    </p>
                </div>
                <div class="tp-inset-sm" style="border-radius:12px;padding:12px 14px;">
                    <label class="tp-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;letter-spacing:.04em;">ทักแชทส่วนตัว (DM)</label>
                    <p style="margin:6px 0 0;font-size:13px;color:var(--ink);">
                        {{ $settings->comment_dm_template ?? 'สวัสดีค่ะ เห็นว่าสนใจดูดวง พิมพ์วันเกิดมาได้เลยนะคะ' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Comment Flow --}}
        <div class="tp-inset" style="border-radius:14px;padding:16px;margin-top:14px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-project-diagram" style="color:#d6824a;"></i>
                <span>Flow: Comment → Auto DM</span>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
                <div class="tp-card tp-raise" style="display:flex;align-items:center;gap:8px;padding:10px 14px;font-size:13px;color:var(--ink);">
                    <i class="fas fa-comment" style="color:#d6824a;"></i> ผู้ใช้ Comment
                </div>
                <i class="fas fa-arrow-right tp-muted"></i>
                <div class="tp-card tp-raise" style="display:flex;align-items:center;gap:8px;padding:10px 14px;font-size:13px;color:var(--ink);">
                    <i class="fas fa-robot" style="color:#5689b8;"></i> Webhook รับ Event
                </div>
                <i class="fas fa-arrow-right tp-muted"></i>
                <div class="tp-card tp-raise" style="display:flex;align-items:center;gap:8px;padding:10px 14px;font-size:13px;color:var(--ink);">
                    <i class="fas fa-reply" style="color:#5aa07e;"></i> ตอบ Comment
                </div>
                <i class="fas fa-arrow-right tp-muted"></i>
                <div class="tp-card tp-raise" style="display:flex;align-items:center;gap:8px;padding:10px 14px;font-size:13px;color:var(--ink);">
                    <i class="fas fa-paper-plane" style="color:#b79ae8;"></i> ทักแชท DM ทันที
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Section 5: Business Management (business_management) ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <i class="fas fa-building" style="color:var(--accent1);"></i>
            <span>การจัดการธุรกิจ</span>
        </div>
        <p class="tp-muted" style="font-size:12px;margin:0 0 16px;">
            ข้อมูลธุรกิจและการเชื่อมต่อกับ Facebook Business Manager
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;">
            {{-- ข้อมูล App --}}
            <div>
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <i class="fas fa-info-circle" style="color:var(--accent1);"></i>
                    <span>ข้อมูล App</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span class="tp-muted" style="font-size:13px;">App ID</span>
                        <span style="font-size:13px;font-family:monospace;color:var(--ink);">{{ $settings->facebook_app_id ?? 'ไม่ได้ตั้งค่า' }}</span>
                    </div>
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span class="tp-muted" style="font-size:13px;">Page ID</span>
                        <span style="font-size:13px;font-family:monospace;color:var(--ink);">{{ $settings->facebook_page_id ?? 'ไม่ได้ตั้งค่า' }}</span>
                    </div>
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span class="tp-muted" style="font-size:13px;">สถานะ</span>
                        @if($settings->facebook_page_token)
                            <span class="tp-pill" style="background:rgba(90,160,126,.16);color:#5aa07e;font-size:12px;"><i class="fas fa-circle-check"></i> เชื่อมต่อแล้ว</span>
                        @else
                            <span class="tp-pill" style="background:rgba(224,165,46,.16);color:#e0a52e;font-size:12px;"><i class="fas fa-triangle-exclamation"></i> รอตั้งค่า</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ความปลอดภัย --}}
            <div>
                <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <i class="fas fa-shield-alt" style="color:var(--accent1);"></i>
                    <span>ความปลอดภัย</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span class="tp-muted" style="font-size:13px;">Webhook Signature</span>
                        <span style="font-size:13px;color:#5aa07e;"><i class="fas fa-circle-check"></i> เปิดใช้งาน (SHA256)</span>
                    </div>
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span class="tp-muted" style="font-size:13px;">Admin Handover</span>
                        <span style="font-size:13px;color:{{ ($settings->admin_handover_enabled ?? false) ? '#5aa07e' : 'var(--ink2)' }};">
                            {{ ($settings->admin_handover_enabled ?? false) ? 'เปิด' : 'ปิด' }}
                        </span>
                    </div>
                    <div class="tp-inset-sm" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;">
                        <span class="tp-muted" style="font-size:13px;">Graph API Version</span>
                        <span style="font-size:13px;color:var(--ink);">v21.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- scripts stack: Alpine facebookPageMgmt() — คง AJAX endpoint/payload/CSRF เดิม 100%
     ไม่ใช้ alertMessage box เดิมแล้ว → เปลี่ยนเป็น toast ของ layout admin-v4 ($dispatch notify) --}}
@push('scripts')
<script>
function facebookPageMgmt() {
    return {
        loadingPages: false,
        testingConnection: false,
        settingUpProfile: false,

        // รีเฟรช Page list
        refreshPages() {
            this.loadingPages = true;
            setTimeout(() => {
                this.loadingPages = false;
                this.showAlert('รีเฟรชรายการ Pages สำเร็จ', 'success');
            }, 1500);
        },

        // ทดสอบการเชื่อมต่อ
        async testConnection() {
            this.testingConnection = true;
            try {
                const response = await fetch('{{ route("admin.fortune.channels.test-facebook") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.showAlert(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.testingConnection = false;
        },

        // ตั้งค่า Messenger Profile
        async setupMessengerProfile() {
            this.settingUpProfile = true;
            try {
                const response = await fetch('{{ route("admin.fortune.channels.setup-facebook-messenger") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.showAlert(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                this.showAlert('เกิดข้อผิดพลาด', 'error');
            }
            this.settingUpProfile = false;
        },

        // คัดลอกข้อความ
        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            this.showAlert('คัดลอกแล้ว', 'success');
        },

        // แสดง Alert ผ่าน toast ของ layout admin-v4
        showAlert(message, type) {
            this.$dispatch('notify', { type: type === 'error' ? 'error' : 'success', message: message });
        }
    };
}
</script>
@endpush
@endsection
