<?php

namespace Easifyphp\Template;

class Setup
{
    public static function setup()
    {

        echo 'Package name: ';
        $packageName = strtolower(trim(fgets(STDIN)));
        $matches = [];
        if (!preg_match('/(^[a-z0-9][_.-]?[a-z0-9]+)*\/([a-z0-9]([_.]|-{1,2})?[a-z0-9]+)*$/', $packageName, $matches)) {
            echo "Package name must be in the format vendor/package\n";
            exit(1);
        }
        array_shift($matches);
        [$vendor, $package] = array_map('ucfirst', $matches);

        echo 'Description: ';
        $description = trim(fgets(STDIN));

        echo 'License [MIT]: ';
        $license = trim(fgets(STDIN));
        if (empty($license)) {
            $license = 'MIT';
        }
        if (!preg_match('/^[a-zA-Z0-9\-.+]+$/', $license)) {
            echo "License must be a valid SPDX identifier\n";
            exit(1);
        }

        echo 'Type [library]: ';
        $type = trim(fgets(STDIN));
        if (empty($type)) {
            $type = 'library';
        }
        if (!preg_match('/^[a-z0-9-]+$/', $type)) {
            echo "Type must be a valid composer package type\n";
            exit(1);
        }

        echo 'Minimum PHP version [8.2]: ';
        $phpVersion = trim(fgets(STDIN));
        if (empty($phpVersion)) {
            $phpVersion = '8.2';
        }
        if (!preg_match('/^\d+\.\d+$/', $phpVersion)) {
            echo "PHP version must be in the format x.y\n";
            exit(1);
        }

        echo 'Author name: ';
        $authorName = trim(fgets(STDIN));
        if (!empty($authorName) && !preg_match('/^[a-zA-Z\s-]+$/', $authorName)) {
            echo "Author name must contain only [a-zA-Z] letters, spaces, and dashes\n";
            exit(1);
        }

        echo 'Author email: ';
        $authorEmail = trim(fgets(STDIN));
        if (!empty($authorEmail) && !filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
            echo "Author email must be a valid email address\n";
            exit(1);
        }

        $template = file_get_contents('composer.json');
        $data = json_decode($template, true);

        $data['name'] = $packageName;
        $data['description'] = $description;
        $data['license'] = $license;
        $data['type'] = $type;
        $data['require']['php'] = '>='.$phpVersion;
        $data['authors'] = [
            [
                'name' => $authorName,
                'email' => $authorEmail,
            ],
        ];

        if (empty($description)) {
            unset($data['description']);
        }
        if (empty($authorName)) {
            unset($data['authors'][0]['name']);
        }
        if (empty($authorEmail)) {
            unset($data['authors'][0]['email']);
        }

        $data['autoload']['psr-4'] = [
            $vendor.'\\'.$package.'\\' => 'src/',
        ];
        $data['autoload-dev']['psr-4'] = [
            $vendor.'\\'.$package.'\\Tests\\' => 'tests/',
        ];

        unset($data['scripts']['post-create-project-cmd']);

        $newComposerJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents('composer.json', $newComposerJson);

    }

    public static function unlink()
    {
        unlink(__FILE__);
    }
}
