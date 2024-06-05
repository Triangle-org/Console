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
class DisableCommand extends Command
{
    protected static string $defaultName = 'disable';
    protected static string $defaultDescription = 'Удалить проект из автозагрузки';

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
        $name = $name ?: config('app.domain');
        $file = "/etc/supervisor/conf.d/$name.conf";

        if (is_file($file)) {
            if (!unlink($file)) {
                $output->writeln("<error>Не удалось удалить файл: $file</>");
                return self::FAILURE;
            }
            $output->writeln("<info>Ссылка удалена</>");

            exec("service supervisor restart");
            $output->writeln("<info>Supervisor перезапущен</>");
        } else {
            $output->writeln("<error>Файл не существует</>");
        }


        return self::SUCCESS;
    }
}
