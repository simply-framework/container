# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.0] - 2018-10-17
### Added
- The `Container::getCacheFile()` now accepts an optional callable parameter for encoding values.

### Changed
- The container now stores cached information only in a single property
- Improved tests and some failure conditions with mutation testing

## [0.2.1] - 2018-07-18
### Added
- Added new entry type `WiredEntry` for instantiating objects with container values as constructor parameters.
- Added `ContainerBuilder::registerAutowiredClasses`, which creates `WiredEntry` container entries based on
  inspected constructor parameters for the given classes.

## [0.2.0] - 2018-07-16
### Changed
- Moved the logic of detecting provider methods to new interface method `EntryProvider::getMethods()`.
- Prefer constructors in cache loading, when possible

### Fixed
- `ProviderEntry` will now ensure the value returned by container is actually an object

## 0.1.0 - 2018-07-06
### Added
- Initial development release

[Unreleased]: https://github.com/simply-framework/container/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/simply-framework/container/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/simply-framework/container/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/simply-framework/container/compare/v0.1.0...v0.2.0
