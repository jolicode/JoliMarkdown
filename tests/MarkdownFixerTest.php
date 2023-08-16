<?php

declare(strict_types=1);

namespace JoliMarkdown\Tests;

use JoliMarkdown\MarkdownFixer;
use JoliMarkdown\MarkdownRendererExtension;
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
final class MarkdownFixerTest extends TestCase
{
    private MarkdownFixer $markdownFixer;
    private MarkdownParser $docParser;
    private HtmlRenderer $htmlRenderer;

    protected function setUp(): void
    {
        $internalDomains = [
            'internaldomain.com',
            'www.internaldomain.com',
        ];
        $this->markdownFixer = new MarkdownFixer(internalDomains: $internalDomains);

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
                'internal_hosts' => ['internaldomain.com'],
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
            'default_attributes' => [
                Image::class => [
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
        $this->docParser = new MarkdownParser($environment);
        $this->htmlRenderer = new HtmlRenderer($environment);
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
            $testName = basename((string) $markdownFile, '-in.md');
            $inputMarkdown = file_get_contents($markdownFile);
            $expectedMarkdown = file_get_contents(__DIR__ . '/data/' . $testName . '-out.md');
            $expectedhtml = file_get_contents(__DIR__ . '/data/' . $testName . '-out.html');

            yield [$inputMarkdown, $expectedMarkdown, $expectedhtml, $testName];
        }

        return $ret;
    }

    protected function assertFixMarkdown(string $inputMarkdown, string $expectedMarkdown, string $testName): void
    {
        $fixedMarkdown = $this->markdownFixer->fix($inputMarkdown);

        static::assertSame($expectedMarkdown, $fixedMarkdown, sprintf('Unexpected result for "%s" test', $testName));

        $lastMarkdown = $fixedMarkdown;
        $i = 0;

        while ($i < 3) {
            $previousMarkdown = $lastMarkdown;
            $lastMarkdown = $this->markdownFixer->fix($previousMarkdown);

            ++$i;
        }
        static::assertSame($lastMarkdown, $previousMarkdown, sprintf('Unexpected multi-pass result for "%s" test', $testName));
    }

    protected function assertConvertToHtml(string $markdown, string $expectedHtml, string $testName): void
    {
        $document = $this->docParser->parse($markdown);
        $actualHtml = $this->htmlRenderer->renderDocument($document)->getContent();

        $failureMessage = sprintf('Unexpected result for "%s" test', $testName);
        $failureMessage .= "\n=== markdown ===============\n" . $markdown;
        $failureMessage .= "\n=== expected ===============\n" . $expectedHtml . '(strlen=' . mb_strlen($expectedHtml) . ')';
        $failureMessage .= "\n=== got ====================\n" . $actualHtml . '(strlen=' . mb_strlen($actualHtml) . ')';

        static::assertSame($expectedHtml, $actualHtml, $failureMessage);
    }
}
