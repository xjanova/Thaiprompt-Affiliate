<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinControlAction extends Model
{
    use HasFactory;

    protected $table = 'coin_control_actions';

    protected $fillable = [
        'token_id',
        'admin_id',
        'action_type',
        'target_address',
        'amount',
        'reason',
        'tx_hash',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Scopes
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Helpers
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getActionDescription(): string
    {
        $descriptions = [
            'mint' => 'Mint Tokens',
            'burn' => 'Burn Tokens',
            'freeze_address' => 'Freeze Address',
            'unfreeze_address' => 'Unfreeze Address',
            'pause_contract' => 'Pause Contract',
            'unpause_contract' => 'Unpause Contract',
            'transfer_ownership' => 'Transfer Ownership',
            'update_settings' => 'Update Settings',
        ];

        return $descriptions[$this->action_type] ?? 'Unknown Action';
    }

    public function getActionIcon(): string
    {
        $icons = [
            'mint' => '➕',
            'burn' => '🔥',
            'freeze_address' => '❄️',
            'unfreeze_address' => '🔓',
            'pause_contract' => '⏸️',
            'unpause_contract' => '▶️',
            'transfer_ownership' => '🔑',
            'update_settings' => '⚙️',
        ];

        return $icons[$this->action_type] ?? '📝';
    }

    public function getExplorerUrl(): ?string
    {
        if (!$this->tx_hash) {
            return null;
        }

        $explorerBaseUrl = config('crypto.networks.tpix.explorer');
        return $explorerBaseUrl . '/tx/' . $this->tx_hash;
    }
}
