<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class BlockQuoteRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param BlockQuote $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        BlockQuote::assertInstanceOf($node);

        $content = trim($childRenderer->renderNodes($node->children()));
        $content = explode("\n", $content);

        return $this->addAttributes($node, implode("\n", array_map(fn (string $item): string => '> ' . rtrim($item), $content)) . "\n");
    }
}
