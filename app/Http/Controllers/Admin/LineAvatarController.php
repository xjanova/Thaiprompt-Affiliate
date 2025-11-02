<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineAvatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LineAvatarController extends Controller
{
    public function index()
    {
        $avatars = LineAvatar::orderBy('created_at', 'desc')->get();

        return view('admin.line-bot.avatars.index', compact('avatars'));
    }

    public function create()
    {
        $types = LineAvatar::getTypes();
        return view('admin.line-bot.avatar.create', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:image,gif,lottie,video',
            'source_type' => 'required|in:upload,url',
            'file' => 'required_if:source_type,upload|file|mimes:jpg,jpeg,png,gif,json,mp4,webm|max:5120',
            'file_url' => 'required_if:source_type,url|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($request->source_type === 'upload' && $request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('avatars', 'public');

            $validated['file_path'] = $path;
            $validated['file_size'] = $file->getSize();
            $validated['file_url'] = Storage::url($path);
        } else {
            $validated['file_path'] = $request->file_url;
        }

        $validated['created_by'] = Auth::id();

        $avatar = LineAvatar::create($validated);

        if ($request->is_default) {
            $avatar->setAsDefault();
        }

        return redirect()->route('admin.line-bot.avatar.index')
            ->with('success', 'สร้าง Avatar สำเร็จ');
    }

    public function edit($id)
    {
        $avatar = LineAvatar::findOrFail($id);
        $types = LineAvatar::getTypes();

        return view('admin.line-bot.avatar.edit', compact('avatar', 'types'));
    }

    public function update(Request $request, $id)
    {
        $avatar = LineAvatar::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:image,gif,lottie,video',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $avatar->update($validated);

        if ($request->is_default && !$avatar->is_default) {
            $avatar->setAsDefault();
        }

        return redirect()->route('admin.line-bot.avatar.index')
            ->with('success', 'อัปเดต Avatar สำเร็จ');
    }

    public function destroy($id)
    {
        $avatar = LineAvatar::findOrFail($id);

        if ($avatar->source_type === 'upload' && $avatar->file_path) {
            Storage::disk('public')->delete($avatar->file_path);
        }

        $avatar->delete();

        return redirect()->route('admin.line-bot.avatar.index')
            ->with('success', 'ลบ Avatar สำเร็จ');
    }

    public function setDefault($id)
    {
        $avatar = LineAvatar::findOrFail($id);
        $avatar->setAsDefault();

        return back()->with('success', 'ตั้งเป็น Avatar หลักสำเร็จ');
    }
}
