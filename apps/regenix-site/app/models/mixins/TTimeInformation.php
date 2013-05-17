<?php
namespace models\mixins;

function TTimeInformation_callBeforeSave($self, $isNew){
    if ($isNew)
        $self->created = new \DateTime();
}

trait TTimeInformation {

    /**
     * @column crd
     * @var \DateTime
     */
    public $created;

    /**
     * @column upd
     * @timestamp
     * @var \DateTime
     */
    public $updated;

    public static function TTimeInformation_initialize($class){
        $class::addHandle('beforeSave', __NAMESPACE__ . '\\TTimeInformation_callBeforeSave');
    }
}