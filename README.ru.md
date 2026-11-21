# Triangle Console

Triangle Console связывает [Localzet Console](https://github.com/localzet/Console) с Triangle Engine и добавляет команды для работы с проектом. Компонент загружает команды приложения и включённых плагинов, создаёт заготовки приложений, плагинов, команд, контроллеров, middleware и моделей, а также собирает PHAR и автономные бинарные пакеты.

> Эта линия совместимости требует PHP 8.4.1 или новее, Triangle Engine 9 и Localzet Console 2.2. Она несовместима с зависимостями линии Engine 8 / Localzet Console 1.

## Установка

```sh
composer require triangle/console:^2.0
```

Компонент работает в приложении на Triangle Engine. Подключите автозагрузчик Composer и запустите `Triangle\Console\Console`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Triangle\Console\Console;

(new Console())->run();
```

Если конфигурация Engine уже загружена, компонент читает `config/command.php`, ищет команды приложения в `app/command` и команды включённых плагинов в их конфигурации. Команда из `config/command.php` должна объявлять имя, совместимое с Symfony Console:

```php
<?php

return [
    App\Command\HealthCommand::class,
];
```

Команды наследуются от `localzet\Console\Commands\Command` и используют интерфейсы ввода и вывода Symfony Console:

```php
<?php

namespace App\Command;

use localzet\Console\Commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HealthCommand extends Command
{
    protected static string $defaultName = 'app:health';
    protected static string $defaultDescription = 'Проверить состояние приложения';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('OK');

        return self::SUCCESS;
    }
}
```

## Команды Laravel Console

Localzet Console 2.2 поддерживает команды Illuminate Console. Triangle Console предоставляет унаследованные методы `useIlluminate()` и `addIlluminateCommand()`:

```php
use Illuminate\Container\Container;
use Triangle\Console\Console;

$console = (new Console([], false))->useIlluminate(new Container());
$console->addIlluminateCommand(new App\Console\Commands\ReportCommand());
$console->run();
```

Если командам нужны зависимости приложения, передайте настроенный Laravel container/application. Параметр `false` отключает автоматическую регистрацию встроенных команд в этой вручную собранной консоли.

## Встроенные команды

Компонент регистрирует свои команды вместе со встроенными командами Localzet Console. Основные команды Triangle:

| Команда | Назначение |
| --- | --- |
| `make:command <name>` | Создать команду приложения |
| `make:controller <name>` | Создать контроллер |
| `make:middleware <name>` | Создать middleware |
| `make:model <name>` | Создать модель |
| `app:create <name>` | Создать структуру приложения |
| `plugin:create <name>` | Создать структуру плагина |
| `build:phar` | Упаковать проект в PHAR |
| `build:bin [php-version]` | Упаковать проект вместе с PHP micro runtime |

Список команд текущего приложения можно посмотреть через `php <точка-входа> list`. Параметры сборки читаются из `config/console.php`; версия PHP для бинарной сборки задаётся значением major/minor, например `8.4`. Бинарная упаковка не исключает извлечение или анализ PHP-кода. Не храните секреты в пакете и заранее проверьте условия лицензии для распространяемых артефактов.

## Совместимость и обновление

Эта линия требует PHP `^8.4.1`, Triangle Engine `^9.0`, Localzet Console `^2.2`, Symfony Console `^8.1` и Symfony Process `^8.1`. Набор проверок компонента проверяет обнаружение команд через Engine 9 и регистрацию команд Localzet Console 2.

Для проекта на предыдущей линии обновите PHP и Engine, затем разрешите новые версии зависимостей:

```sh
composer require triangle/engine:^9 localzet/console:^2.2 triangle/console --with-all-dependencies
```

После перехода на Engine 9 проверьте команды приложения и плагины. Не смешивайте эту линию с Engine 8 или Localzet Console 1 в одном графе зависимостей Composer.

## Разработка

```sh
composer install
composer run check
composer audit --locked
```

`composer run check` проверяет синтаксис PHP и запускает smoke-тесты обнаружения команд Engine 9, регистрации встроенных команд, исполнения Symfony-команды и исполнения Illuminate-команды.

## Лицензия

AGPL-3.0-or-later. См. [LICENSE](LICENSE).

---

English version: [README.md](README.md).
