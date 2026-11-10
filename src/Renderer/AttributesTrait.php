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

namespace JoliMarkdown\Renderer;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Node;

trait AttributesTrait
{
    private function renderAttributes(Node $node): string
    {
        $attributes = $node->data->get('attributes');
        $result = '';

        // These attributes are rendered with the native markdown syntax of
        // links and images, so they must not be repeated in the attributes
        // block of these nodes. Other nodes keep them as regular attributes.
        $nativeAttributes = ($node instanceof Link || $node instanceof Image)
            ? ['alt', 'href', 'src', 'title']
            : [];

        foreach ($attributes as $key => $value) {
            if ('class' === $key) {
                if ('' !== trim((string) $value)) {
                    $result .= implode('', array_unique(array_filter(array_map(static fn ($class) => ' .' . trim($class), explode(' ', (string) $value)))));
                }
            } elseif ('id' === $key) {
                if ('' !== trim((string) $value)) {
                    $result .= ' #' . $value;
                }
            } elseif (!\in_array($key, $nativeAttributes, true)) {
                if ('' !== $value) {
                    if (\is_array($value)) {
                        $value = implode(' ', array_unique($value));
                    }

                    $result .= " {$key}=\"{$value}\"";
                } else {
                    $result .= " {$key}=true";
                }
            }
        }

        return '' !== $result ? '{' . trim($result) . '}' : '';
    }

    /**
     * Returns the title of a link or an image. The native markdown title
     * (`[text](url "title")`) wins, otherwise the `title` attribute set with
     * the attributes syntax (`[text](url){title="title"}`) is used.
     */
    private function getNativeTitle(Link|Image $node): ?string
    {
        $title = $node->getTitle();

        if (null !== $title && '' !== $title) {
            return $title;
        }

        $attributeTitle = $node->data->get('attributes/title', null);

        if (\is_string($attributeTitle) && '' !== $attributeTitle) {
            return $attributeTitle;
        }

        return $title;
    }

    private function addAttributes(Node $node, string $output): string
    {
        if ($node instanceof AbstractInline) {
            $output .= $this->renderAttributes($node);
        } else {
            $attributes = $this->renderAttributes($node);

            if ('' !== $attributes) {
                $output = $attributes . "\n" . $output;
            }
        }

        return $output;
    }
}
