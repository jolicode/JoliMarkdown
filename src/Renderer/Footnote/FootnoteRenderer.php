<?php

declare(strict_types=1);

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliMarkdown\Renderer\Footnote;

use League\CommonMark\Extension\Footnote\Node\Footnote;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FootnoteRenderer implements NodeRendererInterface
{
    /**
     * @param Footnote $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): ?string
    {
        Footnote::assertInstanceOf($node);
        $children = (array) $node->children();

        if (0 === \count($children)) {
            return null;
        }

        $nodes = $children[0]->children();

        /* @phpstan-ignore-next-line */
        array_unshift($nodes, array_pop($nodes));

        return $childRenderer->renderNodes($nodes);
    }
}
