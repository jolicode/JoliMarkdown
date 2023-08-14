<?php

declare(strict_types=1);

namespace JoliMarkdown\Tests;

use JoliMarkdown\MarkdownConverter;
use JoliMarkdown\MarkdownFixer;
use JoliMarkdown\MarkdownRendererExtension;
use JoliMarkdown\Renderer\MarkdownRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;
use League\CommonMark\Util\HtmlFilter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MarkdownParsingTest extends TestCase
{
    private $mdConverter;
    private $markdownParser;
    private $markdownFixer;
    private $markdownRenderer;

    protected function setUp(): void
    {
        $environment = new Environment([
            'html_input' => HtmlFilter::ALLOW,
            'allow_unsafe_links' => true,
            'max_nesting_level' => 1000,
            'renderer' => [
                'block_separator' => "\n",
                'inner_separator' => " ",
                'soft_break' => "\n",
            ],
        ]);
        $environment->addExtension(new MarkdownRendererExtension());
        $environment->addExtension(new FootnoteExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new DefaultAttributesExtension());
        $environment->addExtension(new AttributesExtension());

        $this->markdownParser = new MarkdownParser($environment);
        $this->markdownRenderer = new MarkdownRenderer($environment);

        $environment = new Environment([
            'html_input' => HtmlFilter::ALLOW,
            'allow_unsafe_links' => true,
            'max_nesting_level' => 1000,
            'renderer' => [
                'block_separator' => "\n",
                'inner_separator' => "\n",
                'soft_break' => "\n",
            ],
            'external_link' => [
                'internal_hosts' => ['jolicode.com', 'preprod.jolicode.com', 'local.jolicode.com', 'jolicode.ch', 'jolicampus.com'],
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
            'default_attributes' => [
                Image::class  => [
                    'loading' => 'lazy',
                    'decoding' => 'async',
                ],
            ],
        ]);
        $environment->addExtension(new FootnoteExtension());
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new DefaultAttributesExtension());
        $environment->addExtension(new AttributesExtension());

        $htmlRenderer = new HtmlRenderer($environment);
        $this->mdConverter = new MarkdownConverter(
            $this->markdownParser,
            $htmlRenderer
        );

        $this->markdownFixer = new MarkdownFixer();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testFixer(string $inputMarkdown, string $expectedMarkdown, string $convertedhtml, string $testName): void
    {
        $this->assertFixMarkdown($inputMarkdown, $expectedMarkdown, $testName);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testRenderer(string $inputMarkdown, string $expectedMarkdown, string $expectedhtml, string $testName): void
    {
        $this->assertConvertToHtml($expectedMarkdown, $expectedhtml, $testName);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        $ret = [];

        foreach (glob(__DIR__ . '/data/*-in.md') as $markdownFile) {
            $testName = basename($markdownFile, '-in.md');
            $inputMarkdown = file_get_contents($markdownFile);
            $expectedMarkdown = file_get_contents(__DIR__ . '/data/' . $testName . '-out.md');
            $expectedhtml = file_get_contents(__DIR__ . '/data/' . $testName . '-out.html');

            yield [$inputMarkdown, $expectedMarkdown, $expectedhtml, $testName];
        }

        return $ret;
    }

    protected function assertFixMarkdown(string $inputMarkdown, string $expectedMarkdown, string $testName): void
    {
        $document = $this->markdownParser->parse($inputMarkdown);
        $document = $this->markdownFixer->fix($document);
        $fixedMarkdown = $this->markdownRenderer->renderDocument($document)->getContent();

        static::assertSame($expectedMarkdown, $fixedMarkdown, sprintf('Unexpected result for "%s" test', $testName));

        $lastMarkdown = $fixedMarkdown;
        $i = 0;

        while ($i < 3) {
            $previousMarkdown = $lastMarkdown;
            $document = $this->markdownParser->parse($previousMarkdown);
            $document = $this->markdownFixer->fix($document);
            $lastMarkdown = $this->markdownRenderer->renderDocument($document)->getContent();

            ++$i;
        }
        static::assertSame($lastMarkdown, $previousMarkdown, sprintf('Unexpected result for "%s" test', $testName));
    }

    protected function assertConvertToHtml(string $markdown, string $expectedHtml, string $testName): void
    {
        $actualHtml = $this->mdConverter->mdToHtml($markdown);

        $failureMessage = sprintf('Unexpected result for "%s" test', $testName);
        $failureMessage .= "\n=== markdown ===============\n" . $markdown;
        $failureMessage .= "\n=== expected ===============\n" . $expectedHtml . '(strlen=' . mb_strlen($expectedHtml) . ')';
        $failureMessage .= "\n=== got ====================\n" . $actualHtml . '(strlen=' . mb_strlen($actualHtml) . ')';

        static::assertSame($expectedHtml, $actualHtml, $failureMessage);
    }
}
