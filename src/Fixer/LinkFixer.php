<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliMarkdown\Fixer;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Node;
use Psr\Log\LoggerInterface;

class LinkFixer extends AbstractFixer implements FixerInterface
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        private readonly ?string $internalDomainsPattern = null,
    ) {
    }

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
            if (null !== $this->internalDomainsPattern) {
                $url = preg_replace($this->internalDomainsPattern, '', $node->getUrl(), -1, $count);

                if (null !== $url && $count > 0) {
                    // absolute URLs for the site domain are converted to relative URLs
                    $node->setUrl($url);
                }
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
