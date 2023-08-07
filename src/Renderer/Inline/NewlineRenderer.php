<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Inline;

use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class NewlineRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }

    /**
     * @param Newline $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Newline::assertInstanceOf($node);

        if (Newline::HARDBREAK === $node->getType()) {
            return "\n";
        }

        return $this->config->get('renderer/soft_break');
    }
}
