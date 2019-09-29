<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Closure;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\RateLimitedMiddleware\RateLimited;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;

class RateLimitedTest extends TestCase
{
    /** @var Connection|MockObject */
    private $redis;
    /** @var Closure */
    private $next;
    /** @var Job|MockObject */
    private $job;
    /** @var DurationLimiterBuilder|MockObject */
    private $limiter;

    /** @var RateLimited */
    private $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->limiter = $this->createMock(DurationLimiterBuilder::class);
        $this->redis = $this->createMock(Connection::class);
        $this->job = $this->createMock(Job::class);
        $this->next = function (Job $job) {
            $job->fire(); // just anything on the $job so we can ensure it gets called
        };

        $this->middleware = new RateLimited();
    }

    /** @test */
    public function does_the_job_when_enabled()
    {
        $this->middleware->enabled(true);

        // a bit of exaggeration here just to map setters to underlying laravel's calls:
        $this->middleware->connectionName('my-connection');
        Redis::shouldReceive('connection')->with('my-connection')->andReturn($this->redis);

        $this->middleware->key('my-key');
        $this->redis->expects($this->once())->method('throttle')->with('my-key')->willReturn($this->limiter);

        $this->middleware->timespanInSeconds(60);
        $this->limiter->expects($this->once())->method('every')->with(60)->willReturnSelf();

        $this->middleware->allowedNumberOfJobsInTimeSpan(3);
        $this->limiter->expects($this->once())->method('allow')->with(3)->willReturnSelf();

        // this one will apply on the job in case of throttling
        $this->middleware->releaseInSeconds(90);
        $jobThrottled = function ($callback) {
            $this->job->expects($this->once())->method('release')->with(90);
            $callback();

            return true;
        };

        $jobAllowed = function ($callback) {
            $this->job->expects($this->once())->method('fire');
            $callback();

            return true;
        };

        $this->limiter->expects($this->once())->method('then')->with(
            $this->callback($jobAllowed),
            $this->callback($jobThrottled),
        );

        // This one is simply hard-coded to 0
        $this->limiter->expects($this->once())->method('block')->with(0)->willReturnSelf();

        $this->middleware->handle($this->job, $this->next);
    }

    /** @test */
    public function stops_when_disabled()
    {
        $this->redis->expects($this->never())->method($this->anything());
        $this->job->expects($this->once())->method('fire');
        $this->middleware->enabled(false);

        $this->middleware->handle($this->job, $this->next);
    }
}
