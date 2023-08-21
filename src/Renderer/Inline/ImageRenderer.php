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
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\RegexHelper;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class ImageRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    use AttributesTrait;

    private ConfigurationInterface $config;

    /**
     * @param Image $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Image::assertInstanceOf($node);

        $attrs = $node->data->get('attributes');

        $forbidUnsafeLinks = !$this->config->get('allow_unsafe_links');
        if ($forbidUnsafeLinks && RegexHelper::isLinkPotentiallyUnsafe($node->getUrl())) {
            $attrs['src'] = '';
        } else {
            $attrs['src'] = $node->getUrl();
        }

        $alt = $childRenderer->renderNodes($node->children());
        $alt = preg_replace('/\<[^>]*alt="([^"]*)"[^>]*\>/', '$1', $alt);
        $attrs['alt'] = preg_replace('/\<[^>]*\>/', '', $alt ?? '');

        if (($title = $node->getTitle()) !== null) {
            $attrs['title'] = $title;

            return $this->addAttributes($node, "![{$attrs['alt']}]({$attrs['src']} \"{$title}\")");
        }

        return $this->addAttributes($node, "![{$attrs['alt']}]({$attrs['src']})");
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
