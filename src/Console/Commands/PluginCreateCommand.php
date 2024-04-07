<?php

declare(strict_types=1);

/**
 * @package     Triangle Console Plugin
 * @link        https://github.com/Triangle-org/Console
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     GNU Affero General Public License, version 3
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Triangle\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Triangle\Console\Util;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class PluginCreateCommand extends Command
{
    protected static $defaultName = 'plugin:create';
    protected static $defaultDescription = 'Создать плагин';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('name', 'name', InputOption::VALUE_REQUIRED, 'Название плагина (например, triangle/plugin)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = strtolower($input->getOption('name'));
        $output->writeln("Создание плагина $name");
        if (!strpos($name, '/')) {
            $output->writeln('<error>Некорректное название, оно должно содержать символ \'/\' , например framex/plugin</error>');
            return self::FAILURE;
        }

        $namespace = Util::nameToNamespace($name);

        // Create dir config/plugin/$name
        if (is_dir($plugin_config_path = config_path() . "/plugin/$name")) {
            $output->writeln("<error>Папка $plugin_config_path уже существует</error>");
            return self::FAILURE;
        }

        if (is_dir($plugin_path = base_path() . "/vendor/$name")) {
            $output->writeln("<error>Папка $plugin_path уже существует</error>");
            return self::FAILURE;
        }

        // Add psr-4
        if ($err = $this->addAutoloadToComposerJson($name, $namespace)) {
            $output->writeln("<error>$err</error>");
            return self::FAILURE;
        }

        $this->createConfigFiles($plugin_config_path);

        $this->createVendorFiles($name, $namespace, $plugin_path, $output);

        return self::SUCCESS;
    }

    /**
     * @param $name
     * @param $namespace
     * @return string|void
     */
    protected function addAutoloadToComposerJson($name, $namespace)
    {
        if (!is_file($composer_json_file = base_path() . "/composer.json")) {
            return "$composer_json_file не существует";
        }
        $composer_json = json_decode($composer_json_str = file_get_contents($composer_json_file), true);
        if (!$composer_json) {
            return "Некорректный $composer_json_file";
        }
        if (isset($composer_json['autoload']['psr-4'][$namespace . "\\"])) {
            return;
        }
        $namespace = str_replace("\\", "\\\\", $namespace);
        $composer_json_str = str_replace('"psr-4": {', '"psr-4": {' . "\n      \"$namespace\\\\\" : \"vendor/$name/src\",", $composer_json_str);
        file_put_contents($composer_json_file, $composer_json_str);
    }

    protected function createConfigFiles($plugin_config_path): void
    {
        mkdir($plugin_config_path, 0777, true);
        $app_str = <<<EOF
<?php
return [
    'enable' => true,
];
EOF;
        file_put_contents("$plugin_config_path/app.php", $app_str);
    }

    protected function createVendorFiles($name, $namespace, $plugin_path, $output): void
    {
        mkdir("$plugin_path/src", 0777, true);
        $this->createComposerJson($name, $namespace, $plugin_path);
        if (is_callable('exec')) {
            exec("composer dumpautoload");
        } else {
            $output->writeln("<info>Запустите команду 'composer dumpautoload'</info>");
        }
    }

    /**
     * @param $name
     * @param $namespace
     * @param $dest
     * @return void
     */
    protected function createComposerJson($name, $namespace, $dest): void
    {
        $namespace = str_replace('\\', '\\\\', $namespace);
        $composer_json_content = <<<EOT
{
  "name": "$name",
  "type": "library",
  "license": "MIT",
  "description": "Triangle Plugin $name",
  "require": {
  },
  "autoload": {
    "psr-4": {
      "$namespace\\\\": "src"
    }
  }
}
EOT;
        file_put_contents("$dest/composer.json", $composer_json_content);
    }

    /**
     * @param $namespace
     * @param $path_relations
     * @param $dest_dir
     * @return void
     */
    protected function writeInstallFile($namespace, $path_relations, $dest_dir): void
    {
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0777, true);
        }
        $relations = [];
        foreach ($path_relations as $relation) {
            $relations[$relation] = $relation;
        }
        $relations = var_export($relations, true);
        $install_php_content = <<<EOT
<?php
namespace $namespace;

class Install
{
    const TRIANGLE_PLUGIN = true;

    /**
     * @var array
     */
    protected static \$pathRelation = $relations;

    /**
     * @return void
     */
    public static function install()
    {
        static::installByRelation();
    }

    /**
     * @return void
     */
    public static function uninstall()
    {
        self::uninstallByRelation();
    }

    /**
     * @return void
     */
    public static function installByRelation()
    {
        foreach (static::\$pathRelation as \$source => \$target) {
            \$sourceFile = __DIR__ . "/\$source";
            \$targetFile = base_path(\$target);

            if (\$pos = strrpos(\$dest, '/')) {
                \$parentDir = base_path(substr(\$source, 0, \$pos));
                if (!is_dir(\$parentDir)) {
                    mkdir(\$parentDir, 0777, true);
                }
            }

            copy_dir(\$sourceFile, \$targetFile);
            echo "Создан \$targetFile\\r\\n";
        }
    }

    /**
     * @return void
     */
    public static function uninstallByRelation()
    {
        foreach (static::\$pathRelation as \$source => \$target) {
            \$targetFile = base_path(\$target);
            
            remove_dir(\$targetFile);
            echo "Удалён \$target\\r\\n";
        }
    }
}
EOT;
        file_put_contents("$dest_dir/Install.php", $install_php_content);
    }
}
