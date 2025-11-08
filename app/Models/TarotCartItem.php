<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarotCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'category_id',
        'spread_type_id',
        'question',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the user that owns the cart item
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this cart item
     */
    public function category()
    {
        return $this->belongsTo(TarotReadingCategory::class, 'category_id');
    }

    /**
     * Get the spread type for this cart item
     */
    public function spreadType()
    {
        return $this->belongsTo(TarotSpreadType::class, 'spread_type_id');
    }

    /**
     * Get cart items for current session/user
     */
    public static function getCartItems($userId = null, $sessionId = null, $ipAddress = null)
    {
        $query = static::with(['category', 'spreadType']);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where(function ($q) use ($sessionId, $ipAddress) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
                if ($ipAddress) {
                    $q->orWhere('ip_address', $ipAddress);
                }
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get total price of cart
     */
    public static function getCartTotal($userId = null, $sessionId = null, $ipAddress = null)
    {
        $items = static::getCartItems($userId, $sessionId, $ipAddress);
        return $items->sum('price');
    }

    /**
     * Get cart count
     */
    public static function getCartCount($userId = null, $sessionId = null, $ipAddress = null)
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where(function ($q) use ($sessionId, $ipAddress) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
                if ($ipAddress) {
                    $q->orWhere('ip_address', $ipAddress);
                }
            });
        }

        return $query->count();
    }

    /**
     * Clear cart
     */
    public static function clearCart($userId = null, $sessionId = null, $ipAddress = null)
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where(function ($q) use ($sessionId, $ipAddress) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
                if ($ipAddress) {
                    $q->orWhere('ip_address', $ipAddress);
                }
            });
        }

        return $query->delete();
    }
}
