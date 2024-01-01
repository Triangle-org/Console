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

use Triangle\Console\Exception\InvalidArgumentException;
use function count;
use function strlen;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Color
{
    /**
     *
     */
    private const COLORS = [
        'black' => 0,
        'red' => 1,
        'green' => 2,
        'yellow' => 3,
        'blue' => 4,
        'magenta' => 5,
        'cyan' => 6,
        'white' => 7,
        'default' => 9,
    ];

    /**
     *
     */
    private const BRIGHT_COLORS = [
        'gray' => 0,
        'bright-red' => 1,
        'bright-green' => 2,
        'bright-yellow' => 3,
        'bright-blue' => 4,
        'bright-magenta' => 5,
        'bright-cyan' => 6,
        'bright-white' => 7,
    ];

    /**
     *
     */
    private const AVAILABLE_OPTIONS = [
        'bold' => ['set' => 1, 'unset' => 22],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink' => ['set' => 5, 'unset' => 25],
        'reverse' => ['set' => 7, 'unset' => 27],
        'conceal' => ['set' => 8, 'unset' => 28],
    ];

    /**
     * @var string
     */
    private $foreground;
    /**
     * @var string
     */
    private $background;
    /**
     * @var array
     */
    private $options = [];

    /**
     * @param string $foreground
     * @param string $background
     * @param array $options
     */
    public function __construct(string $foreground = '', string $background = '', array $options = [])
    {
        $this->foreground = $this->parseColor($foreground);
        $this->background = $this->parseColor($background, true);

        foreach ($options as $option) {
            if (!isset(self::AVAILABLE_OPTIONS[$option])) {
                throw new InvalidArgumentException(sprintf('Invalid option specified: "%s". Expected one of (%s).', $option, implode(', ', array_keys(self::AVAILABLE_OPTIONS))));
            }

            $this->options[$option] = self::AVAILABLE_OPTIONS[$option];
        }
    }

    /**
     * @param string $color
     * @param bool $background
     * @return string
     */
    private function parseColor(string $color, bool $background = false): string
    {
        if ('' === $color) {
            return '';
        }

        if ('#' === $color[0]) {
            $color = substr($color, 1);

            if (3 === strlen($color)) {
                $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
            }

            if (6 !== strlen($color)) {
                throw new InvalidArgumentException(sprintf('Invalid "%s" color.', $color));
            }

            return ($background ? '4' : '3') . $this->convertHexColorToAnsi(hexdec($color));
        }

        if (isset(self::COLORS[$color])) {
            return ($background ? '4' : '3') . self::COLORS[$color];
        }

        if (isset(self::BRIGHT_COLORS[$color])) {
            return ($background ? '10' : '9') . self::BRIGHT_COLORS[$color];
        }

        throw new InvalidArgumentException(sprintf('Invalid "%s" color; expected one of (%s).', $color, implode(', ', array_merge(array_keys(self::COLORS), array_keys(self::BRIGHT_COLORS)))));
    }

    /**
     * @param int $color
     * @return string
     */
    private function convertHexColorToAnsi(int $color): string
    {
        $r = ($color >> 16) & 255;
        $g = ($color >> 8) & 255;
        $b = $color & 255;

        // see https://github.com/termstandard/colors/ for more information about true color support
        if ('truecolor' !== getenv('COLORTERM')) {
            return (string)$this->degradeHexColorToAnsi($r, $g, $b);
        }

        return sprintf('8;2;%d;%d;%d', $r, $g, $b);
    }

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     * @return int
     */
    private function degradeHexColorToAnsi(int $r, int $g, int $b): int
    {
        if (0 === round($this->getSaturation($r, $g, $b) / 50)) {
            return 0;
        }

        return (round($b / 255) << 2) | (round($g / 255) << 1) | round($r / 255);
    }

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     * @return int
     */
    private function getSaturation(int $r, int $g, int $b): int
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;
        $v = max($r, $g, $b);

        if (0 === $diff = $v - min($r, $g, $b)) {
            return 0;
        }

        return (int)$diff * 100 / $v;
    }

    /**
     * @param string $text
     * @return string
     */
    public function apply(string $text): string
    {
        return $this->set() . $text . $this->unset();
    }

    /**
     * @return string
     */
    public function set(): string
    {
        $setCodes = [];
        if ('' !== $this->foreground) {
            $setCodes[] = $this->foreground;
        }
        if ('' !== $this->background) {
            $setCodes[] = $this->background;
        }
        foreach ($this->options as $option) {
            $setCodes[] = $option['set'];
        }
        if (0 === count($setCodes)) {
            return '';
        }

        return sprintf("\033[%sm", implode(';', $setCodes));
    }

    /**
     * @return string
     */
    public function unset(): string
    {
        $unsetCodes = [];
        if ('' !== $this->foreground) {
            $unsetCodes[] = 39;
        }
        if ('' !== $this->background) {
            $unsetCodes[] = 49;
        }
        foreach ($this->options as $option) {
            $unsetCodes[] = $option['unset'];
        }
        if (0 === count($unsetCodes)) {
            return '';
        }

        return sprintf("\033[%sm", implode(';', $unsetCodes));
    }
}
