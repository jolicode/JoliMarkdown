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

use JoliMarkdown\Bridge\Symfony\Validator\MarkdownValidator;
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

        $fixerDefinition = new Definition(MarkdownFixer::class);
        $fixerDefinition->setArgument('$environment', $environmentDefinition);
        $container->setDefinition('joli_markdown.fixer', $fixerDefinition);

        $validatorDefinition = new Definition(MarkdownValidator::class);
        $validatorDefinition->addTag('validator.constraint_validator');
        $validatorDefinition->setAutowired(true);
        $container->setDefinition('joli_markdown.validator', $validatorDefinition);
    }
}
