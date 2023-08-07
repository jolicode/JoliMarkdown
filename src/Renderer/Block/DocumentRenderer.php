<?php

declare(strict_types=1);

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
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Document::assertInstanceOf($node);

        $rendered = $childRenderer->renderNodes($node->children());
        $rendered = preg_replace("/\n\n\n+/", "\n\n", $rendered);

        return preg_replace("/(\n\n+$)/", "\n", $rendered);
    }
}
