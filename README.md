# Triangle Console

Triangle Console adds Triangle Engine integration and project commands to [Localzet Console](https://github.com/localzet/Console). It loads commands from the application and enabled plugins, and includes helpers for creating applications, plugins, commands, controllers, middleware and models. It also provides PHAR and self-contained binary build commands.

> This compatibility line requires PHP 8.4.1 or newer, Triangle Engine 9, and Localzet Console 2.2. It is not compatible with the Engine 8 / Localzet Console 1 dependency line.

## Install

```sh
composer require triangle/console
```

The package uses the Triangle Engine runtime. Install it in a Triangle application, then register `Triangle\Console\Console` as the command application:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Triangle\Console\Console;

(new Console())->run();
```

When Engine configuration has been loaded, the component reads `config/command.php`, discovers application commands under `app/command`, and reads command entries from enabled plugin configuration. A command listed in `config/command.php` must expose a Symfony-compatible `defaultName`:

```php
<?php

return [
    App\Command\HealthCommand::class,
];
```

Commands extend `localzet\Console\Commands\Command` and use Symfony Console input/output contracts:

```php
<?php

namespace App\Command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HealthCommand extends Command
{
    protected static string $defaultName = 'app:health';
    protected static string $defaultDescription = 'Check application health';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('OK');

        return self::SUCCESS;
    }
}
```

## Laravel Console commands

Localzet Console 2.2 supports Illuminate Console commands. Triangle Console exposes that integration through the inherited `useIlluminate()` and `addIlluminateCommand()` methods:

```php
use Illuminate\Container\Container;
use Triangle\Console\Console;

$console = (new Console([], false))->useIlluminate(new Container());
$console->addIlluminateCommand(new App\Console\Commands\ReportCommand());
$console->run();
```

Pass a Laravel application/container configured for your command dependencies when required. `false` disables automatic internal commands for this explicitly composed console instance.

## Built-in commands

The component registers its commands along with Localzet Console's internal commands. Common Triangle commands include:

| Command | Purpose |
| --- | --- |
| `make:command <name>` | Generate an application command |
| `make:controller <name>` | Generate a controller |
| `make:middleware <name>` | Generate middleware |
| `make:model <name>` | Generate a model |
| `app:create <name>` | Create an application scaffold |
| `plugin:create <name>` | Create a plugin scaffold |
| `build:phar` | Package the configured project as a PHAR |
| `build:bin [php-version]` | Package the project with the configured PHP micro runtime |

Run `php your-entrypoint.php list` to see the commands available in the current application. Build commands use settings from `config/console.php`; the binary PHP version is a major/minor value such as `8.4`. Binary packaging does not make PHP source impossible to extract or reverse engineer; protect secrets outside the package and review the license terms for distributed artifacts.

## Compatibility and upgrade

This release line targets PHP `^8.4.1`, Triangle Engine `^9.0`, Localzet Console `^2.2`, Symfony Console `^8.1`, and Symfony Process `^8.1`. Engine 9 plugin discovery and Localzet Console 2 command integration are exercised by the package test suite.

Projects on the previous dependency line must upgrade PHP and Engine together, then resolve the new locked dependencies:

```sh
composer require triangle/engine:^9 localzet/console:^2.2 triangle/console --with-all-dependencies
```

Review application commands and plugins after the Engine 9 migration. Do not combine this package line with Engine 8 or Localzet Console 1 in the same Composer dependency graph.

## Development

```sh
composer install
composer run check
composer audit --locked
```

`composer run check` runs PHP syntax validation and the compatibility smoke tests for Engine 9 command discovery, built-in command registration, Symfony command execution, and Illuminate command execution.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).

---

Русская версия: [README.ru.md](README.ru.md).
