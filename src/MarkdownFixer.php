<?php

namespace JoliMarkdown;

use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Node;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MarkdownFixer
{
    private readonly iterable $fixers;

    public function __construct()
    {
        $container = new ContainerBuilder();
        $this->fixers = $container->findTaggedServiceIds('app.markdown.fixer');
    }

    public function fix(Document $document): Document
    {
        foreach ($document->children() as $child) {
            $this->fixNode($child);
        }

        return $document;
    }

    public function fixNode(Node $node, int $indentLevel = 0): void
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
