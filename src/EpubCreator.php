<?php

namespace Lockex1987\Epub;

use ZipArchive;

/**
 * https://packagist.org/packages/lockex1987/epub-creator
 */
class EpubCreator
{
    // Tiêu đề
    private string $title;

    // Tác giả
    private string $author;

    // Mảng các chương
    // Mỗi phần tử gồm có name: string là tên và content: string là mã XHTML
    private array $chapters;

    // Đường dẫn ảnh bìa
    private string $coverPath;

    // ID sách (tự sinh)
    private string $bookId;

    // Ngôn ngữ
    private string $language;


    /**
     * Sinh nội dung file epub.
     * @param     
     */
    public function __construct(
        string $title,
        string $author,
        array $chapters,
        string $coverPath
    ) {
        $this->title = $title;
        $this->author = $author;
        $this->chapters = $chapters;
        $this->coverPath = $coverPath;

        $this->bookId = UuidUtils::generateUuid4();

        // Cố định là Tiếng Việt (chỗ file nav đã là tiếng Việt rồi)
        $this->language = 'vi-VN';

        [$coverWidth, $coverHeight] = getimagesize($this->coverPath);
        

        for ($i = 0; $i < count($this->chapters); $i++) {
            // Trường fileName không bao gồm đuôi .xhtml
            $this->chapters[$i]['fileName'] = 'section-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        }

        $this->clean();

        file_put_contents('mimetype', 'application/epub+zip');

        $this->createFolderIfNotExist('./META-INF');
        $this->generateContainerFile();

        $this->createFolderIfNotExist('./OEBPS');
        $this->generateContentFile();
        $this->geneateTocFile();

        $this->createFolderIfNotExist('./OEBPS/text');
        $this->generateCoverFile($coverWidth, $coverHeight);
        $this->generateJacketFile();
        $this->generateNavFile();

        // Ghi nội dung file
        foreach ($this->chapters as $chapter) {
            $fileName = $chapter['fileName'];
            $content = $chapter['content'];
            file_put_contents('./OEBPS/text/' . $fileName . '.xhtml', $content);
        }

        $this->createFolderIfNotExist('./OEBPS/images');
        copy($this->coverPath, './OEBPS/images/cover.jpg');

        $this->createFolderIfNotExist('./OEBPS/css');
        $this->generateStyleFile();

        return $this;
    }

    /**
     * Tạo thư mục nếu không tồn tại.
     */
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

            .jacket-page .title {
                margin-top: 40%;
                text-align: center;
                font-size: 1.5rem;
                margin-bottom: 3rem;
            }

            .jacket-page .author {
                text-align: center;
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
    private function generateContentFile(): void
    {
        $temp1 = implode("\n", array_map(function ($chapter) {
            return <<<XML
                    <item media-type="application/xhtml+xml" href="text/${chapter['fileName']}.xhtml" id="${chapter['fileName']}.xhtml" />
            XML;
        }, $this->chapters));
        $temp2 = implode("\n", array_map(function ($chapter) {
            return <<<XML
                    <itemref idref="${chapter['fileName']}.xhtml" />
            XML;
        }, $this->chapters));
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
                    <dc:identifier id="BookId">$this->bookId</dc:identifier>
                    <dc:title>$this->title</dc:title>
                    <dc:creator>$this->author</dc:creator>
                    <dc:language>$this->language</dc:language>
                </metadata>

                <manifest>
                    <item media-type="application/x-dtbncx+xml" href="toc.ncx" id="toc.ncx" />

                    <item media-type="application/xhtml+xml" href="text/cover.xhtml" id="cover.xhtml" properties="svg" />
                    <item media-type="application/xhtml+xml" href="text/jacket.xhtml" id="jacket.xhtml" />
                    <item media-type="application/xhtml+xml" href="text/nav.xhtml" id="nav.xhtml" properties="nav" />

            $temp1

                    <item media-type="image/jpeg" href="images/cover.jpg" id="cover.jpg" properties="cover-image" />
                    <item media-type="text/css" href="css/style.css" id="style.css" />
                </manifest>

                <spine toc="toc.ncx">
                    <itemref idref="cover.xhtml" />
                    <itemref idref="jacket.xhtml"/>
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
    private function generateNavFile(): void
    {
        $temp1 = implode("\n", array_map(function ($chapter) {
            return <<<XML
                        <li>
                            <a href="${chapter['fileName']}.xhtml">${chapter['name']}</a>
                        </li>
            XML;
        }, $this->chapters));
        $text = <<<XML
            <?xml version="1.0"
                encoding="utf-8"?>
            <!DOCTYPE html>
            <html xmlns="http://www.w3.org/1999/xhtml"
                xmlns:epub="http://www.idpf.org/2007/ops"
                lang="$this->language"
                xml:lang="$this->language">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>Mục lục</title>
                <link href="../css/style.css" rel="stylesheet" type="text/css" />
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
     * Generate file OEBPS/text/jacket.xhtml.
     */
    private function generateJacketFile(): void
    {
        $text = <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE html>
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>Bìa lót</title>
                <link href="../css/style.css" rel="stylesheet" type="text/css" />
            </head>

            <body class="jacket-page">
                <div class="title">$this->title</div>
                <div class="author">$this->author</div>
            </body>
            </html>
            XML;
        file_put_contents('OEBPS/text/jacket.xhtml', $text);
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
    private function geneateTocFile(): void
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
        }, $this->chapters, array_keys($this->chapters)));
        $text = <<<XML
            <?xml version="1.0"
                encoding="utf-8"?>
            <ncx version="2005-1"
                xmlns="http://www.daisy.org/z3986/2005/ncx/">
                <head>
                    <meta name="dtb:uid" content="$this->bookId" />
                    <meta name="dtb:depth" content="1" />
                    <meta name="dtb:totalPageCount" content="0" />
                    <meta name="dtb:maxPageNumber" content="0" />
                </head>

                <docTitle>
                    <text>$this->title</text>
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
     * @param string $outputFile    Đường dẫn file epub đầu ra
     */
    public function writeEpubFile(string $outputFile = '')
    {
        // Nếu người dùng không chỉ định file đầu ra thì tự sinh tên file từ tiêu đề
        if (!$outputFile) {
            $outputFile = CharacterUtils::convertVietnameseToLowerAscii($this->title) . '.epub';
        }

        if (file_exists($outputFile)) {
            unlink($outputFile);
        }

        $zipFile = new ZipArchive();
        $zipFile->open($outputFile, ZipArchive::CREATE);

        $zipFile->addFile('mimetype', 'mimetype');

        ZipUtils::zipDir('./META-INF', $zipFile);
        ZipUtils::zipDir('./OEBPS', $zipFile);

        $zipFile->close();

        $this->clean();
    }

    /**
     * Xóa các file trung gian.
     */
    private function clean(): void
    {
        if (file_exists('mimetype')) {
            unlink('mimetype');
        }
        FileUtils::deleteFolder('./META-INF');
        FileUtils::deleteFolder('./OEBPS');
    }
}
