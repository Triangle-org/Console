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

namespace Triangle;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use support\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as Commands;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 *
 */
class Console extends Application
{
    /**
     * @return void
     */
    public function installInternalCommands(): void
    {
        $this->installCommands(__DIR__ . '/Console/Commands', 'Triangle\Console\Commands');
    }

    /**
     * @param string $path
     * @param string $namspace
     * @return void
     */
    public function installCommands(string $path, string $namspace = 'app\\command'): void
    {
        $dir_iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // abc\def.php
            $relativePath = str_replace(str_replace('/', '\\', $path . '\\'), '', str_replace('/', '\\', $file->getRealPath()));
            // app\command\abc
            $realNamespace = trim($namspace . '\\' . trim(dirname(str_replace('\\', DIRECTORY_SEPARATOR, $relativePath)), '.'), '\\');
            $realNamespace = str_replace('/', '\\', $realNamespace);
            // app\command\doc\def
            $class_name = trim($realNamespace . '\\' . $file->getBasename('.php'), '\\');
            if (!class_exists($class_name) || !is_a($class_name, Commands::class, true)) {
                continue;
            }
            $reflection = new ReflectionClass($class_name);
            if ($reflection->isAbstract()) {
                continue;
            }
            $properties = $reflection->getStaticProperties();
            $name = $properties['defaultName'] ?? null;
            if (!$name) {
                throw new RuntimeException("У команды $class_name нет defaultName");
            }
            $description = $properties['defaultDescription'] ?? '';
            $command = Container::get($class_name);
            $command->setName($name)->setDescription($description);
            $this->add($command);
        }
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'Команда для выполнения'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Отобразить справку по данной команде. Если команда не задана, отобразится справка для команды <info>list</info>.'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Не выводить никаких сообщений'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Увеличьте уровень детализации сообщений: 1 - для обычного вывода, 2 - для более подробного вывода и 3 - для отладки.'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Показать версию приложения'),
            new InputOption('--ansi', '', InputOption::VALUE_NEGATABLE, 'Принудительно вывести ANSI (или --no-ansi чтобы отключить ANSI)', null),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Не задавайте интерактивных вопросов'),
        ]);
    }
}
