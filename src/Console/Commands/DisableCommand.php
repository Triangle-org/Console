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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ivan Zorin <ivan@zorin.space>
 */
class DisableCommand extends Command
{
    protected static $defaultName = 'supervisor:disable|disable';
    protected static $defaultDescription = 'Удалить проект из автозагрузки';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_dir("/etc/supervisor/conf.d")) {
            $output->writeln("<error>Для автозагрузки требуется Supervisor</>");
            return self::FAILURE;
        }

        $domain = config('app.domain');
        if (empty($domain)) {
            $output->writeln("<error>Не задан app.domain</>");
            return self::FAILURE;
        }
        $file = "/etc/supervisor/conf.d/$domain.conf";

        if (is_file($file)) {
            @unlink($file);
            $output->writeln("<info>Ссылка удалена</>");

            exec("service supervisor restart");
            $output->writeln("<info>Supervisor перезапущен</>");
        } else {
            $output->writeln("<error>Файл не существует</>");
        }


        return self::SUCCESS;
    }
}
