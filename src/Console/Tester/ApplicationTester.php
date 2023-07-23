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

namespace Triangle\Console\Tester;

use Triangle\Console\Application;
use Triangle\Console\Input\ArrayInput;
use function function_exists;

/**
 * Eases the testing of console applications.
 *
 * When testing an application, don't forget to disable the auto exit flag:
 *
 *     $application = new Application();
 *     $application->setAutoExit(false);
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ApplicationTester
{
    use TesterTrait;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Executes the application.
     *
     * Available options:
     *
     *  * interactive:               Sets the input interactive flag
     *  * decorated:                 Sets the output decorated flag
     *  * verbosity:                 Sets the output verbosity flag
     *  * capture_stderr_separately: Make output of stdOut and stdErr separately available
     *
     * @return int The command exit code
     */
    public function run(array $input, array $options = [])
    {
        $prevShellVerbosity = getenv('SHELL_VERBOSITY');

        try {
            $this->input = new ArrayInput($input);
            if (isset($options['interactive'])) {
                $this->input->setInteractive($options['interactive']);
            }

            if ($this->inputs) {
                $this->input->setStream(self::createStream($this->inputs));
            }

            $this->initOutput($options);

            return $this->statusCode = $this->application->run($this->input, $this->output);
        } finally {
            // SHELL_VERBOSITY is set by Application::configureIO so we need to unset/reset it
            // to its previous value to avoid one test's verbosity to spread to the following tests
            if (false === $prevShellVerbosity) {
                if (function_exists('putenv')) {
                    @putenv('SHELL_VERBOSITY');
                }
                unset($_ENV['SHELL_VERBOSITY']);
                unset($_SERVER['SHELL_VERBOSITY']);
            } else {
                if (function_exists('putenv')) {
                    @putenv('SHELL_VERBOSITY=' . $prevShellVerbosity);
                }
                $_ENV['SHELL_VERBOSITY'] = $prevShellVerbosity;
                $_SERVER['SHELL_VERBOSITY'] = $prevShellVerbosity;
            }
        }
    }
}
