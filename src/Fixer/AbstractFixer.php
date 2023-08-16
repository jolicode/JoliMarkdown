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

use Psr\Log\LoggerInterface;

abstract class AbstractFixer
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }
}
