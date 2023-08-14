<?php

namespace JoliMarkdown\Fixer;

use League\CommonMark\Node\Node;

interface FixerInterface
{
    public function getName(): string;

    public function supports(Node $node): bool;

    public function fix(Node $node): ?iterable;
}
