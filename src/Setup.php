<?php

namespace Easifyphp\Template;

class Setup
{
    public static function setup()
    {

        echo 'Package name: ';
        $packageName = strtolower(trim(stream_get_line(STDIN, 0, PHP_EOL)));
        $matches = [];
        if (!preg_match('/(^[a-z0-9][_.-]?[a-z0-9]+)*\/([a-z0-9]([_.]|-{1,2})?[a-z0-9]+)*$/', $packageName, $matches)) {
            echo "Package name must be in the format vendor/package\n";
            exit(1);
        }
        array_shift($matches);
        [$vendor, $package] = array_map('ucfirst', $matches);

        echo 'Description: ';
        $description = trim(stream_get_line(STDIN, 0, PHP_EOL));

        echo 'License [MIT]: ';
        $license = trim(stream_get_line(STDIN, 0, PHP_EOL));
        if (empty($license)) {
            $license = 'MIT';
        }
        if (!preg_match('/^[a-zA-Z0-9\-.+]+$/', $license)) {
            echo "License must be a valid SPDX identifier\n";
            exit(1);
        }

        echo 'Type [library]: ';
        $type = trim(stream_get_line(STDIN, 0, PHP_EOL));
        if (empty($type)) {
            $type = 'library';
        }
        if (!preg_match('/^[a-z0-9-]+$/', $type)) {
            echo "Type must be a valid composer package type\n";
            exit(1);
        }

        echo 'Minimum PHP version [8.2]: ';
        $phpVersion = trim(stream_get_line(STDIN, 0, PHP_EOL));
        if (empty($phpVersion)) {
            $phpVersion = '8.2';
        }
        if (!preg_match('/^\d+\.\d+$/', $phpVersion)) {
            echo "PHP version must be in the format x.y\n";
            exit(1);
        }

        echo 'Author name: ';
        $authorName = trim(stream_get_line(STDIN, 0, PHP_EOL));

        echo 'Author email: ';
        $authorEmail = trim(stream_get_line(STDIN, 0, PHP_EOL));

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
