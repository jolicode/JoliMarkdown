<?php

namespace JoliMarkdown;

use JoliMarkdown\Fixer\FencedCodeFixer;
use JoliMarkdown\Fixer\HtmlBlockFixer;
use JoliMarkdown\Fixer\HtmlInlineFixer;
use JoliMarkdown\Fixer\ImageFixer;
use JoliMarkdown\Fixer\LinkFixer;
use JoliMarkdown\Fixer\TextFixer;
use JoliMarkdown\Renderer\MarkdownRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Util\HtmlFilter;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\Environment as HTMLToMarkdownEnvironment;
use League\HTMLToMarkdown\HtmlConverter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MarkdownFixer
{
    private readonly MarkdownParser $markdownParser;
    private readonly DocumentFixer $documentFixer;
    private readonly MarkdownRenderer $markdownRenderer;

    public function __construct(
        LoggerInterface $logger = null,
        Environment $environment = null,
        string $internalDomainsPattern = null,
    ) {
        if (null === $environment) {
            $environment = new Environment([
                'html_input' => HtmlFilter::ALLOW,
                'allow_unsafe_links' => true,
                'max_nesting_level' => 1000,
                'renderer' => [
                    'block_separator' => "\n",
                    'inner_separator' => ' ',
                    'soft_break' => "\n",
                ],
            ]);
            $environment->addExtension(new MarkdownRendererExtension());
            $environment->addExtension(new FootnoteExtension());
            $environment->addExtension(new StrikethroughExtension());
            $environment->addExtension(new DefaultAttributesExtension());
            $environment->addExtension(new AttributesExtension());
        }

        $this->markdownParser = new MarkdownParser($environment);
        $this->markdownRenderer = new MarkdownRenderer($environment);

        $htmlConverterEnvironment = new HTMLToMarkdownEnvironment();
        $htmlConverterEnvironment->addConverter(new TableConverter());
        $htmlConverter = new HtmlConverter($htmlConverterEnvironment);
        $logger ??= new NullLogger();
        $this->documentFixer = new DocumentFixer([
            new FencedCodeFixer($logger),
            new HtmlBlockFixer($logger, $htmlConverter),
            new HtmlInlineFixer($logger, $htmlConverter),
            new ImageFixer($logger, $internalDomainsPattern),
            new LinkFixer($logger, $internalDomainsPattern),
            new TextFixer($logger),
        ]);
    }

    public function fix(string $inputMarkdown): string
    {
        $document = $this->markdownParser->parse($inputMarkdown);
        $fixedDocument = $this->documentFixer->fix($document);

        return $this->markdownRenderer->renderDocument($fixedDocument)->getContent();
    }
}
