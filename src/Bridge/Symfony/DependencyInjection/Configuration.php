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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('joli_markdown');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('prefer_asterisk_over_underscore')
                    ->defaultTrue()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('unordered_list_marker')
                    ->defaultValue('-')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('internal_domains')
                    ->scalarPrototype()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
