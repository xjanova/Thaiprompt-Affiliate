<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WindowsUiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WindowsUiController extends Controller
{
    /**
     * Display Windows UI settings dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = WindowsUiSetting::getAll();

        return view('admin.windows-ui.index', compact('settings'));
    }

    /**
     * Update Windows UI settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Taskbar Settings
            'windows_taskbar_position' => ['nullable', 'string', 'in:top,bottom'],
            'windows_taskbar_height' => ['nullable', 'integer', 'min:32', 'max:80'],
            'windows_taskbar_blur' => ['nullable', 'boolean'],
            'windows_taskbar_transparency' => ['nullable', 'integer', 'min:0', 'max:100'],

            // Start Button Settings
            'windows_start_button_text' => ['nullable', 'string', 'max:50'],
            'windows_start_button_use_logo' => ['nullable', 'boolean'],

            // Millennium Taskbar Settings
            'millennium_back_button_enabled' => ['nullable', 'boolean'],
            'millennium_back_button_text' => ['nullable', 'string', 'max:20'],
            'millennium_center_section_enabled' => ['nullable', 'boolean'],
            'millennium_center_section_text' => ['nullable', 'string', 'max:100'],
            'millennium_rgb_enabled' => ['nullable', 'boolean'],
            'millennium_rgb_speed' => ['nullable', 'integer', 'min:1', 'max:10'],

            // Millennium Start Menu Settings
            'millennium_menu_position' => ['nullable', 'string', 'in:left,center,right'],
            'millennium_menu_width' => ['nullable', 'string', 'max:20'],
            'millennium_menu_width_unit' => ['nullable', 'string', 'in:px,%'],
            'millennium_menu_max_height' => ['nullable', 'string', 'max:20'],
            'millennium_menu_max_height_unit' => ['nullable', 'string', 'in:px,%,vh'],

            // RGB Settings
            'windows_rgb_enabled' => ['nullable', 'boolean'],
            'windows_rgb_speed' => ['nullable', 'integer', 'min:1', 'max:10'],
            'windows_rgb_glow' => ['nullable', 'boolean'],

            // Theme Settings
            'windows_theme_mode' => ['nullable', 'string', 'in:auto,light,dark'],
            'windows_accent_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],

            // Spaceship Theme
            'windows_spaceship_theme' => ['nullable', 'boolean'],
            'windows_spaceship_stars' => ['nullable', 'boolean'],

            // System Tray Info
            'windows_license_text' => ['nullable', 'string', 'max:100'],
            'windows_copyright_text' => ['nullable', 'string', 'max:200'],

            // Content Width Settings
            'content_width_mode' => ['nullable', 'string', 'in:max,container,custom'],
            'content_width_custom' => ['nullable', 'integer', 'min:800', 'max:3000'],
        ]);

        // Handle checkboxes
        $validated['windows_taskbar_blur'] = $request->has('windows_taskbar_blur');
        $validated['windows_start_button_use_logo'] = $request->has('windows_start_button_use_logo');
        $validated['millennium_back_button_enabled'] = $request->has('millennium_back_button_enabled');
        $validated['millennium_center_section_enabled'] = $request->has('millennium_center_section_enabled');
        $validated['millennium_rgb_enabled'] = $request->has('millennium_rgb_enabled');
        $validated['windows_rgb_enabled'] = $request->has('windows_rgb_enabled');
        $validated['windows_rgb_glow'] = $request->has('windows_rgb_glow');
        $validated['windows_spaceship_theme'] = $request->has('windows_spaceship_theme');
        $validated['windows_spaceship_stars'] = $request->has('windows_spaceship_stars');

        // Save each setting
        foreach ($validated as $key => $value) {
            $type = $this->getSettingType($key, $value);
            WindowsUiSetting::set($key, $value, $type);
        }

        return redirect()->route('admin.windows-ui.index')
            ->with('success', 'อัพเดตการตั้งค่า Windows UI สำเร็จ');
    }

    /**
     * Manage Start Menu items
     *
     * @return \Illuminate\View\View
     */
    public function startMenu()
    {
        $items = WindowsUiSetting::getStartMenuItems();

        return view('admin.windows-ui.start-menu', compact('items'));
    }

    /**
     * Update Start Menu items
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStartMenu(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.icon' => ['required', 'string', 'max:10'],
            'items.*.label' => ['required', 'string', 'max:100'],
            'items.*.url' => ['required', 'string', 'max:500'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ]);

        WindowsUiSetting::set('windows_start_menu_items', $validated['items']);

        return redirect()->route('admin.windows-ui.start-menu')
            ->with('success', 'อัพเดตเมนู Start สำเร็จ');
    }

    /**
     * Manage Taskbar Apps
     *
     * @return \Illuminate\View\View
     */
    public function taskbarApps()
    {
        $apps = WindowsUiSetting::getTaskbarApps();

        return view('admin.windows-ui.taskbar-apps', compact('apps'));
    }

    /**
     * Update Taskbar Apps
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTaskbarApps(Request $request)
    {
        $validated = $request->validate([
            'apps' => ['required', 'array'],
            'apps.*.icon' => ['required', 'string', 'max:10'],
            'apps.*.label' => ['required', 'string', 'max:100'],
            'apps.*.url' => ['required', 'string', 'max:500'],
            'apps.*.order' => ['required', 'integer', 'min:0'],
        ]);

        WindowsUiSetting::set('windows_taskbar_apps', $validated['apps']);

        return redirect()->route('admin.windows-ui.taskbar-apps')
            ->with('success', 'อัพเดต Taskbar Apps สำเร็จ');
    }

    /**
     * Manage System Tray Icons
     *
     * @return \Illuminate\View\View
     */
    public function systemTray()
    {
        $icons = WindowsUiSetting::getSystemTrayIcons();

        return view('admin.windows-ui.system-tray', compact('icons'));
    }

    /**
     * Update System Tray Icons
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSystemTray(Request $request)
    {
        $validated = $request->validate([
            'icons' => ['required', 'array'],
            'icons.*.icon' => ['required', 'string', 'max:20'],
            'icons.*.label' => ['required', 'string', 'max:100'],
            'icons.*.url' => ['required', 'string', 'max:500'],
            'icons.*.requires_auth' => ['nullable', 'boolean'],
            'icons.*.requires_guest' => ['nullable', 'boolean'],
            'icons.*.order' => ['required', 'integer', 'min:0'],
        ]);

        WindowsUiSetting::set('windows_system_tray_icons', $validated['icons']);

        return redirect()->route('admin.windows-ui.system-tray')
            ->with('success', 'อัพเดต System Tray สำเร็จ');
    }

    /**
     * Manage RGB Settings
     *
     * @return \Illuminate\View\View
     */
    public function rgbSettings()
    {
        $rgbSettings = WindowsUiSetting::getRgbSettings();

        return view('admin.windows-ui.rgb-settings', compact('rgbSettings'));
    }

    /**
     * Update RGB Settings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRgbSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'colors' => ['required', 'array'],
            'colors.*' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'speed' => ['required', 'integer', 'min:1', 'max:10'],
            'glow' => ['nullable', 'boolean'],
        ]);

        WindowsUiSetting::set('windows_rgb_enabled', $request->has('enabled'));
        WindowsUiSetting::set('windows_rgb_colors', $validated['colors']);
        WindowsUiSetting::set('windows_rgb_speed', $validated['speed']);
        WindowsUiSetting::set('windows_rgb_glow', $request->has('glow'));

        return redirect()->route('admin.windows-ui.rgb-settings')
            ->with('success', 'อัพเดตการตั้งค่า RGB สำเร็จ');
    }

    /**
     * Get setting type based on key and value
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function getSettingType(string $key, $value): string
    {
        // Boolean types
        if (in_array($key, [
            'windows_taskbar_blur',
            'windows_start_button_use_logo',
            'millennium_back_button_enabled',
            'millennium_center_section_enabled',
            'millennium_rgb_enabled',
            'windows_rgb_enabled',
            'windows_rgb_glow',
            'windows_spaceship_theme',
            'windows_spaceship_stars',
        ])) {
            return 'boolean';
        }

        // Integer types
        if (in_array($key, [
            'windows_taskbar_height',
            'windows_taskbar_transparency',
            'millennium_rgb_speed',
            'windows_rgb_speed',
            'content_width_custom',
        ])) {
            return 'integer';
        }

        // Color types
        if (in_array($key, ['windows_accent_color'])) {
            return 'color';
        }

        // Default to string
        return 'string';
    }
}
