<?php

namespace JoliMarkdown\Fixer;

use Psr\Log\LoggerInterface;

abstract class AbstractFixer
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }
}
