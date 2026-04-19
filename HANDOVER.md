# Backend Ops Handover

Operational notes that don't belong in code or `CLAUDE.md` but the next person needs to know.

## Cloudflare Page Rule fix — bare-domain redirect bug

**Symptom:** any request to `https://thaiprompt.online/...` returns a 301 to a broken URL where the path is concatenated to the host without a separator slash:

```text
$ curl -sSI https://thaiprompt.online/api/v1/app/config
HTTP/2 301
location: http://main.thaiprompt.onlineapi/v1/app/config     ← BROKEN (missing /)
server: cloudflare
```

The mobile app's HTTP client follows the 301, fails DNS for the bogus host, and shows "no network." Affects every endpoint hit via the bare brand domain.

**Root cause:** A Cloudflare **Forwarding URL** Page Rule whose destination is

```text
http://main.thaiprompt.online$1
```

— missing the `/` between the host and the `$1` capture, so a path like `api/v1/app/config` (captured without leading slash by `*thaiprompt.online/*`) is appended directly to the host.

**Fix (10-second dashboard edit):**

1. Cloudflare dashboard → **thaiprompt.online** → **Rules → Page Rules**
2. Find the rule whose URL pattern is `*thaiprompt.online/*` (or similar) with a "Forwarding URL" action
3. Edit the destination to add the missing slash AND switch to HTTPS:
   - Old: `http://main.thaiprompt.online$1`
   - **New:** `https://main.thaiprompt.online/$1`
4. Save · the change is global within ~30s

**Verify:**
```bash
curl -sSI https://thaiprompt.online/api/v1/app/config | grep -i location
# Expect:  location: https://main.thaiprompt.online/api/v1/app/config
```

**Repo-side safety net:** `public/.htaccess` has a defensive `RewriteRule` that catches `HTTP_HOST = thaiprompt.online` and issues a clean 301. It's a no-op while the CF rule short-circuits at the edge, but it's there in case the CF rule is removed and Apache starts seeing bare-domain traffic.

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
