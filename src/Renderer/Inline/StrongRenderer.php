<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Inline;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class StrongRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    use AttributesTrait;

    private ConfigurationInterface $config;

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }

    /**
     * @param Strong $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Strong::assertInstanceOf($node);

        $content = $childRenderer->renderNodes($node->children());
        $delimiter = $this->config->get('joli_markdown/prefer_asterisk_over_underscore') ? '**' : '__';

        return $this->addAttributes($node, $delimiter . $content . $delimiter);
    }
}
