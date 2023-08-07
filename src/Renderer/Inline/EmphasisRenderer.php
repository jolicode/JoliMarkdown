<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Inline;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class EmphasisRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    use AttributesTrait;

    private ConfigurationInterface $config;

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }

    /**
     * @param Emphasis $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Emphasis::assertInstanceOf($node);

        $content = $childRenderer->renderNodes($node->children());
        $delimiter = $this->config->get('commonmark/use_asterisk') ? '*' : '_';

        return $this->addAttributes($node, $delimiter . $content . $delimiter);
    }
}
