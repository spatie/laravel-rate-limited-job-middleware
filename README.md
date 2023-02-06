# A job middleware to rate limit jobs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-rate-limited-job-middleware.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-rate-limited-job-middleware)
![run-tests](https://github.com/spatie/laravel-rate-limited-job-middleware/workflows/run-tests/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-rate-limited-job-middleware.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-rate-limited-job-middleware)

This package contains a [job middleware](https://laravel.com/docs/master/queues#job-middleware) that can rate limit jobs in Laravel apps.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-rate-limited-job-middleware.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-rate-limited-job-middleware)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-rate-limited-job-middleware
```

This package requires Redis to be set up in your Laravel app.

## Usage

By default, the middleware will only allow 5 jobs to be executed per second. Any jobs that are not allowed will be released for 5 seconds. 

To apply the middleware just add the `Spatie\RateLimitedMiddleware\RateLimited` to the middlewares of your job.

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\RateLimitedMiddleware\RateLimited;

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        // your job logic
    }

    public function middleware()
    {
        return [new RateLimited()];
    }
}
```

### Configuring attempts

When using rate limiting, the number of attempts of your job may be hard to predict. Instead of using a fixed number of attempts, it's better to use [time based attempts](https://laravel.com/docs/master/queues#time-based-attempts).

You can add this to your job class:

```php
/*
 * Determine the time at which the job should timeout.
 *
 */
public function retryUntil() :  \DateTime
{
    return now()->addDay();
}
```

### Customizing the behaviour

You can customize all the behaviour. Here's an example where the middleware allows a maximum of 30 jobs to performed in a timespan of 60 seconds. Jobs that are not allowed will be released for 90 seconds.

```php
// in your job

public function middleware()
{
    $rateLimitedMiddleware = (new RateLimited())
        ->allow(30)
        ->everySeconds(60)
        ->releaseAfterSeconds(90);

    return [$rateLimitedMiddleware];
}
```

### Implementing Exponential Backoff

Often remote services such as APIs have rate limits or otherwise respond with a server error. Under these circumstances it makes sense to increment our delay before trying again. You can replace `releaseAfter` methods with `releaseAfterBackoff($this->attempts()` to use the default Rate Limiter interval of 5 seconds. Otherwise, you may chain the `releaseAfter` calls to adjust the backoff interval.

#### Example: `releaseAfterOneMinute()`

```php
// in your job

/**
 * Attempt 1: Release after 60 seconds
 * Attempt 2: Release after 180 seconds
 * Attempt 3: Release after 420 seconds
 * Attempt 4: Release after 900 seconds
 */
public function middleware()
{
    $rateLimitedMiddleware = (new RateLimited())
        ->allow(30)
        ->everySeconds(60)
        ->releaseAfterOneMinute()
        ->releaseAfterBackoff($this->attempts());

    return [$rateLimitedMiddleware];
}
```

#### Example: `releaseAfterSeconds()`

```php
// in your job

/**
 * Attempt 1: Release after 5 seconds
 * Attempt 2: Release after 15 seconds
 * Attempt 3: Release after 35 seconds
 * Attempt 4: Release after 75 seconds
 */
public function middleware()
{
    $rateLimitedMiddleware = (new RateLimited())
        ->allow(30)
        ->everySeconds(60)
        ->releaseAfterSeconds(5)
        ->releaseAfterBackoff($this->attempts());

    return [$rateLimitedMiddleware];
}
```

#### Example: Customize Backoff Rate

`releaseAfterBackoff()` accepts the rate multiplier as the second argument. By default, the multiplier is 2.

Below is an example of setting the rate to 3. You'll notice that as the attempts grow, the difference between a rate of 2 vs. a rate of 3 becomes significantly greater.

```php
// in your job

/**
 * Attempt 1: Release after 5 seconds
 * Attempt 2: Release after 20 seconds
 * Attempt 3: Release after 65 seconds
 * Attempt 4: Release after 200 seconds
 */
public function middleware()
{
    $rateLimitedMiddleware = (new RateLimited())
        ->allow(30)
        ->everySeconds(60)
        ->releaseAfterBackoff($this->attempts(), 3);

    return [$rateLimitedMiddleware];
}
```

### Customizing Redis

By default, the middleware will use the default Redis connection. 

The default key that will be used in redis will be the name of the class that created the instance of the middleware. In most cases this will be name of job in which the middleware is applied. If this is not what you expect, you can use the `key` method to customize it. 
 


Here's an example where a custom connection and custom key is used.

```php
// in your job

public function middleware()
{
    $rateLimitedMiddleware = (new RateLimited())
        ->connectionName('my-custom-connection')
        ->key('my-custom-key');

    return [$rateLimitedMiddleware];
}
```

### Conditionally applying the middleware

If you want to conditionally apply the middleware you can use the `enabled` method. If accepts a boolean that determines if the middleware should rate limit your job or not.

You can also pass a `Closure` to `enabled`. If it evaluates to a truthy value the middleware will be enable.

Here's a silly example where the rate limiting is only activated in January.

```php
// in your job

public function middleware()
{
    $shouldRateLimitJobs = Carbon::now()->month === 1;

    $rateLimitedMiddleware = (new RateLimited())
        ->enabled($shouldRateLimitJobs);

    return [$rateLimitedMiddleware];
}
```

### Available methods.

These methods are available to be called on the middleware. Their names should be self-explanatory.

- `allow(int $allowedNumberOfJobsInTimeSpan)`
- `everySecond(int $timespanInSeconds = 1)`
- `everySeconds(int $timespanInSeconds)`
- `everyMinute(int $timespanInMinutes = 1)`
- `everyMinutes(int $timespanInMinutes)`
- `releaseAfterOneSecond()`
- `releaseAfterSeconds(int $releaseInSeconds)`
- `releaseAfterOneMinute()`
- `releaseAfterMinutes(int $releaseInMinutes)`
- `releaseAfterRandomSeconds(int $min = 1, int $max = 10)`

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

### Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Kruikstraat 22, 2018 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

This code is heavily based on [the rate limiting example](https://laravel.com/docs/master/queues#job-middleware) found in the Laravel docs.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
