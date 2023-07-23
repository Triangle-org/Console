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

namespace Triangle\Console\SignalRegistry;

use function count;
use function function_exists;
use function in_array;
use function ini_get;
use function is_callable;

/**
 *
 */
final class SignalRegistry
{
    /**
     * @var array
     */
    private $signalHandlers = [];

    /**
     *
     */
    public function __construct()
    {
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }
    }

    /**
     * @return bool
     */
    public static function isSupported(): bool
    {
        if (!function_exists('pcntl_signal')) {
            return false;
        }

        if (in_array('pcntl_signal', explode(',', ini_get('disable_functions')))) {
            return false;
        }

        return true;
    }

    /**
     * @param int $signal
     * @param callable $signalHandler
     * @return void
     */
    public function register(int $signal, callable $signalHandler): void
    {
        if (!isset($this->signalHandlers[$signal])) {
            $previousCallback = pcntl_signal_get_handler($signal);

            if (is_callable($previousCallback)) {
                $this->signalHandlers[$signal][] = $previousCallback;
            }
        }

        $this->signalHandlers[$signal][] = $signalHandler;

        pcntl_signal($signal, [$this, 'handle']);
    }

    /**
     * @internal
     */
    public function handle(int $signal): void
    {
        $count = count($this->signalHandlers[$signal]);

        foreach ($this->signalHandlers[$signal] as $i => $signalHandler) {
            $hasNext = $i !== $count - 1;
            $signalHandler($signal, $hasNext);
        }
    }
}
