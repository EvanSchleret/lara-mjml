# LaraMjml

[![Latest Version on Packagist](https://img.shields.io/packagist/v/evanschleret/lara-mjml.svg?style=flat-square)](https://packagist.org/packages/evanschleret/lara-mjml)

This package is a wrapper for the [MJML](https://mjml.io/) email templating engine. It allows you to easily compile MJML templates into html.

It also uses the spatie MJML package under the hood.

## Installation

You can install the package via composer:

```bash
composer require evanschleret/lara-mjml
```

This package relies on [MJML](https://mjml.io), which you need to install separately using your preferred package manager (e.g. npm):

```bash
npm install mjml
```

## Usage

You can use this package by simply calling the `view` helper function with the name of your MJML template.

Your views must be names as `*.mjml.blade.php` to be compiled by this package.

### Environment Variables and Configuration

You can set the path to the MJML binary in your `.env` file.

```env
MJML_NODE_PATH=null
LARA_MJML_BEAUTIFY=false
LARA_MJML_MINIFY=true
LARA_MJML_KEEP_COMMENTS=false
```

Publish the config file to customize the package configuration and add additional options to the MJML binary.

```bash
php artisan vendor:publish --provider="EvanSchleret\LaraMjml\Providers\LaraMjmlServiceProvider"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
