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

namespace JoliMarkdown\Renderer\Inline;

use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class TextRenderer implements NodeRendererInterface
{
    /**
     * @param Text $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Text::assertInstanceOf($node);

        if (preg_match('/^\h+$/u', $node->getLiteral(), $matches)) {
            return ' ';
        }

        $literal = (string) preg_replace('/(?:^[\s\n]+)/u', ' ', $node->getLiteral());
        $literal = (string) preg_replace('/(?:[\s\n]+$)/u', ' ', $literal);

        return ' ' !== $literal && null !== $literal ? $literal : '';
    }
}
