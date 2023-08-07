<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class HeadingRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param Heading $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Heading::assertInstanceOf($node);

        $level = str_repeat('#', $node->getLevel());

        $content = $childRenderer->renderNodes($node->children());

        return $this->addAttributes($node, "$level $content\n");
    }
}
