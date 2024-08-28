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
use ErrorException;
use localzet\Console\Util;
use Phar;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Triangle\Engine\Autoload;
use Triangle\Engine\Plugin;

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
        $config += config('console', ['build' => [
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
        ]]);
        $config['name'] = 'Triangle Console';
        $config['version'] = InstalledVersions::getPrettyVersion('triangle/console');

        parent::__construct($config, $installInternalCommands);
    }

    /**
     * @return Console
     * @throws ErrorException
     * @throws ReflectionException
     */
    public function loadAll(): static
    {
        if (!in_array($argv[1] ?? '', ['start', 'restart', 'stop', 'status', 'reload', 'connections'])) {
            Autoload::loadAll();
        } else {
            Autoload::loadCore();
        }

        $config = config();
        $command_path = Util::guessPath(app_path(), 'command', true);

        // Грузим команды из /app/command/*.php
        $this->loadFromConfig($config['command'] ?? []);
        $command_path && $this->installCommands($command_path);

        // Грузим команды из /plugin/{plugin}/app/command/*.php
        Plugin::app_reduce(function ($plugin, $config) {
            $this->loadFromConfig($config['command'] ?? []);

            $plugin_path = config('app.plugin_alias', 'plugin');
            $base_path = "$plugin_path/app/$plugin";
            $command_str = Util::guessPath(base_path($base_path), 'command');

            if ($command_str) {
                $path = "$base_path/$command_str";
                $this->installCommands(base_path($path), str_replace('/', "\\", $path));
            }
        });

        // Грузим команды из /config/plugin/{vendor}/{plugin}/command.php
        Plugin::plugin_reduce(function ($vendor, $plugins, $plugin, $config) {
            $this->loadFromConfig($config['command'] ?? []);
        });

        return $this;
    }

    /**
     * @param array $config
     * @throws ReflectionException
     */
    protected function loadFromConfig(array $config): void
    {
        foreach ($config as $class_name) {
            $reflection = new ReflectionClass($class_name);
            if ($reflection->isAbstract()) continue;

            $properties = $reflection->getStaticProperties();

            $name = $properties['defaultName'] ?? null;
            if (!$name) throw new RuntimeException("У команды $class_name нет defaultName");

            $this->add(new $class_name(config('console', [])));
        }
    }

    public function installInternalCommands(): void
    {
        parent::installInternalCommands();
        $this->installCommands(rtrim(InstalledVersions::getInstallPath('triangle/console'), '/') . '/src/Commands', 'Triangle\\Console\\Commands');
    }
}
