<?php

/*
 * This file is part of JoliCode's "markdown fixer" project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace website;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use PhpWasm\Stack;
use Symfony\Component\Console\Input\InputOption;

use function Castor\context;
use function Castor\fs;
use function Castor\http_request;
use function Castor\io;
use function Castor\run;
use function PhpWasm\build as build_wasm;
use function PhpWasm\run_code;

/**
 * @param list<string> $set
 */
#[AsTask(description: 'Builds the website: PHP as WASM with the fixer embedded, and the CSS')]
function build(
    #[AsOption(description: 'Given to "docker buildx bake --set", for a cache', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)]
    array $set = [],
): void {
    $root = \dirname(__DIR__);
    $app = $root . '/website/build/app';
    // The PHP that runs the fixer in the browser, see https://github.com/lyrixx/php-wasm-builder
    $stack = Stack::named('jolimarkdown')
        ->php('8.5.12')
        ->extensions('ctype', 'dom', 'mbstring', 'xml')
        ->embed('website/build/app') // shows up as /app in the WASM filesystem
        ->targets('web')
        ->outputDir('website/public/build');

    // What the WASM filesystem gets as /app
    fs()->remove($app);
    fs()->mirror($root . '/src', $app . '/src');
    fs()->copy($root . '/composer.json', $app . '/composer.json');
    fs()->copy($root . '/website/fix.php', $app . '/fix.php');
    foreach (glob($root . '/tests/data/*-in.md') as $example) {
        fs()->copy($example, $app . '/examples/' . basename($example));
    }
    run('composer install --no-dev --classmap-authoritative -n', context: context()->withWorkingDirectory($app));

    build_wasm([$stack], $set);

    $footer = http_request('GET', 'https://raw.githubusercontent.com/jolicode/oss-theme/refs/heads/main/snippet-joli-footer.html')->getContent();
    fs()->dumpFile($root . '/website/public/build/joli-footer.html', strtr($footer, [
        '#GITHUB_REPO' => 'jolicode/JoliMarkdown',
        '<!-- #SUBTITLE -->' => 'JoliMarkdown is licensed under <a href="https://github.com/jolicode/JoliMarkdown/blob/main/LICENSE.md" target="_blank" rel="noreferrer noopener" class="jf-link">MIT license</a>. Read more about it on <a href="https://jolicode.com/blog/introducing-jolimarkdown-an-attempt-to-make-markdown-content-more-robust-and-rigorous" target="_blank" rel="noreferrer noopener" class="jf-link">our blog</a>.',
    ]));

    run("npx --yes tailwindcss@3 -i website/app.css -o website/public/build/app.css --content 'website/public/*.{html,js}' --minify");

    // Runs the fixer from node, in the build for the web: checks the binary, the embed and the autoload
    $output = run_code($stack, 'require "/app/fix.php";', json_encode([
        'markdown' => "* a\n",
        'options' => ['unorderedListMarker' => '-', 'preferAsteriskOverUnderscore' => true, 'internalDomains' => []],
    ]));

    if ('- a' !== trim($output)) {
        throw new \RuntimeException(\sprintf('The fixer does not work in the WASM build, it gave: "%s".', $output));
    }

    io()->success('The website is built in website/public/.');
}

#[AsTask(description: 'Serves the website on http://localhost:9999')]
function serve(): void
{
    run('php -S localhost:9999 -t website/public');
}
