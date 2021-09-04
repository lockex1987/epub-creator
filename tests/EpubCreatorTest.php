<?php

namespace Tests\Lockex1987\Epub;

use PHPUnit\Framework\TestCase;
use Lockex1987\Epub\EpubCreator;

class EpubCreatorTest extends TestCase
{
    public function testCreateEpub(): void
    {
        $content = <<<HTML
            <?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE html>
            <html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>Test title</title>
                <link href="../css/style.css" rel="stylesheet" type="text/css" />
            </head>
                <p>Test content</p>
            </html>
            HTML;
        
        $title = 'Thư Kiếm Ân Cừu Lục';
        $author = 'Kim Dung';

        $chapters = [
            [
                'name' => 'Hồi 1: Núi hoang hào kiệt trừ ưng cẩu',
                'content' => $content
            ]
        ];
        $coverPath = 'assets/cover.jpg';
        $epubCreator = new EpubCreator();
        $epubCreator->createEpub($title, $author, $chapters, $coverPath);
    }
}
