<?php

namespace Spatie\RateLimitedMiddleware;

use Illuminate\Support\Facades\Redis;

class RateLimited
{
    /** @var bool */
    protected $enabled = true;

    protected $connectionName = '';

    protected $key = 'rate-limited-job-middleware';

    protected $timeSpanInSeconds = 1;

    protected $allowedNumberOfJobsInTimeSpan = 5;

    protected $releaseInSeconds = 5;

    public function enabled(bool $enabled = true)
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function connectionName(string $connectionName)
    {
        $this->connectionName = $connectionName;

        return $this;
    }

    public function key(string $key)
    {
        $this->key = $key;

        return $this;
    }

    public function timespanInSeconds(int $timespanInSeconds)
    {
        $this->timeSpanInSeconds = $timespanInSeconds;

        return $this;
    }

    public function allowedNumberOfJobsInTimeSpan(int $allowedNumberOfJobsInTimeSpan)
    {
        $this->allowedNumberOfJobsInTimeSpan = $allowedNumberOfJobsInTimeSpan;

        return $this;
    }

    public function releaseInSeconds(int $releaseInSeconds)
    {
        $this->releaseInSeconds = $releaseInSeconds;

        return $this;
    }

    public function handle($job, $next)
    {
        if (! $this->enabled) {
            return $next($job);
        }

        Redis::connection($this->connectionName)
            ->throttle($this->key)
            ->block(0)
            ->allow($this->allowedNumberOfJobsInTimeSpan)
            ->every($this->timeSpanInSeconds)
            ->then(function () use ($job, $next) {
                $next($job);
            }, function () use ($job) {
                $job->release($this->releaseInSeconds);
            });
    }
}
