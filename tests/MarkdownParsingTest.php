<?php

declare(strict_types=1);

namespace JoliMarkdown\Tests;

use JoliMarkdown\MarkdownConverter;
use JoliMarkdown\MarkdownFixer;
use JoliMarkdown\Renderer\MarkdownRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;
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
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        $this->markdownParser = new MarkdownParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);
        $this->mdConverter = new MarkdownConverter(
            $this->markdownParser,
            $htmlRenderer
        );
        $this->markdownFixer = new MarkdownFixer();
        $this->markdownRenderer = new MarkdownRenderer($environment);
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
