<?php

namespace Tests\Feature\Rider\Fixtures;

use App\Contracts\RiderDeliverable;
use App\Models\RiderJob;
use Illuminate\Database\Eloquent\Model;

/**
 * ออเดอร์ปลอมสำหรับเทสต์ระบบไรเดอร์ (implement RiderDeliverable)
 *
 * ทำไมผูกกับตาราง users: เทสต์ใช้ RefreshDatabase (ครอบด้วย transaction) —
 * ถ้าสร้างตารางใหม่ (DDL) ใน MySQL จะ implicit commit แล้ว rollback ไม่ได้
 * จึงยืมแถวใน users มาเป็น "id ของออเดอร์" แล้วเก็บข้อมูลออเดอร์ไว้ในตัวแปร static แทน
 * (morphTo ของ RiderJob จะหาแถวนี้เจอด้วย id เดียวกัน)
 */
class FakeDeliverableSource extends Model implements RiderDeliverable
{
    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * ข้อมูลออเดอร์ตาม id
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $config = [];

    /**
     * บันทึกทุกครั้งที่ hook ถูกเรียก
     *
     * @var array<int, array{id: int, from: string, to: string, rider_id: ?int}>
     */
    public static array $hookCalls = [];

    public static function reset(): void
    {
        self::$config = [];
        self::$hookCalls = [];
    }

    /**
     * @param  array<string, mixed>  $config  pickup, dropoff, cod, party, customer_id, items
     */
    public static function configure(int $id, array $config): void
    {
        self::$config[$id] = $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function cfg(): array
    {
        return self::$config[(int) $this->getKey()] ?? [];
    }

    public function riderPickupPoint(): array
    {
        return $this->cfg()['pickup'];
    }

    public function riderDropoffPoint(): array
    {
        return $this->cfg()['dropoff'];
    }

    public function riderCodAmount(): float
    {
        return (float) ($this->cfg()['cod'] ?? 0);
    }

    public function riderItemsSummary(): string
    {
        return (string) ($this->cfg()['items'] ?? 'ผักบุ้ง x2, มะนาว x1');
    }

    public function riderPartyUserIds(): array
    {
        return array_map('intval', $this->cfg()['party'] ?? []);
    }

    public function riderCustomerUserId(): ?int
    {
        return $this->cfg()['customer_id'] ?? null;
    }

    public function onRiderJobStatusChanged(RiderJob $job, string $fromStatus): void
    {
        self::$hookCalls[] = [
            'id' => (int) $this->getKey(),
            'from' => $fromStatus,
            'to' => (string) $job->status,
            'rider_id' => $job->rider_id ? (int) $job->rider_id : null,
        ];
    }
}
