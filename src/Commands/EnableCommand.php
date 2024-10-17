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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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
        $file_dir = runtime_path("/conf.d/supervisor/");
        if (!is_dir($file_dir)) {
            mkdir($file_dir, 777, true);
        }

        $symlink_dir = "/etc/supervisor/conf.d/";
        if (!is_dir($symlink_dir)) {
            $output->writeln("<error>Для автозагрузки требуется Supervisor. Выполните `apt install supervisor`</>");
            return self::FAILURE;
        }

        $runfile = path_combine($file_dir, "triangle.run");
        if (is_file($runfile)) {
            $name = file_get_contents($runfile);
        } else {
            $name = $this->config('service.name', config('app.domain', generateId()));
        }

        $file = path_combine($file_dir, "$name.conf");
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

        file_put_contents($runfile, $name);

        $symlink = path_combine($symlink_dir, "$name.conf");
        if (is_file($symlink)) {
            if (!unlink($symlink)) {
                $output->writeln("<error>Не удалось удалить файл: $symlink</>");
                return self::FAILURE;
            }
        }
        if (!symlink($file, $symlink)) {
            $output->writeln("<error>Не удалось создать символическую ссылку</>");
            return self::FAILURE;
        }
        $output->writeln("<info>Ссылка создана</>");

        $this->exec("supervisorctl update", $output);
        $output->writeln("<info>Supervisor перезапущен</>");

        sleep(1);
        $process = $this->exec("supervisorctl status", $output);

        $table = new Table($output);
        $table->setHeaders(['NAME', 'STATUS', 'PID', 'UPTIME']);

        $rows = [];
        foreach (explode("\n", $process->getOutput()) as $connection) {
            if (!empty($connection)) {
                $parts = preg_split('/\s+/', $connection);
                $rows[] = [
                    'NAME' => $parts[0],
                    'STATUS' => $parts[1],
                    'PID' => rtrim($parts[3] ?? '', ','),
                    'UPTIME' => $parts[5] ?? ''
                ];
            }
        }

        usort($rows, function ($a, $b) {
            return $a['NAME'] <=> $b['NAME'];
        });

        $table->setRows($rows);
        $table->render();
        return self::SUCCESS;
    }

    private function exec(array|string $command, $output)
    {
        $process = new Process(is_string($command) ? explode(" ", $command) : $command);
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln($process->getErrorOutput());
            return self::FAILURE;
        }

        return $process;
    }
}
