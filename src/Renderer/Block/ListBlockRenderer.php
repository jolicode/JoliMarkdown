<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;

final class ListBlockRenderer implements \League\CommonMark\Renderer\NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param ListBlock $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        ListBlock::assertInstanceOf($node);

        $listData = $node->getListData();
        $content = $childRenderer->renderNodes($node->children());
        $content = array_filter(explode("\n", $content));

        if (ListBlock::TYPE_BULLET === $listData->type) {
            $content = array_map(function (string $item): string {
                if (preg_match('/^LIST_BULLET_POINT/', $item)) {
                    // this is an item
                    return '- ' . mb_substr($item, 17);
                }

                return "  {$item}";
            }, $content);
        }

        if (ListBlock::TYPE_ORDERED === $listData->type) {
            $key = 0;

            $content = array_map(function (string $item) use (&$key): string {
                if (preg_match('/^LIST_BULLET_POINT/', $item)) {
                    // this is an item
                    ++$key;

                    return "{$key}. " . mb_substr($item, 17);
                }

                return "    {$item}";
            }, $content);
        }

        return $this->addAttributes($node, implode("\n", $content)) . "\n";
    }
}
