<?php

namespace Lockex1987\Epub;

use ZipArchive;

class ZipUtils
{
    /**
     * Thêm các file và thư mục con trong một thư mục vào file zip.
     * Hàm private gọi đệ quy.
     * @param string $folder    Tên thư mục
     * @param ZipArchive $zipFile    Đối tượng ZipArchive
     * @param int $exclusiveLength    Number of text to be exclusived from the file path.
     */
    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";

                $localPath = substr($filePath, $exclusiveLength);

                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Nén zip một thư mục (bao gồm chính nó).
     *
     * @param string $sourcePath    Đường dẫn thư mục
     * @param ZipArchive $zipFile    File zip
     */
    public static function zipDir(string $sourcePath, ZipArchive $zipFile): void
    {
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        self::folderToZip($sourcePath, $zipFile, strlen("$parentPath/"));
    }
}
