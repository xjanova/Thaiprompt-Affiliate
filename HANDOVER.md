# Backend Ops Handover

Operational notes that don't belong in code or `CLAUDE.md` but the next person needs to know.

## Bare-domain redirect — the real fix (origin .htaccess, NOT Cloudflare)

**Symptom (resolved 2026-04-19):** any request to `https://thaiprompt.online/...` returned a 301 to a broken URL where the host and path concatenated without a separator slash:

```text
$ curl -sSI https://thaiprompt.online/api/v1/app/config
HTTP/2 301
location: http://main.thaiprompt.onlineapi/v1/app/config     ← BROKEN (missing /)
server: cloudflare
```

The mobile app's HTTP client followed the 301, failed DNS for the bogus host, and showed "no network." Affected every endpoint hit via the bare brand domain.

**Root cause** (verified by inspecting CF dashboard + walking docroot via SSH):

The bug was NOT in Cloudflare — Page Rules / Redirect Rules / Bulk Redirects all empty. The bug lived in the bare domain's `.htaccess` at the origin server:

```text
/home/admin/domains/thaiprompt.online/public_html/.htaccess
└── Redirect 301 / http://main.thaiprompt.online        ← OLD/BROKEN
```

Apache's `Redirect 301 <URL-path> <URL>` directive strips the matching prefix from the request and appends the remainder to the destination URL. With source=`/` and dest=`http://main.thaiprompt.online` (no trailing slash):

  request `/api/v1/app/config`
  → strip `/`  → leftover `api/v1/app/config`
  → concat → `http://main.thaiprompt.online` + `api/v1/app/config`
  → final  → `http://main.thaiprompt.onlineapi/v1/app/config`  💥

Cloudflare just relayed the broken 301 from origin (`cf-cache-status: DYNAMIC`).

**Fix applied** — replaced the file in-place via Server Logs `tinker` (backup at `.htaccess.bak.YYYYMMDD_HHMMSS`):

```apache
RewriteEngine On
RewriteRule ^(.*)$ https://main.thaiprompt.online/$1 [R=301,L]
```

Now `RewriteRule` keeps the leading slash on `$1` AND upgrades to HTTPS. Verified:

```bash
$ curl -sSI https://thaiprompt.online/api/v1/app/config | grep -i location
location: https://main.thaiprompt.online/api/v1/app/config   ← clean
```

**If the file gets reverted by cPanel / a deploy script / a "reset" button**, redeploy the same content, OR rely on the in-repo safety net at `public/.htaccess` of the Laravel app (handles the `HTTP_HOST = thaiprompt.online` case at the application layer too — works only after the request reaches the Laravel docroot, which it does NOT today because of the per-domain vhost split, but is there as a belt-and-braces).

---

## Mobile auto-update — wiring + first-time setup

The mobile app's auto-update flow reads from the `app_releases` table via `GET /api/v1/app/latest-version`. Two ingestion paths populate it:

### Path A — automatic (GitHub webhook)

On every `release.published` event, GitHub posts to `/api/webhooks/github/release`. The handler (`App\Http\Controllers\Api\WebhookController::handleGitHubRelease`) verifies the HMAC signature, calls `AppReleaseSync::upsertFromGitHubRelease`, then clears the response cache. End result: a new GitHub release is queryable by the mobile app within seconds.

**One-time GitHub-side setup** (per repo that publishes mobile APKs):

1. `xjanova/thaipromptapp` → **Settings → Webhooks → Add webhook**
2. Payload URL: `https://main.thaiprompt.online/api/webhooks/github/release`
3. Content type: `application/json`
4. Secret: a random string — also set as `GITHUB_WEBHOOK_SECRET` in the backend `.env` (same value)
5. Events: **Let me select individual events → Releases**
6. Active: ✓
7. Save

Verify by editing the most recent release on GitHub (no real change needed) and checking backend logs:
```bash
tail -f storage/logs/laravel.log | grep "GitHub release processed"
```

### Path B — backfill (`releases:backfill` artisan)

For releases that existed before the webhook was wired up, or after a DB wipe:

```bash
php artisan releases:backfill                          # default: xjanova/thaipromptapp, last 30
php artisan releases:backfill --limit=50
php artisan releases:backfill --repo=other-org/x
php artisan releases:backfill --dry-run                # preview only
```

Idempotent — safe to re-run. Uses `GITHUB_TOKEN` from `.env` if set (raises rate limit 60/hr → 5000/hr) but works without on public repos.

### Verify auto-update is live end-to-end

```bash
# 1. Public endpoint returns the latest row
curl -s https://main.thaiprompt.online/api/v1/app/latest-version | jq '.data.latest_version,.data.apk_url'

# 2. Older app version sees the prompt — bump min_supported_version in DB
#    to test the "mandatory update" code path:
php artisan tinker
>>> DB::table('app_releases')->where('version', '1.0.3')->update(['min_supported_version' => '1.0.3']);
```

---

## Mobile app endpoints (v1)

Live as of PR [#2534](https://github.com/xjanova/Thaiprompt-Affiliate/pull/2534) (merged 2026-04-19).

| Endpoint | Auth | Rate limit | Purpose |
|---|---|---|---|
| `GET  /api/v1/app/config` | optional* | — | Remote config + ETag (private keys filtered when no auth) |
| `GET  /api/v1/app/flags` | optional* | — | Resolved feature flags for current user/device |
| `GET  /api/v1/app/menus` | none | — | App nav menus |
| `GET  /api/v1/app/sliders` | none | — | Hero sliders |
| `GET  /api/v1/app/promotions` | none | — | Active promotions |
| `GET  /api/v1/app/latest-version` | none | — | Auto-update version check |
| `POST /api/v1/events/batch` | sanctum | 60/min/user | Mobile analytics ingestion |
| `POST /api/v1/ai/chat` | sanctum | 20/min/user | "น้องหญิง" cloud chat fallback |
| `POST /api/v1/ai/tts` | sanctum | 20/min/user | Gemini TTS streaming (female voices only) |

\* Anonymous callers get only public keys; authenticated users get the full set.

**Hard rules enforced server-side** (mirror of `lib/core/ai/prompts.dart` + tests):
- AI chat system prompt forbids `ครับ / นะครับ / ผม / กระผม` — must use `ค่ะ/คะ/นะคะ` + `หนู`.
- TTS voice list = female only (`th-premwadee`, `th-achara`). Server rejects unknown keys.
- Never add male voices or relax persona — see `app/Http/Controllers/Api/V1/AiTtsApiController.php::THAI_VOICES`.

---

## Mobile app repo + secrets

- App repo: [xjanova/thaipromptapp](https://github.com/xjanova/thaipromptapp) (v1.0.3 ↑)
- Default API base in app: `https://thaiprompt.online/api` (override per-build via `--dart-define=API_BASE_URL=...`)
- Optional repo secrets here that nothing currently sets:
  - `BACKEND_RELEASE_URL` — endpoint app's release CI hits to populate `app_releases` automatically (HMAC-signed POST)
  - `BACKEND_RELEASE_SECRET` — the HMAC secret matching what backend expects
