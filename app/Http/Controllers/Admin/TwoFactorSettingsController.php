<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TwoFactorSettingsController extends Controller
{
    /**
     * Display 2FA settings page
     */
    public function index()
    {
        $settings = TwoFactorSetting::first();

        // Create default settings if none exist
        if (!$settings) {
            $settings = TwoFactorSetting::create([
                'enabled' => false,
                'require_on_login' => false,
                'require_on_withdrawal' => true,
                'require_on_transfer' => true,
                'require_on_profile_change' => false,
                'require_on_password_change' => false,
                'require_on_payment_method_change' => false,
                'allow_sms' => true,
                'allow_line' => true,
                'allow_email' => false,
                'default_method' => 'sms',
                'grace_period_minutes' => 15,
                'allow_remember_device' => true,
                'remember_device_days' => 30,
            ]);
        }

        return view('admin.two-factor.settings', compact('settings'));
    }

    /**
     * Update 2FA settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => ['boolean'],
            'require_on_login' => ['boolean'],
            'require_on_withdrawal' => ['boolean'],
            'require_on_transfer' => ['boolean'],
            'require_on_profile_change' => ['boolean'],
            'require_on_password_change' => ['boolean'],
            'require_on_payment_method_change' => ['boolean'],
            'allow_sms' => ['boolean'],
            'allow_line' => ['boolean'],
            'allow_email' => ['boolean'],
            'default_method' => ['required', 'in:sms,line,email'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'allow_remember_device' => ['boolean'],
            'remember_device_days' => ['required', 'integer', 'min:1', 'max:365'],
            'withdrawal_threshold' => ['nullable', 'numeric', 'min:0'],
            'transfer_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง');
        }

        $settings = TwoFactorSetting::firstOrNew();

        $settings->fill([
            'enabled' => $request->boolean('enabled'),
            'require_on_login' => $request->boolean('require_on_login'),
            'require_on_withdrawal' => $request->boolean('require_on_withdrawal'),
            'require_on_transfer' => $request->boolean('require_on_transfer'),
            'require_on_profile_change' => $request->boolean('require_on_profile_change'),
            'require_on_password_change' => $request->boolean('require_on_password_change'),
            'require_on_payment_method_change' => $request->boolean('require_on_payment_method_change'),
            'allow_sms' => $request->boolean('allow_sms'),
            'allow_line' => $request->boolean('allow_line'),
            'allow_email' => $request->boolean('allow_email'),
            'default_method' => $request->input('default_method'),
            'grace_period_minutes' => $request->input('grace_period_minutes'),
            'allow_remember_device' => $request->boolean('allow_remember_device'),
            'remember_device_days' => $request->input('remember_device_days'),
            'withdrawal_threshold' => $request->input('withdrawal_threshold'),
            'transfer_threshold' => $request->input('transfer_threshold'),
        ]);

        $settings->save();

        // Clear cache
        TwoFactorSetting::clearCache();

        return redirect()->back()->with('success', 'บันทึกการตั้งค่า 2FA เรียบร้อยแล้ว');
    }
}
