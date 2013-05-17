<?php
namespace models\mixins;

abstract class TTimeHandle {

    const type = __CLASS__;

    public static function onBeforeSave($self, $isNew){
       if ($isNew)
           $self->created = new \DateTime();
    }
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
        $class::addHandle('beforeSave', TTimeHandle::type . '::onBeforeSave');
    }
}