<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Closure;
use Mockery;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;
use Spatie\RateLimitedMiddleware\RateLimited;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;

class RateLimitedTest extends TestCase
{
    /** @var Closure */
    private $next;
    /** @var Mockery\Mock */
    private $job;
    /** @var Mockery\Mock */
    private $redis;

    /** @var int Fake redis lock */
    private $callsAllowed;

    /** @var RateLimited */
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

        for ($i = 0; $i < 5; $i++) {
            $this->middleware->handle($this->job, $this->next);
        }
    }

    /** @test */
    public function it_does_nothing_when_disabled()
    {
        $this->job->shouldReceive('fire')->times(1);
        $this->middleware->enabled(false)->handle($this->job, $this->next);
    }

    private function mockRedis(): void
    {
        // Let's mock redis service and its lock mechanism so we can test our stuff without hassle
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
