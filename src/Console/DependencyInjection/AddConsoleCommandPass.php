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

namespace Triangle\Console\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Triangle\Console\Command\Command;
use Triangle\Console\Command\LazyCommand;
use Triangle\Console\CommandLoader\ContainerCommandLoader;
use function func_num_args;

/**
 * Registers console commands.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class AddConsoleCommandPass implements CompilerPassInterface
{
    private $commandLoaderServiceId;
    private $commandTag;
    private $noPreloadTag;
    private $privateTagName;

    public function __construct(string $commandLoaderServiceId = 'console.command_loader', string $commandTag = 'console.command', string $noPreloadTag = 'container.no_preload', string $privateTagName = 'container.private')
    {
        if (0 < func_num_args()) {
            trigger_deprecation('symfony/console', '5.3', 'Configuring "%s" is deprecated.', __CLASS__);
        }

        $this->commandLoaderServiceId = $commandLoaderServiceId;
        $this->commandTag = $commandTag;
        $this->noPreloadTag = $noPreloadTag;
        $this->privateTagName = $privateTagName;
    }

    public function process(ContainerBuilder $container)
    {
        $commandServices = $container->findTaggedServiceIds($this->commandTag, true);
        $lazyCommandMap = [];
        $lazyCommandRefs = [];
        $serviceIds = [];

        foreach ($commandServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->addTag($this->noPreloadTag);
            $class = $container->getParameterBag()->resolveValue($definition->getClass());

            if (isset($tags[0]['command'])) {
                $aliases = $tags[0]['command'];
            } else {
                if (!$r = $container->getReflectionClass($class)) {
                    throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $id));
                }
                if (!$r->isSubclassOf(Command::class)) {
                    throw new InvalidArgumentException(sprintf('The service "%s" tagged "%s" must be a subclass of "%s".', $id, $this->commandTag, Command::class));
                }
                $aliases = str_replace('%', '%%', $class::getDefaultName() ?? '');
            }

            $aliases = explode('|', $aliases ?? '');
            $commandName = array_shift($aliases);

            if ($isHidden = '' === $commandName) {
                $commandName = array_shift($aliases);
            }

            if (null === $commandName) {
                if (!$definition->isPublic() || $definition->isPrivate() || $definition->hasTag($this->privateTagName)) {
                    $commandId = 'console.command.public_alias.' . $id;
                    $container->setAlias($commandId, $id)->setPublic(true);
                    $id = $commandId;
                }
                $serviceIds[] = $id;

                continue;
            }

            $description = $tags[0]['description'] ?? null;

            unset($tags[0]);
            $lazyCommandMap[$commandName] = $id;
            $lazyCommandRefs[$id] = new TypedReference($id, $class);

            foreach ($aliases as $alias) {
                $lazyCommandMap[$alias] = $id;
            }

            foreach ($tags as $tag) {
                if (isset($tag['command'])) {
                    $aliases[] = $tag['command'];
                    $lazyCommandMap[$tag['command']] = $id;
                }

                $description = $description ?? $tag['description'] ?? null;
            }

            $definition->addMethodCall('setName', [$commandName]);

            if ($aliases) {
                $definition->addMethodCall('setAliases', [$aliases]);
            }

            if ($isHidden) {
                $definition->addMethodCall('setHidden', [true]);
            }

            if (!$description) {
                if (!$r = $container->getReflectionClass($class)) {
                    throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $id));
                }
                if (!$r->isSubclassOf(Command::class)) {
                    throw new InvalidArgumentException(sprintf('The service "%s" tagged "%s" must be a subclass of "%s".', $id, $this->commandTag, Command::class));
                }
                $description = str_replace('%', '%%', $class::getDefaultDescription() ?? '');
            }

            if ($description) {
                $definition->addMethodCall('setDescription', [$description]);

                $container->register('.' . $id . '.lazy', LazyCommand::class)
                    ->setArguments([$commandName, $aliases, $description, $isHidden, new ServiceClosureArgument($lazyCommandRefs[$id])]);

                $lazyCommandRefs[$id] = new Reference('.' . $id . '.lazy');
            }
        }

        $container
            ->register($this->commandLoaderServiceId, ContainerCommandLoader::class)
            ->setPublic(true)
            ->addTag($this->noPreloadTag)
            ->setArguments([ServiceLocatorTagPass::register($container, $lazyCommandRefs), $lazyCommandMap]);

        $container->setParameter('console.command.ids', $serviceIds);
    }
}
