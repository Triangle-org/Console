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
use localzet\Console\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class PluginUninstallCommand extends Command
{
    protected static string $defaultName = 'plugin:uninstall';
    protected static string $defaultDescription = 'Удалить плагин';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Название плагина (например, triangle/plugin)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln("Удаление плагина $name");
        if (!strpos($name, '/')) {
            $output->writeln('<error>Некорректное название, оно должно содержать символ \'/\' , например framex/plugin</error>');
            return self::FAILURE;
        }
        $namespace = Util::nameToNamespace($name);
        $uninstall_function = "\\$namespace\\Install::uninstall";
        $plugin_const = "\\$namespace\\Install::TRIANGLE_PLUGIN";
        if (defined($plugin_const) && is_callable($uninstall_function)) {
            $uninstall_function();
        }
        return self::SUCCESS;
    }
}
