<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FacebookOAuthSetting;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FortuneAffiliateService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Facebook OAuth Login Controller
 *
 * ใช้ Laravel Socialite เชื่อมกับ Facebook Login
 *
 * Flow:
 * 1. ลูกค้ากด "Login with Facebook" → /auth/facebook
 * 2. Redirect ไป Facebook OAuth
 * 3. FB redirect กลับมา /auth/facebook/callback พร้อม code
 * 4. Socialite แลก code → user data (id, name, email, avatar)
 * 5. Match กับ User ที่มีอยู่:
 *    a. facebook_user_id ตรง → existing user
 *    b. email ตรง → link FB ให้ existing user
 *    c. ไม่เจอ → สร้างใหม่ + Wallet + MlmMember
 * 6. login + redirect ไป intended URL (default: /user/wallet)
 */
class FacebookLoginController extends Controller
{
    /**
     * Redirect ไป Facebook OAuth
     */
    public function redirect(Request $request): RedirectResponse
    {
        $setting = $this->loadSetting();
        if (! $setting) {
            return redirect()->route('login')
                ->with('error', 'Facebook Login ยังไม่ได้ตั้งค่า — กรุณาแจ้งผู้ดูแลระบบ');
        }

        // เก็บ intended URL เพื่อ redirect หลัง login (default: wallet)
        if ($request->has('redirect')) {
            Session::put('facebook_login_redirect', $request->get('redirect'));
        }

        // เก็บ referral code ถ้ามี (สำหรับสมัครใหม่ผ่านลิงก์)
        if ($request->has('ref')) {
            Session::put('facebook_login_referral', $request->get('ref'));
        }

        // เก็บ origin page (login/register) เพื่อ redirect กลับ on error
        $referer = $request->headers->get('referer', '');
        Session::put('facebook_login_origin', str_contains($referer, '/register') ? 'register' : 'login');

        return Socialite::driver('facebook')
            ->scopes($setting->getScopes())
            ->redirect();
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function callback(Request $request): RedirectResponse
    {
        // Load setting + apply runtime config (สำคัญ! Socialite อ่าน config ตอน driver init)
        $setting = $this->loadSetting();
        $errorOrigin = Session::get('facebook_login_origin', 'login');
        $errorRoute = $errorOrigin === 'register' ? 'register' : 'login';

        if (! $setting) {
            return redirect()->route($errorRoute)
                ->with('error', 'Facebook Login ยังไม่ได้ตั้งค่า — กรุณาแจ้งผู้ดูแลระบบ');
        }

        // User cancel หรือ error จาก FB
        if ($request->has('error')) {
            Log::info('Facebook OAuth: user denied or error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);

            return redirect()->route($errorRoute)
                ->with('error', 'การเข้าสู่ระบบด้วย Facebook ถูกยกเลิก');
        }

        try {
            $fbUser = Socialite::driver('facebook')->user();
        } catch (Exception $e) {
            Log::error('Facebook OAuth: callback failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route($errorRoute)
                ->with('error', 'ไม่สามารถเชื่อมต่อกับ Facebook ได้ กรุณาลองใหม่');
        }

        // Validate FB user data
        if (empty($fbUser->getId())) {
            Log::warning('Facebook OAuth: missing user ID');

            return redirect()->route($errorRoute)
                ->with('error', 'ข้อมูลจาก Facebook ไม่ครบ กรุณาลองใหม่');
        }

        try {
            $user = $this->findOrCreateUser($fbUser);
        } catch (Exception $e) {
            Log::error('Facebook OAuth: findOrCreateUser failed', [
                'fb_user_id' => $fbUser->getId(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route($errorRoute)
                ->with('error', 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่');
        }

        // Login user
        Auth::login($user, true);

        // Track login stats
        $setting->recordLogin();

        Log::info('Facebook OAuth: login success', [
            'user_id' => $user->id,
            'fb_user_id' => $fbUser->getId(),
            'is_new_user' => $user->wasRecentlyCreated,
        ]);

        // Redirect ไป intended URL หรือ wallet (default)
        $redirectUrl = Session::pull('facebook_login_redirect', route('user.wallet.index'));
        Session::forget(['facebook_login_origin', 'facebook_login_referral']);

        return redirect($redirectUrl)
            ->with('success', 'เข้าสู่ระบบด้วย Facebook สำเร็จ ยินดีต้อนรับ ' . $user->name);
    }

    /**
     * หา user จาก FB data หรือสร้างใหม่
     *
     * Match priority:
     *   1. facebook_user_id ตรง → existing FB-linked user
     *   2. user.email ตรง → link FB ให้ user เดิม (เคย register แบบอื่น)
     *   3. email = fb_{psid}@thaiprompt.local → existing auto-registered user (จากดูดวง)
     *   4. ไม่เจอ → สร้างใหม่
     */
    protected function findOrCreateUser($fbUser): User
    {
        $fbId = $fbUser->getId();
        $fbEmail = $fbUser->getEmail();

        // 1. หา by facebook_user_id (linked แล้ว)
        $user = User::where('facebook_user_id', $fbId)->first();
        if ($user) {
            $this->refreshFacebookFields($user, $fbUser);

            return $user;
        }

        // 2. หา by email (เคย register แบบอื่น)
        if ($fbEmail) {
            $user = User::where('email', $fbEmail)->first();
            if ($user) {
                $this->linkFacebookToUser($user, $fbUser);

                return $user;
            }
        }

        // 3. ลูกค้าเคย auto-register จากดูดวง — มี email pattern fb_{psid}@thaiprompt.local
        //    แต่ FB OAuth ส่ง app-scoped ID ที่ต่างจาก Messenger PSID
        //    ดังนั้น match ไม่ได้ตรงๆ ต้องอาศัย email หรือสร้างใหม่
        //    (ในอนาคตอาจใช้ id_for_pages API เพื่อ map app-scoped → PSID)

        // 4. สร้าง user ใหม่ + wallet + MLM
        return $this->createNewUserFromFacebook($fbUser);
    }

    /**
     * Update facebook fields เมื่อ login ซ้ำ (refresh profile + verified flag)
     */
    protected function refreshFacebookFields(User $user, $fbUser): void
    {
        $user->update([
            'facebook_email' => $fbUser->getEmail() ?: $user->facebook_email,
            'facebook_name' => $fbUser->getName() ?: $user->facebook_name,
            'facebook_picture_url' => $fbUser->getAvatar() ?: $user->facebook_picture_url,
            'facebook_verified' => true,
        ]);
    }

    /**
     * Link Facebook ให้ user เดิม (เคย register email/LINE)
     */
    protected function linkFacebookToUser(User $user, $fbUser): void
    {
        // กัน: facebook_user_id ของ FB คนนี้ถูก link กับ user คนอื่น
        $existingFb = User::where('facebook_user_id', $fbUser->getId())
            ->where('id', '!=', $user->id)
            ->exists();
        if ($existingFb) {
            throw new Exception('บัญชี Facebook นี้ถูกผูกกับผู้ใช้อื่นแล้ว');
        }

        $user->update([
            'facebook_user_id' => $fbUser->getId(),
            'facebook_email' => $fbUser->getEmail(),
            'facebook_name' => $fbUser->getName(),
            'facebook_picture_url' => $fbUser->getAvatar(),
            'facebook_verified' => true,
            'facebook_linked_at' => now(),
            // ใช้ avatar จาก FB ถ้า user ยังไม่มี
            'profile_picture' => $user->profile_picture ?: $fbUser->getAvatar(),
        ]);

        Log::info('Facebook OAuth: linked to existing user', [
            'user_id' => $user->id,
            'fb_user_id' => $fbUser->getId(),
        ]);
    }

    /**
     * สร้าง user ใหม่จาก Facebook profile + Wallet + MlmMember
     *
     * ใช้ FortuneAffiliateService::ensureWalletExists pattern
     * ผูก sponsor เป็น Super Admin (user_id=1) เป็น default
     */
    protected function createNewUserFromFacebook($fbUser): User
    {
        $email = $fbUser->getEmail() ?: 'fb_' . $fbUser->getId() . '@thaiprompt.local';

        return DB::transaction(function () use ($fbUser, $email) {
            $user = User::create([
                'name' => $fbUser->getName() ?: 'Facebook User',
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // random password — login ผ่าน FB เท่านั้น
                'profile_picture' => $fbUser->getAvatar(),
                'facebook_user_id' => $fbUser->getId(),
                'facebook_email' => $fbUser->getEmail(),
                'facebook_name' => $fbUser->getName(),
                'facebook_picture_url' => $fbUser->getAvatar(),
                'facebook_verified' => true,
                'facebook_linked_at' => now(),
                'email_verified_at' => $fbUser->getEmail() ? now() : null,
            ]);

            // สร้าง wallet ทันที (รองรับการรับคอมมิชชั่น)
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'currency' => 'THB', 'status' => 'active']
            );

            // สร้าง MlmMember ผ่าน FortuneAffiliateService (มี logic ผูก sponsor + binary)
            try {
                $affiliateService = app(FortuneAffiliateService::class);
                // ใช้ reflection เรียก protected method (หรือเปิด method เป็น public ในอนาคต)
                $createMethod = new \ReflectionMethod($affiliateService, 'createMlmMember');
                $createMethod->setAccessible(true);
                $createMethod->invoke($affiliateService, $user, $fbUser->getId());
            } catch (Exception $mlmErr) {
                Log::warning('Facebook OAuth: createMlmMember failed (non-blocking)', [
                    'user_id' => $user->id,
                    'error' => $mlmErr->getMessage(),
                ]);
            }

            Log::info('Facebook OAuth: new user created', [
                'user_id' => $user->id,
                'fb_user_id' => $fbUser->getId(),
                'has_email' => ! empty($fbUser->getEmail()),
            ]);

            return $user;
        });
    }

    /**
     * โหลด setting จาก DB + apply runtime config ให้ Socialite
     *
     * Override config ตอน runtime — เพราะ Socialite อ่าน config('services.facebook.*')
     * ตอน driver instantiation. การเปลี่ยน DB row ไม่ทันที ต้อง Config::set() ตรงๆ
     *
     * Return null ถ้า config ยังไม่พร้อม (admin ยังไม่ได้กรอก/disabled)
     */
    protected function loadSetting(): ?FacebookOAuthSetting
    {
        $setting = FacebookOAuthSetting::getActive();

        if (! $setting || ! $setting->isReady()) {
            return null;
        }

        // Override Socialite config runtime — แทนที่ค่าจาก env ใน config/services.php
        Config::set('services.facebook.client_id', $setting->app_id);
        Config::set('services.facebook.client_secret', $setting->app_secret);
        Config::set('services.facebook.redirect', $setting->getRedirectUri());

        return $setting;
    }
}
