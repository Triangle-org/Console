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

use function function_exists;
use function is_resource;
use const DIRECTORY_SEPARATOR;

/**
 *
 */
class Terminal
{
    /**
     * @var
     */
    private static $width;
    /**
     * @var
     */
    private static $height;
    /**
     * @var
     */
    private static $stty;

    /**
     * Gets the terminal width.
     *
     * @return int
     */
    public function getWidth()
    {
        $width = getenv('COLUMNS');
        if (false !== $width) {
            return (int)trim($width);
        }

        if (null === self::$width) {
            self::initDimensions();
        }

        return self::$width ?: 80;
    }

    /**
     * @return void
     */
    private static function initDimensions()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $matches)) {
                // extract [w, H] from "wxh (WxH)"
                // or [w, h] from "wxh"
                self::$width = (int)$matches[1];
                self::$height = isset($matches[4]) ? (int)$matches[4] : (int)$matches[2];
            } elseif (!self::hasVt100Support() && self::hasSttyAvailable()) {
                // only use stty on Windows if the terminal does not support vt100 (e.g. Windows 7 + git-bash)
                // testing for stty in a Windows 10 vt100-enabled console will implicitly disable vt100 support on STDOUT
                self::initDimensionsUsingStty();
            } elseif (null !== $dimensions = self::getConsoleMode()) {
                // extract [w, h] from "wxh"
                self::$width = (int)$dimensions[0];
                self::$height = (int)$dimensions[1];
            }
        } else {
            self::initDimensionsUsingStty();
        }
    }

    /**
     * Returns whether STDOUT has vt100 support (some Windows 10+ configurations).
     */
    private static function hasVt100Support(): bool
    {
        return function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(fopen('php://stdout', 'w'));
    }

    /**
     * @internal
     */
    public static function hasSttyAvailable(): bool
    {
        if (null !== self::$stty) {
            return self::$stty;
        }

        // skip check if exec function is disabled
        if (!function_exists('exec')) {
            return false;
        }

        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = 0 === $exitcode;
    }

    /**
     * Initializes dimensions using the output of an stty columns line.
     */
    private static function initDimensionsUsingStty()
    {
        if ($sttyString = self::getSttyColumns()) {
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
                // extract [w, h] from "rows h; columns w;"
                self::$width = (int)$matches[2];
                self::$height = (int)$matches[1];
            } elseif (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                // extract [w, h] from "; h rows; w columns"
                self::$width = (int)$matches[2];
                self::$height = (int)$matches[1];
            }
        }
    }

    /**
     * Runs and parses stty -a if it's available, suppressing any error output.
     */
    private static function getSttyColumns(): ?string
    {
        return self::readFromProcess('stty -a | grep columns');
    }

    /**
     * @param string $command
     * @return string|null
     */
    private static function readFromProcess(string $command): ?string
    {
        if (!function_exists('proc_open')) {
            return null;
        }

        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (!is_resource($process)) {
            return null;
        }

        $info = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $info;
    }

    /**
     * Runs and parses mode CON if it's available, suppressing any error output.
     *
     * @return int[]|null An array composed of the width and the height or null if it could not be parsed
     */
    private static function getConsoleMode(): ?array
    {
        $info = self::readFromProcess('mode CON');

        if (null === $info || !preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
            return null;
        }

        return [(int)$matches[2], (int)$matches[1]];
    }

    /**
     * Gets the terminal height.
     *
     * @return int
     */
    public function getHeight()
    {
        $height = getenv('LINES');
        if (false !== $height) {
            return (int)trim($height);
        }

        if (null === self::$height) {
            self::initDimensions();
        }

        return self::$height ?: 50;
    }
}
