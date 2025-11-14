<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $games = Game::ordered()->get();
        return view('admin.games.index', compact('games'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.games.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'icon' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'url' => 'required|url',
            'primary_color' => 'required|string|max:50',
            'secondary_color' => 'required|string|max:50',
            'glow_color' => 'required|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'card_style' => 'required|string|in:default,gradient,glass,neon',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images/games'), $imageName);
            $validated['image'] = 'images/games/' . $imageName;
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        Game::create($validated);

        return redirect()->route('admin.games.index')
            ->with('success', 'เกมถูกสร้างเรียบร้อยแล้ว');
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        return view('admin.games.show', compact('game'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Game $game)
    {
        return view('admin.games.edit', compact('game'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'icon' => 'required|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'url' => 'required|url',
            'primary_color' => 'required|string|max:50',
            'secondary_color' => 'required|string|max:50',
            'glow_color' => 'required|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'card_style' => 'required|string|in:default,gradient,glass,neon',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($game->image && file_exists(public_path($game->image))) {
                unlink(public_path($game->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images/games'), $imageName);
            $validated['image'] = 'images/games/' . $imageName;
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        $game->update($validated);

        return redirect()->route('admin.games.index')
            ->with('success', 'เกมถูกอัปเดตเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        // Delete image
        if ($game->image && file_exists(public_path($game->image))) {
            unlink(public_path($game->image));
        }

        $game->delete();

        return redirect()->route('admin.games.index')
            ->with('success', 'เกมถูกลบเรียบร้อยแล้ว');
    }

    /**
     * Update order of games
     */
    public function updateOrder(Request $request)
    {
        $games = $request->input('games', []);

        foreach ($games as $index => $gameId) {
            Game::where('id', $gameId)->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Game $game)
    {
        $game->update(['is_active' => !$game->is_active]);

        return redirect()->back()
            ->with('success', 'สถานะเกมถูกเปลี่ยนเรียบร้อยแล้ว');
    }
}
