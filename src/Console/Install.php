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

namespace Triangle\Console;

/**
 * Класс Install
 * Этот класс предназначен для установки и обновления плагина.
 */
class Install
{
    public const TRIANGLE_PLUGIN = true;

    /**
     * @var array
     */
    protected static array $pathRelation = [
        'Config' => 'config/plugin/triangle/console',
    ];

    /**
     * Установка плагина
     * @return void
     */
    public static function install(): void
    {
        static::installByRelation();
    }

    /**
     * Обновление плагина
     * @return void
     */
    public static function update(): void
    {
        static::installByRelation();
    }

    /**
     * Uninstall
     * @return void
     */
    public static function uninstall()
    {
    }

    /**
     * Установка плагина
     * @return void
     */
    public static function installByRelation(): void
    {
        foreach (static::$pathRelation as $source => $target) {
            $sourceFile = __DIR__ . "/$source";
            $targetFile = base_path($target);

            if ($pos = strrpos($source, '/')) {
                $parentDir = base_path(substr($source, 0, $pos));
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0777, true);
                }
            }

            copy_dir($sourceFile, $targetFile, true);
            remove_dir($sourceFile);

            echo "Создан $target\r\n";
        }
    }

    /**
     * uninstallByRelation
     * @return void
     */
    public static function uninstallByRelation(): void
    {
        foreach (static::$pathRelation as $source => $target) {
            remove_dir(base_path($target));

            echo "Удалён $target\r\n";
        }
    }
}
