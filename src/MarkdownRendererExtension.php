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

namespace JoliMarkdown;

use JoliMarkdown\Node\Block\CommonmarkContainer as BlockCommonmarkContainer;
use JoliMarkdown\Node\Inline\CommonmarkContainer as InlineCommonmarkContainer;
use JoliMarkdown\Renderer\Block\BlockQuoteRenderer;
use JoliMarkdown\Renderer\Block\CommonmarkContainerRenderer as BlockCommonmarkContainerRenderer;
use JoliMarkdown\Renderer\Block\DocumentRenderer;
use JoliMarkdown\Renderer\Block\HeadingRenderer;
use JoliMarkdown\Renderer\Block\HtmlBlockRenderer;
use JoliMarkdown\Renderer\Block\IndentedCodeRenderer;
use JoliMarkdown\Renderer\Block\ListBlockRenderer;
use JoliMarkdown\Renderer\Block\ListItemRenderer;
use JoliMarkdown\Renderer\Block\ParagraphRenderer;
use JoliMarkdown\Renderer\Footnote\FootnoteBackrefRenderer;
use JoliMarkdown\Renderer\Footnote\FootnoteContainerRenderer;
use JoliMarkdown\Renderer\Footnote\FootnoteRefRenderer;
use JoliMarkdown\Renderer\Footnote\FootnoteRenderer;
use JoliMarkdown\Renderer\Inline\CodeRenderer;
use JoliMarkdown\Renderer\Inline\CommonmarkContainerRenderer as InlineCommonmarkContainerRenderer;
use JoliMarkdown\Renderer\Inline\EmphasisRenderer;
use JoliMarkdown\Renderer\Inline\HtmlInlineRenderer;
use JoliMarkdown\Renderer\Inline\ImageRenderer;
use JoliMarkdown\Renderer\Inline\LinkRenderer;
use JoliMarkdown\Renderer\Inline\NewlineRenderer;
use JoliMarkdown\Renderer\Inline\Strikethrough\StrikethroughRenderer;
use JoliMarkdown\Renderer\Inline\StrongRenderer;
use JoliMarkdown\Renderer\Inline\TaskListItemMarkerRenderer;
use JoliMarkdown\Renderer\Inline\TextRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\CommonMark\Parser\Block\BlockQuoteStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\FencedCodeStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\HeadingStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\HtmlBlockStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\IndentedCodeStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\ListBlockStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Block\ThematicBreakStartParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\AutolinkParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\BacktickParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\BangParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\CloseBracketParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\EntityParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\EscapableParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\HtmlInlineParser;
use League\CommonMark\Extension\CommonMark\Parser\Inline\OpenBracketParser;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Footnote\Node\Footnote;
use League\CommonMark\Extension\Footnote\Node\FootnoteBackref;
use League\CommonMark\Extension\Footnote\Node\FootnoteContainer;
use League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Extension\TaskList\TaskListItemMarkerParser;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\NewlineParser;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;

/**
 * @psalm-suppress UnusedClass
 */
final class MarkdownRendererExtension implements ExtensionInterface, ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('commonmark', Expect::structure([
            'use_asterisk' => Expect::bool(true),
            'use_underscore' => Expect::bool(true),
            'enable_strong' => Expect::bool(true),
            'enable_em' => Expect::bool(true),
            'unordered_list_markers' => Expect::listOf('string')->min(1)->default(['*', '+', '-'])->mergeDefaults(false),
        ]));

        $builder->addSchema('joli_markdown', Expect::structure([
            'prefer_asterisk_over_underscore' => Expect::bool(true),
            'unordered_list_marker' => Expect::string('-'),
            'internal_domains' => Expect::listOf('string')->default([]),
        ]));
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FootnoteExtension());
        $environment->addExtension(new StrikethroughExtension());

        $environment->addBlockStartParser(new BlockQuoteStartParser(), 70);
        $environment->addBlockStartParser(new HeadingStartParser(), 60);
        $environment->addBlockStartParser(new FencedCodeStartParser(), 50);
        $environment->addBlockStartParser(new HtmlBlockStartParser(), 40);
        $environment->addBlockStartParser(new ThematicBreakStartParser(), 20);
        $environment->addBlockStartParser(new ListBlockStartParser(), 10);
        $environment->addBlockStartParser(new IndentedCodeStartParser(), -100);

        $environment->addInlineParser(new NewlineParser(), 200);
        $environment->addInlineParser(new BacktickParser(), 150);
        $environment->addInlineParser(new EscapableParser(), 80);
        $environment->addInlineParser(new EntityParser(), 70);
        $environment->addInlineParser(new AutolinkParser(), 50);
        $environment->addInlineParser(new HtmlInlineParser(), 40);
        $environment->addInlineParser(new CloseBracketParser(), 30);
        $environment->addInlineParser(new OpenBracketParser(), 20);
        $environment->addInlineParser(new BangParser(), 10);
        $environment->addInlineParser(new TaskListItemMarkerParser(), 35);

        $environment->addRenderer(BlockQuote::class, new BlockQuoteRenderer());
        $environment->addRenderer(Document::class, new DocumentRenderer());
        $environment->addRenderer(FencedCode::class, new Renderer\Block\FencedCodeRenderer());
        $environment->addRenderer(Heading::class, new HeadingRenderer());
        $environment->addRenderer(HtmlBlock::class, new HtmlBlockRenderer());
        $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer());
        $environment->addRenderer(ListBlock::class, new ListBlockRenderer());
        $environment->addRenderer(ListItem::class, new ListItemRenderer());
        $environment->addRenderer(Paragraph::class, new ParagraphRenderer());
        $environment->addRenderer(ThematicBreak::class, new Renderer\Block\ThematicBreakRenderer());

        $environment->addRenderer(Code::class, new CodeRenderer());
        $environment->addRenderer(Emphasis::class, new EmphasisRenderer());
        $environment->addRenderer(HtmlInline::class, new HtmlInlineRenderer());
        $environment->addRenderer(Image::class, new ImageRenderer());
        $environment->addRenderer(Link::class, new LinkRenderer());
        $environment->addRenderer(Newline::class, new NewlineRenderer());
        $environment->addRenderer(Strong::class, new StrongRenderer());
        $environment->addRenderer(Text::class, new TextRenderer());
        $environment->addRenderer(TaskListItemMarker::class, new TaskListItemMarkerRenderer());

        // footnote extension
        $environment->addRenderer(FootnoteRef::class, new FootnoteRefRenderer());
        $environment->addRenderer(FootnoteContainer::class, new FootnoteContainerRenderer());
        $environment->addRenderer(Footnote::class, new FootnoteRenderer());
        $environment->addRenderer(FootnoteBackref::class, new FootnoteBackrefRenderer());

        // strikethrough extension
        $environment->addRenderer(Strikethrough::class, new StrikethroughRenderer());

        // remaining HTML block elements
        $environment->addRenderer(BlockCommonmarkContainer::class, new BlockCommonmarkContainerRenderer());
        $environment->addRenderer(InlineCommonmarkContainer::class, new InlineCommonmarkContainerRenderer());
    }
}
