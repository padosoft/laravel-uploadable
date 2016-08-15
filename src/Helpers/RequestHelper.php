<?php

namespace Padosoft\Uploadable\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Illuminate\Http\Request helper
 * @package Padosoft\Uploadable\Helpers
 */
class RequestHelper
{
    /**
     * Check if the current request has at least one file
     * @return bool
     */
    public static function currentRequestHasFiles() : bool
    {
        return self::requestHasFiles(request());
    }

    /**
     * Check if the passed request has at least one file
     * @param Request $request
     * @return bool
     */
    public static function requestHasFiles(Request $request) : bool
    {
        return ($request && $request->allFiles() && count($request->allFiles()) > 0);
    }

    /**
     * Check if uploaded File in current request is valid and has a valid Mime Type.
     * Return true is all ok, otherwise return false.
     * @param string $uploadField
     * @param array $arrMimeType
     * @return bool
     */
    public static function isValidCurrentRequestUploadFile(string $uploadField, array $arrMimeType = array()) : bool
    {
        return self::isValidUploadFile($uploadField, $arrMimeType, request());
    }

    /**
     * Check if uploaded File is valid and has a valid Mime Type.
     * Return true is all ok, otherwise return false.
     * @param string $uploadField
     * @param array $arrMimeType
     * @param Request $request
     * @return bool
     */
    public static function isValidUploadFile(string $uploadField, array $arrMimeType = array(), Request $request) : bool
    {
        if (!$request) {
            return false;
        }

        $uploadedFile = self::getFileSafe($uploadField, [], $request);

        return UploadedFileHelper::isValidUploadFile($arrMimeType, $uploadedFile);
    }

    /**
     * Return File in Current Request if ok, otherwise return null
     * @param string $uploadField
     * @param array $arrMimeType
     * @return null|UploadedFile
     */
    public static function getCurrentRequestFileSafe(string $uploadField, array $arrMimeType = array()) : UploadedFile
    {
        return self::getFileSafe($uploadField, $arrMimeType, request());
    }

    /**
     * Return File in passed request if ok, otherwise return null
     * @param string $uploadField
     * @param array $arrMimeType
     * @param Request $request
     * @return null|UploadedFile
     */
    public static function getFileSafe(string $uploadField, array $arrMimeType = array(), Request $request) : UploadedFile
    {
        // Check if uploaded File is valid
        if (!self::isValidUploadFile($uploadField, $arrMimeType, $request)) {
            return null;
        }

        $uploadedFile = $request->file($uploadField);
        //check type because $request->file() return UploadedFile|array|null
        if(!is_a($uploadedFile, UploadedFile::class)){
            return null;
        }

        return $uploadedFile;
    }
}
