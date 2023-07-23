<?php

declare(strict_types=1);

/**
 * @package     Triangle Console Plugin
 * @link        https://github.com/Triangle-org/Console
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
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

namespace Triangle\Console\Descriptor;

use Triangle\Console\Application;
use Triangle\Console\Command\Command;
use Triangle\Console\Helper\Helper;
use Triangle\Console\Input\InputArgument;
use Triangle\Console\Input\InputDefinition;
use Triangle\Console\Input\InputOption;
use Triangle\Console\Output\OutputInterface;
use function count;

/**
 * Markdown descriptor.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 *
 * @internal
 */
class MarkdownDescriptor extends Descriptor
{
    /**
     * {@inheritdoc}
     */
    public function describe(OutputInterface $output, object $object, array $options = [])
    {
        $decorated = $output->isDecorated();
        $output->setDecorated(false);

        parent::describe($output, $object, $options);

        $output->setDecorated($decorated);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeApplication(Application $application, array $options = [])
    {
        $describedNamespace = $options['namespace'] ?? null;
        $description = new ApplicationDescription($application, $describedNamespace);
        $title = $this->getApplicationTitle($application);

        $this->write($title . "\n" . str_repeat('=', Helper::width($title)));

        foreach ($description->getNamespaces() as $namespace) {
            if (ApplicationDescription::GLOBAL_NAMESPACE !== $namespace['id']) {
                $this->write("\n\n");
                $this->write('**' . $namespace['id'] . ':**');
            }

            $this->write("\n\n");
            $this->write(implode("\n", array_map(function ($commandName) use ($description) {
                return sprintf('* [`%s`](#%s)', $commandName, str_replace(':', '', $description->getCommand($commandName)->getName()));
            }, $namespace['commands'])));
        }

        foreach ($description->getCommands() as $command) {
            $this->write("\n\n");
            if (null !== $describeCommand = $this->describeCommand($command, $options)) {
                $this->write((string)$describeCommand);
            }
        }
    }

    private function getApplicationTitle(Application $application): string
    {
        if ('UNKNOWN' !== $application->getName()) {
            if ('UNKNOWN' !== $application->getVersion()) {
                return sprintf('%s %s', $application->getName(), $application->getVersion());
            }

            return $application->getName();
        }

        return 'Инструмент консоли';
    }

    /**
     * {@inheritdoc}
     */
    protected function write(string $content, bool $decorated = true)
    {
        parent::write($content, $decorated);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCommand(Command $command, array $options = [])
    {
        if ($options['short'] ?? false) {
            $this->write(
                '`' . $command->getName() . "`\n"
                . str_repeat('-', Helper::width($command->getName()) + 2) . "\n\n"
                . ($command->getDescription() ? $command->getDescription() . "\n\n" : '')
                . '### Использование' . "\n\n"
                . array_reduce($command->getAliases(), function ($carry, $usage) {
                    return $carry . '* `' . $usage . '`' . "\n";
                })
            );

            return;
        }

        $command->mergeApplicationDefinition(false);

        $this->write(
            '`' . $command->getName() . "`\n"
            . str_repeat('-', Helper::width($command->getName()) + 2) . "\n\n"
            . ($command->getDescription() ? $command->getDescription() . "\n\n" : '')
            . '### Использование' . "\n\n"
            . array_reduce(array_merge([$command->getSynopsis()], $command->getAliases(), $command->getUsages()), function ($carry, $usage) {
                return $carry . '* `' . $usage . '`' . "\n";
            })
        );

        if ($help = $command->getProcessedHelp()) {
            $this->write("\n");
            $this->write($help);
        }

        $definition = $command->getDefinition();
        if ($definition->getOptions() || $definition->getArguments()) {
            $this->write("\n\n");
            $this->describeInputDefinition($definition);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputDefinition(InputDefinition $definition, array $options = [])
    {
        if ($showArguments = count($definition->getArguments()) > 0) {
            $this->write('### Аргументы');
            foreach ($definition->getArguments() as $argument) {
                $this->write("\n\n");
                if (null !== $describeInputArgument = $this->describeInputArgument($argument)) {
                    $this->write((string)$describeInputArgument);
                }
            }
        }

        if (count($definition->getOptions()) > 0) {
            if ($showArguments) {
                $this->write("\n\n");
            }

            $this->write('### Опции');
            foreach ($definition->getOptions() as $option) {
                $this->write("\n\n");
                if (null !== $describeInputOption = $this->describeInputOption($option)) {
                    $this->write((string)$describeInputOption);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputArgument(InputArgument $argument, array $options = [])
    {
        $this->write(
            '#### `' . ($argument->getName() ?: '<none>') . "`\n\n"
            . ($argument->getDescription() ? preg_replace('/\s*[\r\n]\s*/', "\n", $argument->getDescription()) . "\n\n" : '')
            . '* Обязателен: ' . ($argument->isRequired() ? 'Да' : 'Нет') . "\n"
            . '* Массив: ' . ($argument->isArray() ? 'Да' : 'Нет') . "\n"
            . '* По умолчанию: `' . str_replace("\n", '', var_export($argument->getDefault(), true)) . '`'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputOption(InputOption $option, array $options = [])
    {
        $name = '--' . $option->getName();
        if ($option->isNegatable()) {
            $name .= '|--no-' . $option->getName();
        }
        if ($option->getShortcut()) {
            $name .= '|-' . str_replace('|', '|-', $option->getShortcut());
        }

        $this->write(
            '#### `' . $name . '`' . "\n\n"
            . ($option->getDescription() ? preg_replace('/\s*[\r\n]\s*/', "\n", $option->getDescription()) . "\n\n" : '')
            . '* Принимает значения: ' . ($option->acceptValue() ? 'Да' : 'Нет') . "\n"
            . '* Значение обязательно: ' . ($option->isValueRequired() ? 'Да' : 'Нет') . "\n"
            . '* Несколько значений: ' . ($option->isArray() ? 'Да' : 'Нет') . "\n"
            . '* Is negatable: ' . ($option->isNegatable() ? 'Да' : 'Нет') . "\n"
            . '* По умолчанию: `' . str_replace("\n", '', var_export($option->getDefault(), true)) . '`'
        );
    }
}
