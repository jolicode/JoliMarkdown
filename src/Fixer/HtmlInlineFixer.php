<?php

namespace JoliMarkdown\Fixer;

use JoliMarkdown\Node\Inline\CommonmarkContainer;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

class HtmlInlineFixer extends HtmlBlockFixer
{
    public function getName(): string
    {
        return 'HtmlInline';
    }

    public function supports(Node $node): bool
    {
        return $node instanceof HtmlInline;
    }

    public function fix(Node $node): ?iterable
    {
        if ($node instanceof HtmlInline) {
            return $this->fixHtmlInline($node);
        }

        return null;
    }

    protected function buildCommonMarkContainer(string $literal, array $attributes = []): Node
    {
        $node = new CommonmarkContainer($literal);
        $node->data['attributes'] = $attributes;

        return $node;
    }

    protected function createCommentNode(string $text): Node
    {
        return new HtmlInline('<!--' . $text . '-->');
    }

    /**
     * @return iterable<Node>|null
     */
    private function fixHtmlInline(HtmlInline $node): ?iterable
    {
        if (null === $node->parent()) {
            // the node has been removed
            return null;
        }

        // search for adjacent HtmlBlock nodes
        $literal = [$node->getLiteral()];

        if ($this->closes($literal)) {
            // self-closing html block
            return $this->fixAndReplace($literal, $node);
        }

        // search for the tag name
        preg_match('/^<([^\s>]+).*>$/', $node->getLiteral(), $matches);

        if (!isset($matches[1])) {
            // this is a tag not a tag
            return $this->fixAndReplace($literal, $node);
        }

        $closingNodes = [$node];
        $nodesReplacements = [];
        $currentNode = $node;
        $isClosed = false;

        while (!$isClosed && ($currentNode = $currentNode->next())) {
            if ($currentNode instanceof AbstractInline) {
                if ($currentNode instanceof HtmlInline) {
                    $literal[] = $currentNode->getLiteral();
                    $closingNodes[] = $currentNode;

                    if ($this->closes($literal)) {
                        $isClosed = true;
                    }
                } elseif ($currentNode instanceof Text) {
                    $literal[] = htmlspecialchars($currentNode->getLiteral(), \ENT_NOQUOTES);
                    $closingNodes[] = $currentNode;
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

        $fixedNodes = $this->fixAndReplace(
            $literal,
            nodes: $closingNodes,
            separator: ''
        );

        if (null !== $fixedNodes) {
            $this->applyReplacementNodes($fixedNodes, $nodesReplacements);
        }

        return $fixedNodes;
    }
}
