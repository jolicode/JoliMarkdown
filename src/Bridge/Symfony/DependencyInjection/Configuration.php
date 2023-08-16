<?php

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
                ->end()
                ->scalarNode('unordered_list_marker')
                    ->defaultValue('-')
                ->end()
                ->arrayNode('internal_domains')
                    ->scalarPrototype()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
