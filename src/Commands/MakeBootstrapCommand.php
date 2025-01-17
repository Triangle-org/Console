<?php declare(strict_types=1);

/**
 * @package     Triangle Console Component
 * @link        https://github.com/Triangle-org/Console
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2023-2024 Triangle Framework Team
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <triangle@localzet.com>
 */

namespace Triangle\Console\Commands;

use localzet\Console\Commands\Command;
use localzet\Console\Util;
use Symfony\Component\Console\{Input\InputArgument, Input\InputInterface, Output\OutputInterface};

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class MakeBootstrapCommand extends Command
{
    protected static string $defaultName = 'make:bootstrap';
    protected static string $defaultDescription = 'Добавить класс автозагрузки';

    public function addConfig($class, $config_file): void
    {
        $config = include $config_file;
        if (!in_array($class, $config ?? [])) {
            $config_file_content = file_get_contents($config_file);
            $config_file_content = preg_replace('/\];/', "    $class::class,\n];", $config_file_content);
            file_put_contents($config_file, $config_file_content);
        }
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Название класса автозагрузки');
        $this->addArgument('enable', InputArgument::OPTIONAL, 'Активировать сразу?');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $enable = !in_array($input->getArgument('enable'), ['no', '0', 'false', 'n']);
        $output->writeln("Создание загрузчика $name");

        $name = str_replace('\\', '/', $name);
        if (!$bootstrap_str = Util::guessPath(app_path(), 'bootstrap')) {
            $bootstrap_str = Util::guessPath(app_path(), 'controller') === 'Controller' ? 'Bootstrap' : 'bootstrap';
        }
        $upper = $bootstrap_str === 'Bootstrap';
        if (!($pos = strrpos($name, '/'))) {
            $name = ucfirst($name);
            $file = app_path() . "/$bootstrap_str/$name.php";
            $namespace = $upper ? 'App\Bootstrap' : 'app\bootstrap';
        } else {
            if ($real_name = Util::guessPath(app_path(), $name)) {
                $name = $real_name;
            }
            if ($upper && !$real_name) {
                $name = preg_replace_callback('/\/([a-z])/', function ($matches) {
                    return '/' . strtoupper($matches[1]);
                }, ucfirst($name));
            }
            $path = "$bootstrap_str/" . substr($upper ? ucfirst($name) : $name, 0, $pos);
            $name = ucfirst(substr($name, $pos + 1));
            $file = app_path() . "/$path/$name.php";
            $namespace = str_replace('/', '\\', ($upper ? 'App/' : 'app/') . $path);
        }

        $this->createBootstrap($name, $namespace, $file);
        $output->writeln("Готово!");

        if ($enable) {
            $this->addConfig("$namespace\\$name", config_path() . '/bootstrap.php');
        }

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
            create_dir($path);
        }
        $bootstrap_content = <<<EOF
<?php

namespace $namespace;

use localzet\Server;
use Triangle\Engine\Bootstrap;

class $name extends Bootstrap
{
  public static function start(?Server \$server = null): void
    {
        // Если вы не хотите выполнять это в консоли, просто ничего не делайте.
        if (!\$server) return;
        
        // Ваш код
    }
}

EOF;
        file_put_contents($file, $bootstrap_content);
    }
}
