<?php

namespace utils;

use Redis;
use RedisException;

final class SendCodeLimiter
{
    private const KEY_PREFIX = 'RBT:SEND_CODE:';

    private const RESERVE = <<<'LUA'
        redis.replicate_commands()
        local sends, cooldown, pin = KEYS[1], KEYS[2], KEYS[3]
        local time = redis.call('TIME')
        local now = time[1] * 1000 + math.floor(time[2] / 1000)
        local day = 86400000
        redis.call('ZREMRANGEBYSCORE', sends, '-inf', now - day)
        
        local function windowDelay(window, limit)
            local cutoff = '(' .. (now - window)
            local count = redis.call('ZCOUNT', sends, cutoff, '+inf')
            if count < limit then
                return 0
            end
            -- A lowered limit can require more than one reservation to expire.
            local entry = redis.call('ZRANGEBYSCORE', sends, cutoff, '+inf', 'WITHSCORES', 'LIMIT', count - limit, 1)
            return tonumber(entry[2]) + window - now
        end
        
        local wait = math.max(
            redis.call('PTTL', cooldown),
            redis.call('PTTL', pin),
            windowDelay(60000, tonumber(ARGV[2])),
            windowDelay(day, tonumber(ARGV[3]))
        )
        if wait > 0 then
            return math.ceil(wait / 1000)
        end
        
        redis.call('SET', cooldown, '1', 'EX', ARGV[1])
        redis.call('ZADD', sends, now, ARGV[4])
        redis.call('PEXPIRE', sends, day)
        return 0
    LUA;

    private readonly int $phoneCooldown;
    private readonly int $perMinute;
    private readonly int $per24h;

    public function __construct(private readonly Redis $redis, array $config)
    {
        $settings = $config['backends']['isdn']['send_code_limits'] ?? [];
        $this->phoneCooldown = self::positiveIntOrDefault($settings['phone_cooldown'] ?? null, 60);
        $this->perMinute = self::positiveIntOrDefault($settings['per_minute'] ?? null, 10);
        $this->per24h = self::positiveIntOrDefault($settings['per_24h'] ?? null, 200);
    }

    private static function positiveIntOrDefault(mixed $value, int $default): int
    {
        return is_int($value) && $value > 0 ? $value : $default;
    }

    public function reserve(string $phone): ?int
    {
        try {
            $result = $this->redis->eval(self::RESERVE, [
                self::KEY_PREFIX . 'sends',
                self::KEY_PREFIX . 'cooldown:' . $phone,
                'USERPIN:' . $phone,
                $this->phoneCooldown,
                $this->perMinute,
                $this->per24h,
                bin2hex(random_bytes(16)),
            ], 3);
        } catch (RedisException) {
            return null;
        }

        return is_int($result) && $result >= 0 ? $result : null;
    }
}
