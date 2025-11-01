<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class PremiumPageController extends Controller
{
    /**
     * Display the premium page management
     */
    public function index()
    {
        $sections = $this->getSections();
        return view('admin.premium-page.index', compact('sections'));
    }

    /**
     * Show the form for editing a specific section
     */
    public function edit($section)
    {
        $sections = $this->getSections();

        if (!isset($sections[$section])) {
            abort(404);
        }

        $sectionData = $sections[$section];
        return view('admin.premium-page.edit', compact('section', 'sectionData'));
    }

    /**
     * Update a specific section
     */
    public function update(Request $request, $section)
    {
        $sections = $this->getSections();

        if (!isset($sections[$section])) {
            abort(404);
        }

        // Validate based on section type
        $validated = $this->validateSection($request, $section);

        // Save to settings
        foreach ($validated as $key => $value) {
            $settingKey = "premium_page_{$section}_{$key}";
            Setting::set($settingKey, $value, is_bool($value) ? 'boolean' : 'string', 'premium_page');
        }

        // Return JSON response if requested via AJAX
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'อัปเดตส่วน ' . $sections[$section]['name'] . ' เรียบร้อยแล้ว'
            ]);
        }

        return redirect()->route('admin.premium-page.index')
            ->with('success', 'อัปเดตส่วน ' . $sections[$section]['name'] . ' เรียบร้อยแล้ว');
    }

    /**
     * Toggle section visibility
     */
    public function toggle($section)
    {
        $sections = $this->getSections();

        if (!isset($sections[$section])) {
            return response()->json(['success' => false], 404);
        }

        $settingKey = "premium_page_{$section}_enabled";
        $currentValue = Setting::get($settingKey, true);
        Setting::set($settingKey, !$currentValue, 'boolean', 'premium_page');

        return response()->json([
            'success' => true,
            'enabled' => !$currentValue
        ]);
    }

    /**
     * Get all sections configuration
     */
    private function getSections()
    {
        return [
            'hero' => [
                'name' => 'Hero Section (ส่วนหัว)',
                'icon' => '🎯',
                'fields' => ['title', 'subtitle', 'description', 'cta_text', 'enabled'],
            ],
            'statistics' => [
                'name' => 'Live Statistics (สถิติ)',
                'icon' => '📊',
                'fields' => ['title', 'subtitle', 'enabled'],
            ],
            'features' => [
                'name' => 'Features (คุณสมบัติเด่น)',
                'icon' => '✨',
                'fields' => ['title', 'subtitle', 'enabled'],
            ],
            'leaderboard' => [
                'name' => 'Top Affiliates (อันดับยอดนิยม)',
                'icon' => '🏆',
                'fields' => ['title', 'subtitle', 'limit', 'enabled'],
            ],
            'how_it_works' => [
                'name' => 'How It Works (วิธีการใช้งาน)',
                'icon' => '📝',
                'fields' => ['title', 'subtitle', 'enabled'],
            ],
            'faq' => [
                'name' => 'FAQ (คำถามที่พบบ่อย)',
                'icon' => '❓',
                'fields' => ['title', 'subtitle', 'enabled'],
            ],
            'cta' => [
                'name' => 'Final CTA (เรียกใช้งาน)',
                'icon' => '🚀',
                'fields' => ['title', 'subtitle', 'cta_text', 'enabled'],
            ],
        ];
    }

    /**
     * Validate section data
     */
    private function validateSection(Request $request, $section)
    {
        $rules = [
            'enabled' => 'nullable|boolean',
        ];

        $sections = $this->getSections();
        $fields = $sections[$section]['fields'];

        if (in_array('title', $fields)) {
            $rules['title'] = 'nullable|string|max:255';
        }
        if (in_array('subtitle', $fields)) {
            $rules['subtitle'] = 'nullable|string|max:500';
        }
        if (in_array('description', $fields)) {
            $rules['description'] = 'nullable|string';
        }
        if (in_array('cta_text', $fields)) {
            $rules['cta_text'] = 'nullable|string|max:100';
        }
        if (in_array('limit', $fields)) {
            $rules['limit'] = 'nullable|integer|min:1|max:20';
        }

        $validated = $request->validate($rules);
        $validated['enabled'] = $request->has('enabled');

        return $validated;
    }
}
