# Dependency Injection Container #

[![Travis](https://img.shields.io/travis/simply-framework/container.svg?style=flat-square)](https://travis-ci.org/simply-framework/container)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/simply-framework/container.svg?style=flat-square)](https://scrutinizer-ci.com/g/simply-framework/container/)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/simply-framework/container.svg?style=flat-square)](https://scrutinizer-ci.com/g/simply-framework/container/)
[![Packagist](https://img.shields.io/packagist/v/simply/container.svg?style=flat-square)](https://packagist.org/packages/simply/container)

This package provides a [PSR-11](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md)
compatible dependency injection container that attempts to encourage
dependency configuration in static cachaeable manner and using actual
code via classes rather than closures to configure dependencies over
just configuration.

The container also implements container delegation pattern as described in
[container-interop](https://github.com/container-interop/container-interop/blob/master/docs/Delegate-lookup.md)
standard.

The API documentation is available at: http://docs.riimu.net/simply/container/

NOTE: This package is part of personal experimental framework. No stable API or proper documentation is to be expected
until the framework has proven itself in practical use cases.

## Credits ##

This library is Copyright (c) 2017-2018 Riikka Kalliomäki.

See LICENSE for license and copying information.
