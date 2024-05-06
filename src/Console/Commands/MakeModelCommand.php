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

use Doctrine\Inflector\InflectorFactory;
use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use localzet\Console\Util;
use Triangle\Engine\Database\Manager;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class MakeModelCommand extends Command
{
    protected static string $defaultName = 'make:model';
    protected static string $defaultDescription = 'Создать модель';

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
            if ($driver === 'sqlite') {
                // SQLite не поддерживает комментарии к таблицам
                $tableColumns = $con->select("SELECT DISTINCT
                                                        name            AS name,
                                                        type            AS type,
                                                        CASE 
                                                            WHEN pk = 1 
                                                            THEN 1 
                                                            ELSE 0 
                                                        END             AS ispk,
                                                        '' AS description 
                                                    FROM pragma_table_info('$table')");
            } elseif ($driver === 'mysql') {
                $tableComment = $con->select("SELECT 
                                                        table_comment AS comment 
                                                    FROM INFORMATION_SCHEMA.`TABLES` 
                                                    WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$table'");
                $comments = $tableComment[0]->comment ?? '';
                $tableColumns = $con->select("SELECT DISTINCT
                                                        COLUMN_NAME     AS name, 
                                                        DATA_TYPE       AS type, 
                                                        CASE 
                                                            WHEN COLUMN_KEY  = 'PRI' 
                                                            THEN 1 
                                                            ELSE 0 
                                                        END             AS ispk, 
                                                        COLUMN_COMMENT  AS description,
                                                        ORDINAL_POSITION
                                                    FROM INFORMATION_SCHEMA.COLUMNS 
                                                    WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$table'
                                                    ORDER BY ORDINAL_POSITION");
            } elseif ($driver === 'pgsql') {
                $tableComment = $con->select("SELECT 
                                                        obj_description(oid) AS comment 
                                                    FROM pg_class 
                                                    WHERE relname = '$table'");
                $comments = $tableComment[0]->comment ?? '';
                $tableColumns = $con->select("SELECT DISTINCT
                                                        column_name     AS name, 
                                                        data_type       AS type, 
                                                        CASE 
                                                            WHEN pg_attribute.attnum = ANY (pg_index.indkey) 
                                                            THEN 1 
                                                            ELSE 0 
                                                        END             AS ispk, 
                                                        COALESCE(description, '') AS description,
                                                        ordinal_position
                                                    FROM information_schema.columns 
                                                         LEFT JOIN pg_class ON (relname = table_name)
                                                         LEFT JOIN pg_catalog.pg_description ON (objsubid = ordinal_position AND objoid = oid)
                                                         LEFT JOIN pg_attribute ON (pg_attribute.attrelid = pg_class.oid AND pg_attribute.attname = information_schema.columns.column_name)
                                                         LEFT JOIN pg_index ON (pg_index.indrelid = pg_class.oid)
                                                    WHERE table_catalog = '$database' AND table_name = '$table' 
                                                    ORDER BY ordinal_position");
            } elseif ($driver === 'sqlsrv') {
                // SQL Server не поддерживает комментарии к таблицам
                $tableColumns = $con->select("SELECT DISTINCT
                                                        C.COLUMN_NAME   AS name, 
                                                        C.DATA_TYPE     AS type, 
                                                        CASE 
                                                            WHEN PK.COLUMN_NAME IS NOT NULL 
                                                            THEN 1 
                                                            ELSE 0 
                                                        END             AS ispk,
                                                        ISNULL((SELECT value
                                                                FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', 'dbo', 'table', C.TABLE_NAME, 'column', C.COLUMN_NAME)), '') AS description,
                                                        ORDINAL_POSITION
                                                    FROM INFORMATION_SCHEMA.COLUMNS  AS C
                                                        LEFT JOIN (
                                                            SELECT 
                                                                i.name AS index_name, 
                                                                ic.index_column_id AS column_id, 
                                                                col.name AS column_name
                                                            FROM sys.indexes AS i
                                                                     INNER JOIN sys.index_columns AS ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                                                                     INNER JOIN sys.columns AS col ON ic.object_id = col.object_id AND col.column_id = ic.column_id
                                                            WHERE i.is_primary_key = 1
                                                        ) AS PK ON C.COLUMN_NAME = PK.column_name
                                                    WHERE TABLE_CATALOG = '$database' AND TABLE_NAME = '$table'
                                                    ORDER BY ORDINAL_POSITION");
            }

            $properties .= " * {$table} {$comments}" . PHP_EOL;

            foreach ($tableColumns as $column) {
                if ($column?->ispk == 1) {
                    $pk = $column->name;
                    $column->description .= " (primary)";
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
        if (str_contains($type, 'int') || str_contains($type, 'bit') || str_contains($type, 'serial')) {
            return 'integer';
        }

        if (str_contains($type, 'float')
            || str_contains($type, 'double')
            || str_contains($type, 'real')
            || str_contains($type, 'numeric')
            || str_contains($type, 'decimal')) {
            return 'float';
        }

        if (str_contains($type, 'bool')) {
            return 'boolean';
        }

        if (str_contains($type, 'char')
            || str_contains($type, 'text')
            || str_contains($type, 'date')
            || str_contains($type, 'time')
            || str_contains($type, 'guid')
            || str_contains($type, 'enum')
            || str_contains($type, 'cidr')
            || str_contains($type, 'inet')
            || str_contains($type, 'macaddr')
            || str_contains($type, 'tsvector')
            || str_contains($type, 'uuid')
            || str_contains($type, 'xml')
            || str_contains($type, 'json')) {
            return 'string';
        }

        return 'mixed';
    }
}
