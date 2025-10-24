<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\MlmNetwork;
use App\Services\MLM\MlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MlmServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MlmService $mlmService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mlmService = new MlmService();
    }

    /** @test */
    public function it_can_register_user_in_mlm_network()
    {
        $sponsor = User::factory()->create(['referral_code' => 'SPONSOR123']);
        $user = User::factory()->create();

        $this->mlmService->registerUser($user, $sponsor);

        $this->assertDatabaseHas('mlm_networks', [
            'user_id' => $user->id,
            'sponsor_id' => $sponsor->id,
            'level' => 1
        ]);
    }

    /** @test */
    public function it_builds_genealogy_correctly()
    {
        $sponsor = User::factory()->create();
        $user = User::factory()->create();

        $this->mlmService->buildGenealogy($user, $sponsor);

        $this->assertDatabaseHas('mlm_genealogy', [
            'user_id' => $sponsor->id,
            'downline_id' => $user->id,
            'depth' => 1
        ]);
    }

    /** @test */
    public function it_generates_correct_mlm_path()
    {
        $sponsor = User::factory()->create();
        MlmNetwork::create([
            'user_id' => $sponsor->id,
            'sponsor_id' => null,
            'level' => 0,
            'path' => $sponsor->id
        ]);

        $path = $this->mlmService->generatePath($sponsor);

        $this->assertEquals($sponsor->id, $path);
    }

    /** @test */
    public function it_calculates_team_sales_correctly()
    {
        $sponsor = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        // Register members under sponsor
        $this->mlmService->registerUser($member1, $sponsor);
        $this->mlmService->registerUser($member2, $sponsor);

        // Create orders for members
        Order::factory()->create([
            'user_id' => $member1->id,
            'total_amount' => 1000,
            'status' => 'delivered'
        ]);
        Order::factory()->create([
            'user_id' => $member2->id,
            'total_amount' => 2000,
            'status' => 'delivered'
        ]);

        $teamSales = $this->mlmService->calculateTeamSales($sponsor);

        $this->assertEquals(3000, $teamSales);
    }

    /** @test */
    public function it_distributes_commissions_to_upline()
    {
        // Create sponsor chain
        $level1 = User::factory()->create();
        $level2 = User::factory()->create();
        $level3 = User::factory()->create();

        $this->mlmService->registerUser($level2, $level1);
        $this->mlmService->registerUser($level3, $level2);

        // Create order
        $order = Order::factory()->create([
            'user_id' => $level3->id,
            'total_amount' => 1000,
            'status' => 'delivered'
        ]);

        // Distribute commissions
        $this->mlmService->distributeCommissions($order);

        // Check commissions were created
        $this->assertDatabaseHas('commissions', [
            'user_id' => $level1->id,
            'order_id' => $order->id,
            'type' => 'level_commission'
        ]);

        $this->assertDatabaseHas('commissions', [
            'user_id' => $level2->id,
            'order_id' => $order->id,
            'type' => 'level_commission'
        ]);
    }

    /** @test */
    public function it_gets_downline_members_correctly()
    {
        $sponsor = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $member3 = User::factory()->create();

        // Build network: sponsor -> member1 -> member2
        //                sponsor -> member3
        $this->mlmService->registerUser($member1, $sponsor);
        $this->mlmService->registerUser($member2, $member1);
        $this->mlmService->registerUser($member3, $sponsor);

        $downline = $this->mlmService->getDownline($sponsor, 2);

        $this->assertCount(3, $downline);
        $this->assertTrue($downline->contains($member1));
        $this->assertTrue($downline->contains($member2));
        $this->assertTrue($downline->contains($member3));
    }

    /** @test */
    public function it_calculates_user_stats_correctly()
    {
        $user = User::factory()->create();
        $referral1 = User::factory()->create();
        $referral2 = User::factory()->create();

        $this->mlmService->registerUser($referral1, $user);
        $this->mlmService->registerUser($referral2, $user);

        Order::factory()->create([
            'user_id' => $referral1->id,
            'total_amount' => 1000,
            'status' => 'delivered'
        ]);

        $stats = $this->mlmService->getUserStats($user);

        $this->assertEquals(2, $stats['direct_referrals']);
        $this->assertEquals(2, $stats['total_team']);
        $this->assertGreaterThan(0, $stats['team_sales']);
    }
}
