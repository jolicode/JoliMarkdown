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

use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Node;

trait AttributesTrait
{
    private function renderAttributes(Node $node): string
    {
        $attributes = $node->data->get('attributes');
        $result = '';

        foreach ($attributes as $key => $value) {
            if ('class' === $key) {
                if ('' !== trim((string) $value)) {
                    $result .= implode('', array_unique(array_filter(array_map(static fn ($class) => ' .' . trim($class), explode(' ', (string) $value)))));
                }
            } elseif ('id' === $key) {
                if ('' !== trim((string) $value)) {
                    $result .= ' #' . $value;
                }
            } elseif (!\in_array($key, ['alt', 'href', 'src', 'title'], true)) {
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
