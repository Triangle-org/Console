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

namespace Triangle\Console\Input;

use Triangle\Console\Exception\InvalidArgumentException;
use Triangle\Console\Exception\LogicException;
use function is_array;

/**
 * Represents a command line option.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class InputOption
{
    /**
     * Do not accept input for the option (e.g. --yell). This is the default behavior of options.
     */
    public const VALUE_NONE = 1;

    /**
     * A value must be passed when the option is used (e.g. --iterations=5 or -i5).
     */
    public const VALUE_REQUIRED = 2;

    /**
     * The option may or may not have a value (e.g. --yell or --yell=loud).
     */
    public const VALUE_OPTIONAL = 4;

    /**
     * The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
     */
    public const VALUE_IS_ARRAY = 8;

    /**
     * The option may have either positive or negative value (e.g. --ansi or --no-ansi).
     */
    public const VALUE_NEGATABLE = 16;

    private $name;
    private $shortcut;
    private $mode;
    private $default;
    private $description;

    /**
     * @param string|array|null $shortcut The shortcuts, can be null, a string of shortcuts delimited by | or an array of shortcuts
     * @param int|null $mode The option mode: One of the VALUE_* constants
     * @param string|bool|int|float|array|null $default The default value (must be null for self::VALUE_NONE)
     *
     * @throws InvalidArgumentException If option mode is invalid or incompatible
     */
    public function __construct(string $name, $shortcut = null, int $mode = null, string $description = '', $default = null)
    {
        if (str_starts_with($name, '--')) {
            $name = substr($name, 2);
        }

        if (empty($name)) {
            throw new InvalidArgumentException('An option name cannot be empty.');
        }

        if (empty($shortcut)) {
            $shortcut = null;
        }

        if (null !== $shortcut) {
            if (is_array($shortcut)) {
                $shortcut = implode('|', $shortcut);
            }
            $shortcuts = preg_split('{(\|)-?}', ltrim($shortcut, '-'));
            $shortcuts = array_filter($shortcuts);
            $shortcut = implode('|', $shortcuts);

            if (empty($shortcut)) {
                throw new InvalidArgumentException('An option shortcut cannot be empty.');
            }
        }

        if (null === $mode) {
            $mode = self::VALUE_NONE;
        } elseif ($mode >= (self::VALUE_NEGATABLE << 1) || $mode < 1) {
            throw new InvalidArgumentException(sprintf('Option mode "%s" is not valid.', $mode));
        }

        $this->name = $name;
        $this->shortcut = $shortcut;
        $this->mode = $mode;
        $this->description = $description;

        if ($this->isArray() && !$this->acceptValue()) {
            throw new InvalidArgumentException('Impossible to have an option mode VALUE_IS_ARRAY if the option does not accept a value.');
        }
        if ($this->isNegatable() && $this->acceptValue()) {
            throw new InvalidArgumentException('Impossible to have an option mode VALUE_NEGATABLE if the option also accepts a value.');
        }

        $this->setDefault($default);
    }

    /**
     * Returns true if the option can take multiple values.
     *
     * @return bool true if mode is self::VALUE_IS_ARRAY, false otherwise
     */
    public function isArray()
    {
        return self::VALUE_IS_ARRAY === (self::VALUE_IS_ARRAY & $this->mode);
    }

    /**
     * Returns true if the option accepts a value.
     *
     * @return bool true if value mode is not self::VALUE_NONE, false otherwise
     */
    public function acceptValue()
    {
        return $this->isValueRequired() || $this->isValueOptional();
    }

    /**
     * Returns true if the option requires a value.
     *
     * @return bool true if value mode is self::VALUE_REQUIRED, false otherwise
     */
    public function isValueRequired()
    {
        return self::VALUE_REQUIRED === (self::VALUE_REQUIRED & $this->mode);
    }

    /**
     * Returns true if the option takes an optional value.
     *
     * @return bool true if value mode is self::VALUE_OPTIONAL, false otherwise
     */
    public function isValueOptional()
    {
        return self::VALUE_OPTIONAL === (self::VALUE_OPTIONAL & $this->mode);
    }

    public function isNegatable(): bool
    {
        return self::VALUE_NEGATABLE === (self::VALUE_NEGATABLE & $this->mode);
    }

    /**
     * Returns the description text.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Checks whether the given option equals this one.
     *
     * @return bool
     */
    public function equals(self $option)
    {
        return $option->getName() === $this->getName()
            && $option->getShortcut() === $this->getShortcut()
            && $option->getDefault() === $this->getDefault()
            && $option->isNegatable() === $this->isNegatable()
            && $option->isArray() === $this->isArray()
            && $option->isValueRequired() === $this->isValueRequired()
            && $option->isValueOptional() === $this->isValueOptional();
    }

    /**
     * Returns the option name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the option shortcut.
     *
     * @return string|null
     */
    public function getShortcut()
    {
        return $this->shortcut;
    }

    /**
     * Returns the default value.
     *
     * @return string|bool|int|float|array|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param string|bool|int|float|array|null $default
     */
    public function setDefault($default = null)
    {
        if (self::VALUE_NONE === (self::VALUE_NONE & $this->mode) && null !== $default) {
            throw new LogicException('Cannot set a default value when using InputOption::VALUE_NONE mode.');
        }

        if ($this->isArray()) {
            if (null === $default) {
                $default = [];
            } elseif (!is_array($default)) {
                throw new LogicException('A default value for an array option must be an array.');
            }
        }

        $this->default = $this->acceptValue() || $this->isNegatable() ? $default : false;
    }
}
