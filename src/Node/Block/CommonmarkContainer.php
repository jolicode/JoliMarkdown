<?php

declare(strict_types=1);

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
