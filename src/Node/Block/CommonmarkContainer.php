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

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\StringContainerInterface;

class CommonmarkContainer extends AbstractBlock implements StringContainerInterface
{
    public function __construct(protected string $literal)
    {
        parent::__construct();
    }

    public function getLiteral(): string
    {
        return $this->literal;
    }

    public function setLiteral(string $literal): void
    {
        $this->literal = $literal;
    }
}
