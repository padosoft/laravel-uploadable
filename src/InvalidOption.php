<?php

namespace Padosoft\Uploadable;

use Exception;

class InvalidOption extends Exception
{
    public static function missingUploadFields()
    {
        return new static('Could not determinate which fields should be treat as upload attribute or these fields are empty');
    }
    public static function missingUploadBasePath()
    {
        return new static('Could not determinate uploadBasePath or this option is empty.');
    }
}
