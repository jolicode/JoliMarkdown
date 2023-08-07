<?php

namespace JoliMarkdown;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Query;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

class MarkdownConverter
{
    private const DIALOG_MIN_IMAGE_WIDTH = 1000;
    private const PUBLIC_DIR = '/public';

    public function __construct(
        private readonly MarkdownParser $docParser,
        private readonly HtmlRenderer $htmlRenderer,
    ) {
    }

    public function mdToHtml(string $markdown = null): string
    {
        if (!$markdown) {
            return '';
        }

        $document = $this->docParser->parse($markdown);
        $this->convertImageNodes($document);

        return $this->htmlRenderer->renderDocument($document);
    }

    private function convertImageNodes(Document $document): void
    {
        $imageNodes = (new Query())
            ->where(Query::type(Image::class))
            ->findAll($document)
        ;

        /** @var Image $image */
        foreach ($imageNodes as $image) {
            $this->convertImageNode($image);
        }
    }

    private function convertImageNode(Image $imageNode): void
    {
        // TODO: make this configurable
        $url = preg_replace('#^(https?)?://(www.)?jolicode.com/?#', '', $imageNode->getUrl(), -1, $count);

        if ($count > 0) {
            // absolute images served by jolicode.com are converted to relative images
            $imageNode->setUrl($url);
        }

        if (!$imageFilename = $this->getFilename($imageNode)) {
            // if we cannot get the local filename, we cannot convert the image node
            return;
        }

        $imageSize = @getimagesize($imageFilename);

        if (!$imageSize) {
            return;
        }

        if (!\in_array($imageSize['mime'], ['image/jpeg', 'image/png', 'image/gif'])) {
            return;
        }

        $imgNode = clone $imageNode;
        $imgNode->__clone();
        $imgNode->data->set('attributes/decoding', 'async');
        $imgNode->data->set('attributes/loading', 'lazy');

        // $imageSize[0] is the width of the image
        if ($imageSize[0] >= self::DIALOG_MIN_IMAGE_WIDTH) {
            $imgNode->data->append('attributes/class', 'c-dialog__target c-dialog__image js-dialog-target');
        }
        // set an aspect ratio to avoid repaints with the lazy loading
        // possibly existing style are *appended* to this default styles
        // in order to allow local overrides
        $existingStyle = $imgNode->data->get('attributes/style', '');
        $imgNode->data->set('attributes/style', sprintf(
            'width: %spx; height: auto; aspect-ratio: calc(%s / %s)%s',
            $imageSize[0],
            $imageSize[0],
            $imageSize[1],
            $existingStyle ? '; ' . $existingStyle : '',
        ));

        $imageNode->replaceWith($imgNode);
    }

    private function getFilename(Image $image): false|string
    {
        if (str_starts_with($image->getUrl(), 'http')) {
            // image with absolute path found, skipping it.
            return false;
        }

        if ('/' !== $image->getUrl()[0] && !str_starts_with($image->getUrl(), '://')) {
            $image->setUrl('/' . $image->getUrl());
        }

        // $imagePath = str_replace('//', '/', self::PUBLIC_DIR . $image->getUrl());
        $imagePath = preg_replace('/\/\//u', '/', self::PUBLIC_DIR . $image->getUrl());

        return rawurldecode($imagePath);
    }
}
