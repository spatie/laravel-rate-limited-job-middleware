<?php

namespace Spatie\RateLimitedMiddleware;

use ArtisanSdk\RateLimiter\Buckets\Leaky;
use ArtisanSdk\RateLimiter\Limiter;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RateLimited
{
    protected bool|Closure $enabled = true;

    protected string $connectionName = '';

    protected string $key;

    protected int $timeSpanInSeconds = 1;

    protected int $allowedNumberOfJobsInTimeSpan = 5;

    protected int $releaseInSeconds = 5;

    protected ?array $releaseRandomSeconds = null;

    protected bool $useRedis = true;

    public function __construct(bool $useRedis = true)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $calledByClass = $backtrace['class'] ?? $backtrace['file'];

        $this->key($calledByClass);
        $this->useRedis = $useRedis;
    }

    public function enabled(bool|Closure $enabled = true): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function connectionName(string $connectionName): self
    {
        $this->connectionName = $connectionName;

        return $this;
    }

    public function key(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function timespanInSeconds(int $timespanInSeconds): self
    {
        $this->timeSpanInSeconds = $timespanInSeconds;

        return $this;
    }

    public function allow(int $allowedNumberOfJobsInTimeSpan): self
    {
        $this->allowedNumberOfJobsInTimeSpan = $allowedNumberOfJobsInTimeSpan;

        return $this;
    }

    public function everySecond(int $timespanInSeconds = 1): self
    {
        $this->timeSpanInSeconds = $timespanInSeconds;

        return $this;
    }

    public function everySeconds(int $timespanInSeconds): self
    {
        return $this->everySecond($timespanInSeconds);
    }

    public function everyMinute(int $timespanInMinutes = 1): self
    {
        return $this->everySecond($timespanInMinutes * 60);
    }

    public function everyMinutes(int $timespanInMinutes): self
    {
        return $this->everySecond($timespanInMinutes * 60);
    }

    public function releaseAfterOneSecond(): self
    {
        return $this->releaseAfterSeconds(1);
    }

    public function releaseAfterSeconds(int $releaseInSeconds): self
    {
        $this->releaseInSeconds = $releaseInSeconds;

        return $this;
    }

    public function releaseAfterOneMinute(): self
    {
        return $this->releaseAfterMinutes(1);
    }

    public function releaseAfterMinutes(int $releaseInMinutes): self
    {
        return $this->releaseAfterSeconds($releaseInMinutes * 60);
    }

    public function releaseAfterRandomSeconds(int $min = 1, int $max = 10): self
    {
        $this->releaseRandomSeconds = [$min, $max];

        return $this;
    }

    public function releaseAfterBackoff(int $attemptedCount, int $backoffRate = 2): self
    {
        $releaseAfterSeconds = 0;
        $interval = $this->releaseInSeconds;
        for ($attempt = 0; $attempt <= $attemptedCount; $attempt++) {
            $releaseAfterSeconds += $interval * pow($backoffRate, $attempt);
        }

        return $this->releaseAfterSeconds($releaseAfterSeconds);
    }

    protected function releaseDuration(): int
    {
        if (! is_null($this->releaseRandomSeconds)) {
            return random_int(...$this->releaseRandomSeconds);
        }

        return $this->releaseInSeconds;
    }

    public function handle($job, Closure $next): void
    {
        if ($this->enabled instanceof Closure) {
            $this->enabled = (bool) $this->enabled();
        }

        if (! $this->enabled) {
            $next($job);

            return;
        }

        if ($this->useRedis) {
            $this->handleRedis($job, $next);

            return;
        }

        $this->handleCache($job, $next);
    }

    private function handleRedis($job, $next): void
    {
        Redis::connection($this->connectionName)
            ->throttle($this->key)
            ->block(0)
            ->allow($this->allowedNumberOfJobsInTimeSpan)
            ->every($this->timeSpanInSeconds)
            ->then(function () use ($job, $next) {
                $next($job);
            }, function () use ($job) {
                $job->release($this->releaseDuration());
            });
    }

    private function handleCache($job, $next): void
    {
        $bucket = new Leaky(
            key: $this->key,
            max: $this->allowedNumberOfJobsInTimeSpan,
            rate: $this->allowedNumberOfJobsInTimeSpan / $this->timeSpanInSeconds,
        );
        $limiter = new Limiter(Cache::store(), $bucket);

        if ($limiter->exceeded()) {
            $job->release($this->releaseDuration());
            return;
        }

        $limiter->hit();
        $next($job);
    }
}
