<?php

declare(strict_types=1);

/**
 * @package     Triangle Console Plugin
 * @link        https://github.com/Triangle-org/Console
 *
 * @author      Ivan Zorin <ivan@zorin.space>
 * @copyright   Copyright (c) 2022-2024 Triangle Team
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
 *
 *              For any questions, please contact <support@localzet.com>
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
