<?php declare(strict_types=1);

/**
 * @package     Triangle Console Component Tests
 * @link        https://github.com/Triangle-org/Console
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright © 2016-2026 Localzet Group. All rights reserved.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * @license     AGPL-3.0-or-later
 */

namespace {
    require dirname(__DIR__) . '/vendor/autoload.php';
}

namespace Triangle\Console\Tests {
    use Illuminate\Console\Command as IlluminateCommand;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use localzet\Console\Commands\Command;

    class FrameworkCommand extends Command
    {
        protected static string $defaultName = 'triangle:test';
        protected static string $defaultDescription = 'Exercise framework command registration';

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $output->writeln('framework-command-ok');
            return self::SUCCESS;
        }
    }

    class IlluminateSmokeCommand extends IlluminateCommand
    {
        protected $signature = 'triangle:illuminate';
        protected $description = 'Exercise Laravel command registration';

        public function handle(): int
        {
            $this->getOutput()->writeln('illuminate-command-ok');
            return self::SUCCESS;
        }
    }
}

namespace {
    use Illuminate\Container\Container;
    use Symfony\Component\Console\Command\Command as SymfonyCommand;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Output\BufferedOutput;
    use Triangle\Console;
    use Triangle\Engine\Config;
    use Triangle\Engine\Path;
    use Triangle\Console\Tests\FrameworkCommand;
    use Triangle\Console\Tests\IlluminateSmokeCommand;

    $container = new class extends Container {
        public function runningUnitTests(): bool
        {
            return false;
        }
    };
    $directory = sys_get_temp_dir() . '/triangle-console-' . bin2hex(random_bytes(8));
    $configDirectory = $directory . '/config';
    $appDirectory = $directory . '/app';
    mkdir($configDirectory, 0700, true);
    mkdir($appDirectory, 0700, true);

    file_put_contents(
        $configDirectory . '/command.php',
        '<?php return [' . var_export(FrameworkCommand::class, true) . '];'
    );

    Path::$basePath = $directory;
    Path::$appPath = $appDirectory;
    Config::load($configDirectory);

    try {
        $console = new Console();
        $console->setAutoExit(false);
        if (!$console->has('triangle:test') || !$console->has('build:bin')) {
            throw new RuntimeException('Triangle commands were not registered with Localzet Console.');
        }

        $frameworkOutput = new BufferedOutput();
        $frameworkStatus = $console->run(new ArrayInput(['command' => 'triangle:test']), $frameworkOutput);
        if ($frameworkStatus !== SymfonyCommand::SUCCESS || !str_contains($frameworkOutput->fetch(), 'framework-command-ok')) {
            throw new RuntimeException('Triangle framework command did not run through Symfony Console.');
        }

        $laravelConsole = (new Console([], false))->useIlluminate($container);
        $laravelConsole->setAutoExit(false);
        $laravelConsole->addIlluminateCommand(new IlluminateSmokeCommand());
        $laravelOutput = new BufferedOutput();
        $laravelStatus = $laravelConsole->run(new ArrayInput(['command' => 'triangle:illuminate']), $laravelOutput);
        if ($laravelStatus !== SymfonyCommand::SUCCESS || !str_contains($laravelOutput->fetch(), 'illuminate-command-ok')) {
            throw new RuntimeException('Illuminate command did not run through Triangle Console.');
        }

        echo "ENGINE9_CONSOLE_COMMANDS=ok\n";
        echo "ILLUMINATE_CONSOLE_COMMANDS=ok\n";
    } finally {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        ) as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($directory);
    }
}
