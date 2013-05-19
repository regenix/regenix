<?php
namespace framework\exceptions;


class JsonFileException extends ParseFileException {

    public function __construct($file){
        $error = json_last_error();
        switch($error){
            case JSON_ERROR_DEPTH: $msg = 'maximum stack depth has been exceeded'; break;
            case JSON_ERROR_STATE_MISMATCH: $msg = 'invalid or malformed JSON'; break;
            case JSON_ERROR_CTRL_CHAR: $msg = 'control character error, possibly incorrectly encoded'; break;
            case JSON_ERROR_SYNTAX: $msg = 'syntax error'; break;
            /*JSON_ERROR_UTF8*/ case 5: $msg = 'malformed UTF-8 characters, possibly incorrectly encoded'; break;
        }
        parent::__construct($file, 'json: ' . $msg);
    }
}