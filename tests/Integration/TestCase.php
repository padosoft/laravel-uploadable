<?php

namespace Padosoft\Uploadable\Test\Integration;

use File;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Padosoft\Test\traits\ExceptionTestable;
use Padosoft\Test\traits\FileSystemTestable;
use Padosoft\Laravel\Request\UploadedFileTestable;


abstract class TestCase extends Orchestra
{
    use ExceptionTestable, FileSystemTestable, UploadedFileTestable;

    /**
     * @var \Padosoft\Uploadable\Test\Integration\TestModel
     */
    protected $testModel;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        //init files and paths needed for tests.
        $this->initFileAndPath(__DIR__);
    }

    protected function tearDown(): void
    {
        //remove created path during test
        $this->removeCreatedPathDuringTest(__DIR__);
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            //'database' => $this->getTempDirectory().'/database.sqlite',
            'prefix' => '',
        ]);
    }

    /**
     * @param  $app
     */
    protected function setUpDatabase(Application $app)
    {
     //   file_put_contents($this->getTempDirectory().'/database.sqlite', null);

        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->string('image_mobile')->nullable();
            $table->string('image_custom')->nullable();
        });
    }

    protected function initializeDirectory(string $directory)
    {
        return;
        /*
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
        */
    }

    public function getTempDirectory() : string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'temp';
    }

    public function getSysTempDirectory() : string
    {
        return sys_get_temp_dir();
    }
}
