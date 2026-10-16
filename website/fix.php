<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Runs in the browser (WASM): reads the Markdown and the options as JSON on
// stdin, prints the fixed Markdown.

use JoliMarkdown\MarkdownFixer;
use League\CommonMark\Environment\Environment;

require __DIR__ . '/vendor/autoload.php';

// Warnings on stderr, not in the fixed Markdown: the embed SAPI ignores display_errors=stderr
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    $input = json_decode(file_get_contents('php://stdin'), true, flags: \JSON_THROW_ON_ERROR);

    $markdownFixer = new MarkdownFixer(new Environment([
        'joli_markdown' => [
            'unordered_list_marker' => $input['options']['unorderedListMarker'],
            'prefer_asterisk_over_underscore' => $input['options']['preferAsteriskOverUnderscore'],
            'internal_domains' => array_values(array_filter($input['options']['internalDomains'])),
        ],
    ]));

    echo $markdownFixer->fix($input['markdown']);
} catch (Throwable $e) {
    file_put_contents('php://stderr', $e->getMessage());
    exit(1);
}
