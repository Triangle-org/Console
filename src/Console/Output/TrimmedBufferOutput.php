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

namespace Triangle\Console\Output;

use Triangle\Console\Exception\InvalidArgumentException;
use Triangle\Console\Formatter\OutputFormatterInterface;
use const PHP_EOL;

/**
 * A BufferedOutput that keeps only the last N chars.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class TrimmedBufferOutput extends Output
{
    private $maxLength;
    private $buffer = '';

    public function __construct(int $maxLength, ?int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = false, OutputFormatterInterface $formatter = null)
    {
        if ($maxLength <= 0) {
            throw new InvalidArgumentException(sprintf('"%s()" expects a strictly positive maxLength. Got %d.', __METHOD__, $maxLength));
        }

        parent::__construct($verbosity, $decorated, $formatter);
        $this->maxLength = $maxLength;
    }

    /**
     * Empties buffer and returns its content.
     *
     * @return string
     */
    public function fetch()
    {
        $content = $this->buffer;
        $this->buffer = '';

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite(string $message, bool $newline)
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->buffer .= PHP_EOL;
        }

        $this->buffer = substr($this->buffer, 0 - $this->maxLength);
    }
}
