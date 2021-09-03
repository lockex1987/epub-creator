<?php

namespace Lockex1987\Epub;

use ZipArchive;

/**
 * https://packagist.org/packages/lockex1987/epub-creator
 */
class EpubCreator
{
    /**
     * Sinh nội dung file epub.
     * @param array $chapters Mảng [name, content]
     */
    public function createEpub(
        string $title,
        string $author,
        array $chapters,
        string $coverPath
    ): void {
        $bookId = $this->generateUuid4();
        [$coverWidth, $coverHeight] = getimagesize($coverPath);
        $language = 'vi-VN'; // chỗ file nav đã là tiếng Việt rồi

        for ($i = 0; $i < count($chapters); $i++) {
            // Trường fileName không bao gồm đuôi .xhtml
            $chapters[$i]['fileName'] = 'section-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        }

        $this->clean();

        file_put_contents('mimetype', 'application/epub+zip');

        $this->createFolderIfNotExist('./META-INF');
        $this->generateContainerFile();

        $this->createFolderIfNotExist('./OEBPS');
        $this->generateContentFile($title, $author, $language, $bookId, $chapters);
        $this->geneateTocFile($title, $bookId, $chapters);

        $this->createFolderIfNotExist('./OEBPS/text');
        $this->generateNavFile($chapters, $language);
        $this->generateCoverFile($coverWidth, $coverHeight);

        // Ghi nội dung file
        foreach ($chapters as $chapter) {
            $fileName = $chapter['fileName'];
            $content = $chapter['content'];
            file_put_contents('./OEBPS/text/' . $fileName . '.xhtml', $content);
        }

        $this->createFolderIfNotExist('./OEBPS/images');
        copy($coverPath, './OEBPS/images/cover.jpg');

        $this->createFolderIfNotExist('./OEBPS/css');
        $this->generateStyleFile();

        $outputFile = $this->convertVietnameseToLowerAscii($title) . '.epub';
        $this->writeEpubFile($outputFile);
        
        $this->clean();
    }

    private function createFolderIfNotExist(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path);
        }
    }

    /**
     * Sinh file META-INF/css/style.css.
     */
    private function generateStyleFile(): void
    {
        $text = <<<'CSS'
            .img-block {
                display: block;
                margin: 0 auto 1rem;
                text-align: center;
            }

            .mb-3 {
                margin-bottom: 1rem;
            }

            .text-center {
                text-align: center;
            }

            .font-italic {
                font-style: italic;
            }

            .font-weight-500 {
                font-weight: 500;
            }

            .font-weight-700 {
                font-weight: 700;
            }

            .font-size-1\.5 {
                font-size: 1.5rem;
            }

            aside {
                display: none;
            }
            CSS;
        file_put_contents('./OEBPS/css/style.css', $text);
    }

    /**
     * Sinh file META-INF/container.xml.
     */
    private function generateContainerFile(): void
    {
        $text = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <container version="1.0"
                xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
                <rootfiles>
                    <rootfile full-path="OEBPS/content.opf"
                        media-type="application/oebps-package+xml"/>
                </rootfiles>
            </container>
            XML;
        file_put_contents('./META-INF/container.xml', $text);
    }

    /**
     * Sinh file OEBPS/content.opf.
     */
    private function generateContentFile(string $title, string $author, string $language, string $bookId, array $chapters): void
    {
        $temp1 = implode("\n", array_map(function ($chapter) {
            return <<<XML
                    <item media-type="application/xhtml+xml" href="text/${chapter['fileName']}.xhtml" id="${chapter['fileName']}.xhtml" />
            XML;
        }, $chapters));
        $temp2 = implode("\n", array_map(function ($chapter) {
            return <<<XML
                    <itemref idref="${chapter['fileName']}.xhtml" />
            XML;
        }, $chapters));
        $text = <<<XML
            <?xml version="1.0"
                encoding="utf-8"
                standalone="no"?>
            <package prefix="rendition: http://www.idpf.org/vocab/rendition/#"
                version="3.0"
                unique-identifier="BookId"
                xmlns="http://www.idpf.org/2007/opf">
                <metadata xmlns:opf="http://www.idpf.org/2007/opf"
                    xmlns:dcterms="http://purl.org/dc/terms/"
                    xmlns:dc="http://purl.org/dc/elements/1.1/">
                    <dc:identifier id="BookId">$bookId</dc:identifier>
                    <dc:title>$title</dc:title>
                    <dc:creator>$author</dc:creator>
                    <dc:language>$language</dc:language>
                </metadata>

                <manifest>
                    <item media-type="application/x-dtbncx+xml" href="toc.ncx" id="toc.ncx" />

                    <item media-type="application/xhtml+xml" href="text/cover.xhtml" id="cover.xhtml" properties="svg" />
                    <item media-type="application/xhtml+xml" href="text/nav.xhtml" id="nav.xhtml" properties="nav" />

            $temp1

                    <item media-type="image/jpeg" href="images/cover.jpg" id="cover.jpg" properties="cover-image" />
                    <item media-type="text/css" href="css/style.css" id="style.css" />
                </manifest>

                <spine toc="toc.ncx">
                    <itemref idref="cover.xhtml" />
                    <itemref idref="nav.xhtml" />

            $temp2
                </spine>

                <guide>
                    <reference href="text/cover.xhtml" type="cover" title="Hình bìa" />
                </guide>
            </package>
            XML;
        file_put_contents('./OEBPS/content.opf', $text);
    }

    /**
     * Generate file OEBPS/text/nav.xhtml.
     */
    private function generateNavFile(array $chapters, string $language): void
    {
        $temp1 = implode("\n", array_map(function ($chapter) {
            return <<<XML
                        <li>
                            <a href="${chapter['fileName']}.xhtml">${chapter['name']}</a>
                        </li>
            XML;
        }, $chapters));
        $text = <<<XML
            <?xml version="1.0"
                encoding="utf-8"?>
            <!DOCTYPE html>
            <html xmlns="http://www.w3.org/1999/xhtml"
                xmlns:epub="http://www.idpf.org/2007/ops"
                lang="$language"
                xml:lang="$language">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>Mục lục</title>
            </head>

            <body epub:type="frontmatter">
                <h1>Mục lục</h1>
                <nav epub:type="toc"
                    id="toc">
                    <ul>
            $temp1
                    </ul>
                </nav>
            </body>
            </html>
            XML;
        file_put_contents('./OEBPS/text/nav.xhtml', $text);
    }

    /**
     * Generate file OEBPS/text/cover.xhtml.
     */
    private function generateCoverFile(int $coverWidth, int $coverHeight): void
    {
        $text = <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE html>
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>Cover</title>
            </head>

            <body>
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        version="1.1"
                        width="100%"
                        height="100%"
                        preserveAspectRatio="xMidYMid meet"
                        viewBox="0 0 $coverWidth $coverHeight">
                        <image xlink:href="../images/cover.jpg"
                            width="$coverWidth"
                            height="$coverHeight" />
                    </svg>
                </div>
            </body>
            </html>
            XML;
        file_put_contents('./OEBPS/text/cover.xhtml', $text);
    }

    /**
     * Sinh file OEBPS/toc.ncx.
     */
    private function geneateTocFile(string $title, string $bookId, array $chapters): void
    {
        $temp1 = implode("\n", array_map(function ($chapter, $idx) {
            $id = $idx + 2;
            return <<<XML
                    <navPoint id="navPoint{$id}" playOrder="${id}">
                        <navLabel>
                            <text>${chapter['name']}</text>
                        </navLabel>

                        <content src="text/${chapter['fileName']}.xhtml" />
                    </navPoint>
            XML;
        }, $chapters, array_keys($chapters)));
        $text = <<<XML
            <?xml version="1.0"
                encoding="utf-8"?>
            <ncx version="2005-1"
                xmlns="http://www.daisy.org/z3986/2005/ncx/">
                <head>
                    <meta name="dtb:uid" content="$bookId" />
                    <meta name="dtb:depth" content="1" />
                    <meta name="dtb:totalPageCount" content="0" />
                    <meta name="dtb:maxPageNumber" content="0" />
                </head>

                <docTitle>
                    <text>$title</text>
                </docTitle>

                <navMap>
                    <navPoint id="navPoint1" playOrder="1">
                        <navLabel>
                            <text>Cover</text>
                        </navLabel>

                        <content src="text/cover.xhtml" />
                    </navPoint>

            $temp1
                </navMap>
            </ncx>
            XML;
        file_put_contents('./OEBPS/toc.ncx', $text);
    }

    /**
     * Tạo file đầu ra Epub.
     */
    public function writeEpubFile(string $outZipPath): void
    {
        if (file_exists($outZipPath)) {
            unlink($outZipPath);
        }

        $zipFile = new ZipArchive();
        $zipFile->open($outZipPath, ZipArchive::CREATE);

        $zipFile->addFile('mimetype', 'mimetype');

        ZipUtils::zipDir('./META-INF', $zipFile);
        ZipUtils::zipDir('./OEBPS', $zipFile);

        $zipFile->close();
    }

    /**
     * Xóa các file trung gian.
     */
    public function clean(): void
    {
        if (file_exists('mimetype')) {
            unlink('mimetype');
        }
        FileUtils::deleteFolder('./META-INF');
        FileUtils::deleteFolder('./OEBPS');
    }

    /**
     * Sinh UUID (version 4).
     */
    private function generateUuid4(): string
    {
        // Generate 16 bytes (128 bits) of random data
        $data = random_bytes(16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Convert các ký tự tiếng Việt sang ASCII (không dấu) chữ thường.
     */
    private function convertVietnameseToLowerAscii(string $str): string
    {
        $str = mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        return $str;
    }
}
