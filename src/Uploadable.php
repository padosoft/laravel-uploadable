<?php

namespace Padosoft\Uploadable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use DB;

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
    protected function getUploadOptionsOrDefault() : UploadOptions
    {
        if (method_exists($this, 'getUploadOptions')) {
            return $this->getUploadOptions();
        } else {
            return UploadOptions::create()->getUploadOptionsDefault()
                ->setUploadBasePath(public_path('upload/' . $this->getTable()));
        }
    }

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     * @throws InvalidOption
     */
    protected function guardAgainstInvalidUploadOptions()
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
        if (!$this->id || $this->id < 1) {
            return;
        }

        //current request has not uploaded files
        if (!$this->currentRequestHasFiles()) {
            return;
        }

        //ensure that all upload path are ok or create it.
        if (!$this->checkOrCreateAllUploadBasePaths()) {
            return;
        }

        //loop for every upload model attributes and do upload if has a file in request
        foreach ($this->getUploadOptionsOrDefault()->uploads as $uploadField) {
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
        if (!request()->hasFile($uploadField)) {
            return;
        }

        // Check if uploaded File is valid
        if (!$this->checkUploadFileIsValid($uploadField, $this->getUploadOptionsOrDefault()->uploadsMimeType,
            request())
        ) {
            return;
        }

        //all ok => do upload
        $this->doUpload(request()->file($uploadField), $uploadField);
    }

    /**
     * Check if uploaded File is valid and has a valid Mime Type.
     * @param string $uploadField
     * @param array $arrMimeType
     * @param Request|null $request
     * @return bool
     */
    public function checkUploadFileIsValid(string $uploadField, array $arrMimeType = array(), Request $request = null)
    {
        if (!$request) {
            $request = request();
        }

        //if request is null, get the current request.
        if (!$request) {
            return false;
        }

        //retrive files
        $uploadedFile = $request->file($uploadField);

        //check if is valid file
        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return false;
        }

        // Check if uploaded File has a correct MimeType if specified.
        if ($arrMimeType && count($arrMimeType) > 0 && !in_array($uploadedFile->getMimeType(), $arrMimeType)) {
            return false;
        }

        return true;
    }

    /**
     * Get an UploadedFile, generate new name, and save it in destination path.
     * Return empty string if it fails.
     * @param UploadedFile $uploadedFile
     * @param string $uploadAttribute
     * @return string
     */
    public function doUpload(UploadedFile $uploadedFile, $uploadAttribute)
    {
        if (!$this->id || $this->id < 1) {
            return '';
        }

        if (!$uploadedFile) {
            return '';
        }

        //generate new file name
        $newName = $this->{$uploadAttribute};

        //get upload path to store
        $pathToStore = $this->getUploadFilePath($uploadAttribute);

        //delete if file already exists
        $this->unlinkSafe($pathToStore . '/' . $newName);

        //move uploaded file to destination folder
        try {
            $targetFile = $uploadedFile->move($pathToStore, $newName);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
            return '';
        }

        return $targetFile ? $newName : '';
    }

    /**
     * Generate a new file name for uploaded file.
     * Return empty string if $uploadedFile is null.
     * @param UploadedFile $uploadedFile
     * @param string $uploadField
     * @return string
     */
    public function generateNewUploadFileName(UploadedFile $uploadedFile, string $uploadField)
    {
        if (!$uploadedFile) {
            return '';
        }

        $newName = $uploadedFile->getFilename();

        if ($this->getUploadOptionsOrDefault()->appendModelIdSuffixInUploadedFileName) {
            //retrive original file name and extension
            $filenameWithoutExtension = $this->getFilenameWithoutExtension($uploadedFile);
            $ext = $uploadedFile->getClientOriginalExtension();

            $newName = $filenameWithoutExtension . $this->getUploadOptionsOrDefault()->uploadFileNameSuffixSeparator . $this->id . '.' . $ext;
        }

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
        $this->unlinkSafe($path);

        //set to black attribute
        $this->{$uploadField} = '';

        //save on db (not call $this->save() because invoke event and entering in loop)
        DB::table($this->getTable())
            ->where('id', $this->id)
            ->update([$uploadField => '']);
    }

    /**
     * Return true If All Upload atrributes Are Empty or
     * if the uploads array is not set.
     * @return bool
     */
    public function checkIfAllUploadFieldsAreEmpty()
    {
        if (!$this->getUploadOptionsOrDefault()->uploads) {
            true;
        }
        foreach ($this->getUploadOptionsOrDefault()->uploads as $uploadField) {
            //for performance if one attribute has value exit false
            if ($this->{$uploadField}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the current request has at least one file
     * @return bool
     */
    public function currentRequestHasFiles()
    {
        return $this->requestHasFiles(request());
    }

    /**
     * Check if the passed request has at least one file
     * @param Request $request
     * @return bool
     */
    public function requestHasFiles(Request $request)
    {
        return ($request && $request->allFiles() && count($request->allFiles()) > 0);
    }

    /**
     * Check all attributes upload path, and try to create dir if not already exists.
     * Return false if it fails to create all founded dirs.
     * @return bool
     */
    public function checkOrCreateAllUploadBasePaths()
    {
        //exit if trait uploads attribute not set
        if (!$this->getUploadOptionsOrDefault()->uploads) {
            return true;
        }

        //loop for every model attributes upload pats and try to create it if not exists
        foreach ($this->getUploadOptionsOrDefault()->uploads as $uploadField) {
            if (!$this->checkOrCreateUploadBasePath($uploadField)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check attribute upload path, and try to create dir if not already exists.
     * Return false if it fails to create the dir.
     * @param string $uploadField
     * @return bool
     */
    public function checkOrCreateUploadBasePath(string $uploadField)
    {
        if (!$this->getUploadOptionsOrDefault()->uploads) {
            return true;
        }

        $uploadFieldPath = $this->getUploadFilePath($uploadField);

        return $this->checkDirExistOrCreate($uploadFieldPath,
            $this->getUploadOptionsOrDefault()->uploadCreateDirModeMask);
    }

    /**
     * Return the upload path for the passed attribute.
     * @param string $uploadField
     * @return string
     */
    public function getUploadFilePath(string $uploadField)
    {
        //default model upload path
        $uploadFieldPath = $this->getUploadOptionsOrDefault()->uploadBasePath;

        //check if there is a specified upload path
        if ($this->getUploadOptionsOrDefault()->uploadPaths && count($this->getUploadOptionsOrDefault()->uploadPaths) > 0 && array_key_exists($uploadField,
                $this->getUploadOptionsOrDefault()->uploadPaths)
        ) {
            $uploadFieldPath = public_path($this->getUploadOptionsOrDefault()->uploadsPaths[$uploadField]);
        }

        //check if exists or try to create dir
        if(!$this->checkDirExistOrCreate($uploadFieldPath,$this->getUploadOptionsOrDefault()->uploadCreateDirModeMask)){
            $uploadFieldPath = '';
        }

        return $uploadFieldPath;
    }

    /**
     * Check if passed path exists or try to create it.
     * Return false if it fails to create it.
     * @param string $uploadFieldPath
     * @param string $modeMask
     * @return bool
     */
    public function checkDirExistOrCreate(string $uploadFieldPath, string $modeMask)
    {
        if (!$uploadFieldPath) {
            return false;
        }

        return file_exists($uploadFieldPath)
        || (mkdir($uploadFieldPath, $modeMask, true) && is_dir($uploadFieldPath));
    }

    /**
     * Return the file name of uploaded file (without path and witout extension).
     * Ex.: \public\upload\pippo.txt ritorna 'pippo'
     * @param UploadedFile $uploadedFile
     * @return string
     */
    public function getFilenameWithoutExtension(UploadedFile $uploadedFile)
    {
        return pathinfo($uploadedFile->getClientOriginalName())['filename'];
    }

    /**
     * unlink file if exists.
     * Return false if exists and unlink fails.
     * @param string $filePath
     * @return bool
     */
    public function unlinkSafe(string $filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        return unlink($filePath);
    }

    /**
     * Calcolate the new name for ALL uploaded files and set relative upload attributes
     */
    public function generateAllNewUploadFileNameAndSetAttribute()
    {
        if (!$this->getUploadOptionsOrDefault()->uploads) {
            return;
        }
        //loop for every upload model attributes and do work if has a file in request
        foreach ($this->getUploadOptionsOrDefault()->uploads as $uploadField) {
            $this->generateNewUploadFileNameAndSetAttribute($uploadField);
        }
    }

    /**
     * Calcolate the new name for uploaded file relative to passed attribute name and set the upload attribute
     * @param string $uploadField
     */
    public function generateNewUploadFileNameAndSetAttribute(string $uploadField)
    {
        //check if there is a file in request for current attribute
        if (!request()->hasFile($uploadField)) {
            return;
        }

        // Check if uploaded File is valid
        if (!$this->checkUploadFileIsValid($uploadField, $this->getUploadOptionsOrDefault()->uploadsMimeType,
            request())
        ) {
            return;
        }

        //generate new file name
        $uploadedFile = request()->file($uploadField);
        $newName = $this->generateNewUploadFileName($uploadedFile, $uploadField);

        //set attribute
        $this->{$uploadField} = $newName;
    }
}
