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

namespace Triangle\Console\Command;

use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};

class NginxDisableCommand extends Command
{
    protected static ?string $defaultName = 'nginx:disable';
    protected static ?string $defaultDescription = 'Удалить сайт из Nginx';

    /**
     * @return void
     */
    protected function configure()
    {
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = config('nginx.path', "/etc/nginx/sites-enabled");

        if ($path === false) {
            $output->writeln("<info>Сохранение отключено</>");
        }

        if (!is_dir($path)) {
            $output->writeln("<error>Папка $path не существует</>");
            return self::FAILURE;
        }

        $domain = config('app.domain');
        if (empty($domain)) {
            $output->writeln("<error>Не задан app.domain</>");
            return self::FAILURE;
        }
        $file = "$path/$domain.conf";

        if (is_file($file)) {
            @unlink($file);
            $output->writeln("<info>Ссылка удалена</>");

            $output->writeln("<info>Проверка конфигурации:</>");
            exec("nginx -t");

            exec("service nginx restart");
            $output->writeln("<info>Nginx перезагружен</>");
        } else {
            $output->writeln("<error>Файл не существует</>");
        }

        return self::SUCCESS;
    }
}
