<?php

namespace Padosoft\Uploadable\Test\Integration;

use Illuminate\Database\Eloquent\Model;
use Padosoft\Uploadable\Uploadable;
use Padosoft\Uploadable\UploadOptions;

class TestModel extends Model
{
    use Uploadable;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Retrive a specifice UploadOptions for this model, or return default UploadOptions
     * @return UploadOptions
     */
    public function getUploadOptions() : UploadOptions
    {
        if($this->uploadOptions){
            return $this->uploadOptions;
        }

        $this->uploadOptions = UploadOptions::create()->getUploadOptionsDefault()
            ->setUploadBasePath(public_path('upload/' . $this->getTable()))
            ->setUploadsAttributes(['image', 'image_mobile']);

        return $this->uploadOptions;
    }

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
