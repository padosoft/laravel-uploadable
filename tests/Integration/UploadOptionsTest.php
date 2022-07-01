<?php

namespace Padosoft\Uploadable\Test\Integration;

use Orchestra\Testbench\TestCase as Orchestra;
use Padosoft\Uploadable\UploadOptions;

class UploadOptionsTest extends Orchestra
{
    /** @test */
    public function getUploadOptionsDefaultTest()
    {
        $obj = UploadOptions::create()->getUploadOptionsDefault();
        $this->assertEquals(true, $obj->appendModelIdSuffixInUploadedFileName);
        $this->assertEquals('upload', $obj->uploadBasePath);
        $this->assertEquals(canonicalize(storage_path('app/public/upload/')), canonicalize($obj->getStorage()->path($obj->uploadBasePath)));
        $this->assertEquals('0755', $obj->uploadCreateDirModeMask);
        $this->assertEquals('_', $obj->uploadFileNameSuffixSeparator);
        $this->assertEquals([], $obj->uploadPaths);
        $this->assertEquals(['image', 'image_mobile'], $obj->uploads);
        $this->assertEquals(['image/gif','image/jpeg','image/png',], $obj->uploadsMimeType);
    }

    /** @test */
    public function all_setUploadOptionsTest()
    {
        $obj = UploadOptions::create()->getUploadOptionsDefault()
            ->dontAppendModelIdSuffixInFileName()
            ->setUploadBasePath(public_path('upload2'))
            ->setCreateDirModeMask('0777')
            ->setFileNameSuffixSeparator('-')
            ->setUploadPaths(['image2' => public_path('upload').'/image2', 'image_mobile2' => public_path('upload').'/image_mobile2'])
            ->setUploadsAttributes(['image2', 'image_mobile2'])
            ->setMimeType(['image/bmp','text/plain'])
        ;
        $this->assertEquals(false, $obj->appendModelIdSuffixInUploadedFileName);
        $this->assertEquals(canonicalize(public_path('upload2')), $obj->uploadBasePath);
        $this->assertEquals('0777', $obj->uploadCreateDirModeMask);
        $this->assertEquals('-', $obj->uploadFileNameSuffixSeparator);
        $this->assertEquals(['image2' => public_path('upload').'/image2', 'image_mobile2' => public_path('upload').'/image_mobile2'], $obj->uploadPaths);
        $this->assertEquals(['image2', 'image_mobile2'], $obj->uploads);
        $this->assertEquals(['image/bmp','text/plain'], $obj->uploadsMimeType);

        $obj->appendModelIdSuffixInFileName();
        $this->assertEquals(true, $obj->appendModelIdSuffixInUploadedFileName);
    }
}
