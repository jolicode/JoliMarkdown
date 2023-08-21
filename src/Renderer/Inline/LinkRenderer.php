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
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\RegexHelper;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class LinkRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    use AttributesTrait;

    private ConfigurationInterface $config;

    /**
     * @param Link $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Link::assertInstanceOf($node);

        $attrs = $node->data->get('attributes');
        $forbidUnsafeLinks = !$this->config->get('allow_unsafe_links');
        $alt = $childRenderer->renderNodes($node->children());

        if (!($forbidUnsafeLinks && RegexHelper::isLinkPotentiallyUnsafe($node->getUrl()))) {
            $attrs['href'] = $node->getUrl();
        }

        if (($title = $node->getTitle()) !== null) {
            $content = "[$alt]({$attrs['href']} \"{$title}\")";
        } else {
            $content = "[$alt]({$attrs['href']})";
        }

        return $this->addAttributes($node, $content);
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
