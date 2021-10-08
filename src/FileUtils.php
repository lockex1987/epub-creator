<?php

namespace Lockex1987\Epub;

class FileUtils
{
    /**
     * Xóa thư mục và tất cả các file trong đó.
     */
    public static function deleteFolder(string $folderPath): void
    {
        if (!file_exists($folderPath)) {
            return;
        }

        if (substr($folderPath, strlen($folderPath) - 1, 1) != '/') {
            $folderPath .= '/';
        }

        $a = glob($folderPath . '*', GLOB_MARK);
        foreach ($a as $f) {
            if (is_dir($f)) {
                self::deleteFolder($f);
            } else {
                unlink($f);
            }
        }

        rmdir($folderPath);
    }
}
