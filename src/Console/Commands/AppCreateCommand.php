<?php

namespace Triangle\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author walkor <walkor@workerman.net>
 * @author Ivan Zorin <ivan@zorin.space>
 */
class AppCreateCommand extends Command
{
    protected static $defaultName = 'app:create';
    protected static $defaultDescription = 'Создать приложение';

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
        $base_path = base_path();
        $this->mkdir("$base_path/plugin/$name/app/controller");
        $this->mkdir("$base_path/plugin/$name/app/model");
        $this->mkdir("$base_path/plugin/$name/app/middleware");
        $this->mkdir("$base_path/plugin/$name/app/view/index");
        $this->mkdir("$base_path/plugin/$name/config");
        $this->mkdir("$base_path/plugin/$name/public");
        $this->mkdir("$base_path/plugin/$name/api");
        $this->createFunctionsFile("$base_path/plugin/$name/app/functions.php");
        $this->createControllerFile("$base_path/plugin/$name/app/controller/IndexController.php", $name);
        $this->createConfigFiles("$base_path/plugin/$name/config", $name);
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

use support\\Request;

class IndexController
{

    public function index()
    {
        return response('Добро пожаловать в $name!');
    }

}

EOF;
        file_put_contents($path, $content);

    }

    /**
     * @param $file
     * @return void
     */
    protected function createFunctionsFile($file): void
    {
        $content = <<<EOF
<?php
/**
 * Here is your custom functions.
 */



EOF;
        file_put_contents($file, $content);
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
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    'version' => '1.0.0'
];

EOF;
        file_put_contents("$base/app.php", $content);

        // autoload.php
        $content = <<<EOF
<?php
return [
    'files' => [
        base_path() . '/plugin/$name/app/functions.php',
    ]
];
EOF;
        file_put_contents("$base/autoload.php", $content);

        // container.php
        $content = <<<EOF
<?php
return new Triangle\\Engine\\Container;

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
    '' => Triangle\\Engine\\Exception\\ExceptionHandler::class,
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
                    runtime_path() . '/logs/$name.log',
                    7,
                    Monolog\\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\\Formatter\\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
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
    '' => [
        
    ]
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
    'default' => [
        'host' => '127.0.0.1',
        'password' => null,
        'port' => 6379,
        'database' => 0,
    ],
];

EOF;
        file_put_contents("$base/redis.php", $content);

        // route.php
        $content = <<<EOF
<?php

use Triangle\\Engine\\Router;


EOF;
        file_put_contents("$base/route.php", $content);

        // static.php
        $content = <<<EOF
<?php

return [
    'enable' => true,
    'middleware' => [],    // Static file Middleware
];

EOF;
        file_put_contents("$base/static.php", $content);

        // view.php
        $content = <<<EOF
<?php

use Triangle\\Engine\\View\\Raw;
use Triangle\\Engine\\View\\Twig;
use Triangle\\Engine\\View\\Blade;
use Triangle\\Engine\\View\\ThinkPHP;

return [
    'handler' => Raw::class,
    'options' => [
        'view_suffix' => 'phtml',
        'pre_renders' => [],
        'post_renders' => [],
        'vars' => [],
    ],
];

EOF;
        file_put_contents("$base/view.php", $content);

    }

}