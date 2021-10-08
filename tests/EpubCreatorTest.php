<?php

namespace Tests\Lockex1987\Epub;

use PHPUnit\Framework\TestCase;
use Lockex1987\Epub\EpubCreator;
use Lockex1987\Epub\CharacterUtils;

class EpubCreatorTest extends TestCase
{
    /**
     * Tạo file epub.
     */
    public function testCreateEpub(): void
    {
        $content = file_get_contents('assets/chapter-1.html');

        $title = 'Thư Kiếm Ân Cừu Lục';
        $author = 'Kim Dung';
        $chapters = [
            [
                'name' => 'Hồi 1: Núi hoang hào kiệt trừ ưng cẩu',
                'content' => $content
            ]
        ];
        $coverPath = 'assets/cover.jpg';

        $outputFile = CharacterUtils::convertVietnameseToLowerAscii($title) . '.epub';
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }

        (new EpubCreator($title, $author, $chapters, $coverPath))
            ->writeEpubFile($outputFile);

        $this->assertFileExists($outputFile);
    }
}
