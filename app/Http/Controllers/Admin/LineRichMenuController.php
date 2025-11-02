<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineRichMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LineRichMenuController extends Controller
{
    public function index()
    {
        $menus = LineRichMenu::orderBy('created_at', 'desc')->get();
        return view('admin.line-bot.rich-menu.index', compact('menus'));
    }

    public function create()
    {
        return view('admin.line-bot.rich-menu.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size' => 'required|in:full,half',
            'selected' => 'boolean',
            'chat_bar_text' => 'required|string|max:14',
            'areas' => 'required|json',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('rich-menus', 'public');
            $validated['image_path'] = $path;
        }

        $validated['areas'] = json_decode($validated['areas'], true);
        $validated['created_by'] = Auth::id();

        $menu = LineRichMenu::create($validated);

        if ($request->is_default) {
            $menu->setAsDefault();
        }

        return redirect()->route('admin.line-bot.rich-menu.index')
            ->with('success', 'สร้าง Rich Menu สำเร็จ');
    }

    public function edit($id)
    {
        $menu = LineRichMenu::findOrFail($id);
        return view('admin.line-bot.rich-menu.edit', compact('menu'));
    }

    public function update(Request $request, $id)
    {
        $menu = LineRichMenu::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'size' => 'required|in:full,half',
            'selected' => 'boolean',
            'chat_bar_text' => 'required|string|max:14',
            'areas' => 'required|json',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($menu->image_path) {
                Storage::disk('public')->delete($menu->image_path);
            }
            $path = $request->file('image')->store('rich-menus', 'public');
            $validated['image_path'] = $path;
        }

        $validated['areas'] = json_decode($validated['areas'], true);

        $menu->update($validated);

        if ($request->is_default && !$menu->is_default) {
            $menu->setAsDefault();
        }

        return redirect()->route('admin.line-bot.rich-menu.index')
            ->with('success', 'อัปเดต Rich Menu สำเร็จ');
    }

    public function destroy($id)
    {
        $menu = LineRichMenu::findOrFail($id);

        if ($menu->image_path) {
            Storage::disk('public')->delete($menu->image_path);
        }

        $menu->delete();

        return redirect()->route('admin.line-bot.rich-menu.index')
            ->with('success', 'ลบ Rich Menu สำเร็จ');
    }

    public function setDefault($id)
    {
        $menu = LineRichMenu::findOrFail($id);
        $menu->setAsDefault();

        return back()->with('success', 'ตั้งเป็น Rich Menu หลักสำเร็จ');
    }
}
