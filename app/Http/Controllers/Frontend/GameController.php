<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Display the game selector page
     */
    public function index()
    {
        $games = Game::active()->ordered()->get();
        return view('demo-game-selector', compact('games'));
    }

    /**
     * Get games as JSON for API
     */
    public function getGames()
    {
        $games = Game::active()->ordered()->get();
        return response()->json($games);
    }
}
