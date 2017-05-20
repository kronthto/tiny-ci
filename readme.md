# Tiny CI - Self-hosted Status checks

[![Software License][ico-license]](LICENSE.md)
[![Latest Stable Version][ico-githubversion]][link-releases]
[![Build Status][ico-build]][link-build]

This tool strives to be a lightweight testing automation utility for your private Github projects. It receives a push event via a Webhook and utilizes the Github Status API to report the test results.

## Features

* Verification of [*X-Hub-Signature*](https://developer.github.com/webhooks/securing/)
* Test preparation and script configurable via [config-file](#test-build-configuration)
* Return results to Github using [Statuses](https://developer.github.com/v3/repos/statuses/)
* Track and provide build-log via protected URL

## Install

``` bash
$ composer install (--no-dev -o)
$ cp .env.example .env
$ ./artisan key:generate
```
* Adjust *.env* to your environment (database, ...)
* Set the [Github-Token](https://github.com/settings/tokens) to *.env* - it must have the *repo:status* scope
``` bash
$ ./artisan migrate
```
* Start the [queue worker](https://laravel.com/docs/5.4/queues#running-the-queue-worker)

## Setting up a project

* Add your project to the *projects* table: `repo` should be in Github syntax (*vendor/project*); freely choose a slug and secret.
* Setup an `application/json` webhook for the push-event with the chosen secret to the URL *https://tiny-ci.your.domain/api/hook/myslug*
* Checkout the project to test in *storage/app/repos/myslug/*

### Test-build configuration

The project to test must have a configfile named `tinyci.json` in its root. Example:
``` json
{
  "before": [
    "sleep 5",
    "composer install --prefer-dist --no-interaction --no-suggest"
  ],
  "script": "./vendor/bin/phpunit"
}
```

## Changelog

Please see the [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-githubversion]: https://badge.fury.io/gh/kronthto%2Ftiny-ci.svg
[ico-build]: https://travis-ci.org/kronthto/tiny-ci.svg?branch=master

[link-releases]: https://github.com/kronthto/tiny-ci/releases
[link-contributors]: ../../contributors
[link-build]: https://travis-ci.org/kronthto/tiny-ci
