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

namespace JoliMarkdown\Node\Block;

/**
 * The opening or the closing tag of an HTML element kept around markdown
 * blocks. Unlike other containers, it is always separated from its siblings
 * by a blank line, so that the blocks it wraps are parsed as markdown.
 */
final class HtmlWrapperTag extends CommonmarkContainer
{
}
