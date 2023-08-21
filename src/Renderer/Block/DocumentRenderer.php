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

namespace JoliMarkdown\Renderer\Block;

use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class DocumentRenderer implements NodeRendererInterface
{
    /**
     * @param Document $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): ?string
    {
        Document::assertInstanceOf($node);

        $rendered = $childRenderer->renderNodes($node->children());
        $rendered = (string) preg_replace("/\n\n\n+/", "\n\n", $rendered);

        return (string) preg_replace("/(\n\n+$)/", "\n", $rendered);
    }
}
