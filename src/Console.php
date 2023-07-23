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

namespace Triangle;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Triangle\Console\Application;
use Triangle\Console\Command\Command as Commands;

/**
 *
 */
class Console extends Application
{
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
            if (is_dir($file)) {
                continue;
            }
            $class_name = $namspace . '\\' . basename($file, '.php');
            if (!is_a($class_name, Commands::class, true)) {
                continue;
            }
            $this->add(new $class_name);
        }
    }
}
