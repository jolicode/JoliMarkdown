<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlFilter;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class HtmlBlockRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    /**
     * @param HtmlBlock $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        HtmlBlock::assertInstanceOf($node);
        $html = $node->getLiteral();

        if (HtmlBlock::TYPE_2_COMMENT === $node->getType()) {
            return "<!--{$html}-->\n";
        }

        $htmlInput = $this->config->get('html_input');

        return HtmlFilter::filter($html, $htmlInput) . "\n";
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
