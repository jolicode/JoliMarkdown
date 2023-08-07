<?php

namespace JoliMarkdown\Fixer;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;

class LinkFixer extends AbstractFixer implements FixerInterface
{
    public function getName(): string
    {
        return 'Link';
    }

    public function supports(Node $node): bool
    {
        return $node instanceof Link;
    }

    public function fix(Node $node): ?iterable
    {
        if ($node instanceof Link) {
            $url = preg_replace('#^(https?)?://(www.)?jolicode.com/?#', '', $node->getUrl(), -1, $count);

            if ($count > 0) {
                // absolute URLs for the site domain are converted to relative URLs
                $node->setUrl($url);
            }

            if (str_starts_with($node->getUrl(), 'http')) {
                return null;
            }

            if (!str_starts_with($node->getUrl(), '/') && !str_starts_with($node->getUrl(), '://') && !str_starts_with($node->getUrl(), '#')) {
                $node->setUrl('/' . $node->getUrl());
            }

            return new \ArrayObject([$node]);
        }

        return null;
    }
}
