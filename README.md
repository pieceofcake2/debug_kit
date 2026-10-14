# CakePHP 2 DebugKit

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/debug_kit?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/debug_kit?label=Packagist)](https://packagist.org/packages/pieceofcake2/debug_kit)
![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/debug_kit/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen?label=PHPStan)](https://github.com/pieceofcake2/debug_kit/actions/workflows/CI.yml)
![CakePHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/debug_kit/pieceofcake2/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)
[![CI](https://img.shields.io/github/actions/workflow/status/pieceofcake2/debug_kit/CI.yml?label=CI)](https://github.com/pieceofcake2/debug_kit/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/debug_kit?label=Coverage)](https://codecov.io/gh/pieceofcake2/debug_kit)

DebugKit provides a debugging toolbar and enhanced debugging tools for CakePHP 2
applications. This is a fork of [cakephp/debug_kit](https://github.com/cakephp/debug_kit)
for [pieceofcake2/cakephp](https://github.com/pieceofcake2/cakephp).

![DebugKit toolbar](docs/img/legacy/sql_log.png)

## Requirements

* PHP 8.0+
* pieceofcake2/cakephp 2.12+

## Installation

```
composer require --dev pieceofcake2/debug_kit
```

Then load the plugin and add the `DebugKit.Toolbar` component to your `AppController`.
See [Installation](docs/README.md#installation) for the details, including the
`installer-paths` setting for the modern and legacy directory layouts.

## Documentation

See the [documentation](docs/README.md).

## Reporting Issues

Please open an issue on [GitHub](https://github.com/pieceofcake2/debug_kit/issues).

## Contributing

See the [contribution guidelines](https://github.com/pieceofcake2/.github/blob/master/CONTRIBUTING.md).
