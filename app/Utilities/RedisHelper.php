<?php

namespace App\Utilities;

use App\Utilities\Contracts\RedisHelperInterface;
use Illuminate\Support\Facades\Cache;

class RedisHelper implements RedisHelperInterface
{
    public const RECENT_MESSAGES_KEY = 'recent-messages';

    public function storeRecentMessage(mixed $id, string $messageSubject, string $toEmailAddress): void
    {
        Cache::remember(self::RECENT_MESSAGES_KEY."-$id", 60, function () use ($id, $messageSubject, $toEmailAddress) {
            $message = [
            'id' => $id,
            'subject' => $messageSubject,
            'to' => $toEmailAddress,
            'timestamp' => time(),
            ];
            return $message;
        });
    }

    public function getRecentMessageById(mixed $id)
    {
        return Cache::get(self::RECENT_MESSAGES_KEY."-$id");
    }
}
