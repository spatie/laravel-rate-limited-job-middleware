<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Limiters\DurationLimiterBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Spatie\RateLimitedMiddleware\Events\LimitExceeded;
use Spatie\RateLimitedMiddleware\RateLimited;

const CALLS_ALLOWED = 2;

dataset('middlewares', fn () => [
    'Redis' => (new RateLimited())
        ->allow(CALLS_ALLOWED)
        ->everySeconds(1),
    'Cache' => (new RateLimited(useRedis: false))
        ->allow(CALLS_ALLOWED)
        ->everySeconds(1),
]);

beforeEach(function () {
    config()->set('cache.default', 'array');
    Cache::flush();

    $this->callsAllowed = CALLS_ALLOWED;
    $this->redis = Mockery::mock(Connection::class);

    $this->redis->shouldReceive('throttle')->andReturn(new DurationLimiterBuilder($this->redis, 'key'));
    $this->redis->shouldReceive('eval')->andReturnUsing(function () {
        return [
            $this->callsAllowed > 0,
            strtotime('1 seconds'),
            $this->callsAllowed--,
        ];
    });

    Redis::shouldReceive('connection')->andReturn($this->redis);

    $this->job = Mockery::mock(TestJob::class);

    $this->next = function ($job) {
        $job->fire();
    };

    Event::fake();
});

test('limits job execution', function (RateLimited $middleware) {
    $this->job->shouldReceive('fire')->times(2);
    $this->job->shouldReceive('release')->times(3);

    foreach (range(1, 5) as $i) {
        $middleware->handle($this->job, $this->next);
    }

    Event::assertDispatchedTimes(LimitExceeded::class, 3);
})->with('middlewares');

test('limits job execution but does not release', function (RateLimited $middleware) {
    $middleware->dontRelease();

    $this->job->shouldReceive('fire')->times(2);
    $this->job->shouldReceive('release')->never();

    foreach (range(1, 5) as $i) {
        $middleware->handle($this->job, $this->next);
    }
})->with('middlewares');

test('limits job execution but does not release with callback', function (RateLimited $middleware) {
    $middleware->dontRelease(function ($job = null) {
        expect($job)->not()->toBeNull();

        return true;
    });

    $this->job->shouldReceive('fire')->times(2);
    $this->job->shouldReceive('release')->never();

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

test('release after backoff can handle large number of attempts', function () {
    expect((new RateLimited())->releaseAfterBackoff(30))->toBeInstanceOf(RateLimited::class);
});
