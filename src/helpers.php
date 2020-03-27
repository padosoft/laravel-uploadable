<?php

/**
 * Copyright (c) Padosoft.com 2020.
 */

//TODO: move some this functions into padosoft/support or padosoft/laravel-support

if (!function_exists('getFilenameWithoutExtension')) {
    function getFileNameWithoutExtension($filePath){
        if ($filePath == '' || is_dir($filePath) || \Illuminate\Support\Str::endsWith($filePath,'/')) {
            return '';
        }

        $info = pathinfo($filePath, PATHINFO_FILENAME);

        if ($info == '.' && PATHINFO_FILENAME == PATHINFO_DIRNAME) {
            return '';
        }
        return ($info !== null && $info != '') ? $info : '';
    }
}
if (!function_exists('getUploadedFilenameWithoutExtension')) {

    /**
     * Return the file name of uploaded file (without path and witout extension).
     * Ex.: /public/upload/pippo.txt ritorna 'pippo'
     * @param UploadedFile $uploadedFile
     * @return string
     */
    function getUploadedFilenameWithoutExtension(\Illuminate\Http\UploadedFile $uploadedFile)
    {
        return getFilenameWithoutExtension($uploadedFile->getClientOriginalName());
    }
}
if (!function_exists('currentRequestHasFiles')) {
    /**
     * Check if the current request has at least one file
     * @return bool
     */
    function currentRequestHasFiles(): bool
    {
        return requestHasFiles(request());
    }
}

if (!function_exists('requestHasFiles')) {
    /**
     * Check if the passed request has at least one file
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    function requestHasFiles(\Illuminate\Http\Request $request): bool
    {
        return ($request && $request->allFiles() && count($request->allFiles()) > 0);
    }

}
if (!function_exists('hasValidMimeType')) {
    /**
     * Check if uploaded File has a correct MimeType if specified.
     * If $arrMimeType is empty array return true.
     *
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @param array $arrMimeType
     *
     * @return bool
     */
    function hasValidMimeType(\Illuminate\Http\UploadedFile $uploadedFile, array $arrMimeType): bool
    {
        return count($arrMimeType) > 0 ? in_array($uploadedFile->getMimeType(), $arrMimeType) : true;
    }
}
if (!function_exists('getCurrentRequestFileSafe')) {
    /**
     * Return File in Current Request if ok, otherwise return null
     *
     * @param string $uploadField
     *
     * @return array|null|\Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]
     */
    function getCurrentRequestFileSafe(string $uploadField)
    {
        return getRequestFileSafe($uploadField, request());
    }
}
if (!function_exists('getRequestFileSafe')) {
    /**
     * Return File in passed request if ok, otherwise return null
     *
     * @param string $uploadField
     * @param \Illuminate\Http\Request $request
     *
     * @return array|null|\Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]
     */
    function getRequestFileSafe(
        string $uploadField,
        \Illuminate\Http\Request $request
    ) {
        if (!$request) {
            return null;
        }

        $uploadedFile = $request->file($uploadField);

        //check type because request file method, may returns UploadedFile, array or null
        if (!is_a($uploadedFile, \Illuminate\Http\UploadedFile::class)) {
            return null;
        }

        return $uploadedFile;
    }
}
if (!function_exists('isValidUploadFile')) {
    /**
     * Check if uploaded File is valid and
     * has a valid Mime Type (only if $arrMimeType is not empty array).
     * Return true is all ok, otherwise return false.
     *
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @param array $arrMimeType
     *
     * @return bool
     */
    function isValidUploadFile(\Illuminate\Http\UploadedFile $uploadedFile, array $arrMimeType = array()): bool
    {
        if (empty($uploadedFile) || !$uploadedFile->isValid()) {
            return false;
        }

        return hasValidMimeType($uploadedFile, $arrMimeType);
    }
}
if (!function_exists('hasValidUploadFile')) {
    /**
     * Check if uploaded File is valid and has a valid Mime Type.
     * Return true is all ok, otherwise return false.
     *
     * @param string $uploadField
     * @param array $arrMimeType
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    function hasValidUploadFile(
        string $uploadField,
        \Illuminate\Http\Request $request,
        array $arrMimeType = array()
    ): bool {
        $uploadedFile = getRequestFileSafe($uploadField, $request);

        if (!is_a($uploadedFile, \Illuminate\Http\UploadedFile::class)) {
            return false;
        }

        return isValidUploadFile($uploadedFile, $arrMimeType);
    }
}
if (!function_exists('isValidCurrentRequestUploadFile')) {
    /**
     * Check if uploaded File in current request is valid and has a valid Mime Type.
     * Return true is all ok, otherwise return false.
     *
     * @param string $uploadField
     * @param array $arrMimeType
     *
     * @return bool
     */
    function isValidCurrentRequestUploadFile(string $uploadField, array $arrMimeType = array()): bool
    {
        return hasValidUploadFile($uploadField, request(), $arrMimeType);
    }
}
if (!function_exists('addFinalSlash')) {
    /**
     * If dir passed, check if finishes with '/' otherwise append a slash to path.
     * If wrong or empty string passed, return '/'.
     *
     * @param string $path
     *
     * @return string
     */
    function addFinalSlash(string $path): string
    {
        if ($path === null || $path == '') {
            return '/';
        }

        $quoted = preg_quote('/', '/');
        $path = preg_replace('/(?:' . $quoted . ')+$/', '', $path) . '/';

        return $path;
    }
}
if (!function_exists('removeStartSlash')) {
    /**
     * Remove start slash ('/') char in dir if starts with slash.
     *
     * @param string $directory
     *
     * @return string
     */
    function removeStartSlash($directory): string
    {
        if (\Illuminate\Support\Str::startsWith($directory, '/')) {
            $directory = substr($directory, 1);
        }

        return $directory;
    }
}
if (!function_exists('removeFinalSlash')) {
    /**
     * Remove final slash ('/') char in dir if ends with slash.
     *
     * @param $directory
     *
     * @return string
     */
    function removeFinalSlash($directory): string
    {
        if (\Illuminate\Support\Str::endsWith($directory, '/')) {
            $directory = substr($directory, 0, -1);
        }

        return $directory;
    }
}
if (!function_exists('njoin')) {
    /**
     * Joins a split file system path.
     *
     * @param array|string
     *
     * @return string
     * @see https://github.com/laradic/support/blob/master/src/Path.php
     */
    function joinPath(): string
    {
        $paths = func_get_args();
        if (func_num_args() === 1 && is_array($paths[0])) {
            $paths = $paths[0];
        }
        foreach ($paths as $key => &$argument) {
            if (is_array($argument)) {
                $argument = join($argument);
            }
            $argument = removeFinalSlash($argument);
            if ($key > 0) {
                $argument = removeStartSlash($argument);
            }
        }

        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}
if (!function_exists('njoin')) {
    /**
     * Similar to the joinPath() method, but also normalize()'s the result
     *
     * @param string|array
     *
     * @return string
     * @see https://github.com/laradic/support/blob/master/src/Path.php
     */
    function njoin(): string
    {
        return canonicalize(joinPath(func_get_args()));
    }
}
if (!function_exists('collapseDotFolder')) {
    /**
     * Collapse dot folder '.', '..', if possible
     *
     * @param string $root
     * @param $part
     * @param $canonicalParts
     */
    function collapseDotFolder($root, $part, &$canonicalParts): void
    {
        if ('.' === $part) {
            return;
        }
        // Collapse ".." with the previous part, if one exists
        // Don't collapse ".." if the previous part is also ".."
        if ('..' === $part && count($canonicalParts) > 0
            && '..' !== $canonicalParts[count($canonicalParts) - 1]
        ) {
            array_pop($canonicalParts);

            return;
        }
        // Only add ".." prefixes for relative paths
        if ('..' !== $part || '' === $root) {
            $canonicalParts[] = $part;
        }
    }
}
if (!function_exists('splitDir')) {
    /**
     * Splits a part into its root directory and the remainder.
     *
     * If the path has no root directory, an empty root directory will be
     * returned.
     *
     * If the root directory is a Windows style partition, the resulting root
     * will always contain a trailing slash.
     *
     * list ($root, $path) = DirHelpersplit("C:/webmozart")
     * // => array("C:/", "webmozart")
     *
     * list ($root, $path) = DirHelpersplit("C:")
     * // => array("C:/", "")
     *
     * @param string $path The canonical path to split
     *
     * @return string[] An array with the root directory and the remaining relative
     *               path
     * @see https://github.com/laradic/support/blob/master/src/Path.php
     */
    function splitDir($path)
    {
        if ('' === $path) {
            return ['', ''];
        }
        $root = '';
        $length = strlen($path);
        // Remove and remember root directory
        if ('/' === $path[0]) {
            $root = '/';
            $path = $length > 1 ? substr($path, 1) : '';
        } elseif ($length > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            if (2 === $length) {
                // Windows special case: "C:"
                $root = $path . '/';
                $path = '';
            } elseif ('/' === $path[2]) {
                // Windows normal case: "C:/"..
                $root = substr($path, 0, 3);
                $path = $length > 3 ? substr($path, 3) : '';
            }
        }
        return [$root, $path];
    }
}
if (!function_exists('canonicalize')) {
    /**
     * Canonicalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     * Furthermore, all "." and ".." segments are removed as far as possible.
     * ".." segments at the beginning of relative paths are not removed.
     *
     * ```php
     * echo DirHelper::canonicalize("\webmozart\puli\..\css\style.css");
     * // => /webmozart/style.css
     *
     * echo DirHelper::canonicalize("../css/./style.css");
     * // => ../css/style.css
     * ```
     *
     * This method is able to deal with both UNIX and Windows paths.
     *
     * @param string $path A path string
     *
     * @return string The canonical path
     * @see https://github.com/laradic/support/blob/master/src/Path.php
     */
    function canonicalize($path): string
    {
        $path = (string)$path;
        if ('' === $path) {
            return '';
        }
        $path = str_replace('\\', '/', $path);
        list ($root, $path) = splitDir($path);
        $parts = array_filter(explode('/', $path), 'strlen');
        $canonicalParts = [];
        // Collapse dot folder ., .., i f possible
        foreach ($parts as $part) {
            collapseDotFolder($root, $part, $canonicalParts);
        }

        // Add the root directory again
        return $root . implode('/', $canonicalParts);
    }
}
