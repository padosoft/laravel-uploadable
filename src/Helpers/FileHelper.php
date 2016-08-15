<?php

namespace Padosoft\Uploadable\Helpers;

/**
 * Helper Class FileHelper
 * @package Padosoft\Uploadable\Helpers
 */
class FileHelper
{
    /**
     * Check if passed path exists or try to create it.
     * Return false if it fails to create it.
     * @param string $filedPath
     * @param string $modeMask Ex.: '0755'
     * @return bool
     */
    public static function checkDirExistOrCreate(string $filedPath, string $modeMask) : bool
    {
        if (!$filedPath) {
            return false;
        }

        return file_exists($filedPath)
        || (mkdir($filedPath, $modeMask, true) && is_dir($filedPath));
    }

    /**
     * Return the file name of file (without path and witout extension).
     * Ex.: \public\upload\pippo.txt return 'pippo'
     * @param string $filePath
     * @return string
     */
    public static function getFilenameWithoutExtension(string $filePath) : string
    {
        if(!$filePath){
            return '';
        }

        $info = pathinfo($filePath);

        return (is_array($info) && array_key_exists('filename', $info)) ? $info['filename'] : '';
    }

    /**
     * unlink file if exists.
     * Return false if exists and unlink fails.
     * @param string $filePath
     * @return bool
     */
    public static function unlinkSafe(string $filePath) : bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        return unlink($filePath);
    }
}
