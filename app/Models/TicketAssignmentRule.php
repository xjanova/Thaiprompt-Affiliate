<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAssignmentRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'priority',
        'priority_filter',
        'keywords',
        'strategy',
        'assignable_users',
        'specific_user_id',
        'assign_to_user_id',
        'last_assigned_index',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'keywords' => 'array',
        'assignable_users' => 'array',
        'is_active' => 'boolean',
        'last_assigned_index' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category this rule applies to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Get the specific user for specific_user strategy
     */
    public function specificUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specific_user_id');
    }

    /**
     * Get the user to assign to
     */
    public function assignTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to_user_id');
    }

    /**
     * Scope: Active rules only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'desc'); // Higher sort_order = higher priority
    }

    /**
     * Check if this rule matches a ticket
     */
    public function matches($ticket)
    {
        // Check category
        if ($this->category_id && $ticket->category_id !== $this->category_id) {
            return false;
        }

        // Check priority
        if ($this->priority && $ticket->priority !== $this->priority) {
            return false;
        }

        // Check keywords
        if ($this->keywords && count($this->keywords) > 0) {
            $hasKeyword = false;
            $searchText = strtolower($ticket->subject . ' ' . $ticket->description);

            foreach ($this->keywords as $keyword) {
                if (str_contains($searchText, strtolower($keyword))) {
                    $hasKeyword = true;
                    break;
                }
            }

            if (!$hasKeyword) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get next user to assign based on strategy
     */
    public function getNextAssignee()
    {
        switch ($this->strategy) {
            case 'specific_user':
                return $this->specific_user_id;

            case 'round_robin':
                return $this->getRoundRobinAssignee();

            case 'least_active':
                return $this->getLeastActiveAssignee();

            case 'random':
                return $this->getRandomAssignee();

            default:
                return null;
        }
    }

    /**
     * Round-robin assignment
     */
    private function getRoundRobinAssignee()
    {
        if (!$this->assignable_users || count($this->assignable_users) === 0) {
            return null;
        }

        $users = $this->assignable_users;
        $index = $this->last_assigned_index % count($users);
        $userId = $users[$index];

        // Update index for next assignment
        $this->increment('last_assigned_index');

        return $userId;
    }

    /**
     * Assign to user with least active tickets
     */
    private function getLeastActiveAssignee()
    {
        if (!$this->assignable_users || count($this->assignable_users) === 0) {
            return null;
        }

        $userTicketCounts = Ticket::whereIn('assigned_to', $this->assignable_users)
            ->whereIn('status', ['open', 'in_progress'])
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to, count(*) as ticket_count')
            ->pluck('ticket_count', 'assigned_to')
            ->toArray();

        // Find user with minimum tickets (or 0 if not in array)
        $minUserId = null;
        $minCount = PHP_INT_MAX;

        foreach ($this->assignable_users as $userId) {
            $count = $userTicketCounts[$userId] ?? 0;
            if ($count < $minCount) {
                $minCount = $count;
                $minUserId = $userId;
            }
        }

        return $minUserId;
    }

    /**
     * Random assignment
     */
    private function getRandomAssignee()
    {
        if (!$this->assignable_users || count($this->assignable_users) === 0) {
            return null;
        }

        return $this->assignable_users[array_rand($this->assignable_users)];
    }

    /**
     * Find matching rule for a ticket and assign
     */
    public static function autoAssignTicket($ticket)
    {
        $rules = self::active()->ordered()->get();

        foreach ($rules as $rule) {
            if ($rule->matches($ticket)) {
                $assigneeId = $rule->getNextAssignee();
                if ($assigneeId) {
                    $ticket->update(['assigned_to' => $assigneeId]);
                    return $assigneeId;
                }
            }
        }

        return null;
    }
}
