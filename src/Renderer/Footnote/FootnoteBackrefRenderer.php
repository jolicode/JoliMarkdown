<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Footnote;

use League\CommonMark\Extension\Footnote\Node\FootnoteBackref;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FootnoteBackrefRenderer implements NodeRendererInterface
{
    /**
     * @param FootnoteBackref $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        FootnoteBackref::assertInstanceOf($node);

        return "[^{$node->getReference()->getLabel()}]: ";
    }
}
