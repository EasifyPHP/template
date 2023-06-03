# EasifyPHP Template

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

This is a template for all EasifyPHP packages. It provides a starting point to create a library that follows modern PHP
standards.

## Features

- PSR-4 autoloading.
- PHP 8.2+ requirement.
- Pre-configured for use with PHP CS Fixer, Pest, Faker, JetBrains PhpStorm Attributes, Composer Normalize, Symfony
  VarDumper, and Roave Security Advisories.
- Scripts for testing, test coverage, and code fixing.

## Installation

Use [Composer](https://getcomposer.org/) to create a project from this template.

```bash
composer create-project easifyphp/template
```

## Usage

After installing, replace the namespace `EasifyPHP\Template` with the namespace you prefer. The source files should be
placed in the `src/` directory.

The template includes [Faker](https://github.com/FakerPHP/Faker) for generating fake data in your tests or seed scripts.
You can use it like so:

```php
$faker = Faker\Factory::create();

$name = $faker->name();
```

Additionally, the template includes
the [JetBrains PhpStorm Attributes](https://github.com/JetBrains/phpstorm-attributes) package. This package provides
additional syntax support when you're using JetBrains PhpStorm as your IDE.

```php
use JetBrains\PhpStorm\ArrayShape;

#[ArrayShape(['id' => 'int', 'name' => 'string'])]
function getEntityData(): array
{
    // ...
}
```

The template also includes the [Symfony VarDumper](https://github.com/symfony/var-dumper) package. This package provides
a better `var_dump()` function that is easier to read and understand.

```php
dump($data);
```

Please refer to the package documentation for more details on how to use it effectively in your project.

## Testing

Use Pest for testing. You can run the tests with the following command:

```bash
composer run test
```

To check the test coverage, run:

```bash
composer run test:coverage
```

This template enforces a minimum test coverage of 90%.

## Code Formatting

The template uses PHP CS Fixer for code formatting. You can run the formatter with the following command:

```bash
composer run fix
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability within this template, please send an e-mail to Mark
via [hello@mmark.me](mailto:hello@mmark.me). All security vulnerabilities will be promptly addressed.

## License

The EasifyPHP Template is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Credits

- [Mark](https://github.com/mmark) (Author)
