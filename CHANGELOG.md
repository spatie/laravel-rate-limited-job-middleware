# Changelog

All notable changes to `laravel-rate-limited-job-middleware` will be documented in this file

## 2.8.0 - 2025-02-27

### What's Changed

* Laravel 12.x Compatibility by @laravel-shift in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/58

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.7.0...2.8.0

## 2.7.0 - 2024-09-07

* Add the option to pass a closure to determine if the job should release

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.6.0...2.7.0

## 2.6.0 - 2024-07-29

- Add a new `LimitExceeded` event when the rate limit has exceeded which receives the `$job`

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.5.0...2.6.0

## 2.5.0 - 2024-05-27

* Use artisansdk/ratelimiter again

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.4.3...2.5.0

## 2.4.4 - 2024-05-27

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.4.3...2.4.4

## 2.4.3 - 2024-05-27

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.4.2...2.4.3

## 2.4.2 - 2024-05-27

* Initialize bucket with a timer

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.4.1...2.4.2

## 2.4.1 - 2024-05-08

* Improve leaky bucket for our use case

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.4.0...2.4.1

## 2.4.0 - 2024-03-14

### What's Changed

* Laravel 11.x Compatibility by @laravel-shift in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/55
* Use our own LeakyBucket class

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.3.0...2.4.0

## 2.3.0 - 2023-06-19

### What's Changed

- Add `->dontRelease()` method by @ralphjsmit in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/48

### New Contributors

- @ralphjsmit made their first contribution in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/48

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.7...2.3.0

## 2.2.7 - 2023-02-06

### What's Changed

- Add PHP 8.2 Support by @patinthehat in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/42
- Laravel 10.x Compatibility by @laravel-shift in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/43

### New Contributors

- @patinthehat made their first contribution in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/42
- @laravel-shift made their first contribution in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/43

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.6...2.2.7

## 2.2.6 - 2022-11-02

- Fix implicit float to int casting

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.5...2.2.6

## 2.2.5 - 2022-08-17

- Use v1 of artisansdk/ratelimiter

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.4...2.2.5

## 2.2.4 - 2022-08-15

- The cache rate limiter was using a limit in minutes instead of seconds

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.3...2.2.4

## 2.2.3 - 2022-08-07

### What's Changed

- Changed method return type to static by @Sammyjo20 in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/39

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.2...2.2.3

## 2.2.2 - 2022-08-06

### What's Changed

- Allow release logic to be easily extended by @Sammyjo20 in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/38

### New Contributors

- @Sammyjo20 made their first contribution in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/38

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.1...2.2.2

## 2.2.1 - 2022-04-18

## What's Changed

- Fix "`$releaseInSeconds` must be of type int" exception by @stevebauman in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/37

## New Contributors

- @stevebauman made their first contribution in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/37

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.2.0...2.2.1

## 2.2.0 - 2022-01-12

## What's Changed

- 'off' => 'of' by @edalzell in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/33
- Allow Laravel 9

## New Contributors

- @edalzell made their first contribution in https://github.com/spatie/laravel-rate-limited-job-middleware/pull/33

**Full Changelog**: https://github.com/spatie/laravel-rate-limited-job-middleware/compare/2.1.1...2.2.0

## 2.1.1 - 2021-10-07

- Fix missing timeout on limiter

## 2.1.0 - 2021-10-07

- Improve the cache implementation using a leaky bucket rate limiter.

## 2.0.0 - 2021-09-15

- allow for a cache option + PHP 8 only + Pest (#32)

The API hasn't changed, so you could upgrade without having to change your code.

## 1.5.0 - 2020-11-27

- add support for PHP 8

## 1.4.1 - 2020-09-08

- add support for Laravel 8

## 1.4.0 - 2020-05-29

- add feature exponential back-off to release logic (#18)

## 1.3.0 - 2020-03-02

- add support for Laravel 7

## 1.2.0 - 2019-10-17

- add `releaseAfterRandomSeconds`

## 1.1.1 - 2019-10-14

- renamed parameter method `releaseAfterMinutes` (#13)

## 1.1.0 - 2019-10-14

- add release duration callback (#12)

## 1.0.0 - 2019-10-04

- initial release
