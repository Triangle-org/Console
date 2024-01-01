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
use Triangle\Console\Exception\RuntimeException;

/**
 * InputInterface is the interface implemented by all input classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface InputInterface
{
    /**
     * Returns the first argument from the raw parameters (not parsed).
     *
     * @return string|null
     */
    public function getFirstArgument();

    /**
     * Returns true if the raw parameters (not parsed) contain a value.
     *
     * This method is to be used to introspect the input parameters
     * before they have been validated. It must be used carefully.
     * Does not necessarily return the correct result for short options
     * when multiple flags are combined in the same option.
     *
     * @param string|array $values The values to look for in the raw parameters (can be an array)
     * @param bool $onlyParams Only check real parameters, skip those following an end of options (--) signal
     *
     * @return bool
     */
    public function hasParameterOption($values, bool $onlyParams = false);

    /**
     * Returns the value of a raw option (not parsed).
     *
     * This method is to be used to introspect the input parameters
     * before they have been validated. It must be used carefully.
     * Does not necessarily return the correct result for short options
     * when multiple flags are combined in the same option.
     *
     * @param string|array $values The value(s) to look for in the raw parameters (can be an array)
     * @param string|bool|int|float|array|null $default The default value to return if no result is found
     * @param bool $onlyParams Only check real parameters, skip those following an end of options (--) signal
     *
     * @return mixed
     */
    public function getParameterOption($values, $default = false, bool $onlyParams = false);

    /**
     * Binds the current Input instance with the given arguments and options.
     *
     * @throws RuntimeException
     */
    public function bind(InputDefinition $definition);

    /**
     * Validates the input.
     *
     * @throws RuntimeException When not enough arguments are given
     */
    public function validate();

    /**
     * Returns all the given arguments merged with the default values.
     *
     * @return array<string|bool|int|float|array|null>
     */
    public function getArguments();

    /**
     * Returns the argument value for a given argument name.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     */
    public function getArgument(string $name);

    /**
     * Sets an argument value by name.
     *
     * @param mixed $value The argument value
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     */
    public function setArgument(string $name, $value);

    /**
     * Returns true if an InputArgument object exists by name or position.
     *
     * @return bool
     */
    public function hasArgument(string $name);

    /**
     * Returns all the given options merged with the default values.
     *
     * @return array<string|bool|int|float|array|null>
     */
    public function getOptions();

    /**
     * Returns the option value for a given option name.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function getOption(string $name);

    /**
     * Sets an option value by name.
     *
     * @param mixed $value The option value
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function setOption(string $name, $value);

    /**
     * Returns true if an InputOption object exists by name.
     *
     * @return bool
     */
    public function hasOption(string $name);

    /**
     * Is this input means interactive?
     *
     * @return bool
     */
    public function isInteractive();

    /**
     * Sets the input interactivity.
     */
    public function setInteractive(bool $interactive);
}
