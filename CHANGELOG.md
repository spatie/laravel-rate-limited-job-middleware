# Changelog

All notable changes to `laravel-rate-limited-job-middleware` will be documented in this file

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
