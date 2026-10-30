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

**Fix applied** — replaced the file in-place via Server Logs `tinker` (backup at `.htaccess.bak.YYYYMMDD_HHMMSS`).

First attempt used `[R=301,L]` — that fixed the slash bug but introduced a new one: HTTP/1.1 clients downgrade `POST` → `GET` when following a 301 (legacy behaviour from RFC 2616). Mobile registration started returning 405 Method Not Allowed because `/api/v1/register` only accepts POST. The app's catch-all error handler then surfaced "ไม่มีสัญญาณอินเทอร์เน็ต".

The current rule uses **308 (Permanent Redirect)** which preserves the request method:

```apache
RewriteEngine On
RewriteRule ^(.*)$ https://main.thaiprompt.online/$1 [R=308,L]
```

308 is the right choice for an API redirect — clients keep `POST`/`PUT`/`DELETE` intact and the body is re-sent. Verified:

```bash
$ curl -sSI https://thaiprompt.online/api/v1/app/config | grep -i location
location: https://main.thaiprompt.online/api/v1/app/config   ← clean
```

**If the file gets reverted by cPanel / a deploy script / a "reset" button**, redeploy the same content, OR rely on the in-repo safety net at `public/.htaccess` of the Laravel app (handles the `HTTP_HOST = thaiprompt.online` case at the application layer too — works only after the request reaches the Laravel docroot, which it does NOT today because of the per-domain vhost split, but is there as a belt-and-braces).
