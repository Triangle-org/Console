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

namespace Triangle\Console\Completion;

use LogicException;
use Triangle\Console\Exception\RuntimeException;
use Triangle\Console\Input\ArgvInput;
use Triangle\Console\Input\InputDefinition;
use Triangle\Console\Input\InputOption;
use function count;
use function is_array;

/**
 * An input specialized for shell completion.
 *
 * This input allows unfinished option names or values and exposes what kind of
 * completion is expected.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
final class CompletionInput extends ArgvInput
{
    public const TYPE_ARGUMENT_VALUE = 'argument_value';
    public const TYPE_OPTION_VALUE = 'option_value';
    public const TYPE_OPTION_NAME = 'option_name';
    public const TYPE_NONE = 'none';

    private $tokens;
    private $currentIndex;
    private $completionType;
    private $completionName = null;
    private $completionValue = '';

    /**
     * Converts a terminal string into tokens.
     *
     * This is required for shell completions without COMP_WORDS support.
     */
    public static function fromString(string $inputStr, int $currentIndex): self
    {
        preg_match_all('/(?<=^|\s)([\'"]?)(.+?)(?<!\\\\)\1(?=$|\s)/', $inputStr, $tokens);

        return self::fromTokens($tokens[0], $currentIndex);
    }

    /**
     * Create an input based on an COMP_WORDS token list.
     *
     * @param string[] $tokens the set of split tokens (e.g. COMP_WORDS or argv)
     * @param int $currentIndex the index of the cursor (e.g. COMP_CWORD)
     * @return CompletionInput
     */
    public static function fromTokens(array $tokens, int $currentIndex): self
    {
        $input = new self($tokens);
        $input->tokens = $tokens;
        $input->currentIndex = $currentIndex;

        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function bind(InputDefinition $definition): void
    {
        parent::bind($definition);

        $relevantToken = $this->getRelevantToken();
        if ('-' === $relevantToken[0]) {
            // the current token is an input option: complete either option name or option value
            [$optionToken, $optionValue] = explode('=', $relevantToken, 2) + ['', ''];

            $option = $this->getOptionFromToken($optionToken);
            if (null === $option && !$this->isCursorFree()) {
                $this->completionType = self::TYPE_OPTION_NAME;
                $this->completionValue = $relevantToken;

                return;
            }

            if (null !== $option && $option->acceptValue()) {
                $this->completionType = self::TYPE_OPTION_VALUE;
                $this->completionName = $option->getName();
                $this->completionValue = $optionValue ?: (!str_starts_with($optionToken, '--') ? substr($optionToken, 2) : '');

                return;
            }
        }

        $previousToken = $this->tokens[$this->currentIndex - 1];
        if ('-' === $previousToken[0] && '' !== trim($previousToken, '-')) {
            // check if previous option accepted a value
            $previousOption = $this->getOptionFromToken($previousToken);
            if (null !== $previousOption && $previousOption->acceptValue()) {
                $this->completionType = self::TYPE_OPTION_VALUE;
                $this->completionName = $previousOption->getName();
                $this->completionValue = $relevantToken;

                return;
            }
        }

        // complete argument value
        $this->completionType = self::TYPE_ARGUMENT_VALUE;

        foreach ($this->definition->getArguments() as $argumentName => $argument) {
            if (!isset($this->arguments[$argumentName])) {
                break;
            }

            $argumentValue = $this->arguments[$argumentName];
            $this->completionName = $argumentName;
            if (is_array($argumentValue)) {
                $this->completionValue = $argumentValue ? $argumentValue[array_key_last($argumentValue)] : null;
            } else {
                $this->completionValue = $argumentValue;
            }
        }

        if ($this->currentIndex >= count($this->tokens)) {
            if (!isset($this->arguments[$argumentName]) || $this->definition->getArgument($argumentName)->isArray()) {
                $this->completionName = $argumentName;
                $this->completionValue = '';
            } else {
                // we've reached the end
                $this->completionType = self::TYPE_NONE;
                $this->completionName = null;
                $this->completionValue = '';
            }
        }
    }

    /**
     * The token of the cursor, or the last token if the cursor is at the end of the input.
     */
    private function getRelevantToken(): string
    {
        return $this->tokens[$this->isCursorFree() ? $this->currentIndex - 1 : $this->currentIndex];
    }

    /**
     * Whether the cursor is "free" (i.e. at the end of the input preceded by a space).
     */
    private function isCursorFree(): bool
    {
        $nrOfTokens = count($this->tokens);
        if ($this->currentIndex > $nrOfTokens) {
            throw new LogicException('Current index is invalid, it must be the number of input tokens or one more.');
        }

        return $this->currentIndex >= $nrOfTokens;
    }

    private function getOptionFromToken(string $optionToken): ?InputOption
    {
        $optionName = ltrim($optionToken, '-');
        if (!$optionName) {
            return null;
        }

        if ('-' === ($optionToken[1] ?? ' ')) {
            // long option name
            return $this->definition->hasOption($optionName) ? $this->definition->getOption($optionName) : null;
        }

        // short option name
        return $this->definition->hasShortcut($optionName[0]) ? $this->definition->getOptionForShortcut($optionName[0]) : null;
    }

    /**
     * The value already typed by the user (or empty string).
     */
    public function getCompletionValue(): string
    {
        return $this->completionValue;
    }

    public function mustSuggestOptionValuesFor(string $optionName): bool
    {
        return self::TYPE_OPTION_VALUE === $this->getCompletionType() && $optionName === $this->getCompletionName();
    }

    /**
     * Returns the type of completion required.
     *
     * TYPE_ARGUMENT_VALUE when completing the value of an input argument
     * TYPE_OPTION_VALUE   when completing the value of an input option
     * TYPE_OPTION_NAME    when completing the name of an input option
     * TYPE_NONE           when nothing should be completed
     *
     * @return string One of self::TYPE_* constants. TYPE_OPTION_NAME and TYPE_NONE are already implemented by the Console component
     */
    public function getCompletionType(): string
    {
        return $this->completionType;
    }

    /**
     * The name of the input option or argument when completing a value.
     *
     * @return string|null returns null when completing an option name
     */
    public function getCompletionName(): ?string
    {
        return $this->completionName;
    }

    public function mustSuggestArgumentValuesFor(string $argumentName): bool
    {
        return self::TYPE_ARGUMENT_VALUE === $this->getCompletionType() && $argumentName === $this->getCompletionName();
    }

    public function __toString()
    {
        $str = '';
        foreach ($this->tokens as $i => $token) {
            $str .= $token;

            if ($this->currentIndex === $i) {
                $str .= '|';
            }

            $str .= ' ';
        }

        if ($this->currentIndex > $i) {
            $str .= '|';
        }

        return rtrim($str);
    }

    protected function parseToken(string $token, bool $parseOptions): bool
    {
        try {
            return parent::parseToken($token, $parseOptions);
        } catch (RuntimeException $e) {
            // suppress errors, completed input is almost never valid
        }

        return $parseOptions;
    }
}
