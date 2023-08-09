<?php

namespace JoliMarkdown\Fixer;

use League\CommonMark\Node\Node;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

interface FixerInterface
{
    public function getName(): string;

    public function supports(Node $node): bool;

    public function fix(Node $node): ?iterable;
}
