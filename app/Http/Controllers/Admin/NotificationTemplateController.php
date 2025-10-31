<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationTemplateController extends Controller
{
    /**
     * Display all templates
     */
    public function index()
    {
        $templates = NotificationTemplate::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.notification-templates.index', compact('templates'));
    }

    /**
     * Show create template form
     */
    public function create()
    {
        return view('admin.notification-templates.create');
    }

    /**
     * Store new template
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'priority' => 'required|in:low,normal,high,urgent',
            'is_important' => 'boolean',
            'show_immediately' => 'boolean',
            'action_url' => 'nullable|string|max:255',
            'action_text' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template = NotificationTemplate::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'icon' => $request->icon,
            'color' => $request->color,
            'priority' => $request->priority,
            'is_important' => $request->boolean('is_important'),
            'show_immediately' => $request->boolean('show_immediately'),
            'action_url' => $request->action_url,
            'action_text' => $request->action_text,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'สร้างเทมเพลตเรียบร้อยแล้ว');
    }

    /**
     * Show template details
     */
    public function show($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return view('admin.notification-templates.show', compact('template'));
    }

    /**
     * Show edit template form
     */
    public function edit($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return view('admin.notification-templates.edit', compact('template'));
    }

    /**
     * Update template
     */
    public function update(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'priority' => 'required|in:low,normal,high,urgent',
            'is_important' => 'boolean',
            'show_immediately' => 'boolean',
            'action_url' => 'nullable|string|max:255',
            'action_text' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'icon' => $request->icon,
            'color' => $request->color,
            'priority' => $request->priority,
            'is_important' => $request->boolean('is_important'),
            'show_immediately' => $request->boolean('show_immediately'),
            'action_url' => $request->action_url,
            'action_text' => $request->action_text,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'อัปเดตเทมเพลตเรียบร้อยแล้ว');
    }

    /**
     * Delete template
     */
    public function destroy($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        return redirect()
            ->route('admin.notification-templates.index')
            ->with('success', 'ลบเทมเพลตเรียบร้อยแล้ว');
    }

    /**
     * Toggle template status
     */
    public function toggleStatus($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update([
            'is_active' => !$template->is_active,
        ]);

        return redirect()
            ->back()
            ->with('success', 'อัปเดตสถานะเทมเพลตเรียบร้อยแล้ว');
    }

    /**
     * Get template data (API endpoint for form)
     */
    public function getTemplate($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->incrementUsage();

        return response()->json([
            'type' => $template->type,
            'title' => $template->title,
            'message' => $template->message,
            'icon' => $template->icon,
            'color' => $template->color,
            'priority' => $template->priority,
            'is_important' => $template->is_important,
            'show_immediately' => $template->show_immediately,
            'action_url' => $template->action_url,
            'action_text' => $template->action_text,
        ]);
    }
}
