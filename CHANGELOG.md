# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/).

## [Unreleased]

## [1.0.0] - 2017-06-05
## Upgrading from 0.x
- The `context` for the GitHub status is no longer adjustable (always *push* now), so you may need to adjust the required checks of your protected branches
- Optionally, also add the PR event to your webhooks

### Added
- Handling of PR events

### Fixed
- Also use `putenv` to set *DEBIAN_FRONTEND=noninteractive*

### Removed
- Possibility to set task label in DB

## [0.2.0] - 2017-05-20
### Added
- Track and provide build-log via protected URL

### Changed
- Updated deps
- Error handling duplicate-code moved to own classes/methods

## 0.1.0 - 2017-05-07
### Added
- Basic Project setup
- Verification of X-Hub-Signature
- Github Statuses
- Webhook API
- Test config parsing & running

[Unreleased]: https://github.com/kronthto/tiny-ci/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/kronthto/tiny-ci/compare/v0.2.0...v1.0.0
[0.2.0]: https://github.com/kronthto/tiny-ci/compare/v0.1.0...v0.2.0
