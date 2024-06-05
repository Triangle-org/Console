<?php

declare(strict_types=1);

/**
 * @package     Triangle Console Plugin
 * @link        https://github.com/Triangle-org/Console
 *
 * @author      Ivan Zorin <ivan@zorin.space>
 * @copyright   Copyright (c) 2022-2024 Triangle Team
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
 *
 *              For any questions, please contact <support@localzet.com>
 */

namespace Triangle\Console\Commands;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ivan Zorin <ivan@zorin.space>
 */
class EnableCommand extends Command
{
    protected static string $defaultName = 'enable';
    protected static string $defaultDescription = 'Добавить проект в автозагрузку';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_dir("/etc/supervisor/conf.d/")) {
            $output->writeln("<error>Для автозагрузки требуется Supervisor. Выполните `apt install supervisor`</>");
            return self::FAILURE;
        }

        $name = file_get_contents(runtime_path("/conf.d/supervisor/triangle.run"));
        $name = $name ?: config('app.domain', generateId());
        $file = runtime_path("/conf.d/supervisor/$name.conf");

        if (!is_dir(runtime_path("/conf.d/supervisor/"))) {
            mkdir(runtime_path("/conf.d/supervisor/"), 777, true);
        }

        if (!is_file($file)) {
            $directory = base_path();
            $stdout_logfile = config('server.stdout_file', runtime_path('logs/stdout.log'));
            $user = config('server.user', 'root');
            empty($user) && $user = 'root';
            $executor = match (true) {
                is_file("$directory/php") => "$directory/php",
                is_file("$directory/php-8.0") => "$directory/php-8.0",
                is_file("$directory/php-8.1") => "$directory/php-8.1",
                is_file("$directory/php-8.2") => "$directory/php-8.2",
                is_file("$directory/php-8.3") => "$directory/php-8.3",
                default => "php",
            };

            $conf = <<<EOF
            [program:$name]
            command = $executor master restart
            directory = $directory
            autostart = true
            autorestart = true
            stopsignal=QUIT
            user = $user
            redirect_stderr=true
            stdout_logfile=$stdout_logfile
            EOF;

            if (file_put_contents($file, $conf) === false) {
                $output->writeln("Ошибка при записи в файл $file");
                return self::FAILURE;
            }

            $output->writeln("<comment>Конфигурация создана</>");
        }

        file_put_contents(runtime_path("/conf.d/supervisor/triangle.run"), $name);

        if (!symlink($file, "/etc/supervisor/conf.d/$name.conf")) {
            $output->writeln("<error>Не удалось создать символическую ссылку</>");
            return self::FAILURE;
        }
        $output->writeln("<info>Ссылка создана</>");

        exec("service supervisor restart");
        $output->writeln("<info>Supervisor перезапущен</>");

        return self::SUCCESS;
    }
}
