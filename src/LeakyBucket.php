<?php

namespace Spatie\RateLimitedMiddleware;

use Illuminate\Support\Facades\Cache;

/**
 * @source https://github.com/artisansdk/ratelimiter
 * We've combined the Bucket and Limiter into one class
 * here as we only need this specific implementation
 */
class LeakyBucket
{
    protected int $drips = 0;

    protected float $timer = 0;

    public function __construct(
        protected string $key = 'default',
        protected int $max = 60,
        protected float|int $rate = 1,
    ) {
        $bucket = Cache::get($this->key);
        $this->drips = $bucket['drips'] ?? 0;
        $this->timer = $bucket['timer'] ?? microtime(true);
    }

    public function isOverflowing(): bool
    {
        $this->leak();

        if ($this->drips >= $this->max) {
            return true;
        }

        return false;
    }

    public function fill(): int
    {
        $this->drips++;

        $this->store();

        return $this->drips;
    }

    protected function store(): void
    {
        Cache::put(
            $this->key,
            [
                'timer' => $this->timer,
                'drips' => $this->drips,
            ],
            (int) max(1, ceil($this->duration())) // $ttl to $seconds conversion requires minimally 1s
        );
    }

    protected function duration(): float
    {
        return (float) (
            max(
                0,
                microtime(true)
                + ($this->drips / $this->rate)
                - $this->timer
            )
        );
    }

    protected function leak(): self
    {
        $drips = $this->drips;

        $elapsed = microtime(true) - $this->timer;

        $drops = (int) floor($elapsed * $this->rate);

        $this->drips = $this->bounded($drips - $drops);

        if ($drops > 0) {
            $this->timer = microtime(true);
        }

        $this->store();

        return $this;
    }

    protected function bounded(int $drips): int
    {
        return (int) max(0, min($this->max, $drips));
    }
}
