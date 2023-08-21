<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliMarkdown;

use JoliMarkdown\Fixer\FixerInterface;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Node;

class DocumentFixer
{
    /**
     * @param iterable<FixerInterface> $fixers
     */
    public function __construct(
        private readonly iterable $fixers,
    ) {
    }

    public function fix(Document $document): Document
    {
        foreach ($document->children() as $child) {
            $this->fixNode($child);
        }

        return $document;
    }

    private function fixNode(Node $node, int $indentLevel = 0): void
    {
        foreach ($node->children() as $child) {
            if (null !== $child->parent()) {
                $this->fixNode($child, $indentLevel + 1);
            }
        }

        foreach ($this->fixers as $fixer) {
            if (null !== $node->parent() && $fixer->supports($node)) {
                $fixer->fix($node);
            }
        }
    }
}
