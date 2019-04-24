<?php


namespace Padosoft\Uploadable\Events;

use Illuminate\Database\Eloquent\Model;

class UploadExecuted
{
    /**
     * @var String
     */
    public $attributeName;
    /**
     * @var Model
     */
    public $target;

    public function __construct($attributeName, $target)
    {
        $this->attributeName = $attributeName;
        $this->target = $target;
    }
}
