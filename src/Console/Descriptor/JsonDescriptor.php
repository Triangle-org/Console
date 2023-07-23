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
use Triangle\Console\Input\InputArgument;
use Triangle\Console\Input\InputDefinition;
use Triangle\Console\Input\InputOption;
use const INF;

/**
 * JSON descriptor.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 *
 * @internal
 */
class JsonDescriptor extends Descriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeInputArgument(InputArgument $argument, array $options = [])
    {
        $this->writeData($this->getInputArgumentData($argument), $options);
    }

    /**
     * Writes data as json.
     */
    private function writeData(array $data, array $options)
    {
        $flags = $options['json_encoding'] ?? 0;

        $this->write(json_encode($data, $flags));
    }

    private function getInputArgumentData(InputArgument $argument): array
    {
        return [
            'name' => $argument->getName(),
            'is_required' => $argument->isRequired(),
            'is_array' => $argument->isArray(),
            'description' => preg_replace('/\s*[\r\n]\s*/', ' ', $argument->getDescription()),
            'default' => INF === $argument->getDefault() ? 'INF' : $argument->getDefault(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputOption(InputOption $option, array $options = [])
    {
        $this->writeData($this->getInputOptionData($option), $options);
        if ($option->isNegatable()) {
            $this->writeData($this->getInputOptionData($option, true), $options);
        }
    }

    private function getInputOptionData(InputOption $option, bool $negated = false): array
    {
        return $negated ? [
            'name' => '--no-' . $option->getName(),
            'shortcut' => '',
            'accept_value' => false,
            'is_value_required' => false,
            'is_multiple' => false,
            'description' => 'Negate the "--' . $option->getName() . '" option',
            'default' => false,
        ] : [
            'name' => '--' . $option->getName(),
            'shortcut' => $option->getShortcut() ? '-' . str_replace('|', '|-', $option->getShortcut()) : '',
            'accept_value' => $option->acceptValue(),
            'is_value_required' => $option->isValueRequired(),
            'is_multiple' => $option->isArray(),
            'description' => preg_replace('/\s*[\r\n]\s*/', ' ', $option->getDescription()),
            'default' => INF === $option->getDefault() ? 'INF' : $option->getDefault(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputDefinition(InputDefinition $definition, array $options = [])
    {
        $this->writeData($this->getInputDefinitionData($definition), $options);
    }

    private function getInputDefinitionData(InputDefinition $definition): array
    {
        $inputArguments = [];
        foreach ($definition->getArguments() as $name => $argument) {
            $inputArguments[$name] = $this->getInputArgumentData($argument);
        }

        $inputOptions = [];
        foreach ($definition->getOptions() as $name => $option) {
            $inputOptions[$name] = $this->getInputOptionData($option);
            if ($option->isNegatable()) {
                $inputOptions['no-' . $name] = $this->getInputOptionData($option, true);
            }
        }

        return ['arguments' => $inputArguments, 'options' => $inputOptions];
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCommand(Command $command, array $options = [])
    {
        $this->writeData($this->getCommandData($command, $options['short'] ?? false), $options);
    }

    private function getCommandData(Command $command, bool $short = false): array
    {
        $data = [
            'name' => $command->getName(),
            'description' => $command->getDescription(),
        ];

        if ($short) {
            $data += [
                'usage' => $command->getAliases(),
            ];
        } else {
            $command->mergeApplicationDefinition(false);

            $data += [
                'usage' => array_merge([$command->getSynopsis()], $command->getUsages(), $command->getAliases()),
                'help' => $command->getProcessedHelp(),
                'definition' => $this->getInputDefinitionData($command->getDefinition()),
            ];
        }

        $data['hidden'] = $command->isHidden();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function describeApplication(Application $application, array $options = [])
    {
        $describedNamespace = $options['namespace'] ?? null;
        $description = new ApplicationDescription($application, $describedNamespace, true);
        $commands = [];

        foreach ($description->getCommands() as $command) {
            $commands[] = $this->getCommandData($command, $options['short'] ?? false);
        }

        $data = [];
        if ('UNKNOWN' !== $application->getName()) {
            $data['application']['name'] = $application->getName();
            if ('UNKNOWN' !== $application->getVersion()) {
                $data['application']['version'] = $application->getVersion();
            }
        }

        $data['commands'] = $commands;

        if ($describedNamespace) {
            $data['namespace'] = $describedNamespace;
        } else {
            $data['namespaces'] = array_values($description->getNamespaces());
        }

        $this->writeData($data, $options);
    }
}
