<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Mockery;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;
use Spatie\RateLimitedMiddleware\RateLimited;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;

class RateLimitedTest extends TestCase
{
    /** @var \Closure */
    private $next;

    /** @var \Mockery\Mock */
    private $job;

    /** @var \Mockery\Mock */
    private $redis;

    /** @var int */
    private $callsAllowed;

    /** @var \Spatie\RateLimitedMiddleware\RateLimited */
    private $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->callsAllowed = 2;

        $this->mockRedis();
        $this->mockJob();

        $this->middleware = new RateLimited();
    }

    /** @test */
    public function it_limits_job_execution()
    {
        $this->job->shouldReceive('fire')->times(2);
        $this->job->shouldReceive('release')->times(3);

        foreach (range(1, 5) as $i) {
            $this->middleware->handle($this->job, $this->next);
        }
    }

    /** @test */
    public function it_does_nothing_when_disabled()
    {
        $this->job->shouldReceive('fire')->times(1);

        $this->middleware->enabled(false)->handle($this->job, $this->next);
    }

    /** @test */
    public function release_can_be_set_with_random_seconds()
    {
        $this->job->shouldReceive('fire')->times(2);
        $this->job->shouldReceive('release')->times(1)->with(1);

        foreach (range(1, 3) as $i) {
            $this->middleware->releaseAfterRandomSeconds(1, 1)
                ->handle($this->job, $this->next);
        }
    }

    private function mockRedis(): void
    {
        $this->redis = Mockery::mock(Connection::class);

        $this->redis->shouldReceive('throttle')->andReturn(new DurationLimiterBuilder($this->redis, 'key'));
        $this->redis->shouldReceive('eval')->andReturnUsing(function () {
            return [
                $this->callsAllowed > 0,
                strtotime('10 seconds'),
                $this->callsAllowed--,
            ];
        });

        Redis::shouldReceive('connection')->andReturn($this->redis);
    }

    private function mockJob(): void
    {
        $this->job = Mockery::mock();

        $this->next = function ($job) {
            $job->fire();
        };
    }
}
