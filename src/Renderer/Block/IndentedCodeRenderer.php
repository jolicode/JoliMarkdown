<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class IndentedCodeRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param IndentedCode $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        IndentedCode::assertInstanceOf($node);

        $content = $node->getLiteral();

        return $this->addAttributes($node, <<<TXT
            ```
            {$content}```

            TXT);
    }
}
