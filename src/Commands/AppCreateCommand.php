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

namespace Triangle\Console\Commands;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class AppCreateCommand extends Command
{
    protected static string $defaultName = 'app:create';
    protected static string $defaultDescription = 'Создать приложение';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Название приложения');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln("Создание приложения $name");

        if (str_contains($name, '/')) {
            $output->writeln('<error>Некорректное название. Название не должно содержать \'/\'</error>');
            return self::FAILURE;
        }

        if (is_dir($plugin_config_path = base_path() . "/plugin/$name")) {
            $output->writeln("<error>Папка $plugin_config_path уже существует</error>");
            return self::FAILURE;
        }

        $this->createAll($name);

        return self::SUCCESS;
    }

    /**
     * @param $name
     * @return void
     */
    protected function createAll($name): void
    {
        $plugin_path = base_path() . "/plugin/$name";

        $this->mkdir("$plugin_path/app/controller");
        $this->mkdir("$plugin_path/app/middleware");
        $this->mkdir("$plugin_path/app/model");
        $this->mkdir("$plugin_path/app/view");

        $this->mkdir("$plugin_path/config");
        $this->mkdir("$plugin_path/public");
        // $this->mkdir("$plugin_path/api");

        $this->createControllerFile("$plugin_path/app/controller/Index.php", $name);
        $this->createConfigFiles("$plugin_path/config", $name);
    }

    /**
     * @param string $directory
     * @return void
     */
    protected function mkdir(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }
        echo "Создание $directory\r\n";
        create_dir($directory);
    }

    /**
     * @param $path
     * @param $name
     * @return void
     */
    protected function createControllerFile($path, $name): void
    {
        $content = <<<EOF
<?php

namespace plugin\\$name\\app\\controller;

use support\Request;
use Throwable;
use Triangle\Http\Response;

class Index
{
    /**
     * @param Request \$request
     * @return Response
     * @throws Throwable
     */
    public function index(Request \$request): Response
    {
        return response('Добро пожаловать в $name!');
    }

}

EOF;
        file_put_contents($path, $content);

    }

    /**
     * @param $base
     * @param $name
     * @return void
     */
    protected function createConfigFiles($base, $name): void
    {
        // app.php
        $content = <<<EOF
<?php

use support\\Request;

return [
    'debug' => true,
    
    'controller_suffix' => env('CONTROLLER_SUFFIX', ''),
    'controller_reuse' => env('CONTROLLER_REUSE', true),
];

EOF;
        file_put_contents("$base/app.php", $content);

        // autoload.php
        $content = <<<EOF
<?php

return [
    'files' => []
];
EOF;
        file_put_contents("$base/autoload.php", $content);

        // container.php
        $content = <<<EOF
<?php

return new Triangle\\Engine\\Container();

EOF;
        file_put_contents("$base/container.php", $content);


        // database.php
        $content = <<<EOF
<?php

return  [];

EOF;
        file_put_contents("$base/database.php", $content);

        // exception.php
        $content = <<<EOF
<?php

return [
    '' => Triangle\\Exception\\ExceptionHandler::class,
];

EOF;
        file_put_contents("$base/exception.php", $content);

        // log.php
        $content = <<<EOF
<?php

return [
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\\Handler\\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path(env('LOG_FILE_NAME', 'logs/triangle.log')),
                    (int)env('LOG_FILE_COUNT', 7),
                    env('LOG_FILE_LEVEL', Monolog\\Logger::DEBUG),
                ],
                'formatter' => [
                    'class' => Monolog\\Formatter\\LineFormatter::class,
                    'constructor' => [
                        env('LOG_FILE_FORMAT'),
                        env('LOG_FILE_DATE_FORMAT', 'Y-m-d H:i:s'),
                        env('LOG_FILE_INLINE_BREAKS', true)
                    ],
                ],
            ]
        ],
    ],
];

EOF;
        file_put_contents("$base/log.php", $content);

        // middleware.php
        $content = <<<EOF
<?php

return [
    '' => []
];

EOF;
        file_put_contents("$base/middleware.php", $content);

        // process.php
        $content = <<<EOF
<?php

return [];

EOF;
        file_put_contents("$base/process.php", $content);

        // redis.php
        $content = <<<EOF
<?php

return [
    'client' => env('REDIS_CLIENT', 'predis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'triangle_'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'password' => env('REDIS_PASSWORD'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'password' => env('REDIS_PASSWORD'),
        'database' => env('REDIS_DB_CACHE', '1'),
    ],
];

EOF;
        file_put_contents("$base/redis.php", $content);

        // route.php
        $content = <<<EOF
<?php

use Triangle\\Router;


EOF;
        file_put_contents("$base/route.php", $content);

        // static.php
        $content = <<<EOF
<?php

return [
    'enable' => env('STATIC_ENABLE', true),
    'middleware' => [],
];

EOF;
        file_put_contents("$base/static.php", $content);

        // view.php
        $content = <<<EOF
<?php

use Triangle\\Engine\\View\\Blade;
use Triangle\\Engine\\View\\Raw;
use Triangle\\Engine\\View\\ThinkPHP;
use Triangle\\Engine\\View\\Twig;

return [
    'handler' => match (env('VIEW_HANDLER', 'raw')) {
        'blade' => Blade::class,
        'raw' => Raw::class,
        'think' => ThinkPHP::class,
        'twig' => Twig::class,
    },
    'options' => [
        'view_suffix' => 'phtml',
        'vars' => [],
    ],
];

EOF;
        file_put_contents("$base/view.php", $content);

    }

}