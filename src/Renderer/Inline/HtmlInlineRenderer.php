<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Inline;

use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlFilter;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class HtmlInlineRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    /**
     * @param HtmlInline $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        HtmlInline::assertInstanceOf($node);

        $htmlInput = $this->config->get('html_input');

        return HtmlFilter::filter($node->getLiteral(), $htmlInput);
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
