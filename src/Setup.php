<?php

namespace Easifyphp\Template;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;

class Setup
{
    public static function setup()
    {
        $packageName = self::getPackageName();

        [$vendor, $package] = self::getVendorAndPackage($packageName);

        $composerJsonData = self::getComposerJsonData($packageName);

        $authorData = self::getAuthorData();

        $composerJsonData['authors'][0] = $authorData;

        self::setAutoloadPaths($composerJsonData, $vendor, $package);

        self::removeUnwantedScripts($composerJsonData);

        self::writeComposerJson($composerJsonData);
    }

    private static function getPackageName(): string
    {
        return self::prompt('Package name: ', '/(^[a-z0-9][_.-]?[a-z0-9]+)*\/([a-z0-9]([_.]|-{1,2})?[a-z0-9]+)*$/', 'Package name must be in the format vendor/package');
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
            'type' => self::prompt('Type [library]: ', '/^[a-z0-9-]+$/', 'Type must be a valid composer package type', 'library'),
            'description' => self::prompt('Description: '),
            'license' => self::prompt('License [MIT]: ', '/^[a-zA-Z0-9\-.+]+$/', 'License must be a valid SPDX identifier', 'MIT'),
            'require' => ['php' => '>='.self::prompt('Minimum PHP version [8.2]: ', '/^\d+\.\d+$/', 'PHP version must be in the format x.y', '8.2')],
            'authors' => [],
        ];
    }

    #[ArrayShape(['name' => 'string', 'email' => 'string'])]
    private static function getAuthorData(): array
    {
        $authorName = self::getAuthorName();
        $authorEmail = self::getAuthorEmail();

        return [
            'name' => $authorName,
            'email' => $authorEmail,
        ];
    }

    private static function getAuthorName(): string
    {
        $authorName = self::prompt('Author name: ');

        if (!preg_match('/^[a-zA-Z\s-]+$/', $authorName)) {
            self::error('Author name must contain only [a-zA-Z] letters, spaces, and dashes');
        }

        return $authorName;
    }

    private static function getAuthorEmail(): string
    {
        $authorEmail = self::prompt('Author email: ');

        if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
            self::error('Author email must be a valid email address');
        }

        return $authorEmail;
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
    private static function prompt(string $question, ?string $regex = null, ?string $errorMessage = null, ?string $default = null): string
    {
        while (true) {
            echo $question.($default ? " [{$default}]" : '').': ';
            $input = trim(fgets(STDIN));

            if (empty($input) && $default !== null) {
                return $default;
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
    #[NoReturn]
    private static function error(string $errorMessage)
    {
        echo $errorMessage."\n";
        exit(1);
    }

    public static function unlink()
    {
        unlink(__FILE__);
    }
}
