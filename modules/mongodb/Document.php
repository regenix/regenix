<?php
namespace modules\mongodb;

use framework\exceptions\CoreException;
use framework\lang\IClassInitialization;
use framework\lang\String;
use framework\modules\AbstractModule;
use framework\mvc\AbstractModel;

abstract class Document extends AbstractModel {

    const type = __CLASS__;

    public function __construct(){

        $service = static::getService();
        $meta    = $service->getMeta();

        foreach($meta['fields'] as $name => $info){
            $this->__data[ $name ] = $this->{$name};
            unset($this->{$name});
        }
    }

    /**
     * @return Service
     */
    public static function getService(){
        return Service::get(get_called_class());
    }

    // handle, call on first load class
    public static function initialize(){

        /** @var $module Module */
        $module = Module::getCurrent();
        $module->initConnection();

        parent::initialize();
    }
}