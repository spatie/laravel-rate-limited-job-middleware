<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Event;
use Spatie\RateLimitedMiddleware\Events\LimitExceeded;
use Spatie\RateLimitedMiddleware\RateLimited;

beforeEach(function () {
    Event::fake();
});

test('handles SendQueuedMailable without throwing type errors', function () {
    $middleware = new RateLimited(false);
    $middleware->allow(1)->everySecond();

    $mailable = new TestMailable();
    $sendQueuedMailable = new SendQueuedMailable($mailable);

    $next = function ($job) {
        // Mock next callback
    };

    // This should not throw any errors
    $middleware->handle($sendQueuedMailable, $next);
    $middleware->handle($sendQueuedMailable, $next);

    // If we get here without errors, the fix is working
    expect(true)->toBeTrue();
});

test('dispatches LimitExceeded event with correct job type', function () {
    $middleware = new RateLimited(false);
    $middleware->allow(1)->everySecond();

    $mailable = new TestMailable();
    $sendQueuedMailable = new SendQueuedMailable($mailable);

    $next = function ($job) {
        // Mock next callback
    };

    // First call should succeed
    $middleware->handle($sendQueuedMailable, $next);

    // Second call should be rate limited and dispatch event
    $middleware->handle($sendQueuedMailable, $next);

    Event::assertDispatchedTimes(LimitExceeded::class, 1);

    // Verify that the event contains the original mailable
    Event::assertDispatched(LimitExceeded::class, function ($event) {
        return $event->job instanceof TestMailable;
    });
});

test('does not dispatch event when job does not implement ShouldQueue', function () {
    $middleware = new RateLimited(false);
    $middleware->allow(1)->everySecond();

    // Create a mailable that doesn't implement ShouldQueue
    $regularMailable = new class () extends \Illuminate\Mail\Mailable {
        public function build()
        {
            return $this->view('mail.test');
        }
    };

    $sendQueuedMailable = new SendQueuedMailable($regularMailable);

    $next = function ($job) {
        // Mock next callback
    };

    // First call should succeed
    $middleware->handle($sendQueuedMailable, $next);

    // Second call should be rate limited but no event should be dispatched
    $middleware->handle($sendQueuedMailable, $next);

    Event::assertNotDispatched(LimitExceeded::class);
});
