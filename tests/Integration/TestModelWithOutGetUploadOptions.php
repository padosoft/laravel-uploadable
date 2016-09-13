<?php

namespace Padosoft\Uploadable\Test\Integration;

use Illuminate\Database\Eloquent\Model;
use Padosoft\Uploadable\Uploadable;
use Padosoft\Uploadable\UploadOptions;

class TestModelWithOutGetUploadOptions extends Model
{
    use Uploadable;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Set the Uploadable trait options.
     * @param UploadOptions $uploadOptions
     * @return TestModel
     */
    public function setUploadOptions(UploadOptions $uploadOptions) : TestModel
    {
        $this->uploadOptions = $uploadOptions;

        return $this;
    }
}
