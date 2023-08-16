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

use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class CodeRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param Code $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Code::assertInstanceOf($node);

        $content = $node->getLiteral();

        return $this->addAttributes($node, "`$content`");
    }
}
