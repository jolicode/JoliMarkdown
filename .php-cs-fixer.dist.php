<?php

$finder = (new PhpCsFixer\Finder())
    ->ignoreVCSIgnored(true)
    ->ignoreDotFiles(false)
    ->in(__DIR__)
    ->append([
        __FILE__,
    ])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP81Migration' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'concat_space' => ['spacing' => 'one'],
        'ordered_class_elements' => true, // Symfony(PSR12) override the default value, but we don't want
        'blank_line_before_statement' => true, // Symfony(PSR12) override the default value, but we don't want
        'phpdoc_to_comment' => ['ignored_tags' => ['var']],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'match', 'parameters']],
    ])
    ->setFinder($finder)
;
