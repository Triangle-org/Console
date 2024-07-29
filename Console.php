<?php declare(strict_types=1);

/**
 * @package     Triangle Console Component
 * @link        https://github.com/Triangle-org/Console
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2023-2024 Triangle Framework Team
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License v3.0
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as published
 *              by the Free Software Foundation, either version 3 of the License, or
 *              (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *              For any questions, please contact <triangle@localzet.com>
 */

namespace Triangle;

use Composer\InstalledVersions;
use Phar;
use RuntimeException;

/**
 *
 */
class Console extends \localzet\Console
{
    public function __construct(array $config = [], bool $installInternalCommands = true)
    {
        if (!InstalledVersions::isInstalled('triangle/engine')) {
            throw new RuntimeException('Triangle\\Console не может работать без Triangle\\Engine. Для запуска вне среды Triangle используйте `localzet/console`.');
        }

        $base_path = defined('BASE_PATH') ? BASE_PATH : (InstalledVersions::getRootPackage()['install_path'] ?? null);
        $config += config('console', config('plugin.triangle.console.app', ['build' => [
            'input_dir' => $base_path,
            'output_dir' => $base_path . DIRECTORY_SEPARATOR . 'build',
            'exclude_pattern' => '#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/))(.*)$#',
            'exclude_files' => ['.env', 'LICENSE', 'composer.json', 'composer.lock', 'triangle.phar', 'triangle'],
            'phar_alias' => 'triangle',
            'phar_filename' => 'triangle.phar',
            'phar_stub' => 'master',
            'signature_algorithm' => Phar::SHA256,
            'private_key_file' => '',
            'php_version' => 8.3,
            'php_ini' => 'memory_limit = 256M',
            'bin_filename' => 'triangle',
        ]]));
        $config['name'] = 'Triangle Console';
        $config['version'] = InstalledVersions::getPrettyVersion('triangle/console');

        parent::__construct($config, $installInternalCommands);
    }

    public function installInternalCommands(): void
    {
        parent::installInternalCommands();
        $this->installCommands(rtrim(InstalledVersions::getInstallPath('triangle/console'), '/') . '/src/Commands', 'Triangle\\Console\\Commands');
    }
}
