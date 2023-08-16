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

use JoliMarkdown\Node\Inline\CommonmarkContainer;
use JoliMarkdown\Renderer\AttributesTrait;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class CommonmarkContainerRenderer implements NodeRendererInterface
{
    use AttributesTrait;

    /**
     * @param CommonmarkContainer $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        CommonmarkContainer::assertInstanceOf($node);

        return $this->addAttributes($node, $node->getLiteral());
    }
}
