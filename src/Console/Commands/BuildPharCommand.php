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

namespace Triangle\Console\Commands;

use Composer\InstalledVersions;

/**
 * @author Ivan Zorin <ivan@zorin.space>
 */
class BuildPharCommand extends \localzet\Console\Commands\BuildBinCommand
{
    /**
     * @return array
     */
    public function getExcludeFiles(): array
    {
        $exclude_command_files_localzet = array_map(function ($cmd_file) {
            if (InstalledVersions::getInstallPath('localzet/console') == InstalledVersions::getRootPackage()['install_path']) {
                return 'src/Console/Commands/' . $cmd_file;
            } else {
                return 'vendor/localzet/console/src/Console/Commands/' . $cmd_file;
            }
        }, $this->exclude_command_files);

        $exclude_command_files_triangle = array_map(function ($cmd_file) {
            if (InstalledVersions::getInstallPath('triangle/console') == InstalledVersions::getRootPackage()['install_path']) {
                return 'src/Console/Commands/' . $cmd_file;
            } else {
                return 'vendor/triangle/console/src/Console/Commands/' . $cmd_file;
            }
        }, $this->exclude_command_files);

        return array_unique(array_merge($exclude_command_files_triangle, $exclude_command_files_localzet, $this->exclude_files));
    }
}