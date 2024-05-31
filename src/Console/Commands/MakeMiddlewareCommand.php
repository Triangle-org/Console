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

use localzet\Console\Commands\Command;
use localzet\Console\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class MakeMiddlewareCommand extends Command
{
    protected static string $defaultName = 'make:middleware';
    protected static string $defaultDescription = 'Создать промежуточное ПО';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Название промежуточного ПО');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln("Создание промежуточного класса $name");

        $name = str_replace('\\', '/', $name);
        if (!$middleware_str = Util::guessPath(app_path(), 'middleware')) {
            $middleware_str = Util::guessPath(app_path(), 'controller') === 'Controller' ? 'Middleware' : 'middleware';
        }
        $upper = $middleware_str === 'Middleware';
        if (!($pos = strrpos($name, '/'))) {
            $name = ucfirst($name);
            $file = app_path() . "/$middleware_str/$name.php";
            $namespace = $upper ? 'App\Middleware' : 'app\middleware';
        } else {
            if ($real_name = Util::guessPath(app_path(), $name)) {
                $name = $real_name;
            }
            if ($upper && !$real_name) {
                $name = preg_replace_callback('/\/([a-z])/', function ($matches) {
                    return '/' . strtoupper($matches[1]);
                }, ucfirst($name));
            }
            $path = "$middleware_str/" . substr($upper ? ucfirst($name) : $name, 0, $pos);
            $name = ucfirst(substr($name, $pos + 1));
            $file = app_path() . "/$path/$name.php";
            $namespace = str_replace('/', '\\', ($upper ? 'App/' : 'app/') . $path);
        }

        $this->createMiddleware($name, $namespace, $file);
        $output->writeln("Готово!");

        return self::SUCCESS;
    }


    /**
     * @param $name
     * @param $namespace
     * @param $file
     * @return void
     */
    protected function createMiddleware($name, $namespace, $file): void
    {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (!is_dir($path)) {
            create_dir($path);
        }
        $middleware_content = <<<EOF
<?php
namespace $namespace;

use Triangle\Engine\Http\Request;
use Triangle\Engine\Http\Response;
use Triangle\Engine\Middleware\MiddlewareInterface;

class $name implements MiddlewareInterface
{
    /**
     * @param Request \$request
     * @param callable \$handler
     * @return Response
     */
    public function process(Request \$request, callable \$handler) : Response
    {
        return \$handler(\$request);
    }
    
}

EOF;
        file_put_contents($file, $middleware_content);
    }
}
