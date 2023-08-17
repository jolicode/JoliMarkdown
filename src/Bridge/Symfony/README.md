# JoliMarkdownBundle

This bundle integrates [the JoliMarkdown library](https://github.com/jolicode/JoliMarkdown) into Symfony projects.

## Install

Register the bundle `JoliMarkdown\Bridge\Symfony\JoliMarkdownBundle` in your kernel:

```php
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

Once the bundle is loaded, a new service is made available: `joli_markdown.fixer` - it is an instance of `JoliMarkdown\MarkdownFixer` configured as per the bundle configuration.

You may use it as you wish:

```php
$markdown = <<<MARKDOWN
    # A Markdown document

    Some paragraph here
MARKDOWN;

$fixer = $container->get('joli_markdown.fixer');
$fixedMarkdown = $fixer->fix($markdown);
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
