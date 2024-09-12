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

use Closure;
use localzet\Console\Commands\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Triangle\Router;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class RouteListCommand extends Command
{
    protected static string $defaultName = 'route:list';
    protected static string $defaultDescription = 'Список маршрутов';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $headers = ['PATH', 'METHOD', 'CALLBACK', 'MIDDLEWARE', 'NAME'];
        $allMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        $rows = [];
        foreach (Router::getRoutes() as $route) {
            $methods = $route->getMethods();

            if ($methods == $allMethods) {
                $methods = ['ANY'];
            }

            $cb = $route->getCallback();
            $cb = $cb instanceof Closure ? 'Closure' : (is_array($cb) ? $cb[0] . '::' . $cb[1] : var_export($cb, true));
            $rows[] = [$route->getPath(), implode(', ', $methods), $cb, json_encode($route->getMiddleware() ?: null), $route->getName()];
        }

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        return self::SUCCESS;
    }
}
