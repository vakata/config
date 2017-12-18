# config

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Scrutinizer Code Quality][ico-code-quality]][link-scrutinizer]
[![Code Coverage][ico-scrutinizer]][link-scrutinizer]

A PHP config class, which supports parsing .env files.

## Install

Via Composer

``` bash
$ composer require vakata/config
```

## Usage

``` php
$config = new \vakata\config\Config([ 'key' => 'value' ]);
$config->fromFile(__DIR__ . '/config.env');
$config->get('key'); // "value"
$config->set('key', 2); // 2
$config->get('key'); // 2
$config->del('key'); // true
$config->get('key'); // null
$config->get('key', 'default'); // "default"
$config->export(); // export all stored values to enviroment and $_SERVER
$config->export(true); // same as above but overwrite existing values
```

Read more in the [API docs](docs/README.md)

## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email github@vakata.com instead of using the issue tracker.

## Credits

- [vakata][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 

[ico-version]: https://img.shields.io/packagist/v/vakata/config.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/vakata/config/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/vakata/config.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/vakata/config.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/vakata/config.svg?style=flat-square
[ico-cc]: https://img.shields.io/codeclimate/github/vakata/config.svg?style=flat-square
[ico-cc-coverage]: https://img.shields.io/codeclimate/coverage/github/vakata/config.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/vakata/config
[link-travis]: https://travis-ci.org/vakata/config
[link-scrutinizer]: https://scrutinizer-ci.com/g/vakata/config
[link-code-quality]: https://scrutinizer-ci.com/g/vakata/config
[link-downloads]: https://packagist.org/packages/vakata/config
[link-author]: https://github.com/vakata
[link-contributors]: ../../contributors
[link-cc]: https://codeclimate.com/github/vakata/config

