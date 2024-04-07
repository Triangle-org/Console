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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Triangle\Console\Util;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class PluginExportCommand extends Command
{
    protected static $defaultName = 'plugin:export';
    protected static $defaultDescription = 'Экспорт плагина';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('name', 'name', InputOption::VALUE_REQUIRED, 'Название плагина (например, triangle/plugin)');
        $this->addOption('source', 'source', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Папки для экспорта');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Экспорт плагина');
        $name = strtolower($input->getOption('name'));
        if (!strpos($name, '/')) {
            $output->writeln('<error>Некорректное название, оно должно содержать символ \'/\' , например framex/plugin</error>');
            return self::INVALID;
        }
        $namespace = Util::nameToNamespace($name);
        $path_relations = $input->getOption('source');
        if (!in_array("config/plugin/$name", $path_relations)) {
            if (is_dir("config/plugin/$name")) {
                $path_relations[] = "config/plugin/$name";
            }
        }
        $original_dest = $dest = base_path() . "/vendor/$name";
        $dest .= '/src';
        $this->writeInstallFile($namespace, $path_relations, $dest);
        $output->writeln("<info>Создание $dest/Install.php</info>");
        foreach ($path_relations as $source) {
            $base_path = pathinfo("$dest/$source", PATHINFO_DIRNAME);
            if (!is_dir($base_path)) {
                mkdir($base_path, 0777, true);
            }
            $output->writeln("<info>Копирую $source в $dest/$source </info>");
            copy_dir($source, "$dest/$source");
        }
        $output->writeln("<info>Сохраняю $name в $original_dest</info>");
        return self::SUCCESS;
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
     * installByRelation
     * @return void
     */
    public static function installByRelation()
    {
        foreach (static::\$pathRelation as \$source => \$target) {
            \$sourceFile = __DIR__ . "/\$source";
            \$targetFile = base_path(\$target);

            if (\$pos = strrpos(\$target, '/')) {
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
     * uninstallByRelation
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
