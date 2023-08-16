<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Castor\Attribute\AsTask;

use function Castor\import;
use function Castor\run;

import(__DIR__ . '/.castor');

#[AsTask(description: 'Installs the application (composer, yarn, ...)')]
function install(): void
{
    run('composer install -n --prefer-dist --optimize-autoloader');
}
