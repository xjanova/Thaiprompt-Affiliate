# Social Cross-Poster System (Plan for Next Session)

> **Status**: 📝 Planned, NOT yet implemented
> **Owner**: Admin (เราใช้เอง — MVP scope)
> **Created**: 2026-05-16
> **Source session**: User requested ระบบโพสเดียวกระจายหลายช่อง — FB ↔ TikTok / YouTube / LINE VOOM

---

## 🎯 เป้าหมาย (Why)

ตอนนี้ทำคอนเทนต์ (รีล, โพสปกติ) ต้อง upload ทีละ platform → เสียเวลา + พลาดบ่อย
ต้องการ: เขียน/อัปโหลดที่เดียว → กระจายไป YouTube + TikTok + (อนาคต: LINE VOOM, IG)

## ✅ Scope MVP (ยืนยันแล้ว)

| ด้าน | สิ่งที่ทำ | สิ่งที่ไม่ทำ |
|------|----------|------------|
| **Target user** | Admin เราใช้เอง (1-3 บัญชี) | Multi-tenant (ทุก affiliate user) — phase 2 |
| **Source** | (a) Composer ในระบบ, (b) Poll FB page **แม่หมอจันทรา (holyzonethailand)** ที่ใช้กับ Fortune bot อยู่แล้ว | Webhook FB (ต้อง app review เพิ่ม) |
| **Destinations** | **Facebook + YouTube + TikTok** (composer ลง 3 ที่พร้อมกัน) | LINE VOOM (ไม่มี API), Instagram, X/Twitter |
| **Content types** | Video (รีล/Shorts) + รูป + ข้อความ | Live, Stories, Polls |
| **Scheduling** | Schedule + immediate | Recurring (รายสัปดาห์), AI auto-generate |
| **Analytics** | Per-channel status (published/failed) | Reach/views/engagement metrics |

---

## 🔗 ข้อมูล Page/App ที่ตัดสินใจแล้ว (2026-05-16)

| สิ่ง | ค่า | หมายเหตุ |
|------|-----|---------|
| **FB Page** | แม่หมอจันทราพยากรณ์ (facebook.com/holyzonethailand) | เพจเดียวกับ Fortune bot — token ปัจจุบันเก็บใน `fortune_telling_settings.facebook_page_token` |
| **FB App** | 🆕 **สร้างใหม่แยก** ("Thaiprompt Social Poster") | ไม่ reuse Fortune App — แม้จะ delay app review +2-4 wk; เหตุผล: isolation (Fortune App ban → ระบบนี้ไม่กระทบ) |
| **FB Page Token** | จะได้ใหม่จาก OAuth ของ App ใหม่ | ระบบใหม่ขอ token ของตัวเองผ่าน Facebook Login flow (ไม่อ่าน fortune_telling_settings) |
| **YouTube** | สร้าง Google Cloud project ใหม่ + channel ของ admin | ต้อง quota raise ถ้าโพส >6/วัน |
| **TikTok** | สร้าง TikTok Dev app ใหม่ + เชื่อม TikTok account ของ admin | Sandbox ทดสอบก่อน, รอ audit 2-4 wk สำหรับ production |

---

## ⚠️ ข้อจำกัด API ที่ต้องรู้ก่อนเขียน

### YouTube Data API v3
- Endpoint: `videos.insert` (resumable upload)
- Scope: `https://www.googleapis.com/auth/youtube.upload`
- **Quota**: 1 upload = **1,600 units**; default 10,000/day = **6 uploads/วัน**
  - ถ้าโพส >6 คลิป/วัน → ต้อง request quota raise (ฟรี แต่รอ Google approve 1-3 สัปดาห์)
- ไฟล์: max 256 GB, format MP4/MOV/AVI
- **Shorts**: คลิป ≤60 วินาที + อัตราส่วน 9:16 → YouTube จะ tag เป็น Shorts อัตโนมัติ ไม่ต้องเรียก API พิเศษ
- Required fields: title, categoryId (22 = People & Blogs default), description
- OAuth: standard Google OAuth 2.0, refresh token ยาว (≥6 เดือน)

### TikTok Content Posting API
- Endpoint: `POST /v2/post/publish/video/init/` → upload → `POST /v2/post/publish/status/fetch/`
- Scopes: `video.upload` + `video.publish` (ต้องขอเพิ่มทั้งสอง)
- **App Review**: ต้องผ่าน audit ก่อน production (สมัครที่ developers.tiktok.com)
  - Sandbox mode ใช้ทดสอบกับ test users ได้ก่อน
  - Production approval: 2-4 สัปดาห์ ต้องส่ง use case + privacy policy + demo video
- **ห้ามมี FB watermark** — TikTok auto-reject. ถ้า source = FB Reel ต้องดาวน์โหลดต้นฉบับก่อน upload (ไม่ใช่ public URL)
- Required: video URL หรือ direct upload (ใช้ chunked upload สำหรับไฟล์ >5MB)
- Title (caption) max 2,200 chars
- Refresh token: 365 วัน (ยาว)

### Facebook Graph API (อ่าน source)
- Endpoint: `GET /{page-id}/posts?fields=id,message,full_picture,attachments,created_time`
- Scopes: `pages_read_engagement` + `pages_manage_posts` (ถ้าจะโพสกลับด้วย)
- Page Access Token (long-lived, 60 วัน → ต้อง refresh)
- **Reels**: `GET /{page-id}/video_reels` (แยก endpoint)
- Polling interval: ทุก 5-15 นาที (อย่าเร็วกว่านี้ — rate limit)
- ⚠️ Reel ที่ download ผ่าน API URL จะมี FB watermark → ใช้ TikTok ไม่ได้
  - **Workaround**: เก็บไฟล์ต้นฉบับใน composer (ผู้ใช้ upload ตอนเขียน) แล้ว FB poll ใช้แค่ดู metadata

### LINE VOOM
- 🚫 **ไม่มี public posting API** — confirmed via developers.line.biz docs
- ตัวเลือก: ใช้ headless browser (Puppeteer) → เปราะ, อาจผิด ToS, ต้องคน maintain
- **Decision**: skip LINE VOOM ใน MVP — เพิ่มกลับเมื่อ LINE เปิด API หรือใช้คนโพสเอง

---

## 🗄️ Database Schema

### Migration 1: `social_channels`

```php
Schema::create('social_channels', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')
        ->constrained('users')
        ->onDelete('cascade');

    // ช่องทาง
    $table->enum('platform', ['facebook', 'youtube', 'tiktok', 'line_voom']);
    $table->string('account_label', 100);                  // ชื่อแสดง (e.g. "ช่อง Fortune YT")
    $table->string('external_id', 200);                    // page_id / channel_id / open_id
    $table->string('external_name', 200)->nullable();      // ชื่อจริงจาก platform

    // Token (encrypted via Crypt facade)
    $table->text('access_token');                          // encrypted
    $table->text('refresh_token')->nullable();             // encrypted
    $table->timestamp('access_token_expires_at')->nullable();
    $table->timestamp('refresh_token_expires_at')->nullable();
    $table->json('scopes')->nullable();                    // ["video.upload","video.publish"]

    // Metadata (per-platform JSON)
    $table->json('metadata')->nullable();
    // FB: {page_access_token, category, fan_count}
    // YT: {channel_id, default_category_id, default_privacy}
    // TT: {open_id, union_id, avatar_url, display_name}

    // Status
    $table->enum('status', ['active', 'disconnected', 'token_expired', 'revoked'])
        ->default('active');
    $table->timestamp('last_used_at')->nullable();
    $table->text('last_error')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->unique(['user_id', 'platform', 'external_id'], 'social_channels_unique_idx');
    $table->index(['user_id', 'platform', 'status']);
});
```

### Migration 2: `social_posts`

```php
Schema::create('social_posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

    // เนื้อหา
    $table->string('title', 300)->nullable();              // YouTube title / TikTok caption
    $table->text('caption');                                // คำบรรยายหลัก
    $table->text('hashtags')->nullable();                   // "#x #y" — append ตาม platform
    $table->enum('content_type', ['text', 'image', 'video', 'reel']);

    // Media
    $table->string('media_path', 500)->nullable();         // storage path ต้นฉบับ
    $table->string('thumbnail_path', 500)->nullable();
    $table->integer('video_duration_seconds')->nullable();
    $table->string('aspect_ratio', 10)->nullable();        // "9:16" / "16:9" / "1:1"

    // Source tracking (ถ้ามาจาก FB poll)
    $table->foreignId('source_channel_id')
        ->nullable()
        ->constrained('social_channels')
        ->nullOnDelete();
    $table->string('source_external_id', 200)->nullable(); // FB post ID
    $table->json('source_payload')->nullable();             // raw FB response

    // Scheduling
    $table->enum('status', [
        'draft', 'scheduled', 'publishing', 'published', 'partial', 'failed'
    ])->default('draft');
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('published_at')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'status', 'scheduled_at']);
    $table->index('source_external_id');
});
```

### Migration 3: `social_post_targets`

```php
Schema::create('social_post_targets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')
        ->constrained('social_posts')
        ->onDelete('cascade');
    $table->foreignId('channel_id')
        ->constrained('social_channels')
        ->onDelete('cascade');

    $table->enum('status', [
        'pending', 'queued', 'uploading', 'processing',
        'published', 'failed', 'cancelled'
    ])->default('pending');

    // Per-target overrides
    $table->string('platform_caption_override', 2200)->nullable();
    $table->json('platform_options')->nullable();
    // YT: {privacy_status, category_id, made_for_kids, tags}
    // TT: {disable_comment, disable_duet, disable_stitch, privacy_level}
    // FB: {targeting, place_id}

    // Result
    $table->string('platform_post_id', 200)->nullable();   // YT video ID, TT post ID
    $table->string('platform_url', 500)->nullable();
    $table->timestamp('published_at')->nullable();

    // Retry / error
    $table->unsignedTinyInteger('attempts')->default(0);
    $table->timestamp('next_retry_at')->nullable();
    $table->text('last_error')->nullable();
    $table->json('error_history')->nullable();

    $table->timestamps();

    $table->unique(['post_id', 'channel_id']);
    $table->index(['status', 'next_retry_at']);
});
```

---

## 🧩 Models + Relationships

```
app/Models/Social/
├── SocialChannel.php
│   - belongsTo(User)
│   - hasMany(SocialPostTarget)
│   - scopeActive, scopeForPlatform
│   - getAccessTokenAttribute / setAccessTokenAttribute (encrypt/decrypt)
│
├── SocialPost.php
│   - belongsTo(User)
│   - belongsTo(SocialChannel, 'source_channel_id')
│   - hasMany(SocialPostTarget, 'post_id')
│   - hasManyThrough(SocialChannel, SocialPostTarget) → targets()->pluck('channel')
│
└── SocialPostTarget.php
    - belongsTo(SocialPost, 'post_id')
    - belongsTo(SocialChannel, 'channel_id')
    - scopePending, scopeRetryable
```

---

## 🔌 Service Layer

```
app/Services/Social/
├── Contracts/
│   ├── SocialPlatformInterface.php
│   │   - publishPost(SocialPostTarget): array  // ['platform_post_id'=>'', 'url'=>'']
│   │   - refreshToken(SocialChannel): SocialChannel
│   │   - exchangeAuthCode(string $code): array  // tokens
│   │   - getAuthorizationUrl(string $state): string
│   │   - revoke(SocialChannel): void
│   │
│   └── SocialSourceInterface.php
│       - fetchRecentPosts(SocialChannel, since): array
│
├── Connectors/
│   ├── FacebookConnector.php     (implements both interfaces)
│   ├── YouTubeConnector.php      (uses google/apiclient package)
│   └── TikTokConnector.php       (HTTP via Guzzle)
│
├── SocialPublisherService.php
│   - fanOut(SocialPost): void   // create targets + dispatch jobs
│   - publish(SocialPostTarget): array  // dispatch to correct connector
│   - retry(SocialPostTarget): void
│
├── SocialTokenManager.php
│   - getValidToken(SocialChannel): string  // auto-refresh if expired
│   - encrypt/decrypt helpers
│
└── SocialSourceSyncService.php
    - pullFromAllChannels(): void  // called by scheduler
```

### Connector pattern (ตัวอย่าง interface)

```php
interface SocialPlatformInterface
{
    public function getPlatformKey(): string;  // 'youtube'
    public function getAuthorizationUrl(string $state, string $redirectUri): string;
    public function exchangeAuthCode(string $code, string $redirectUri): array;
    public function refreshToken(SocialChannel $channel): SocialChannel;
    public function publishPost(SocialPostTarget $target): array;
    public function revoke(SocialChannel $channel): void;
}
```

---

## 🔐 OAuth Flow (per platform)

### Universal flow
1. Admin → `/admin/social/channels/connect/{platform}` (POST)
2. Server → redirect to platform OAuth URL (with `state` = signed CSRF token)
3. User authorizes on platform → redirect back to `/admin/social/oauth/{platform}/callback?code=...&state=...`
4. Verify state → exchange code → store encrypted token in `social_channels`
5. Fetch external metadata (page name, channel name) → save to `metadata`
6. Redirect to `/admin/social/channels` with success flash

### Token refresh strategy
- **Background job**: `RefreshExpiringTokensJob` runs every 1 hour
  - WHERE `access_token_expires_at < NOW() + 24 hours` AND `status = active`
  - แต่ละ channel → call connector->refreshToken()
- **On-demand**: `SocialTokenManager::getValidToken()` ตรวจก่อนใช้ทุกครั้ง
- **Failure**: ถ้า refresh fail → set `status = token_expired`, alert admin via FCM/LINE OA

---

## 🛣️ Routes (admin only)

```php
// routes/admin.php
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/social')
    ->name('admin.social.')
    ->group(function () {
        // Composer
        Route::get('/composer', [SocialComposerController::class, 'create'])->name('composer');
        Route::post('/composer', [SocialComposerController::class, 'store'])->name('composer.store');

        // Posts history
        Route::get('/posts', [SocialPostController::class, 'index'])->name('posts.index');
        Route::get('/posts/{post}', [SocialPostController::class, 'show'])->name('posts.show');
        Route::post('/posts/{post}/publish', [SocialPostController::class, 'publish'])->name('posts.publish');
        Route::post('/posts/{post}/targets/{target}/retry', [SocialPostController::class, 'retryTarget'])->name('posts.retry');
        Route::delete('/posts/{post}', [SocialPostController::class, 'destroy'])->name('posts.destroy');

        // Channels
        Route::get('/channels', [SocialChannelController::class, 'index'])->name('channels.index');
        Route::post('/channels/connect/{platform}', [SocialChannelController::class, 'connect'])->name('channels.connect');
        Route::get('/oauth/{platform}/callback', [SocialChannelController::class, 'callback'])->name('channels.oauth.callback');
        Route::delete('/channels/{channel}', [SocialChannelController::class, 'disconnect'])->name('channels.disconnect');

        // FB Source Feed
        Route::get('/feed', [SocialFeedController::class, 'index'])->name('feed');
        Route::post('/feed/sync', [SocialFeedController::class, 'sync'])->name('feed.sync');
        Route::post('/feed/{source}/repost', [SocialFeedController::class, 'repost'])->name('feed.repost');
    });
```

---

## 🎨 Admin UI Screens (V3: Tailwind + Alpine + Blade)

### 1. `/admin/social/composer` — เขียนโพส
- **Top bar**: ปุ่ม Save Draft / Schedule / Publish Now
- **Left column**: title, caption (textarea + char count), hashtags, drop-zone media upload
- **Right column**: channel selector (checkbox list of connected channels grouped by platform)
- **Per-platform overrides**: คลิกชื่อช่อง → expand → caption override + platform options
- **Preview panel**: render mock UI ของแต่ละ platform (TikTok mobile, YouTube card, FB post)

### 2. `/admin/social/channels` — จัดการบัญชี
- Card grid: 1 card ต่อ 1 channel
- แต่ละ card: platform logo + account name + status badge + last_used + ปุ่ม Disconnect
- ปุ่ม "+ Connect" → dropdown (Facebook / YouTube / TikTok)
- ปุ่ม "Refresh Token" (manual trigger)

### 3. `/admin/social/posts` — ประวัติ
- Filter: status, platform, date range
- Table: thumbnail / caption (truncate) / created_at / status badges per target
- คลิก row → detail page

### 4. `/admin/social/posts/{post}` — รายละเอียดต่อโพส
- Header: media preview + caption
- Targets table: platform / status / external_url (link out) / attempts / error
- Per-target actions: Retry, View Logs, Cancel

### 5. `/admin/social/feed` — FB source feed
- รายการโพสจาก FB page ที่ poll มา
- แต่ละ row: thumbnail + caption + เวลา + ปุ่ม "ส่งต่อ → composer"

---

## ⚙️ Jobs

```
app/Jobs/Social/
├── PublishToChannelJob.php
│   - input: SocialPostTarget
│   - tries: 3, backoff: [60, 300, 1800] (1m, 5m, 30m)
│   - logic: resolve connector → publishPost → update target status
│   - on permanent fail: set status=failed, alert admin
│
├── SyncFacebookSourceJob.php
│   - input: SocialChannel (FB type)
│   - scheduled every 10 min via Kernel
│   - fetch posts since last_sync → create source rows (ไม่ auto-fan-out)
│
├── RefreshExpiringTokensJob.php
│   - scheduled every 1 hour
│   - query channels with expiring tokens → refresh
│
└── SchedulePublisherJob.php
    - scheduled every 1 min
    - WHERE social_posts.status=scheduled AND scheduled_at <= NOW()
    - → call SocialPublisherService::fanOut
```

### Kernel schedule additions
```php
$schedule->job(new SchedulePublisherJob)->everyMinute();
$schedule->job(new RefreshExpiringTokensJob)->hourly();
$schedule->command('social:sync-sources')->everyTenMinutes(); // calls SyncFacebookSourceJob for all FB channels
```

---

## 📝 Configuration

### `.env` additions

```ini
# Facebook (separate from Fortune bot)
SOCIAL_FB_APP_ID=
SOCIAL_FB_APP_SECRET=
SOCIAL_FB_REDIRECT_URI="${APP_URL}/admin/social/oauth/facebook/callback"

# YouTube / Google
SOCIAL_GOOGLE_CLIENT_ID=
SOCIAL_GOOGLE_CLIENT_SECRET=
SOCIAL_GOOGLE_REDIRECT_URI="${APP_URL}/admin/social/oauth/youtube/callback"

# TikTok
SOCIAL_TIKTOK_CLIENT_KEY=
SOCIAL_TIKTOK_CLIENT_SECRET=
SOCIAL_TIKTOK_REDIRECT_URI="${APP_URL}/admin/social/oauth/tiktok/callback"
SOCIAL_TIKTOK_SANDBOX=true   # false เมื่อผ่าน app review

# Storage
SOCIAL_MEDIA_DISK=public     # หรือ s3 ถ้ามี
SOCIAL_MAX_UPLOAD_MB=512
```

### `config/services.php`

```php
'social_facebook' => [
    'app_id'       => env('SOCIAL_FB_APP_ID'),
    'app_secret'   => env('SOCIAL_FB_APP_SECRET'),
    'redirect_uri' => env('SOCIAL_FB_REDIRECT_URI'),
    'scopes'       => ['pages_read_engagement', 'pages_manage_posts', 'publish_video'],
    'api_version'  => 'v20.0',
],
'social_youtube' => [
    'client_id'     => env('SOCIAL_GOOGLE_CLIENT_ID'),
    'client_secret' => env('SOCIAL_GOOGLE_CLIENT_SECRET'),
    'redirect_uri'  => env('SOCIAL_GOOGLE_REDIRECT_URI'),
    'scopes'        => [
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
    ],
],
'social_tiktok' => [
    'client_key'    => env('SOCIAL_TIKTOK_CLIENT_KEY'),
    'client_secret' => env('SOCIAL_TIKTOK_CLIENT_SECRET'),
    'redirect_uri'  => env('SOCIAL_TIKTOK_REDIRECT_URI'),
    'sandbox'       => env('SOCIAL_TIKTOK_SANDBOX', true),
    'scopes'        => ['user.info.basic', 'video.upload', 'video.publish'],
],
```

---

## 🔑 API Credentials Setup Guide

### Facebook (สร้างใหม่แยกจาก Fortune bot — confirmed ในเซสชัน)
1. ไป https://developers.facebook.com/apps → "Create App"
2. Type: **Business**, Name: e.g. "Thaiprompt Social Poster"
3. Add Products: **Facebook Login** + **Pages API**
4. Settings → Basic → copy App ID + App Secret → ใส่ใน `.env`
5. Facebook Login → Settings → Valid OAuth Redirect URIs → add `https://main.thaiprompt.online/admin/social/oauth/facebook/callback`
6. App Review (สำหรับ production):
   - `pages_read_engagement`, `pages_manage_posts`, `publish_video` ต้องผ่าน review
   - Dev mode ใช้ได้กับ admin/test users ก่อน
7. Privacy Policy URL: `https://main.thaiprompt.online/privacy` (ต้องมีหน้าจริง)

### Google Cloud / YouTube
1. ไป https://console.cloud.google.com → New Project: "Thaiprompt Social"
2. APIs & Services → Library → enable **YouTube Data API v3**
3. OAuth Consent Screen → External → ตั้ง app name + support email + scopes
4. Credentials → Create OAuth Client ID → Web Application
5. Authorized redirect URIs → add callback URL
6. Copy Client ID + Client Secret → `.env`
7. (Production) Submit app for verification — Google review 1-3 สัปดาห์
8. Quota raise request (ถ้าโพส >6/วัน): Quotas → YouTube Data API → Request quota increase

### TikTok Developers
1. ไป https://developers.tiktok.com → register
2. Apps → Create App: "Thaiprompt Cross Poster"
3. Add Products: **Login Kit** + **Content Posting API**
4. App Info: privacy policy URL, terms URL, demo video (วิดีโอ 1-2 นาทีโชว์การใช้งาน)
5. Sandbox: add tester accounts → ทดสอบได้ก่อน
6. Submit for Audit → 2-4 สัปดาห์
7. Configure redirect URI

---

## 📅 Phasing & Effort Estimate

### Phase 1 — Foundation (2-3 วัน) — ไม่ต้องรอ credentials
- ✅ 3 migrations + 3 models + relationships
- ✅ Service interfaces + connector stubs (return mock data)
- ✅ Admin UI skeleton (channels list, composer form — without working backends)
- ✅ Menu entry ใน `config/menus.php`
- ✅ Seeder for default settings (toggles)
- 🔍 Deliverable: รัน `php artisan migrate` ผ่าน, เปิด UI ดูได้, ยังโพสจริงไม่ได้

### Phase 2 — OAuth flows (3-4 วัน) — ต้องมี credentials ครบ
- ✅ FacebookConnector OAuth (get/refresh token)
- ✅ YouTubeConnector OAuth (Google API client)
- ✅ TikTokConnector OAuth (sandbox first)
- ✅ Token encryption + auto-refresh job
- ✅ Channels page → connect/disconnect working
- 🔍 Deliverable: connect บัญชีจริงทั้ง 3 platform ได้, token เก็บปลอดภัย

### Phase 3 — YouTube + Facebook publisher (5-6 วัน)
- ✅ YouTube video upload (resumable, with progress)
- ✅ Facebook publish — text, image, video, reel (Graph API: `/{page-id}/feed`, `/{page-id}/photos`, `/{page-id}/videos`, `/{page-id}/video_reels`)
- ✅ PublishToChannelJob + queue setup
- ✅ Per-target retry logic
- ✅ Composer → upload → fan-out → see status in admin UI
- 🔍 Deliverable: โพสจาก composer → ขึ้น YouTube + เพจแม่หมอจันทรา (FB) จริง

### Phase 4 — TikTok publisher (1-2 สัปดาห์)
- ✅ TikTok chunked upload
- ✅ Sandbox testing
- ✅ Production audit submission (รอ TikTok approve)
- ✅ Handle TikTok-specific options (disable_duet, privacy_level)
- 🔍 Deliverable: โพสไป TikTok ได้ (sandbox อย่างน้อย; production ตามที่ approve)

### Phase 5 — FB source + polish (2-3 วัน)
- ✅ FacebookConnector source pull (page posts + reels จากเพจแม่หมอจันทรา)
- ✅ Feed UI + repost button (โพส FB เก่า → ส่งต่อไป YT/TT)
- ✅ Scheduling (`scheduled_at` + SchedulePublisherJob)
- ✅ Error dashboard + retry buttons
- ✅ FCM/LINE alert on token expiry
- 🔍 Deliverable: FB poll → composer → multi-publish flow ครบทั้ง 3 ปลายทาง

**รวม**: 5-8 สัปดาห์ทำงานเต็มเวลา (ขึ้นกับว่ารอ app review เร็วช้าแค่ไหน)

---

## ❓ Open Questions

### O1. Video transcoding
- FB Reel = 9:16, YouTube ปกติ = 16:9, TikTok = 9:16
- ถ้า admin upload คลิป 16:9 ใน composer → ส่งไป TikTok ต้อง crop หรือ letterbox?
- **Options**:
  - (a) ส่งตามต้นฉบับ ปล่อยให้ platform จัดการ (อาจมีดำขอบ)
  - (b) สร้าง transcoding pipeline ด้วย FFmpeg (เพิ่ม 1-2 สัปดาห์)
  - (c) บังคับให้ admin upload หลายไฟล์ (1 ต่อ platform) — เสีย UX

**Recommendation**: (a) ใน MVP, (b) phase 2

### O2. Caption length differences
- FB: 63,206 chars
- YouTube title: 100 chars, description: 5,000 chars
- TikTok: 2,200 chars
- **Approach**: ใช้ caption หลัก + auto-truncate ตาม platform + show warning ใน composer

### O3. Hashtag strategy
- FB ไม่ค่อยใช้ hashtag, TikTok/YouTube ใช้เยอะ
- **Approach**: เก็บ hashtags แยกจาก caption → append per-platform: FB skip, YT append บน description, TT append บน caption

### O4. Source de-duplication
- FB poll ทุก 10 นาที — อย่าสร้าง row ซ้ำ
- **Approach**: `UNIQUE(source_channel_id, source_external_id)` constraint + `INSERT IGNORE`

### O5. Storage strategy
- Videos กินพื้นที่เยอะ (200-500 MB ต่อคลิป)
- Local disk vs S3?
- **Recommendation**: local ใน MVP, ย้าย S3 ภายหลังถ้าเก็บมาก

### O6. Webhook from FB (real-time vs poll)
- FB Webhooks (subscription) ได้ realtime แต่ต้อง app review
- **Recommendation**: skip webhook ใน MVP — poll ทุก 10 min พอ

### O7. Multi-tenant อนาคต
- Phase 2: เปิดให้ affiliate users connect บัญชีตัวเอง
- ต้องแก้ user_id scope ทั้งระบบ (ตอนนี้ admin-only)
- Quota: ถ้า 1000 users connect YouTube → ใช้ quota เร็วมาก ต้องเพิ่ม Google projects (sharded)

---

## 🚦 Dependencies / Blocking Items

| ลำดับ | ทำให้ได้ก่อน | บล็อก phase ไหน |
|------|-------------|-----------------|
| 1 | สร้าง FB App ใหม่ + privacy policy page | Phase 2 OAuth |
| 2 | Google Cloud project + YouTube Data API enable | Phase 2 OAuth |
| 3 | TikTok Developer account + sandbox setup | Phase 2 OAuth |
| 4 | TikTok app audit submission (รอ approve) | Phase 4 production |
| 5 | (ถ้าโพสเยอะ) YouTube quota raise request | Phase 5 production volume |
| 6 | ติดตั้ง FFmpeg บน server (ถ้าทำ transcoding) | O1 / future |

---

## 📦 Package Dependencies (ต้องเพิ่มใน composer.json)

```bash
# YouTube
composer require google/apiclient:^2.15
composer require google/apiclient-services

# (Facebook + TikTok ใช้ HTTP โดยตรง — มี Guzzle อยู่แล้ว)
```

---

## 🧪 Testing Strategy

- **Unit**: connectors with mocked HTTP (Http::fake())
- **Feature**:
  - OAuth callback flow (state validation, token storage)
  - Composer → fan-out → targets created
  - PublishToChannelJob retry on transient error
- **Integration** (manual, sandbox):
  - Connect real account → publish test video → verify external URL
- **Critical scenario coverage** (per global rules):
  - Token expired mid-publish → auto-refresh → retry
  - Network timeout on upload → retry with backoff
  - User clicks publish twice rapidly → idempotent (use post.status flag)
  - Quota exceeded → graceful fail with clear error message

---

## 🛡️ Security Checklist

- [ ] OAuth `state` parameter signed + verified (CSRF)
- [ ] `access_token` + `refresh_token` encrypted at rest (Crypt::encrypt)
- [ ] No tokens in logs (sanitize Log facade)
- [ ] Rate limit on /admin/social/composer/store (max 30/hour)
- [ ] Only admin role can access /admin/social/*
- [ ] Validate redirect URI exactly matches registered URI
- [ ] Disable webhook signature bypass (verify X-Hub-Signature when adding FB webhook)
- [ ] Media upload size limit + MIME validation
- [ ] Sanitize caption for XSS (when rendering in admin UI)

---

## 📌 Out of Scope (อย่าทำใน MVP)

- ❌ LINE VOOM posting (ไม่มี public API)
- ❌ Instagram (ต้องผ่าน FB Business + IG Graph — เพิ่ม Phase 6 ได้)
- ❌ X/Twitter (API paid + restrictions)
- ❌ Analytics / engagement metrics
- ❌ AI auto-caption generation
- ❌ Multi-tenant (affiliate users)
- ❌ Recurring schedule (รายสัปดาห์)
- ❌ Video editing in browser
- ❌ A/B testing captions
- ❌ Comment management

---

## 🎬 Acceptance Criteria (เสร็จเมื่อ...)

1. Admin connect บัญชี Facebook (เพจแม่หมอจันทรา) + YouTube + TikTok ได้จากหน้า /admin/social/channels
2. Admin upload วิดีโอใน composer → เลือก 3 ช่อง → กด Publish → ขึ้นทั้ง 3 ที่ (FB + YT + TT)
3. ถ้า 1 ใน 3 fail → อีก 2 ที่ยังขึ้นปกติ + แสดง error ที่ fail
4. ปุ่ม Retry ทำงาน → publish ซ้ำสำเร็จ
5. Schedule โพส 1 ชั่วโมงข้างหน้า → ทำงานตรงเวลาอัตโนมัติ
6. Token expire → auto-refresh เงียบๆ + log
7. FB page poll ทุก 10 นาที → โพสใหม่ขึ้นใน feed → คลิก "ส่งต่อ" → composer pre-filled
8. ทุกหน้า admin รองรับ dark/light mode + responsive
9. ทุก comment เป็นภาษาไทย (per project rule)
10. Seeders ทั้งหมดอยู่ใน `DatabaseSeeder.php` (per Git hook check)
