<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Spatie\RateLimitedMiddleware\RateLimited;

const CALLS_ALLOWED = 2;

uses(CreatesApplication::class);

dataset('middlewares', fn () => [
    'Redis' => (new RateLimited())
        ->allow(CALLS_ALLOWED)
        ->everySeconds(5),
    'Cache' => (new RateLimited(useRedis: false))
        ->allow(CALLS_ALLOWED)
        ->everySeconds(5),
]);

beforeEach(function () {
    $this->callsAllowed = CALLS_ALLOWED;
    $this->redis = Mockery::mock(Connection::class);

    $this->redis->shouldReceive('throttle')->andReturn(new DurationLimiterBuilder($this->redis, 'key'));
    $this->redis->shouldReceive('eval')->andReturnUsing(function () {
        return [
            $this->callsAllowed > 0,
            strtotime('5 seconds'),
            $this->callsAllowed--,
        ];
    });

    Redis::shouldReceive('connection')->andReturn($this->redis);

    $this->job = Mockery::mock();

    $this->next = function ($job) {
        $job->fire();
    };
})->createApplication();

test('limits job execution', function (RateLimited $middleware) {
    $this->job->shouldReceive('fire')->times(2);
    $this->job->shouldReceive('release')->times(3);

    foreach (range(1, 5) as $i) {
        $middleware->handle($this->job, $this->next);
    }
})->with('middlewares');

test('does nothing when disabled', function (RateLimited $middleware) {
    $this->job->shouldReceive('fire')->times(1);

    $middleware->enabled(false)->handle($this->job, $this->next);
})->with('middlewares');

test('release can be set with random seconds', function (RateLimited $middleware) {
    $this->job->shouldReceive('fire')->times(2);
    $this->job->shouldReceive('release')->times(1)->with(1);

    foreach (range(1, 3) as $i) {
        $middleware->releaseAfterRandomSeconds(1, 1)
            ->handle($this->job, $this->next);
    }
})->with('middlewares');

test('release can be set with exponential backoff', function (RateLimited $middleware) {
    $this->job->shouldReceive('fire')->times(2);
    $this->job->shouldReceive('release')->with(15)->times(1);
    $this->job->shouldReceive('release')->with(31)->times(1);
    $this->job->shouldReceive('release')->with(63)->times(1);

    foreach (range(1, 5) as $attempts) {
        $middleware
            ->releaseAfterSeconds(1)
            ->releaseAfterBackoff($attempts)
            ->handle($this->job, $this->next);
    }
})->with('middlewares');

test('release can be set with custom exponential backoff rate', function (RateLimited $middleware) {
    $this->job->shouldReceive('fire')->times(2);
    $this->job->shouldReceive('release')->with(40)->times(1);
    $this->job->shouldReceive('release')->with(121)->times(1);
    $this->job->shouldReceive('release')->with(364)->times(1);

    foreach (range(1, 5) as $attempts) {
        $middleware
            ->releaseAfterSeconds(1)
            ->releaseAfterBackoff($attempts, 3)
            ->handle($this->job, $this->next);
    }
})->with('middlewares');
