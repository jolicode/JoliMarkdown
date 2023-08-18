<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use JoliMarkdown\Bridge\Symfony\Validator\MarkdownValidator;
use JoliMarkdown\MarkdownFixer;
use League\CommonMark\Environment\Environment;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('joli_markdown.fixer', MarkdownFixer::class)
            ->args([
                abstract_arg('logger'),
                abstract_arg('environment'),
            ])
        ->set('joli_markdown.validator', MarkdownValidator::class)
            ->args([
                abstract_arg('fixer'),
            ])
            ->tag('validator.constraint_validator')
        ->set('joli_markdown.environment', Environment::class)
            ->args([
                abstract_arg('config'),
            ])
    ;
};
