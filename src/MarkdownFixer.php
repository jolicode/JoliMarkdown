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

use JoliMarkdown\Fixer\FencedCodeFixer;
use JoliMarkdown\Fixer\HtmlBlockFixer;
use JoliMarkdown\Fixer\HtmlInlineFixer;
use JoliMarkdown\Fixer\ImageFixer;
use JoliMarkdown\Fixer\LinkFixer;
use JoliMarkdown\Fixer\TextFixer;
use JoliMarkdown\Renderer\MarkdownRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Parser\MarkdownParser;
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
        Environment $environment = null,
        LoggerInterface $logger = null,
    ) {
        if (null === $environment) {
            $environment = new Environment();
        }

        $environment->addExtension(new MarkdownRendererExtension());

        $internalDomainsPattern = null;
        $internalDomains = $environment->getConfiguration()->get('joli_markdown/internal_domains');

        if (\is_array($internalDomains) && 0 !== \count($internalDomains)) {
            $internalDomainsPattern = sprintf(
                '#^(https?)?://(%s)/?#',
                implode('|', $internalDomains),
            );
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
