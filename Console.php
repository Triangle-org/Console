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
use RuntimeException;

/**
 *
 */
class Console extends \localzet\Console
{
    public function __construct(array $config = [], bool $installInternalCommands = true)
    {
        if (!InstalledVersions::isInstalled('triangle/engine')) {
            throw new RuntimeException('Triangle\\Console не может работать без Triangle\\Engine. Для запуска вне среды Triangle рекомендуется использовать `localzet/console`.');
        }

        $config = array_merge(config('plugin.triangle.console.app', []), $config);
        $config['name'] = 'Triangle Console';
        $config['version'] = InstalledVersions::getPrettyVersion('triangle/console');

        parent::__construct($config, $installInternalCommands);
    }

    public function installInternalCommands(): void
    {
        parent::installInternalCommands();
        $this->installCommands(rtrim(InstalledVersions::getInstallPath('triangle/console'), '/') . '/src/Console/Commands', 'Triangle\\Console\\Commands');
    }
}
