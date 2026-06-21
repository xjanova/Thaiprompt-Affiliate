{{-- หน้าจัดการช่องทางรับส่งข้อความ — ธีม V4 "นวลทองคำ" (Nuan Gold Clay) --}}
@extends('layouts.admin-v4')

@section('title', 'จัดการช่องทางดูดวง')

@section('content')
{{-- คอนเทนเนอร์หลัก: ผูก Alpine state เดิม (fortuneChannels) ทั้งหมด --}}
<div x-data="fortuneChannels()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · ช่องทางรับข้อความ
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                ช่องทางรับส่งข้อความ 📱
            </h1>
            <p class="tp-muted" style="font-size:12.5px; margin:6px 0 0;">
                เชื่อมต่อช่องทางต่างๆ เพื่อรับคำถามดูดวงจากผู้ใช้ — รองรับ Facebook Messenger และ LINE Official Account
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- ลิงก์ไปหน้าตั้งค่า (ห่อ Route::has กันพัง) --}}
            @if(Route::has('admin.fortune.settings.index'))
            <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-gear"></i>&nbsp; ไปหน้าตั้งค่า
            </a>
            @endif
        </div>
    </div>

    {{-- ===== KPI / สถานะช่องทาง (Facebook + LINE) ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:18px;">

        {{-- ---------- Facebook Messenger ---------- --}}
        <div class="tp-card" style="padding:22px; border-left:4px solid #5689b8;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:46px; height:46px; display:flex; align-items:center; justify-content:center; font-size:22px; color:#5689b8;">
                        <i class="fab fa-facebook-messenger"></i>
                    </div>
                    <div>
                        <div style="font-size:16px; font-weight:800; color:var(--ink);">Facebook Messenger</div>
                        @if($settings->hasFacebookConfigured())
                            <span class="tp-pill" style="margin-top:4px; color:#5aa07e; background:rgba(90,160,126,.12);">
                                <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#5aa07e; margin-right:5px;"></span>
                                เชื่อมต่อแล้ว
                            </span>
                        @else
                            <span class="tp-pill" style="margin-top:4px; color:#e0a52e; background:rgba(224,165,46,.12);">
                                <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#e0a52e; margin-right:5px;"></span>
                                ยังไม่ได้ตั้งค่า
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- สถิติ Facebook --}}
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;">
                <div class="tp-inset-sm" style="padding:12px 8px; text-align:center; border-radius:12px;">
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ number_format($stats['facebook']['total']) }}</div>
                    <div class="tp-muted" style="font-size:11px; margin-top:2px;">คำทำนาย</div>
                </div>
                <div class="tp-inset-sm" style="padding:12px 8px; text-align:center; border-radius:12px;">
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ number_format($stats['facebook']['unique_users']) }}</div>
                    <div class="tp-muted" style="font-size:11px; margin-top:2px;">ผู้ใช้</div>
                </div>
                <div class="tp-inset-sm" style="padding:12px 8px; text-align:center; border-radius:12px;">
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">฿{{ number_format($stats['facebook']['total_revenue'], 0) }}</div>
                    <div class="tp-muted" style="font-size:11px; margin-top:2px;">รายได้</div>
                </div>
            </div>

            {{-- ปุ่ม action Facebook --}}
            <div class="tp-divider" style="margin:16px 0 14px;"></div>
            <div style="display:flex; flex-wrap:wrap; gap:9px;">
                @if(Route::has('admin.fortune.settings.index'))
                <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-cog"></i>&nbsp; ตั้งค่า Facebook
                </a>
                @endif
                {{-- ตั้งค่าปุ่มเริ่มต้น Messenger (AJAX setupFacebookMessenger เดิม) --}}
                <button type="button" @click="setupFacebookMessenger()"
                        :disabled="setupingFacebook"
                        class="tp-btn tp-btn-sm"
                        style="opacity:1;" :style="setupingFacebook ? 'opacity:.55; cursor:not-allowed;' : ''">
                    <i class="fas" :class="setupingFacebook ? 'fa-spinner fa-spin' : 'fa-play-circle'"></i>&nbsp;
                    <span x-text="setupingFacebook ? 'กำลังตั้งค่า...' : 'ตั้งค่าปุ่มเริ่มต้น'"></span>
                </button>
            </div>
        </div>

        {{-- ---------- LINE Official Account ---------- --}}
        <div class="tp-card" style="padding:22px; border-left:4px solid #5aa07e;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:46px; height:46px; display:flex; align-items:center; justify-content:center; font-size:22px; color:#5aa07e;">
                        <i class="fab fa-line"></i>
                    </div>
                    <div>
                        <div style="font-size:16px; font-weight:800; color:var(--ink);">LINE Official Account</div>
                        @if($settings->line_enabled && $settings->hasLineConfigured())
                            <span class="tp-pill" style="margin-top:4px; color:#5aa07e; background:rgba(90,160,126,.12);">
                                <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#5aa07e; margin-right:5px;"></span>
                                เปิดใช้งาน
                            </span>
                        @elseif($settings->hasLineConfigured())
                            <span class="tp-pill" style="margin-top:4px; color:#e0a52e; background:rgba(224,165,46,.12);">
                                <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#e0a52e; margin-right:5px;"></span>
                                ตั้งค่าแล้ว (ยังไม่เปิด)
                            </span>
                        @else
                            <span class="tp-pill" style="margin-top:4px; color:#9a8f7c; background:rgba(154,143,124,.14);">
                                <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#9a8f7c; margin-right:5px;"></span>
                                ยังไม่ได้ตั้งค่า
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- สถิติ LINE --}}
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;">
                <div class="tp-inset-sm" style="padding:12px 8px; text-align:center; border-radius:12px;">
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ number_format($stats['line']['total']) }}</div>
                    <div class="tp-muted" style="font-size:11px; margin-top:2px;">คำทำนาย</div>
                </div>
                <div class="tp-inset-sm" style="padding:12px 8px; text-align:center; border-radius:12px;">
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ number_format($stats['line']['unique_users']) }}</div>
                    <div class="tp-muted" style="font-size:11px; margin-top:2px;">ผู้ใช้</div>
                </div>
                <div class="tp-inset-sm" style="padding:12px 8px; text-align:center; border-radius:12px;">
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">฿{{ number_format($stats['line']['total_revenue'], 0) }}</div>
                    <div class="tp-muted" style="font-size:11px; margin-top:2px;">รายได้</div>
                </div>
            </div>

            <div class="tp-divider" style="margin:16px 0 14px;"></div>
            <div class="tp-muted" style="font-size:12.5px;">
                <i class="fas fa-wand-magic-sparkles" style="color:var(--deep1); margin-right:5px;"></i>
                รองรับ Flex Message สวยงาม
            </div>
        </div>
    </div>

    {{-- ===== Cloudflare Workers AI (เจนภาพดวงประจำวัน) ===== --}}
    <div class="tp-card" style="padding:24px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:19px; color:#d6824a;">
                    <i class="fas fa-cloud"></i>
                </div>
                <div>
                    <div style="font-size:16px; font-weight:800; color:var(--ink);">
                        Cloudflare Workers AI
                        <span class="tp-muted" style="font-size:12px; font-weight:500;">(เจนภาพดวงประจำวัน)</span>
                    </div>
                    <p class="tp-muted" style="font-size:12px; margin:4px 0 0;">
                        ใช้ FLUX-1-schnell สร้างภาพประกอบโพสดวง — ฟรี ~40 ภาพ/วัน
                    </p>
                </div>
            </div>

            {{-- Status badge --}}
            @if(($cloudflareAi['configured'] ?? false))
                <span class="tp-pill" style="color:#5aa07e; background:rgba(90,160,126,.12);">
                    <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#5aa07e; margin-right:5px;"></span>
                    @if($cloudflareAi['using_env_fallback'] ?? false)
                        ใช้คีย์จาก .env (TPIX)
                    @else
                        เชื่อมต่อแล้ว
                    @endif
                </span>
            @else
                <span class="tp-pill" style="color:#e0a52e; background:rgba(224,165,46,.12);">
                    <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#e0a52e; margin-right:5px;"></span>
                    ยังไม่ได้ตั้งค่า
                </span>
            @endif
        </div>

        @if(! ($cloudflareAi['available'] ?? false))
            {{-- provider ไม่พร้อม --}}
            <div class="tp-inset" style="padding:14px 16px; border-radius:12px; border-left:4px solid #d9534f;">
                <p style="margin:0; font-size:13px; color:#d9534f;">
                    <i class="fas fa-triangle-exclamation"></i>
                    {{ $cloudflareAi['reason'] ?? 'Cloudflare AI provider ไม่พร้อม' }}
                </p>
            </div>
        @else
            {{-- ฟอร์มบันทึก Cloudflare AI: คง action/method/@csrf/@method('PUT') + field เดิม --}}
            <form action="{{ route('admin.fortune.channels.cloudflare-ai.update') }}" method="POST" style="display:flex; flex-direction:column; gap:16px;">
                @csrf
                @method('PUT')

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
                    {{-- Account ID --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            Account ID <span style="color:#d9534f;">*</span>
                        </label>
                        <div class="tp-well tp-input">
                            <input type="text" name="cloudflare_account_id"
                                   value="{{ old('cloudflare_account_id', $cloudflareAi['account_id'] ?? '') }}"
                                   placeholder="32-character hex ID"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:13.5px; font-family:ui-monospace,monospace;">
                        </div>
                        <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                            หาได้ที่ <a href="https://dash.cloudflare.com" target="_blank" style="color:var(--deep1); text-decoration:underline;">dash.cloudflare.com</a> → sidebar ขวา → Account ID
                        </p>
                    </div>

                    {{-- API Token --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            API Token <span style="color:#d9534f;">*</span>
                        </label>
                        <div class="tp-well tp-input">
                            <input type="password" name="cloudflare_api_token"
                                   placeholder="{{ ! empty($cloudflareAi['masked_token']) ? $cloudflareAi['masked_token'] . ' (กรอกใหม่เพื่อเปลี่ยน)' : 'กรอก API Token...' }}"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:13.5px; font-family:ui-monospace,monospace;">
                        </div>
                        <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                            <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" style="color:var(--deep1); text-decoration:underline;">สร้าง Token ใหม่</a> →
                            permissions: <code style="font-size:11px; background:var(--inset); padding:1px 5px; border-radius:5px;">Account.Workers AI:Read</code>
                        </p>
                    </div>
                </div>

                @if($cloudflareAi['using_env_fallback'] ?? false)
                    <div class="tp-inset" style="padding:12px 14px; border-radius:12px; border-left:4px solid #5689b8; font-size:12.5px; color:var(--ink2);">
                        <i class="fas fa-circle-info" style="color:#5689b8;"></i>
                        ขณะนี้ใช้คีย์จาก <code style="font-size:11px;">.env</code> (TPIX project) — ใส่ค่าด้านบนเพื่อ override
                    </div>
                @endif

                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                    <button type="submit" class="tp-btn tp-btn-primary tp-btn-sm">
                        <i class="fas fa-save"></i>&nbsp; บันทึก Cloudflare AI
                    </button>

                    {{-- ทดสอบการเชื่อมต่อ (AJAX testCloudflareAi เดิม) --}}
                    <button type="button" @click="testCloudflareAi()"
                            :disabled="testingCloudflare"
                            class="tp-btn tp-btn-sm"
                            :style="testingCloudflare ? 'opacity:.55; cursor:not-allowed;' : ''">
                        <i class="fas" :class="testingCloudflare ? 'fa-spinner fa-spin' : 'fa-plug'"></i>&nbsp;
                        <span x-text="testingCloudflare ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
                    </button>

                    <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank"
                       style="font-size:12.5px; color:var(--deep1); text-decoration:underline;">
                        <i class="fas fa-external-link-alt"></i>&nbsp; เปิด Cloudflare Dashboard
                    </a>
                </div>

                {{-- ผลทดสอบ Cloudflare --}}
                <div x-show="cloudflareTestResult" x-cloak
                     class="tp-inset" style="padding:12px 14px; border-radius:12px; font-size:13px;"
                     :style="cloudflareTestResult?.success
                        ? 'border-left:4px solid #5aa07e; color:#5aa07e;'
                        : 'border-left:4px solid #d9534f; color:#d9534f;'">
                    <i class="fas" :class="cloudflareTestResult?.success ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                    <span x-text="cloudflareTestResult?.message"></span>
                </div>

                {{-- คู่มือย่อ --}}
                <details class="tp-muted" style="font-size:12.5px;">
                    <summary style="cursor:pointer; font-weight:600; color:var(--ink2);">
                        <i class="fas fa-question-circle"></i>&nbsp; วิธีสร้าง API Token (3 ขั้นตอน)
                    </summary>
                    <ol style="margin:12px 0 0 6px; padding-left:18px; line-height:1.9;">
                        <li>เข้า <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" style="color:var(--deep1); text-decoration:underline;">Cloudflare API Tokens</a> → กด <strong>Create Token</strong></li>
                        <li>เลือก <strong>Custom Token</strong> → ใส่ชื่อ "Fortune AI" → Permissions: <code style="font-size:11px; background:var(--inset); padding:1px 5px; border-radius:5px;">Account › Workers AI › Read</code></li>
                        <li>กด Create → คัดลอก Token (เห็นครั้งเดียว!) → วางในช่องด้านบน + กรอก Account ID</li>
                    </ol>
                </details>
            </form>
        @endif
    </div>

    {{-- ===== ฟอร์มหลัก: ตั้งค่า LINE Official Account ===== --}}
    {{-- คง action/method/@csrf/@method('PUT') + ชื่อ field ทุกตัวตามของเดิม 100% --}}
    <form action="{{ route('admin.fortune.channels.update') }}" method="POST" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ---------- LINE Settings card ---------- --}}
        <div class="tp-card" style="padding:24px;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; display:flex; align-items:center; justify-content:center; font-size:19px; color:#5aa07e;">
                        <i class="fab fa-line"></i>
                    </div>
                    <div>
                        <div style="font-size:16px; font-weight:800; color:var(--ink);">LINE Official Account</div>
                        <p class="tp-muted" style="font-size:12px; margin:4px 0 0;">
                            เชื่อมต่อ LINE Official Account เพื่อรับคำถามดูดวง
                        </p>
                    </div>
                </div>
                {{-- Toggle เปิด/ปิด LINE (field name=line_enabled คงเดิม + x-model) --}}
                <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                    <input type="checkbox" name="line_enabled" value="1"
                           {{ $settings->line_enabled ? 'checked' : '' }}
                           x-model="lineEnabled"
                           style="position:absolute; opacity:0; width:0; height:0;">
                    <span style="width:52px; height:28px; border-radius:999px; display:inline-block; position:relative; transition:.25s;"
                          :style="lineEnabled
                             ? 'background:#5aa07e;'
                             : 'background:var(--inset);'">
                        <span style="position:absolute; top:3px; left:3px; width:22px; height:22px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.3); transition:.25s;"
                              :style="lineEnabled ? 'transform:translateX(24px);' : ''"></span>
                    </span>
                </label>
            </div>

            {{-- Webhook URL info --}}
            <div class="tp-inset" style="padding:16px; border-radius:12px; border-left:4px solid #5aa07e; margin-bottom:20px;">
                <div style="display:flex; align-items:flex-start; gap:11px;">
                    <i class="fas fa-link" style="color:#5aa07e; margin-top:3px;"></i>
                    <div style="flex:1; min-width:0;">
                        <p style="font-size:12.5px; font-weight:600; color:var(--ink); margin:0 0 7px;">
                            Webhook URL สำหรับ LINE Developers Console:
                        </p>
                        <div style="display:flex; align-items:center; gap:9px;">
                            <code id="line-webhook-url"
                                  style="flex:1; min-width:0; overflow-x:auto; white-space:nowrap; background:var(--surf); padding:9px 12px; border-radius:9px; font-size:12.5px; color:var(--ink);">{{ url('/webhook/line/fortune') }}</code>
                            {{-- คัดลอก URL (copyWebhookUrl เดิม) --}}
                            <button type="button" @click="copyWebhookUrl()" class="tp-icon-btn" title="คัดลอก URL">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LINE Channel fields --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px; margin-bottom:8px;">
                {{-- Channel ID --}}
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        Channel ID <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="line_channel_id"
                               value="{{ old('line_channel_id', $settings->line_channel_id) }}"
                               placeholder="1234567890"
                               style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                    </div>
                    <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                        ดูได้ที่ LINE Developers Console → Basic settings
                    </p>
                </div>

                {{-- Bot Basic ID --}}
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        Bot Basic ID <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="line_bot_basic_id"
                               value="{{ old('line_bot_basic_id', $settings->line_bot_basic_id) }}"
                               placeholder="@002dqcls"
                               style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                    </div>
                    <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                        LINE OA Basic ID (ขึ้นต้นด้วย @) → ใช้สร้างลิงก์เชิญเพื่อน https://line.me/R/ti/p/@xxx
                    </p>
                </div>

                {{-- Channel Secret --}}
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        Channel Secret <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input">
                        <input type="password" name="line_channel_secret"
                               value="{{ old('line_channel_secret', $settings->line_channel_secret) }}"
                               placeholder="••••••••••••••••"
                               style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                    </div>
                    <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                        ดูได้ที่ LINE Developers Console → Basic settings
                    </p>
                </div>

                {{-- Channel Access Token (เต็มความกว้าง) --}}
                <div style="grid-column:1 / -1;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        Channel Access Token (Long-lived) <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <textarea name="line_channel_access_token" rows="3"
                                  placeholder="กด Issue ที่ Messaging API settings"
                                  style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:13.5px; padding:11px 14px; resize:vertical; font-family:ui-monospace,monospace;">{{ old('line_channel_access_token', $settings->line_channel_access_token) }}</textarea>
                    </div>
                    <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                        กด "Issue" ที่ LINE Developers Console → Messaging API → Channel access token
                    </p>
                </div>
            </div>

            {{-- ปรับแต่ง Flex Message --}}
            <div class="tp-divider" style="margin:20px 0;"></div>
            <div class="tp-section-h" style="margin-bottom:16px;">
                <i class="fas fa-palette" style="color:var(--deep1);"></i>&nbsp; ปรับแต่ง Flex Message
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
                {{-- สีหลัก (Primary Color) --}}
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        สีหลัก (Primary Color)
                    </label>
                    <div style="display:flex; align-items:center; gap:11px;">
                        <input type="color" name="line_flex_primary_color"
                               value="{{ old('line_flex_primary_color', $settings->line_flex_primary_color ?? '#6B46C1') }}"
                               style="width:48px; height:48px; border-radius:12px; cursor:pointer; border:2px solid var(--sd); background:var(--surf); padding:2px;">
                        <div class="tp-well tp-input" style="flex:1;">
                            <input type="text" readonly :value="primaryColor"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px; font-family:ui-monospace,monospace;">
                        </div>
                    </div>
                    <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                        ใช้สำหรับปุ่มและส่วนเน้นใน Flex Message
                    </p>
                </div>

                {{-- รูป Welcome Message URL --}}
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        รูปภาพ Welcome Message (URL)
                    </label>
                    <div class="tp-well tp-input">
                        <input type="url" name="line_welcome_image_url"
                               value="{{ old('line_welcome_image_url', $settings->line_welcome_image_url) }}"
                               placeholder="https://example.com/image.jpg"
                               style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                    </div>
                    <p class="tp-muted" style="margin:6px 2px 0; font-size:11.5px;">
                        รูปภาพที่แสดงเมื่อผู้ใช้ Add Friend (แนะนำ: 1040x1040px)
                    </p>
                </div>
            </div>

            {{-- ทดสอบการเชื่อมต่อ LINE --}}
            <div class="tp-divider" style="margin:20px 0;"></div>
            <button type="button" @click="testLineConnection()"
                    :disabled="testingLine"
                    class="tp-btn tp-btn-primary"
                    :style="testingLine ? 'opacity:.55; cursor:not-allowed;' : ''">
                <i class="fas" :class="testingLine ? 'fa-spinner fa-spin' : 'fa-plug'"></i>&nbsp;
                <span x-text="testingLine ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ LINE'"></span>
            </button>

            {{-- ผลทดสอบ LINE --}}
            <div x-show="testResult" x-cloak
                 class="tp-inset" style="margin-top:16px; padding:14px 16px; border-radius:12px;"
                 :style="testResult?.success
                    ? 'border-left:4px solid #5aa07e;'
                    : 'border-left:4px solid #d9534f;'">
                <div style="display:flex; align-items:flex-start; gap:11px;">
                    <i class="fas" style="margin-top:2px;"
                       :class="testResult?.success ? 'fa-check-circle' : 'fa-exclamation-circle'"
                       :style="testResult?.success ? 'color:#5aa07e;' : 'color:#d9534f;'"></i>
                    <div>
                        <p style="margin:0; font-size:13.5px;"
                           :style="testResult?.success ? 'color:#5aa07e;' : 'color:#d9534f;'"
                           x-text="testResult?.message"></p>
                        <div x-show="testResult?.data?.bot_info" class="tp-muted" style="margin-top:8px; font-size:12.5px; line-height:1.7;">
                            <p style="margin:0;"><strong>Bot Name:</strong> <span x-text="testResult?.data?.bot_info?.displayName"></span></p>
                            <p style="margin:0;"><strong>User ID:</strong> <span x-text="testResult?.data?.bot_info?.userId"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- วิธีตั้งค่า LINE (ขั้นตอน 1-5) ---------- --}}
        <div class="tp-card" style="padding:24px;">
            <div class="tp-section-h" style="margin-bottom:18px;">
                <i class="fas fa-book" style="color:var(--deep1);"></i>&nbsp; วิธีตั้งค่า LINE Official Account
            </div>

            <div style="display:flex; flex-direction:column; gap:16px;">
                {{-- ขั้นตอน 1 --}}
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span class="tp-num" style="flex-shrink:0; width:26px; height:26px; border-radius:50%; background:var(--a1soft); color:var(--deep1); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">1</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--ink);">สมัคร LINE Official Account</p>
                        <p class="tp-muted" style="margin:3px 0 0; font-size:12.5px;">
                            ไปที่ <a href="https://manager.line.biz/" target="_blank" style="color:var(--deep1); text-decoration:underline;">LINE Official Account Manager</a> และสร้างบัญชีใหม่
                        </p>
                    </div>
                </div>

                {{-- ขั้นตอน 2 --}}
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span class="tp-num" style="flex-shrink:0; width:26px; height:26px; border-radius:50%; background:var(--a1soft); color:var(--deep1); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">2</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--ink);">เปิดใช้งาน Messaging API</p>
                        <p class="tp-muted" style="margin:3px 0 0; font-size:12.5px;">
                            ไปที่ Settings → Messaging API → Enable Messaging API
                        </p>
                    </div>
                </div>

                {{-- ขั้นตอน 3 --}}
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span class="tp-num" style="flex-shrink:0; width:26px; height:26px; border-radius:50%; background:var(--a1soft); color:var(--deep1); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">3</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--ink);">ดึงข้อมูล Channel จาก LINE Developers Console</p>
                        <p class="tp-muted" style="margin:3px 0 0; font-size:12.5px;">
                            ไปที่ <a href="https://developers.line.biz/console/" target="_blank" style="color:var(--deep1); text-decoration:underline;">LINE Developers Console</a> → เลือก Provider และ Channel
                        </p>
                    </div>
                </div>

                {{-- ขั้นตอน 4 --}}
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span class="tp-num" style="flex-shrink:0; width:26px; height:26px; border-radius:50%; background:var(--a1soft); color:var(--deep1); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">4</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--ink);">ตั้งค่า Webhook URL</p>
                        <p class="tp-muted" style="margin:3px 0 0; font-size:12.5px;">
                            ที่ Messaging API settings → ใส่ Webhook URL: <code style="font-size:11.5px; background:var(--inset); padding:1px 6px; border-radius:6px;">{{ url('/webhook/line/fortune') }}</code>
                        </p>
                    </div>
                </div>

                {{-- ขั้นตอน 5 --}}
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span class="tp-num" style="flex-shrink:0; width:26px; height:26px; border-radius:50%; background:var(--a1soft); color:var(--deep1); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">5</span>
                    <div>
                        <p style="margin:0; font-size:13.5px; font-weight:700; color:var(--ink);">เปิด Use webhook และปิด Auto-reply</p>
                        <p class="tp-muted" style="margin:3px 0 0; font-size:12.5px;">
                            ที่ Messaging API settings → เปิด "Use webhook" และปิด "Auto-reply messages" ที่ LINE Official Account Manager
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- ปุ่มบันทึก ---------- --}}
        <div style="display:flex; flex-wrap:wrap; justify-content:flex-end; gap:11px;">
            @if(Route::has('admin.fortune.settings.index'))
            <a href="{{ route('admin.fortune.settings.index') }}" class="tp-btn">
                ยกเลิก
            </a>
            @endif
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-save"></i>&nbsp; บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>
@endsection

{{-- scripts stack: คง Alpine state + AJAX endpoint/payload เดิมทุกตัว 100% --}}
@push('scripts')
<script>
function fortuneChannels() {
    return {
        lineEnabled: {{ $settings->line_enabled ? 'true' : 'false' }},
        primaryColor: '{{ $settings->line_flex_primary_color ?? '#6B46C1' }}',
        testingLine: false,
        testResult: null,
        setupingFacebook: false,
        facebookSetupResult: null,
        testingCloudflare: false,
        cloudflareTestResult: null,

        init() {
            // เฝ้าฟัง color input เพื่ออัพเดทพรีวิวสด
            const colorInput = document.querySelector('input[name="line_flex_primary_color"]');
            if (colorInput) {
                colorInput.addEventListener('input', (e) => {
                    this.primaryColor = e.target.value;
                });
            }
        },

        copyWebhookUrl() {
            const url = document.getElementById('line-webhook-url').textContent;
            navigator.clipboard.writeText(url).then(() => {
                // แจ้งเตือนผ่าน toast ของ layout V4
                this.$dispatch('notify', { type: 'success', message: 'คัดลอก URL แล้ว!' });
            });
        },

        async testLineConnection() {
            this.testingLine = true;
            this.testResult = null;

            try {
                const response = await fetch('{{ route('admin.fortune.channels.test-line') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                this.testResult = data;

            } catch (error) {
                this.testResult = {
                    success: false,
                    message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message
                };
            } finally {
                this.testingLine = false;
            }
        },

        async testCloudflareAi() {
            this.testingCloudflare = true;
            this.cloudflareTestResult = null;
            try {
                const response = await fetch('{{ route('admin.fortune.channels.test-cloudflare-ai') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });
                this.cloudflareTestResult = await response.json();
            } catch (error) {
                this.cloudflareTestResult = {
                    success: false,
                    message: 'เกิดข้อผิดพลาด: ' + error.message,
                };
            } finally {
                this.testingCloudflare = false;
            }
        },

        async setupFacebookMessenger() {
            this.setupingFacebook = true;
            this.facebookSetupResult = null;

            try {
                const response = await fetch('{{ route('admin.fortune.channels.setup-facebook-messenger') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                this.facebookSetupResult = data;

                if (data.success) {
                    alert('✅ ' + data.message + '\n\nผู้ใช้จะเห็นปุ่ม "เริ่มต้นใช้งาน" และเมนูถาวรใน Messenger แล้ว');
                } else {
                    alert('❌ ' + data.message);
                }

            } catch (error) {
                this.facebookSetupResult = {
                    success: false,
                    message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message
                };
                alert('❌ ' + this.facebookSetupResult.message);
            } finally {
                this.setupingFacebook = false;
            }
        }
    }
}
</script>
@endpush
