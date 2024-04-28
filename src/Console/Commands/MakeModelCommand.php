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

namespace Triangle\Console\Commands;

use Doctrine\Inflector\InflectorFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Triangle\Console\Util;
use Triangle\Engine\Database\Manager;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class MakeModelCommand extends Command
{
    protected static $defaultName = 'make:model';
    protected static $defaultDescription = 'Создать модель';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Название модели');
        $this->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Соединение с БД. ');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $name = Util::nameToClass($name);
        $connection = $input->getOption('connection');
        $output->writeln("Создание модели $name");

        if (!($pos = strrpos($name, '/'))) {
            $name = ucfirst($name);
            $model_str = Util::guessPath(app_path(), 'model') ?: 'model';
            $file = app_path() . "/$model_str/$name.php";
            $namespace = $model_str === 'Model' ? 'App\Model' : 'app\model';
        } else {
            $name_str = substr($name, 0, $pos);
            if ($real_name_str = Util::guessPath(app_path(), $name_str)) {
                $name_str = $real_name_str;
            } else if ($real_section_name = Util::guessPath(app_path(), strstr($name_str, '/', true))) {
                $upper = strtolower($real_section_name[0]) !== $real_section_name[0];
            } else if ($real_base_controller = Util::guessPath(app_path(), 'controller')) {
                $upper = strtolower($real_base_controller[0]) !== $real_base_controller[0];
            }
            $upper = $upper ?? strtolower($name_str[0]) !== $name_str[0];
            if ($upper && !$real_name_str) {
                $name_str = preg_replace_callback('/\/([a-z])/', function ($matches) {
                    return '/' . strtoupper($matches[1]);
                }, ucfirst($name_str));
            }
            $path = "$name_str/" . ($upper ? 'Model' : 'model');
            $name = ucfirst(substr($name, $pos + 1));
            $file = app_path() . "/$path/$name.php";
            $namespace = str_replace('/', '\\', ($upper ? 'App/' : 'app/') . $path);
        }

        $this->createModel($name, $namespace, $file, $connection);
        $output->writeln("Готово!");

        return self::SUCCESS;
    }

    /**
     * @param $class
     * @param $namespace
     * @param $file
     * @param null $connection
     * @return void
     */
    protected function createModel($class, $namespace, $file, $connection = null): void
    {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (!is_dir($path)) {
            create_dir($path);
        }
        $table = Util::classToName($class);
        $table_val = 'null';
        $pk = 'id';
        $properties = '';
        $connection = $connection ?: 'mysql';
        try {
            $driver = config("database.connections.$connection.driver");
            $prefix = config("database.connections.$connection.prefix") ?? '';
            $database = config("database.connections.$connection.database");
            $inflector = InflectorFactory::create()->build();
            $table_plura = $inflector->pluralize($inflector->tableize($class));
            $con = Manager::connection($connection);

            if ($con->getSchemaBuilder()->hasTable("{$prefix}{$table_plura}")) {
                $table_val = "'$table'";
                $table = "{$prefix}{$table_plura}";
            } else if ($con->getSchemaBuilder()->hasTable("{$prefix}{$table}")) {
                $table_val = "'$table'";
                $table = "{$prefix}{$table}";
            }

            $tableColumns = [];
            $comments = '';
            if ($driver === 'mysql') {
                $tableComment = $con->select('SELECT table_comment AS comment FROM information_schema.`TABLES` WHERE table_schema = ? AND table_name = ?', [$database, $table]);
                $comments = $tableComment[0]->comment ?? '';
                $tableColumns = $con->select("SELECT 
                                                        COLUMN_NAME     AS name, 
                                                        DATA_TYPE       AS type, 
                                                        CASE 
                                                            WHEN COLUMN_KEY  = 'PRI' 
                                                            THEN true 
                                                            ELSE false 
                                                        END             AS ispk, 
                                                        COLUMN_COMMENT  AS description
                                                    FROM information_schema.columns 
                                                    WHERE table_name = '$table' AND table_schema = '$database' ORDER BY ordinal_position");
            } elseif ($driver === 'pgsql') {
                $tableComment = $con->select('SELECT obj_description(oid) AS comment FROM pg_class WHERE relname = ?', [$table]);
                $comments = $tableComment[0]->comment ?? '';
                $tableColumns = $con->select("SELECT 
                                                        column_name                 AS name, 
                                                        data_type                   AS type, 
                                                        false                       AS ispk, 
                                                        COALESCE(description, '')   AS description
                                                    FROM information_schema.columns 
                                                        LEFT JOIN pg_catalog.pg_description on (objsubid = ordinal_position) 
                                                    WHERE table_name = '$table' AND table_catalog = '$database'  ORDER BY ordinal_position");
            } elseif ($driver === 'sqlsrv') {
                // SQL Server не поддерживает комментарии к таблицам
                $tableColumns = $con->select("SELECT 
                                                        column_name AS name, 
                                                        data_type   AS type, 
                                                        false       AS ispk,
                                                        ''          AS description
                                                    FROM information_schema.columns 
                                                    WHERE TABLE_NAME = '$table'");
            } elseif ($driver === 'sqlite') {
                // SQL Server не поддерживает комментарии к таблицам
                $tableColumns = $con->select("SELECT
                                                        name AS name,
                                                        type AS type,
                                                        CASE WHEN pk = 1 THEN true ELSE false END AS ispk,
                                                        '' AS description 
                                                    FROM pragma_table_info('$table')");
            }

            $properties .= " * {$table} {$comments}" . PHP_EOL;

            foreach ($tableColumns as $column) {
                if ($column?->ispk == 1 || $column?->ispk === true) {
                    $pk = $column->name;
                    $column->description .= "(primary)";
                }

                $type = $this->getType($column->type);
                $properties .= " * @property $type \${$column->name} {$column->description}\n";
            }
        } catch (Throwable $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        $properties = rtrim($properties) ?: ' *';
        $model_content = <<<EOF
<?php

namespace $namespace;

use Triangle\\Engine\\Database\\Model;

/**
$properties
 */
class $class extends Model
{
    /**
     * Соединение для модели
     *
     * @var string|null
     */
    protected \$connection = '$connection';
    
    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected \$table = $table_val;

    /**
     * Первичный ключ, связанный с таблицей.
     *
     * @var string
     */
    protected \$primaryKey = '$pk';

    /**
     * Указывает, должна ли модель быть временной меткой.
     *
     * @var bool
     */
    public \$timestamps = false;
    
    
}

EOF;
        file_put_contents($file, $model_content);
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getType(string $type): string
    {
        if (str_contains($type, 'int')) {
            return 'integer';
        }
        return match ($type) {
            'varchar', 'string', 'text', 'date', 'time', 'guid', 'datetimetz', 'datetime', 'decimal', 'enum' => 'string',
            'boolean', 'bool' => 'integer',
            'float' => 'float',
            default => 'mixed',
        };
    }
}
