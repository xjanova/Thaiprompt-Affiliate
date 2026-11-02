<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TPIXTokenBalance extends Model
{
    protected $table = 'tpix_token_balances';

    protected $fillable = [
        'token_id',
        'user_id',
        'wallet_address',
        'balance',
        'locked_balance',
        'staked_balance',
        'available_balance',
        'total_received',
        'total_sent',
        'transfers_count',
        'first_received_at',
        'last_activity_at',
        'is_frozen',
        'freeze_reason',
    ];

    protected $casts = [
        'is_frozen' => 'boolean',
        'first_received_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('balance', '>', 0);
    }

    public function scopeNotFrozen($query)
    {
        return $query->where('is_frozen', false);
    }

    /**
     * หาแถวยอดเหรียญของสมาชิก (ล็อกแถวไว้) ถ้ายังไม่มีให้สร้างใหม่พร้อม wallet_address
     *
     * คอลัมน์ wallet_address เป็น NOT NULL ไม่มีค่าเริ่มต้น — เดิม firstOrCreate โดยไม่ใส่ค่านี้
     * ทำให้แลก/ถอนไปยังเหรียญที่สมาชิกยังไม่เคยถือล้มเหลวทุกครั้ง
     * ที่อยู่ที่ใช้: ที่อยู่เดิมของสมาชิกจากแถวเหรียญอื่น → กระเป๋า TPIX → ที่อยู่ที่สร้างจาก user id (ไม่ซ้ำกันต่อสมาชิก)
     */
    public static function lockOrCreateFor(int $userId, int $tokenId): self
    {
        $balance = static::where('user_id', $userId)
            ->where('token_id', $tokenId)
            ->lockForUpdate()
            ->first();

        if ($balance) {
            return $balance;
        }

        static::create([
            'user_id' => $userId,
            'token_id' => $tokenId,
            'wallet_address' => static::walletAddressFor($userId),
            'balance' => '0',
            'available_balance' => '0',
        ]);

        return static::where('user_id', $userId)
            ->where('token_id', $tokenId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * ที่อยู่กระเป๋าของสมาชิกสำหรับแถวยอดเหรียญใหม่
     */
    public static function walletAddressFor(int $userId): string
    {
        $address = static::where('user_id', $userId)
            ->whereNotNull('wallet_address')
            ->where('wallet_address', '!=', '')
            ->value('wallet_address');

        if (! $address) {
            $address = rescue(fn () => \App\Models\CryptoWallet::where('user_id', $userId)
                ->whereHas('currency', fn ($q) => $q->where('code', 'TPIX'))
                ->whereNotNull('address')
                ->value('address'), null, false);
        }

        if (! $address || strlen($address) > 42) {
            $address = '0x'.str_pad(dechex($userId), 40, '0', STR_PAD_LEFT);
        }

        return $address;
    }
}
