<?php

namespace Spatie\RateLimitedMiddleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class RateLimited
{
    /** @var bool|\Closure */
    protected $enabled = true;

    /** @var string */
    protected $connectionName = '';

    /** @var string */
    protected $key;

    /** @var int */
    protected $timeSpanInSeconds = 1;

    /** @var int */
    protected $allowedNumberOfJobsInTimeSpan = 5;

    /** @var int */
    protected $releaseInSeconds = 5;

    /** @var callable */
    protected $releaseAfterCallback = null;

    public function __construct()
    {
        $calledByClass = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'];

        $this->key($calledByClass);
    }

    /**
     * @param bool|\Closure $enabled
     *
     * @return $this
     */
    public function enabled($enabled = true)
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

    public function allow(int $allowedNumberOfJobsInTimeSpan)
    {
        $this->allowedNumberOfJobsInTimeSpan = $allowedNumberOfJobsInTimeSpan;

        return $this;
    }

    public function everySecond(int $timespanInSeconds = 1)
    {
        $this->timeSpanInSeconds = $timespanInSeconds;

        return $this;
    }

    public function everySeconds(int $timespanInSeconds)
    {
        return $this->everySecond($timespanInSeconds);
    }

    public function everyMinute(int $timespanInMinutes = 1)
    {
        return $this->everySecond($timespanInMinutes * 60);
    }

    public function everyMinutes(int $timespanInMinutes)
    {
        return $this->everySecond($timespanInMinutes * 60);
    }

    public function releaseAfterOneSecond()
    {
        return $this->releaseAfterSeconds(1);
    }

    public function releaseAfterSeconds(int $releaseInSeconds)
    {
        $this->releaseInSeconds = $releaseInSeconds;

        return $this;
    }

    public function releaseAfterOneMinute()
    {
        return $this->releaseAfterMinutes(1);
    }

    public function releaseAfterMinutes(int $releaseInSeconds)
    {
        return $this->releaseAfterSeconds($releaseInSeconds * 60);
    }

    public function releaseAfter(callable $releaseAfter)
    {
        $this->releaseAfterCallback = $releaseAfter;

        return $this;
    }

    protected function releaseDuration($job) :int
    {
        if (! is_null($this->releaseAfterCallback)) {
            return call_user_func($this->releaseAfterCallback, [$job]);
        }

        return $this->releaseInSeconds;
    }

    public function handle($job, $next)
    {
        if ($this->enabled instanceof Closure) {
            $this->enabled = (bool) $this->enabled();
        }

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
                $job->release($this->releaseDuration($job));
            });
    }
}
