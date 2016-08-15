<?php

namespace Padosoft\Uploadable;

use DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Padosoft\Uploadable\Helpers\FileHelper;
use Padosoft\Uploadable\Helpers\RequestHelper;
use Padosoft\Uploadable\Helpers\UploadedFileHelper;

/**
 * Class Uploadable
 * Auto upload and save files on save/create/delete model.
 * @package Padosoft\Uploadable
 */
trait Uploadable
{
    /** @var UploadOptions */
    protected $uploadOptions;

    /**
     * Boot the trait.
     */
    public static function bootUploadable()
    {
        static::creating(function ($model) {
            $model->uploadOptions = $model->getUploadOptionsOrDefault();
            $model->guardAgainstInvalidUploadOptions();
        });

        static::saving(function (Model $model) {
            $model->generateAllNewUploadFileNameAndSetAttribute();
        });
        static::saved(function (Model $model) {
            $model->uploadFiles();
        });

        static::updating(function (Model $model) {
            $model->generateAllNewUploadFileNameAndSetAttribute();
        });
        static::updated(function (Model $model) {
            $model->uploadFiles();
        });

        static::deleting(function (Model $model) {
            $model->uploadOptions = $model->getUploadOptionsOrDefault();
            $model->guardAgainstInvalidUploadOptions();
        });

        static::deleting(function (Model $model) {
            $model->uploadOptions = $model->getUploadOptionsOrDefault();
            $model->guardAgainstInvalidUploadOptions();
        });

        static::deleted(function (Model $model) {
            $model->deleteUploadedFiles();
        });
    }

    /**
     * Retrive a specifice UploadOptions for this model, or return default UploadOptions
     * @return UploadOptions
     */
    public function getUploadOptionsOrDefault() : UploadOptions
    {
        if (method_exists($this, 'getUploadOptions')) {
            $method = 'getUploadOptions';
            return $this->{$method}();
        } else {
            return UploadOptions::create()->getUploadOptionsDefault()
                ->setUploadBasePath(public_path('upload/' . $this->getTable()));
        }
    }

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     * @throws InvalidOption
     */
    public function guardAgainstInvalidUploadOptions()
    {
        if (!count($this->uploadOptions->uploads)) {
            throw InvalidOption::missingUploadFields();
        }
        if (!strlen($this->uploadOptions->uploadBasePath)) {
            throw InvalidOption::missingUploadBasePath();
        }
    }

    /**
     * Handle file upload.
     */
    public function uploadFiles()
    {
        //invalid model
        if ($this->id < 1) {
            return;
        }

        //current request has not uploaded files
        if (!RequestHelper::currentRequestHasFiles()) {
            return;
        }

        //ensure that all upload path are ok or create it.
        if (!$this->checkOrCreateAllUploadBasePaths()) {
            return;
        }

        //loop for every upload model attributes and do upload if has a file in request
        foreach ($this->getUploadsAttributesSafe() as $uploadField) {
            $this->uploadFile($uploadField);
        }
    }

    /**
     * Upload a file releted to a passed attribute name
     * @param string $uploadField
     */
    public function uploadFile(string $uploadField)
    {
        //check if there is a file in request for current attribute
        $uploadedFile = RequestHelper::getCurrentRequestFileSafe($uploadField, $this->getUploadOptionsOrDefault()->uploadsMimeType);
        if (!$uploadedFile) {
            return;
        }

        //all ok => do upload
        $this->doUpload($uploadedFile, $uploadField);
    }

    /**
     * Get an UploadedFile, generate new name, and save it in destination path.
     * Return empty string if it fails, otherwise return the saved file name.
     * @param UploadedFile $uploadedFile
     * @param string $uploadAttribute
     * @return string
     */
    public function doUpload(UploadedFile $uploadedFile, $uploadAttribute) : string
    {
        if ($this->id < 1) {
            return '';
        }

        if (!$uploadedFile || !$uploadAttribute) {
            return '';
        }

        //get file name by attribute
        $newName = $this->{$uploadAttribute};

        //get upload path to store
        $pathToStore = $this->getUploadFilePath($uploadAttribute);

        //delete if file already exists
        FileHelper::unlinkSafe($pathToStore . '/' . $newName);

        //move uploaded file to destination folder
        try {
            $targetFile = $uploadedFile->move($pathToStore, $newName);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
            Log::warning('Error in doUpload() when try to move '.$newName.' to folder: '.$pathToStore.PHP_EOL.$e->getMessage().PHP_EOL.$e->getTraceAsString());
            return '';
        }

        return $targetFile ? $newName : '';
    }

    /**
     * Generate a new file name for uploaded file.
     * Return empty string if uploadedFile is null, otherwise return the new file name..
     * @param UploadedFile $uploadedFile
     * @param string $uploadField
     * @return string
     */
    public function generateNewUploadFileName(UploadedFile $uploadedFile, string $uploadField) : string
    {
        if (!$uploadField) {
            return '';
        }
        if (!$uploadedFile) {
            return '';
        }

        //check if file need a new name
        $newName = $this->calcolateNewUploadFileName($uploadedFile);
        if($newName!=''){
            return $newName;
        }

        //no new file name, return original file name
        return $uploadedFile->getFilename();
    }

    /**
     * Check if file need a new name and return it, otherwise return empty string.
     * @param UploadedFile $uploadedFile
     * @return string
     */
    protected function calcolateNewUploadFileName(UploadedFile $uploadedFile) : string
    {
        if (!$this->getUploadOptionsOrDefault()->appendModelIdSuffixInUploadedFileName) {
            return '';
        }

        //retrive original file name and extension
        $filenameWithoutExtension = UploadedFileHelper::getFilenameWithoutExtension($uploadedFile);
        $ext = $uploadedFile->getClientOriginalExtension();

        $newName = $filenameWithoutExtension . $this->getUploadOptionsOrDefault()->uploadFileNameSuffixSeparator . $this->id . '.' . $ext;
        return $newName;
    }

    /**
     * delete all Uploaded Files
     */
    public function deleteUploadedFiles()
    {
        //loop for every upload model attributes
        foreach ($this->getUploadOptionsOrDefault()->uploads as $uploadField) {
            $this->deleteUploadedFile($uploadField);
        }
    }

    /**
     * Delete upload file related to passed attribute name
     * @param string $uploadField
     */
    public function deleteUploadedFile(string $uploadField)
    {
        //if empty prop exit
        if (!$uploadField) {
            return;
        }

        //if a blank attribute value skip it
        if (!$this->{$uploadField}) {
            return;
        }

        //retrive correct upload storage path for current attribute
        $uploadFieldPath = $this->getUploadFilePath($uploadField);

        //unlink file
        $path = sprintf("%s/%s", $uploadFieldPath, $this->{$uploadField});
        FileHelper::unlinkSafe($path);

        //reset model attribute and update db field
        $this->setBlanckAttributeAndDB($uploadField);
    }

    /**
     * Reset model attribute and update db field
     * @param string $uploadField
     */
    protected function setBlanckAttributeAndDB(string $uploadField)
    {
        //set to black attribute
        $this->{$uploadField} = '';

        //save on db (not call model save because invoke event and entering in loop)
        DB::table($this->getTable())
            ->where('id', $this->id)
            ->update([$uploadField => '']);
    }

    /**
     * Return true If All Upload atrributes Are Empty or
     * if the uploads array is not set.
     * @return bool
     */
    public function checkIfAllUploadFieldsAreEmpty() : bool
    {
        foreach ($this->getUploadsAttributesSafe() as $uploadField) {
            //for performance if one attribute has value exit false
            if ($this-> {$uploadField}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check all attributes upload path, and try to create dir if not already exists.
     * Return false if it fails to create all founded dirs.
     * @return bool
     */
    public function checkOrCreateAllUploadBasePaths() : bool
    {
        foreach ($this->getUploadsAttributesSafe() as $uploadField) {
            if (!$this->checkOrCreateUploadBasePath($uploadField)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check uploads property and return a uploads class field
     * or empty array if somethings wrong.
     * @return array
     */
    public function getUploadsAttributesSafe() : array
    {
        if (!is_array($this->getUploadOptionsOrDefault()->uploads)) {
            return [];
        }

        return $this->getUploadOptionsOrDefault()->uploads;
    }

    /**
     * Check attribute upload path, and try to create dir if not already exists.
     * Return false if it fails to create the dir.
     * @param string $uploadField
     * @return bool
     */
    public function checkOrCreateUploadBasePath(string $uploadField) : bool
    {
        $uploadFieldPath = $this->getUploadFilePath($uploadField);

        return FileHelper::checkDirExistOrCreate($uploadFieldPath, $this->getUploadOptionsOrDefault()->uploadCreateDirModeMask);
    }

    /**
     * Return the upload path for the passed attribute and try to create it if not exists.
     * Returns empty string if dir if not exists and fails to create it.
     * @param string $uploadField
     * @return string
     */
    public function getUploadFilePath(string $uploadField) : string
    {
        //default model upload path
        $uploadFieldPath = $this->getUploadOptionsOrDefault()->uploadBasePath;

        //overwrite if there is specific path for the field
        $specificPath = $this->getUploadFilePathSpecific($uploadField);
        if($specificPath!=''){
            $uploadFieldPath = $specificPath;
        }

        //check if exists or try to create dir
        if(!FileHelper::checkDirExistOrCreate($uploadFieldPath,$this->getUploadOptionsOrDefault()->uploadCreateDirModeMask)){
            return '';
        }

        return $uploadFieldPath;
    }

    /**
     * Return the specific upload path (by uploadPaths prop) for the passed attribute if exists.
     * @param string $uploadField
     * @return string
     */
    public function getUploadFilePathSpecific(string $uploadField) : string
    {
        //check if there is a specified upload path
        if ($this->getUploadOptionsOrDefault()->uploadPaths && count($this->getUploadOptionsOrDefault()->uploadPaths) > 0 && array_key_exists($uploadField,
        $this->getUploadOptionsOrDefault()->uploadPaths)
        ) {
            return public_path($this->getUploadOptionsOrDefault()->uploadPaths[$uploadField]);
        }

        return '';
    }

    /**
     * Calcolate the new name for ALL uploaded files and set relative upload attributes
     */
    public function generateAllNewUploadFileNameAndSetAttribute()
    {
        foreach ($this->getUploadsAttributesSafe() as $uploadField) {
            $this->generateNewUploadFileNameAndSetAttribute($uploadField);
        }
    }

    /**
     * Calcolate the new name for uploaded file relative to passed attribute name and set the upload attribute
     * @param string $uploadField
     */
    public function generateNewUploadFileNameAndSetAttribute(string $uploadField)
    {
        if($uploadField==''){
            return;
        }

        //generate new file name
        $uploadedFile = $this->getCurrentRequestFileSafe($uploadField);
        $newName = $this->generateNewUploadFileName($uploadedFile, $uploadField);
        if($newName==''){
            return;
        }

        //set attribute
        $this->{$uploadField} = $newName;
    }
}
