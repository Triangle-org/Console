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

namespace Triangle\Console\Helper;

use Symfony\Component\String\UnicodeString;
use Triangle\Console\Formatter\OutputFormatterInterface;
use function count;
use function strlen;

/**
 * Helper is the base class for all helper classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Helper implements HelperInterface
{
    /**
     * @var null
     */
    protected $helperSet = null;

    /**
     * Returns the length of a string, using mb_strwidth if it is available.
     *
     * @return int
     * @deprecated since Symfony 5.3
     *
     */
    public static function strlen(?string $string)
    {
        trigger_deprecation('symfony/console', '5.3', 'Method "%s()" is deprecated and will be removed in Symfony 6.0. Use Helper::width() or Helper::length() instead.', __METHOD__);

        return self::width($string);
    }

    /**
     * Returns the width of a string, using mb_strwidth if it is available.
     * The width is how many characters positions the string will use.
     */
    public static function width(?string $string): int
    {
            $string ?? $string = '';

        if (preg_match('//u', $string)) {
            return (new UnicodeString($string))->width(false);
        }

        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * Returns the length of a string, using mb_strlen if it is available.
     * The length is related to how many bytes the string will use.
     */
    public static function length(?string $string): int
    {
            $string ?? $string = '';

        if (preg_match('//u', $string)) {
            return (new UnicodeString($string))->length();
        }

        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }

        return mb_strlen($string, $encoding);
    }

    /**
     * Returns the subset of a string, using mb_substr if it is available.
     *
     * @return string
     */
    public static function substr(?string $string, int $from, int $length = null)
    {
            $string ?? $string = '';

        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return substr($string, $from, $length);
        }

        return mb_substr($string, $from, $length, $encoding);
    }

    /**
     * @param $secs
     * @return mixed|string|void
     */
    public static function formatTime($secs)
    {
        static $timeFormats = [
            [0, '< 1 sec'],
            [1, '1 sec'],
            [2, 'secs', 1],
            [60, '1 min'],
            [120, 'mins', 60],
            [3600, '1 hr'],
            [7200, 'hrs', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index == count($timeFormats) - 1
                ) {
                    if (2 == count($format)) {
                        return $format[1];
                    }

                    return floor($secs / $format[2]) . ' ' . $format[1];
                }
            }
        }
    }

    /**
     * @param int $memory
     * @return string
     */
    public static function formatMemory(int $memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MiB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * @deprecated since Symfony 5.3
     */
    public static function strlenWithoutDecoration(OutputFormatterInterface $formatter, ?string $string)
    {
        trigger_deprecation('symfony/console', '5.3', 'Method "%s()" is deprecated and will be removed in Symfony 6.0. Use Helper::removeDecoration() instead.', __METHOD__);

        return self::width(self::removeDecoration($formatter, $string));
    }

    /**
     * @param OutputFormatterInterface $formatter
     * @param string|null $string
     * @return array|string|string[]|null
     */
    public static function removeDecoration(OutputFormatterInterface $formatter, ?string $string)
    {
        $isDecorated = $formatter->isDecorated();
        $formatter->setDecorated(false);
        // remove <...> formatting
        $string = $formatter->format($string ?? '');
        // remove already formatted characters
        $string = preg_replace("/\033\[[^m]*m/", '', $string ?? '');
        // remove terminal hyperlinks
        $string = preg_replace('/\\033]8;[^;]*;[^\\033]*\\033\\\\/', '', $string ?? '');
        $formatter->setDecorated($isDecorated);

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * {@inheritdoc}
     */
    public function setHelperSet(HelperSet $helperSet = null)
    {
        $this->helperSet = $helperSet;
    }
}
