<?php

namespace Padosoft\Uploadable;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Padosoft\Io\DirHelper;
use Padosoft\Io\FileHelper;
use Padosoft\Laravel\Request\RequestHelper;
use Padosoft\Laravel\Request\UploadedFileHelper;

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
     * Determine if the model or given attribute(s) have been modified.
     * Implemented in \Illuminate\Database\Eloquent\Model
     *
     * @param array|string|null $attributes = null
     *
     * @return bool
     */
    abstract public function isDirty($attributes = null);

    /**
     * Get the model's original attribute values.
     * Implemented in \Illuminate\Database\Eloquent\Model
     *
     * @param string|null $key = null
     * @param mixed $default = null
     *
     * @return mixed|array
     */
    abstract public function getOriginal($key = null, $default = null);

    /**
     * Boot the trait.
     */
    public static function bootUploadable()
    {
        self::bindCreateEvents();
        self::bindSaveEvents();
        self::bindUpdateEvents();
        self::bindDeleteEvents();
    }

    /**
     * Bind create model events.
     */
    protected static function bindCreateEvents()
    {
        static::creating(function ($model) {
            $model->uploadOptions = $model->getUploadOptionsOrDefault();
            $model->guardAgainstInvalidUploadOptions();
        });
    }

    /**
     * Bind save model events.
     */
    protected static function bindSaveEvents()
    {
        static::saved(function (Model $model) {
            $model->uploadFiles();
        });
    }

    /**
     * Bind update model events.
     */
    protected static function bindUpdateEvents()
    {
        static::updating(function (Model $model) {
            //check if there is old files to delete
            $model->checkIfNeedToDeleteOldFiles();
        });
    }

    /**
     * Bind delete model events.
     */
    protected static function bindDeleteEvents()
    {
        static::deleting(function (Model $model) {
            $model->uploadOptions = $model->getUploadOptionsOrDefault();
            $model->guardAgainstInvalidUploadOptions();
            //check if there is old files to delete
            $model->checkIfNeedToDeleteOldFiles();
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
        if ($this->uploadOptions) {
            return $this->uploadOptions;
        }

        if (method_exists($this, 'getUploadOptions')) {
            $method = 'getUploadOptions';
            $this->uploadOptions = $this->{$method}();
        } else {
            $this->uploadOptions = UploadOptions::create()->getUploadOptionsDefault()
                ->setUploadBasePath(public_path('upload/' . $this->getTable()));
        }

        return $this->uploadOptions;
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
        if ($this->uploadOptions->uploadBasePath === null || $this->uploadOptions->uploadBasePath == '') {
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
        //check if there is a valid file in request for current attribute
        if (!RequestHelper::isValidCurrentRequestUploadFile($uploadField,
            $this->getUploadOptionsOrDefault()->uploadsMimeType)
        ) {
            return;
        }

        //retrive the uploaded file
        $uploadedFile = RequestHelper::getCurrentRequestFileSafe($uploadField);
        if ($uploadedFile === null) {
            return;
        }

        //calcolate new file name and set it to model attribute
        $this->generateNewUploadFileNameAndSetAttribute($uploadField);

        //do the work
        $newName = $this->doUpload($uploadedFile, $uploadField);

        //save on db (not call model save because invoke event and entering in loop)
        $this->updateDb($uploadField, $newName);
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
        if (!$uploadAttribute) {
            return '';
        }

        //get file name by attribute
        $newName = $this->{$uploadAttribute};

        //get upload path to store (method create dir if not exists and return '' if it failed)
        $pathToStore = $this->getUploadFileBasePath($uploadAttribute);

        //delete if file already exists
        FileHelper::unlinkSafe(DirHelper::njoin($pathToStore, $newName));

        //move file to destination folder
        try {
            $targetFile = $uploadedFile->move($pathToStore, $newName);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
            $targetFile = null;
            Log::warning('Error in doUpload() when try to move ' . $newName . ' to folder: ' . $pathToStore . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }

        return $targetFile ? $newName : '';
    }

    /**
     * Check request for valid files and server for correct paths defined in model
     * @return bool
     */
    public function requestHasValidFilesAndCorrectPaths() : bool
    {
        //current request has not uploaded files
        if (!RequestHelper::currentRequestHasFiles()) {
            return false;
        }

        //ensure that all upload path are ok or create it.
        if (!$this->checkOrCreateAllUploadBasePaths()) {
            return false;
        }

        return true;
    }

    /**
     * Generate a new file name for uploaded file.
     * Return empty string if uploadedFile is null, otherwise return the new file name..
     * @param UploadedFile $uploadedFile
     * @return string
     * @internal param string $uploadField
     */
    public function generateNewUploadFileName(UploadedFile $uploadedFile) : string
    {
        if (!$this->id && $this->getUploadOptionsOrDefault()->appendModelIdSuffixInUploadedFileName) {
            return '';
        }

        //check if file need a new name
        $newName = $this->calcolateNewUploadFileName($uploadedFile);
        if ($newName != '') {
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
    public function calcolateNewUploadFileName(UploadedFile $uploadedFile) : string
    {
        if (!$this->getUploadOptionsOrDefault()->appendModelIdSuffixInUploadedFileName) {
            return '';
        }

        //retrive original file name and extension
        $filenameWithoutExtension = UploadedFileHelper::getFilenameWithoutExtension($uploadedFile);
        $ext = $uploadedFile->getClientOriginalExtension();

        $newName = $filenameWithoutExtension . $this->getUploadOptionsOrDefault()->uploadFileNameSuffixSeparator . $this->id . '.' . $ext;
        return sanitize_filename($newName);
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
        if (!$uploadField) {
            return;
        }

        if (!$this->{$uploadField}) {
            return;
        }

        //retrive correct upload storage path for current attribute
        $uploadFieldPath = $this->getUploadFileBasePath($uploadField);

        //unlink file
        $path = DirHelper::njoin($uploadFieldPath, $this->{$uploadField});
        FileHelper::unlinkSafe($path);

        //reset model attribute and update db field
        $this->setBlanckAttributeAndDB($uploadField);
    }

    /**
     * Reset model attribute and update db field
     * @param string $uploadField
     */
    public function setBlanckAttributeAndDB(string $uploadField)
    {
        if (!$uploadField) {
            return;
        }

        //set to black attribute
        $this->{$uploadField} = '';

        //save on db (not call model save because invoke event and entering in loop)
        $this->updateDb($uploadField, '');
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
            if ($uploadField && $this->{$uploadField}) {
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
        $uploadFieldPath = $this->getUploadFileBasePath($uploadField);

        return $uploadFieldPath == '' ? '' : DirHelper::checkDirExistOrCreate($uploadFieldPath,
            $this->getUploadOptionsOrDefault()->uploadCreateDirModeMask);
    }

    /**
     * Return the upload path for the passed attribute and try to create it if not exists.
     * Returns empty string if dir if not exists and fails to create it.
     * @param string $uploadField
     * @return string
     */
    public function getUploadFileBasePath(string $uploadField) : string
    {
        //default model upload path
        $uploadFieldPath = DirHelper::canonicalize($this->getUploadOptionsOrDefault()->uploadBasePath);

        //overwrite if there is specific path for the field
        $specificPath = $this->getUploadFileBasePathSpecific($uploadField);
        if ($specificPath != '') {
            $uploadFieldPath = $specificPath;
        }

        //check if exists or try to create dir
        if (!DirHelper::checkDirExistOrCreate($uploadFieldPath,
            $this->getUploadOptionsOrDefault()->uploadCreateDirModeMask)
        ) {
            return '';
        }

        return $uploadFieldPath;
    }

    /**
     * Return the specific upload path (by uploadPaths prop) for the passed attribute if exists,
     * otherwise return empty string.
     * If uploadPaths for this field is relative, a public_path was appended.
     * @param string $uploadField
     * @return string
     */
    public function getUploadFileBasePathSpecific(string $uploadField) : string
    {
        //check if there is a specified upload path for this field
        if (!$this->hasUploadPathSpecifiedForThisField($uploadField)) {
            return '';
        }

        $path = $this->getUploadOptionsOrDefault()->uploadPaths[$uploadField];

        //if path is relative, adjust it by public puth.
        if (DirHelper::isRelative($path)) {
            $path = public_path($path);
        }

        return DirHelper::canonicalize($path);
    }

    /**
     * Check if there is a specified upload path setted for this field
     * @param string $uploadField
     * @return bool
     */
    public function hasUploadPathSpecifiedForThisField(string $uploadField) : bool
    {
        return !(empty($this->getUploadOptionsOrDefault()->uploadPaths)
            || count($this->getUploadOptionsOrDefault()->uploadPaths) < 1
            || !array_key_exists($uploadField, $this->getUploadOptionsOrDefault()->uploadPaths)
        );
    }

    /**
     * Return the full (path+filename) upload abs path for the passed attribute.
     * Returns empty string if dir if not exists.
     * @param string $uploadField
     * @return string
     */
    public
    function getUploadFileFullPath(
        string $uploadField
    ) : string
    {
        $uploadFieldPath = $this->getUploadFileBasePath($uploadField);
        $uploadFieldPath = DirHelper::canonicalize(DirHelper::addFinalSlash($uploadFieldPath) . $this->{$uploadField});

        if ($this->isSlashOrEmptyDir($uploadFieldPath)) {
            return '';
        }

        return $uploadFieldPath;
    }

    /**
     * Return the full url (base url + filename) for the passed attribute.
     * Returns empty string if dir if not exists.
     * Ex.:  http://localhost/laravel/public/upload/news/pippo.jpg
     * @param string $uploadField
     * @return string
     */
    public
    function getUploadFileUrl(
        string $uploadField
    ) : string
    {
        $Url = $this->getUploadFileFullPath($uploadField);

        $uploadFieldPath = DirHelper::canonicalize($this->removePublicPath($Url));

        return $uploadFieldPath == '' ? '' : URL::to($uploadFieldPath);
    }

    /**
     * get a path and remove public_path.
     * @param string $path
     * @return string
     */
    public
    function removePublicPath(
        string $path
    ) : string
    {
        if ($path == '') {
            return '';
        }
        $path = str_replace(DirHelper::canonicalize(public_path()), '', DirHelper::canonicalize($path));
        if ($this->isSlashOrEmptyDir($path)) {
            return '';
        }

        return $path;
    }

    /**
     * Return the base url (without filename) for the passed attribute.
     * Returns empty string if dir if not exists.
     * Ex.:  http://localhost/laravel/public/upload/
     * @param string $uploadField
     * @return string
     */
    public
    function getUploadFileBaseUrl(
        string $uploadField
    ) : string
    {
        $uploadFieldPath = $this->getUploadFileBasePath($uploadField);

        $uploadFieldPath = DirHelper::canonicalize(DirHelper::addFinalSlash($this->removePublicPath($uploadFieldPath)));

        if ($this->isSlashOrEmptyDir($uploadFieldPath)) {
            return '';
        }

        return URL::to($uploadFieldPath);
    }

    /**
     * Calcolate the new name for ALL uploaded files and set relative upload attributes
     */
    public
    function generateAllNewUploadFileNameAndSetAttribute()
    {
        foreach ($this->getUploadsAttributesSafe() as $uploadField) {
            $this->generateNewUploadFileNameAndSetAttribute($uploadField);
        }
    }

    /**
     * Calcolate the new name for uploaded file relative to passed attribute name and set the upload attribute
     * @param string $uploadField
     */
    public
    function generateNewUploadFileNameAndSetAttribute(
        string $uploadField
    ) {
        if (!trim($uploadField)) {
            return;
        }

        //generate new file name
        $uploadedFile = RequestHelper::getCurrentRequestFileSafe($uploadField);
        if ($uploadedFile === null) {
            return;
        }
        $newName = $this->generateNewUploadFileName($uploadedFile);
        if ($newName == '') {
            return;
        }

        //set attribute
        $this->{$uploadField} = $newName;
    }

    /**
     * @param string $uploadField
     * @param string $newName
     */
    public
    function updateDb(
        string $uploadField,
        string $newName
    ) {
        if ($this->id < 1) {
            return;
        }
        DB::table($this->getTable())
            ->where('id', $this->id)
            ->update([$uploadField => $newName]);
    }

    /**
     * @param string $path
     * @return bool
     */
    public
    function isSlashOrEmptyDir(
        string $path
    ):bool
    {
        return isNullOrEmpty($path) || $path == '\/' || $path == DIRECTORY_SEPARATOR;
    }

    /**
     * Check if all uploadedFields are changed, so there is an old value
     * for attribute and if true try to unlink old file.
     */
    public
    function checkIfNeedToDeleteOldFiles()
    {
        foreach ($this->getUploadsAttributesSafe() as $uploadField) {
            $this->checkIfNeedToDeleteOldFile($uploadField);
        }
    }

    /**
     * Check if $uploadField are changed, so there is an old value
     * for $uploadField attribute and if true try to unlink old file.
     * @param string $uploadField
     */
    public
    function checkIfNeedToDeleteOldFile(
        string $uploadField
    ) {
        if (isNullOrEmpty($uploadField)
            || !$this->isDirty($uploadField)
            || ($this->{$uploadField} == $this->getOriginal($uploadField))
            || isNullOrEmpty($this->getOriginal($uploadField))
        ) {
            return;
        }

        $oldValue = $this->getOriginal($uploadField);

        FileHelper::unlinkSafe(DirHelper::njoin($this->getUploadFileBasePath($uploadField), $oldValue));
    }
}
