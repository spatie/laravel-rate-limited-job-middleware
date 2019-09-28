<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Orchestra\Testbench\TestCase;
use Spatie\RateLimitedMiddleware\RateLimited;

class RateLimitedTest extends TestCase
{
    /** @test */
    public function it_can_be_instanciated()
    {
        $this->assertInstanceOf(RateLimited::class, new RateLimited());
    }
}
