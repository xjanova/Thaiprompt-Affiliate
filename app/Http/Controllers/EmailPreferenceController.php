<?php

namespace App\Http\Controllers;

use App\Models\EmailPreference;
use Illuminate\Http\Request;

class EmailPreferenceController extends Controller
{
    /**
     * Display email preferences.
     */
    public function index()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());

        return view('user.email-preferences', compact('preference'));
    }

    /**
     * Update email preferences.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'marketing_emails' => 'boolean',
            'security_alerts' => 'boolean',
            'commission_notifications' => 'boolean',
            'withdrawal_notifications' => 'boolean',
            'system_announcements' => 'boolean',
            'weekly_reports' => 'boolean',
            'all_emails' => 'boolean',
            'preferred_language' => 'string|in:th,en',
        ]);

        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->update($validated);

        return redirect()
            ->route('user.email.preferences')
            ->with('success', 'Email preferences updated successfully');
    }

    /**
     * Get email preferences (API).
     */
    public function show()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());

        return response()->json($preference);
    }

    /**
     * Update email preferences (API).
     */
    public function updateApi(Request $request)
    {
        $validated = $request->validate([
            'marketing_emails' => 'boolean',
            'security_alerts' => 'boolean',
            'commission_notifications' => 'boolean',
            'withdrawal_notifications' => 'boolean',
            'system_announcements' => 'boolean',
            'weekly_reports' => 'boolean',
            'all_emails' => 'boolean',
            'preferred_language' => 'string|in:th,en',
        ]);

        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Email preferences updated successfully',
            'data' => $preference,
        ]);
    }

    /**
     * Disable all emails.
     */
    public function disableAll()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->disableAll();

        return back()->with('success', 'All email notifications have been disabled');
    }

    /**
     * Enable all emails.
     */
    public function enableAll()
    {
        $preference = EmailPreference::getOrCreateForUser(auth()->id());
        $preference->enableAll();

        return back()->with('success', 'All email notifications have been enabled');
    }
}
