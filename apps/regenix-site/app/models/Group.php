<?php
namespace models;

use framework\mvc\IHandleBeforeSave;
use models\mixins\TSaveAndUpdate;
use models\mixins\TTimeInformation;
use modules\mongodb\ActiveRecord;

/**
 * Class Group
 * @collection groups
 * @package models
 */
class Group extends ActiveRecord implements IHandleBeforeSave {

    use TTimeInformation;

    /**
     * @id
     * @var string
     */
    public $code;

    /** @var string */
    public $name;

    /**
     * @column acc
     * @var array
     */
    public $access = array();

    public function onBeforeSave($isNew){
        $this->initTimeInformation($isNew);
    }
}