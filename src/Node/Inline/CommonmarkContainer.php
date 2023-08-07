<?php

declare(strict_types=1);

namespace JoliMarkdown\Node\Inline;

use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\StringContainerInterface;

class CommonmarkContainer extends AbstractInline implements StringContainerInterface
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
