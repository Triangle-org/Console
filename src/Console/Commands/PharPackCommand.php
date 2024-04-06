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

use Phar;
use RuntimeException;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};

class PharPackCommand extends Command
{
    protected static ?string $defaultName = 'phar:pack';
    protected static ?string $defaultDescription = 'Может быть стоит просто упаковать проект в файлы Phar. Легко распространять и использовать.';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkEnv();

        $phar_file_output_dir = base_path();

        if (!file_exists($phar_file_output_dir) && !is_dir($phar_file_output_dir)) {
            if (!mkdir($phar_file_output_dir, 0777, true)) {
                throw new RuntimeException("Не удалось создать выходной каталог phar-файла. Пожалуйста, проверьте разрешение.");
            }
        }

        $phar_filename = 'master.phar';

        $phar_file = rtrim($phar_file_output_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $phar_filename;
        if (file_exists($phar_file)) {
            unlink($phar_file);
        }

        $phar = new Phar($phar_file, 0, 'framex');

        $phar->startBuffering();

        $signature_algorithm = Phar::SHA256;
        $phar->setSignatureAlgorithm($signature_algorithm);

        $phar->buildFromDirectory(BASE_PATH);

        $exclude_files = ['.env', 'LICENSE', 'composer.json', 'composer.lock', 'start.php'];

        foreach ($exclude_files as $file) {
            if ($phar->offsetExists($file)) {
                $phar->delete($file);
            }
        }

        $output->writeln('Сбор файлов завершен, начинаю добавлять файлы в Phar.');

        $phar->setStub("#!/usr/bin/env php
<?php
define('IN_PHAR', true);
Phar::mapPhar('framex');
require 'phar://framex/framex';
__HALT_COMPILER();
");

        $output->writeln('Запись запросов в Phar архив и сохранение изменений');

        $phar->stopBuffering();
        unset($phar);
        return self::SUCCESS;
    }

    /**
     * @throws RuntimeException
     */
    private function checkEnv(): void
    {
        if (!class_exists(Phar::class, false)) {
            throw new RuntimeException("Расширение «Phar» требуется для сборки Phar");
        }

        if (ini_get('phar.readonly')) {
            throw new RuntimeException(
                "'phar.readonly' сейчас в 'On', phar должен установить его в 'Off' или выполнить 'php -d phar.readonly=0 ./framex phar:pack'"
            );
        }
    }
}
