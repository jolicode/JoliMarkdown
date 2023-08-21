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

use League\CommonMark\Extension\Footnote\Node\FootnoteBackref;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class FootnoteBackrefRenderer implements NodeRendererInterface
{
    /**
     * @param FootnoteBackref $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        FootnoteBackref::assertInstanceOf($node);

        return "[^{$node->getReference()->getLabel()}]: ";
    }
}
