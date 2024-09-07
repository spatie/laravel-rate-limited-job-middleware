<?php

namespace Spatie\RateLimitedMiddleware;

use ArtisanSdk\RateLimiter\Buckets\Leaky;
use ArtisanSdk\RateLimiter\Limiter;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Spatie\RateLimitedMiddleware\Events\LimitExceeded;

class RateLimited
{
    protected bool|Closure $enabled = true;

    protected string $connectionName = '';

    protected string $key;

    protected bool|Closure $dontRelease = false;

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

    public function enabled(bool|Closure $enabled = true): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function connectionName(string $connectionName): static
    {
        $this->connectionName = $connectionName;

        return $this;
    }

    public function key(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function dontRelease(bool|Closure $dontRelease = true): static
    {
        $this->dontRelease = $dontRelease;

        return $this;
    }

    public function timespanInSeconds(int $timespanInSeconds): static
    {
        $this->timeSpanInSeconds = $timespanInSeconds;

        return $this;
    }

    public function allow(int $allowedNumberOfJobsInTimeSpan): static
    {
        $this->allowedNumberOfJobsInTimeSpan = $allowedNumberOfJobsInTimeSpan;

        return $this;
    }

    public function everySecond(int $timespanInSeconds = 1): static
    {
        $this->timeSpanInSeconds = $timespanInSeconds;

        return $this;
    }

    public function everySeconds(int $timespanInSeconds): static
    {
        return $this->everySecond($timespanInSeconds);
    }

    public function everyMinute(int $timespanInMinutes = 1): static
    {
        return $this->everySecond($timespanInMinutes * 60);
    }

    public function everyMinutes(int $timespanInMinutes): static
    {
        return $this->everySecond($timespanInMinutes * 60);
    }

    public function releaseAfterOneSecond(): static
    {
        return $this->releaseAfterSeconds(1);
    }

    public function releaseAfterSeconds(int $releaseInSeconds): static
    {
        $this->releaseInSeconds = $releaseInSeconds;

        return $this;
    }

    public function releaseAfterOneMinute(): static
    {
        return $this->releaseAfterMinutes(1);
    }

    public function releaseAfterMinutes(int $releaseInMinutes): static
    {
        return $this->releaseAfterSeconds($releaseInMinutes * 60);
    }

    public function releaseAfterRandomSeconds(int $min = 1, int $max = 10): static
    {
        $this->releaseRandomSeconds = [$min, $max];

        return $this;
    }

    public function releaseAfterBackoff(int $attemptedCount, int $backoffRate = 2): static
    {
        $releaseAfterSeconds = 0;
        $interval = $this->releaseInSeconds;
        for ($attempt = 0; $attempt <= $attemptedCount; $attempt++) {
            $releaseAfterSeconds += $interval * pow($backoffRate, $attempt);
        }

        return $this->releaseAfterSeconds((int) $releaseAfterSeconds);
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
                $this->releaseJob($job);
            });
    }

    private function handleCache($job, $next): void
    {
        $limiter = new Limiter(Cache::store(), new Leaky(
            key: $this->key,
            max: $this->allowedNumberOfJobsInTimeSpan,
            rate: $this->allowedNumberOfJobsInTimeSpan / $this->timeSpanInSeconds,
        ));

        if ($limiter->exceeded()) {
            $this->releaseJob($job);

            return;
        }

        $limiter->hit();

        $next($job);
    }

    protected function releaseJob($job): void
    {
        event(new LimitExceeded($job));

        $dontRelease = $this->dontRelease;

        $dontRelease = is_callable($dontRelease)
            ? $dontRelease($job)
            : $dontRelease;

        if ($dontRelease) {
            return;
        }

        $job->release($this->releaseDuration());
    }
}
