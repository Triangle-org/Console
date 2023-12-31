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

namespace Triangle\Console\Style;

/**
 * Output style helpers.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface StyleInterface
{
    /**
     * Formats a command title.
     */
    public function title(string $message);

    /**
     * Formats a section title.
     */
    public function section(string $message);

    /**
     * Formats a list.
     */
    public function listing(array $elements);

    /**
     * Formats informational text.
     *
     * @param string|array $message
     */
    public function text($message);

    /**
     * Formats a success result bar.
     *
     * @param string|array $message
     */
    public function success($message);

    /**
     * Formats an error result bar.
     *
     * @param string|array $message
     */
    public function error($message);

    /**
     * Formats an warning result bar.
     *
     * @param string|array $message
     */
    public function warning($message);

    /**
     * Formats a note admonition.
     *
     * @param string|array $message
     */
    public function note($message);

    /**
     * Formats a caution admonition.
     *
     * @param string|array $message
     */
    public function caution($message);

    /**
     * Formats a table.
     */
    public function table(array $headers, array $rows);

    /**
     * Asks a question.
     *
     * @return mixed
     */
    public function ask(string $question, string $default = null, callable $validator = null);

    /**
     * Asks a question with the user input hidden.
     *
     * @return mixed
     */
    public function askHidden(string $question, callable $validator = null);

    /**
     * Asks for confirmation.
     *
     * @return bool
     */
    public function confirm(string $question, bool $default = true);

    /**
     * Asks a choice question.
     *
     * @param string|int|null $default
     *
     * @return mixed
     */
    public function choice(string $question, array $choices, $default = null);

    /**
     * Add newline(s).
     */
    public function newLine(int $count = 1);

    /**
     * Starts the progress output.
     */
    public function progressStart(int $max = 0);

    /**
     * Advances the progress output X steps.
     */
    public function progressAdvance(int $step = 1);

    /**
     * Finishes the progress output.
     */
    public function progressFinish();
}
