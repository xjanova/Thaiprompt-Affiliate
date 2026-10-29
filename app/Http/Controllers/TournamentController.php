<?php

namespace App\Http\Controllers;

use App\Models\GameTournament;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

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
     * สมัครเข้าร่วมทัวร์นาเมนต์ (หักค่าสมัครจาก wallet ถ้ามี)
     *
     * ล็อกแถวทัวร์นาเมนต์ก่อน → การสมัครพร้อมกันต่อคิวกัน ไม่รับเกินจำนวน และกดสมัครซ้ำไม่หักเงินซ้ำ
     */
    public function register(Request $request, $slug)
    {
        $tournament = GameTournament::where('slug', $slug)->firstOrFail();
        $user = Auth::user();
        $entryFee = round((float) $tournament->entry_fee, 2);

        try {
            return DB::transaction(function () use ($tournament, $user, $entryFee) {
                $tournament = GameTournament::whereKey($tournament->id)->lockForUpdate()->first();

                if (! $tournament->isRegistrationOpen()) {
                    return response()->json(['error' => 'ทัวร์นาเมนต์นี้ปิดรับสมัครแล้ว'], 400);
                }

                if (TournamentParticipant::where('tournament_id', $tournament->id)
                    ->where('user_id', $user->id)->exists()) {
                    return response()->json(['error' => 'คุณสมัครทัวร์นาเมนต์นี้แล้ว'], 400);
                }

                $wallet = null;

                if ($entryFee > 0) {
                    $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                    $balance = round((float) ($wallet?->balance ?? 0), 2);

                    if ($wallet && ! $wallet->isActive()) {
                        return response()->json(['error' => 'กระเป๋าเงินของคุณใช้งานไม่ได้ในขณะนี้ กรุณาติดต่อเจ้าหน้าที่'], 403);
                    }

                    if ($balance < $entryFee) {
                        return response()->json([
                            'error' => 'ยอดเงินในกระเป๋าไม่เพียงพอสำหรับค่าสมัคร',
                            'required' => $entryFee,
                            'balance' => $balance,
                        ], 400);
                    }
                }

                $participant = TournamentParticipant::create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $user->id,
                    'player_name' => $user->name,
                    'paid_entry' => $entryFee > 0,
                    'registered_at' => now(),
                ]);

                if ($entryFee > 0) {
                    $this->walletService->deductForService(
                        $wallet,
                        $entryFee,
                        "ค่าสมัครทัวร์นาเมนต์ {$tournament->name}",
                        'tournament_entry',
                        $participant->id,
                        ['tournament_id' => $tournament->id]
                    );
                }

                return response()->json([
                    'success' => true,
                    'participant' => $participant,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['error' => 'คุณสมัครทัวร์นาเมนต์นี้แล้ว'], 400);
        } catch (\Throwable $e) {
            Log::error('Tournament registration failed', [
                'user_id' => $user->id,
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'สมัครทัวร์นาเมนต์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'], 500);
        }
    }

    /**
     * Submit tournament match score
     */
    public function submitScore(Request $request, $slug)
    {
        $tournament = GameTournament::where('slug', $slug)->firstOrFail();

        if (! $tournament->isActive()) {
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
