<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Footnote;

use League\CommonMark\Extension\Footnote\Node\FootnoteContainer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FootnoteContainerRenderer implements NodeRendererInterface
{
    /**
     * @param FootnoteContainer $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        FootnoteContainer::assertInstanceOf($node);

        $content = $childRenderer->renderNodes($node->children());

        return $content . "\n";
    }
}
