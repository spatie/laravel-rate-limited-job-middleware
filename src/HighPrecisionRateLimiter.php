<?php

namespace Spatie\RateLimitedMiddleware;

use DateTimeInterface;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Carbon;

class HighPrecisionRateLimiter extends RateLimiter
{
    public function hit($key, $decaySeconds = 60)
    {
        $decayMilliseconds = $decaySeconds * 1000;

        $this->cache->add(
            $key.':timer', $this->availableAt($decayMilliseconds), $decayMilliseconds
        );

        $added = $this->cache->add($key, 0, $decayMilliseconds);

        $hits = (int) $this->cache->increment($key);

        if (! $added && $hits == 1) {
            $this->cache->put($key, 1, $decayMilliseconds);
        }

        return $hits;
    }

    protected function currentTime()
    {
        return Carbon::now()->getTimestampMs();
    }

    protected function availableAt($delay = 0)
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? $delay->getTimestamp() * 1000
            : Carbon::now()->addRealMilliSeconds($delay)->getTimestampMs();
    }
}
