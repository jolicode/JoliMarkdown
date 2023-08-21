# JoliMarkdownBundle

This bundle integrates [the JoliMarkdown library](https://github.com/jolicode/JoliMarkdown) into Symfony projects.

## Install

Register the bundle `JoliMarkdown\Bridge\Symfony\JoliMarkdownBundle` in your kernel:

```php
// config/bundles.php
return [
    // ...
    JoliMarkdown\Bridge\Symfony\JoliMarkdownBundle::class => ['all' => true],
];
```

Define the fixer configuration in `config/packages/joli_markdown.yaml`:

```yaml
joli_markdown:
    internal_domains: ['example.com']
    prefer_asterisk_over_underscore: true
    unordered_list_marker: '-'
```

## Usage

Once the bundle is loaded, a new service is made available: `JoliMarkdown\MarkdownFixer` - it is an instance of `JoliMarkdown\MarkdownFixer` configured as per the bundle configuration.

You may use it as you wish:

```php
use JoliMarkdown\MarkdownFixer;

class HelloController
{
    public function index(MarkdownFixer $fixer)
    {
        $markdown = <<<MARKDOWN
            # A sample Markdown document

            Some paragraph here with an image <img src="/image.png" alt="description" /> inside.
        MARKDOWN;

        $fixedMarkdown = $fixer->fix($markdown);
        // # A sample Markdown document

        // Some paragraph here with an image ![description](/image.png) inside.
    }
}
```

If you need dynamic configuration capabilities for the fixer, rather use the lower level library.

### Markdown validator

A markdown validator is available:

```php
<?php
// src/Entity/Article.php

use JoliMarkdown\Bridge\Symfony\Validator\Markdown;

class Article
{
    #[Markdown()]
    public string $markdownBody;
}
```
