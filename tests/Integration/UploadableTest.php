<?php

namespace Padosoft\Uploadable\Test\Integration;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use League\Flysystem\Exception;
use Padosoft\Io\DirHelper;
use Padosoft\Uploadable\UploadOptions;
use Padosoft\Laravel\Request\RequestHelper;
use Padosoft\Laravel\Request\UploadedFileHelper;
use Padosoft\Uploadable\InvalidOption;

class UploadableTest extends TestCase
{
    /** @test */
    public function dummy_test()
    {
        /*
        $model = TestModel::create();
        $model->name = 'test';
        $model->save();
        */

        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.txt');
        $this->assertFileExists($this->getSysTempDirectory().'/dummy.txt');

        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFile]);
        $result = RequestHelper::getFileSafe('image', $request);
        $this->assertInstanceOf(UploadedFile::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertEquals('dummy.txt', $result->getClientOriginalName());
        $result = RequestHelper::getFileSafe('', $request);
        $this->assertNull($result);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);
        $result = RequestHelper::getCurrentRequestFileSafe('image');
        $this->assertInstanceOf(UploadedFile::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertEquals('dummy.txt', $result->getClientOriginalName());
        $this->assertEquals('1', '1');
    }

    /**
     * @test
     */
    public function guardAgainstInvalidUploadOptionsTest()
    {
        $model = new TestModel();
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageRegExp('/^Could not determinate which fields should be/');
        $model->getUploadOptionsOrDefault()->uploads = [];
        $model->guardAgainstInvalidUploadOptions();
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageRegExp('/^Could not determinate which fields should be/');
        $model->getUploadOptionsOrDefault()->uploads = '';
        $model->guardAgainstInvalidUploadOptions();

        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageRegExp('/^Could not determinate uploadBasePath/');
        $model->getUploadOptionsOrDefault()->uploadBasePath = '';
        $model->guardAgainstInvalidUploadOptions();
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageRegExp('/^Could not determinate uploadBasePath/');
        $model->getUploadOptionsOrDefault()->uploadBasePath = null;
        $model->guardAgainstInvalidUploadOptions();
    }

    /**
     * @test
     */
    public function getUploadOptionsOrDefaultTest()
    {
        $model = new TestModel();
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals(true, $options->appendModelIdSuffixInUploadedFileName);
        $this->assertNotNull($model->getUploadOptionsOrDefault());

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->dontAppendModelIdSuffixInFileName();
            }
        };

        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals(false, $options->appendModelIdSuffixInUploadedFileName);
    }

    /**
     * @test
     */
    public function updateDbTest()
    {
        $model = new TestModel();
        $model->updateDb('image', '');
        $model->name='test';
        $model->save();
        $model->updateDb('image', 'dummy.txt');
        $this->assertEquals('dummy.txt', $model->first()->image);
        $model->updateDb('image', '');
        $this->assertEquals('', $model->first()->image);
    }

    /**
     * @test
     */
    public function setBlanckAttributeAndDBTest()
    {
        $model = new TestModel();
        $model->name='test';
        $model->image='dummy.txt';
        $model->save();
        $this->assertEquals('dummy.txt', TestModel::first()->image);
        $model->setBlanckAttributeAndDB('image');
        $this->assertEquals('', $model->image);
        $this->assertEquals('', TestModel::first()->image);
    }

    /**
     * @test
     */
    public function deleteUploadedFile()
    {
    }

    /**
     * @test
     */
    public function requestHasValidFilesAndCorrectPaths()
    {
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');
        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFile]);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);

        $model = new TestModel();
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals(true, $model->requestHasValidFilesAndCorrectPaths());

        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt','',UPLOAD_ERR_NO_FILE);
        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFile]);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);
        $this->assertEquals(true, $model->requestHasValidFilesAndCorrectPaths());
    }

    /**
     * @test
     */
    public function checkIfAllUploadFieldsAreEmpty()
    {
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploads = ['image', 'image_mobile'];
        $this->assertEquals(true, $model->checkIfAllUploadFieldsAreEmpty());
        $model->image='dummy.txt';
        $model->image_mobile='';
        $this->assertEquals(false, $model->checkIfAllUploadFieldsAreEmpty());
        $model->image='';
        $model->image_mobile='dummy.txt';
        $this->assertEquals(false, $model->checkIfAllUploadFieldsAreEmpty());
        $model->image='dummy.txt';
        $model->image_mobile='dummy.txt';
        $this->assertEquals(false, $model->checkIfAllUploadFieldsAreEmpty());
        $model->image='';
        $model->image_mobile='';
        $this->assertEquals(true, $model->checkIfAllUploadFieldsAreEmpty());
    }

    /**
     * @test
     */
    public function checkOrCreateAllUploadBasePaths()
    {
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploads = ['image', 'image_mobile'];
        $this->assertEquals(true, $model->checkOrCreateAllUploadBasePaths());
    }

    /**
     * @test
     */
    public function checkOrCreateAllUploadBasePath()
    {
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploads = ['image', 'image_mobile'];
        $this->assertEquals(true, $model->checkOrCreateUploadBasePath('image'));
        $this->assertEquals(true, $model->checkOrCreateUploadBasePath('image_mobile'));
    }

    /**
     * @test
     */
    public function getUploadsAttributesSafe()
    {
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploads = '';
        $this->assertEquals([], $model->getUploadsAttributesSafe());
        $model->getUploadOptionsOrDefault()->uploads = [];
        $this->assertEquals([], $model->getUploadsAttributesSafe());
        $model->getUploadOptionsOrDefault()->uploads = ['image', 'image_mobile'];
        $this->assertEquals(['image', 'image_mobile'], $model->getUploadsAttributesSafe());
    }

    /**
     * @test
     */
    public function checkOrCreateUploadBasePath()
    {
        $path = $this->getSysTempDirectory().DIRECTORY_SEPARATOR.'upload';
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploadBasePath = $path;
        $this->assertEquals(true, $model->checkOrCreateUploadBasePath('image'));
        $this->fileExists($path);
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'upload'.DIRECTORY_SEPARATOR.'dummy'];
        $this->assertEquals(true, $model->checkOrCreateUploadBasePath('image'));
        $this->fileExists(public_path('upload'.DIRECTORY_SEPARATOR.'dummy'));
        @unlink($path);
        @unlink(public_path('upload'.DIRECTORY_SEPARATOR.'dummy'));
    }

    /**
     * @test
     */
    public function getUploadFileBasePath()
    {
        $path = $this->getSysTempDirectory().DIRECTORY_SEPARATOR.'upload';
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploadBasePath = $path;
        $this->assertEquals(DirHelper::canonicalize($path), $model->getUploadFileBasePath('image'));
        @unlink(DirHelper::canonicalize($path));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy/'];
        $this->assertEquals(DirHelper::canonicalize(public_path('dummy/')), $model->getUploadFileBasePath('image'));
        @unlink(DirHelper::canonicalize(public_path('dummy/')));
    }

    /**
     * @test
     */
    public function getUploadFileBasePathSpecific()
    {
        $model = new TestModel();
        $this->assertEquals('', $model->getUploadFileBasePathSpecific('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy/'];
        $this->assertEquals(DirHelper::canonicalize(public_path('dummy/')), $model->getUploadFileBasePathSpecific('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => '/var/www/vhosts/dummy.com/upload/'];
        $this->assertEquals('/var/www/vhosts/dummy.com/upload', $model->getUploadFileBasePathSpecific('image'));
    }

    /**
     * @test
     */
    public function getUploadFileFullPath()
    {
        $model = new TestModel();
        $model->image = 'dummy.txt';
        $model->getUploadOptionsOrDefault()->uploadBasePath = public_path('dummy/');
        $this->assertEquals(DirHelper::canonicalize(public_path('dummy/').$model->image), $model->getUploadFileFullPath('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy2/'];
        $this->assertEquals(DirHelper::canonicalize(public_path('dummy2/').$model->image), $model->getUploadFileFullPath('image'));
    }

    /**
     * @test
     */
    public function getUploadFileUrl()
    {
        $model = new TestModel();
        $model->image = 'dummy.txt';
        $model->getUploadOptionsOrDefault()->uploadBasePath = public_path('dummy/');
        $this->assertEquals(URL::to('dummy/'.$model->image), $model->getUploadFileUrl('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy2/'];
        $this->assertEquals(URL::to('dummy2/'.$model->image), $model->getUploadFileUrl('image'));
    }

    /**
     * @test
     */
    public function isSlashOrEmptyDir()
    {
        $model = new TestModel();
        $this->assertEquals(true, $model->isSlashOrEmptyDir(''));
        $this->assertEquals(false, $model->isSlashOrEmptyDir(public_path('')));
        $this->assertEquals(true, $model->isSlashOrEmptyDir(DIRECTORY_SEPARATOR));
        $this->assertEquals(true, $model->isSlashOrEmptyDir('\/'));
    }

    /**
     * @test
     */
    public function removePublicPath()
    {
        $model = new TestModel();
        $this->assertEquals('', $model->removePublicPath(''));
        $this->assertEquals('', $model->removePublicPath(public_path('')));
        $this->assertEquals('', $model->removePublicPath(public_path('/')));
        $this->assertEquals(DirHelper::canonicalize(DIRECTORY_SEPARATOR.'upload/image'), $model->removePublicPath(public_path('upload/image')));
    }

    /**
     * @test
     */
    public function getUploadFileBaseUrl()
    {
        $model = new TestModel();
        $model->image = 'dummy.txt';
        $model->getUploadOptionsOrDefault()->uploadBasePath = public_path('dummy/');
        $this->assertEquals(URL::to('dummy/'), $model->getUploadFileBaseUrl('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy2/'];
        $this->assertEquals(URL::to('dummy2/'), $model->getUploadFileBaseUrl('image'));
    }

    /**
     * @test
     */
    public function generateNewUploadFileName()
    {
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->dontAppendModelIdSuffixInFileName()
                    ->setFileNameSuffixSeparator('-')
                    ;
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals('dummy.txt', $model->generateNewUploadFileName($uploadedFile, 'image'));

        $this->assertEquals('dummy.txt', $model->generateNewUploadFileName($uploadedFile, ''));

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->appendModelIdSuffixInFileName()
                    ->setFileNameSuffixSeparator('-')
                    ;
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals('', $model->generateNewUploadFileName($uploadedFile, 'image'));
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->save();
        $model->first();
        $this->assertEquals('dummy-'.$model->id.'.txt', $model->generateNewUploadFileName($uploadedFile, 'image'));
    }

    /**
     * @test
     */
    public function calcolateNewUploadFileName()
    {
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->dontAppendModelIdSuffixInFileName()
                    ->setFileNameSuffixSeparator('-')
                    ;
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals('', $model->calcolateNewUploadFileName($uploadedFile));

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->appendModelIdSuffixInFileName()
                    ->setFileNameSuffixSeparator('-')
                    ;
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $model->first();
        $this->assertEquals('dummy-'.$model->id.'.txt', $model->calcolateNewUploadFileName($uploadedFile));
    }

    /**
     * @test
     */
    public function generateAllNewUploadFileNameAndSetAttribute()
    {
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');
        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFile, 'image_mobile' => $uploadedFile]);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);

        $model = new TestModel();
        $model->generateAllNewUploadFileNameAndSetAttribute();
        $this->assertEquals('', $model->image);
        $model->first();
        $model->name='dummy.txt';
        $model->image='dummy.txt';
        $model->image_mobile='dummy.txt';
        $model->save();
        $model->image='';
        $model->image_mobile='';
        $options = $model->getUploadOptionsOrDefault();
        $model->generateAllNewUploadFileNameAndSetAttribute();
        $this->assertEquals('dummy_'.$model->id.'.txt', $model->image);
        $this->assertEquals('dummy_'.$model->id.'.txt', $model->image_mobile);

        $uploadedFileKO = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt','',1);
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt','',1);
        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFileKO, 'image_mobile' => $uploadedFile]);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);
        $model->image='';
        $model->image_mobile='';
        $model->generateAllNewUploadFileNameAndSetAttribute();
        $this->assertEquals('dummy_'.$model->id.'.txt', $model->image);
        $this->assertEquals('dummy_'.$model->id.'.txt', $model->image_mobile);
    }

    /**
     * @test
     */
    public function generateNewUploadFileNameAndSetAttribute()
    {
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');
        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFile]);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);

        $model = new TestModel();
        $model->generateNewUploadFileNameAndSetAttribute('');
        $this->assertEquals('', $model->image);
        $model->first();
        $model->name='dummy.txt';
        $model->image='dummy.txt';
        $model->save();
        $model->image='';
        $options = $model->getUploadOptionsOrDefault();
        $model->generateNewUploadFileNameAndSetAttribute('image');
        $this->assertEquals('dummy_'.$model->id.'.txt', $model->image);

        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt','',1);
        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFile]);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);
        $model->image='';
        $model->generateNewUploadFileNameAndSetAttribute('');
        $this->assertEquals('', $model->image);
    }

    /**
     * @test
     */
    public function uploadFilesTest()
    {
    }

    /**
     * @test
     */
    public function uploadFileTest()
    {
    }

    /**
     * @test
     */
    public function doUploadTest()
    {
    }

    /**
     * @test
     */
    public function deleteUploadedFiles()
    {
    }
}
