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

namespace JoliMarkdown\Renderer\Block;

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class ThematicBreakRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param ThematicBreak $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        ThematicBreak::assertInstanceOf($node);

        return $this->addAttributes($node, "\n---\n");
    }
}
