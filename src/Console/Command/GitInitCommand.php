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

use Triangle\Console\Input\InputInterface;
use Triangle\Console\Output\OutputInterface;

class GitInitCommand extends Command
{
    protected static ?string $defaultName = 'git:connect|git-connect';
    protected static ?string $defaultDescription = 'Добавить удалённый репозиторий Git';

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
        if (empty(config('app.git'))) {
            $output->writeln("<error>Не задан app.git</>");
            return self::FAILURE;
        }


        exec('git config --global --add safe.directory ' . base_path());
        exec('cd ' . base_path() . ' && sudo git init .');
        $output->writeln("<info>Git инициирован</>");

        exec('cd ' . base_path() . ' && sudo git remote add origin ' . config('app.git'));
        $output->writeln("<info>Добавлен удалённый репозиторий</>");

        exec('cd ' . base_path() . ' && sudo git fetch origin');
        $output->writeln("<info>Получены данные</>");

        $output->writeln("<info>Репозиторий связан с удалённым</>");
        return self::SUCCESS;
    }
}
