<?php

namespace JoliMarkdown\Fixer;

use JoliMarkdown\Node\Block\CommonmarkContainer;
use JoliMarkdown\Node\Inline\CommonmarkContainer as InlineCommonmarkContainer;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListData;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\StringContainerInterface;
use League\CommonMark\Util\UrlEncoder;
use League\CommonMark\Util\Xml;
use League\HTMLToMarkdown\HtmlConverter;
use Psr\Log\LoggerInterface;

class HtmlBlockFixer extends AbstractFixer implements FixerInterface
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        private readonly HtmlConverter $htmlConverter,
    ) {
    }

    public function getName(): string
    {
        return 'HtmlBlock';
    }

    public function supports(Node $node): bool
    {
        return $node instanceof HtmlBlock;
    }

    public function fix(Node $node): ?iterable
    {
        if ($node instanceof HtmlBlock) {
            return $this->fixHtmlBlock($node);
        }

        return null;
    }

    /**
     * @param iterable<Node>      $nodes
     * @param array<string, Node> $replacements
     */
    protected function applyReplacementNodes(iterable $nodes, array $replacements): void
    {
        foreach ($nodes as $node) {
            $this->applyReplacementNodes($node->children(), $replacements);

            if ($node instanceof StringContainerInterface) {
                // there might be a string in it
                $newNodes = [$node];

                foreach ($replacements as $key => $replacement) {
                    if ('' !== $key) {
                        foreach ($newNodes as $newNodeKey => $newNode) {
                            if ($newNode instanceof StringContainerInterface) {
                                $exploded = explode($key, $newNode->getLiteral());

                                if (2 === \count($exploded)) {
                                    $className = $newNode::class;
                                    $newNodes = [...\array_slice($newNodes, 0, $newNodeKey), new $className($exploded[0]), $replacement, new $className($exploded[1]), ...\array_slice($newNodes, $newNodeKey + 1)];
                                }
                            }
                        }
                    }
                }

                if (\count($newNodes) > 1) {
                    foreach ($newNodes as $newNode) {
                        $node->insertBefore($newNode);
                    }

                    $node->detach();
                }
            }
        }
    }

    /**
     * @return Node|Node[]|null
     */
    protected function buildNodeFromDomElement(\DOMNode $element, bool $allowLtrim = false, bool $allowRtrim = false, bool $canConvertStyledDiv = true): mixed
    {
        $node = null;

        if ($element instanceof \DOMText) {
            $value = match (true) {
                $allowLtrim && $allowRtrim => trim((string) $element->nodeValue),
                $allowLtrim => ltrim((string) $element->nodeValue),
                $allowRtrim => rtrim((string) $element->nodeValue),
                default => $element->nodeValue,
            };

            if (' ' !== $value && '' === $this->trimSpaces((string) $value)) {
                return null;
            }

            return new Text((string) $value);
        } elseif ($element instanceof \DOMElement) {
            $attributes = $this->getAttributes($element);

            if (\in_array($element->tagName, ['div', 'p'])) {
                if ($element->childNodes->length > 0) {
                    $firstChild = $element->childNodes->item(0);

                    while (null !== $firstChild && !$firstChild instanceof \DOMElement && !($firstChild instanceof \DOMText && '' !== trim((string) $firstChild->nodeValue))) {
                        $firstChild = $firstChild->nextSibling;
                    }

                    if (
                        !$canConvertStyledDiv && 0 === \count($attributes)
                    ) {
                        // the div is useless, build its children instead
                        $node = $this->buildChildElementsAsChildNodes($element, $canConvertStyledDiv);
                    } elseif (
                        null === $firstChild
                        || 0 === \count($attributes)
                        || (
                            $canConvertStyledDiv
                            && (
                                $firstChild instanceof \DOMText
                                || !\in_array($firstChild->tagName, ['div', 'iframe', 'p', 'blockquote', 'pre', 'ul', 'ol'])
                                || 0 === \count($firstChild->attributes)
                            )
                        )
                    ) {
                        $node = new Paragraph();
                        $node->data['attributes'] = $attributes;
                        $this->appendChildElementsAsChildNodes($element, $node, $canConvertStyledDiv && 0 === \count($attributes));
                    }
                }
            } elseif ('blockquote' === $element->tagName) {
                if ($canConvertStyledDiv || 0 === \count($attributes)) {
                    $node = new BlockQuote();
                    $node->data['attributes'] = $attributes;
                    $this->appendChildElementsAsChildNodes($element, $node, true);
                }
            } elseif ('pre' === $element->tagName) {
                foreach ($element->childNodes as $key => $child) {
                    if ($child instanceof \DOMElement && 'code' === $child->tagName) {
                        $node = new FencedCode(0, '', 0);
                        $childAttributes = $this->getAttributes($child);

                        if (isset($childAttributes['class']) && str_starts_with((string) $childAttributes['class'], 'language-')) {
                            $node->setInfo(mb_substr((string) $childAttributes['class'], 9));
                            unset($childAttributes['class']);
                        }

                        $node->data['attributes'] = [...$attributes, ...$childAttributes];
                        $node->setLiteral($this->rtrimSpaces((string) $child->nodeValue));

                        break;
                    }

                    if (!$child instanceof \DOMText || '' !== trim((string) $child->nodeValue)) {
                        break;
                    }
                }
            } elseif ('span' === $element->tagName) {
                $nodes = [];

                foreach ($element->childNodes as $key => $child) {
                    $childNode = $this->buildNodeFromDomElement($child, 0 === $key, $key === $element->childNodes->length - 1);

                    if ($childNode instanceof Node) {
                        $nodes[] = $childNode;
                    } elseif (\is_array($childNode)) {
                        $nodes = array_merge($nodes, $childNode);
                    }
                }

                if (\count($attributes) > 0) {
                    // the span has attributes, so enclose its childNodes in CommonmarkContainers
                    $openingNode = new InlineCommonmarkContainer(sprintf(
                        '<span%s>',
                        $this->outputAttributes($attributes),
                    ));
                    array_unshift($nodes, $openingNode);
                    $nodes[] = new InlineCommonmarkContainer('</span>');
                }

                return $nodes;
            } elseif ('ol' === $element->tagName || 'ul' === $element->tagName) {
                if ($canConvertStyledDiv || 0 === \count($attributes)) {
                    $data = new ListData();
                    $data->type = 'ul' === $element->tagName ? ListBlock::TYPE_BULLET : ListBlock::TYPE_ORDERED;
                    $node = new ListBlock($data);
                    $node->data['attributes'] = $attributes;

                    foreach ($element->childNodes as $child) {
                        if ($child instanceof \DOMElement && 'li' === $child->tagName) {
                            $listItem = new ListItem($data);

                            foreach ($child->childNodes as $key => $grandChild) {
                                $childNode = $this->buildNodeFromDomElement(
                                    element: $grandChild,
                                    allowLtrim: 0 === $key,
                                    allowRtrim: $key === $element->childNodes->length - 1
                                );
                                $this->appendChildNodes($listItem, $childNode);
                            }

                            $node->appendChild($listItem);
                        }
                    }
                }
            } elseif ('a' === $element->tagName) {
                if (!isset($attributes['href'])) {
                    $this->logger->notice('Link without href attribute');
                    $href = '';
                } else {
                    $href = $attributes['href'] ? UrlEncoder::unescapeAndEncode($attributes['href']) : '';
                }

                $node = new Link($href, null, $attributes['title'] ?? '');
                $node->data['attributes'] = $attributes;
                $node = $this->appendChildElementsAsChildNodes($element, $node, false);
            } elseif ('img' === $element->tagName) {
                if (!isset($attributes['src'])) {
                    $this->logger->notice('Image without src attribute');
                }

                $url = $attributes['src'] ? UrlEncoder::unescapeAndEncode($attributes['src']) : '';
                $node = new Image($url, $attributes['alt'] ?? '', $attributes['title'] ?? '');
                $node->data['attributes'] = $attributes;
            } elseif ('code' === $element->tagName) {
                $node = new Code($element->textContent);
                $node->data['attributes'] = $attributes;
            } elseif ('strong' === $element->tagName) {
                $node = new Strong();
                $node->data['attributes'] = $attributes;
                $node = $this->appendChildElementsAsChildNodes($element, $node, false);
            } elseif ('em' === $element->tagName) {
                $node = new Emphasis();
                $node->data['attributes'] = $attributes;
                $node = $this->appendChildElementsAsChildNodes($element, $node, false);
            } elseif ('hr' === $element->tagName) {
                $node = new ThematicBreak();
                $node->data['attributes'] = $attributes;
            } elseif (preg_match('/^h([1-6])$/', $element->tagName, $matches)) {
                $node = new Heading((int) $matches[1]);
                $node->data['attributes'] = $attributes;
                $node = $this->appendChildElementsAsChildNodes($element, $node, false);
            }

            if (null === $node && null !== $element->ownerDocument) {
                $elementXml = $element->ownerDocument->saveXML($element);
                $elementHtml = $element->ownerDocument->saveHTML($element);

                if (false !== $elementHtml && false !== $elementXml) {
                    $converted = $this->htmlConverter->convert($elementHtml);

                    // search for the tag name
                    preg_match('/^\s*<([^\s>]+).*>/', $converted, $tagNameMatches);

                    // try to let self-closed html tags as-is
                    preg_match('/^<\s*([a-z0-9-]+)(\s[^>]*)?\/>$/', $elementXml, $matches);

                    if (isset($matches[1]) && preg_match(sprintf('/^<%s(\s[^>]*)?\>.*<\/%s>$/', $matches[1], $matches[1]), $converted, $subMatches)) {
                        if (\in_array($matches[1], ['br', 'hr', 'source'])) {
                            $converted = $elementXml;
                        } else {
                            $converted = $elementHtml;
                        }
                    }

                    if (isset($tagNameMatches[1]) || isset($matches[1]) && preg_match(sprintf('/^<%s(\s.*)?>$/', $matches[1]), $converted, $subMatches)) {
                        $attributes = [];
                    }
                } else {
                    $converted = $elementXml;
                }

                if (false !== $converted) {
                    $node = $this->buildCommonMarkContainer($converted, $attributes);
                }
            }
        } elseif ($element instanceof \DOMComment) {
            $node = $this->createCommentNode($element->data);
        }

        return $node;
    }

    protected function appendChildElementsAsChildNodes(\DOMNode $element, Node $node, bool $canConvertStyledDiv = true): ?Node
    {
        $childNodes = $this->buildChildElementsAsChildNodes($element, $canConvertStyledDiv);

        return $this->appendChildNodes($node, $childNodes);
    }

    /**
     * @param Node|Node[] $childNode
     */
    protected function appendChildNodes(Node $node, array|Node|null $childNode): ?Node
    {
        if (!\is_array($childNode)) {
            $childNode = [$childNode];
        }

        $childNode = array_filter($childNode);
        $isNodeInline = $node instanceof AbstractInline;

        foreach ($childNode as $child) {
            if ($isNodeInline) {
                if ($child instanceof CommonmarkContainer) {
                    // let's convert it to an inline commonmark container
                    $child = new InlineCommonmarkContainer($child->getLiteral());
                } elseif ($child instanceof AbstractBlock) {
                    // cannot nest block nodes into inline ones, so return an HTML string
                    return null;
                }
            }

            $node->appendChild($child);
        }

        return $node;
    }

    /**
     * @return Node[]
     */
    protected function buildChildElementsAsChildNodes(\DOMNode $element, bool $canConvertStyledDiv = true): array
    {
        $childNodes = [];

        foreach ($element->childNodes as $key => $child) {
            $childNode = $this->buildNodeFromDomElement(
                element: $child,
                allowLtrim: 0 === $key,
                allowRtrim: $key === $element->childNodes->length - 1,
                canConvertStyledDiv: $canConvertStyledDiv,
            );

            if (null !== $childNode) {
                if (\is_array($childNode)) {
                    $childNodes = array_merge($childNodes, $childNode);
                } else {
                    $childNodes[] = $childNode;
                }
            }
        }

        // trim successive space nodes
        $previous = null;

        foreach ($childNodes as $key => $childNode) {
            if ($childNode instanceof Text
                && (
                    null === $previous
                    || $previous instanceof AbstractBlock
                )
                && $this->isSpace($childNode->getLiteral())
                && (
                    !isset($childNodes[$key + 1])
                    || $childNodes[$key + 1] instanceof AbstractBlock
                )
            ) {
                unset($childNodes[$key]);
            } else {
                $previous = $childNode;
            }
        }

        return array_values($childNodes);
    }

    /**
     * @param array<string, string|null> $attributes
     */
    protected function buildCommonMarkContainer(string $literal, array $attributes = []): Node
    {
        $node = new CommonmarkContainer($literal);
        $node->data['attributes'] = $attributes;

        return $node;
    }

    protected function createCommentNode(string $text): Node
    {
        $node = new HtmlBlock(HtmlBlock::TYPE_2_COMMENT);
        $node->setLiteral($text);

        return $node;
    }

    /**
     * @param string|array<string> $literal
     * @param Node|array<Node>     $nodes
     *
     * @return iterable<Node>|null
     */
    protected function fixAndReplace(string|array $literal, Node|array $nodes, string $separator = "\n"): ?iterable
    {
        if (\is_array($literal)) {
            $literal = implode($separator, $literal);
        }

        // try to fix broken abbr tags
        $literal = preg_replace('/<abbr\s+title=["”]?([^>"”]+)["”]?>/u', '<abbr title="$1">', $literal);

        if (!\is_array($nodes)) {
            $nodes = [$nodes];
        }

        if ((is_countable($nodes[0]->data->get('attributes')) ? \count($nodes[0]->data->get('attributes')) : 0) > 0) {
            // the HTMLBlock has atrributes, encapsulate it in a div
            $attributes = '';

            foreach ($nodes[0]->data->get('attributes') as $key => $value) {
                $attributes .= sprintf(' %s="%s"', $key, Xml::escape($value));
            }

            $literal = sprintf('<div%s>%s</div>', $attributes, $literal);
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $literal . '</div>', \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        $documentElement = $dom->documentElement;

        if (null === $documentElement) {
            $this->removeNodes($nodes);

            return [];
        }

        $fixedNodes = $this->buildNodeFromDomElement(
            element: $documentElement,
            canConvertStyledDiv: HtmlInlineFixer::class !== static::class,
        );

        if ($fixedNodes instanceof Node) {
            $fixedNodes = $fixedNodes->children();
        }

        if (null !== $fixedNodes) {
            foreach ($fixedNodes as $fixedNode) {
                if (!$fixedNode instanceof AbstractBlock && HtmlInlineFixer::class !== static::class) {
                    $wrapper = new Paragraph();
                    $wrapper->appendChild($fixedNode);
                    $fixedNode = $wrapper;
                }

                $nodes[0]->insertBefore($fixedNode);
            }
        }

        $this->removeNodes($nodes);

        return $fixedNodes;
    }

    protected function removeNode(Node $node): void
    {
        if (null === $node->parent()) {
            return;
        }

        $newChildren = $node->parent()->children();

        foreach ($newChildren as $key => $child) {
            if ($child === $node) {
                unset($newChildren[$key]);
            }
        }

        $node->parent()->replaceChildren(array_values((array) $newChildren));
    }

    /**
     * @param Node[] $nodes
     */
    protected function removeNodes(array $nodes): void
    {
        foreach ($nodes as $node) {
            $node->detach();
        }
    }

    /**
     * @param string[] $literal
     */
    protected function closes(array $literal): bool
    {
        $literal = array_map(function (string $line): string {
            $line = preg_replace('/(?:<\s+)/', '<', $line);
            $line = preg_replace('/(?:\s+>)/', '>', (string) $line);
            $line = preg_replace('/(?:\s+\/>)/', '/>', (string) $line);

            return (string) $line;
        }, $literal);

        $literalString = implode('', $literal);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $literalString . '</div>', \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD | \LIBXML_SCHEMA_CREATE);
        $documentElement = $dom->documentElement;

        if (null === $documentElement) {
            return false;
        }

        return
            null !== $documentElement->ownerDocument && (
                $documentElement->ownerDocument->saveXML($documentElement) === '<div>' . $literalString . '</div>'
                || $documentElement->ownerDocument->saveHTML($documentElement) === '<div>' . $literalString . '</div>'
            );
    }

    /**
     * @return iterable<Node>|null
     */
    private function fixHtmlBlock(HtmlBlock $node): ?iterable
    {
        if (null === $node->parent()) {
            // the node has been removed
            return null;
        }

        $literal = $this->trimSpaces($node->getLiteral());

        if ($literal !== $node->getLiteral()) {
            // clear the trailing spaces
            $node->setLiteral($literal);
        }

        // search for adjacent HtmlBlock nodes
        $literal = [$node->getLiteral()];

        if ($this->closes($literal)) {
            // self-closing html block
            return $this->fixAndReplace($literal, $node);
        }

        // search for the tag name
        preg_match('/^\s*<([^\s>]+).*>/', $node->getLiteral(), $matches);

        if (!isset($matches[1])) {
            // this is a tag not a tag
            return $this->fixAndReplace($literal, $node);
        }

        $closingNodes = [$node];
        $nodesReplacements = [];
        $currentNode = $node;
        $isClosed = false;

        while (!$isClosed && ($currentNode = $currentNode->next())) {
            if ($currentNode instanceof AbstractBlock) {
                if ($currentNode instanceof HtmlBlock) {
                    $literal[] = $currentNode->getLiteral();
                    $closingNodes[] = $currentNode;

                    if ($this->closes($literal)) {
                        $isClosed = true;
                    }
                } else {
                    $key = uniqid();
                    $nodesReplacements[$key] = $currentNode;
                    $literal[] = $key;
                }
            }
        }

        if (!$isClosed) {
            $this->logger->info(sprintf('<%s> tag is NOT closed', $matches[1]));
        }

        $fixedNodes = $this->fixAndReplace($literal, $closingNodes);

        if (null !== $fixedNodes) {
            $this->applyReplacementNodes($fixedNodes, $nodesReplacements);
        }

        return $fixedNodes;
    }

    /**
     * @return array<string, string|null>
     */
    private function getAttributes(\DOMElement $element): array
    {
        $attributes = [];

        foreach ($element->attributes as $name => $attr) {
            $attributes[(string) $name] = $attr->value;
        }

        return $attributes;
    }

    /**
     * @param array<string, bool|string|null> $attributes
     */
    private function outputAttributes(array $attributes): string
    {
        $result = '';

        foreach ($attributes as $key => $value) {
            if (true === $value) {
                $result .= ' ' . $key;
            } elseif (false === $value) {
                continue;
            } else {
                $result .= ' ' . $key . '="' . Xml::escape((string) $value) . '"';
            }
        }

        return $result;
    }

    private function isSpace(string $string): bool
    {
        return '' === $this->trimSpaces($string);
    }

    private function ltrimSpaces(string $string): string
    {
        return (string) preg_replace('/(^\s+)/us', '', $string);
    }

    private function rtrimSpaces(string $string): string
    {
        return (string) preg_replace('/(\s+$)/us', '', $string);
    }

    private function trimSpaces(string $string): string
    {
        return $this->ltrimSpaces($this->rtrimSpaces($string));
    }
}
