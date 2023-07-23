<?php

declare(strict_types=1);

/**
 * @package     Triangle Console Plugin
 * @link        https://github.com/Triangle-org/Console
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
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

namespace Triangle\Console\Command;

use Triangle\Console\{Input\InputArgument, Input\InputInterface, Output\OutputInterface};


class MakeBootstrapCommand extends Command
{
    protected static ?string $defaultName = 'make:bootstrap';
    protected static ?string $defaultDescription = 'Добавить класс в автозагрузку';

    public function addConfig($class, $config_file): void
    {
        $config = include $config_file;
        if (!in_array($class, $config ?? [])) {
            $config_file_content = file_get_contents($config_file);
            $config_file_content = preg_replace('/];/', "    $class::class,\n];", $config_file_content);
            file_put_contents($config_file, $config_file_content);
        }
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Название класса для автозагрузки');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln("Создание загрузчика $name");
        if (!($pos = strrpos($name, '/'))) {
            $name = ucfirst($name);
            $file = "app/bootstrap/$name.php";
            $namespace = 'app\bootstrap';
        } else {
            $path = 'app/' . substr($name, 0, $pos) . '/bootstrap';
            $name = ucfirst(substr($name, $pos + 1));
            $file = "$path/$name.php";
            $namespace = str_replace('/', '\\', $path);
        }
        $this->createBootstrap($name, $namespace, $file);
        //$this->addConfig("$namespace\\$name", config_path() . '/bootstrap.php');

        return self::SUCCESS;
    }

    /**
     * @param $name
     * @param $namespace
     * @param $file
     * @return void
     */
    protected function createBootstrap($name, $namespace, $file): void
    {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $bootstrap_content = <<<EOF
<?php

namespace $namespace;

use Triangle\Engine\Bootstrap;

class $name implements Bootstrap
{
    public static function start(\$server)
    {
        
    }

}

EOF;
        file_put_contents($file, $bootstrap_content);
    }
}
