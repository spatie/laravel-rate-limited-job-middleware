<?php

namespace Spatie\RateLimitedMiddleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Carbon;

class HighPrecisionRateLimiter extends RateLimiter
{
    protected function currentTime()
    {
        return Carbon::now()->getTimestampMs();
    }
}
