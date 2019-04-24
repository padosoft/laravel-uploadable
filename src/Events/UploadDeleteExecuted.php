<?php


namespace Padosoft\Uploadable\Events;

use Illuminate\Database\Eloquent\Model;

class UploadDeleteExecuted
{
    /**
     * @var String
     */
    public $attributeName;
    /**
     * @var String
     */
    public $attributeValue;
    /**
     * @var Model
     */
    public $target;

    public function __construct($attributeName, $attributeValue, $target)
    {
        $this->attributeName = $attributeName;
        $this->attributeValue = $attributeValue;
        $this->target = $target;
    }
}
