<?php

namespace EasifyPHP\Template;

use JetBrains\PhpStorm\ArrayShape;

class Setup
{
    public static function setup()
    {
        $template = json_decode(file_get_contents('composer.json'), true);

        if ($template === null) {
            self::error('composer.json is not valid JSON');
            exit(1);
        }

        $packageName = self::getPackageName();

        [$vendor, $package] = self::getVendorAndPackage($packageName);

        $composerJsonData = self::getComposerJsonData($packageName);

        $authorData = self::getAuthorData();

        if (!empty($authorData)) {
            $composerJsonData['authors'][0] = $authorData;
        }

        self::setAutoloadPaths($composerJsonData, $vendor, $package);

        $composerJsonData = [...$template, ...$composerJsonData];

        self::checkDependencies($composerJsonData);

        self::removeUnwantedScripts($composerJsonData);

        self::writeComposerJson($composerJsonData);
    }

    private static function getPackageName(): string
    {
        return self::prompt('Package name', '/(^[a-z0-9][_.-]?[a-z0-9]+)*\/([a-z0-9]([_.]|-{1,2})?[a-z0-9]+)*$/', 'Package name must be in the format vendor/package');
    }

    private static function getVendorAndPackage(string $packageName): array
    {
        return array_map([self::class, 'transformName'], explode('/', $packageName));
    }

    private static function transformName(string $name): string
    {
        $name = str_replace(' ', '', $name);
        $name = ucfirst($name);

        return preg_replace_callback('/([-_]+)([a-zA-Z0-9])/', static fn ($matches) => strtoupper($matches[2]), $name);
    }

    #[ArrayShape(['name' => 'string', 'type' => 'string', 'description' => 'string', 'license' => 'string', 'require' => 'string[]', 'authors' => 'array'])]
    private static function getComposerJsonData(string $packageName): array
    {
        return [
            'name' => $packageName,
            'description' => self::prompt('Description'),
            'type' => self::prompt('Type', '/^[a-z0-9-]+$/', 'Type must be a valid composer package type', 'library'),
            'license' => self::prompt('License', '/^[a-zA-Z0-9\-.+]+$/', 'License must be a valid SPDX identifier', 'MIT'),
            'require' => ['php' => '>=' . self::promptForPHPVersion()],
            'authors' => [],
        ];
    }

    #[ArrayShape(['name' => 'string', 'email' => 'string'])]
    private static function getAuthorData(): array
    {
        $authorName = self::getAuthorName();
        $authorEmail = self::getAuthorEmail();

        $result = [
            'name' => $authorName,
            'email' => $authorEmail,
        ];

        if (empty($result['name'])) {
            unset($result['name']);
        }

        if (empty($result['email'])) {
            unset($result['email']);
        }

        return $result;
    }

    private static function getAuthorName(): string
    {
        return self::prompt('Author name', '/^[a-zA-Z\s-]+$/', 'Author name must contain only [a-zA-Z] letters, spaces, and dashes', optional: true);
    }

    private static function getAuthorEmail(): string
    {
        return self::prompt('Author email', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', 'Author email must be a valid email address', optional: true);
    }

    private static function setAutoloadPaths(array &$composerJsonData, string $vendor, string $package)
    {
        $composerJsonData['autoload']['psr-4'] = [
            $vendor . '\\' . $package . '\\' => 'src/',
        ];
        $composerJsonData['autoload-dev']['psr-4'] = [
            $vendor . '\\' . $package . '\\Test\\' => 'tests/',
        ];
    }

    private static function promptForPHPVersion(): string
    {
        $minimumVersion = '8.1';

        while (true) {
            $input = self::prompt('Minimum PHP version', '/^\d+\.\d+$/', 'PHP version must be in the format x.y', $minimumVersion);

            if (version_compare($input, $minimumVersion, '>=')) {
                return $input;
            }

            self::error("PHP version must be {$minimumVersion} or higher");
        }
    }

    private static function checkDependencies(array &$composerJsonData)
    {
        $packagesToCheck = [
            'ergebnis/composer-normalize' => [
                'config.allow-plugins.ergebnis/composer-normalize',
                'extra.composer-normalize',
                'scripts.post-autoload-dump',
            ],
            'fakerphp/faker' => [],
            'friendsofphp/php-cs-fixer' => [
                'scripts.fix',
                'scripts.fix:dry',
            ],
            'jetbrains/phpstorm-attributes' => [],
            'pestphp/pest' => [
                'autoload-dev.psr-4.EasifyPHP\Template\Tests\\',
                'config.allow-plugins.pestphp/pest-plugin',
                'scripts.test',
                'scripts.test:coverage',
            ],
            'roave/security-advisories' => [],
            'xheaven/composer-git-hooks' => [
                'scripts.post-install-cmd',
                'scripts.post-update-cmd',
                'extra.hooks',
            ],
        ];

        foreach ($packagesToCheck as $package => $config) {
            if (isset($composerJsonData['require'][$package]) || isset($composerJsonData['require-dev'][$package])) {
                $remove = self::prompt("Do you need {$package}? [yes/no]", '/^yes|no$/', 'Please answer with "yes" or "no"', 'yes');
                if (strtolower($remove) === 'no') {
                    unset($composerJsonData['require'][$package], $composerJsonData['require-dev'][$package]);

                    foreach ($config as $path) {
                        $keys = explode('.', $path);
                        self::unsetNestedKeys($composerJsonData, $keys);
                    }
                }
            }
        }
    }

    private static function unsetNestedKeys(array &$data, array $keys)
    {
        $current = &$data;
        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!isset($current[$keys[$i]])) {
                return;
            }
            $current = &$current[$keys[$i]];
        }
        unset($current[end($keys)]);
    }

    private static function removeUnwantedScripts(array &$composerJsonData)
    {
        unset($composerJsonData['scripts']['post-create-project-cmd']);
    }

    private static function writeComposerJson(array $composerJsonData)
    {
        file_put_contents('composer.json', json_encode($composerJsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Prompt for user input and validate using regex
     */
    private static function prompt(string $question, ?string $regex = null, ?string $errorMessage = null, ?string $default = null, bool $optional = false): string
    {
        while (true) {
            echo $question . ($default ? " [{$default}]" : '') . ': ';
            $input = trim(fgets(STDIN));

            if (empty($input)) {
                if ($default !== null) {
                    return $default;
                }

                if ($optional) {
                    return '';
                }
            }

            if ($regex && !preg_match($regex, $input)) {
                self::error($errorMessage);
            } else {
                return $input;
            }
        }
    }

    /**
     * Display error message and exit
     */
    private static function error(string $errorMessage)
    {
        echo $errorMessage . "\n";
    }

    public static function unlink()
    {
        unlink(__FILE__);
    }
}
