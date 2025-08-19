<?php

namespace Spatie\RateLimitedMiddleware\Tests;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class TestMailable extends Mailable implements ShouldQueue
{
    public function build()
    {
        return $this->view('mail.test');
    }
}
