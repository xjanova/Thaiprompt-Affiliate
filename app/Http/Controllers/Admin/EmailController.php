<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\EmailProvider;
use App\Models\EmailTemplate;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    public function __construct(
        protected EmailService $emailService
    ) {}

    /**
     * Display email dashboard.
     */
    public function index()
    {
        $stats = $this->emailService->getStats([
            'date_from' => now()->subDays(30),
        ]);

        $providers = EmailProvider::all();
        $recentLogs = EmailLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.email.index', compact('stats', 'providers', 'recentLogs'));
    }

    /**
     * Display email logs.
     */
    public function logs(Request $request)
    {
        $query = EmailLog::with('user');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message_id', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(50);

        return view('admin.email.logs', compact('logs'));
    }

    /**
     * Show email log detail.
     */
    public function showLog(EmailLog $log)
    {
        return view('admin.email.log-detail', compact('log'));
    }

    /**
     * Display email providers.
     */
    public function providers()
    {
        $providers = EmailProvider::all();

        return view('admin.email.providers', compact('providers'));
    }

    /**
     * Show create provider form.
     */
    public function createProvider()
    {
        return view('admin.email.provider-create');
    }

    /**
     * Store new email provider.
     */
    public function storeProvider(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:email_providers',
            'display_name' => 'required|string',
            'type' => 'required|in:smtp,api',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'daily_limit' => 'nullable|integer|min:1',
            'hourly_limit' => 'nullable|integer|min:1',
            'configuration' => 'required|array',
        ]);

        $provider = EmailProvider::create($validated);

        if ($validated['is_default'] ?? false) {
            $provider->setAsDefault();
        }

        return redirect()
            ->route('admin.email.providers')
            ->with('success', 'Email provider created successfully');
    }

    /**
     * Show edit provider form.
     */
    public function editProvider(EmailProvider $provider)
    {
        return view('admin.email.provider-edit', compact('provider'));
    }

    /**
     * Update email provider.
     */
    public function updateProvider(Request $request, EmailProvider $provider)
    {
        $validated = $request->validate([
            'display_name' => 'required|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'daily_limit' => 'nullable|integer|min:1',
            'hourly_limit' => 'nullable|integer|min:1',
            'configuration' => 'required|array',
        ]);

        $provider->update($validated);

        if ($validated['is_default'] ?? false) {
            $provider->setAsDefault();
        }

        return redirect()
            ->route('admin.email.providers')
            ->with('success', 'Email provider updated successfully');
    }

    /**
     * Delete email provider.
     */
    public function destroyProvider(EmailProvider $provider)
    {
        if ($provider->is_default) {
            return back()->with('error', 'Cannot delete default provider');
        }

        $provider->delete();

        return redirect()
            ->route('admin.email.providers')
            ->with('success', 'Email provider deleted successfully');
    }

    /**
     * Test email provider.
     */
    public function testProvider(EmailProvider $provider)
    {
        $result = $this->emailService->testProvider($provider);

        return response()->json($result);
    }

    /**
     * Set default provider.
     */
    public function setDefaultProvider(EmailProvider $provider)
    {
        $provider->setAsDefault();

        return redirect()
            ->route('admin.email.providers')
            ->with('success', 'Default provider updated successfully');
    }

    /**
     * Display email templates.
     */
    public function templates()
    {
        $templates = EmailTemplate::all();

        return view('admin.email.templates', compact('templates'));
    }

    /**
     * Show create template form.
     */
    public function createTemplate()
    {
        return view('admin.email.template-create');
    }

    /**
     * Store new email template.
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:email_templates',
            'subject' => 'required|string',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'required|in:system,marketing,transactional',
            'variables' => 'nullable|string',
            'is_active' => 'boolean',
            'language' => 'required|string|max:10',
            'description' => 'nullable|string',
        ]);

        // Convert comma-separated string to array
        if (!empty($validated['variables'])) {
            $validated['variables'] = array_map('trim', explode(',', $validated['variables']));
        }

        // Set is_active default
        $validated['is_active'] = $request->has('is_active');

        EmailTemplate::create($validated);

        return redirect()
            ->route('admin.email.templates')
            ->with('success', 'Email template created successfully');
    }

    /**
     * Show edit template form.
     */
    public function editTemplate(EmailTemplate $template)
    {
        return view('admin.email.template-edit', compact('template'));
    }

    /**
     * Update email template.
     */
    public function updateTemplate(Request $request, EmailTemplate $template)
    {
        $validated = $request->validate([
            'subject' => 'required|string',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'required|in:system,marketing,transactional',
            'variables' => 'nullable|string',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Convert comma-separated string to array
        if (!empty($validated['variables'])) {
            $validated['variables'] = array_map('trim', explode(',', $validated['variables']));
        }

        // Set is_active
        $validated['is_active'] = $request->has('is_active');

        $template->update($validated);

        return redirect()
            ->route('admin.email.templates')
            ->with('success', 'Email template updated successfully');
    }

    /**
     * Delete email template.
     */
    public function destroyTemplate(EmailTemplate $template)
    {
        $template->delete();

        return redirect()
            ->route('admin.email.templates')
            ->with('success', 'Email template deleted successfully');
    }

    /**
     * Send test email.
     */
    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'provider' => 'nullable|string',
        ]);

        $result = $this->emailService->send([
            'to' => $validated['to'],
            'subject' => 'Test Email from TP-Affiliate',
            'body_html' => '<h1>Test Email</h1><p>This is a test email from the TP-Affiliate email delivery system.</p>',
            'body_text' => 'Test Email - This is a test email from the TP-Affiliate email delivery system.',
            'provider' => $validated['provider'] ?? null,
        ]);

        return response()->json($result);
    }

    /**
     * Get email statistics.
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30));
        $dateTo = $request->input('date_to', now());

        $stats = $this->emailService->getStats([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        // Daily stats for chart
        $dailyStats = EmailLog::selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date', 'status')
            ->get()
            ->groupBy('date');

        return response()->json([
            'overall' => $stats,
            'daily' => $dailyStats,
        ]);
    }
}
