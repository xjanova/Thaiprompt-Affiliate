<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineSignupFlow;
use Illuminate\Http\Request;

class LineSignupFlowController extends Controller
{
    /**
     * Display signup flows list
     */
    public function index()
    {
        $flows = LineSignupFlow::ordered()->get();

        return view('admin.line-signup-flow.index', compact('flows'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $availableSteps = LineSignupFlow::all();

        return view('admin.line-signup-flow.create', compact('availableSteps'));
    }

    /**
     * Store new signup flow
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'step_key' => 'required|string|max:255|unique:line_signup_flows',
            'step_order' => 'required|integer',
            'message_text' => 'required|string',
            'input_type' => 'required|in:text,phone,email,name,confirm,choice,none',
            'next_step_key' => 'nullable|string|exists:line_signup_flows,step_key',
            'is_active' => 'boolean',
            'is_skippable' => 'boolean',
            'require_ai' => 'boolean',
        ]);

        $flow = LineSignupFlow::create($validated);

        return redirect()->route('admin.line-signup-flow.index')
            ->with('success', 'สร้าง Signup Flow สำเร็จ!');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $flow = LineSignupFlow::findOrFail($id);
        $availableSteps = LineSignupFlow::where('id', '!=', $id)->get();

        return view('admin.line-signup-flow.edit', compact('flow', 'availableSteps'));
    }

    /**
     * Update signup flow
     */
    public function update(Request $request, $id)
    {
        $flow = LineSignupFlow::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'step_key' => 'required|string|max:255|unique:line_signup_flows,step_key,' . $id,
            'step_order' => 'required|integer',
            'message_text' => 'required|string',
            'input_type' => 'required|in:text,phone,email,name,confirm,choice,none',
            'next_step_key' => 'nullable|string|exists:line_signup_flows,step_key',
            'is_active' => 'boolean',
            'is_skippable' => 'boolean',
            'require_ai' => 'boolean',
        ]);

        $flow->update($validated);

        return redirect()->route('admin.line-signup-flow.index')
            ->with('success', 'อัพเดท Signup Flow สำเร็จ!');
    }

    /**
     * Delete signup flow
     */
    public function destroy($id)
    {
        $flow = LineSignupFlow::findOrFail($id);
        $flow->delete();

        return redirect()->route('admin.line-signup-flow.index')
            ->with('success', 'ลบ Signup Flow สำเร็จ!');
    }

    /**
     * Reorder signup flows
     */
    public function reorder(Request $request)
    {
        $order = $request->input('order', []);

        foreach ($order as $index => $id) {
            LineSignupFlow::where('id', $id)->update(['step_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
