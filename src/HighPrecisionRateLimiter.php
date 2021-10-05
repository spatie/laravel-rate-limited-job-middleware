<?php

namespace Spatie\RateLimitedMiddleware;

use DateTimeInterface;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Carbon;

class HighPrecisionRateLimiter extends RateLimiter
{
    protected function availableAt($delay = 0): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? $delay->getTimestamp()
            : Carbon::now()->addRealSeconds($delay)->getTimestampMs();
    }

    protected function currentTime(): int
    {
        return Carbon::now()->getTimestampMs();
    }
}
