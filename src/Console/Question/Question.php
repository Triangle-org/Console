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

namespace Triangle\Console\Question;

use Traversable;
use Triangle\Console\Exception\InvalidArgumentException;
use Triangle\Console\Exception\LogicException;
use function count;
use function is_array;

/**
 * Represents a Question.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Question
{
    /**
     * @var string
     */
    private $question;
    /**
     * @var
     */
    private $attempts;
    /**
     * @var bool
     */
    private $hidden = false;
    /**
     * @var bool
     */
    private $hiddenFallback = true;
    /**
     * @var
     */
    private $autocompleterCallback;
    /**
     * @var
     */
    private $validator;
    /**
     * @var bool|float|int|string|null
     */
    private $default;
    /**
     * @var
     */
    private $normalizer;
    /**
     * @var bool
     */
    private $trimmable = true;
    /**
     * @var bool
     */
    private $multiline = false;

    /**
     * @param string $question The question to ask to the user
     * @param string|bool|int|float|null $default The default answer to return if the user enters nothing
     */
    public function __construct(string $question, $default = null)
    {
        $this->question = $question;
        $this->default = $default;
    }

    /**
     * Returns the question.
     *
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Returns the default answer.
     *
     * @return string|bool|int|float|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Returns whether the user response accepts newline characters.
     */
    public function isMultiline(): bool
    {
        return $this->multiline;
    }

    /**
     * Sets whether the user response should accept newline characters.
     *
     * @return $this
     */
    public function setMultiline(bool $multiline): self
    {
        $this->multiline = $multiline;

        return $this;
    }

    /**
     * Returns whether the user response must be hidden.
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * Sets whether the user response must be hidden or not.
     *
     * @return $this
     *
     * @throws LogicException In case the autocompleter is also used
     */
    public function setHidden(bool $hidden)
    {
        if ($this->autocompleterCallback) {
            throw new LogicException('A hidden question cannot use the autocompleter.');
        }

        $this->hidden = $hidden;

        return $this;
    }

    /**
     * In case the response cannot be hidden, whether to fallback on non-hidden question or not.
     *
     * @return bool
     */
    public function isHiddenFallback()
    {
        return $this->hiddenFallback;
    }

    /**
     * Sets whether to fallback on non-hidden question if the response cannot be hidden.
     *
     * @return $this
     */
    public function setHiddenFallback(bool $fallback)
    {
        $this->hiddenFallback = $fallback;

        return $this;
    }

    /**
     * Gets values for the autocompleter.
     *
     * @return iterable|null
     */
    public function getAutocompleterValues()
    {
        $callback = $this->getAutocompleterCallback();

        return $callback ? $callback('') : null;
    }

    /**
     * Gets the callback function used for the autocompleter.
     */
    public function getAutocompleterCallback(): ?callable
    {
        return $this->autocompleterCallback;
    }

    /**
     * Sets the callback function used for the autocompleter.
     *
     * The callback is passed the user input as argument and should return an iterable of corresponding suggestions.
     *
     * @return $this
     */
    public function setAutocompleterCallback(callable $callback = null): self
    {
        if ($this->hidden && null !== $callback) {
            throw new LogicException('A hidden question cannot use the autocompleter.');
        }

        $this->autocompleterCallback = $callback;

        return $this;
    }

    /**
     * Sets values for the autocompleter.
     *
     * @return $this
     *
     * @throws LogicException
     */
    public function setAutocompleterValues(?iterable $values)
    {
        if (is_array($values)) {
            $values = $this->isAssoc($values) ? array_merge(array_keys($values), array_values($values)) : array_values($values);

            $callback = static function () use ($values) {
                return $values;
            };
        } elseif ($values instanceof Traversable) {
            $valueCache = null;
            $callback = static function () use ($values, &$valueCache) {
                return $valueCache ?? $valueCache = iterator_to_array($values, false);
            };
        } else {
            $callback = null;
        }

        return $this->setAutocompleterCallback($callback);
    }

    /**
     * @param array $array
     * @return bool
     */
    protected function isAssoc(array $array)
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Gets the validator for the question.
     *
     * @return callable|null
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Sets a validator for the question.
     *
     * @return $this
     */
    public function setValidator(callable $validator = null)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Sets the maximum number of attempts.
     *
     * Null means an unlimited number of attempts.
     *
     * @return $this
     *
     * @throws InvalidArgumentException in case the number of attempts is invalid
     */
    public function setMaxAttempts(?int $attempts)
    {
        if (null !== $attempts && $attempts < 1) {
            throw new InvalidArgumentException('Maximum number of attempts must be a positive value.');
        }

        $this->attempts = $attempts;

        return $this;
    }

    /**
     * Gets the maximum number of attempts.
     *
     * Null means an unlimited number of attempts.
     *
     * @return int|null
     */
    public function getMaxAttempts()
    {
        return $this->attempts;
    }

    /**
     * Gets the normalizer for the response.
     *
     * The normalizer can ba a callable (a string), a closure or a class implementing __invoke.
     *
     * @return callable|null
     */
    public function getNormalizer()
    {
        return $this->normalizer;
    }

    /**
     * Sets a normalizer for the response.
     *
     * The normalizer can be a callable (a string), a closure or a class implementing __invoke.
     *
     * @return $this
     */
    public function setNormalizer(callable $normalizer)
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrimmable(): bool
    {
        return $this->trimmable;
    }

    /**
     * @return $this
     */
    public function setTrimmable(bool $trimmable): self
    {
        $this->trimmable = $trimmable;

        return $this;
    }
}
