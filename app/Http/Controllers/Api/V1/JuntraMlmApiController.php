<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MlmMember;
use App\Models\User;
use App\Services\FortuneAffiliateService;
use App\Services\Juntra\JuntraMlmReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Juntra (จันทรา.online) MLM consumer API — ทาง token ของลูกค้า
 *
 * Read-only endpoints exposing the **fortune-telling slice** of the MLM tree
 * and commissions. NEVER returns marketplace, NFC, vendor, or TPIX data —
 * only what's tied to fortune_readings + fortune_commissions.
 *
 * ข้อมูลประกอบที่ JuntraMlmReadService (ใช้ร่วมกับทางเซิร์ฟเวอร์ /juntra/server/affiliate/*)
 *
 * Auth: Sanctum/Passport token ของลูกค้า
 * - Normal user: can read their OWN tree + commissions.
 * - Admin (role 'admin'): can read ANY user's tree + commissions, list active
 *   fortune customers, and view aggregate stats.
 */
class JuntraMlmApiController extends Controller
{
    public function __construct(private JuntraMlmReadService $reads) {}

    /* =========================================================================
       GET /api/v1/juntra/mlm/tree?user_id=&depth=
       ========================================================================= */
    public function tree(Request $request): JsonResponse
    {
        $targetId = $this->resolveTargetUserId($request, $request->user());
        if ($targetId instanceof JsonResponse) {
            return $targetId;
        }

        $depth = (int) $request->query('depth', JuntraMlmReadService::DEFAULT_TREE_DEPTH);

        return response()->json($this->reads->tree($targetId, $depth));
    }

    /* =========================================================================
       GET /api/v1/juntra/mlm/commissions?user_id=&page=&status=&from=&to=
       ========================================================================= */
    public function commissions(Request $request): JsonResponse
    {
        $targetId = $this->resolveTargetUserId($request, $request->user());
        if ($targetId instanceof JsonResponse) {
            return $targetId;
        }

        return response()->json($this->reads->commissions(
            $targetId,
            [
                'status' => $request->query('status'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
            ],
            (int) $request->query('page', 1),
            (int) $request->query('per_page', 25),
        ));
    }

    /* =========================================================================
       GET /api/v1/juntra/mlm/stats?user_id=
       ========================================================================= */
    public function stats(Request $request): JsonResponse
    {
        $targetId = $this->resolveTargetUserId($request, $request->user());
        if ($targetId instanceof JsonResponse) {
            return $targetId;
        }

        return response()->json($this->reads->stats($targetId));
    }

    /* =========================================================================
       POST /api/v1/juntra/mlm/claim-referral   {code: <member_code>}

       จันทรา.online referral attribution: a visitor lands on จันทรา.online/r/{code}
       (code = the inviter's MlmMember.member_code), juntraweb stores it in a
       30-day cookie, and after the user links Thaiprompt (OAuth) juntraweb
       calls this with the NEW user's token to enroll them under the inviter.

       Conservative by design: if the caller already has an MlmMember we NEVER
       re-parent (unilevel_path of the whole downline would break) — we return
       409 `already_enrolled` and juntraweb clears its cookie.

       (2026-09-21) juntraweb รุ่นใหม่ต่อสายงานผ่าน POST /juntra/server/affiliate/accounts
       แทน (ลูกค้าไม่ต้องผูก Thaiprompt) — เส้นนี้คงไว้ให้แอพรุ่นเก่า
       ========================================================================= */
    public function claimReferral(Request $request): JsonResponse
    {
        $auth = $request->user();
        if (! $auth) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $request->validate(['code' => 'required|string|max:64']);
        $code = substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $request->input('code')), 0, 64);
        if ($code === '') {
            return response()->json(['claimed' => false, 'reason_code' => 'invalid_code'], 404);
        }

        $sponsor = MlmMember::where('member_code', $code)->first();
        if (! $sponsor) {
            return response()->json(['claimed' => false, 'reason_code' => 'invalid_code'], 404);
        }
        if ((int) $sponsor->user_id === (int) $auth->id) {
            return response()->json(['claimed' => false, 'reason_code' => 'self_referral'], 422);
        }

        $existing = MlmMember::where('user_id', $auth->id)->first();
        if ($existing) {
            return response()->json([
                'claimed' => false,
                'reason_code' => 'already_enrolled',
                'member_code' => $existing->member_code,
            ], 409);
        }

        $member = app(FortuneAffiliateService::class)->enrollUserUnderSponsor($auth, $sponsor);
        if (! $member) {
            return response()->json(['claimed' => false, 'reason_code' => 'enrollment_failed'], 503);
        }

        // ทุกคนที่ผังเพิ่งเปลี่ยน: สมาชิกใหม่ ผู้แนะนำ และทุกคนเหนือขึ้นไป
        $this->reads->forgetCachesForUpline($member);

        return response()->json([
            'claimed' => true,
            'member_code' => $member->member_code,
            'sponsor' => [
                'name' => $sponsor->user?->name,
                'member_code' => $sponsor->member_code,
            ],
        ], 201);
    }

    /* =========================================================================
       GET /api/v1/juntra/mlm/users  (admin only)
       List of users who have fortune activity — drives the admin "view any user"
       picker on the juntra dashboard.
       ========================================================================= */
    public function users(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request->user())) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        return response()->json($this->reads->users(
            trim((string) $request->query('q', '')),
            (int) $request->query('page', 1),
            (int) $request->query('per_page', 50),
        ));
    }

    /* ============================================================
       INTERNAL
       ============================================================ */

    /**
     * Resolve who we're querying for.
     *  - If `user_id` query param is given AND caller is admin → use it.
     *  - If `user_id` is given but caller is NOT admin → 403.
     *  - Otherwise → caller's own id.
     */
    private function resolveTargetUserId(Request $request, ?User $auth): int|JsonResponse
    {
        if (! $auth) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $explicit = $request->query('user_id');
        if ($explicit !== null && (string) $explicit !== (string) $auth->id) {
            if (! $this->isAdmin($auth)) {
                return response()->json(['error' => 'forbidden — admin only'], 403);
            }

            return (int) $explicit;
        }

        return (int) $auth->id;
    }

    private function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;
    }
}
