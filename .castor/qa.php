<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace qa;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

use function Castor\exit_code;
use function Castor\run;

#[AsTask(description: 'Runs all QA tasks')]
function all(): void
{
    install();
    cs(false);
    phpstan();
    rector();
    phpunit();
}

#[AsTask(description: 'Installs tooling')]
function install(?string $only = null): void
{
    $map = [
        'php-cs-fixer' => fn () => run('composer install --working-dir tools/php-cs-fixer'),
        'phpstan' => fn () => run('composer install --working-dir tools/phpstan'),
        'phpunit' => fn () => run('composer install --working-dir tools/phpunit'),
        'rector' => fn () => run('composer install --working-dir tools/rector'),
    ];

    if ($only) {
        $map = array_filter($map, fn ($key) => $key === $only, \ARRAY_FILTER_USE_KEY);
    }

    foreach ($map as $task) {
        $task();
    }
}

#[AsTask(description: 'Update tooling')]
function update(?string $only = null): void
{
    $map = [
        'php-cs-fixer' => fn () => run('composer update --working-dir tools/php-cs-fixer'),
        'phpstan' => fn () => run('composer update --working-dir tools/phpstan'),
        'phpunit' => fn () => run('composer update --working-dir tools/phpunit'),
        'rector' => fn () => run('composer update --working-dir tools/rector'),
    ];

    if ($only) {
        $map = array_filter($map, fn ($key) => $key === $only, \ARRAY_FILTER_USE_KEY);
    }

    foreach ($map as $task) {
        $task();
    }
}

#[AsTask(description: 'Fix coding standards', aliases: ['cs'])]
function cs(
    #[AsOption(name: 'dry-run', description: 'Do not make changes and outputs diff', mode: InputOption::VALUE_NONE)]
    bool $dryRun,
): int {
    $command = 'tools/php-cs-fixer/vendor/bin/php-cs-fixer fix';

    if ($dryRun) {
        $command .= ' --dry-run --diff';
    }

    return exit_code($command);
}

#[AsTask(description: 'Run the phpstan analysis', aliases: ['phpstan'])]
function phpstan(): int
{
    return exit_code('tools/phpstan/vendor/bin/phpstan analyse -v');
}

#[AsTask(description: 'Run the phpunit tests', ignoreValidationErrors: true, aliases: ['phpunit'])]
function phpunit(#[AsRawTokens] array $rawTokens): int
{
    return exit_code(['tools/phpunit/vendor/bin/phpunit', ...$rawTokens]);
}

#[AsTask(description: 'Run the rector upgrade', aliases: ['rector'])]
function rector(): int
{
    return exit_code('tools/rector/vendor/bin/rector process');
}
