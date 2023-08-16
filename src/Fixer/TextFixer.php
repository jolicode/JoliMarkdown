<?php

namespace JoliMarkdown\Fixer;

use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

class TextFixer extends AbstractFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'Text';
    }

    public function supports(Node $node): bool
    {
        return $node instanceof Text;
    }

    public function fix(Node $node): ?iterable
    {
        if ($node instanceof Text) {
            if ($htmlNode = $this->fixBrokenAbbr($node)) {
                return new \ArrayObject([$htmlNode]);
            }

            $literal = preg_replace([
                '/&/u',
                "/\u{a0}/u",
                '/</u',
                '/>/u',
                '/\\\\/',
            ], [
                '&amp;',
                '&nbsp;',
                '&lt;',
                '&gt;',
                '\\\\\\\\',
            ], $node->getLiteral());

            if ($literal !== $node->getLiteral() && null !== $literal) {
                $node->setLiteral($literal);
            }

            return new \ArrayObject([$node]);
        }

        return null;
    }

    private function fixBrokenAbbr(Text $node): ?HtmlInline
    {
        $literal = preg_replace('/^<abbr\s+title=["”]?([^>"”]+)["”]?>(.*)$/u', '<abbr title="$1">$2</abbr>', $node->getLiteral(), -1, $count);

        if (null !== $literal && $count > 0 && ($nextNode = $node->next()) && $nextNode instanceof HtmlInline) {
            $nextNodeLiteral = trim($nextNode->getLiteral());

            if (str_starts_with($nextNodeLiteral, '</abbr>')) {
                $htmlNode = new HtmlInline($literal);
                $node->replaceWith($htmlNode);

                if ('</abbr>' === $nextNodeLiteral) {
                    $nextNode->detach();
                } else {
                    $nextNode->setLiteral(substr($nextNode->getLiteral(), 7));
                }

                return $htmlNode;
            }
        }

        return null;
    }
}
