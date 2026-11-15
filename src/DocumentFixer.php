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
     * @var array<int, FixerInterface>
     */
    private readonly array $fixers;

    /**
     * The positions, in the pipeline, of the fixers already applied to each
     * node of the document being fixed.
     *
     * @var \SplObjectStorage<Node, array<int, true>>
     */
    private \SplObjectStorage $appliedFixers;

    /**
     * @param iterable<FixerInterface> $fixers
     */
    public function __construct(
        iterable $fixers,
    ) {
        $this->fixers = \is_array($fixers) ? array_values($fixers) : iterator_to_array($fixers, false);
        $this->appliedFixers = new \SplObjectStorage();
    }

    public function fix(Document $document): Document
    {
        $this->appliedFixers = new \SplObjectStorage();

        try {
            foreach ($document->children() as $child) {
                $this->fixNode($child, $this->fixers);
            }
        } finally {
            $this->appliedFixers = new \SplObjectStorage();
        }

        return $document;
    }

    /**
     * @param array<int, FixerInterface> $fixers the fixers to apply, indexed by their position in the pipeline
     */
    private function fixNode(Node $node, array $fixers): void
    {
        foreach ($node->children() as $child) {
            if (null !== $child->parent()) {
                $this->fixNode($child, $fixers);
            }
        }

        foreach ($fixers as $position => $fixer) {
            if (null === $node->parent() || !$fixer->supports($node) || $this->isApplied($node, $position)) {
                continue;
            }

            $this->markApplied($node, $position);
            $replacements = $fixer->fix($node);

            if (null === $replacements) {
                continue;
            }

            // The nodes a fixer builds to replace the node (the markdown nodes
            // an HTML block becomes, for instance) have not been fixed yet: they
            // go through the fixers that follow in the pipeline. A node that a
            // fixer returns unchanged, or moves into these nodes, is not fixed
            // twice by the same fixer.
            $followingFixers = array_filter(
                $fixers,
                static fn (int $followingPosition): bool => $followingPosition > $position,
                \ARRAY_FILTER_USE_KEY,
            );

            foreach ($replacements as $replacement) {
                if ($replacement !== $node && null !== $replacement->parent()) {
                    $this->fixNode($replacement, $followingFixers);
                }
            }
        }
    }

    private function isApplied(Node $node, int $position): bool
    {
        return $this->appliedFixers->contains($node) && isset($this->appliedFixers[$node][$position]);
    }

    private function markApplied(Node $node, int $position): void
    {
        $applied = $this->appliedFixers->contains($node) ? $this->appliedFixers[$node] : [];
        $applied[$position] = true;
        $this->appliedFixers[$node] = $applied;
    }
}
