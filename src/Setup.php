<?php

namespace Easifyphp\Template;

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

        self::removeUnwantedScripts($composerJsonData);

        $composerJsonData = array_merge_recursive($template, $composerJsonData);

        self::writeComposerJson($composerJsonData);
    }

    private static function getPackageName(): string
    {
        return self::prompt('Package name', '/(^[a-z0-9][_.-]?[a-z0-9]+)*\/([a-z0-9]([_.]|-{1,2})?[a-z0-9]+)*$/', 'Package name must be in the format vendor/package');
    }

    private static function getVendorAndPackage(string $packageName): array
    {
        return array_map('ucfirst', explode('/', $packageName));
    }

    #[ArrayShape(['name' => 'string', 'type' => 'string', 'description' => 'string', 'license' => 'string', 'require' => 'string[]', 'authors' => 'array'])]
    private static function getComposerJsonData(string $packageName): array
    {
        return [
            'name' => $packageName,
            'description' => self::prompt('Description'),
            'type' => self::prompt('Type', '/^[a-z0-9-]+$/', 'Type must be a valid composer package type', 'library'),
            'license' => self::prompt('License', '/^[a-zA-Z0-9\-.+]+$/', 'License must be a valid SPDX identifier', 'MIT'),
            'require' => ['php' => '>='.self::prompt('Minimum PHP version', '/^\d+\.\d+$/', 'PHP version must be in the format x.y', '8.2')],
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
            $vendor.'\\'.$package.'\\' => 'src/',
        ];
        $composerJsonData['autoload-dev']['psr-4'] = [
            $vendor.'\\'.$package.'\\Test\\' => 'tests/',
        ];
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
            echo $question.($default ? " [{$default}]" : '').': ';
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
        echo $errorMessage."\n";
    }

    public static function unlink()
    {
        unlink(__FILE__);
    }
}
