<?php

namespace JoliMarkdown;

use JoliMarkdown\Fixer\FencedCodeFixer;
use JoliMarkdown\Fixer\HtmlBlockFixer;
use JoliMarkdown\Fixer\HtmlInlineFixer;
use JoliMarkdown\Fixer\ImageFixer;
use JoliMarkdown\Fixer\LinkFixer;
use JoliMarkdown\Fixer\TextFixer;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Node;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

class MarkdownFixer
{
    private readonly iterable $fixers;

    public function __construct()
    {
        $htmlConverter = new HtmlConverter();
        $htmlConverter->getEnvironment()->addConverter(new TableConverter());
        $logger = new ConsoleLogger(new ConsoleOutput());
        $this->fixers = [
            new FencedCodeFixer($logger),
            new HtmlBlockFixer($logger, $htmlConverter),
            new HtmlInlineFixer($logger, $htmlConverter),
            new ImageFixer($logger),
            new LinkFixer($logger),
            new TextFixer($logger),
        ];
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
