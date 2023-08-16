<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class ListBlockRenderer implements NodeRendererInterface, ConfigurationAwareInterface
{
    use AttributesTrait;

    private ConfigurationInterface $config;

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }

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
                    return sprintf('%s %s',
                        $this->config->get('joli_markdown/unordered_list_marker'),
                        mb_substr($item, 17),
                    );
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
