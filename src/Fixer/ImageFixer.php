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

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Node;
use Psr\Log\LoggerInterface;

class ImageFixer extends AbstractFixer implements FixerInterface
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        private readonly ?string $internalDomainsPattern = null,
    ) {
    }

    public function getName(): string
    {
        return 'Image';
    }

    public function supports(Node $node): bool
    {
        return $node instanceof Image;
    }

    public function fix(Node $node): ?iterable
    {
        if ($node instanceof Image) {
            if (null !== $this->internalDomainsPattern) {
                $url = preg_replace($this->internalDomainsPattern, '', $node->getUrl(), -1, $count);

                if ($count > 0 && null !== $url) {
                    // absolute URLs for the site domain are converted to relative URLs
                    $node->setUrl($url);
                }
            }

            if (str_starts_with($node->getUrl(), 'http')) {
                $this->logger->notice(
                    sprintf(
                        'Image with absolute path found: %s',
                        $node->getUrl(),
                    )
                );

                return null;
            }

            if (!str_starts_with($node->getUrl(), '/') && !str_starts_with($node->getUrl(), '://')) {
                $node->setUrl('/' . $node->getUrl());
            }

            return new \ArrayObject([$node]);
        }

        return null;
    }
}
