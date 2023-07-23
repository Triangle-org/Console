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

class GitWebhookCommand extends Command
{
    protected static ?string $defaultName = 'git:webhook|git-webhook';
    protected static ?string $defaultDescription = 'Добавить Route для GitHub Webhook';

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
        $file = base_path() . "/config/route.php";
        $conf = <<<EOF

        Route::any('/.githook', function(\$request) {
            \$output = exec('cd ' . base_path() . ' && sudo git pull');
            exec('cd ' . base_path() . ' && sudo php master restart');
            return responseJson(\$output);
        });
        EOF;

        $fstream = fopen($file, 'a');
        fwrite($fstream, $conf);
        fclose($fstream);

        $output->writeln("<info>Route добавлен. Настройте репозиторий на отправку Webhook на " . config('app.domain', '{домен}') . "/.githook</>");
        return self::SUCCESS;
    }
}
