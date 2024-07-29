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
class MakeCommandCommand extends Command
{
    protected static string $defaultName = 'make:command';
    protected static string $defaultDescription = 'Добавить команду';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Название команды');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $name = $input->getArgument('name');
        $output->writeln("Создание команды $name");

        $name = str_replace(['\\', '/'], '', $name);
        if (!$command_str = Util::guessPath(app_path(), 'command')) {
            $command_str = Util::guessPath(app_path(), 'controller') === 'Controller' ? 'Command' : 'command';
        }
        $items = explode(':', $name);
        $name = '';
        foreach ($items as $item) {
            $name .= ucfirst($item);
        }
        $file = app_path() . "/$command_str/$name.php";
        $upper = $command_str === 'Command';
        $namespace = $upper ? 'App\Command' : 'app\command';

        $this->createCommand($name, $namespace, $file, $command);
        $output->writeln("Готово!");

        return self::SUCCESS;
    }

    protected function getClassName($name): string
    {
        return preg_replace_callback('/:([a-zA-Z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, ucfirst($name)) . 'Command';
    }

    /**
     * @param $name
     * @param $namespace
     * @param $file
     * @param $command
     * @return void
     */
    protected function createCommand($name, $namespace, $file, $command): void
    {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (!is_dir($path)) {
            create_dir($path);
        }
        $desc = str_replace(':', ' ', $command);
        $command_content = <<<EOF
<?php

namespace $namespace;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class $name extends Command
{
    protected static string \$defaultName = '$command';
    protected static string \$defaultDescription = '$desc';

    /**
     * @return void
     */
    protected function configure()
    {
        \$this->addArgument('name', InputArgument::OPTIONAL, 'Описание');
    }

    /**
     * @param InputInterface \$input
     * @param OutputInterface \$output
     * @return int
     */
    protected function execute(InputInterface \$input, OutputInterface \$output): int
    {
        \$name = \$input->getArgument('name');
        \$output->writeln('Выполнена команда $command, name:' . \$name);
        return self::SUCCESS;
    }
}

EOF;
        file_put_contents($file, $command_content);
    }
}
