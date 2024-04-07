<?php

namespace Triangle\Console\Commands;

use Phar;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ivan Zorin <ivan@zorin.space>
 */
class BuildPharCommand extends Command
{
    protected static $defaultName = 'build:phar';
    protected static $defaultDescription = 'Упаковать проект в PHAR';

    protected string $buildDir = '';

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->buildDir = config('plugin.triangle.console.app.build_dir', base_path() . '/build');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkEnv();
        if (!file_exists($this->buildDir) && !is_dir($this->buildDir)) {
            if (!create_dir($this->buildDir)) {
                throw new RuntimeException("Не удалось создать выходной каталог phar-файла. Пожалуйста, проверьте разрешение.");
            }
        }

        $phar_filename = config('plugin.triangle.console.app.phar_filename', 'triangle.phar');
        if (empty($phar_filename)) {
            throw new RuntimeException('Пожалуйста установите имя будущего файла PHAR.');
        }

        $phar_file = rtrim($this->buildDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $phar_filename;
        if (file_exists($phar_file)) {
            unlink($phar_file);
        }

        $exclude_pattern = config('plugin.triangle.console.app.exclude_pattern', '');

        $phar = new Phar($phar_file, 0, 'triangle');

        $phar->startBuffering();

        $signature_algorithm = config('plugin.triangle.console.app.signature_algorithm');
        if (!in_array($signature_algorithm, [Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, Phar::OPENSSL])) {
            throw new RuntimeException('Алгоритм подписи должен быть Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, или Phar::OPENSSL.');
        }
        if ($signature_algorithm === Phar::OPENSSL) {
            $private_key_file = config('plugin.triangle.console.app.private_key_file');
            if (!file_exists($private_key_file)) {
                throw new RuntimeException("Если значение вы выбрали алгоритм Phar::OPENSSL - необходимо задать файл закрытого ключа.");
            }
            $private = openssl_get_privatekey(file_get_contents($private_key_file));
            $pkey = '';
            openssl_pkey_export($private, $pkey);
            $phar->setSignatureAlgorithm($signature_algorithm, $pkey);
        } else {
            $phar->setSignatureAlgorithm($signature_algorithm);
        }

        $phar->buildFromDirectory(BASE_PATH, $exclude_pattern);

        // Исключаем соответствующие файлы
        $exclude_files = config('plugin.triangle.console.app.exclude_files', []);
        $exclude_command_files = [
            'AppCreateCommand.php',
            'BuildBinCommand.php',
            'BuildPharCommand.php',
            'MakeBootstrapCommand.php',
            'MakeCommandCommand.php',
            'MakeControllerCommand.php',
            'MakeMiddlewareCommand.php',
            'MakeModelCommand.php',
            'PluginCreateCommand.php',
            'PluginDisableCommand.php',
            'PluginEnableCommand.php',
            'PluginExportCommand.php',
            'PluginInstallCommand.php',
            'PluginUninstallCommand.php'
        ];
        $exclude_command_files = array_map(function ($cmd_file) {
            return 'vendor/triangle/console/src/Commands/' . $cmd_file;
        }, $exclude_command_files);
        $exclude_files = array_unique(array_merge($exclude_command_files, $exclude_files));
        foreach ($exclude_files as $file) {
            if ($phar->offsetExists($file)) {
                $phar->delete($file);
            }
        }

        $output->writeln('Сбор файлов завершен, начинаю добавлять файлы в PHAR.');

        $phar->setStub("#!/usr/bin/env php
<?php
define('IN_PHAR', true);
Phar::mapPhar('triangle');
require 'phar://triangle/master';
__HALT_COMPILER();
");

        $output->writeln('Запись файлов в PHAR архив и сохранение изменений.');

        $phar->stopBuffering();
        unset($phar);
        return self::SUCCESS;
    }

    /**
     * @throws RuntimeException
     */
    public function checkEnv(): void
    {
        if (!class_exists(Phar::class, false)) {
            throw new RuntimeException("Для сборки пакета требуется расширение PHAR");
        }

        if (ini_get('phar.readonly')) {
            $command = static::$defaultName;
            throw new RuntimeException(
                "В конфигурации php включен параметр 'phar.readonly'! Для сборки отключите его или повторите команду с флагом: 'php -d phar.readonly=0 master $command'"
            );
        }
    }

}