<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;

final class ListItemRenderer implements \League\CommonMark\Renderer\NodeRendererInterface
{
    /**
     * @param ListItem $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        ListItem::assertInstanceOf($node);

        $content = $childRenderer->renderNodes($node->children());

        $content = explode("\n", $content);
        $content = array_values(array_filter(array_map(function ($item) {
            if ('' === trim($item)) {
                return null;
            }

            return rtrim($item);
        }, $content)));

        if (\array_key_exists(0, $content)) {
            $content[0] = 'LIST_BULLET_POINT' . $content[0];
        }

        return implode("\n", $content);
    }
}
