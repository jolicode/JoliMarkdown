<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Node\Block\CommonmarkContainer;
use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class CommonmarkContainerRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param CommonmarkContainer $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        CommonmarkContainer::assertInstanceOf($node);

        return $this->addAttributes($node, $node->getLiteral() . "\n");
    }
}
