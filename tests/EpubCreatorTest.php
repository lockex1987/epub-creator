<?php

namespace Tests\Lockex1987\Epub;

use PHPUnit\Framework\TestCase;
use Lockex1987\Epub\EpubCreator;

class EpubCreatorTest extends TestCase
{
    public function testCreateEpub(): void
    {
        $title = 'Thư Kiếm Ân Cừu Lục';
        $author = 'Kim Dung';

        $chapters = [
            [
                'name' => 'Hồi 1: Núi hoang hào kiệt trừ ưng cẩu, Dọc đường tỷ kiếm gặp anh hùng',
                'content' => 'Test'
            ]
        ];
        $coverPath = 'assets/cover.jpg';
        $epubCreator = new EpubCreator();
        $epubCreator->createEpub($title, $author, $chapters, $coverPath);
    }
}
