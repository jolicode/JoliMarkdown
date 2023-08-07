<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FencedCodeRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param FencedCode $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        FencedCode::assertInstanceOf($node);

        $attrs = $node->data->getData('attributes');

        $infoWords = $node->getInfoWords();
        $language = null;
        if (0 !== \count($infoWords) && '' !== $infoWords[0]) {
            $attrs->append('class', 'language-' . $infoWords[0]);
            $language = $infoWords[0];
        }

        $content = $node->getLiteral();

        if (!str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        return $this->addAttributes($node, <<<TXT
            ```{$language}
            {$content}```

            TXT);
    }
}
