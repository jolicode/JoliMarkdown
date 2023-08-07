<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Footnote;

use League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FootnoteRefRenderer implements NodeRendererInterface
{
    /**
     * @param FootnoteRef $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        FootnoteRef::assertInstanceOf($node);

        return "[^{$node->getReference()->getLabel()}]";
    }
}
