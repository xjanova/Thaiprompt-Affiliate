<?php

namespace App\Http\Controllers;

use App\Models\GameTournament;
use App\Models\TournamentParticipant;
use App\Models\TournamentMatch;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TournamentController extends Controller
{
    /**
     * List all tournaments
     */
    public function index()
    {
        $activeTournaments = GameTournament::active()
            ->with('game')
            ->withCount('participants')
            ->orderBy('starts_at')
            ->get();

        $upcomingTournaments = GameTournament::upcoming()
            ->with('game')
            ->withCount('participants')
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $finishedTournaments = GameTournament::finished()
            ->with('game')
            ->orderBy('ends_at', 'desc')
            ->limit(5)
            ->get();

        return view('tournaments.index', compact('activeTournaments', 'upcomingTournaments', 'finishedTournaments'));
    }

    /**
     * Show specific tournament
     */
    public function show($slug)
    {
        $tournament = GameTournament::where('slug', $slug)
            ->with(['game', 'participants.user'])
            ->withCount('participants')
            ->firstOrFail();

        // Get leaderboard
        $leaderboard = TournamentParticipant::where('tournament_id', $tournament->id)
            ->orderBy('best_score', 'desc')
            ->limit(50)
            ->get();

        // Check if user is registered
        $userParticipant = null;
        if (Auth::check()) {
            $userParticipant = TournamentParticipant::where('tournament_id', $tournament->id)
                ->where('user_id', Auth::id())
                ->first();
        }

        return view('tournaments.show', compact('tournament', 'leaderboard', 'userParticipant'));
    }

    /**
     * Register for tournament
     */
    public function register(Request $request, $slug)
    {
        $tournament = GameTournament::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        if (!$tournament->isRegistrationOpen()) {
            return response()->json(['error' => 'Registration is closed'], 400);
        }

        // Check if already registered
        if (TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Already registered'], 400);
        }

        DB::beginTransaction();
        try {
            // Check entry fee
            if ($tournament->entry_fee > 0) {
                if ($user->wallet_balance < $tournament->entry_fee) {
                    return response()->json(['error' => 'Insufficient balance'], 400);
                }

                // Deduct entry fee
                $user->decrement('wallet_balance', $tournament->entry_fee);
            }

            // Create participant
            $participant = TournamentParticipant::create([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'player_name' => $user->name,
                'paid_entry' => $tournament->entry_fee > 0,
                'registered_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'participant' => $participant,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit tournament match score
     */
    public function submitScore(Request $request, $slug)
    {
        $tournament = GameTournament::where('slug', $slug)->firstOrFail();

        if (!$tournament->isActive()) {
            return response()->json(['error' => 'Tournament is not active'], 400);
        }

        $validated = $request->validate([
            'score' => 'required|integer|min:0',
            'stats' => 'nullable|array',
        ]);

        $participant = TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Create match record
        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
            'score' => $validated['score'],
            'stats' => $validated['stats'] ?? null,
            'played_at' => now(),
        ]);

        // Update participant's best score
        $participant->updateScore($validated['score']);

        return response()->json([
            'success' => true,
            'best_score' => $participant->fresh()->best_score,
        ]);
    }

    /**
     * Get tournament leaderboard (API)
     */
    public function leaderboard($slug)
    {
        $tournament = GameTournament::where('slug', $slug)->firstOrFail();

        $leaderboard = TournamentParticipant::where('tournament_id', $tournament->id)
            ->with('user:id,name,profile_picture')
            ->orderBy('best_score', 'desc')
            ->get()
            ->map(function ($participant, $index) {
                return [
                    'rank' => $index + 1,
                    'player_name' => $participant->player_name,
                    'best_score' => $participant->best_score,
                    'total_games' => $participant->total_games,
                    'user' => $participant->user,
                ];
            });

        return response()->json($leaderboard);
    }
}
