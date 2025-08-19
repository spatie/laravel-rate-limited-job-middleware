<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestMailable extends Mailable implements ShouldQueue
{
    public function build()
    {
        return $this->view('mail.test');
    }
}
