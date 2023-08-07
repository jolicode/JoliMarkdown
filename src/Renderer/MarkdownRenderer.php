<?php

declare(strict_types=1);

namespace JoliMarkdown\Renderer;

use JoliMarkdown\Node\Block\CommonmarkContainer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentPreRenderEvent;
use League\CommonMark\Event\DocumentRenderedEvent;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Node;
use League\CommonMark\Output\RenderedContent;
use League\CommonMark\Output\RenderedContentInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\DocumentRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @psalm-suppress UnusedClass
 */
final class MarkdownRenderer implements DocumentRendererInterface, ChildNodeRendererInterface
{
    public function __construct(
        #[Autowire(service: 'app.commonmark.markdown_renderer_environment')]
        private readonly Environment $environment,
    ) {
    }

    public function renderDocument(Document $document): RenderedContentInterface
    {
        $this->environment->dispatch(new DocumentPreRenderEvent($document, 'md'));

        $output = new RenderedContent($document, (string) $this->renderNode($document));

        $event = new DocumentRenderedEvent($output);
        $this->environment->dispatch($event);

        return $event->getOutput();
    }

    public function renderNodes(iterable $nodes): string
    {
        $output = '';
        $skipSeparator = true;

        foreach ($nodes as $node) {
            $renderedNode = $this->renderNode($node);

            if ('' !== $renderedNode) {
                if (
                    !$skipSeparator
                    && $node instanceof AbstractBlock
                    && !(
                        ($node instanceof CommonmarkContainer)
                        && $node->previous()
                        && ($node->previous() instanceof CommonmarkContainer)
                    )) {
                    $output .= $this->getBlockSeparator();
                }

                $output .= $renderedNode;
                $skipSeparator = $node instanceof Newline || !$node instanceof AbstractInline && !$node instanceof AbstractBlock;
            }
        }

        return $output;
    }

    public function getBlockSeparator(): string
    {
        return $this->environment->getConfiguration()->get('renderer/block_separator');
    }

    public function getInnerSeparator(): string
    {
        return $this->environment->getConfiguration()->get('renderer/inner_separator');
    }

    /**
     * @throws \RuntimeException
     */
    private function renderNode(Node $node): \Stringable|string
    {
        $renderers = $this->environment->getRenderersForClass($node::class);

        foreach ($renderers as $renderer) {
            \assert($renderer instanceof NodeRendererInterface);
            if (($result = $renderer->render($node, $this)) !== null) {
                return $result;
            }
        }

        throw new \RuntimeException('Unable to find corresponding renderer for node type ' . $node::class);
    }
}
