<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Models\ThemePreset;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    protected $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Display themes management
     */
    public function index()
    {
        $themes = $this->themeService->getAllThemes(false);
        $presets = ThemePreset::orderBy('is_featured', 'desc')
            ->orderBy('usage_count', 'desc')
            ->get();

        return view('admin.themes.index', compact('themes', 'presets'));
    }

    /**
     * Show theme builder
     */
    public function builder($id = null)
    {
        $theme = $id ? Theme::findOrFail($id) : null;
        $presets = ThemePreset::all();

        return view('admin.themes.builder', compact('theme', 'presets'));
    }

    /**
     * Store new theme
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:themes,name|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'colors' => 'nullable|array',
            'dark_colors' => 'nullable|array',
            'typography' => 'nullable|array',
            'spacing' => 'nullable|array',
            'borders' => 'nullable|array',
            'shadows' => 'nullable|array',
            'animations' => 'nullable|array',
            'custom_css' => 'nullable|array',
        ]);

        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active') ? true : true;
        $validated['created_by'] = auth()->id();

        $theme = $this->themeService->createTheme($validated);

        return redirect()->route('admin.themes.index')
            ->with('success', 'สร้าง Theme สำเร็จ');
    }

    /**
     * Update theme
     */
    public function update(Request $request, $id)
    {
        $theme = Theme::findOrFail($id);

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'colors' => 'nullable|array',
            'dark_colors' => 'nullable|array',
            'typography' => 'nullable|array',
            'spacing' => 'nullable|array',
            'borders' => 'nullable|array',
            'shadows' => 'nullable|array',
            'animations' => 'nullable|array',
            'custom_css' => 'nullable|array',
        ]);

        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');
        $validated['updated_by'] = auth()->id();

        $this->themeService->updateTheme($id, $validated);

        return redirect()->route('admin.themes.index')
            ->with('success', 'อัพเดท Theme สำเร็จ');
    }

    /**
     * Delete theme
     */
    public function destroy($id)
    {
        try {
            $this->themeService->deleteTheme($id);

            return redirect()->route('admin.themes.index')
                ->with('success', 'ลบ Theme สำเร็จ');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Duplicate theme
     */
    public function duplicate(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:themes,name|max:255',
            'display_name' => 'required|string|max:255',
        ]);

        $theme = $this->themeService->duplicateTheme(
            $id,
            $validated['name'],
            $validated['display_name']
        );

        return redirect()->route('admin.themes.builder', $theme->id)
            ->with('success', 'คัดลอก Theme สำเร็จ');
    }

    /**
     * Create from preset
     */
    public function createFromPreset(Request $request, $presetId)
    {
        $validated = $request->validate([
            'display_name' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $theme = $this->themeService->createThemeFromPreset(
            $presetId,
            $validated['display_name'] ?? null,
            $request->has('is_default'),
            auth()->id()
        );

        return redirect()->route('admin.themes.builder', $theme->id)
            ->with('success', 'สร้าง Theme จาก Preset สำเร็จ');
    }

    /**
     * Upload preview image
     */
    public function uploadPreview(Request $request, $id)
    {
        $request->validate([
            'preview' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'mode' => 'required|in:light,dark',
        ]);

        $theme = Theme::findOrFail($id);
        $url = $this->themeService->uploadPreviewImage(
            $theme,
            $request->file('preview'),
            $request->mode
        );

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    /**
     * Get theme statistics
     */
    public function statistics($id)
    {
        $stats = $this->themeService->getThemeStatistics($id);

        return response()->json($stats);
    }

    /**
     * Export theme
     */
    public function export($id)
    {
        $config = $this->themeService->exportTheme($id);
        $theme = Theme::findOrFail($id);

        $filename = "theme-{$theme->name}-" . date('Y-m-d') . ".json";

        return response()->json($config)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Import theme
     */
    public function import(Request $request)
    {
        $request->validate([
            'config_file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $content = file_get_contents($request->file('config_file')->getRealPath());
            $config = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'ไฟล์ JSON ไม่ถูกต้อง');
            }

            $theme = $this->themeService->importTheme($config, auth()->id());

            return redirect()->route('admin.themes.builder', $theme->id)
                ->with('success', 'Import Theme สำเร็จ');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Initialize default themes
     */
    public function initialize()
    {
        try {
            $this->themeService->initializeDefaultThemes();

            return back()->with('success', 'เริ่มต้น Theme เริ่มต้นสำเร็จ');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Set as default
     */
    public function setDefault($id)
    {
        $theme = Theme::findOrFail($id);
        $theme->setAsDefault();

        return back()->with('success', 'ตั้งเป็น Theme เริ่มต้นสำเร็จ');
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        $theme = Theme::findOrFail($id);
        $theme->is_active = !$theme->is_active;
        $theme->save();

        $status = $theme->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "{$status}  Theme สำเร็จ");
    }

    /**
     * Set theme for current admin user
     */
    public function setTheme(Request $request)
    {
        $validated = $request->validate([
            'theme_id' => 'required|exists:themes,id',
            'mode' => 'required|in:light,dark,auto',
        ]);

        $this->themeService->setThemeForUser(
            auth()->id(),
            $validated['theme_id'],
            $validated['mode']
        );

        return response()->json([
            'success' => true,
            'message' => 'เปลี่ยน Theme สำเร็จ'
        ]);
    }

    /**
     * Get current theme CSS
     */
    public function getCss()
    {
        $mode = request('mode', 'light');
        $css = $this->themeService->getCssForUser(auth()->id(), $mode);

        return response($css)->header('Content-Type', 'text/css');
    }
}
