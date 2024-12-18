# Changelog

All notable changes to YusrClient will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

## [v1.0.0]

- Initial release of YusrClient
- Implemented PSR-18 HTTP Client interface
- Added support for GET, POST, PUT, DELETE, and PATCH methods
- Implemented Singleton pattern for easy global access
- Added customizable options for each request
- Implemented automatic handling of redirects
- Added configurable timeout and SSL verification options

### Changed

### Deprecated

### Removed

### Fixed

### Security

## [1.0.0] - 2024-10-12

- Initial release

## [1.0.1] - 2024-10-13

- add rector for code style
- fix code style issues
- correct some typos & improve readme

## [1.0.2] - 2024-10-14

## [1.0.3] - 2024-10-19

## [1.0.4] - 2024-10-27

### Added
- Implemented comprehensive retry mechanism with ExponentialBackoff strategy
- Added specific exception classes for better error handling:
  - NetworkException for connection issues
  - TimeoutException for request timeouts
  - SSLException for SSL/TLS related errors
  - RateLimitException for rate limiting
- Enhanced rate limit enforcement with detailed error messages
- Added support for retry strategies through RetryStrategy interface
- Improved error handling with detailed HTTP status codes and messages
- Added ability to customize retry attempts and backoff delays

### Changed
- Refactored sendRequest method to handle exceptions more gracefully
- Updated exception messages to be more descriptive and helpful
- Modified rate limit error handling to use specific exception class
- Improved test coverage for rate limiting and error scenarios

### Fixed
- Fixed rate limit exception message consistency in tests
- Improved error handling for various curl error scenarios
- Better handling of HTTP error responses with retry mechanism


## [1.0.5] - 2024-12-08

## Added
- Enhance curl handle options
- Improve parsing of headers
- Configurable rate limit and time frame
