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

use League\CommonMark\Node\Node;

interface FixerInterface
{
    public function getName(): string;

    public function supports(Node $node): bool;

    /**
     * @return iterable<Node>|null
     */
    public function fix(Node $node): ?iterable;
}
