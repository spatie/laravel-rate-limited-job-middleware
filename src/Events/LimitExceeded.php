<?php

namespace Spatie\RateLimitedMiddleware\Events;

use Illuminate\Contracts\Queue\ShouldQueue;

class LimitExceeded
{
    public function __construct(
        public ShouldQueue $job
    )
    {
    }
}
