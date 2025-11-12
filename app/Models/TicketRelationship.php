<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'related_ticket_id',
        'relationship_type',
        'note',
        'created_by',
    ];

    /**
     * Get the main ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the related ticket
     */
    public function relatedTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'related_ticket_id');
    }

    /**
     * Get the user who created this relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: By relationship type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope: Duplicates only
     */
    public function scopeDuplicates($query)
    {
        return $query->where('relationship_type', 'duplicate');
    }

    /**
     * Get relationship type label in Thai
     */
    public function getTypeLabelAttribute()
    {
        return match($this->relationship_type) {
            'duplicate' => 'ซ้ำกับ',
            'related' => 'เกี่ยวข้องกับ',
            'blocks' => 'ขัดขวาง',
            'blocked_by' => 'ถูกขัดขวางโดย',
            'parent' => 'Ticket หลัก',
            'child' => 'Ticket ย่อย',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Get relationship icon
     */
    public function getTypeIconAttribute()
    {
        return match($this->relationship_type) {
            'duplicate' => 'fa-copy',
            'related' => 'fa-link',
            'blocks' => 'fa-ban',
            'blocked_by' => 'fa-lock',
            'parent' => 'fa-level-up-alt',
            'child' => 'fa-level-down-alt',
            default => 'fa-question',
        };
    }

    /**
     * Create bidirectional relationship
     */
    public static function createBidirectional($ticketId, $relatedTicketId, $type, $note = null, $createdBy = null)
    {
        $createdBy = $createdBy ?? auth()->id();

        // Create forward relationship
        $forward = self::create([
            'ticket_id' => $ticketId,
            'related_ticket_id' => $relatedTicketId,
            'relationship_type' => $type,
            'note' => $note,
            'created_by' => $createdBy,
        ]);

        // Create reverse relationship (if applicable)
        $reverseType = self::getReverseType($type);
        if ($reverseType) {
            self::create([
                'ticket_id' => $relatedTicketId,
                'related_ticket_id' => $ticketId,
                'relationship_type' => $reverseType,
                'note' => $note,
                'created_by' => $createdBy,
            ]);
        }

        return $forward;
    }

    /**
     * Get reverse relationship type
     */
    private static function getReverseType($type)
    {
        return match($type) {
            'blocks' => 'blocked_by',
            'blocked_by' => 'blocks',
            'parent' => 'child',
            'child' => 'parent',
            'duplicate' => 'duplicate',
            'related' => 'related',
            default => null,
        };
    }

    /**
     * Remove bidirectional relationship
     */
    public function removeBidirectional()
    {
        // Find and delete reverse relationship
        $reverseType = self::getReverseType($this->relationship_type);
        if ($reverseType) {
            self::where('ticket_id', $this->related_ticket_id)
                ->where('related_ticket_id', $this->ticket_id)
                ->where('relationship_type', $reverseType)
                ->delete();
        }

        // Delete this relationship
        $this->delete();
    }
}
