<?php
namespace models;

use framework\mvc\IHandleBeforeSave;
use modules\mongodb\ActiveRecord;

/**
 * Class Group
 * @collection groups
 * @package models
 */
class Group extends ActiveRecord implements IHandleBeforeSave {

    /**
     * @id
     * @var string
     */
    public $code;

    /** @var string */
    public $name;

    /**
     * @column crd
     * @var \DateTime
     */
    public $created;

    /**
     * @column upd
     * @var \DateTime
     */
    public $updated;

    /**
     * @column acc
     * @var array
     */
    public $access = array();

    public function onBeforeSave($isNew){
        if ($isNew)
            $this->created = new \DateTime();
    }
}