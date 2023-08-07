<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer\Inline;

use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class TaskListItemMarkerRenderer implements NodeRendererInterface
{
    /**
     * @param TaskListItemMarker $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        return sprintf('[%s]', $node->isChecked() ? 'x' : ' ');
    }
}
