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
            'windows_start_button_position' => ['nullable', 'string', 'in:left,center,right'],

            // Millennium Taskbar Settings
            'millennium_back_button_enabled' => ['nullable', 'boolean'],
            'millennium_back_button_text' => ['nullable', 'string', 'max:20'],
            'millennium_center_section_enabled' => ['nullable', 'boolean'],
            'millennium_center_section_text' => ['nullable', 'string', 'max:100'],
            'millennium_rgb_enabled' => ['nullable', 'boolean'],
            'millennium_rgb_speed' => ['nullable', 'integer', 'min:1', 'max:10'],

            // Millennium Start Menu Settings
            'millennium_menu_position' => ['nullable', 'string', 'in:left,center,right'],
            'millennium_menu_width' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'millennium_menu_width_unit' => ['nullable', 'string', 'in:px,%'],
            'millennium_menu_max_height' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'millennium_menu_max_height_unit' => ['nullable', 'string', 'in:px,%,vh'],
            'millennium_menu_rgb_enabled' => ['nullable', 'boolean'],

            // Responsive Taskbar Settings
            'millennium_taskbar_collapse_enabled' => ['nullable', 'boolean'],
            'millennium_taskbar_collapse_breakpoint' => ['nullable', 'integer', 'min:320', 'max:1920'],

            // Clock Settings
            'millennium_clock_style' => ['nullable', 'string', 'in:digital,minimal,full,hidden'],
            'millennium_clock_format' => ['nullable', 'string', 'in:12h,24h'],
            'millennium_clock_show_seconds' => ['nullable', 'boolean'],
            'millennium_clock_show_date' => ['nullable', 'boolean'],
            'millennium_clock_date_format' => ['nullable', 'string', 'in:short,long'],

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

            // Back to Top Button Settings
            'millennium_back_to_top_enabled' => ['nullable', 'boolean'],
            'millennium_back_to_top_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'millennium_back_to_top_animation' => ['nullable', 'string', 'in:fade,slide,bounce,scale,zoom'],
            'millennium_back_to_top_position' => ['nullable', 'string', 'in:bottom-right,bottom-left,bottom-center'],

            // Start Button Display Settings
            'millennium_start_button_show_icon' => ['nullable', 'boolean'],
            'millennium_start_button_show_text' => ['nullable', 'boolean'],
        ]);

        // Handle checkboxes
        $validated['windows_taskbar_blur'] = $request->has('windows_taskbar_blur');
        $validated['windows_start_button_use_logo'] = $request->has('windows_start_button_use_logo');
        $validated['millennium_back_button_enabled'] = $request->has('millennium_back_button_enabled');
        $validated['millennium_center_section_enabled'] = $request->has('millennium_center_section_enabled');
        $validated['millennium_rgb_enabled'] = $request->has('millennium_rgb_enabled');
        $validated['millennium_menu_rgb_enabled'] = $request->has('millennium_menu_rgb_enabled');
        $validated['millennium_taskbar_collapse_enabled'] = $request->has('millennium_taskbar_collapse_enabled');
        $validated['millennium_clock_show_seconds'] = $request->has('millennium_clock_show_seconds');
        $validated['millennium_clock_show_date'] = $request->has('millennium_clock_show_date');
        $validated['windows_rgb_enabled'] = $request->has('windows_rgb_enabled');
        $validated['windows_rgb_glow'] = $request->has('windows_rgb_glow');
        $validated['windows_spaceship_theme'] = $request->has('windows_spaceship_theme');
        $validated['windows_spaceship_stars'] = $request->has('windows_spaceship_stars');
        $validated['millennium_back_to_top_enabled'] = $request->has('millennium_back_to_top_enabled');
        $validated['millennium_start_button_show_icon'] = $request->has('millennium_start_button_show_icon');
        $validated['millennium_start_button_show_text'] = $request->has('millennium_start_button_show_text');

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
    public function startMenu(Request $request)
    {
        $role = $request->get('role', 'admin'); // admin, seller, user

        // Validate role
        if (!in_array($role, ['admin', 'seller', 'user'])) {
            $role = 'admin';
        }

        $settingKey = 'windows_start_menu_items_' . $role;
        $items = WindowsUiSetting::get($settingKey, []);

        return view('admin.windows-ui.start-menu', compact('items', 'role'));
    }

    /**
     * Update Start Menu items
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStartMenu(Request $request)
    {
        $role = $request->input('role', 'admin');

        // Validate role
        if (!in_array($role, ['admin', 'seller', 'user'])) {
            $role = 'admin';
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.icon' => ['required', 'string', 'max:10'],
            'items.*.label' => ['required', 'string', 'max:100'],
            'items.*.route' => ['nullable', 'string', 'max:500'],
            'items.*.order' => ['required', 'integer', 'min:0'],
            'items.*.has_submenu' => ['nullable', 'boolean'],
            'items.*.submenu' => ['nullable', 'array'],
            'items.*.submenu.*.label' => ['required_with:items.*.submenu', 'string', 'max:100'],
            'items.*.submenu.*.route' => ['required_with:items.*.submenu', 'string', 'max:500'],
        ]);

        // Process menu items to clean up structure
        $menuItems = collect($validated['items'])->map(function($item) {
            $processedItem = [
                'icon' => $item['icon'],
                'label' => $item['label'],
                'route' => $item['route'] ?? null,
                'order' => $item['order'],
            ];

            // Add submenu if exists
            if (!empty($item['has_submenu']) && !empty($item['submenu'])) {
                $processedItem['submenu'] = $item['submenu'];
            }

            return $processedItem;
        })->toArray();

        $settingKey = 'windows_start_menu_items_' . $role;
        WindowsUiSetting::set($settingKey, $menuItems);

        return redirect()->route('admin.windows-ui.start-menu', ['role' => $role])
            ->with('success', 'อัพเดตเมนู Start (' . ucfirst($role) . ') สำเร็จ');
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
            'millennium_menu_rgb_enabled',
            'millennium_menu_item_hover_rgb',
            'millennium_taskbar_collapse_enabled',
            'millennium_clock_show_seconds',
            'millennium_clock_show_date',
            'windows_rgb_enabled',
            'windows_rgb_glow',
            'windows_spaceship_theme',
            'windows_spaceship_stars',
            'millennium_back_to_top_enabled',
            'millennium_start_button_show_icon',
            'millennium_start_button_show_text',
            'millennium_start_button_tooltip_enabled',
        ])) {
            return 'boolean';
        }

        // Integer types
        if (in_array($key, [
            'windows_taskbar_height',
            'windows_taskbar_transparency',
            'millennium_rgb_speed',
            'millennium_taskbar_collapse_breakpoint',
            'windows_rgb_speed',
            'content_width_custom',
            'millennium_back_to_top_threshold',
            'millennium_menu_width',
            'millennium_menu_max_height',
            'millennium_menu_rgb_speed',
            'millennium_menu_rgb_border_width',
            'millennium_menu_rgb_glow_size',
            'millennium_start_button_width',
            'millennium_start_button_height',
            'millennium_start_button_border_radius',
            'millennium_start_button_icon_size',
            'millennium_start_button_font_size',
            'millennium_start_button_tooltip_duration',
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

    /**
     * Update Start Button Settings (from start-menu page)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStartButtonSettings(Request $request)
    {
        $validated = $request->validate([
            // Start Button Position
            'windows_start_button_position' => ['nullable', 'string', 'in:left,center,right'],

            // Start Button Dimensions & Style
            'millennium_start_button_width' => ['nullable', 'integer', 'min:80', 'max:200'],
            'millennium_start_button_height' => ['nullable', 'integer', 'min:32', 'max:80'],
            'millennium_start_button_shape' => ['nullable', 'string', 'in:square,rounded,pill,circle'],
            'millennium_start_button_border_radius' => ['nullable', 'integer', 'min:0', 'max:50'],

            // Start Button Display Options
            'millennium_start_button_show_icon' => ['nullable', 'boolean'],
            'millennium_start_button_show_text' => ['nullable', 'boolean'],
            'millennium_start_button_text' => ['nullable', 'string', 'max:20'],
            'millennium_start_button_icon_size' => ['nullable', 'integer', 'min:16', 'max:64'],
            'millennium_start_button_font_size' => ['nullable', 'integer', 'min:12', 'max:32'],

            // Icon Settings
            'millennium_start_button_icon_type' => ['nullable', 'string', 'in:default,upload,fontawesome,emoji'],
            'millennium_start_button_custom_icon' => ['nullable', 'image', 'max:2048'], // 2MB max
            'millennium_start_button_fontawesome_icon' => ['nullable', 'string', 'max:50'],
            'millennium_start_button_emoji' => ['nullable', 'string', 'max:4'],
            'millennium_start_button_icon_position' => ['nullable', 'string', 'in:left,right'],

            // Button Style Settings
            'millennium_start_button_style' => ['nullable', 'string', 'in:gradient,solid,outline,glass,neon,3d'],
            'millennium_start_button_color_primary' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'millennium_start_button_color_secondary' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'millennium_start_button_text_color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],

            // Start Button Tooltip Settings
            'millennium_start_button_tooltip_enabled' => ['nullable', 'boolean'],
            'millennium_start_button_tooltip_text' => ['nullable', 'string', 'max:100'],
            'millennium_start_button_tooltip_duration' => ['nullable', 'integer', 'min:3', 'max:30'],
            'millennium_start_button_tooltip_position' => ['nullable', 'string', 'in:top,bottom,left,right'],
            'millennium_start_button_tooltip_animation' => ['nullable', 'string', 'in:bounce,pulse,shake,swing,tada,flash'],

            // Responsive Taskbar Settings
            'millennium_taskbar_collapse_enabled' => ['nullable', 'boolean'],
            'millennium_taskbar_collapse_breakpoint' => ['nullable', 'integer', 'min:320', 'max:1920'],
            'millennium_taskbar_collapse_style' => ['nullable', 'string', 'in:dropdown,slide-up,fullscreen'],
        ]);

        // Handle checkboxes (only the ones in this form)
        $validated['millennium_start_button_show_icon'] = $request->has('millennium_start_button_show_icon');
        $validated['millennium_start_button_show_text'] = $request->has('millennium_start_button_show_text');
        $validated['millennium_start_button_tooltip_enabled'] = $request->has('millennium_start_button_tooltip_enabled');
        $validated['millennium_taskbar_collapse_enabled'] = $request->has('millennium_taskbar_collapse_enabled');

        // Handle file upload for custom icon
        if ($request->hasFile('millennium_start_button_custom_icon')) {
            $file = $request->file('millennium_start_button_custom_icon');
            $filename = 'start_button_icon_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('icons', $filename, 'public');

            // Save the path
            WindowsUiSetting::set('millennium_start_button_custom_icon_path', 'storage/' . $path, 'string');
        }

        // Remove the file field from validated data (it's already handled)
        unset($validated['millennium_start_button_custom_icon']);

        // Save each setting (only save fields that have actual values)
        foreach ($validated as $key => $value) {
            // Skip null values to avoid overwriting existing settings
            if ($value === null) {
                continue;
            }

            $type = $this->getSettingType($key, $value);
            WindowsUiSetting::set($key, $value, $type);
        }

        return redirect()->route('admin.windows-ui.start-menu')
            ->with('success', 'อัพเดตการตั้งค่าปุ่ม Start สำเร็จ');
    }

    /**
     * Update Menu RGB Settings (from start-menu page)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMenuRgbSettings(Request $request)
    {
        $validated = $request->validate([
            // Menu RGB Settings
            'millennium_menu_rgb_enabled' => ['nullable', 'boolean'],
            'millennium_menu_item_hover_rgb' => ['nullable', 'boolean'],
            'millennium_menu_rgb_speed' => ['nullable', 'integer', 'min:1', 'max:20'],
            'millennium_menu_rgb_border_width' => ['nullable', 'integer', 'min:1', 'max:10'],
            'millennium_menu_rgb_glow_size' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        // Handle checkboxes
        $validated['millennium_menu_rgb_enabled'] = $request->has('millennium_menu_rgb_enabled');
        $validated['millennium_menu_item_hover_rgb'] = $request->has('millennium_menu_item_hover_rgb');

        // Save each setting (only save fields that have actual values)
        foreach ($validated as $key => $value) {
            // Skip null values to avoid overwriting existing settings
            if ($value === null) {
                continue;
            }

            $type = $this->getSettingType($key, $value);
            WindowsUiSetting::set($key, $value, $type);
        }

        return redirect()->route('admin.windows-ui.start-menu')
            ->with('success', 'อัพเดตการตั้งค่า RGB สำเร็จ');
    }

    /**
     * Update Menu Size & Position Settings (from start-menu page)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMenuSettings(Request $request)
    {
        $validated = $request->validate([
            // Menu Position & Size
            'millennium_menu_position' => ['nullable', 'string', 'in:left,center,right'],
            'millennium_menu_width' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'millennium_menu_width_unit' => ['nullable', 'string', 'in:px,%'],
            'millennium_menu_max_height' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'millennium_menu_max_height_unit' => ['nullable', 'string', 'in:px,%,vh'],

            // Menu RGB (can be set from this form too)
            'millennium_menu_rgb_enabled' => ['nullable', 'boolean'],
        ]);

        // Handle checkbox - always set it (true if checked, false if not)
        $validated['millennium_menu_rgb_enabled'] = $request->has('millennium_menu_rgb_enabled');

        // Save each setting (only save fields that have actual values)
        foreach ($validated as $key => $value) {
            // Skip null values to avoid overwriting existing settings
            if ($value === null) {
                continue;
            }

            $type = $this->getSettingType($key, $value);
            WindowsUiSetting::set($key, $value, $type);
        }

        return redirect()->route('admin.windows-ui.start-menu')
            ->with('success', 'อัพเดตการตั้งค่าเมนู Start สำเร็จ');
    }
}
