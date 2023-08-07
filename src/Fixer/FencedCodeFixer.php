<?php

namespace JoliMarkdown\Fixer;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;

class FencedCodeFixer extends AbstractFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'FencedCode';
    }

    public function supports(Node $node): bool
    {
        return $node instanceof FencedCode;
    }

    public function fix(Node $node): ?iterable
    {
        if ($node instanceof FencedCode) {
            $info = $node->getInfo();
            $attributes = $node->data->getData('attributes');

            if ('' === $info && $attributes->has('class') && preg_match('/language-(\w+)/', (string) $attributes->get('class'), $infoWords)) {
                $node->setInfo($infoWords[1]);
                // $node->data->set('attributes.class', str_replace($infoWords[0], '', $attributes->get('class')));
                $node->data->set('attributes.class', preg_replace('/' . $infoWords[0] . '/u', '', (string) $attributes->get('class')));

                return new \ArrayObject([$node]);
            }
        }

        return null;
    }
}
