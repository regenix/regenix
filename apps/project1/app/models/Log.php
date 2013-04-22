<?php
namespace models;

use modules\mongodb\Document;


class Log extends Document {

    /**
     * @id
     */
    public $id;

    /** @var timestamp */
    public $upd;

    /** @var string */
    public $message;


    public static function add($message){
        $log = new Log();
        $log->message = $message;
        return $log->save();
    }
}