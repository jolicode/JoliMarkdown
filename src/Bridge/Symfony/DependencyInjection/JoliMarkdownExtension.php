<?php

namespace JoliMarkdown\Bridge\Symfony\DependencyInjection;

use JoliMarkdown\MarkdownFixer;
use League\CommonMark\Environment\Environment;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class JoliMarkdownExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->createFixerDefinition($container, $config);
    }

    /**
     * @param array<mixed, mixed> $config
     */
    private function createFixerDefinition(ContainerBuilder $container, array $config): void
    {
        $environmentDefinition = new Definition(Environment::class);
        $environmentDefinition->addArgument([
            'joli_markdown' => $config,
        ]);

        $definition = new Definition(MarkdownFixer::class);
        $definition->setArgument('$environment', $environmentDefinition);
        $container->setDefinition('joli_markdown.fixer', $definition);
    }
}
