<?php

namespace Padosoft\Uploadable\Helpers;

use Illuminate\Http\UploadedFile;

/**
 * UploadedFile Helper Class
 * @package Padosoft\Uploadable\Helpers
 */
class UploadedFileHelper
{
    /**
     * Check if uploaded File is valid and has a valid Mime Type.
     * Return true is all ok, otherwise return false.
     * @param array $arrMimeType
     * @param UploadedFile $uploadedFile
     * @return bool
     */
    public static function isValidUploadFile(array $arrMimeType = array(), UploadedFile $uploadedFile) : bool
    {
        //check if is valid file
        if ($uploadedFile===null || empty($uploadedFile) || !$uploadedFile->isValid()) {
            return false;
        }

        // Check if uploaded File has a correct MimeType if specified.
        return self::hasValidMimeType($arrMimeType, $uploadedFile);
    }

    /**
     * Check if uploaded File has a correct MimeType if specified.
     * If $arrMimeType is empty array return true.
     * @param array $arrMimeType
     * @param UploadedFile $uploadedFile
     * @return bool
     */
    public static function hasValidMimeType(array $arrMimeType, UploadedFile $uploadedFile) : bool
    {
        return !(!empty($arrMimeType) && count($arrMimeType) > 0 && !in_array($uploadedFile->getMimeType(), $arrMimeType));
    }

    /**
     * Return the file name of uploaded file (without path and witout extension).
     * Ex.: \public\upload\pippo.txt ritorna 'pippo'
     * @param UploadedFile $uploadedFile
     * @return string
     */
    public static function getFilenameWithoutExtension(UploadedFile $uploadedFile)
    {
        return FileHelper::getFilenameWithoutExtension($uploadedFile->getClientOriginalName());
    }

}
