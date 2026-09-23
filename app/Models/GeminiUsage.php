<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeminiUsage extends Model
{
    protected $table = 'gemini_usage';

    protected $fillable = [
        'user_id',
        'user_type',
        'type',
        'entity_type',
        'entity_id',
        'prompt_hash',
        'tokens_used',
        'ip_address',
    ];

    /**
     * Get usage count for a user within time period
     */
    public static function getUsageCount($userId, $userType, $type, $hours = 24)
    {
        return self::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    /**
     * Check if user has exceeded limit
     */
    public static function hasExceededLimit($userId, $userType, $type, $limit, $hours = 24)
    {
        if ($limit <= 0) {
            return false;
        }
        return self::getUsageCount($userId, $userType, $type, $hours) >= $limit;
    }

    /**
     * Get global usage count (all users) within time period
     */
    public static function getGlobalUsageCount($type, $hours = 24)
    {
        return self::where('type', $type)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    /**
     * Check if global usage has exceeded limit
     */
    public static function hasExceededGlobalLimit($type, $limit, $hours = 24)
    {
        if ($limit <= 0) {
            return false;
        }
        return self::getGlobalUsageCount($type, $hours) >= $limit;
    }
}
