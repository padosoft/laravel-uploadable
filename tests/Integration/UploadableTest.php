<?php

namespace Padosoft\Uploadable\Test\Integration;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\Directory;
use Padosoft\Uploadable\UploadOptions;
use Padosoft\Uploadable\InvalidOption;

function is_uploaded_file($tmpName): bool
{
    return strpos($tmpName,'dummy')!==false;
}

class UploadableTest extends TestCase
{
    use RequestTestable;

    /** @test */
    public function dummy_test()
    {
        /*
        $model = TestModel::create();
        $model->name = 'test';
        $model->save();
        */

        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.txt');
        $this->assertFileExists($this->getSysTempDirectory() . '/dummy.txt');
        $request = Request::create('/', 'GET', [], [], ['image' => $uploadedFile]);
        $result = getRequestFileSafe('image', $request);

        $this->assertInstanceOf(UploadedFile::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertEquals('dummy.txt', $result->getClientOriginalName());
        $result = getRequestFileSafe('', $request);
        $this->assertNull($result);
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);
        $result = getCurrentRequestFileSafe('image');
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
        $this->expectExceptionMessageMatches('/^Could not determinate which fields should be/');
        $model->getUploadOptionsOrDefault()->uploads = [];
        $model->guardAgainstInvalidUploadOptions();
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageMatches('/^Could not determinate which fields should be/');
        $model->getUploadOptionsOrDefault()->uploads = '';
        $model->guardAgainstInvalidUploadOptions();

        $model->getUploadOptionsOrDefault()->uploads = 'image';

        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageMatches('/^Could not determinate uploadBasePath/');
        $model->getUploadOptionsOrDefault()->uploadBasePath = '';
        $model->guardAgainstInvalidUploadOptions();
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageMatches('/^Could not determinate uploadBasePath/');
        $model->getUploadOptionsOrDefault()->uploadBasePath = null;
        $model->guardAgainstInvalidUploadOptions();
    }

    /**
     * @test
     */
    public function guardAgainstInvalidUploadBasePathOptionsTest()
    {
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploads = ['image'];
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageMatches('/^Could not determinate uploadBasePath/');
        $model->getUploadOptionsOrDefault()->uploadBasePath = '';
        $model->guardAgainstInvalidUploadOptions();
        $this->expectException(InvalidOption::class);
        $this->expectExceptionMessageMatches('/^Could not determinate uploadBasePath/');
        $model->getUploadOptionsOrDefault()->uploadBasePath = null;
        $model->guardAgainstInvalidUploadOptions();
    }

    /**
     * @test
     */
    public function getUploadOptionsOrDefaultTest()
    {
        $model = new TestModelWithOutGetUploadOptions();
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals(true, $options->appendModelIdSuffixInUploadedFileName);
        $this->assertNotNull($model->getUploadOptionsOrDefault());

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
        $model->name = 'test';
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
        $model->name = 'test';
        $model->image = 'dummy.txt';
        $model->save();
        $this->assertEquals('dummy.txt', TestModel::first()->image);
        $model->setBlanckAttributeAndDB('image');
        $this->assertEquals('', $model->image);
        $this->assertEquals('', TestModel::first()->image);
    }

    /**
     * @test
     */
    public function requestHasValidFilesAndCorrectPaths()
    {
        $request = $this->getRequestAndBindItForUploadTest([]);
        $model = new TestModel();
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals(false, $model->requestHasValidFilesAndCorrectPaths());

        /*$uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $model = new TestModel();
        $options = $model->getUploadOptionsOrDefault();
        $options->setUploadBasePath(__DIR__ . '/resources/dummy.txt');
        $this->assertEquals(false, $model->requestHasValidFilesAndCorrectPaths());*/

        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $model = new TestModel();
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals(true, $model->requestHasValidFilesAndCorrectPaths());

        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt', '', UPLOAD_ERR_NO_FILE);
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
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
        $model->image = 'dummy.txt';
        $model->image_mobile = '';
        $this->assertEquals(false, $model->checkIfAllUploadFieldsAreEmpty());
        $model->image = '';
        $model->image_mobile = 'dummy.txt';
        $this->assertEquals(false, $model->checkIfAllUploadFieldsAreEmpty());
        $model->image = 'dummy.txt';
        $model->image_mobile = 'dummy.txt';
        $this->assertEquals(false, $model->checkIfAllUploadFieldsAreEmpty());
        $model->image = '';
        $model->image_mobile = '';
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
        $path = $this->getSysTempDirectory() . DIRECTORY_SEPARATOR . 'upload';
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploadBasePath = $path;
        $this->assertEquals(true, $model->checkOrCreateUploadBasePath('image'));
        $this->fileExists($path);
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'upload' . DIRECTORY_SEPARATOR . 'dummy'];
        $this->assertEquals(true, $model->checkOrCreateUploadBasePath('image'));
        $this->fileExists(public_path('upload' . DIRECTORY_SEPARATOR . 'dummy'));
        @unlink($path);
        @unlink(public_path('upload' . DIRECTORY_SEPARATOR . 'dummy'));
    }

    /**
     * @test
     */
    public function getUploadFileBasePath()
    {
        $path = $this->getSysTempDirectory() . DIRECTORY_SEPARATOR . 'upload';
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->uploadBasePath = $path;
        $this->assertEquals(canonicalize($path), $model->getUploadFileBasePath('image'));
        @unlink(canonicalize($path));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy/'];
        $this->assertEquals(canonicalize(storage_path('app/public/dummy/')), canonicalize($model->getUploadOptionsOrDefault()->getStorage()->path($model->getUploadFileBasePath('image'))));
        @unlink(canonicalize(public_path('dummy/')));
    }

    /**
     * @test
     */
    public function getUploadFileBasePathSpecific()
    {
        $model = new TestModel();
        $this->assertEquals('', $model->getUploadFileBasePathSpecific('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy/'];
        $this->assertEquals(canonicalize(storage_path('app/public/dummy/')),
            canonicalize($model->getUploadOptionsOrDefault()->getStorage()->path($model->getUploadFileBasePath('image'))));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'custom_upload/'];
        $this->assertEquals(canonicalize(storage_path('app/public/custom_upload/')), canonicalize($model->getUploadOptionsOrDefault()->getStorage()->path($model->getUploadFileBasePath('image'))));
    }

    /**
     * @test
     */
    public function getUploadFileFullPath()
    {
        $model = new TestModel();
        $model->image = 'dummy.txt';
        $model->getUploadOptionsOrDefault()->uploadBasePath = 'dummy/';
        $this->assertEquals(canonicalize(storage_path('app/public/dummy/' . $model->image)),
            $model->getUploadFileFullPath('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy2/'];
        $this->assertEquals(canonicalize(storage_path('app/public/dummy2/' . $model->image)),
            $model->getUploadFileFullPath('image'));
        $model->image = '/';
        $this->assertEquals(canonicalize(storage_path('app/public/dummy2/' . $model->image)),
            $model->getUploadFileFullPath('image'));
        $model->image = '';
        $this->assertEquals(canonicalize(storage_path('app/public/dummy2/' . $model->image)),
            $model->getUploadFileFullPath('image'));
    }

    /**
     * @test
     */
    public function getUploadFileUrl()
    {
        $model = new TestModel();
        $model->image = 'dummy.txt';
        $model->getUploadOptionsOrDefault()->uploadBasePath = 'dummy/';
        $this->assertEquals($model->getUploadOptionsOrDefault()->getStorage()->url('dummy/' . $model->image), $model->getUploadFileUrl('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy2/'];
        $this->assertEquals($model->getUploadOptionsOrDefault()->getStorage()->url('dummy2/' . $model->image), $model->getUploadFileUrl('image'));
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
    /*public function removePublicPath()
    {
        $model = new TestModel();
        $this->assertEquals('', $model->removePublicPath(''));
        $this->assertEquals('', $model->removePublicPath($model->getUploadOptionsOrDefault()->getStorage()->path('')));
        $this->assertEquals('', $model->removePublicPath($model->getUploadOptionsOrDefault()->getStorage()->path('/')));
        $this->assertEquals(canonicalize(DIRECTORY_SEPARATOR . 'upload/image'),
            $model->removePublicPath($model->getUploadOptionsOrDefault()->getStorage()->path('upload/image')));
    }*/

    /**
     * @test
     */
    public function getUploadFileBaseUrl()
    {
        $model = new TestModel();
        $model->image = 'dummy.txt';
        $model->getUploadOptionsOrDefault()->uploadBasePath = 'dummy/';

        $this->assertEquals($model->getUploadOptionsOrDefault()->getStorage()->url('dummy/'), $model->getUploadFileBaseUrl('image'));
        $model->getUploadOptionsOrDefault()->uploadPaths = ['image' => 'dummy2/'];
        $this->assertEquals($model->getUploadOptionsOrDefault()->getStorage()->url('dummy2/'), $model->getUploadFileBaseUrl('image'));
        $model->image = '/';
        $this->assertEquals($model->getUploadOptionsOrDefault()->getStorage()->url('dummy2/'), $model->getUploadFileBaseUrl('image'));
        $model->image = '';
        $this->assertEquals($model->getUploadOptionsOrDefault()->getStorage()->url('dummy2/'), $model->getUploadFileBaseUrl('image'));
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
                    ->setFileNameSuffixSeparator('-');
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals('dummy.txt', $model->generateNewUploadFileName($uploadedFile));

        $this->assertEquals('dummy.txt', $model->generateNewUploadFileName($uploadedFile));

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->appendModelIdSuffixInFileName()
                    ->setFileNameSuffixSeparator('-');
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals('', $model->generateNewUploadFileName($uploadedFile));
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->save();
        $model->first();
        $this->assertEquals('dummy-' . $model->id . '.txt', $model->generateNewUploadFileName($uploadedFile));

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->appendModelIdSuffixInFileName()
                    ->appendFieldSuffixSuffixInFileName()
                    ->setFileNameSuffixSeparator('-');
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $this->assertEquals('', $model->generateNewUploadFileName($uploadedFile));
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->save();
        $model->first();
        $this->assertEquals('dummy-' . $model->id .'-image'. '.txt', $model->generateNewUploadFileName($uploadedFile,'image'));
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
                    ->setFileNameSuffixSeparator('-');
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
                    ->setFileNameSuffixSeparator('-');
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $model->first();
        $this->assertEquals('dummy-' . $model->id . '.txt', $model->calcolateNewUploadFileName($uploadedFile));

        $model = new class extends TestModel
        {
            public function getUploadOptions(): UploadOptions
            {
                return UploadOptions::create()->getUploadOptionsDefault()
                    ->appendModelIdSuffixInFileName()
                    ->appendFieldSuffixSuffixInFileName()
                    ->setFileNameSuffixSeparator('-');
            }
        };
        $options = $model->getUploadOptionsOrDefault();
        $model->first();
        $this->assertEquals('dummy-' . $model->id . '-image'.'.txt', $model->calcolateNewUploadFileName($uploadedFile,'image'));
    }

    /**
     * @test
     */
    public function generateAllNewUploadFileNameAndSetAttribute()
    {
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile, 'image_mobile' => $uploadedFile]);

        $model = new TestModel();
        $model->generateAllNewUploadFileNameAndSetAttribute();
        $this->assertEquals('', $model->image);
        $model->first();
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->image_mobile = 'dummy.txt';
        $model->save();
        $model->image = '';
        $model->image_mobile = '';
        $options = $model->getUploadOptionsOrDefault()->appendFieldSuffixSuffixInFileName();
        $model->generateAllNewUploadFileNameAndSetAttribute();
        $this->assertEquals('dummy_' . $model->id . '_image.txt', $model->image);
        $this->assertEquals('dummy_' . $model->id . '_image_mobile.txt', $model->image_mobile);

        $uploadedFileKO = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt', '', 1);
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt', '', 1);
        $request = $this->getRequestAndBindItForUploadTest([
            'image' => $uploadedFileKO,
            'image_mobile' => $uploadedFile
        ]);
        $model->image = '';
        $model->image_mobile = '';
        $model->generateAllNewUploadFileNameAndSetAttribute();
        $this->assertEquals('dummy_' . $model->id . '_image.txt', $model->image);
        $this->assertEquals('dummy_' . $model->id . '_image_mobile.txt', $model->image_mobile);
    }

    /**
     * @test
     */
    public function generateNewUploadFileNameAndSetAttribute()
    {
        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);

        $model = new TestModel();
        $model->generateNewUploadFileNameAndSetAttribute('');
        $this->assertEquals('', $model->image);
        $model->first();
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->save();
        $model->image = '';
        $options = $model->getUploadOptionsOrDefault();
        $model->generateNewUploadFileNameAndSetAttribute('image');
        $this->assertEquals('dummy_' . $model->id . '.txt', $model->image);

        $uploadedFile = $this->getUploadedFileForTest(__DIR__ . '/resources/dummy.txt', '', 1);
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $model->image = '';
        $model->generateNewUploadFileNameAndSetAttribute('');
        $this->assertEquals('', $model->image);
    }

    /**
     * @test
     */
    public function uploadFilesTest()
    {
        $model = new TestModel();
        Storage::fake('dummy');
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();
        $model->image = '';
        $model->uploadFiles();
        $this->assertEquals('', $model->image);
        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.txt');
        $uploadedFile2 = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.csv');
        $options->setMimeType([
            mime_content_type(__DIR__ . '/resources/dummy.txt'),
            mime_content_type(__DIR__ . '/resources/dummy.csv')
        ]);
        $request = $this->getRequestAndBindItForUploadTest([
            'image' => $uploadedFile,
            'image_mobile' => $uploadedFile2
        ]);

        $options->setUploadBasePath( 'upload');
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->image_mobile = 'dummy.csv';
        $model->save();

        $this->assertTrue($model->id > 0);
        $this->assertEquals('dummy_' . $model->id . '.txt', $model->image);
        $this->assertEquals('dummy_' . $model->id . '.csv', $model->image_mobile);
        //$this->assertFileExists(njoin($options->uploadBasePath, $model->image));
        //$this->assertFileExists(njoin($options->uploadBasePath, $model->image_mobile));
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image_mobile));
        /*@unlink(njoin($options->uploadBasePath, $model->image));
        @unlink(njoin($options->uploadBasePath, $model->image_mobile));
        @unlink($options->uploadBasePath);*/
    }

    /**
     * @test
     */
    public function uploadFileTest()
    {
        $model = new TestModel();
        Storage::fake('dummy');
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();

        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.txt');
        $options->setMimeType([mime_content_type(__DIR__ . '/resources/dummy.txt')]);
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);


        $options->setUploadBasePath( 'upload');
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->save();
        //dump(Storage::disk('dummy')->get(''));
        $this->assertTrue($model->id > 0);
        $this->assertEquals('dummy_' . $model->id . '.txt', $model->image);
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));

        //update model and change image
        $model = TestModel::first();
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();
        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.csv');
        $options->setMimeType([mime_content_type(__DIR__ . '/resources/dummy.csv')]);
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $model->name = 'dummy.csv';
        $model->image = 'dummy.csv';

        $model->save();
        $this->assertTrue($model->id > 0);
        $this->assertEquals('dummy_' . $model->id . '.csv', $model->image);
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));
        Storage::disk('dummy')->assertMissing(njoin($options->uploadBasePath,  'dummy_' . $model->id . '.txt'));
    }

    /**
     * @test
     */
    public function checkIfNeedToDeleteOldFiles()
    {
        //copy csv to tmp dir and set image property
        Storage::fake('dummy');
        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.csv');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $this->assertFileExists(njoin($this->getSysTempDirectory(), 'dummy.csv'));
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();
        $options->setUploadBasePath( 'upload');
        $options->setMimeType([mime_content_type(__DIR__ . '/resources/dummy.txt')]);
        $model->name = 'dummy.csv';
        $model->image = 'dummy.csv';
        $model->save();
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));

        //simulate another upload that overwrite previuous file
        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.txt');
        $options->setMimeType([mime_content_type(__DIR__ . '/resources/dummy.txt')]);
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $model->name = 'dummy.txt';
        $model->image = 'dummy.txt';
        $model->save();
        $this->assertTrue($model->id > 0);
        $this->assertEquals('dummy_' . $model->id . '.txt', $model->image);
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));
        Storage::disk('dummy')->assertMissing(njoin($options->uploadBasePath, 'dummy_' . $model->id . '.csv'));



    }

    /**
     * @test
     */
    public function doUploadTest()
    {
        //UPLOAD
        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.csv');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $this->assertFileExists(njoin($this->getSysTempDirectory(), 'dummy.csv'));
        $model = new TestModel();
        Storage::fake('dummy');
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();
        $options->setUploadBasePath( 'upload');
        $options->setMimeType([mime_content_type(__DIR__ . '/resources/dummy.csv')]);
        $model->image = 'pippo.csv';
        $newName = $model->doUpload($uploadedFile, 'image');
        $this->assertTrue($newName != '');
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));

        $newName = $model->doUpload($uploadedFile, '');
        $this->assertEquals('', $newName);

    }

    /**
     * @test
     */
    public function deleteUploadedFiles()
    {
        //UPLOAD
        Storage::fake('dummy');
        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.csv');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $this->assertFileExists(njoin($this->getSysTempDirectory(), 'dummy.csv'));
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();
        $options->setUploadBasePath('upload');
        $options->setMimeType([mime_content_type(__DIR__ . '/resources/dummy.csv')]);
        $model->name = 'dummy.csv';
        $model->image = 'dummy.csv';
        $model->save();
        $this->assertTrue($model->id > 0);
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));

        //RETRIVE AND DELETE
        $model = TestModel::first();
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();
        $options->setUploadBasePath('upload');
        $oldImage = $model->image;
        $model->delete();
        Storage::disk('dummy')->assertMissing(njoin($options->uploadBasePath, $oldImage));

        //UPLOAD
        $uploadedFile = $this->getUploadedFileForTestAndCopyInSysTempDir(__DIR__ . '/resources/dummy.csv');
        $request = $this->getRequestAndBindItForUploadTest(['image' => $uploadedFile]);
        $this->assertFileExists(njoin($this->getSysTempDirectory(), 'dummy.csv'));
        $model = new TestModel();
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $options = $model->getUploadOptionsOrDefault();
        $options->setUploadBasePath( 'upload');
        $options->setMimeType([mime_content_type(__DIR__ . '/resources/dummy.csv')]);
        $model->name = 'dummy.csv';
        $model->image = 'dummy.csv';
        $model->save();
        $this->assertTrue($model->id > 0);
        Storage::disk('dummy')->assertExists(njoin($options->uploadBasePath, $model->image));

        //RETRIVE SET ATTRIB TO BLANK AND DELETE
        $model = TestModel::first();
        $model->getUploadOptionsOrDefault()->setStorageDisk('dummy');
        $this->assertTrue($model->id > 0);
        $options = $model->getUploadOptionsOrDefault();
        $options->setUploadBasePath( 'upload');
        //set to empty image attribute!
        $oldImage = $model->image;
        $model->image = '';
        $model->delete();
        Storage::disk('dummy')->assertMissing(njoin($options->uploadBasePath, $oldImage));

        @unlink(njoin($options->uploadBasePath, $model->image));
        @unlink(njoin($options->uploadBasePath, 'dummy_' . $model->id . '.csv'));
        @unlink($options->uploadBasePath);
    }
}
