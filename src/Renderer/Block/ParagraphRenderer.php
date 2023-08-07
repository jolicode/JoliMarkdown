<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Block\TightBlockInterface;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class ParagraphRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param Paragraph $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Paragraph::assertInstanceOf($node);

        if ($this->inTightList($node)) {
            return $childRenderer->renderNodes($node->children());
        }

        return $this->addAttributes($node, $childRenderer->renderNodes($node->children()) . "\n");
    }

    private function inTightList(Paragraph $node): bool
    {
        // Only check up to two (2) levels above this for tightness
        $i = 2;
        while (($node = $node->parent()) && $i--) {
            if ($node instanceof TightBlockInterface) {
                return $node->isTight();
            }
        }

        return false;
    }
}
