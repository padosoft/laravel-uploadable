<?php

namespace Padosoft\Uploadable\Test\Integration;

use Illuminate\Http\UploadedFile;

/**
 * Class UploadedFileTestable
 * Trait to easy get an istance of Illuminate\Http\UploadedFile for testing.
 * @package Padosoft\Laravel\Request
 */
trait UploadedFileTestable
{
    /**
     * Implemented in PHPUnit
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     *
     */
    abstract public static function assertFileExists(string $filename, string $message = ''): void;

    /**
     * Create an instance of Illuminate\Http\UploadedFile for testing (param test=true).
     * Before creating UploadedFile class check if file exists with assertFileExists($fullPath).
     * @param string $fullPath
     * @param string $mimeType if empty try to resolve mimeType automatically.
     * @param int $errorCode default 0 (no error).
     * For all possible values see Symfony\Component\HttpFoundation\File\UploadedFile::getErrorMessage()
     * @return UploadedFile
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
     */
    public function getUploadedFileForTest(string $fullPath, string $mimeType = '', int $errorCode = 0) : UploadedFile
    {
        $this->assertFileExists($fullPath);

        $uploadedFile = new UploadedFile(
            $fullPath,
            pathinfo($fullPath, PATHINFO_BASENAME),
            ($mimeType === null || $mimeType == '') ? mime_content_type($fullPath) : $mimeType,
            //filesize($fullPath),
            $errorCode,
            true // true for test
        );
        return $uploadedFile;
    }

    /**
     * Take $fullPath existing file, copy it to system temp dir with random name and
     * Create an instance of Illuminate\Http\UploadedFile for testing (param test=true).
     * Before creating UploadedFile class check if file exists with assertFileExists($fullPath).
     * @param string $fullPath
     * @param string $mimeType if empty try to resolve mimeType automatically.
     * @param int $errorCode default 0 (no error).
     * For all possible values see Symfony\Component\HttpFoundation\File\UploadedFile::getErrorMessage()
     * @return UploadedFile
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
     */
    public function getUploadedFileForTestAndCopyInSysTempDir(
        string $fullPath,
        string $mimeType = '',
        int $errorCode = 0
    ) : UploadedFile
    {
        $this->assertFileExists($fullPath);

        $origname = pathinfo($fullPath, PATHINFO_BASENAME);
        $path = sys_get_temp_dir() . '/' . $origname;
        if (file_exists($path)) {
            unlink($path);
        }
        copy($fullPath, $path);

        $uploadedFile = $this->getUploadedFileForTest($path, $mimeType, $errorCode);
        return $uploadedFile;
    }
}
