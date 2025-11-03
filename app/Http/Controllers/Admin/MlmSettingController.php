<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmSetting;
use Illuminate\Http\Request;

class MlmSettingController extends Controller
{
    public function index()
    {
        $settings = MlmSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.mlm.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = MlmSetting::where('key', $key)->first();

            if ($setting) {
                $setting->setTypedValue($value);
            }
        }

        return redirect()
            ->route('admin.mlm.settings.index')
            ->with('success', 'Settings updated successfully');
    }

    public function create()
    {
        return view('admin.mlm.settings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:mlm_settings,key',
            'value' => 'required|string',
            'type' => 'required|in:string,number,boolean,json',
            'group' => 'required|string',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
        ]);

        MlmSetting::create($validated);

        return redirect()
            ->route('admin.mlm.settings.index')
            ->with('success', 'Setting created successfully');
    }

    public function destroy(MlmSetting $setting)
    {
        $setting->delete();

        return redirect()
            ->route('admin.mlm.settings.index')
            ->with('success', 'Setting deleted successfully');
    }
}
