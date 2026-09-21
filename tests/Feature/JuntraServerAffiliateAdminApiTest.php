<?php

namespace Tests\Feature;

use App\Models\FortuneCommission;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsJuntraAffiliateWorld;
use Tests\TestCase;

/**
 * 🌙 /api/v1/juntra/server/affiliate/admin/* — หลังบ้านจันทราจัดการผังแม่หมอผ่าน API
 *
 * เจ้าของสั่ง (2026-09-21): หลังบ้านจันทราทำได้ครบเหมือนหลังบ้านแม่หมอ ข้อมูลเก็บ/คำนวณที่แม่หมอ
 *   ทุกคำสั่งใช้ service ตัวเดียวกับหลังบ้านแม่หมอ — เทสต์นี้ตรึงกติกาเงินที่เคยพัง:
 *   กดจ่ายซ้ำห้ามจ่ายซ้ำ · ห้ามปรับยอดรายการที่เข้ากระเป๋าแล้ว · ห้ามย้ายสายเข้าลูกทีมตัวเอง
 *
 * @group fortune-commission
 */
class JuntraServerAffiliateAdminApiTest extends TestCase
{
    use BuildsJuntraAffiliateWorld;
    use RefreshDatabase;

    private const ACTOR = ['juntra_user_id' => 7, 'name' => 'แอดมินจันทรา'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraAffiliateWorld();
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    public function test_paying_twice_pays_once(): void
    {
        $commission = $this->pendingCommission(12.5);

        $first = $this->postJson('/api/v1/juntra/server/affiliate/admin/commissions/pay', [
            'ids' => [$commission->id], 'actor' => self::ACTOR,
        ])->assertOk()->assertJsonPath('data.count', 1);

        $this->postJson('/api/v1/juntra/server/affiliate/admin/commissions/pay', [
            'ids' => [$commission->id, $commission->id], 'actor' => self::ACTOR,
        ])->assertOk()->assertJsonPath('data.count', 0);

        $this->assertSame(FortuneCommission::STATUS_PAID, $commission->fresh()->status);
        $this->assertEquals(12.5, (float) Wallet::where('user_id', $commission->user_id)->value('balance'));
        $this->assertNotNull($first->json('data'));
    }

    public function test_paid_commission_amount_cannot_be_adjusted(): void
    {
        $pending = $this->pendingCommission(10);
        $this->postJson("/api/v1/juntra/server/affiliate/admin/commissions/{$pending->id}/adjust", [
            'amount' => 7, 'reason' => 'ปรับตามโปร', 'actor' => self::ACTOR,
        ])->assertOk()->assertJsonPath('data.amount', 7);

        $this->postJson('/api/v1/juntra/server/affiliate/admin/commissions/pay', ['ids' => [$pending->id], 'actor' => self::ACTOR])
            ->assertOk();

        $this->postJson("/api/v1/juntra/server/affiliate/admin/commissions/{$pending->id}/adjust", [
            'amount' => 50, 'actor' => self::ACTOR,
        ])->assertStatus(422)->assertJsonPath('reason_code', 'not_adjustable');

        $this->assertEquals(7.0, (float) $pending->fresh()->amount);
        $this->assertEquals(7.0, (float) Wallet::where('user_id', $pending->user_id)->value('balance'));
    }

    public function test_reject_only_touches_pending(): void
    {
        $pending = $this->pendingCommission(5);

        $this->postJson("/api/v1/juntra/server/affiliate/admin/commissions/{$pending->id}/reject", [
            'reason' => 'ซ้ำ', 'actor' => self::ACTOR,
        ])->assertOk()->assertJsonPath('data.status', FortuneCommission::STATUS_REJECTED);

        $this->postJson("/api/v1/juntra/server/affiliate/admin/commissions/{$pending->id}/reject", ['actor' => self::ACTOR])
            ->assertStatus(422)->assertJsonPath('reason_code', 'not_pending');
    }

    public function test_every_write_needs_an_actor(): void
    {
        $pending = $this->pendingCommission(5);

        $this->postJson('/api/v1/juntra/server/affiliate/admin/commissions/pay', ['ids' => [$pending->id]])
            ->assertStatus(422)->assertJsonValidationErrors(['actor']);
        $this->postJson('/api/v1/juntra/server/affiliate/admin/commissions/'.$pending->id.'/reject', ['reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['actor']);
    }

    /** อัตรา: หลังบ้านจันทราดูได้อย่างเดียว — ตั้งที่หน้าคอมแม่หมอของ Thaiprompt แล้วบิลถัดไปใช้ทันที */
    public function test_rates_are_read_only_from_juntra_and_set_at_thaiprompt(): void
    {
        [$inviterUser] = $this->activeMember('INVITE01', $this->rootMember);

        $this->getJson('/api/v1/juntra/server/affiliate/admin/settings')
            ->assertOk()
            ->assertJsonPath('data.fortune_juntra_l1_percent', 10)
            ->assertJsonMissingPath('data.fortune_level1_commission_amount'); // อัตราบอทไม่ใช่เรื่องของหลังบ้านจันทรา
        $this->putJson('/api/v1/juntra/server/affiliate/admin/settings', [
            'fortune_juntra_l1_percent' => 50,
            'actor' => self::ACTOR,
        ])->assertStatus(405);

        // หลังบ้านแม่หมอของ Thaiprompt ตั้งค่า
        FortuneTellingSetting::first()->update(['fortune_juntra_l1_percent' => 20, 'fortune_juntra_l2_enabled' => false]);
        FortuneTellingSetting::clearSettingsCache();

        $this->bill(9101, 701, 99, referral: 'INVITE01')->assertStatus(201)->assertJsonCount(1, 'data.commissions');

        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $this->readingId(9101),
            'user_id' => $inviterUser->id,
            'level' => 1,
            'amount' => '19.80',
        ]);
    }

    /** เว็บใครเว็บมัน: หลังบ้านจันทราเห็นและจัดการได้เฉพาะค่าแนะนำจากบิลจันทรา */
    public function test_juntra_back_office_only_sees_and_manages_juntra_commissions(): void
    {
        $this->activeMember('INVITE01', $this->rootMember);
        $this->bill(9102, 702, 39, referral: 'INVITE01')->assertStatus(201);
        $botCommission = $this->pendingCommission(5, juntra: false);

        $this->getJson('/api/v1/juntra/server/affiliate/admin/commissions')
            ->assertOk()
            ->assertJsonPath('meta.total', 2) // สายตรง + หลานของบิลจันทรา — ไม่มีของบอท
            ->assertJsonPath('data.0.reading.source', 'juntra')
            ->assertJsonPath('data.1.reading.source', 'juntra');

        $this->getJson('/api/v1/juntra/server/affiliate/admin/overview')
            ->assertOk()
            ->assertJsonPath('data.commissions.total_count', 2)
            ->assertJsonPath('data.juntra_bills.paid_count', 1)
            ->assertJsonPath('data.juntra_bills.paid_amount', 39);

        foreach ([
            ['post', '/api/v1/juntra/server/affiliate/admin/commissions/pay', ['ids' => [$botCommission->id]]],
            ['post', '/api/v1/juntra/server/affiliate/admin/commissions/approve', ['ids' => [$botCommission->id]]],
            ['post', "/api/v1/juntra/server/affiliate/admin/commissions/{$botCommission->id}/reject", []],
            ['post', "/api/v1/juntra/server/affiliate/admin/commissions/{$botCommission->id}/adjust", ['amount' => 1]],
        ] as [$method, $url, $body]) {
            $this->json($method, $url, $body + ['actor' => self::ACTOR])
                ->assertStatus(422)
                ->assertJsonPath('reason_code', 'not_juntra');
        }
        $this->assertSame(FortuneCommission::STATUS_PENDING, $botCommission->fresh()->status);
    }

    /** สร้างรายการเอง: แอดมินจันทราใส่เลขบิลจันทราได้เลย · บิลบอทสร้างจากที่นี่ไม่ได้ */
    public function test_manual_commission_takes_a_juntra_bill_number_and_refuses_bot_bills(): void
    {
        [$recipient] = $this->activeMember('RCPT0001', $this->rootMember);
        $this->bill(9103, 703, 99)->assertStatus(201);
        $buyerId = \App\Models\FortuneReading::where('bill_reference', 'JW-9103')->value('user_id');

        $id = $this->postJson('/api/v1/juntra/server/affiliate/admin/commissions/manual', [
            'user_id' => $recipient->id,
            'bill_id' => 9103,
            'level' => 1,
            'amount' => 4.5,
            'actor' => self::ACTOR,
        ])->assertStatus(201)->json('data.id');

        $this->assertDatabaseHas('fortune_commissions', [
            'id' => $id,
            'fortune_reading_id' => $this->readingId(9103),
            'from_user_id' => $buyerId,
            'user_id' => $recipient->id,
            'status' => FortuneCommission::STATUS_PENDING,
        ]);

        $botReadingId = $this->pendingCommission(1, juntra: false)->fortune_reading_id;
        foreach ([['bill_id' => 999999], ['fortune_reading_id' => $botReadingId]] as $which) {
            $this->postJson('/api/v1/juntra/server/affiliate/admin/commissions/manual', $which + [
                'user_id' => $recipient->id,
                'level' => 1,
                'amount' => 4.5,
                'actor' => self::ACTOR,
            ])->assertStatus(422)->assertJsonPath('reason_code', 'not_juntra');
        }
    }

    public function test_move_member_carries_the_whole_team(): void
    {
        [, $a] = $this->activeMember('AAAA0001', $this->rootMember);
        [, $b] = $this->activeMember('BBBB0001', $this->rootMember);
        [, $child] = $this->activeMember('CHLD0001', $a);
        $this->makeJuntraCustomer($a, 801);

        $this->postJson("/api/v1/juntra/server/affiliate/admin/members/{$a->id}/move", [
            'new_sponsor_member_id' => $b->id,
            'actor' => self::ACTOR,
        ])->assertOk()->assertJsonPath('data.new_sponsor_id', $b->id);

        $this->assertSame($b->id, $a->fresh()->unilevel_sponsor_id);
        $this->assertSame($a->id, $child->fresh()->unilevel_sponsor_id);

        $this->getJson("/api/v1/juntra/server/affiliate/admin/users/{$b->user_id}/tree")
            ->assertOk()
            ->assertJsonPath('total_descendants', 2)
            ->assertJsonPath('tree.children.0.id', $a->id)
            ->assertJsonPath('tree.children.0.is_juntra', true) // หลังบ้านจันทราโชว์ปุ่มย้ายเฉพาะคนนี้
            ->assertJsonPath('tree.children.0.children.0.is_juntra', false);
    }

    public function test_cannot_move_a_member_under_their_own_downline(): void
    {
        [, $a] = $this->activeMember('AAAA0001', $this->rootMember);
        [, $child] = $this->activeMember('CHLD0001', $a);
        $this->makeJuntraCustomer($a, 802);

        $this->postJson("/api/v1/juntra/server/affiliate/admin/members/{$a->id}/move", [
            'new_sponsor_member_id' => $child->id,
            'actor' => self::ACTOR,
        ])->assertStatus(422)->assertJsonPath('reason_code', 'transfer_refused');

        $this->assertSame($this->rootMember->id, $a->fresh()->unilevel_sponsor_id);
    }

    public function test_members_who_are_not_juntra_customers_cannot_be_moved_from_juntra(): void
    {
        [, $a] = $this->activeMember('AAAA0001', $this->rootMember);
        [, $b] = $this->activeMember('BBBB0001', $this->rootMember);

        $this->postJson("/api/v1/juntra/server/affiliate/admin/members/{$a->id}/move", [
            'new_sponsor_member_id' => $b->id,
            'actor' => self::ACTOR,
        ])->assertStatus(422)->assertJsonPath('reason_code', 'not_juntra');

        $this->assertSame($this->rootMember->id, $a->fresh()->unilevel_sponsor_id);
    }

    private function makeJuntraCustomer(MlmMember $member, int $juntraUserId): void
    {
        \App\Models\JuntraAccount::create(['juntra_user_id' => $juntraUserId, 'user_id' => $member->user_id, 'linked_via' => 'auto']);
    }

    private function pendingCommission(float $amount, bool $juntra = true): FortuneCommission
    {
        [$recipient, $recipientMember] = $this->activeMember('RCPT'.random_int(1000, 9999));
        [$buyer] = $this->activeMember('BUYR'.random_int(1000, 9999), $recipientMember);

        $reading = \App\Models\FortuneReading::create([
            'user_id' => $buyer->id,
            'facebook_user_id' => 'test_fb_'.uniqid(),
            'reading_type' => $juntra ? \App\Models\FortuneReading::READING_TYPE_JUNTRA : 'deep',
            'bill_reference' => $juntra ? 'JW-'.random_int(100000, 999999) : null,
            'questions' => ['ทดสอบ'],
            'is_paid' => true,
            'amount_paid' => 39,
            'paid_at' => now(),
            'conversation_status' => 'completed',
            'platform' => 'facebook',
        ]);

        return FortuneCommission::create([
            'user_id' => $recipient->id,
            'from_user_id' => $buyer->id,
            'fortune_reading_id' => $reading->id,
            'mlm_member_id' => MlmMember::where('user_id', $recipient->id)->value('id'),
            'level' => 1,
            'commission_type' => 'fixed',
            'commission_rate' => $amount,
            'amount' => $amount,
            'reading_price' => 39,
            'status' => FortuneCommission::STATUS_PENDING,
        ]);
    }
}
