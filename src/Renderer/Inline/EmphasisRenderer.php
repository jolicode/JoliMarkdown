<?php

declare(strict_types=1);

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $delimiter = $this->config->get('joli_markdown/prefer_asterisk_over_underscore') ? '*' : '_';

        return $this->addAttributes($node, $delimiter . $content . $delimiter);
    }
}
