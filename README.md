# JoliMarkdown

## Usage

```php
use JoliMarkdown\MarkdownFixer;

$markdown = <<<MARKDOWN
    # A sample Markdown document

    Some paragraph here with an image <img src="/image.png" alt="description" /> inside.
MARKDOWN;

$markdownFixer = new MarkdownFixer();
$fixedMarkdown = $markdownFixer->fix($markdown);
```

The code above will return a "markdownized" version of the input string:

```md
# A sample Markdown document

Some paragraph here with an image [description](/image.png) inside.
```

If you are using Symfony, you may want to read the [documentation for the associated bundle](src/Bridge/Symfony/README.md).

## Installation

```
composer require jolicode/jolimarkdown
```

## Configuration

Several configuration options are available as [League CommonMark](https://commonmark.thephpleague.com/) environment configuration options, to customize the behavior of the Markdown fixer:

```php
use JoliMarkdown\MarkdownFixer;
use League\CommonMark\Environment\Environment;

$markdown = <<<MARKDOWN
    - some
    - list
MARKDOWN;

$markdownFixer = new MarkdownFixer(new Environment([
    'joli_markdown' => [
        'unordered_list_marker' => '*',
    ],
]));
$fixedMarkdown = $markdownFixer->fix($markdown);

// outputs:
// * some
// * list
```

- `internal_domains`: an array of domains that are considered internal to the website. Whenever an image or a link URL is found, that sits under one of the listed domains, the URL will be converted to a relative one. Defaults to `[]`.
- `prefer_asterisk_over_underscore`: a boolean to indicate whether to prefer `*` over `_` for emphasis. Defaults to `true`.
- `unordered_list_marker`: a string to use as the marker for unordered lists. Defaults to `-`.

## Tests

Tests are located under the `tests` directory and are written using [PHPUnit](https://phpunit.de/):

```bash
castor qa:install
castor qa:phpunit
```

## Context

Markdown is a simple text syntax for writing structured documents. Since its creation in 2004, this syntax has aimed to offer an alternative, faster and simpler way of writing HTML documents for Web publishing. Over the ensuing years, Markdown syntax has evolved iteratively, without any formal, perfectly standardized specification. Various variants have emerged, but none has become a de facto standard.

One of the most robust alternatives, however, is [CommonMark](https://commonmark.org/), a Markdown variant that was formally specified in 2014 and has been evolving ever since.

Markdown / Commonmark are frequently used in the development world (documentation in the form of a markdown README file, adoption by many publishing platforms) and is often also employed for web publishing. It was, for example, the syntax chosen when the [JoliCode website](https://jolicode.com) was created in 2012, and is still used today to structure the various bodies of content (blog posts, customer references, technologies, team sheets, etc.).

However, over the last 12 years, our way of transforming Markdown content into HTML has changed: writing a few articles in pure HTML, then using a *client-side* javascript Markdown pre-processor (in the Web browser), then finally, over the last few years, migrating to the [`league/commonmark`](https://commonmark.thephpleague.com/) library, which allows you to transform Markdown into HTML on the server side, in PHP. This library was chosen because it is particularly complete, well-maintained, extensible and robust.

During the development of `league/commonmark`, extension mechanisms were added, to support different Markdown "extensions", i.e. to support syntax elements that are not part of the CommonMark standard, but bring syntactic flexibility to writers. For example, the [tables extension](https://commonmark.thephpleague.com/2.4/extensions/tables/#syntax) makes it possible to write tables in Markdown, with a lighter, more readable syntax, which is not possible in "standard" CommonMark.

One of the founding features of Markdown is its compatibility with HTML: in Markdown, it's perfectly valid to insert HTML tags into text, and these will simply be passed on as they are in the final HTML document. For example, you can write:

```markdown
# A Markdown document

<p>An HTML paragraph.</p>

A paragraph in Markdown.
```

Such a document will be rendered, in HTML, as follows:

```html
<h1>A Markdown document</h1>
<p>A paragraph in HTML.</p>
<p>A paragraph in Markdown.</p>
```

CommonMark's extension mechanism is therefore interesting, as it allows syntactic elements to be added that the extension will be able to interpret to generate rich, complex HTML output, without the end user (the editor) having to write HTML. This notion of extension is provided for in CommonMark (the [CommonMark specification](https://spec.commonmark.org/0.30/) is itself [written in CommonMark](https://github.com/commonmark/commonmark-spec/blob/master/spec.txt) and uses an extension to generate side-by-side rendering of Markdown syntax and the corresponding HTML output, as can be seen, for example, in the [tabs](https://spec.commonmark.org/0.30/#tabs) section).

On the JoliCode site, we've taken advantage of the flexibility of `league/commonmark` to enrich HTML rendering, over the years, so that we can write richer, more expressive, more visual Markdown documents. For example, we've added an extension to write footnotes, HTML tables, strikethrough text, add HTML attributes to external links, automatically add attributes to `<img>` tags, and so on.

In spite of this, over the past 12 years we have frequently written HTML code within Markdown articles, in order to meet certain needs:

- add CSS classes to HTML elements, to be able to style them differently (centering an image on the page, for example) ;
- insert code with CSS classes, to use a syntax highlighting library;
- create the HTML structure to position two images side by side ;
- etc.

Sometimes HTML code has been added because the author of an article was uncomfortable with certain arcana of markdown, and chose the most direct approach to be able to publish his content. The use of HTML may have been appropriate at the time, but as the possibilities offered by HTML change, so do its limits: whereas for elements written in markdown, we can now make the program in charge of HTML rendering evolve to take on board new HTML functionalities, we can't do this for elements written directly in HTML, which will remain frozen in time in the form their author has chosen.

For example, we'd like to be able to offer images in modern, higher-performance formats (such as webp, which is both smaller and of better quality) than those used just a few years ago. For these images, we also want to move away from the use of the `<img>` tag, and take advantage of `<picture>`, `<source>` tags, and attributes like `srcset`. For images that have been inserted into articles using Markdown syntax, we can upgrade the HTML rendering program to support these new formats and tags. For images that have been inserted in HTML, we can't do this, and so have to replace them manually - or leave them as they are, with the inconvenience of having to accept that the articles concerned use dated, less efficient technologies, which have an impact on both speed and the comfort offered to site users.

So we're looking for an approach to *correct* existing Markdown articles, replacing the HTML elements they contain with equivalent Markdown elements wherever possible without distorting the final HTML rendering.

An extension, available in `league/commonmark` [for a few years now](https://github.com/thephpleague/commonmark/pull/489), can specifically help us with this task: it's the [Attributes extension](https://commonmark.thephpleague.com/2.4/extensions/attributes/), which lets you add HTML attributes to Markdown elements. For example, you can write:

```markdown
{.block-class}
![An image](/path/to/image.jpg)

![Another image](/path/to/image.jpg){.image-class}
```

which will be rendered in HTML as follows:

```html
<p class="block-class"><img src="/path/to/image.jpg" alt="Une image"></p>
<p><img src="/path/to/image.jpg" alt="Another image" class="image-class"></p>
```

With the help of this extension, we'd like to be able to write a program which, for each Markdown article on the site, will:

- analyze the Markdown content of the article;
- identify HTML elements that can be replaced by equivalent Markdown elements;
- replace these HTML elements with Markdown elements, adding the necessary HTML attributes so that the final HTML rendering is identical to that of the original article.

This repository proposes a tool to achieve this goal, using the following overall approach:

- from an existing string (the initial Markdown content of the article), an abstract syntax tree (AST) is generated using the `league/commonmark` Markdown parser. This parser is specifically configured, with few extensions enabled, to be as close as possible to standard CommonMark syntax and to obtain an AST that contains (almost) only the basic syntactic elements of CommonMark ;
- the `league/commonmark` parser returns a `Document`, which is a hierarchy of `Nodes` (a `Node` being an element of the AST). Each node is typed (for example, a `Node` of type `Paragraph` represents a paragraph, a `Node` of type `Image` represents an image, etc.), and the HTML code parts are parsed in the form of `HtmlBlock` or `HtmlInline` nodes;
- this Document is then **corrected** via a set of correction classes. For example:
  - if a Node of type `FencedCode` has a CSS class attribute `language-php`, then this class is removed and instead the Node's `Info` attribute is updated with the value `php` ;
  - if a node of type `Image` has an absolute URL while the image is served by the JoliCode site, then this URL is replaced by a relative URL.
    Numerous tests can be used to check special cases and different situations.
  - ditto for HTML links;
  - for `HTMLBlock` and `HTMLInline` nodes, a special treatment is applied:
    - HTML content is loaded into a DOM tree
    - this tree is then recursively traversed in an attempt to reconstitute equivalent pure Markdown nodes. For example, if we find a `<p>` element in the DOM, we'll try to replace it with a Markdown Node of type `Paragraph`. Each time, the HTML attributes are transformed into attributes as proposed by the `league/commonmark` "Attributes" extension;
    - as a last resort, if at a given level of recursion we are unable to reconstitute a Markdown Node :
      - we use the `league/html-to-markdown` library to try and convert HTML into Markdown. This step is necessary to transform HTML elements into Markdown that are not supported by the correction classes we've implemented (for example, HTML tables: we don't offer a "Fixer" for the DOM element `<table>`).
      - the resulting string is returned as a new `HTMLBlock` or `HTMLInline` node, depending on the type of first-level node;
- finally, as a last step, the new `Document` thus corrected is "rendered" as a string, which is the corrected Markdown content of the original article. For this purpose, a set of Renderer classes have been written, heavily inspired by the [wnx/commonmark-markdown-renderer library](https://github.com/stefanzweifel/commonmark-markdown-renderer).

## License

This library is under MIT License. See the LICENSE file.
