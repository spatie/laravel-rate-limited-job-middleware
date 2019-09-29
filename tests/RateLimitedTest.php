<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Closure;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Orchestra\Testbench\TestCase;
use Spatie\RateLimitedMiddleware\RateLimited;

class RateLimitedTest extends TestCase
{
    /** @var Closure */
    private $next;
    /** @var Mockery\Mock */
    private $job;
    /** @var Mockery\Mock */
    private $redis;

    /** @var RateLimited */
    private $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = Mockery::mock();
        $this->job = Mockery::mock();
        $this->next = function ($job) {
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
        $this->redis->shouldReceive('throttle')->once()->with('my-key')->andReturn($this->redis);

        $this->middleware->timespanInSeconds(60);
        $this->redis->shouldReceive('every')->once()->with(60)->andReturnSelf();

        $this->middleware->allowedNumberOfJobsInTimeSpan(3);
        $this->redis->shouldReceive('allow')->once()->with(3)->andReturnSelf();

        // this one will apply on the job in case of throttling
        $this->middleware->releaseInSeconds(90);
        $jobThrottled = function ($callback) {
            $this->job->shouldReceive('release')->once()->with(90);
            $callback();
            return true;
        };

        $jobAllowed = function ($callback) {
            $this->job->shouldReceive('fire')->once();
            $callback();
            return true;
        };

        $this->redis->shouldReceive('then')->once()->with(
            Mockery::on($jobAllowed),
            Mockery::on($jobThrottled),
        );

        // This one is simply hard-coded to 0
        $this->redis->shouldReceive('block')->once()->with(0)->andReturnSelf();

        $this->middleware->handle($this->job, $this->next);
    }

    /** @test */
    public function stops_when_disabled()
    {
        Redis::shouldReceive('connection')->never();
        $this->job->shouldReceive('fire')->once();
        $this->middleware->enabled(false);

        $this->middleware->handle($this->job, $this->next);
    }
}
