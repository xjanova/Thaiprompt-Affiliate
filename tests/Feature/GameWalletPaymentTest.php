<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameSkin;
use App\Models\GameTournament;
use App\Models\GameTransaction;
use App\Models\TournamentParticipant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * จ่ายเงินในระบบเกมผ่าน wallet จริง — ต้องใช้ MySQL
 *
 * เดิมร้านสกินและค่าสมัครทัวร์นาเมนต์อ่าน/หัก users.wallet_balance ซึ่งไม่มีคอลัมน์นี้
 * (ยอดเงินจริงอยู่ที่ wallets.balance) → แสดง ฿0 ทุกคน และหักเงินแล้ว SQL error
 *
 * - ยอดพอ: หักผ่าน WalletService ครั้งเดียว มี WalletTransaction อ้างอิงรายการ
 * - ยอดไม่พอ: ตอบข้อความภาษาไทย ไม่หักเงิน ไม่มอบสินค้า
 * - กดซ้ำ: คำขอที่สองถูกปฏิเสธ หักเงินแค่ครั้งเดียว
 */
class GameWalletPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithWallet(float $balance): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => $balance, 'currency' => 'THB', 'status' => 'active']);

        return $user;
    }

    private function makeSkin(Game $game, float $price): GameSkin
    {
        return GameSkin::create([
            'game_id' => $game->id,
            'slug' => 'fire-'.$game->id,
            'name' => 'Fire Snake',
            'price' => $price,
            'currency' => 'THB',
            'is_active' => true,
        ]);
    }

    private function makeTournament(Game $game, int $entryFee): GameTournament
    {
        return GameTournament::create([
            'game_id' => $game->id,
            'name' => 'ศึกงูชิงแชมป์',
            'slug' => 'snake-cup-'.$game->id,
            'type' => 'high_score',
            'status' => 'upcoming',
            'max_participants' => 100,
            'entry_fee' => $entryFee,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);
    }

    private function balanceOf(User $user): float
    {
        return (float) Wallet::where('user_id', $user->id)->value('balance');
    }

    private function purchase(User $user, Game $game, GameSkin $skin)
    {
        return $this->actingAs($user)->postJson(route('games.shop.purchase', [
            'slug' => $game->slug,
            'skinId' => $skin->id,
        ]));
    }

    private function register(User $user, GameTournament $tournament)
    {
        return $this->actingAs($user)->postJson(route('tournaments.register', ['slug' => $tournament->slug]));
    }

    // ─── ร้านสกิน ───

    public function test_shop_page_shows_balance_from_wallets_table(): void
    {
        $this->withoutVite();
        $user = $this->makeUserWithWallet(150);
        $game = Game::factory()->create();

        $this->actingAs($user)
            ->get(route('games.shop.index', ['slug' => $game->slug]))
            ->assertOk()
            ->assertViewHas('walletBalance', 150.0);
    }

    public function test_skin_purchase_with_enough_balance_debits_wallet_through_service(): void
    {
        $user = $this->makeUserWithWallet(150);
        $game = Game::factory()->create();
        $skin = $this->makeSkin($game, 100);

        $this->purchase($user, $game, $skin)
            ->assertOk()
            ->assertJson(['success' => true, 'new_balance' => 50]);

        $this->assertSame(50.0, $this->balanceOf($user));
        $this->assertTrue($user->gameSkins()->where('skin_id', $skin->id)->exists());

        $walletTx = WalletTransaction::where('user_id', $user->id)->sole();
        $this->assertSame('fee', $walletTx->type);
        $this->assertSame('game_skin', $walletTx->reference_type);
        $this->assertSame($skin->id, (int) $walletTx->reference_id);
        $this->assertEquals(100, $walletTx->amount);
        $this->assertEquals(150, $walletTx->balance_before);
        $this->assertEquals(50, $walletTx->balance_after);

        $gameTx = GameTransaction::where('user_id', $user->id)->sole();
        $this->assertEquals(150, $gameTx->wallet_balance_before);
        $this->assertEquals(50, $gameTx->wallet_balance_after);
    }

    public function test_skin_purchase_with_insufficient_balance_is_refused_without_deduction(): void
    {
        $user = $this->makeUserWithWallet(50);
        $game = Game::factory()->create();
        $skin = $this->makeSkin($game, 100);

        $this->purchase($user, $game, $skin)
            ->assertStatus(400)
            ->assertJson(['error' => 'ยอดเงินในกระเป๋าไม่เพียงพอ', 'required' => 100, 'balance' => 50]);

        $this->assertSame(50.0, $this->balanceOf($user));
        $this->assertSame(0, WalletTransaction::where('user_id', $user->id)->count());
        $this->assertSame(0, GameTransaction::where('user_id', $user->id)->count());
        $this->assertFalse($user->gameSkins()->where('skin_id', $skin->id)->exists());
    }

    public function test_skin_purchase_without_wallet_is_refused_as_insufficient(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $skin = $this->makeSkin($game, 100);

        $this->purchase($user, $game, $skin)
            ->assertStatus(400)
            ->assertJson(['error' => 'ยอดเงินในกระเป๋าไม่เพียงพอ', 'balance' => 0]);

        $this->assertFalse($user->gameSkins()->where('skin_id', $skin->id)->exists());
    }

    public function test_skin_purchase_double_submit_debits_only_once(): void
    {
        $user = $this->makeUserWithWallet(300);
        $game = Game::factory()->create();
        $skin = $this->makeSkin($game, 100);

        $this->purchase($user, $game, $skin)->assertOk();
        $this->purchase($user, $game, $skin)
            ->assertStatus(400)
            ->assertJson(['error' => 'คุณมีสกินนี้อยู่แล้ว']);

        $this->assertSame(200.0, $this->balanceOf($user));
        $this->assertSame(1, WalletTransaction::where('user_id', $user->id)->count());
        $this->assertSame(1, GameTransaction::where('user_id', $user->id)->count());
        $this->assertSame(1, $user->gameSkins()->where('skin_id', $skin->id)->count());
    }

    public function test_free_skin_needs_no_wallet(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $skin = $this->makeSkin($game, 0);

        $this->purchase($user, $game, $skin)->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($user->gameSkins()->where('skin_id', $skin->id)->exists());
        $this->assertSame(0, WalletTransaction::where('user_id', $user->id)->count());
    }

    // ─── ค่าสมัครทัวร์นาเมนต์ ───

    public function test_tournament_entry_with_enough_balance_debits_wallet_through_service(): void
    {
        $user = $this->makeUserWithWallet(100);
        $tournament = $this->makeTournament(Game::factory()->create(), 30);

        $this->register($user, $tournament)->assertOk()->assertJson(['success' => true]);

        $participant = TournamentParticipant::where('tournament_id', $tournament->id)->sole();
        $this->assertSame($user->id, (int) $participant->user_id);
        $this->assertTrue($participant->paid_entry);

        $this->assertSame(70.0, $this->balanceOf($user));

        $walletTx = WalletTransaction::where('user_id', $user->id)->sole();
        $this->assertSame('tournament_entry', $walletTx->reference_type);
        $this->assertSame($participant->id, (int) $walletTx->reference_id);
        $this->assertEquals(30, $walletTx->amount);
    }

    public function test_tournament_entry_with_insufficient_balance_is_refused_without_deduction(): void
    {
        $user = $this->makeUserWithWallet(10);
        $tournament = $this->makeTournament(Game::factory()->create(), 30);

        $this->register($user, $tournament)
            ->assertStatus(400)
            ->assertJson(['error' => 'ยอดเงินในกระเป๋าไม่เพียงพอสำหรับค่าสมัคร', 'required' => 30, 'balance' => 10]);

        $this->assertSame(10.0, $this->balanceOf($user));
        $this->assertSame(0, WalletTransaction::where('user_id', $user->id)->count());
        $this->assertSame(0, TournamentParticipant::where('tournament_id', $tournament->id)->count());
    }

    public function test_tournament_entry_double_submit_debits_only_once(): void
    {
        $user = $this->makeUserWithWallet(100);
        $tournament = $this->makeTournament(Game::factory()->create(), 30);

        $this->register($user, $tournament)->assertOk();
        $this->register($user, $tournament)
            ->assertStatus(400)
            ->assertJson(['error' => 'คุณสมัครทัวร์นาเมนต์นี้แล้ว']);

        $this->assertSame(70.0, $this->balanceOf($user));
        $this->assertSame(1, WalletTransaction::where('user_id', $user->id)->count());
        $this->assertSame(1, TournamentParticipant::where('tournament_id', $tournament->id)->count());
    }

    public function test_free_tournament_needs_no_wallet(): void
    {
        $user = User::factory()->create();
        $tournament = $this->makeTournament(Game::factory()->create(), 0);

        $this->register($user, $tournament)->assertOk()->assertJson(['success' => true]);

        $this->assertFalse(TournamentParticipant::where('tournament_id', $tournament->id)->sole()->paid_entry);
        $this->assertSame(0, WalletTransaction::where('user_id', $user->id)->count());
    }
}
