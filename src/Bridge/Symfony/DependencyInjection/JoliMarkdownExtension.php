<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliMarkdown\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class JoliMarkdownExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__) . '/Resources/config'));
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->registerJoliMarkdowConfiguration($config, $container, $loader);
    }

    /**
     * @param array<mixed, mixed> $config
     */
    private function registerJoliMarkdowConfiguration(array $config, ContainerBuilder $container, PhpFileLoader $loader): void
    {
        $loader->load('joli_markdown.php');

        $environmentDefinition = $container->getDefinition('joli_markdown.environment');
        $environmentDefinition->replaceArgument(0, [
            'joli_markdown' => $config,
        ]);
    }
}
