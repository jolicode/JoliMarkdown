<?php

use Castor\Attribute\AsTask;

use function Castor\import;
use function Castor\run;

import(__DIR__ . '/.castor');

#[AsTask(description: 'Installs the application (composer, yarn, ...)')]
function install(): void
{
    run('composer install -n --prefer-dist --optimize-autoloader');
}
