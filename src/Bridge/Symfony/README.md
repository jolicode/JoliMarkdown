# JoliMarkdownBundle

This bundle integrates [the JoliMarkdown library](https://github.com/jolicode/JoliMarkdown) into Symfony projects.

## Install

Register the bundle `JoliMarkdown\Bridge\Symfony\JoliMarkdownBundle` in your kernel:

```php
// config/bundles.php
  new JoliMarkdown\Bridge\Symfony\JoliMarkdownBundle(),
```

Define the fixer configuration (either in `config/packages/joli_markdown.yaml` or in `config.yml`):

```yaml
joli_markdown:
    internal_domains: ['example.com']
    prefer_asterisk_over_underscore: true
    unordered_list_marker: '-'
```

## Usage

Once the bundle is loaded, a new service is made available: `@JoliMarkdown\MarkdownFixer` - it is an instance of `JoliMarkdown\MarkdownFixer` configured as per the bundle configuration.

You may use it as you wish:

```php
$markdown = <<<MARKDOWN
    # A sample Markdown document

    Some paragraph here with an image <img src="/image.png" alt="description" /> inside.
MARKDOWN;

$fixer = $container->get('@JoliMarkdown\MarkdownFixer');
$fixedMarkdown = $fixer->fix($markdown);
```

The code above will return a "markdownized" version of the input string:

```md
# A sample Markdown document

Some paragraph here with an image[description](/image.png) inside.
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
