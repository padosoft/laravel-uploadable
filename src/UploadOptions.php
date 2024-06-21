<?php

namespace Padosoft\Uploadable;

use Illuminate\Support\Facades\Storage;

class UploadOptions
{
    /**
     * upload Create Dir Mode file Mask (default '0755')
     * @var string
     */
    public $uploadCreateDirModeMask = '0755';

    /**
     * If set to true, a $model->uploadFileNameSuffixSeparator.$model->id suffx are added to upload file name.
     * Ex.:
     * original uploaded file name: pippo.jpg
     * final name: 'pippo'.$model->uploadFileNameSuffixSeparator.$model->id.'jpg'
     * @var bool
     */
    public $appendModelIdSuffixInUploadedFileName = true;

    /**
     * If set to true, a $model->uploadFileNameSuffixSeparator.[field_name] suffx are added to upload file name.
     * Ex.:
     * original uploaded file name: pippo.jpg
     * field name: image_thumb
     * final name: 'pippo'.$model->uploadFileNameSuffixSeparator.$model->uploadFileNameSuffixSeparator.'.jpg'
     * @var bool
     */
    public $appendFieldSuffixInUploadedFileName = false;

    /**
     * Suffix separator to generate new file name
     * @var string
     */
    public $uploadFileNameSuffixSeparator = '_';

    /**
     * Array of upload attributes
     * Ex.:
     * public $uploads = ['image', 'image_mobile'];
     * @var array
     */
    public $uploads = [];

    /**
     * Array of Mime type string.
     * Ex. (default):
     * public $uploadsMimeType = [
     * 'image/gif',
     * 'image/jpeg',
     * 'image/png',
     * ];
     * A full listing of MIME types and their corresponding extensions may be found
     * at the following location: http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * @var array
     */
    public $uploadsMimeType = [
        'image/gif',
        'image/jpeg',
        'image/png',
    ];

    /**
     * upload base path relative to $storageDiskName root folder
     * @var string
     */
    public $uploadBasePath;

    /**
     * The default storage disk name
     * used for default upload base path
     * default: public
     * @var string
     */
    public $storageDiskName = 'public';

    /**
     * An associative array of 'attribute_name' => 'uploadBasePath'
     * set an attribute name here to override its default upload base path
     * The path is relative to $storageDiskName root folder.
     * Ex.:
     * public $uploadPaths = ['image' => 'product', 'image_thumb' => 'product/thumb'];
     * @var array
     */
    public $uploadPaths = [];

    /**
     * @return UploadOptions
     */
    public static function create(): UploadOptions
    {
        return new static();
    }

    /**
     * upload Create Dir Mode file Mask (Ex.: '0755')
     * @param string $mask
     * @return UploadOptions
     */
    public function setCreateDirModeMask(string $mask): UploadOptions
    {
        $this->uploadCreateDirModeMask = $mask;

        return $this;
    }

    /**
     * $model->uploadFileNameSuffixSeparator.$model->id suffx are added to upload file name.
     * Ex.:
     * original uploaded file name: pippo.jpg
     * final name: 'pippo'.$model->uploadFileNameSuffixSeparator.$model->id.'jpg'
     */
    public function appendModelIdSuffixInFileName(): UploadOptions
    {
        $this->appendModelIdSuffixInUploadedFileName = true;

        return $this;
    }

    /**
     */
    public function dontAppendModelIdSuffixInFileName(): UploadOptions
    {
        $this->appendModelIdSuffixInUploadedFileName = false;

        return $this;
    }

    public function appendFieldSuffixSuffixInFileName(): UploadOptions
    {
        $this->appendFieldSuffixInUploadedFileName = true;

        return $this;
    }

    /**
     */
    public function dontAppendFieldSuffixSuffixInFileName(): UploadOptions
    {
        $this->appendFieldSuffixInUploadedFileName = false;

        return $this;
    }

    /**
     * Suffix separator to generate new file name
     * @param string $suffix
     * @return UploadOptions
     */
    public function setFileNameSuffixSeparator(string $suffix): UploadOptions
    {
        $this->uploadFileNameSuffixSeparator = $suffix;

        return $this;
    }

    /**
     * Array of upload attributes
     * Ex.: ['image', 'image_mobile'];
     * @param array $attributes
     * @return UploadOptions
     */
    public function setUploadsAttributes(array $attributes): UploadOptions
    {
        $this->uploads = $attributes;

        return $this;
    }

    /**
     * Array of Mime type string.
     * Ex.:
     * [
     * 'image/gif',
     * 'image/jpeg',
     * 'image/png',
     * ];
     * A full listing of MIME types and their corresponding extensions may be found
     * at the following location: http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * @param array $mime
     * @return UploadOptions
     */
    public function setMimeType(array $mime): UploadOptions
    {
        $this->uploadsMimeType = $mime;

        return $this;
    }

    /**
     * upload base path. path relative to $storageDiskName root folder
     * Ex.: public/upload/news
     * @param string $path
     * @return UploadOptions
     */
    public function setUploadBasePath(string $path): UploadOptions
    {
        $this->uploadBasePath = canonicalize($path);

        return $this;
    }

    /**
     * An associative array of 'attribute_name' => 'uploadBasePath'
     * set an attribute name here to override its default upload base path
     * The path is relative to $storageDiskName root folder.
     * Ex.:
     * public $uploadPaths = ['image' => 'product', 'image_thumb' => 'product/thumb'];
     * @param array $attributesPaths
     * @return UploadOptions
     */
    public function setUploadPaths(array $attributesPaths): UploadOptions
    {
        array_map(function ($v) {
            return $v == '' ? $v : canonicalize($v);
        }, $attributesPaths);

        $this->uploadPaths = $attributesPaths;

        return $this;
    }

    /**
     * Set a Storage Disk name
     * @param string $diskName
     * @return UploadOptions
     */
    public function setStorageDisk(string $diskName)
    {
        $this->storageDiskName=$diskName;

        return $this;
    }

    /**
     * Get a Storage Disk
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public function getStorage() : \Illuminate\Filesystem\FilesystemAdapter
    {
        return Storage::disk($this->storageDiskName);
    }

    /**
     * Get the options for generating the upload.
     */
    public function getUploadOptionsDefault(): UploadOptions
    {
        return UploadOptions::create()
                            ->setCreateDirModeMask('0755')
                            ->appendModelIdSuffixInFileName()
                            ->setFileNameSuffixSeparator('_')
                            ->setUploadsAttributes(['image', 'image_mobile'])
                            ->setMimeType([
                                              'image/gif',
                                              'image/jpeg',
                                              'image/png',
                                          ])
                            ->setUploadBasePath( 'upload/')
                            ->setUploadPaths([])
                            ->setStorageDisk('public');
    }
}
