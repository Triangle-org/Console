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

use Symfony\Component\Console\{Input\InputArgument, Input\InputInterface, Output\OutputInterface};
use Symfony\Component\Console\Command\Command;
use Triangle\Console\Util;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class MakeCommandCommand extends Command
{
    protected static $defaultName = 'make:command';
    protected static $defaultDescription = 'Добавить команду';

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
            mkdir($path, 0777, true);
        }
        $desc = str_replace(':', ' ', $command);
        $command_content = <<<EOF
<?php

namespace $namespace;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class $name extends Command
{
    protected static \$defaultName = '$command';
    protected static \$defaultDescription = '$desc';

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
    protected function execute(InputInterface \$input, OutputInterface \$output)
    {
        \$name = \$input->getArgument('name');
        \$output->writeln('Выполнена команда $command');
        return self::SUCCESS;
    }
}

EOF;
        file_put_contents($file, $command_content);
    }
}